<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

try {
    // Get JSON data from request body
    $jsonData = file_get_contents('php://input');
    error_log("Received data: " . $jsonData);
    
    $data = json_decode($jsonData, true);
    
    if (!$data) {
        throw new Exception('Invalid request data: ' . json_last_error_msg());
    }

    // Log the received data
    error_log("Processing doctor registration for: " . ($data['full_name'] ?? 'unknown'));

    // Validate required fields
    $required_fields = [
        'full_name', 'email', 'phone', 'gender', 
        'qualifications', 'username', 'password'
    ];

    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }

    // Validate email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Validate phone number (basic validation)
    if (!preg_match('/^[0-9]{10,15}$/', $data['phone'])) {
        throw new Exception('Invalid phone number format. Please enter 10-15 digits only.');
    }

    $conn = get_db_connection();
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT doctor_id FROM Doctors WHERE username = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare username check query: ' . $conn->error);
        }
        $stmt->bind_param("s", $data['username']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception('Username already exists');
        }
        $stmt->close();

        // Check if email already exists
        $stmt = $conn->prepare("SELECT doctor_id FROM Doctors WHERE email = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare email check query: ' . $conn->error);
        }
        $stmt->bind_param("s", $data['email']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception('Email already exists');
        }
        $stmt->close();

        // Create Doctors table if it doesn't exist
        $create_table_sql = "CREATE TABLE IF NOT EXISTS Doctors (
            doctor_id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            phone VARCHAR(15) NOT NULL,
            gender VARCHAR(10) NOT NULL,
            qualifications TEXT NOT NULL,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if (!$conn->query($create_table_sql)) {
            throw new Exception('Failed to create Doctors table: ' . $conn->error);
        }

        // Insert doctor data
        $sql = "INSERT INTO Doctors (
            full_name, email, phone, gender,
            qualifications, username, password
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare insert query: ' . $conn->error);
        }

        $stmt->bind_param(
            "sssssss",
            $data['full_name'],
            $data['email'],
            $data['phone'],
            $data['gender'],
            $data['qualifications'],
            $data['username'],
            $data['password']
        );

        if (!$stmt->execute()) {
            throw new Exception('Failed to insert doctor data: ' . $stmt->error);
        }

        $doctor_id = $stmt->insert_id;
        
        // Verify the insertion
        $verify_stmt = $conn->prepare("SELECT doctor_id FROM Doctors WHERE doctor_id = ?");
        if (!$verify_stmt) {
            throw new Exception('Failed to prepare verification query: ' . $conn->error);
        }
        
        $verify_stmt->bind_param("i", $doctor_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();

        if ($verify_result->num_rows === 0) {
            throw new Exception('Failed to verify doctor registration');
        }

        // If everything is successful, commit the transaction
        $conn->commit();
        
        error_log("Doctor registration successful for ID: $doctor_id");

        echo json_encode([
            'success' => true,
            'message' => 'Doctor registered successfully',
            'doctor_id' => $doctor_id
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Registration error: " . $e->getMessage());
        throw $e;
    }

} catch (Exception $e) {
    error_log("Registration failed: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($verify_stmt)) {
        $verify_stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?> 