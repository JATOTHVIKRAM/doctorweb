<?php
header('Content-Type: application/json');
session_start();

// Set session variables without authentication
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';
$_SESSION['admin_logged_in'] = true;
$_SESSION['user_type'] = 'admin';
$_SESSION['last_activity'] = time();

// Always return success
echo json_encode([
    'success' => true,
    'message' => 'Login successful'
]);
?> 