<?php
session_start();
if (!isset($_SESSION['doctor'])) {
    exit(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Debug log function
function debug_log($message, $data = null) {
    error_log(sprintf(
        "[DEBUG] %s - %s - %s",
        date('Y-m-d H:i:s'),
        $message,
        $data ? json_encode($data) : 'null'
    ));
}

debug_log('Request received', $_GET);

$conn = new mysqli("localhost", "root", "Vikram@2005", "hospital_database");
if ($conn->connect_error) {
    debug_log('Database connection failed', $conn->connect_error);
    exit(json_encode(['success' => false, 'error' => "Connection failed: " . $conn->connect_error]));
}

if (isset($_GET['patient_id']) && isset($_GET['visit_date'])) {
    $patient_id = (int)$_GET['patient_id'];
    $visit_date = $_GET['visit_date'];
    
    debug_log('Processing request for', ['patient_id' => $patient_id, 'visit_date' => $visit_date]);
    
    // Get patient and visit details
    $query = "SELECT 
                p.name,
                p.gender,
                ph.*
             FROM patients p 
             INNER JOIN patient_history ph ON p.patient_id = ph.patient_id 
             WHERE p.patient_id = ?
             AND DATE(ph.visited_at) = DATE(?)";

    debug_log('SQL Query', $query);

    try {
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("is", $patient_id, $visit_date);
        debug_log('Parameters bound', ['patient_id' => $patient_id, 'visit_date' => $visit_date]);

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        debug_log('Query executed', ['num_rows' => $result ? $result->num_rows : 0]);
        
        if ($result && $result->num_rows > 0) {
            $data = $result->fetch_assoc();
            debug_log('Raw data fetched', $data);
            
            // Format the visit date
            $visitDateTime = new DateTime($data['visited_at']);
            $visitDate = $visitDateTime->format('Y-m-d');
            
            // Now fetch all prescriptions for this patient and visit date
            $prescriptionQuery = "SELECT prescription_text, prescribed_date 
                                  FROM prescription 
                                  WHERE patient_id = ? 
                                  AND DATE(prescribed_date) = DATE(?)
                                  ORDER BY prescribed_date DESC";
            
            $prescStmt = $conn->prepare($prescriptionQuery);
            $prescStmt->bind_param("is", $patient_id, $visitDate);
            $prescStmt->execute();
            $prescResult = $prescStmt->get_result();
            
            // Store all prescriptions in an array
            $prescriptions = [];
            if ($prescResult && $prescResult->num_rows > 0) {
                while ($prescRow = $prescResult->fetch_assoc()) {
                    $prescriptions[] = [
                        'text' => $prescRow['prescription_text'],
                        'time' => date('h:i A', strtotime($prescRow['prescribed_date'])),
                        'date' => date('Y-m-d', strtotime($prescRow['prescribed_date']))
                    ];
                }
            }
            
            $response = [
                'success' => true,
                'data' => [
                    'name' => $data['name'],
                    'gender' => $data['gender'],
                    'phone_number' => $data['phone_number'],
                    'age' => (int)$data['age'],
                    'weight' => (float)$data['weight'],
                    'blood_pressure' => $data['blood_pressure'],
                    'temperature' => (float)$data['temperature'],
                    'pulse_rate' => (int)$data['pulse_rate'],
                    'respiratory_rate' => (int)$data['respiratory_rate'],
                    'diagnosis' => $data['diagnosis'] ?: 'No diagnosis recorded',
                    'prescriptions' => $prescriptions, // Array of all prescriptions
                    'has_prescriptions' => count($prescriptions) > 0,
                    'visited_at' => $visitDateTime->format('F d, Y h:i A')
                ]
            ];
            
            debug_log('Formatted response', $response);
            echo json_encode($response);
        } else {
            debug_log('No data found');
            echo json_encode([
                'success' => false,
                'error' => 'No visit details found for this date'
            ]);
        }
    } catch (Exception $e) {
        debug_log('Exception occurred', ['message' => $e->getMessage()]);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    debug_log('Missing parameters', $_GET);
    echo json_encode([
        'success' => false,
        'error' => 'Missing required parameters'
    ]);
}

$conn->close();
?> 