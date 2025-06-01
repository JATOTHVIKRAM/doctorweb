<?php
session_start();
if (!isset($_SESSION['doctor'])) {
    exit('Unauthorized');
}

$conn = new mysqli("localhost", "root", "Vikram@2005", "hospital_database");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$timeRange = isset($_GET['range']) ? $_GET['range'] : 'today';
$today = date('Y-m-d');

if ($timeRange === 'week') {
    $query = "SELECT * FROM Patients 
              WHERE DATE(visited_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
              ORDER BY visited_at DESC";
    $stmt = $conn->prepare($query);
} else {
    $query = "SELECT * FROM Patients 
              WHERE DATE(visited_at) = ? 
              ORDER BY visited_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $today);
}

$stmt->execute();
$patients = $stmt->get_result();

if ($patients->num_rows > 0) {
    $currentDate = '';
    while ($row = $patients->fetch_assoc()) {
        $visitDate = date('Y-m-d', strtotime($row['visited_at']));
        $visitTime = date('h:i A', strtotime($row['visited_at']));
        
        // Add date separator if it's a new date and we're in week view
        if ($visitDate !== $currentDate && $timeRange === 'week') {
            $currentDate = $visitDate;
            $formattedDate = date('l, F d', strtotime($visitDate));
            echo "<div class='date-separator'>$formattedDate</div>";
        }
        
        echo '<div class="patient-item animate__animated animate__fadeIn" onclick="viewPatient(' . $row['patient_id'] . ')">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-1">' . htmlspecialchars($row['name']) . '</h5>
                    <span class="badge bg-success">
                        ' . $visitTime . '
                    </span>
                </div>
                <small class="text-muted">ID: ' . $row['patient_id'] . '</small>';
        
        if (!empty($row['diagnosis'])) {
            echo '<div class="mt-1">
                    <small class="text-muted">
                        <i class="fas fa-stethoscope me-1"></i>
                        ' . htmlspecialchars(substr($row['diagnosis'], 0, 50)) . (strlen($row['diagnosis']) > 50 ? '...' : '') . '
                    </small>
                  </div>';
        }
        
        echo '</div>';
    }
} else {
    echo '<div class="alert alert-info mt-3">
            <i class="fas fa-info-circle me-2"></i>
            No patients found for the selected time range.
          </div>';
}

$conn->close();
?> 