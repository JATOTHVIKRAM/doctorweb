<?php
require_once 'db_connection.php';

try {
    $conn = get_db_connection();
    
    // Check if Administrators table exists
    $result = $conn->query("SHOW TABLES LIKE 'Administrators'");
    
    if ($result->num_rows === 0) {
        // Create Administrators table if it doesn't exist
        $sql = "CREATE TABLE Administrators (
            admin_id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($sql)) {
            echo "Administrators table created successfully.<br>";
            
            // Add a default admin user
            $username = "admin";
            $password = password_hash("admin123", PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO Administrators (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $password);
            
            if ($stmt->execute()) {
                echo "Default admin user created:<br>";
                echo "Username: admin<br>";
                echo "Password: admin123<br>";
            } else {
                echo "Failed to create default admin user.<br>";
            }
        } else {
            echo "Failed to create Administrators table.<br>";
        }
    } else {
        echo "Administrators table already exists.<br>";
        
        // Check if there are any admin users
        $result = $conn->query("SELECT COUNT(*) as count FROM Administrators");
        $row = $result->fetch_assoc();
        
        if ($row['count'] == 0) {
            // Add a default admin user if no users exist
            $username = "admin";
            $password = password_hash("admin123", PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO Administrators (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $password);
            
            if ($stmt->execute()) {
                echo "Default admin user created:<br>";
                echo "Username: admin<br>";
                echo "Password: admin123<br>";
            } else {
                echo "Failed to create default admin user.<br>";
            }
        } else {
            echo "Admin users exist in the table.<br>";
        }
    }
    
    // Display current admin users
    $result = $conn->query("SELECT admin_id, username FROM Administrators");
    echo "<br>Current admin users:<br>";
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['admin_id'] . ", Username: " . $row['username'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 