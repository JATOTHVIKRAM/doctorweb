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
    
    // Add back to current visit button
    echo '<div class="mb-4" style="padding: 16px;">
            <button type="button" class="btn btn-outline-primary w-100" onclick="viewPatient(' . $patient_id . ')" style="padding: 12px;">
                <i class="fas fa-arrow-left me-2"></i>Back to Current Visit
            </button>
          </div>';
    
    // Get all visits from patient_history table
    $query = "SELECT 
                ph.visited_at,
                ph.diagnosis
             FROM patient_history ph 
             WHERE ph.patient_id = ? 
             ORDER BY ph.visited_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo '<div class="list-group">';
        while ($row = $result->fetch_assoc()) {
            $visitDate = new DateTime($row['visited_at']);
            $formattedDate = $visitDate->format('Y-m-d H:i:s');
            $shortDiagnosis = substr($row['diagnosis'], 0, 30) . (strlen($row['diagnosis']) > 30 ? '...' : '');
            
            echo '<button type="button" class="list-group-item list-group-item-action" onclick="loadHistoryDetails(' . $patient_id . ', \'' . $formattedDate . '\')">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold">' . $visitDate->format('F d, Y') . '</div>
                            <div class="small text-muted">' . $visitDate->format('h:i A') . '</div>
                            <div class="small text-muted mt-1">
                                <i class="fas fa-stethoscope me-1"></i>' . htmlspecialchars($shortDiagnosis) . '
                            </div>
                        </div>
                        <i class="fas fa-chevron-right text-muted"></i>
                    </div>
                  </button>';
        }
        echo '</div>';
    } else {
        echo '<div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No previous visits found.
              </div>';
    }
}

$conn->close();
?> 