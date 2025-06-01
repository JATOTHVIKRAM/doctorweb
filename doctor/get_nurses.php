<?php
header('Content-Type: application/json');

require_once 'db_connection.php';

try {
    $conn = get_db_connection();
    
    $sql = "SELECT nurse_id, full_name, email, phone, gender, date_of_birth, 
            nursing_qualifications, years_of_experience, username, created_at, updated_at 
            FROM Nurses ORDER BY created_at DESC";
            
    $result = $conn->query($sql);
    
    if ($result) {
        $nurses = array();
        while ($row = $result->fetch_assoc()) {
            // Remove sensitive information
            unset($row['password']);
            $nurses[] = $row;
        }
        echo json_encode($nurses);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch nurses']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 