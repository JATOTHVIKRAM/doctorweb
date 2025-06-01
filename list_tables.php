<?php
require_once 'config.php';

try {
    $conn = getDBConnection();
    
    // Get all tables
    $result = $conn->query("SHOW TABLES");
    
    if ($result->num_rows > 0) {
        echo "Tables in hospital database:\n";
        while ($row = $result->fetch_array()) {
            echo "- " . $row[0] . "\n";
            
            // Get table structure
            $tableName = $row[0];
            $structure = $conn->query("DESCRIBE $tableName");
            
            echo "  Columns:\n";
            while ($column = $structure->fetch_assoc()) {
                echo "    - " . $column['Field'] . " (" . $column['Type'] . ")\n";
            }
            echo "\n";
        }
    } else {
        echo "No tables found in the database.";
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 