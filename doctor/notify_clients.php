<?php
// Simple endpoint to notify WebSocket clients
// This can be called from other PHP scripts to broadcast messages

// Add CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = file_get_contents("php://input");
    $jsonData = json_decode($data, true);
    
    if (isset($jsonData["message"])) {
        // Write to a notification file that WebSocket clients can check
        file_put_contents(__DIR__ . "/notifications.txt", json_encode($jsonData) . "\n", FILE_APPEND);
        echo json_encode(["status" => "success", "message" => "Notification sent"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid message format"]);
    }
} else {
    // For GET requests, return the latest notification
    if (file_exists(__DIR__ . "/notifications.txt")) {
        $notifications = file_get_contents(__DIR__ . "/notifications.txt");
        $lines = explode("\n", $notifications);
        $latestLine = "";
        
        foreach (array_reverse($lines) as $line) {
            if (!empty(trim($line))) {
                $latestLine = $line;
                break;
            }
        }
        
        if (!empty($latestLine)) {
            echo $latestLine;
        } else {
            echo json_encode(["status" => "success", "message" => "No notifications"]);
        }
    } else {
        echo json_encode(["status" => "success", "message" => "No notifications"]);
    }
}
?> 