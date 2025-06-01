<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['doctor'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli("localhost", "root", "Vikram@2005", "hospital_database");
    
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    $doctorId = $_SESSION['doctor']['doctor_id'];
    $fullName = $_POST['fullName'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $qualifications = $_POST['qualifications'];
    $yearsOfExperience = (int)$_POST['yearsOfExperience'];
    
    // Update doctor profile
    $stmt = $conn->prepare("UPDATE Doctors SET full_name = ?, email = ?, phone = ?, qualifications = ?, years_of_experience = ? WHERE doctor_id = ?");
    $stmt->bind_param("ssssis", $fullName, $email, $phone, $qualifications, $yearsOfExperience, $doctorId);
    
    if ($stmt->execute()) {
        // Update session data
        $_SESSION['doctor']['full_name'] = $fullName;
        $_SESSION['doctor']['email'] = $email;
        $_SESSION['doctor']['phone'] = $phone;
        $_SESSION['doctor']['qualifications'] = $qualifications;
        $_SESSION['doctor']['years_of_experience'] = $yearsOfExperience;
        
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    }
    
    $stmt->close();
    $conn->close();
}
?> 