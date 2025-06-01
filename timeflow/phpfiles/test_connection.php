<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";

// Database configuration
$servername = "localhost";
$username = "root";
$password = "Vikram@2005";
$dbname = "timeflow_db";

// Test MySQL server connection first
try {
    $conn = new PDO("mysql:host=$servername", $username, $password);
    echo "<p style='color: green;'>✓ Successfully connected to MySQL server</p>";
} catch(PDOException $e) {
    echo "<p style='color: red;'>✗ MySQL server connection failed: " . $e->getMessage() . "</p>";
    exit();
}

// Test specific database connection
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✓ Successfully connected to database '$dbname'</p>";
    
    // Test database tables
    $tables = array('providers', 'services', 'appointments', 'customers', 'business_hours');
    echo "<h3>Checking Database Tables:</h3>";
    
    foreach($tables as $table) {
        try {
            $result = $conn->query("SELECT 1 FROM $table LIMIT 1");
            echo "<p style='color: green;'>✓ Table '$table' exists</p>";
        } catch(PDOException $e) {
            echo "<p style='color: red;'>✗ Table '$table' not found or inaccessible</p>";
        }
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}

// Display PHP and MySQL version information
echo "<h3>System Information:</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";
try {
    $version = $conn->query('SELECT VERSION()')->fetch()[0];
    echo "<p>MySQL Version: " . $version . "</p>";
} catch(PDOException $e) {
    echo "<p style='color: red;'>Could not retrieve MySQL version</p>";
}
?> 