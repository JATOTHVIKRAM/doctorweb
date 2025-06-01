<?php
session_start();
header('Content-Type: application/json');

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json);

if (!$data || !isset($data->username) || !isset($data->password)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$username = $data->username;
$password = $data->password;

// Database connection
$conn = new mysqli("localhost", "root", "Vikram@2005", "hospital_database");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Prepare and execute query
$stmt = $conn->prepare("SELECT doctor_id, email, username, full_name FROM doctors WHERE username = ? AND password = ?");
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $doctor = $result->fetch_assoc();
    
    // Set session variables
    $_SESSION['doctor_id'] = $doctor['doctor_id'];
    $_SESSION['doctor_email'] = $doctor['email'];
    $_SESSION['doctor_username'] = $doctor['username'];
    $_SESSION['doctor_name'] = $doctor['full_name'];
    $_SESSION['doctor'] = true;
    $_SESSION['last_activity'] = time();
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
}

$stmt->close();
$conn->close();
?> 