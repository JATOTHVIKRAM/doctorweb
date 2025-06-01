<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $patient_id = isset($_GET['id']) ? $_GET['id'] : null;
    
    if ($patient_id) {
        // Get patient details with latest history
        $query = "SELECT p.*, ph.*, pr.prescription_text
                 FROM patients p
                 LEFT JOIN patient_history ph ON p.patient_id = ph.patient_id
                 LEFT JOIN prescription pr ON p.patient_id = pr.patient_id
                 WHERE p.patient_id = ?
                 ORDER BY ph.visited_at DESC
                 LIMIT 1";
                 
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $patient_id);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $patient = $result->fetch_assoc();
            
            if ($patient) {
                echo json_encode([
                    'status' => 'success',
                    'data' => $patient
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Patient not found'
                ]);
            }
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error fetching patient details: ' . $stmt->error
            ]);
        }
        $stmt->close();
    } else {
        // Get all patients list
        $query = "SELECT p.patient_id, p.name, p.gender, p.visited_at, 
                        ph.phone_number, ph.age 
                 FROM patients p
                 LEFT JOIN patient_history ph ON p.patient_id = ph.patient_id
                 ORDER BY p.visited_at DESC";
                 
        $result = $conn->query($query);
        
        if ($result) {
            $patients = [];
            while ($row = $result->fetch_assoc()) {
                $patients[] = $row;
            }
            echo json_encode([
                'status' => 'success',
                'data' => $patients
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error fetching patients list: ' . $conn->error
            ]);
        }
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
}

$conn->close();
?> 