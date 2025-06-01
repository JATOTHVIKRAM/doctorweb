<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Connect to database
$conn = new mysqli("localhost", "root", "Vikram@2005", "hospital_database");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get POST data
$currentPassword = $_POST['currentPassword'];
$newPassword = $_POST['newPassword'];
$doctorId = $_SESSION['doctor_id'];

// Verify current password
$query = "SELECT password FROM doctors WHERE doctor_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();

if (!$doctor || !password_verify($currentPassword, $doctor['password'])) {
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
    exit;
}

// Hash the new password
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Update password
$query = "UPDATE doctors SET password = ? WHERE doctor_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $hashedPassword, $doctorId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update password']);
}

$conn->close();
?> 