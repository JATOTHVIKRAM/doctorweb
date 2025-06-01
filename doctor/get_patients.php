<?php
require_once 'db_connection.php';
header('Content-Type: application/json');

try {
    $conn = get_db_connection();
    
    // Get patients data and count their visits
    $query = "SELECT 
                p.patient_id,
                p.name,
                p.gender,
                COALESCE(ph.visit_count, 0) as total_visits
            FROM patients p
            LEFT JOIN (
                SELECT patient_id, COUNT(*) as visit_count 
                FROM patient_history 
                GROUP BY patient_id
            ) ph ON p.patient_id = ph.patient_id
            ORDER BY p.patient_id ASC";
            
    $result = $conn->query($query);
    
    $patients = [];
    while ($row = $result->fetch_assoc()) {
        $patients[] = [
            'patient_id' => $row['patient_id'],
            'name' => htmlspecialchars($row['name']),
            'gender' => htmlspecialchars($row['gender']),
            'total_visits' => (int)$row['total_visits']
        ];
    }
    
    echo json_encode($patients);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch patients: ' . $e->getMessage()]);
}
?> 