<?php
require_once 'db_connection.php';
header('Content-Type: application/json');

if (!isset($_GET['patient_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Patient ID is required']);
    exit;
}

$patient_id = intval($_GET['patient_id']);

try {
    $conn = get_db_connection();
    
    // Get patient's basic information
    $query = "SELECT * FROM patients WHERE patient_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();
    
    if (!$patient) {
        http_response_code(404);
        echo json_encode(['error' => 'Patient not found']);
        exit;
    }
    
    // Get patient's visit history
    $query = "SELECT * FROM patient_history 
              WHERE patient_id = ? 
              ORDER BY visited_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $visits = [];
    while ($row = $result->fetch_assoc()) {
        $visits[] = [
            'visited_at' => $row['visited_at'],
            'age' => $row['age'],
            'phone_number' => htmlspecialchars($row['phone_number']),
            'weight' => $row['weight'],
            'blood_pressure' => $row['blood_pressure'],
            'temperature' => $row['temperature'],
            'pulse_rate' => $row['pulse_rate'],
            'respiratory_rate' => $row['respiratory_rate'],
            'symptoms' => htmlspecialchars($row['symptoms']),
            'diagnosis' => htmlspecialchars($row['diagnosis']),
            'prescription' => htmlspecialchars($row['prescription']),
            'notes' => htmlspecialchars($row['notes'])
        ];
    }
    
    $response = [
        'patient' => [
            'patient_id' => $patient['patient_id'],
            'name' => htmlspecialchars($patient['name']),
            'gender' => htmlspecialchars($patient['gender'])
        ],
        'visits' => $visits
    ];
    
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch patient details: ' . $e->getMessage()]);
}
?> 