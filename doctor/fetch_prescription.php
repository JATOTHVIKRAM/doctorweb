<?php
session_start();
if (!isset($_SESSION['doctor'])) {
    exit('Unauthorized');
}

$conn = new mysqli("localhost", "root", "Vikram@2005", "hospital_database");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['patient_id'])) {
    $patient_id = (int)$_GET['patient_id'];
    $visit_date = isset($_GET['visit_date']) ? $_GET['visit_date'] : date('Y-m-d');
    
    // Query to get all prescriptions for the patient on the given date
    $query = "SELECT prescription_text, prescribed_date 
              FROM prescription 
              WHERE patient_id = ? 
              AND DATE(prescribed_date) = DATE(?)
              ORDER BY prescribed_date DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $patient_id, $visit_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo '<div class="previous-prescriptions">';
        echo '<h5 class="mb-3">Prescriptions for ' . date('F d, Y', strtotime($visit_date)) . '</h5>';
        
        $count = 1;
        while ($row = $result->fetch_assoc()) {
            $time = date('h:i A', strtotime($row['prescribed_date']));
            
            echo '<div class="prescription-entry mb-3 p-3 border rounded">';
            echo '<div class="d-flex justify-content-between align-items-center mb-2">';
            echo '<span class="prescription-number fw-bold">Prescription #' . $count . '</span>';
            echo '<span class="prescription-time text-muted"><i class="far fa-clock me-1"></i>' . $time . '</span>';
            echo '</div>';
            echo '<div class="prescription-content">' . nl2br(htmlspecialchars($row['prescription_text'])) . '</div>';
            echo '</div>';
            
            $count++;
        }
        echo '</div>';
    } else {
        echo '<div class="previous-prescriptions"></div>';
    }
} else {
    echo '<div class="alert alert-danger">Missing patient ID.</div>';
}

$conn->close();
?> 