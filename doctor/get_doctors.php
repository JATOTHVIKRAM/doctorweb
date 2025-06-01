<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

try {
    $conn = get_db_connection();
    
    // Select all doctors with their information
    $sql = "SELECT doctor_id, full_name, email, phone, gender, qualifications, username FROM Doctors ORDER BY created_at DESC";
    $result = $conn->query($sql);
    
    if ($result) {
        $doctors = array();
        while ($row = $result->fetch_assoc()) {
            $doctors[] = $row;
        }
        echo json_encode($doctors);
    } else {
        throw new Exception("Failed to fetch doctors: " . $conn->error);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 