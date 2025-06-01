<?php
// db_connect.php - Database connection file
$host = 'localhost';
$dbname = 'hospital_database';
$username = 'root';
$password = 'Vikram@2005';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// get_patients.php - Fetch patients list
include 'db_connect.php';
$query = "SELECT id, full_name, date_of_birth FROM patients";
$stmt = $pdo->query($query);
echo json_encode($stmt->fetchAll());

// save_prescription.php - Save prescription to database
include 'db_connect.php';
$data = json_decode(file_get_contents("php://input"), true);
$query = "INSERT INTO prescription (patient_id, prescription_text) VALUES (?, ?)";
$stmt = $pdo->prepare($query);
$stmt->execute([$data['patient_id'], $data['prescription_text']]);
echo json_encode(["message" => "Prescription saved successfully"]);

// get_medical_history.php - Fetch medical history
include 'db_connect.php';
$patient_id = $_GET['patient_id'];
$query = "SELECT * FROM medical_history WHERE patient_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$patient_id]);
echo json_encode($stmt->fetchAll());
