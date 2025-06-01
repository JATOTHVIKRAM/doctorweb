<?php
session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    error_log("Delete attempt failed: Admin not logged in.");
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if patient_id is provided
if (!isset($_POST['patient_id'])) {
    error_log("Delete attempt failed: Patient ID not provided.");
    echo json_encode(['success' => false, 'message' => 'Patient ID is required']);
    exit();
}

$patient_id = $_POST['patient_id'];
error_log("Attempting to delete patient ID: " . $patient_id . " (Prescription -> Patients order).");

// Database connection
$conn = new mysqli("localhost", "root", "Vikram@2005", "hospital_database");

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

try {
    error_log("Starting transaction for patient ID: " . $patient_id);
    // Start transaction
    $conn->begin_transaction();

    // 1. Delete from prescription table
    error_log("Attempting to delete from prescription for patient ID: " . $patient_id);
    $stmt = $conn->prepare("DELETE FROM prescription WHERE patient_id = ?");
    if (!$stmt) throw new Exception("Failed to prepare statement for prescription: " . $conn->error);
    $stmt->bind_param("i", $patient_id);
    if (!$stmt->execute()) throw new Exception("Error deleting from prescription: " . $stmt->error);
    error_log("Deleted from prescription (affected rows: " . $stmt->affected_rows . ")");
    $stmt->close();

    // 2. Finally delete from patients table (parent table)
    error_log("Attempting to delete from parent table: patients for patient ID: " . $patient_id);
    $stmt = $conn->prepare("DELETE FROM patients WHERE patient_id = ?");
    if (!$stmt) throw new Exception("Failed to prepare statement for patients: " . $conn->error);
    $stmt->bind_param("i", $patient_id);
    
    if (!$stmt->execute()) {
         error_log("Error deleting from patients table: " . $stmt->error);
         throw new Exception("Failed to delete patient from main table: " . $stmt->error); // Include SQL error
    }

    if ($stmt->affected_rows === 0) {
        // This case means the patient didn't exist in the patients table, but prescription records might have been deleted.
        error_log("Patient ID " . $patient_id . " not found in patients table, but transaction will commit as prescription records (if any) were deleted.");
    } else {
         error_log("Successfully deleted patient ID: " . $patient_id . " from patients table.");
    }
    $stmt->close();

    // Commit transaction
    $conn->commit();
    error_log("Transaction committed successfully for patient ID: " . $patient_id);
    
    echo json_encode(['success' => true, 'message' => 'Patient deleted successfully']);

} catch (Exception $e) {
    error_log("Error during deletion for patient ID: " . $patient_id . ". Message: " . $e->getMessage());
    // Rollback transaction on error
    $conn->rollback();
    error_log("Transaction rolled back for patient ID: " . $patient_id);
    // Send back a more specific error message
    echo json_encode(['success' => false, 'message' => 'Error deleting patient: ' . $e->getMessage()]); 
} finally {
    $conn->close();
    error_log("Database connection closed for patient ID: " . $patient_id);
}
?> 