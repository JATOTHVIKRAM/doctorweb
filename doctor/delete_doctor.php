<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

try {
    // Get JSON data from request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['doctor_id'])) {
        throw new Exception('Doctor ID is required');
    }

    $conn = get_db_connection();
    
    // Start transaction
    $conn->begin_transaction();

    try {
        // Delete the doctor
        $stmt = $conn->prepare("DELETE FROM Doctors WHERE doctor_id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare delete query: ' . $conn->error);
        }

        $stmt->bind_param("i", $data['doctor_id']);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to delete doctor: ' . $stmt->error);
        }

        if ($stmt->affected_rows === 0) {
            throw new Exception('Doctor not found');
        }

        // Commit the transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Doctor deleted successfully'
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?> 