<?php
// Include database configuration
require_once 'config.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Get form data
    $business_name = $_POST['business_name'] ?? '';
    $business_type = $_POST['business_type'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $state = $_POST['state'] ?? '';
    $zip_code = $_POST['zip_code'] ?? '';
    $contact_person = $_POST['contact_person'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $opening_time = $_POST['opening_time'] ?? '';
    $closing_time = $_POST['closing_time'] ?? '';

    // Validate required fields
    $required_fields = [
        'business_name', 'business_type', 'address', 'city', 
        'state', 'zip_code', 'contact_person', 'phone', 
        'email', 'opening_time', 'closing_time'
    ];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Validate business type
    $valid_business_types = ['salon', 'medical', 'dental', 'spa', 'fitness'];
    if (!in_array($business_type, $valid_business_types)) {
        throw new Exception("Invalid business type");
    }

    // Insert into providers table
    $stmt = $conn->prepare("
        INSERT INTO providers (
            business_name, business_type, address, city, 
            state, zip_code, contact_person, phone, email,
            is_active
        ) VALUES (
            :business_name, :business_type, :address, :city,
            :state, :zip_code, :contact_person, :phone, :email,
            TRUE
        )
    ");

    $stmt->execute([
        ':business_name' => $business_name,
        ':business_type' => $business_type,
        ':address' => $address,
        ':city' => $city,
        ':state' => $state,
        ':zip_code' => $zip_code,
        ':contact_person' => $contact_person,
        ':phone' => $phone,
        ':email' => $email
    ]);

    // Get the last inserted provider ID
    $provider_id = $conn->lastInsertId();

    // Insert business hours for each day of the week
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $stmt = $conn->prepare("
        INSERT INTO business_hours (
            provider_id, day_of_week, opening_time, closing_time, is_open
        ) VALUES (
            :provider_id, :day_of_week, :opening_time, :closing_time, :is_open
        )
    ");

    foreach ($days as $day) {
        $is_open = ($day === 'Sunday') ? 0 : 1;
        $stmt->execute([
            ':provider_id' => $provider_id,
            ':day_of_week' => $day,
            ':opening_time' => $opening_time,
            ':closing_time' => $closing_time,
            ':is_open' => $is_open
        ]);
    }

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Provider registered successfully',
        'provider_id' => $provider_id
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    // Return error response
    echo json_encode([
        'status' => 'error',
        'message' => 'Registration failed: ' . $e->getMessage()
    ]);
} 