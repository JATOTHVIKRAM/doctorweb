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
$newUsername = $_POST['newUsername'];
$currentPassword = $_POST['currentPassword'];
$doctorId = $_SESSION['doctor_id'];

// Check if inputs are empty
if (empty($newUsername) || empty($currentPassword)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

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

// Check if username already exists
$query = "SELECT doctor_id FROM doctors WHERE username = ? AND doctor_id != ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $newUsername, $doctorId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Username already exists']);
    exit;
}

// Update username
$query = "UPDATE doctors SET username = ? WHERE doctor_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $newUsername, $doctorId);

if ($stmt->execute()) {
    // Update session to reflect the new username
    $_SESSION['username'] = $newUsername;
    echo json_encode(['success' => true, 'message' => 'Username updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update username']);
}

$conn->close();
?> 