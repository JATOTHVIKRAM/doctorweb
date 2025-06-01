<?php
session_start();
if (!isset($_SESSION['doctor'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$conn = new mysqli("localhost", "root", "Vikram@2005", "hospital_database");
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Get the current timestamp from session or set it
if (!isset($_SESSION['last_check_timestamp'])) {
    $_SESSION['last_check_timestamp'] = date('Y-m-d H:i:s');
}

$last_check = $_SESSION['last_check_timestamp'];
$current_time = date('Y-m-d H:i:s');
$timeRange = isset($_GET['range']) ? $_GET['range'] : 'today';

// Query to check for new or updated patients
if ($timeRange === 'week') {
    $query = "SELECT COUNT(*) as updates
              FROM (
                  SELECT patient_id 
                  FROM Patients 
                  WHERE (visited_at > ? OR updated_at > ?)
                  AND DATE(visited_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                  UNION
                  SELECT p.patient_id
                  FROM prescription p
                  JOIN Patients pt ON p.patient_id = pt.patient_id
                  WHERE p.prescribed_date > ?
                  AND DATE(pt.visited_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
              ) updates";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $last_check, $last_check, $last_check);
} else {
    $query = "SELECT COUNT(*) as updates
              FROM (
                  SELECT patient_id 
                  FROM Patients 
                  WHERE (visited_at > ? OR updated_at > ?)
                  AND DATE(visited_at) = CURDATE()
                  UNION
                  SELECT p.patient_id
                  FROM prescription p
                  JOIN Patients pt ON p.patient_id = pt.patient_id
                  WHERE p.prescribed_date > ?
                  AND DATE(pt.visited_at) = CURDATE()
              ) updates";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $last_check, $last_check, $last_check);
}

$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// Update the last check timestamp
$_SESSION['last_check_timestamp'] = $current_time;

header('Content-Type: application/json');

if ($row['updates'] > 0) {
    echo json_encode([
        'hasUpdates' => true,
        'message' => $row['updates'] . ' new update(s) available'
    ]);
} else {
    echo json_encode([
        'hasUpdates' => false
    ]);
}

$conn->close();
?> 