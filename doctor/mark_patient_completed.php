<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
ini_set('display_errors', 0); // Disable error display in production
error_reporting(E_ALL);

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get request body
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    if (!isset($data['patient_id']) || empty($data['patient_id'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Patient ID is required'
        ]);
        exit;
    }
    
    $patient_id = $data['patient_id'];
    
    // Get completion date if provided, otherwise use current timestamp
    $completion_date = isset($data['completion_date']) ? $data['completion_date'] : date('Y-m-d H:i:s');
    
    try {
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
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Completion date updated for patient'
                ]);
            } else {
                throw new Exception("Error updating record: " . $conn->error);
            }
            $update_stmt->close();
        } else {
            // Insert patient into completed_patients table with completion date
            $insert_query = "INSERT INTO completed_patients (patient_id, completed_at) VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("is", $patient_id, $completion_date);
            
            if ($insert_stmt->execute()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Patient marked as completed'
                ]);
            } else {
                throw new Exception("Error inserting record: " . $conn->error);
            }
            $insert_stmt->close();
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    } finally {
        if (isset($check_stmt)) {
            $check_stmt->close();
        }
        $conn->close();
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
}
?> 