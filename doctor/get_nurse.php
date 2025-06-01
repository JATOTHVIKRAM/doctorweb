<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Nurse ID is required');
    }

    $conn = get_db_connection();
    
    // Get nurse data
    $stmt = $conn->prepare("SELECT nurse_id, full_name, email, phone, gender, date_of_birth, nursing_qualifications, years_of_experience, username FROM Nurses WHERE nurse_id = ?");
    if (!$stmt) {
        throw new Exception('Failed to prepare query: ' . $conn->error);
    }

    $stmt->bind_param("i", $_GET['id']);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to fetch nurse data: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Nurse not found');
    }

    $nurse = $result->fetch_assoc();
    echo json_encode($nurse);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
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