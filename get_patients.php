<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Enable error reporting for debugging
ini_set('display_errors', 0); // Disable error display in production
error_reporting(E_ALL);

// Get query parameter if any
$search_query = isset($_GET['query']) ? $_GET['query'] : '';

try {
    if ($search_query) {
        // Search patients by name or phone
        $query = "
            SELECT 
                p.patient_id, 
                p.name, 
                p.gender, 
                p.visited_at, 
                p.updated_at,
                ph.phone_number,
                ph.age,
                ph.weight,
                ph.blood_pressure,
                ph.temperature,
                ph.pulse_rate,
                ph.respiratory_rate,
                ph.diagnosis,
                ph.critical_condition,
                ph.visited_at as history_date,
                CASE WHEN cp.id IS NOT NULL THEN 1 ELSE 0 END as is_completed,
                cp.completed_at as completion_date
            FROM 
                patients p
            LEFT JOIN 
                patient_history ph ON p.patient_id = ph.patient_id
            LEFT JOIN
                completed_patients cp ON p.patient_id = cp.patient_id
            WHERE 
                p.name LIKE ? OR ph.phone_number LIKE ?
            ORDER BY 
                p.visited_at ASC
        ";
        
        $stmt = $conn->prepare($query);
        $search_param = "%$search_query%";
        $stmt->bind_param("ss", $search_param, $search_param);
    } else {
        // Modified query to avoid GROUP BY issue - use a subquery to get latest history
        $query = "
            SELECT 
                p.patient_id, 
                p.name, 
                p.gender, 
                p.visited_at, 
                p.updated_at,
                latest_ph.phone_number,
                latest_ph.age,
                latest_ph.weight,
                latest_ph.blood_pressure,
                latest_ph.temperature,
                latest_ph.pulse_rate,
                latest_ph.respiratory_rate,
                latest_ph.diagnosis,
                latest_ph.critical_condition,
                latest_ph.visited_at as history_date,
                CASE WHEN cp.id IS NOT NULL THEN 1 ELSE 0 END as is_completed,
                cp.completed_at as completion_date
            FROM 
                patients p
            LEFT JOIN (
                SELECT 
                    ph1.*
                FROM
                    patient_history ph1
                INNER JOIN (
                    SELECT 
                        patient_id, 
                        MAX(visited_at) as latest_visit
                    FROM 
                        patient_history
                    GROUP BY 
                        patient_id
                ) ph2 ON ph1.patient_id = ph2.patient_id AND ph1.visited_at = ph2.latest_visit
            ) AS latest_ph ON p.patient_id = latest_ph.patient_id
            LEFT JOIN
                completed_patients cp ON p.patient_id = cp.patient_id
            ORDER BY 
                p.visited_at ASC
        ";
        
        $stmt = $conn->prepare($query);
    }

    if (!$stmt->execute()) {
        throw new Exception("Error executing query: " . $conn->error);
    }

    $result = $stmt->get_result();
    $patients = [];
    $completed_count = 0;
    $total_count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $patient_id = $row['patient_id'];
        $total_count++;
        
        if ($row['is_completed'] == 1) {
            $completed_count++;
        }
        
        // Count total visits for this patient
        $visit_count_query = "SELECT COUNT(*) as total_visits FROM patient_history WHERE patient_id = ?";
        $visit_stmt = $conn->prepare($visit_count_query);
        $visit_stmt->bind_param("i", $patient_id);
        $visit_stmt->execute();
        $visit_result = $visit_stmt->get_result();
        $visit_count = $visit_result->fetch_assoc()['total_visits'];
        $visit_stmt->close();
        
        // Check if there's a prescription for this patient
        $prescription_query = "SELECT prescription_text FROM prescription WHERE patient_id = ? ORDER BY prescribed_date DESC LIMIT 1";
        $prescription_stmt = $conn->prepare($prescription_query);
        $prescription_stmt->bind_param("i", $patient_id);
        $prescription_stmt->execute();
        $prescription_result = $prescription_stmt->get_result();
        $prescription_text = '';
        
        if ($prescription_row = $prescription_result->fetch_assoc()) {
            $prescription_text = $prescription_row['prescription_text'];
        }
        $prescription_stmt->close();
        
        // Format the data for the Flutter app
        $patients[] = [
            'id' => $patient_id,
            'name' => $row['name'],
            'phone' => $row['phone_number'] ?? '',
            'phone_number' => $row['phone_number'] ?? '',
            'gender' => $row['gender'],
            'age' => $row['age'] ?? 0,
            'weight' => $row['weight'] ?? 0,
            'blood_pressure' => $row['blood_pressure'] ?? '',
            'temperature' => $row['temperature'] ?? 0,
            'pulse_rate' => $row['pulse_rate'] ?? 0,
            'respiratory_rate' => $row['respiratory_rate'] ?? 0,
            'diagnosis' => $row['diagnosis'] ?? '',
            'prescription' => $prescription_text,
            'visited_at' => $row['history_date'] ?? $row['visited_at'],
            'visitCount' => $visit_count,
            'critical_condition' => $row['critical_condition'] ?? 'no',
            'isEmergency' => (isset($row['critical_condition']) && strtolower($row['critical_condition']) === 'yes'),
            'isCompleted' => $row['is_completed'] == 1,
            'completionDate' => $row['completion_date'] ?? ''
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $patients,
        'stats' => [
            'total_patients' => $total_count,
            'completed_patients' => $completed_count
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?> 