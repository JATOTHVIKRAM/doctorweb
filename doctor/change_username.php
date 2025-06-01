<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['doctor'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli("localhost", "root", "Vikram@2005", "hospital_database");
    
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    $currentPassword = $_POST['currentPassword'];
    $newUsername = $_POST['newUsername'];
    $doctorId = $_SESSION['doctor']['doctor_id'];
    
    // Verify current password
    $stmt = $conn->prepare("SELECT * FROM Doctors WHERE doctor_id = ? AND password = ?");
    $stmt->bind_param("is", $doctorId, $currentPassword);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit;
    }
    
    // Check if new username already exists
    $stmt = $conn->prepare("SELECT * FROM Doctors WHERE username = ? AND doctor_id != ?");
    $stmt->bind_param("si", $newUsername, $doctorId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username already exists']);
        exit;
    }
    
    // Update username
    $stmt = $conn->prepare("UPDATE Doctors SET username = ? WHERE doctor_id = ?");
    $stmt->bind_param("si", $newUsername, $doctorId);
    
    if ($stmt->execute()) {
        $_SESSION['doctor']['username'] = $newUsername;
        echo json_encode(['success' => true, 'message' => 'Username updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update username']);
    }
    
    $stmt->close();
    $conn->close();
}
?> 