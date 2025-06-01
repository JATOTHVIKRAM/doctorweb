<?php
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Get form data
        $provider_id = $_POST['provider_id'];
        $service_id = $_POST['service_id'];
        $staff_id = $_POST['staff_id'];
        $appointment_date = $_POST['appointment_date'];
        $start_time = $_POST['start_time'];
        
        // Get service duration
        $stmt = $conn->prepare("SELECT duration FROM services WHERE service_id = :service_id");
        $stmt->bindParam(':service_id', $service_id);
        $stmt->execute();
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate end time
        $end_time = date('H:i:s', strtotime($start_time . ' + ' . $service['duration'] . ' minutes'));
        
        // Check if slot is available
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments 
                              WHERE provider_id = :provider_id 
                              AND appointment_date = :appointment_date 
                              AND ((start_time <= :start_time AND end_time > :start_time) 
                              OR (start_time < :end_time AND end_time >= :end_time))");
        
        $stmt->bindParam(':provider_id', $provider_id);
        $stmt->bindParam(':appointment_date', $appointment_date);
        $stmt->bindParam(':start_time', $start_time);
        $stmt->bindParam(':end_time', $end_time);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            throw new Exception("Time slot not available");
        }
        
        // Insert appointment
        $stmt = $conn->prepare("INSERT INTO appointments (
            provider_id, service_id, staff_id, 
            appointment_date, start_time, end_time, status
        ) VALUES (
            :provider_id, :service_id, :staff_id,
            :appointment_date, :start_time, :end_time, 'pending'
        )");
        
        $stmt->bindParam(':provider_id', $provider_id);
        $stmt->bindParam(':service_id', $service_id);
        $stmt->bindParam(':staff_id', $staff_id);
        $stmt->bindParam(':appointment_date', $appointment_date);
        $stmt->bindParam(':start_time', $start_time);
        $stmt->bindParam(':end_time', $end_time);
        
        $stmt->execute();
        $appointment_id = $conn->lastInsertId();
        
        // Return success response
        echo json_encode([
            'status' => 'success', 
            'message' => 'Appointment booked successfully',
            'appointment_id' => $appointment_id,
            'end_time' => $end_time
        ]);
        
    } catch(Exception $e) {
        // Return error response
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    // Return error for invalid request method
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?> 