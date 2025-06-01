<?php
/**
 * Example script to demonstrate how to mark a patient as completed for today
 * 
 * Usage: 
 * 1. Navigate to http://localhost/test_mark_completed.php?patient_id=22
 * 2. This will mark patient with ID 22 as completed for today's date
 */
require_once 'config.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get patient ID from URL parameter
$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;

if ($patient_id <= 0) {
    echo "Please provide a valid patient_id parameter: test_mark_completed.php?patient_id=X";
    exit;
}

try {
    // Set completion date to current date/time
    $completion_date = date('Y-m-d H:i:s');
    
    // Check if patient already exists in completed_patients table
    $check_query = "SELECT id FROM completed_patients WHERE patient_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $patient_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Patient already marked as completed, update the date
        $update_query = "UPDATE completed_patients SET completed_at = ? WHERE patient_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $completion_date, $patient_id);
        
        if ($update_stmt->execute()) {
            echo "<h3>Success: Completion date updated for patient ID $patient_id</h3>";
            echo "<p>Completion date: $completion_date</p>";
        } else {
            echo "<h3>Error updating record: " . $conn->error . "</h3>";
        }
        $update_stmt->close();
    } else {
        // Insert patient into completed_patients table with completion date
        $insert_query = "INSERT INTO completed_patients (patient_id, completed_at) VALUES (?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("is", $patient_id, $completion_date);
        
        if ($insert_stmt->execute()) {
            echo "<h3>Success: Patient ID $patient_id marked as completed</h3>";
            echo "<p>Completion date: $completion_date</p>";
        } else {
            echo "<h3>Error inserting record: " . $conn->error . "</h3>";
        }
        $insert_stmt->close();
    }
    
    echo "<p>After refreshing the app, this patient should appear in the 'Today Completed' filter.</p>";
    echo "<p>Go back to <a href='javascript:history.back()'>previous page</a></p>";
    
    $check_stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo "<h3>Error: " . $e->getMessage() . "</h3>";
}
?> 