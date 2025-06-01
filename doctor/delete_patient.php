<?php
require_once 'db_connection.php';
header('Content-Type: application/json');

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['patient_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Patient ID is required']);
    exit;
}

$patient_id = intval($data['patient_id']);

try {
    $conn = get_db_connection();
    
    // Start transaction to ensure all deletes succeed or none does
    $conn->begin_transaction();
    
    try {
        // First delete from completed_patients table (if exists)
        $stmt = $conn->prepare("DELETE FROM completed_patients WHERE patient_id = ?");
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        
        // Then delete from patient_history table
        $stmt = $conn->prepare("DELETE FROM patient_history WHERE patient_id = ?");
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        
        // Finally delete from patients table
        $stmt = $conn->prepare("DELETE FROM patients WHERE patient_id = ?");
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        
        // If we got here, all deletes were successful
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Patient deleted successfully'
        ]);
    } catch (Exception $e) {
        // If any error occurred, roll back the transaction
        $conn->rollback();
        throw $e;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete patient: ' . $e->getMessage()
    ]);
}
?> 