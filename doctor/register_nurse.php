<?php
header('Content-Type: application/json');

require_once 'db_connection.php';

try {
    // Get JSON data from request body
    $jsonData = file_get_contents('php://input');
    error_log("Received data: " . $jsonData); // Log received data
    
    $data = json_decode($jsonData, true);
    
    if (!$data) {
        throw new Exception('Invalid request data: ' . json_last_error_msg());
    }

    // Log the received data
    error_log("Processing nurse registration for: " . ($data['full_name'] ?? 'unknown'));

    // Validate required fields
    $required_fields = [
        'full_name', 'email', 'phone', 'gender', 
        'date_of_birth', 'nursing_qualifications', 
        'years_of_experience', 'username', 'password'
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

    // Validate years of experience
    if (!is_numeric($data['years_of_experience']) || $data['years_of_experience'] < 0) {
        throw new Exception('Years of experience must be a positive number');
    }

    $conn = get_db_connection();
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT nurse_id FROM Nurses WHERE username = ?");
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
        $stmt = $conn->prepare("SELECT nurse_id FROM Nurses WHERE email = ?");
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

        // Store original password
        $original_password = $data['password'];

        // Insert nurse data with all fields matching your table structure
        $sql = "INSERT INTO Nurses (
            full_name, 
            email, 
            phone, 
            gender, 
            date_of_birth, 
            nursing_qualifications, 
            years_of_experience, 
            username, 
            password
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare insert query: ' . $conn->error);
        }

        // Format date to match MySQL date format
        $formatted_date = date('Y-m-d', strtotime($data['date_of_birth']));

        $stmt->bind_param(
            "ssssssiss",
            $data['full_name'],
            $data['email'],
            $data['phone'],
            $data['gender'],
            $formatted_date,
            $data['nursing_qualifications'],
            $data['years_of_experience'],
            $data['username'],
            $original_password  // Store original password
        );

        if (!$stmt->execute()) {
            throw new Exception('Failed to insert nurse data: ' . $stmt->error);
        }

        $nurse_id = $stmt->insert_id;
        
        // Verify the insertion
        $verify_stmt = $conn->prepare("SELECT nurse_id FROM Nurses WHERE nurse_id = ?");
        if (!$verify_stmt) {
            throw new Exception('Failed to prepare verification query: ' . $conn->error);
        }
        
        $verify_stmt->bind_param("i", $nurse_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();

        if ($verify_result->num_rows === 0) {
            throw new Exception('Failed to verify nurse registration');
        }

        // If everything is successful, commit the transaction
        $conn->commit();
        
        error_log("Nurse registration successful for ID: $nurse_id");

        echo json_encode([
            'success' => true,
            'message' => 'Nurse registered successfully',
            'nurse_id' => $nurse_id
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