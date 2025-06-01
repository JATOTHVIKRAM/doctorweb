<?php
function get_db_connection() {
    $host = 'localhost';
    $username = 'root';
    $password = 'Vikram@2005';
    $database = 'hospital_database';

    try {
        // First connect without database to check/create it
        $conn = new mysqli($host, $username, $password);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        // Create database if it doesn't exist
        $sql = "CREATE DATABASE IF NOT EXISTS " . $database;
        if (!$conn->query($sql)) {
            throw new Exception("Error creating database: " . $conn->error);
        }

        // Close the initial connection
        $conn->close();

        // Reconnect with database selected
        $conn = new mysqli($host, $username, $password, $database);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Set charset to utf8mb4
        $conn->set_charset("utf8mb4");

        // Create Nurses table if it doesn't exist
        $create_table_sql = "CREATE TABLE IF NOT EXISTS Nurses (
            nurse_id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            phone VARCHAR(15) NOT NULL,
            gender VARCHAR(10) NOT NULL,
            date_of_birth DATE NOT NULL,
            nursing_qualifications TEXT NOT NULL,
            years_of_experience INT NOT NULL,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if (!$conn->query($create_table_sql)) {
            throw new Exception("Error creating Nurses table: " . $conn->error);
        }
        
        return $conn;
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
}
?> 