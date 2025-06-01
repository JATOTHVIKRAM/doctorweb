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
        if (!$history_stmt) {
            error_log("Prepare failed: " . $conn->error);
            throw new Exception("Error preparing statement: " . $conn->error);
        }
        
        // Set critical_condition based on is_emergency flag
        $critical_condition = isset($data['is_emergency']) && $data['is_emergency'] ? 'yes' : 'no';
        
        // Convert all data to appropriate types
        $patient_id_val = intval($patient_id);
        // Phone number should be a string for binding
        $phone_val = strval($data['phone_number']);
        $age_val = intval($data['age']);
        $weight_val = floatval($data['weight']);
        $bp_val = strval($data['blood_pressure']);
        $temp_val = floatval($data['temperature']);
        $pulse_val = intval($data['pulse_rate']);
        $resp_val = intval($data['respiratory_rate']);
        $diag_val = strval($data['diagnosis']);
        $critical_val = strval($critical_condition);
        
        // Debug output
        error_log("Binding parameters with types: i s i d s d i i s s");
        
        // Bind parameters with correct types
        $history_stmt->bind_param(
            "isidsdiiss", 
            $patient_id_val,
            $phone_val,
            $age_val,
            $weight_val,
            $bp_val,
            $temp_val,
            $pulse_val,
            $resp_val,
            $diag_val,
            $critical_val
        );

        if (!$history_stmt->execute()) {
            error_log("Execute failed: " . $history_stmt->error);
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
        $ch = curl_init('http://10.0.2.2/notify_clients.php');
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