<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
ini_set('display_errors', 0); // Disable error display in production
error_reporting(E_ALL);

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get request body
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    if (!isset($data['patient_id']) || empty($data['patient_id'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Patient ID is required'
        ]);
        exit;
    }
    
    $patient_id = $data['patient_id'];
    
    try {
        // Delete patient from completed_patients table
        $delete_query = "DELETE FROM completed_patients WHERE patient_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $patient_id);
        
        if ($delete_stmt->execute()) {
            // Check if any rows were affected
            if ($delete_stmt->affected_rows > 0) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Patient unmarked as completed'
                ]);
            } else {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Patient was not marked as completed'
                ]);
            }
        } else {
            throw new Exception("Error deleting record: " . $conn->error);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    } finally {
        if (isset($delete_stmt)) {
            $delete_stmt->close();
        }
        if (isset($conn)) {
            $conn->close();
        }
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
}
?> 