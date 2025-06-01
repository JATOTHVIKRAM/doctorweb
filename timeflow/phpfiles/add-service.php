<?php
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Get form data
        $provider_id = $_POST['provider_id'];
        $service_name = $_POST['service_name'];
        $duration = $_POST['duration'];
        $price = $_POST['price'];
        
        // Insert into services table
        $stmt = $conn->prepare("INSERT INTO services (
            provider_id, service_name, duration, price, is_active
        ) VALUES (
            :provider_id, :service_name, :duration, :price, TRUE
        )");
        
        $stmt->bindParam(':provider_id', $provider_id);
        $stmt->bindParam(':service_name', $service_name);
        $stmt->bindParam(':duration', $duration);
        $stmt->bindParam(':price', $price);
        
        $stmt->execute();
        $service_id = $conn->lastInsertId();
        
        // Return success response
        echo json_encode([
            'status' => 'success', 
            'message' => 'Service added successfully', 
            'service_id' => $service_id
        ]);
        
    } catch(PDOException $e) {
        // Return error response
        echo json_encode(['status' => 'error', 'message' => 'Failed to add service: ' . $e->getMessage()]);
    }
} else {
    // Return error for invalid request method
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?> 