<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$conn = new mysqli("localhost", "root", "Vikram@2005", "hospital_database");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => "Connection failed: " . $conn->connect_error]);
    exit;
}

$doctor_id = $_SESSION['doctor_id'];
$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
$prescription_id = isset($_GET['prescription_id']) ? (int)$_GET['prescription_id'] : 0;

if (!$patient_id) {
    echo json_encode(['success' => false, 'error' => 'Missing patient ID']);
    exit;
}

// Fetch doctor info
$doctor = null;
$stmt = $conn->prepare("SELECT full_name, qualifications FROM doctors WHERE doctor_id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $doctor = $result->fetch_assoc();
}
$stmt->close();

// Fetch patient info
$patient = null;
$stmt = $conn->prepare("SELECT name, address, age, gender FROM patients WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $patient = $result->fetch_assoc();
}
$stmt->close();

// Fetch latest prescription for this patient (optionally by ID)
$prescription = null;
if ($prescription_id) {
    $stmt = $conn->prepare("SELECT prescription_text, prescribed_date, diagnosis FROM prescription WHERE id = ? AND patient_id = ?");
    $stmt->bind_param("ii", $prescription_id, $patient_id);
} else {
    $stmt = $conn->prepare("SELECT prescription_text, prescribed_date, diagnosis FROM prescription WHERE patient_id = ? ORDER BY prescribed_date DESC LIMIT 1");
    $stmt->bind_param("i", $patient_id);
}
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $prescription = $result->fetch_assoc();
}
$stmt->close();

// Hospital info (static for now)
$hospital = [
    'name' => 'V-CARE',
    'address' => 'Address Here Number 123',
    'phone' => '55 47 79 94 15',
    'email' => 'email_hero@email.com',
    'website' => 'www.webpage.com',
    'slogan' => 'SLOGAN HERE'
];

if ($doctor && $patient && $prescription) {
    echo json_encode([
        'success' => true,
        'doctor' => $doctor,
        'patient' => $patient,
        'prescription' => $prescription,
        'hospital' => $hospital
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Data not found']);
}

$conn->close(); 