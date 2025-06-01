<?php
require_once 'db_connection.php';

try {
    $conn = get_db_connection();
    
    // First, check if the Administrators table exists
    $result = $conn->query("SHOW TABLES LIKE 'Administrators'");
    
    if ($result->num_rows === 0) {
        // Create Administrators table
        $sql = "CREATE TABLE Administrators (
            admin_id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if (!$conn->query($sql)) {
            throw new Exception("Failed to create Administrators table: " . $conn->error);
        }
        echo "Administrators table created successfully.<br>";
    }
    
    // Clear existing admin users
    $conn->query("TRUNCATE TABLE Administrators");
    
    // Add the admin user with actual credentials
    $username = "ramuser";
    $password = "123456"; // Using actual password
    
    $stmt = $conn->prepare("INSERT INTO Administrators (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $password);
    
    if ($stmt->execute()) {
        echo "Admin user created successfully:<br>";
        echo "Username: " . $username . "<br>";
        echo "Password: " . $password . "<br>";
        
        // Verify the inserted data
        $verify = $conn->query("SELECT * FROM Administrators");
        $admin = $verify->fetch_assoc();
        echo "<br>Verification of stored data:<br>";
        echo "Stored username: " . $admin['username'] . "<br>";
        echo "Stored password: " . $admin['password'] . "<br>";
    } else {
        throw new Exception("Failed to create admin user: " . $stmt->error);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?> 