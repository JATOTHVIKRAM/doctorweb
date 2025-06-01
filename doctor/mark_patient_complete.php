<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['doctor'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_POST['patient_id'])) {
    echo json_encode(['success' => false, 'message' => 'Patient ID is required']);
    exit;
}

$conn = new mysqli("localhost", "root", "Vikram@2005", "hospital_database");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$patient_id = (int)$_POST['patient_id'];

// Update the patient_history table to mark the visit as completed
$query = "UPDATE patient_history 
          SET is_completed = 1 
          WHERE patient_id = ? 
          AND DATE(visited_at) = CURDATE()";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $patient_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Patient marked as completed']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
}

$stmt->close();
$conn->close();
?> 