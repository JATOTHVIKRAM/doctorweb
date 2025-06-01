<?php
// API for Nurse Flutter App
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// Connect to the same database as the doctor module
$conn = new mysqli("localhost", "root", "Vikram@2005", "hospital_database");

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

// Get request data (for POST/PUT)
$request_data = json_decode(file_get_contents('php://input'), true);
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';

// Set default response
$response = ['success' => false, 'message' => 'Invalid endpoint'];

// Handle different endpoints
switch ($endpoint) {
    // Get all patients
    case 'patients':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $query = "SELECT * FROM Patients";
            $result = $conn->query($query);
            
            if ($result) {
                $patients = [];
                while ($row = $result->fetch_assoc()) {
                    $patients[] = $row;
                }
                $response = ['success' => true, 'data' => $patients];
            } else {
                $response = ['success' => false, 'message' => 'Failed to fetch patients: ' . $conn->error];
            }
        }
        break;
    
    // Get patient details
    case 'patient_details':
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['patient_id'])) {
            $patient_id = $_GET['patient_id'];
            
            // Prepare statement to prevent SQL injection
            $stmt = $conn->prepare("SELECT * FROM Patients WHERE patient_id = ?");
            $stmt->bind_param('i', $patient_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $patient = $result->fetch_assoc()) {
                $response = ['success' => true, 'data' => $patient];
            } else {
                $response = ['success' => false, 'message' => 'Patient not found'];
            }
            $stmt->close();
        } else {
            $response = ['success' => false, 'message' => 'Patient ID required'];
        }
        break;
    
    // Update vitals
    case 'update_vitals':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($request_data['patient_id'])) {
            $patient_id = $request_data['patient_id'];
            $blood_pressure = isset($request_data['blood_pressure']) ? $request_data['blood_pressure'] : null;
            $temperature = isset($request_data['temperature']) ? $request_data['temperature'] : null;
            $weight = isset($request_data['weight']) ? $request_data['weight'] : null;
            $pulse_rate = isset($request_data['pulse_rate']) ? $request_data['pulse_rate'] : null;
            $respiratory_rate = isset($request_data['respiratory_rate']) ? $request_data['respiratory_rate'] : null;
            
            // Update patient vitals
            $stmt = $conn->prepare("UPDATE Patients SET 
                blood_pressure = ?, 
                temperature = ?, 
                weight = ?, 
                pulse_rate = ?, 
                respiratory_rate = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE patient_id = ?");
                
            $stmt->bind_param('sddddi', 
                $blood_pressure, 
                $temperature, 
                $weight, 
                $pulse_rate, 
                $respiratory_rate, 
                $patient_id
            );
            
            if ($stmt->execute()) {
                // Prepare the vitals data for notification
                $vitalsData = [
                    'patient_id' => $patient_id,
                    'blood_pressure' => $blood_pressure,
                    'temperature' => $temperature,
                    'weight' => $weight,
                    'pulse_rate' => $pulse_rate,
                    'respiratory_rate' => $respiratory_rate,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // Send real-time notification
                notifyVitalsUpdated($patient_id, $vitalsData);
                
                $response = ['success' => true, 'message' => 'Vitals updated successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to update vitals: ' . $stmt->error];
            }
            $stmt->close();
        } else {
            $response = ['success' => false, 'message' => 'Invalid request data'];
        }
        break;
    
    // Record patient visit
    case 'record_visit':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($request_data['patient_id'])) {
            $patient_id = $request_data['patient_id'];
            $name = $request_data['name'] ?? '';
            $age = $request_data['age'] ?? null;
            $gender = $request_data['gender'] ?? '';
            $phone_number = $request_data['phone_number'] ?? '';
            $blood_pressure = $request_data['blood_pressure'] ?? null;
            $temperature = $request_data['temperature'] ?? null;
            $weight = $request_data['weight'] ?? null;
            $pulse_rate = $request_data['pulse_rate'] ?? null;
            $respiratory_rate = $request_data['respiratory_rate'] ?? null;
            $notes = $request_data['notes'] ?? '';
            
            // Insert into multi_visited_patients table (revisit)
            $stmt = $conn->prepare("INSERT INTO multi_visited_patients 
                (patient_id, name, age, gender, phone_number, blood_pressure, temperature, weight, 
                pulse_rate, respiratory_rate, notes, appointment_date, appointment_time) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_DATE(), CURRENT_TIME())");
                
            $stmt->bind_param('isississdds', 
                $patient_id, $name, $age, $gender, $phone_number, 
                $blood_pressure, $temperature, $weight, 
                $pulse_rate, $respiratory_rate, $notes
            );
            
            if ($stmt->execute()) {
                $visit_id = $conn->insert_id;
                
                // Prepare visit data for notification
                $visitData = [
                    'id' => $visit_id,
                    'patient_id' => $patient_id,
                    'name' => $name,
                    'age' => $age,
                    'gender' => $gender,
                    'phone_number' => $phone_number,
                    'blood_pressure' => $blood_pressure,
                    'temperature' => $temperature,
                    'weight' => $weight,
                    'pulse_rate' => $pulse_rate,
                    'respiratory_rate' => $respiratory_rate,
                    'notes' => $notes,
                    'appointment_date' => date('Y-m-d'),
                    'appointment_time' => date('H:i:s')
                ];
                
                // Send real-time notification
                notifyNewVisit($patient_id, $visitData);
                
                $response = ['success' => true, 'message' => 'Visit recorded successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to record visit: ' . $stmt->error];
            }
            $stmt->close();
        } else {
            $response = ['success' => false, 'message' => 'Invalid request data'];
        }
        break;
        
    // Get patient medical history
    case 'medical_history':
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['patient_id'])) {
            $patient_id = $_GET['patient_id'];
            
            $stmt = $conn->prepare("SELECT * FROM multi_visited_patients WHERE patient_id = ? ORDER BY appointment_date DESC, appointment_time DESC");
            $stmt->bind_param('i', $patient_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result) {
                $history = [];
                while ($row = $result->fetch_assoc()) {
                    $history[] = $row;
                }
                $response = ['success' => true, 'data' => $history];
            } else {
                $response = ['success' => false, 'message' => 'Failed to fetch medical history: ' . $stmt->error];
            }
            $stmt->close();
        } else {
            $response = ['success' => false, 'message' => 'Patient ID required'];
        }
        break;
        
    // Add a new patient
    case 'add_patient':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $request_data['name'] ?? '';
            $age = $request_data['age'] ?? null;
            $gender = $request_data['gender'] ?? '';
            $phone_number = $request_data['phone_number'] ?? '';
            $blood_pressure = $request_data['blood_pressure'] ?? null;
            $temperature = $request_data['temperature'] ?? null;
            $weight = $request_data['weight'] ?? null;
            $pulse_rate = $request_data['pulse_rate'] ?? null;
            $respiratory_rate = $request_data['respiratory_rate'] ?? null;
            $notes = $request_data['notes'] ?? '';

            // Insert new patient
            $stmt = $conn->prepare("INSERT INTO Patients 
                (name, age, gender, phone_number, blood_pressure, temperature, weight, 
                pulse_rate, respiratory_rate, notes, visited_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
                
            $stmt->bind_param('sisssddds', 
                $name, $age, $gender, $phone_number, 
                $blood_pressure, $temperature, $weight, 
                $pulse_rate, $respiratory_rate, $notes
            );
            
            if ($stmt->execute()) {
                $new_patient_id = $conn->insert_id;
                
                // Prepare patient data for notification
                $patientData = [
                    'patient_id' => $new_patient_id,
                    'name' => $name,
                    'age' => $age,
                    'gender' => $gender,
                    'phone_number' => $phone_number,
                    'blood_pressure' => $blood_pressure,
                    'temperature' => $temperature,
                    'weight' => $weight,
                    'pulse_rate' => $pulse_rate,
                    'respiratory_rate' => $respiratory_rate,
                    'notes' => $notes,
                    'visited_at' => date('Y-m-d H:i:s')
                ];
                
                // Send real-time notification
                notifyPatientUpdated($new_patient_id, $patientData);
                
                $response = [
                    'success' => true, 
                    'message' => 'Patient added successfully',
                    'patient_id' => $new_patient_id
                ];
            } else {
                $response = ['success' => false, 'message' => 'Failed to add patient: ' . $stmt->error];
            }
            $stmt->close();
        } else {
            $response = ['success' => false, 'message' => 'Invalid request method'];
        }
        break;
        
    // Default response for invalid endpoint
    default:
        $response = [
            'success' => false, 
            'message' => 'Invalid endpoint. Available endpoints: patients, patient_details, update_vitals, record_visit, medical_history, add_patient'
        ];
        break;
}

// Output response as JSON
echo json_encode($response);

// Close the database connection
$conn->close();

/**
 * WebSocket Integration Helper
 * This section handles direct notifications to WebSocket clients for immediate updates
 * without waiting for the polling interval
 */
function notifyWebSocketClients($topic, $data) {
    // Use a UDP socket to notify the WebSocket server of changes
    // This allows us to trigger WebSocket messages from API calls
    $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    
    // Prepare notification message
    $message = json_encode([
        'topic' => $topic,
        'data' => $data
    ]);
    
    // Send to the WebSocket notification port (assuming 8081 for notifications)
    socket_sendto($socket, $message, strlen($message), 0, '127.0.0.1', 8081);
    socket_close($socket);
}

// Hook into existing API methods to send real-time updates
// This would be called at appropriate places in the switch statement above
function notifyPatientUpdated($patientId, $data) {
    notifyWebSocketClients("patient_$patientId", [
        'type' => 'patient_updated',
        'data' => $data
    ]);
    
    // Also notify the general patients topic
    notifyWebSocketClients('patients', [
        'type' => 'patient_updated',
        'data' => $data
    ]);
}

function notifyNewVisit($patientId, $visitData) {
    notifyWebSocketClients("patient_$patientId", [
        'type' => 'new_visit',
        'data' => $visitData
    ]);
    
    // Also notify the visits topic
    notifyWebSocketClients('visits', [
        'type' => 'new_visit',
        'data' => $visitData
    ]);
}

function notifyVitalsUpdated($patientId, $vitalsData) {
    notifyWebSocketClients("patient_$patientId", [
        'type' => 'vitals_updated',
        'data' => $vitalsData
    ]);
    
    // Also notify the vitals topic
    notifyWebSocketClients('vitals', [
        'type' => 'vitals_updated',
        'data' => $vitalsData
    ]);
}
?> 