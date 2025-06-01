<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['doctor'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$conn = new mysqli("localhost", "root", "Vikram@2005", "hospital_database");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => "Connection failed: " . $conn->connect_error]);
    exit;
}

if (isset($_POST['patient_id']) && isset($_POST['prescription_text'])) {
    $patient_id = (int)$_POST['patient_id'];
    $prescription_text = trim($_POST['prescription_text']);
    
    // Validation
    if (empty($prescription_text)) {
        echo json_encode(['success' => false, 'error' => 'Prescription text cannot be empty']);
        exit;
    }
    
    if ($patient_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid patient ID']);
        exit;
    }
    
    // Debug information
    error_log("Saving prescription for patient ID: " . $patient_id);
    error_log("Prescription text length: " . strlen($prescription_text));

    // Insert into prescription table
    $query = "INSERT INTO prescription (patient_id, prescription_text, prescribed_date) 
              VALUES (?, ?, CURRENT_TIMESTAMP)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $patient_id, $prescription_text);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Prescription saved successfully']);
    } else {
        error_log("Database error: " . $stmt->error);
        echo json_encode(['success' => false, 'error' => 'Failed to save prescription: ' . $stmt->error]);
    }
} else {
    $post_data = print_r($_POST, true);
    error_log("Missing required data. POST data: " . $post_data);
    echo json_encode(['success' => false, 'error' => 'Missing required data']);
}

$conn->close();
?>
