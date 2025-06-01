<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (isset($data['username']) && isset($data['password'])) {
        $username = $data['username'];
        $password = $data['password'];
        
        try {
            // Query to check nurse credentials
            $query = "SELECT id, name, username FROM nurses 
                     WHERE username = ? AND password = ?";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $username, $password);
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Login successful
                    $nurse = $result->fetch_assoc();
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Login successful',
                        'data' => [
                            'id' => $nurse['id'],
                            'name' => $nurse['name'],
                            'username' => $nurse['username']
                        ]
                    ]);
                } else {
                    // Invalid credentials
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Invalid username or password'
                    ]);
                }
            } else {
                throw new Exception("Error executing query: " . $stmt->error);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error during login: ' . $e->getMessage()
            ]);
        } finally {
            if (isset($stmt)) {
                $stmt->close();
            }
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Username and password are required'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
}

$conn->close();
?> 