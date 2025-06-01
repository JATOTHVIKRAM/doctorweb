<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $patient_id = isset($_GET['id']) ? $_GET['id'] : null;
    
    if ($patient_id) {
        try {
            // First, let's check the patient_history table directly
            $check_query = "SELECT COUNT(*) as count FROM patient_history WHERE patient_id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("i", $patient_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $count_row = $check_result->fetch_assoc();
            error_log("Direct count of records in patient_history for patient_id $patient_id: " . $count_row['count']);
            $check_stmt->close();
            
            // Get all visits for this patient from patient_history
            $query = "SELECT 
                        ph.*,
                        pr.prescription_text as prescription
                     FROM 
                        patient_history ph
                     LEFT JOIN 
                        prescription pr ON ph.patient_id = pr.patient_id 
                            AND DATE(ph.created_at) = DATE(pr.prescribed_date)
                     WHERE 
                        ph.patient_id = ?
                     ORDER BY 
                        ph.created_at DESC";
                     
            // Log the query for debugging
            error_log("Executing query for patient_id $patient_id: $query");
            
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparing statement: " . $conn->error);
            }
            
            $stmt->bind_param("i", $patient_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Error executing query: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $visits = [];
            
            // Add debugging information
            $row_count = $result->num_rows;
            error_log("Number of rows returned for patient_id $patient_id: $row_count");
            
            while ($row = $result->fetch_assoc()) {
                // Log each row for debugging
                error_log("Processing visit: " . json_encode($row));
                
                $visits[] = [
                    'date' => $row['created_at'],
                    'blood_pressure' => $row['blood_pressure'] ?? '',
                    'temperature' => $row['temperature'] ?? 0,
                    'pulse_rate' => $row['pulse_rate'] ?? 0,
                    'respiratory_rate' => $row['respiratory_rate'] ?? 0,
                    'weight' => $row['weight'] ?? 0,
                    'diagnosis' => $row['diagnosis'] ?? '',
                    'prescription' => $row['prescription'] ?? '',
                ];
            }
            
            // Log the final count of visits
            error_log("Final count of visits for patient_id $patient_id: " . count($visits));
            
            // Load the entire patient_history for the patient
            $direct_query = "SELECT * FROM patient_history WHERE patient_id = ? ORDER BY created_at DESC";
            $direct_stmt = $conn->prepare($direct_query);
            $direct_stmt->bind_param("i", $patient_id);
            $direct_stmt->execute();
            $direct_result = $direct_stmt->get_result();
            $all_visit_dates = [];
            $all_visits = [];
            
            error_log("Direct query: $direct_query with patient_id=$patient_id");
            error_log("Direct query found: " . $direct_result->num_rows . " rows");
            
            while ($history_row = $direct_result->fetch_assoc()) {
                $all_visit_dates[] = $history_row['created_at'];
                error_log("Adding visit date: " . $history_row['created_at']);
                
                // Check if this visit is already in the visits array
                $found = false;
                foreach ($visits as $visit) {
                    if ($visit['date'] === $history_row['created_at']) {
                        $found = true;
                        break;
                    }
                }
                
                // If not found, add this visit to the visits array
                if (!$found) {
                    error_log("Creating new visit record for date: " . $history_row['created_at']);
                    $all_visits[] = [
                        'date' => $history_row['created_at'],
                        'blood_pressure' => $history_row['blood_pressure'] ?? '',
                        'temperature' => $history_row['temperature'] ?? 0,
                        'pulse_rate' => $history_row['pulse_rate'] ?? 0,
                        'respiratory_rate' => $history_row['respiratory_rate'] ?? 0,
                        'weight' => $history_row['weight'] ?? 0,
                        'diagnosis' => $history_row['diagnosis'] ?? '',
                        'prescription' => '',  // No prescription data for this visit
                    ];
                }
            }
            
            // Combine visits from both queries
            $visits = array_merge($visits, $all_visits);
            
            // Deduplicate visits based on date
            $unique_visits = [];
            $visit_dates = [];
            foreach ($visits as $visit) {
                if (!in_array($visit['date'], $visit_dates)) {
                    $visit_dates[] = $visit['date'];
                    $unique_visits[] = $visit;
                }
            }
            
            // Sort by date (most recent first)
            usort($unique_visits, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
            
            error_log("All visit dates for patient_id $patient_id: " . json_encode($all_visit_dates));
            error_log("Final unique visits count: " . count($unique_visits));
            $direct_stmt->close();
            
            echo json_encode([
                'status' => 'success',
                'data' => $unique_visits,
                'all_visit_dates' => $all_visit_dates
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
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Patient ID is required'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
}

$conn->close();
?> 