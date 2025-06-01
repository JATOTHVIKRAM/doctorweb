<?php
require_once 'db_connection.php';

try {
    $conn = get_db_connection();
    
    // Query to get all admin users
    $sql = "SELECT admin_id, username, password FROM Administrators";
    $result = $conn->query($sql);
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Admin Credentials</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
                background-color: #f5f5f5;
            }
            table {
                width: 100%;
                max-width: 800px;
                margin: 20px auto;
                border-collapse: collapse;
                background-color: white;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
            th, td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }
            th {
                background-color: #1976D2;
                color: white;
            }
            tr:hover {
                background-color: #f5f5f5;
            }
            .no-data {
                text-align: center;
                padding: 20px;
                color: #666;
            }
        </style>
    </head>
    <body>';
    
    if ($result->num_rows > 0) {
        echo "<h2 style='text-align: center; color: #333;'>Current Admin Users in Database</h2>";
        echo "<table>";
        echo "<tr><th>Admin ID</th><th>Username</th><th>Password</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['admin_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
            echo "<td>" . htmlspecialchars($row['password']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='no-data'>No admin users found in the database.</div>";
    }
    
    echo '</body></html>';
    
} catch (Exception $e) {
    echo "<div style='color: red; text-align: center; padding: 20px;'>Error: " . $e->getMessage() . "</div>";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 