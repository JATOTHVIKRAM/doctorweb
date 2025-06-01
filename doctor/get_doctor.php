<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Doctor ID is required');
    }

    $conn = get_db_connection();
    
    // Get doctor data
    $stmt = $conn->prepare("SELECT doctor_id, full_name, email, phone, gender, qualifications, username FROM Doctors WHERE doctor_id = ?");
    if (!$stmt) {
        throw new Exception('Failed to prepare query: ' . $conn->error);
    }

    $stmt->bind_param("i", $_GET['id']);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to fetch doctor data: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Doctor not found');
    }

    $doctor = $result->fetch_assoc();
    echo json_encode($doctor);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
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