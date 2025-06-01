<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $conn->begin_transaction();

    try {
        $patient_id = null;

        // Check if patient_id is provided (for existing patient)
        if (isset($data['patient_id']) && !empty($data['patient_id'])) {
            // Verify if patient exists with matching details
            $check_query = "SELECT patient_id, name, gender FROM patients WHERE patient_id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("i", $data['patient_id']);
            $check_stmt->execute();
            $result = $check_stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception("Patient ID not found");
            }
            
            // Get the existing patient data
            $existing_patient = $result->fetch_assoc();
            
            // Validate name and gender match
            if ($existing_patient['name'] !== $data['name'] || 
                $existing_patient['gender'] !== $data['gender']) {
                throw new Exception("Patient ID, name and gender do not match with existing records");
            }
            
            $patient_id = $data['patient_id'];
            $check_stmt->close();
        } else {
            // Insert new patient into patients table
            $patient_query = "INSERT INTO patients (name, gender) VALUES (?, ?)";
            $patient_stmt = $conn->prepare($patient_query);
            $patient_stmt->bind_param("ss", $data['name'], $data['gender']);
            
            if (!$patient_stmt->execute()) {
                throw new Exception("Error inserting patient: " . $patient_stmt->error);
            }
            $patient_id = $conn->insert_id;
            $patient_stmt->close();
        }

        // Insert into patient_history table
        $history_query = "INSERT INTO patient_history (
            patient_id, 
            phone_number, 
            age, 
            weight, 
            blood_pressure, 
            temperature, 
            pulse_rate, 
            respiratory_rate, 
            diagnosis,
            critical_condition
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $history_stmt = $conn->prepare($history_query);
        
        // Set critical_condition based on is_emergency flag
        $critical_condition = isset($data['is_emergency']) && $data['is_emergency'] ? 'yes' : 'no';
        
        // Debug logging for diagnosis
        error_log("Diagnosis value before insert: " . ($data['diagnosis'] ?? 'null'));
        
        $history_stmt->bind_param(
            "isidsdiiis", 
            $patient_id,
            $data['phone_number'],
            $data['age'],
            $data['weight'],
            $data['blood_pressure'],
            $data['temperature'],
            $data['pulse_rate'],
            $data['respiratory_rate'],
            $data['diagnosis'],
            $critical_condition
        );

        if (!$history_stmt->execute()) {
            throw new Exception("Error inserting patient history: " . $history_stmt->error);
        }

        $conn->commit();
        
        // After successful registration, send a notification
        $notificationData = [
            "type" => "patient_registered",
            "message" => "New patient registered",
            "patient_id" => $patient_id,
            "name" => $data['name'],
            "timestamp" => date('Y-m-d H:i:s')
        ];
        
        // Send notification using notify_clients.php
        $ch = curl_init('http://localhost/notify_clients.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notificationData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_exec($ch);
        curl_close($ch);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Patient data saved successfully',
            'patient_id' => $patient_id
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }

    if (isset($history_stmt)) $history_stmt->close();
    $conn->close();
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
}
?> 