<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['doctor'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli("localhost", "root", "Vikram@2005", "hospital_database");
    
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    // Get patient data from the form
    $patientId = $_POST['patient_id'];
    $bloodPressure = $_POST['blood_pressure'];
    $temperature = $_POST['temperature'];
    $weight = $_POST['weight'];
    $pulseRate = $_POST['pulse_rate'];
    $respiratoryRate = $_POST['respiratory_rate'];
    $diagnosis = $_POST['diagnosis'];
    $appointmentDate = !empty($_POST['appointment_date']) ? $_POST['appointment_date'] : null;
    $appointmentTime = !empty($_POST['appointment_time']) ? $_POST['appointment_time'] : null;
    
    // First, get the patient's basic information from the patients table
    $stmt = $conn->prepare("SELECT name, age, gender, phone_number FROM patients WHERE patient_id = ?");
    $stmt->bind_param("i", $patientId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Patient not found']);
        exit;
    }
    
    $patientData = $result->fetch_assoc();
    
    // Insert into multi_visited_patients table
    $stmt = $conn->prepare("INSERT INTO multi_visited_patients (patient_id, name, age, gender, phone_number, blood_pressure, temperature, weight, pulse_rate, respiratory_rate, diagnosis, appointment_date, appointment_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssddiiiss", 
        $patientId,
        $patientData['name'],
        $patientData['age'],
        $patientData['gender'],
        $patientData['phone_number'],
        $bloodPressure,
        $temperature,
        $weight,
        $pulseRate,
        $respiratoryRate,
        $diagnosis,
        $appointmentDate,
        $appointmentTime
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Patient revisit recorded successfully',
            'visit_id' => $conn->insert_id
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to record patient revisit: ' . $stmt->error
        ]);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 