<?php
session_start();
$conn = new mysqli("localhost", "root", "Vikram@2005", "hospital_database");

if ($conn->connect_error) {
    die(json_encode(["error" => "Database Connection Failed: " . $conn->connect_error]));
}

// Fetch Medical History Based on Patient ID & Visited Date
if (isset($_GET['patient_id']) && isset($_GET['visited_at'])) {
    $patient_id = intval($_GET['patient_id']);
    $visited_at = $conn->real_escape_string($_GET['visited_at']);

    $query = "SELECT weight, blood_pressure, temperature, pulse_rate, respiratory_rate, prescription 
              FROM Patient_History 
              WHERE patient_id = $patient_id AND visited_at = '$visited_at'";

    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(["error" => "No medical records found for this visit."]);
    }
    exit;
}
?>
