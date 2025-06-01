<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

try {
    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['doctor_id'])) {
        throw new Exception('Doctor ID is required');
    }

    // Required fields validation
    $required_fields = ['full_name', 'email', 'phone', 'gender', 'qualifications', 'username'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            throw new Exception("$field is required");
        }
    }

    // Email validation
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Phone validation (10-15 digits)
    if (!preg_match('/^\d{10,15}$/', $data['phone'])) {
        throw new Exception('Phone number must be between 10 and 15 digits');
    }

    $conn = get_db_connection();
    $conn->begin_transaction();

    // First verify that the doctor exists
    $stmt = $conn->prepare("SELECT doctor_id FROM Doctors WHERE doctor_id = ?");
    $stmt->bind_param("i", $data['doctor_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception('Doctor not found');
    }
    $stmt->close();

    // Check if email exists for other doctors
    $stmt = $conn->prepare("SELECT doctor_id FROM Doctors WHERE email = ? AND doctor_id != ?");
    $stmt->bind_param("si", $data['email'], $data['doctor_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        throw new Exception('Email already exists for another doctor');
    }
    $stmt->close();

    // Check if username exists for other doctors
    $stmt = $conn->prepare("SELECT doctor_id FROM Doctors WHERE username = ? AND doctor_id != ?");
    $stmt->bind_param("si", $data['username'], $data['doctor_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        throw new Exception('Username already exists for another doctor');
    }
    $stmt->close();

    // Update doctor data
    $stmt = $conn->prepare("UPDATE Doctors SET 
        full_name = ?, 
        email = ?, 
        phone = ?, 
        gender = ?, 
        qualifications = ?, 
        username = ?,
        updated_at = CURRENT_TIMESTAMP 
        WHERE doctor_id = ?");

    if (!$stmt) {
        throw new Exception('Failed to prepare update query: ' . $conn->error);
    }

    $stmt->bind_param("ssssssi", 
        $data['full_name'],
        $data['email'],
        $data['phone'],
        $data['gender'],
        $data['qualifications'],
        $data['username'],
        $data['doctor_id']
    );

    if (!$stmt->execute()) {
        throw new Exception('Failed to update doctor: ' . $stmt->error);
    }

    $conn->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Doctor updated successfully'
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
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