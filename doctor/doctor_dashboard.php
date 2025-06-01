<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prevent direct URL access and ensure proper authentication
if (!isset($_SESSION['doctor_id']) || !isset($_SESSION['doctor_email']) || !isset($_SESSION['doctor'])) {
    header("Location: ../mainpage.php");
    exit();
}

// Check session timeout (30 minutes)
if (!isset($_SESSION['last_activity']) || (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: ../mainpage.php?msg=expired");
    exit();
}
$_SESSION['last_activity'] = time();

$conn = new mysqli("localhost", "root", "Vikram@2005", "hospital_database");
if ($conn->connect_error) die("Database Connection Failed: " . $conn->connect_error);

// Verify doctor exists in database
$doctor_id = $_SESSION['doctor_id'];
$stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE doctor_id = ? LIMIT 1");
$stmt->bind_param("s", $doctor_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    session_unset();
    session_destroy();
    header("Location: ../mainpage.php?msg=invalid");
    exit();
}
$stmt->close();

// Check if doctor is logged in
if (!isset($_SESSION['doctor'])) {
    header("Location: doctor_login.php");
    exit();
}

// Fetch Patients based on time range
if (isset($_SESSION['doctor'])) {
    $today = date('Y-m-d');
    $timeRange = isset($_GET['range']) ? $_GET['range'] : 'today';
    
    if ($timeRange === 'week') {
        // Get patients from last 7 days, excluding today
        $query = "SELECT DISTINCT 
                    p.patient_id,
                    p.name,
                    p.gender,
                    ph.phone_number,
                    ph.age,
                    ph.diagnosis,
                    ph.visited_at,
                    ph.blood_pressure,
                    ph.temperature,
                    ph.weight,
                    ph.pulse_rate,
                    ph.respiratory_rate,
                    ph.critical_condition,
                    CASE WHEN cp.patient_id IS NOT NULL THEN 1 ELSE 0 END as is_completed
                 FROM patients p 
                 INNER JOIN patient_history ph ON p.patient_id = ph.patient_id 
                 LEFT JOIN completed_patients cp ON p.patient_id = cp.patient_id
                 WHERE DATE(ph.visited_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
                 AND DATE(ph.visited_at) < CURDATE()
                 ORDER BY is_completed ASC, ph.visited_at ASC";
        $stmt = $conn->prepare($query);
    } else {
        // Get only today's patients
        $query = "SELECT DISTINCT 
                    p.patient_id,
                    p.name,
                    p.gender,
                    ph.phone_number,
                    ph.age,
                    ph.diagnosis,
                    ph.visited_at,
                    ph.blood_pressure,
                    ph.temperature,
                    ph.weight,
                    ph.pulse_rate,
                    ph.respiratory_rate,
                    ph.critical_condition,
                    CASE WHEN cp.patient_id IS NOT NULL THEN 1 ELSE 0 END as is_completed
                 FROM patients p 
                 INNER JOIN patient_history ph ON p.patient_id = ph.patient_id 
                 LEFT JOIN completed_patients cp ON p.patient_id = cp.patient_id
                 WHERE DATE(ph.visited_at) = CURDATE() 
                 ORDER BY is_completed ASC, ph.visited_at ASC";
        $stmt = $conn->prepare($query);
    }
    
    $stmt->execute();
    $patients = $stmt->get_result();
}

// Fetch Patient Details via AJAX
if (isset($_GET['patient_id']) && isset($_SESSION['doctor'])) {
    $patient_id = (int)$_GET['patient_id'];
    $query = "SELECT 
                p.patient_id,
                p.name,
                p.gender,
                ph.phone_number,
                ph.age,
                ph.weight,
                ph.blood_pressure,
                ph.temperature,
                ph.pulse_rate,
                ph.respiratory_rate,
                ph.diagnosis,
                ph.visited_at,
                pr.prescription_text
             FROM patients p 
             INNER JOIN patient_history ph ON p.patient_id = ph.patient_id 
             LEFT JOIN prescription pr ON ph.patient_id = pr.patient_id 
                AND DATE(ph.visited_at) = DATE(pr.prescribed_date)
             WHERE p.patient_id = ? 
             ORDER BY ph.visited_at DESC 
             LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode($row);
    }
    exit;
}

// Add this at the beginning of the PHP section, after session_start()
if (isset($_POST['mark_complete']) && isset($_POST['patient_id'])) {
    $patient_id = (int)$_POST['patient_id'];
    
    // Insert into completed_patients table
    $insert_query = "INSERT INTO completed_patients (patient_id) VALUES (?) ON DUPLICATE KEY UPDATE completed_at = CURRENT_TIMESTAMP";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    
    exit(json_encode(['success' => true]));
}

// Handle Username Update
if (isset($_POST['action']) && $_POST['action'] === 'update_username') {
    $new_username = $_POST['new_username'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    
    if (empty($new_username) || empty($current_password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    // Verify current password
    $verify_query = "SELECT password FROM doctors WHERE doctor_id = ?";
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("i", $_SESSION['doctor_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $doctor = $result->fetch_assoc();

    if (!$doctor || !password_verify($current_password, $doctor['password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit;
    }

    // Check if username already exists
    $check_query = "SELECT doctor_id FROM doctors WHERE username = ? AND doctor_id != ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("si", $new_username, $_SESSION['doctor_id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username already exists']);
        exit;
    }

    // Update username
    $update_query = "UPDATE doctors SET username = ? WHERE doctor_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_username, $_SESSION['doctor_id']);

    if ($stmt->execute()) {
        $_SESSION['username'] = $new_username;
        echo json_encode(['success' => true, 'message' => 'Username updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update username']);
    }
    exit;
}

// Handle Password Update
if (isset($_POST['action']) && $_POST['action'] === 'update_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
        exit;
    }

    if (strlen($new_password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
        exit;
    }

    // Verify current password
    $verify_query = "SELECT password FROM doctors WHERE doctor_id = ?";
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("i", $_SESSION['doctor_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $doctor = $result->fetch_assoc();

    if (!$doctor || !password_verify($current_password, $doctor['password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit;
    }

    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $update_query = "UPDATE doctors SET password = ? WHERE doctor_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $hashed_password, $_SESSION['doctor_id']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update password']);
    }
    exit;
}

// Add this near the top of the PHP section, after session_start()
if (isset($_GET['refresh_list']) && isset($_SESSION['doctor'])) {
    // For debugging
    error_log("Refresh list called with time_range: " . (isset($_GET['time_range']) ? $_GET['time_range'] : 'not set'));
    
    if (isset($_GET['time_range']) && $_GET['time_range'] === 'all') {
        // Keep existing all patients query
        $query = "SELECT 
                    p.patient_id,
                    p.name,
                    p.gender,
                    latest_visit.phone_number,
                    latest_visit.age,
                    latest_visit.diagnosis,
                    latest_visit.visited_at,
                    latest_visit.blood_pressure,
                    latest_visit.temperature,
                    latest_visit.weight,
                    latest_visit.pulse_rate,
                    latest_visit.respiratory_rate,
                    latest_visit.critical_condition,
                    CASE WHEN cp.patient_id IS NOT NULL THEN 1 ELSE 0 END as is_completed
                FROM patients p
                INNER JOIN (
                    SELECT ph1.*
                    FROM patient_history ph1
                    INNER JOIN (
                        SELECT patient_id, MAX(visited_at) as max_visit
                        FROM patient_history
                        GROUP BY patient_id
                    ) ph2 ON ph1.patient_id = ph2.patient_id AND ph1.visited_at = ph2.max_visit
                ) latest_visit ON p.patient_id = latest_visit.patient_id
                LEFT JOIN completed_patients cp ON p.patient_id = cp.patient_id
                ORDER BY latest_visit.visited_at DESC";
        error_log("Using 'all' query");
    } else {
        // Completely revised query for today's patients
        $today = date('Y-m-d');
        error_log("Today's date: " . $today);
        
        $query = "SELECT 
                    p.*,
                    ph.phone_number,
                    ph.age,
                    ph.diagnosis,
                    ph.visited_at,
                    ph.blood_pressure,
                    ph.temperature,
                    ph.weight,
                    ph.pulse_rate,
                    ph.respiratory_rate,
                    ph.critical_condition,
                    CASE WHEN cp.patient_id IS NOT NULL THEN 1 ELSE 0 END as is_completed
                FROM patients p
                INNER JOIN patient_history ph ON p.patient_id = ph.patient_id AND DATE(ph.visited_at) = '{$today}'
                LEFT JOIN completed_patients cp ON p.patient_id = cp.patient_id
                ORDER BY ph.visited_at DESC";
        error_log("Using 'today' query with date: " . $today);
    }
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $patients = $stmt->get_result();
    error_log("Query returned " . $patients->num_rows . " patients");
    
    // Output only the patient list HTML
    if (isset($patients) && $patients->num_rows > 0) {
        while ($row = $patients->fetch_assoc()) {
            $visitTime = date('h:i A', strtotime($row['visited_at']));
            $visitDate = date('M d, Y', strtotime($row['visited_at']));
            $gender = strtolower($row['gender']);
            $genderIcon = $gender === 'male' ? 'male' : 'female';
            $isCompleted = isset($row['is_completed']) && $row['is_completed'] ? 'completed' : '';
            ?>
            <div class="patient-card <?php echo $isCompleted; ?>" data-patient-id="<?php echo $row['patient_id']; ?>">
                <?php if ($row['critical_condition'] === 'yes') { ?>
                    <span class="critical-dot" title="Critical Condition"></span>
                <?php } ?>
                <div class="card-header">
                    <div class="patient-info">
                        <div class="avatar-circle <?php echo $gender; ?>">
                            <i class="fas fa-<?php echo $genderIcon; ?>"></i>
                        </div>
                        <div class="name-id-container">
                            <h6 class="patient-name mb-0"><?php echo $row['name']; ?></h6>
                            <div class="patient-id">
                                <i class="fas fa-id-card"></i>
                                <span>ID: <?php echo $row['patient_id']; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="visit-time">
                        <?php echo $visitTime; ?>
                        <small class="text-muted d-block"><?php echo $visitDate; ?></small>
                    </div>
                </div>
            </div>
            <?php
        }
    } else {
        // Show message when no patients are found
        echo '<div class="text-center text-muted py-4">No patients found for today</div>';
    }
    exit;
}

// Modify the initial patient list query to use the same logic
if (!isset($_GET['patient_id'])) {
    // Check for range parameter to set default view
    $range = isset($_GET['range']) ? $_GET['range'] : 'all';
    
    if ($range === 'today') {
        // Completely revised query for today's patients
        $today = date('Y-m-d');
        
        $query = "SELECT 
                    p.*,
                    ph.phone_number,
                    ph.age,
                    ph.diagnosis,
                    ph.visited_at,
                    ph.blood_pressure,
                    ph.temperature,
                    ph.weight,
                    ph.pulse_rate,
                    ph.respiratory_rate,
                    ph.critical_condition,
                    CASE WHEN cp.patient_id IS NOT NULL THEN 1 ELSE 0 END as is_completed
                FROM patients p
                INNER JOIN patient_history ph ON p.patient_id = ph.patient_id AND DATE(ph.visited_at) = '{$today}'
                LEFT JOIN completed_patients cp ON p.patient_id = cp.patient_id
                ORDER BY ph.visited_at DESC";
    } else {
        // Keep existing all patients query
        $query = "SELECT 
                    p.patient_id,
                    p.name,
                    p.gender,
                    latest_visit.phone_number,
                    latest_visit.age,
                    latest_visit.diagnosis,
                    latest_visit.visited_at,
                    latest_visit.blood_pressure,
                    latest_visit.temperature,
                    latest_visit.weight,
                    latest_visit.pulse_rate,
                    latest_visit.respiratory_rate,
                    latest_visit.critical_condition,
                    CASE WHEN cp.patient_id IS NOT NULL THEN 1 ELSE 0 END as is_completed
                FROM patients p
                INNER JOIN (
                    SELECT ph1.*
                    FROM patient_history ph1
                    INNER JOIN (
                        SELECT patient_id, MAX(visited_at) as max_visit
                        FROM patient_history
                        GROUP BY patient_id
                    ) ph2 ON ph1.patient_id = ph2.patient_id AND ph1.visited_at = ph2.max_visit
                ) latest_visit ON p.patient_id = latest_visit.patient_id
                LEFT JOIN completed_patients cp ON p.patient_id = cp.patient_id
                ORDER BY latest_visit.visited_at DESC";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $patients = $stmt->get_result();
}

// Handle the AJAX request for patient lists
if (isset($_GET['get_patients'])) {
    $response = array();
    
    // Determine which time range to use
    $range = isset($_GET['range']) ? $_GET['range'] : 'today';
    
    if ($range === 'today') {
        // Using explicit date format for comparison
        $today = date('Y-m-d');
        error_log("Getting patients for today's date: " . $today);
        
        // Debug log to show actual records in table
        $debug_query = "SELECT COUNT(*) as count, DATE(visited_at) as visit_date FROM patient_history GROUP BY DATE(visited_at)";
        $debug_result = $conn->query($debug_query);
        while($debug_row = $debug_result->fetch_assoc()) {
            error_log("Date in DB: " . $debug_row['visit_date'] . " with " . $debug_row['count'] . " records");
        }
        
        $query = "SELECT 
                    p.patient_id,
                    p.name,
                    p.gender,
                    ph.phone_number,
                    ph.age,
                    ph.diagnosis,
                    ph.visited_at,
                    ph.blood_pressure,
                    ph.temperature,
                    ph.weight,
                    ph.pulse_rate,
                    ph.respiratory_rate,
                    ph.critical_condition,
                    CASE WHEN cp.patient_id IS NOT NULL THEN 1 ELSE 0 END as is_completed
                FROM patient_history ph
                INNER JOIN patients p ON ph.patient_id = p.patient_id
                LEFT JOIN completed_patients cp ON p.patient_id = cp.patient_id
                WHERE DATE(ph.visited_at) = CURRENT_DATE()
                ORDER BY ph.visited_at ASC";
                
        $stmt = $conn->prepare($query);
    } else {
        // All patients query remains the same
        error_log("Getting all patients in FIFO order");
        
        $query = "SELECT 
                    p.patient_id,
                    p.name,
                    p.gender,
                    latest_visit.phone_number,
                    latest_visit.age,
                    latest_visit.diagnosis,
                    latest_visit.visited_at,
                    latest_visit.blood_pressure,
                    latest_visit.temperature,
                    latest_visit.weight,
                    latest_visit.pulse_rate,
                    latest_visit.respiratory_rate,
                    latest_visit.critical_condition,
                    CASE WHEN cp.patient_id IS NOT NULL THEN 1 ELSE 0 END as is_completed
                FROM patients p
                INNER JOIN (
                    SELECT ph1.*
                    FROM patient_history ph1
                    INNER JOIN (
                        SELECT patient_id, MIN(visited_at) as first_visit
                        FROM patient_history
                        GROUP BY patient_id
                    ) ph2 ON ph1.patient_id = ph2.patient_id AND ph1.visited_at = ph2.first_visit
                ) latest_visit ON p.patient_id = latest_visit.patient_id
                LEFT JOIN completed_patients cp ON p.patient_id = cp.patient_id
                ORDER BY latest_visit.visited_at ASC";
                
        $stmt = $conn->prepare($query);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $patients = array();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Convert visited_at to proper format for display
            $row['visited_at_formatted'] = date('M d, h:i A', strtotime($row['visited_at']));
            $patients[] = $row;
        }
        error_log("Query returned " . count($patients) . " patients");
    } else {
        error_log("Query returned 0 patients - SQL: " . str_replace("\n", " ", $query));
    }
    
    $response['success'] = true;
    $response['patients'] = $patients;
    $response['range'] = $range;
    $response['today'] = $today;
    $response['server_date'] = date('Y-m-d H:i:s');
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// After database connection, fetch doctor details
$doctor_id = $_SESSION['doctor_id'];
$stmt = $conn->prepare("SELECT * FROM doctors WHERE doctor_id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$doctor_result = $stmt->get_result();
$doctor_details = $doctor_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
    <link rel="shortcut icon" type="image/x-icon" href="vcarelogo.ico" />
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="js/live-dashboard.js"></script>
    <style>
        /* Custom Stylesheet */
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #34495e;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #3498db;
        }

        /* Add this for prescriptions styling */
        .prescription-entry {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .prescription-entry:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .prescription-number {
            color: var(--primary-color);
            font-size: 1rem;
        }
        
        .prescription-time {
            font-size: 0.85rem;
            background-color: #f8f9fa;
            padding: 4px 10px;
            border-radius: 12px;
        }
        
        .prescription-content {
            margin-top: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 6px;
            border-left: 3px solid var(--primary-color);
            white-space: pre-line;
        }
        
        .previous-prescriptions {
            max-height: 500px;
            overflow-y: auto;
            padding-right: 10px;
        }

        body {
            background-color: var(--background-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .dashboard-container {
            padding: 20px;
            margin-top: 20px;
        }

        .panel {
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            height: calc(100vh - 100px);
            overflow-y: auto;
            padding: 20px;
            position: relative;
        }

        .patient-list-panel {
            border-right: 1px solid #eee;
        }

        .search-box {
            position: sticky;
            top: 0;
            background: white;
            padding: 10px 0;
            z-index: 100;
        }

        .search-box input {
            border-radius: 25px;
            padding: 10px 20px;
            border: 2px solid #eee;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(191, 194, 175, 0.25);
        }

        .patient-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .patient-item {
            width: 100%;
            margin: 0 0 8px 0; /* Reduced margin between items */
            padding: 10px 15px; /* Reduced vertical padding */
            border-radius: 8px;
            background-color: #ffffff;
            border: 1px solid #e8e8e8;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
            transition: all 0.2s ease;
        }

        .patient-item:hover {
            border-color: #2196F3;
            box-shadow: 0 4px 8px rgba(33, 150, 243, 0.1);
            transform: translateX(5px);
        }

        .patient-item .patient-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px; /* Reduced margin */
        }

        .patient-item .patient-name-section {
            display: flex;
            align-items: center;
            gap: 8px; /* Reduced gap */
        }

        .patient-item .gender-icon {
            width: 32px; /* Reduced size */
            height: 32px; /* Reduced size */
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 0.9rem; /* Reduced font size */
        }

        .patient-item h5 {
            margin: 0;
            font-size: 1.1rem; /* Slightly reduced font size */
            font-weight: 600;
            color: rgb(208, 217, 227);
            letter-spacing: 0.2px;
        }

        .patient-item .time-info {
            display: flex;
            align-items: center;
            gap: 10px; /* Reduced gap */
        }

        .patient-item .time-badge {
            display: flex;
            align-items: center;
            gap: 4px; /* Reduced gap */
            font-size: 0.85rem; /* Reduced font size */
            color: #5c6c7c;
            background-color: #f8f9fa;
            padding: 3px 8px; /* Reduced padding */
            border-radius: 4px;
        }

        .patient-item .patient-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 6px 0; /* Reduced padding */
            border-top: 1px solid #f0f0f0;
            margin: 6px 0; /* Reduced margin */
        }

        .patient-item .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem; /* Reduced font size */
            color: #5c6c7c;
        }

        .patient-item .diagnosis-preview {
            font-size: 0.85rem; /* Reduced font size */
            color: #5c6c7c;
            margin-top: 6px; /* Reduced margin */
            padding-left: 24px; /* Reduced padding */
            position: relative;
            line-height: 1.4; /* Reduced line height */
            max-height: 2.8em; /* Limit to 2 lines */
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .date-separator {
            padding: 8px 15px; /* Reduced padding */
            margin: 15px 0 10px 0; /* Reduced margins */
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.95rem; /* Reduced font size */
            border-bottom: 1px solid #e8e8e8; /* Thinner border */
            display: flex;
            align-items: center;
            gap: 6px; /* Reduced gap */
        }

        .date-separator i {
            color: #2196F3;
        }

        .search-box {
            position: sticky;
            top: 0;
            background: white;
            padding: 15px;
            border-bottom: 1px solid #eee;
            z-index: 100;
        }

        .search-box input {
            border-radius: 25px;
            padding: 10px 20px;
            border: 2px solid #eee;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .badge {
            font-size: 0.8rem;
            padding: 0.4em 0.8em;
        }

        .alert {
            border-radius: 8px;
            border-left: 4px solid var(--secondary-color);
        }

        .patient-details {
            padding: 20px;
            border-radius: 8px;
            background: #fff;
            margin-bottom: 20px;
        }

        .prescription-section {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .medical-history {
            position: relative;
            padding-top: 0;
        }

        .back-to-current {
            position: sticky;
            top: 0;
            z-index: 10;
            background: white;
            padding: 15px;
            border-bottom: 1px solid #eee;
            margin: -15px -15px 15px -15px;
        }

        .back-to-current .btn {
            transition: all 0.3s ease;
        }

        .back-to-current .btn:hover {
            transform: translateX(-5px);
        }

        .list-group {
            margin-top: 15px;
        }

        .list-group-item {
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .list-group-item:hover {
            border-left-color: var(--primary-color);
            background-color: #f8f9fa;
        }

        .list-group-item.active {
            border-left-color: var(--primary-color);
            background-color: #f8f9fa;
            color: inherit;
        }

        .visit-date-item {
            padding: 15px;
            border-left: 4px solid transparent;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 1px solid #eee;
            background: white;
            contain: content;
            will-change: transform;
            transform: translateZ(0);
        }

        .visit-date-item:hover {
            border-left-color: var(--secondary-color);
            background-color: #f8f9fa;
        }

        .visit-date-item.active {
            border-left-color: var(--secondary-color);
            background-color: #f8f9fa;
        }

        .visit-details-container {
            display: none;
            padding: 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            contain: content;
            will-change: transform, opacity;
            transform: translateZ(0);
            transition: all 0.2s ease-out;
        }

        .medical-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 10px;
            margin-top: 10px;
            contain: layout style;
        }

        .metric-item {
            background: white;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #eee;
            contain: content;
            transition: transform 0.2s ease-out;
        }

        .metric-item i {
            font-size: 1.2rem;
            margin-right: 8px;
        }

        .metric-label {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 2px;
        }

        .metric-value {
            font-weight: 500;
            color: var(--primary-color);
        }

        .diagnosis-section,
        .prescription-section {
            margin-top: 15px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 1px solid #eee;
        }

        .section-header {
            display: flex;
            align-items: center;
            color: var(--primary-color);
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .section-header i {
            margin-right: 8px;
        }

        .section-content {
            font-size: 0.9rem;
            line-height: 1.5;
            color: #444;
        }

        .visit-date-item .date {
            font-weight: 500;
            color: var(--primary-color);
        }

        .visit-date-item .time {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .diagnosis-preview {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .visit-date-item:hover .diagnosis-preview {
            color: var(--primary-color);
        }

        .fa-chevron-right {
            font-size: 0.8rem;
            opacity: 0.5;
            transition: transform 0.3s ease;
        }

        .visit-date-item:hover .fa-chevron-right {
            transform: translateX(3px);
            opacity: 1;
            color: var(--secondary-color);
        }

        .visit-details {
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-custom {
            border-radius: 25px;
            padding: 8px 20px;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--secondary-color);
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--secondary-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-color);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .panel {
                height: auto;
                margin-bottom: 20px;
            }
        }

        .bg-secondary {
            background-color: #6c757d !important;
        }

        .bg-success {
            background-color: #28a745 !important;
        }

        .time-range-toggle .btn-group {
            border-radius: 8px;
            overflow: hidden;
        }

        .time-range-toggle .btn {
            padding: 8px;
            flex: 1;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .time-range-toggle .btn:hover {
            transform: translateY(-1px);
        }

        .date-separator {
            padding: 10px 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin: 15px 0 10px 0;
            font-weight: 500;
            color: var(--primary-color);
            font-size: 0.9rem;
            border-left: 4px solid var(--secondary-color);
        }

        .btn-outline-primary {
            color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-outline-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            color: white;
        }

        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .history-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .history-modal.show {
            display: flex;
            opacity: 1;
        }

        .history-modal-content {
            background: white;
            width: 90%;
            max-width: 600px;
            margin: auto;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transform: translateY(-20px);
            transition: transform 0.2s ease;
        }

        .show .history-modal-content {
            transform: translateY(0);
        }

        .history-modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .history-modal-header h5 {
            margin: 0;
            color: var(--primary-color);
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
        }

        .close-modal:hover {
            background: #f0f0f0;
            color: #333;
        }

        .history-modal-body {
            padding: 20px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .quick-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }

        .quick-metric {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
        }

        .quick-metric i {
            font-size: 1.2rem;
            margin-bottom: 5px;
        }

        .quick-metric-value {
            font-weight: 500;
            color: var(--primary-color);
        }

        .quick-metric-label {
            font-size: 0.8rem;
            color: #666;
        }

        .history-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .history-section-title {
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 10px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .history-section-content {
            font-size: 0.9rem;
            line-height: 1.5;
            color: #444;
        }

        .loading-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 200px;
        }
        
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
        
        .history-modal {
            backdrop-filter: blur(5px);
        }
        
        .history-modal-content {
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.2s ease-out;
        }
        
        .show .history-modal-content {
            opacity: 1;
            transform: translateY(0);
        }
        
        .quick-metric {
            transform: translateY(0);
            transition: transform 0.2s ease-out;
        }
        
        .quick-metric:hover {
            transform: translateY(-2px);
        }

        .visit-date-item {
            border-left: 4px solid transparent;
            transition: all 0.2s ease;
        }
        
        .visit-date-item.active {
            border-left-color: var(--secondary-color);
            background-color: #f8f9fa;
        }
        
        .visit-details-section {
            background: #f8f9fa;
            border-left: 4px solid var(--secondary-color);
            margin: 0;
            overflow: hidden;
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(-10px);
            animation: slideDown 0.3s ease forwards;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .visit-details-content {
            padding: 15px;
            opacity: 0;
            animation: fadeIn 0.3s ease 0.2s forwards;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        .metric {
            transform: translateY(20px);
            opacity: 0;
            animation: slideUp 0.3s ease forwards;
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .metric:nth-child(1) { animation-delay: 0.1s; }
        .metric:nth-child(2) { animation-delay: 0.2s; }
        .metric:nth-child(3) { animation-delay: 0.3s; }
        .metric:nth-child(4) { animation-delay: 0.4s; }
        .metric:nth-child(5) { animation-delay: 0.5s; }
        
        .visit-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
            padding: 10px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .metric {
            text-align: center;
            padding: 8px;
            border-radius: 6px;
            background: #f8f9fa;
        }
        
        .metric i {
            display: block;
            font-size: 1.2rem;
            margin-bottom: 5px;
        }
        
        .metric .value {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--primary-color);
        }
        
        .visit-info {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .info-section {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .info-section:last-child {
            border-bottom: none;
        }
        
        .info-header {
            font-weight: 500;
            color: var(--primary-color);
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .info-content {
            font-size: 0.9rem;
            color: #444;
            line-height: 1.5;
        }
        
        .loading-state {
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .spinner-border-sm {
            width: 1.2rem;
            height: 1.2rem;
            border-width: 0.15em;
        }

        /* Fix for nested items */
        .patient-list > * {
            display: block;
            width: 100%;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            background: var(--primary-color);
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            animation-duration: 0.5s;
        }

        body {
            padding-top: 70px; /* Add padding for fixed navbar */
        }
        
        .avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .doctor-avatar {
            position: relative;
        }

        .doctor-info {
            line-height: 1.2;
        }

        /* Dark Mode Styles */
        body.dark-mode {
            background-color: #121212;
            color: #e0e0e0;
        }

        body.dark-mode .navbar {
            background-color: #1e1e1e !important;
            border-color: #333;
        }

        body.dark-mode .navbar-brand,
        body.dark-mode .nav-link {
            color: #e0e0e0;
        }

        body.dark-mode .panel {
            background-color: #1e1e1e;
            border-color: #333;
        }

        body.dark-mode .section-title {
            color: #e0e0e0;
            border-bottom-color: #333;
        }

        body.dark-mode .back-to-current {
            color: #90CAF9;
        }

        body.dark-mode .list-group-item {
            background-color: #1e1e1e;
            color: #e0e0e0;
        }

        body.dark-mode .list-group-item:hover,
        body.dark-mode .list-group-item.active {
            background-color: #333;
        }

        /* Theme Colors */
        :root {
            --theme-primary: #3498db;
            --theme-secondary: #2c3e50;
        }

        [data-theme="green"] {
            --theme-primary: #2ecc71;
            --theme-secondary: #27ae60;
        }

        [data-theme="blue"] {
            --theme-primary: #3498db;
            --theme-secondary: #2980b9;
        }

        [data-theme="orange"] {
            --theme-primary: #e67e22;
            --theme-secondary: #d35400;
        }

        .icon-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }

        .icon-circle i {
            font-size: 1.25rem;
        }

        .vital-card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            border-radius: 12px;
        }

        .vital-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
        }

        .vital-card .card-body {
            padding: 1rem;
        }

        .vital-card h5.fw-bold {
            font-size: 1.25rem;
        }

        .vital-card h6 {
            font-size: 0.875rem;
        }

        .vital-card small {
            font-size: 0.75rem;
        }

        .text-muted {
            color: #6c757d !important;
        }

        .vital-card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            border-radius: 12px;
        }

        .vital-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
        }

        .icon-circle {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }

        .info-item {
            transition: transform 0.2s ease;
        }

        .info-item:hover {
            transform: translateX(5px);
        }

        .card {
            overflow: hidden;
        }

        .card-header {
            border-bottom: none;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 .25rem .5rem rgba(0,0,0,.1);
        }

        .text-muted {
            color: #6c757d !important;
        }

        .fw-medium {
            font-weight: 500;
        }

        .gender-icon-circle {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .diagnosis-text {
            font-size: 1rem;
            line-height: 1.6;
            color: #2c3e50;
        }

        .icon-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            transition: transform 0.2s ease;
        }

        .icon-circle:hover {
            transform: scale(1.1);
        }

        h5.text-primary {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .vital-card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            border-radius: 12px;
        }

        .vital-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
        }

        .info-item {
            transition: transform 0.2s ease;
        }

        .info-item:hover {
            transform: translateX(5px);
        }

        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #eee;
        }

        .section-header .icon-circle {
            margin-right: 1rem;
            margin-left: 0;
            width: 40px;
            height: 40px;
        }

        .section-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
            display: flex;
            align-items: center;
        }

        .section-title .patient-name {
            color: #6c757d;
            font-size: 1rem;
            margin-left: 0.5rem;
        }

        .vital-card {
            height: 100%;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .vital-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
        }

        .vital-card .icon-circle {
            width: 50px;
            height: 50px;
            margin-bottom: 1rem;
        }

        .vital-card .icon-circle i {
            font-size: 1.25rem;
        }

        .diagnosis-section {
            background: #fff;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .patient-header {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            border-radius: 8px 8px 0 0;
            margin: -1.5rem -1.5rem 1.5rem -1.5rem;
        }

        .patient-name {
            font-size: 1.75rem;
            color: #007bff;
            font-weight: 700;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .info-card {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid #eee;
            transition: all 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .info-card .icon-circle {
            width: 45px;
            height: 45px;
            min-width: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .info-content {
            text-align: right;
            margin-right: 15px;
        }

        .info-label {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 2px;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 500;
        }

        .info-card {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid #eee;
            transition: all 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .info-card .icon-circle {
            width: 45px;
            height: 45px;
            min-width: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .info-card .text-start {
            font-size: 1.1rem;
            margin-left: 2cm; /* Add 2cm padding between icon and text */
        }

        .ms-5 {
            margin-left: 2cm !important; /* Override Bootstrap's margin with 2cm */
        }

        /* Add these styles at the appropriate location in your CSS */
        .patient-info-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            margin-bottom: 10px;
        }

        .icon-container {
            flex-shrink: 0;
            margin-left: 15px; /* Add some space from the left edge */
        }

        .info-content {
            margin-left: 10px; /* Changed from 2cm to 10px to place text closer to icon */
            flex-grow: 1;
        }

        .icon-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.1);
        }

        /* Override any conflicting margins */
        /*.ms-3, .ms-5 {
            margin-left: 2cm !important;
        }*/

        .d-flex.align-items-center {
            justify-content: flex-start !important;
        }

        /* Add these styles for the new patient visit design */
        .patient-visits-header {
            margin-bottom: 20px;
            border-bottom: 2px solid #2196F3;
            padding-bottom: 10px;
        }

        .patient-visits-header h3 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .time-range-toggle {
            margin-bottom: 15px;
        }

        .time-range-toggle .btn-group {
            width: 100%;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
        }

        .time-range-toggle .btn {
            flex: 1;
            padding: 10px;
            border: none;
            background: #fff;
            color: #2196F3;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .time-range-toggle .btn.active {
            background: #2196F3;
            color: #fff;
        }

        .time-range-toggle .btn i {
            margin-right: 5px;
        }

        .search-box {
            margin-bottom: 20px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 25px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .search-box .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9e9e9e;
        }

        .patient-item {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            background: #fff;
            border: 1px solid #e0e0e0;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .patient-item:hover {
            transform: translateX(5px);
        }

        .patient-item.selected {
            background: #2196F3;
            border-color: #2196F3;
            color: #fff;
        }

        .patient-item .patient-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .patient-item .patient-name {
            font-size: 1.1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .patient-item .gender-icon {
            color: inherit;
        }

        .patient-item .visit-time {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.85rem;
            background: #4CAF50;
            color: #fff;
        }

        .patient-item .patient-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 0.9rem;
            color: inherit;
            opacity: 0.8;
        }

        .patient-item .diagnosis {
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            color: inherit;
            opacity: 0.8;
        }

        /* Alternative Modern Patient Visit Design */
        .patient-visits-container {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
        }

        .patient-visits-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }

        .patient-visits-header .icon-circle {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }

        .patient-visits-header .icon-circle i {
            color: white;
            font-size: 1.25rem;
        }

        .patient-visits-header h3 {
            color: #1e293b;
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .time-range-toggle {
            background: white;
            padding: 5px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .time-range-toggle .btn-group {
            display: flex;
            width: 100%;
            gap: 5px;
        }

        .time-range-toggle .btn {
            flex: 1;
            padding: 12px;
            border-radius: 8px;
            font-weight: 500;
            color: #64748b;
            background: transparent;
            border: none;
            transition: all 0.3s ease;
        }

        .time-range-toggle .btn.active {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        }

        .time-range-toggle .btn:hover:not(.active) {
            background: #f1f5f9;
            color: #1e293b;
        }

        .search-container {
            position: relative;
            margin-bottom: 25px;
        }

        .search-container input {
            width: 100%;
            padding: 15px 45px 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: white;
        }

        .search-container input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .search-container i {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.1rem;
        }

        .patient-list {
            display: grid;
            gap: 15px;
        }

        .patient-card {
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .patient-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .critical-dot {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 0.4cm;
            height: 0.4cm;
            background-color: #dc3545;
            border-radius: 50%;
            z-index: 1;
        }

        .patient-card .d-flex {
            position: relative;
            padding-right: 20px; /* Add space for the dot */
        }

        .patient-card .badge {
            background-color: #6c757d;
            color: #fff;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        /* Add styles for selected patient card */
        .patient-card.selected {
            border: 2px solid #3498db;
            background: linear-gradient(to right, rgba(52, 152, 219, 0.1), rgba(52, 152, 219, 0.05));
            transform: translateX(10px) scale(1.02);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.15);
            z-index: 1;
        }

        .patient-card.selected::before {
            content: '';
            position: absolute;
            left: -2px;
            top: 0;
            height: 100%;
            width: 4px;
            background: #3498db;
            border-radius: 2px;
        }

        /* Add pulse animation for selected patient */
        @keyframes selectedPulse {
            0% {
                box-shadow: 0 4px 12px rgba(52, 152, 219, 0.15);
            }
            50% {
                box-shadow: 0 4px 20px rgba(52, 152, 219, 0.3);
            }
            100% {
                box-shadow: 0 4px 12px rgba(52, 152, 219, 0.15);
            }
        }

        .patient-card.selected {
            animation: selectedPulse 2s infinite;
        }

        /* Add hover effect for all cards */
        .patient-card:hover {
            opacity: 1;
            transform: scale(1.02);
            transition: all 0.2s ease;
        }

        .patient-card.completed {
            opacity: 0.8;
            border-color: #10b981;
            order: 1; /* Push completed items to bottom */
        }

        .completion-mark {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            color: #10b981;
            font-size: 1.2rem;
            display: none;
        }

        .patient-card.completed .completion-mark {
            display: block;
        }

        .patient-list {
            display: flex;
            flex-direction: column;
        }

        /* Button styling */
        .mark-complete-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .mark-complete-btn:hover {
            background: #059669;
        }

        .mark-complete-btn i {
            font-size: 1rem;
        }

        .patient-card .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 4px;
            padding: 0;
        }

        .patient-info {
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;  /* Added for absolute positioning of children */
        }

        .avatar-circle {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            position: relative;  /* Added for absolute positioning of children */
        }

        .avatar-circle.male {
            background: linear-gradient(135deg, #60a5fa, #3b82f6);
        }

        .avatar-circle.female, .avatar-circle.other {
            background: linear-gradient(135deg, #f472b6, #ec4899);
        }

        .avatar-circle i {
            font-size: 1rem;
            color: white;
        }

        .name-id-container {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .patient-name {
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
            line-height: 1.2;
        }

        .patient-id {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #64748b;
            font-size: 0.8rem;
        }

        .patient-id i {
            color: #3b82f6;
            font-size: 0.8rem;
        }

        .time-badge {
            font-size: 0.85rem;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .diagnosis-preview {
            margin-top: 4px;
            color: #475569;
            font-size: 0.85rem;
            line-height: 1.2;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            flex-grow: 1;
        }

        /* Add these styles for the vitals section */
        .vitals-scroll-container {
            width: 100%;
            overflow-x: auto;
            padding: 10px 0;
            /* Hide scrollbar for IE, Edge and Firefox */
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* Hide scrollbar for Chrome, Safari and Opera */
        .vitals-scroll-container::-webkit-scrollbar {
            display: none;
        }

        .vitals-row {
            display: flex;
            flex-wrap: nowrap;
            margin-right: -10px;
            margin-left: -10px;
            padding: 0 10px;
        }

        .col-vitals {
            flex: 0 0 auto;
            width: 220px;
            padding: 0 10px;
        }

        .vital-card {
            height: 100%;
            min-width: 200px;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            cursor: pointer;
        }

        .vital-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
        }

        .vital-card .card-body {
            padding: 1.25rem;
        }

        .vital-card .icon-circle {
            width: 50px;
            height: 50px;
            margin-bottom: 1rem;
        }

        .vital-card h6 {
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .vital-card h5 {
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }

        .vital-card small {
            font-size: 0.75rem;
            opacity: 0.8;
        }

        /* Add smooth scrolling behavior */
        .vitals-scroll-container {
            scroll-behavior: smooth;
        }

        /* Add scroll indicators */
        .vitals-scroll-container::before,
        .vitals-scroll-container::after {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            width: 40px;
            pointer-events: none;
            z-index: 1;
        }

        .vitals-scroll-container::before {
            left: 0;
            background: linear-gradient(to right, rgba(255,255,255,0.9), rgba(255,255,255,0));
        }

        .vitals-scroll-container::after {
            right: 0;
            background: linear-gradient(to left, rgba(255,255,255,0.9), rgba(255,255,255,0));
        }

        /* Add these styles for the patient info items */
        .patient-info-item {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            margin-bottom: 8px;
            border-radius: 8px;
            background: #f8f9fa;
            transition: all 0.2s ease;
        }

        .patient-info-item:hover {
            background: #f0f4f8;
            transform: translateX(5px);
        }

        .icon-container {
            flex-shrink: 0;
            margin-right: 1cm; /* Set exact 1cm spacing */
            display: flex;
            align-items: center;
        }

        .info-content {
            flex-grow: 1;
            display: flex;
            align-items: baseline;
            gap: 8px;
        }

        .info-content .text-muted {
            min-width: 100px;
            font-size: 0.85rem;
        }

        .info-content .fw-medium {
            font-size: 1rem;
            color: #2c3e50;
        }

        .icon-circle {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease;
        }

        .patient-info-item:hover .icon-circle {
            transform: scale(1.1);
        }

        .icon-circle i {
            font-size: 1rem;
        }

        /* Update the basic patient information section */
        .basic-info-section {
            background: white;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .basic-info-title {
            font-size: 1rem;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .basic-info-title i {
            color: #3498db;
        }

        .patient-card.completed {
            border-left: 4px solid #10b981 !important;
            background-color: rgba(16, 185, 129, 0.05) !important;
        }

        .patient-card.completed .patient-name {
            color: #10b981;
        }

        .mark-complete-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .mark-complete-btn:hover {
            background: #059669;
        }

        .mark-complete-btn:disabled {
            background: #d1d5db;
            cursor: not-allowed;
        }

        .mark-complete-btn i {
            font-size: 1rem;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            background: #10b981;
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .gender-icon-circle {
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .gender-icon-circle:hover {
            transform: scale(1.05);
        }

        .gender-icon-circle i {
            font-size: 2rem;
        }

        .patient-header-info {
            transition: all 0.3s ease;
        }

        .patient-header-info:hover {
            transform: translateX(5px);
        }

        .mark-complete-btn {
            background: rgba(255, 255, 255, 0.9);
            color: #2c3e50;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .mark-complete-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #10b981, #059669);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .mark-complete-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            color: white;
        }

        .mark-complete-btn:hover:not(:disabled)::before {
            opacity: 1;
        }

        .mark-complete-btn:disabled {
            background: #e5e7eb;
            color: #9ca3af;
            cursor: not-allowed;
        }

        .mark-complete-btn i,
        .mark-complete-btn span {
            position: relative;
            z-index: 1;
        }

        .mark-complete-btn:hover:not(:disabled) i,
        .mark-complete-btn:hover:not(:disabled) span {
            color: white;
        }

        .card-header {
            border-radius: 15px 15px 0 0;
        }

        .display-6 {
            font-size: 2.5rem;
            letter-spacing: -0.5px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .card-header {
                padding: 1.5rem;
            }

            .display-6 {
                font-size: 1.8rem;
            }

            .gender-icon-circle {
                width: 50px;
                height: 50px;
            }

            .gender-icon-circle i {
                font-size: 1.5rem;
            }

            .mark-complete-btn {
                padding: 8px 16px;
                font-size: 0.9rem;
            }
        }

        .patient-header-info h2 {
            color: #000 !important;
            margin: 0;
            display: inline-block;
        }

        .patient-header-info .text-white-50 {
            margin-top: 8px;
        }

        .count-badge {
            background: #f8f9fa;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .count-badge:hover {
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transform: translateY(-1px);
        }

        .count-badge i {
            color: #3498db;
        }

        .search-container, .count-container {
            flex: 0.8;  /* Reduced from 1 to make it smaller */
            position: sticky;
            top: 0;
            z-index: 1000;
            background: white;
            padding: 10px;  /* Reduced padding */
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .search-container input, .count-badge {
            width: 90%;  /* Reduced from 100% */
            height: 35px;  /* Reduced from 45px */
            padding: 6px 12px;  /* Reduced padding */
            border-radius: 6px;  /* Slightly reduced border radius */
            border: 1px solid #e9ecef;
            background: #f8f9fa;
            font-size: 0.9rem;  /* Slightly smaller font */
            transition: all 0.3s ease;
        }

        .search-container input:focus {
            outline: none;
            border-color: #3498db;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .count-badge {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: 500;
            color: #2c3e50;
            background: #f8f9fa;
            cursor: default;
        }

        .count-badge:hover {
            background: #fff;
            border-color: #3498db;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .count-label {
            display: flex;
            align-items: center;
            color: #2c3e50;
        }

        .count-label i {
            color: #3498db;
            font-size: 1.1rem;
        }

        .count-value {
            font-weight: 600;
            color: #3498db;
            background: rgba(52, 152, 219, 0.1);
            padding: 4px 12px;
            border-radius: 6px;
        }

        .search-container i {
            position: absolute;
            right: 12%;  /* Adjusted for new width */
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.9rem;  /* Reduced icon size */
        }

        @media (max-width: 768px) {
            .d-flex {
                flex-direction: column;
            }
            
            .search-container, .count-container {
                width: 100%;
            }
        }

        .d-flex justify-content-center align-items-center stats-badge {
            width: 100%;
            padding: 10px 16px;
            border-radius: 8px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .d-flex justify-content-center align-items-center stats-badge i {
            color: #3498db;
            font-size: 1.2rem;
        }

        .d-flex justify-content-center align-items-center stats-value {
            font-weight: 600;
            color: #3498db;
            background: rgba(52, 152, 219, 0.1);
            padding: 4px 12px;
            border-radius: 6px;
            min-width: 60px;
            text-align: center;
            margin-left: 8px;
        }

        .diagnosis-preview {
            margin-top: 10px;
            padding: 8px 12px;
            background: rgba(52, 152, 219, 0.05);
            border-radius: 6px;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: #2c3e50;
            transition: all 0.2s ease;
        }

        .diagnosis-preview:hover {
            background: rgba(52, 152, 219, 0.1);
        }

        .diagnosis-preview i {
            color: #3498db;
        }

        .diagnosis-text {
            margin-top: 8px;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: #2c3e50;
        }

        .diagnosis-text i {
            color: #3498db;
            font-size: 1rem;
        }

        /* Remove the old diagnosis-preview styles */
        .diagnosis-preview {
            margin-top: 8px;
            display: flex;
            align-items: center;
        }

        .doctor-profile .avatar-circle {
            background: linear-gradient(135deg, #4e73df, #224abe);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .doctor-profile .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .doctor-profile .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }

        .doctor-profile .info-item label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 5px;
            display: block;
        }

        .doctor-profile .info-item p {
            margin: 0;
            font-weight: 500;
            color: #2c3e50;
        }

        .dropdown-item {
            padding: 8px 16px;
            display: flex;
            align-items: center;
        }

        .dropdown-item i {
            width: 20px;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
        }

        .critical-dot {
            display: inline-block;
            width: 0.4cm;
            height: 0.4cm;
            background-color: #dc3545;
            border-radius: 50%;
            margin-right: 8px;
            vertical-align: middle;
        }

        .patient-card.completed {
            background-color: rgba(16, 185, 129, 0.05);
            border-left: 4px solid #10b981;
            order: 1;
        }

        .patient-card.completed .patient-name {
            color: #10b981;
        }

        .patient-card.completed .completion-mark {
            display: block;
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            color: #10b981;
            font-size: 1.2rem;
        }

        .patient-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .patient-card.completed {
            background-color: rgba(16, 185, 129, 0.05);
            border-left: 4px solid #10b981;
            order: 1;
        }

        .patient-card.completed .patient-name {
            color: #10b981;
        }

        .badge .fa-check-circle {
            color: #10b981;
        }

        .patient-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .patient-card.completed {
            background-color: rgba(16, 185, 129, 0.05);
            border-left: 4px solid #10b981;
            order: 1;
        }

        .patient-card.completed .patient-name {
            color: #10b981;
        }

        .visit-time {
            font-size: 0.9rem;
            color: #6B7280;
        }

        .visit-time .fa-check-circle {
            color: #10b981;
        }

        .patient-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .patient-card {
            background: white;
            border-radius: 8px;
            padding: 12px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid #e2e8f0;
            margin-bottom: 8px;
            position: relative;
        }

        .patient-name {
            font-size: 1rem;
            font-weight: 500;
            color: #2d3748;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .visit-time {
            font-size: 0.85rem;
            color: #6B7280;
            white-space: nowrap;
            text-align: right;
        }

        .patient-info {
            margin-top: 4px;
        }

        .patient-card.completed {
            background-color: rgba(16, 185, 129, 0.05);
            border-left: 4px solid #10b981;
            order: 1;
        }

        .patient-card.completed .patient-name {
            color: #10b981;
        }

        .visit-time .fa-check-circle {
            color: #10b981;
        }

        .patient-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .basic-info-section {
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            align-items: center;
            font-size: 1rem;
            color: #333;
        }
        .info-row i {
            width: 20px;
        }
        .basic-info-content {
            padding: 10px 0;
        }

        .patient-list-container {
            height: calc(100vh - 200px);
            overflow-y: auto;
            padding: 15px;
            margin-top: 10px;
        }

        .patient-list {
            margin-top: 10px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .critical-dot {
            position: absolute;
            top: -3px;
            right: -3px;
            width: 10px;  /* Smaller size for the dot */
            height: 10px;
            background-color: #dc3545;
            border-radius: 50%;
            border: 2px solid white;  /* White border around the dot */
            z-index: 2;
            margin: 0;  /* Remove default margin */
        }

        .avatar-circle {
            width: 35px;  /* Slightly increased size */
            height: 35px;
            border-radius: 50%;  /* Changed to circle */
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            position: relative;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .avatar-circle.male {
            background: linear-gradient(135deg, #60a5fa, #3b82f6);
        }

        .avatar-circle.female {
            background: linear-gradient(135deg, #f472b6, #ec4899);
        }

        .avatar-circle i {
            font-size: 1.2rem;
            color: white;
        }

        .critical-dot {
            position: absolute;
            top: -4px;
            right: -4px;
            width: 12px;
            height: 12px;
            background-color: #dc3545;
            border-radius: 50%;
            border: 2px solid white;
            z-index: 2;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.2);
                opacity: 0.8;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .logout-link {
            color: #dc3545;
            text-decoration: none;
            padding: 8px 16px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s;
        }

        .logout-link:hover {
            color: #bb2d3b;
        }

        .logout-link i {
            font-size: 1.1em;
        }
    </style>
</head>
<body>
<?php if (isset($_SESSION['doctor'])) { ?>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom fixed-top">
        <div class="container-fluid">
            <!-- Hospital Logo -->
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="v-care logo.png" alt="V-Care Logo" style="height: 80px; margin-right: 10px;">
            </a>

            <!-- Navbar Toggler -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navbar Content -->
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav ms-auto align-items-center">
                    <!-- Doctor Profile Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="doctor-avatar me-2">
                                <div class="avatar-circle bg-primary text-white">
                                    <?php echo strtoupper(substr($doctor_details['full_name'] ?? 'D', 0, 1)); ?>
                                </div>
                            </div>
                            <div class="doctor-info">
                                <div class="fw-bold"><?php echo htmlspecialchars($doctor_details['full_name'] ?? 'Doctor'); ?></div>
                                <div class="small text-muted">General Physician</div>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li>
                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#doctorProfileModal">
                                    <i class="fas fa-user-md me-2"></i>View Profile
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="handleLogout()">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Doctor Profile Modal -->
    <div class="modal fade" id="doctorProfileModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-user-md me-2"></i>Doctor Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="doctor-details bg-light p-3 rounded">
                        <div class="text-center mb-4">
                            <div class="doctor-avatar mx-auto mb-3">
                                <div class="avatar-circle bg-primary text-white" style="width: 100px; height: 100px; font-size: 2.5rem; line-height: 100px;">
                                    <?php echo strtoupper(substr($doctor_details['full_name'] ?? 'D', 0, 1)); ?>
                                </div>
                            </div>
                            <h4 class="mb-0"><?php echo htmlspecialchars($doctor_details['full_name'] ?? 'Doctor'); ?></h4>
                            <p class="text-muted">General Physician</p>
                        </div>
                        <div class="row mb-3">
                            <div class="col-4 text-muted">Username</div>
                            <div class="col-8"><?php echo htmlspecialchars($doctor_details['username'] ?? 'Not set'); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-4 text-muted">Email</div>
                            <div class="col-8"><?php echo htmlspecialchars($doctor_details['email'] ?? 'Not provided'); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-4 text-muted">Phone</div>
                            <div class="col-8"><?php echo htmlspecialchars($doctor_details['phone'] ?? 'Not provided'); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-4 text-muted">Experience</div>
                            <div class="col-8"><?php echo isset($doctor_details['years_of_experience']) ? htmlspecialchars($doctor_details['years_of_experience']) . ' years' : 'Not provided'; ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-4 text-muted">Qualifications</div>
                            <div class="col-8"><?php echo htmlspecialchars($doctor_details['qualifications'] ?? 'Not provided'); ?></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateProfileModal">
                        <i class="fas fa-edit me-2"></i>Edit Profile
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Username Modal -->
    <div class="modal fade" id="changeUsernameModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Username</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="changeUsernameForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">New Username</label>
                            <input type="text" class="form-control" name="new_username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <input type="hidden" name="action" value="update_username">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Username</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Profile Modal -->
    <div class="modal fade" id="updateProfileModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="updateProfileForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="fullName" value="<?php echo isset($_SESSION['doctor']['full_name']) ? htmlspecialchars($_SESSION['doctor']['full_name']) : ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?php echo isset($_SESSION['doctor']['email']) ? htmlspecialchars($_SESSION['doctor']['email']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" value="<?php echo isset($_SESSION['doctor']['phone']) ? htmlspecialchars($_SESSION['doctor']['phone']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Qualifications</label>
                            <textarea class="form-control" name="qualifications" rows="2"><?php echo isset($_SESSION['doctor']['qualifications']) ? htmlspecialchars($_SESSION['doctor']['qualifications']) : ''; ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Years of Experience</label>
                            <input type="number" class="form-control" name="yearsOfExperience" min="0" value="<?php echo isset($_SESSION['doctor']['years_of_experience']) ? htmlspecialchars($_SESSION['doctor']['years_of_experience']) : '0'; ?>">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

    <script>
        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const darkModeSwitch = document.getElementById('darkModeSwitch');
        
        // Check for saved theme preference
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
            darkModeSwitch.checked = true;
            themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        }

        // Theme toggle button click
        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            const isDarkMode = document.body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDarkMode);
            themeToggle.innerHTML = isDarkMode ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
            darkModeSwitch.checked = isDarkMode;
        });

        // Dark mode switch change
        darkModeSwitch.addEventListener('change', (e) => {
            document.body.classList.toggle('dark-mode', e.target.checked);
            localStorage.setItem('darkMode', e.target.checked);
            themeToggle.innerHTML = e.target.checked ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        });

        // Theme color buttons
        document.querySelectorAll('.theme-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const theme = btn.dataset.theme;
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('colorTheme', theme);
            });
        });

        // Change Password Form Submit
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            // Validate passwords match
            if (formData.get('newPassword') !== formData.get('confirmPassword')) {
                alert('New passwords do not match!');
                return;
            }

            // Send password change request
            fetch('change_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Password changed successfully!');
                    $('#changePasswordModal').modal('hide');
                    this.reset();
                } else {
                    alert(data.message || 'Failed to change password');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while changing password');
            });
        });

        // Load saved color theme
        const savedTheme = localStorage.getItem('colorTheme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        }

        // Add this to your existing JavaScript
        function handleLogout() {
            // Use the correct path to logout.php
            window.location.href = '../mainpage.php';
        }

        // Handle Username Change
        $('#changeUsernameForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: 'change_username.php',
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        alert('Username updated successfully!');
                        $('#changeUsernameModal').modal('hide');
                        location.reload();
                    } else {
                        alert(response.message || 'Failed to update username');
                    }
                },
                error: function() {
                    alert('An error occurred while updating username');
                }
            });
        });

        // Handle Profile Update
        $('#updateProfileForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: 'update_profile.php',
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        alert('Profile updated successfully!');
                        $('#updateProfileModal').modal('hide');
                        location.reload();
                    } else {
                        alert(response.message || 'Failed to update profile');
                    }
                },
                error: function() {
                    alert('An error occurred while updating profile');
                }
            });
        });

        // Handle Profile Image Update
        function updateProfileImage(input) {
            if (input.files && input.files[0]) {
                const formData = new FormData();
                formData.append('profile_image', input.files[0]);
                
                $.ajax({
                    url: 'update_profile_image.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert('Profile image updated successfully!');
                            location.reload();
                        } else {
                            alert(response.message || 'Failed to update profile image');
                        }
                    },
                    error: function() {
                        alert('An error occurred while updating profile image');
                    }
                });
            }
        }
    </script>

    <!-- Account Details Modal -->
    <div class="modal fade" id="accountModal" tabindex="-1" aria-labelledby="accountModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="accountModalLabel">My Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php
                    $doctor_id = $_SESSION['doctor_id'];
                    $query = "SELECT * FROM doctors WHERE doctor_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $doctor_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($doctor = $result->fetch_assoc()) {
                    ?>
                        <div class="doctor-profile">
                            <div class="text-center mb-4">
                                <div class="avatar-circle mx-auto mb-3" style="width: 100px; height: 100px;">
                                    <i class="fas fa-user-md fa-3x"></i>
                                </div>
                                <h4 class="mb-0"><?php echo htmlspecialchars($doctor['name']); ?></h4>
                                <p class="text-muted"><?php echo htmlspecialchars($doctor['specialization']); ?></p>
                            </div>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label><i class="fas fa-envelope me-2"></i>Email</label>
                                    <p><?php echo htmlspecialchars($doctor['email']); ?></p>
                                </div>
                                <div class="info-item">
                                    <label><i class="fas fa-phone me-2"></i>Phone</label>
                                    <p><?php echo htmlspecialchars($doctor['phone']); ?></p>
                                </div>
                                <div class="info-item">
                                    <label><i class="fas fa-graduation-cap me-2"></i>Qualifications</label>
                                    <p><?php echo htmlspecialchars($doctor['qualifications']); ?></p>
                                </div>
                                <div class="info-item">
                                    <label><i class="fas fa-briefcase me-2"></i>Experience</label>
                                    <p><?php echo htmlspecialchars($doctor['experience']); ?> years</p>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="changePasswordForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="currentPassword" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="newPassword" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirmPassword" required>
                        </div>
                        <input type="hidden" name="action" value="update_password">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Handle Username Change
    $('#changeUsernameForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if(response.success) {
                    alert('Username updated successfully');
                    $('#changeUsernameModal').modal('hide');
                } else {
                    alert(response.message || 'Failed to update username');
                }
            },
            error: function() {
                alert('An error occurred while updating username');
            }
        });
    });

    // Handle Password Change
    $('#changePasswordForm').on('submit', function(e) {
        e.preventDefault();
        if($('input[name="newPassword"]').val() !== $('input[name="confirmPassword"]').val()) {
            alert('New passwords do not match');
            return;
        }
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if(response.success) {
                    alert('Password updated successfully');
                    $('#changePasswordModal').modal('hide');
                } else {
                    alert(response.message || 'Failed to update password');
                }
            },
            error: function() {
                alert('An error occurred while updating password');
            }
        });
    });
    </script>

<?php } ?>

<div class="container-fluid dashboard-container">
    <?php if (!isset($_SESSION['doctor'])) { ?>
        <div class="card p-4 mx-auto" style="max-width: 400px;">
            <h2 class="text-center mb-4">Doctor Login</h2>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <input type="text" name="username" placeholder="Username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <input type="password" name="password" placeholder="Password" class="form-control" required>
                </div>
                <button type="submit" name="login" class="btn btn-custom btn-primary w-100">Login</button>
            </form>
        </div>
    <?php } else { ?>
        <div class="row">
            <!-- Left Column - Patient List -->
            <div class="col-lg-3">
                <div class="panel patient-list-panel">
                    <div class="patient-visits-container">
                        <div class="patient-visits-header">
                            <div class="icon-circle">
                                <i class="fas fa-user-clock"></i>
                            </div>
                            <h3>Patient Visits</h3>
                        </div>

                        <!-- Time Range Toggle -->
                        <div class="time-range-toggle mb-4">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-primary" id="today-btn" data-range="today">
                                    <i class="fas fa-calendar-day me-2"></i>Today
                                </button>
                                <button type="button" class="btn btn-outline-primary active" id="all-btn" data-range="all">
                                    <i class="fas fa-calendar-alt me-2"></i>All
                                </button>
                            </div>
                        </div>

                        <!-- Add this after the time range toggle div and before the search container -->
                        <div class="d-flex flex-column mb-3">
                            <div class="search-container mb-2">
                            <input type="text" id="search" placeholder="Search patients by name or ID..." onkeyup="searchPatient()">
                            <i class="fas fa-search"></i>
                        </div>
                            <div class="d-flex justify-content-center align-items-center stats-badge">
                                <i class="fas fa-users me-2"></i>
                                <span class="stats-value" id="patientCount">0/0</span>
                                            </div>
                                                </div>

                        <style>
                            .stats-badge {
                                width: 100%;
                                padding: 10px 16px;
                                border-radius: 8px;
                                background: #f8f9fa;
                                border: 1px solid #e9ecef;
                                display: flex;
                                align-items: center;
                                justify-content: space-between;
                                transition: all 0.3s ease;
                            }

                            .stats-badge:hover {
                                background: #fff;
                                border-color: #3498db;
                                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                            }

                            .stats-label i {
                                color: #3498db;
                                font-size: 1.2rem;
                            }

                            .stats-value {
                                font-weight: 600;
                                color: #3498db;
                                background: rgba(52, 152, 219, 0.1);
                                padding: 4px 12px;
                                border-radius: 6px;
                                min-width: 60px;
                                text-align: center;
                            }

                            @media (max-width: 768px) {
                                .stats-badge {
                                    justify-content: center;
                                    gap: 12px;
                                }
                            }
                        </style>

                        <div class="patient-list-container">
                            <div class="patient-list">
                                <!-- Patient cards will be loaded here via AJAX -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Middle Column - Patient Details & Prescription -->
            <div class="col-lg-6">
                <div class="panel">
                    <h3 class="section-title">Patient Information</h3>
                    <div id="patientDetails" class="patient-details"></div>

                    <div id="prescriptionSection" class="prescription-section" style="display: none;">
                        <h3 class="section-title">Prescription</h3>
                        <form id="prescriptionForm">
                            <input type="hidden" id="patient_id" name="patient_id">
                            <div class="mb-3">
                                <textarea id="prescription_text" name="prescription_text" class="form-control" rows="6" 
                                    placeholder="Write prescription here..." required></textarea>
                            </div>
                            <div class="action-buttons">
                                <button type="submit" class="btn btn-custom btn-success">
                                    <i class="fas fa-save"></i> Save Prescription
                                </button>
                                <button type="button" class="btn btn-custom btn-info" onclick="printPrescription()">
                                    <i class="fas fa-print"></i> Print
                                </button>
                            </div>
                        </form>
                        
                        <!-- Container for displaying previous prescriptions on the same day -->
                        <div id="previousPrescription" class="previous-prescriptions mt-4"></div>
                        
                        <div class="mt-3">
                            <button class="mark-complete-btn btn-lg shadow-sm" onclick="markAsComplete($('#patient_id').val())">
                                <i class="fas fa-check"></i>
                                <span class="ms-2">Mark as Complete</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Medical History -->
            <div class="col-lg-3">
                <div class="panel">
                    <h3 class="section-title">Medical History</h3>
                    <div id="medicalHistory" class="medical-history">
                        <!-- Visit dates and details will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php } ?>
</div>

<script>
// Global variable to track current range
let currentRange = 'all';

// Function to load patients based on time range
function loadPatients(range = 'today') {
    console.log("Loading patients with range:", range);
    currentRange = range;
    
    // Update active button state
    $('.time-range-toggle .btn').removeClass('active');
    $(`.time-range-toggle .btn[data-range="${range}"]`).addClass('active');
    
    // Show loading indicator
    $('.patient-list').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading patients...</p>
        </div>
    `);
    
    // Make AJAX request to get patients
    $.ajax({
        url: window.location.href,
        type: 'GET',
        data: {
            get_patients: true,
            range: range
        },
        dataType: 'json',
        success: function(response) {
            console.log("Patients loaded successfully:", response);
            console.log("Server date:", response.server_date);
            console.log("Today's date:", response.today);
            
            if (response.success && response.patients.length > 0) {
                let patientsHtml = '';
                
                // Generate HTML for each patient
                response.patients.forEach(function(patient) {
                    const visitDate = new Date(patient.visited_at);
                    const visitTime = visitDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    const visitDateStr = visitDate.toLocaleDateString([], {month: 'short', day: 'numeric', year: 'numeric'});
                    
                    const gender = patient.gender.toLowerCase();
                    const genderIcon = gender === 'male' ? 'male' : 'female';
                    const isCompleted = patient.is_completed == 1 ? 'completed' : '';
                    
                    patientsHtml += `
                        <div class="patient-card ${isCompleted}" data-patient-id="${patient.patient_id}" onclick="viewPatient(${patient.patient_id})">
                            ${patient.critical_condition === 'yes' ? '<span class="critical-dot" title="Critical Condition"></span>' : ''}
                            <div class="card-header">
                                <div class="patient-info">
                                    <div class="avatar-circle ${gender}">
                                        <i class="fas fa-${genderIcon}"></i>
                                    </div>
                                    <div class="name-id-container">
                                        <h6 class="patient-name mb-0">${patient.name}</h6>
                                        <div class="patient-id">
                                            <i class="fas fa-id-card"></i>
                                            <span>ID: ${patient.patient_id}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="visit-time">
                                    ${visitTime}
                                    <small class="text-muted d-block">${visitDateStr}</small>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                // Update the patient list
                $('.patient-list').html(patientsHtml);
                
                // Update patient count
                const totalPatients = response.patients.length;
                const completedPatients = response.patients.filter(p => p.is_completed == 1).length;
                $('#patientCount').text(`${completedPatients}/${totalPatients}`);
            } else {
                // Show no patients message
                $('.patient-list').html(`
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-user-slash mb-3" style="font-size: 2rem;"></i>
                        <p>No patients found for today (${response.today})</p>
                        <small class="d-block mt-2">Server time: ${response.server_date}</small>
                    </div>
                `);
                $('#patientCount').text('0/0');
            }
        },
        error: function(xhr, status, error) {
            console.error("Error loading patients:", error);
            $('.patient-list').html(`
                <div class="text-center text-danger py-4">
                    <i class="fas fa-exclamation-circle mb-3" style="font-size: 2rem;"></i>
                    <p>An error occurred while loading patients</p>
                    <pre class="text-start small mt-3 bg-light p-2 rounded">${xhr.responseText || error}</pre>
                    <button class="btn btn-outline-primary mt-2" onclick="loadPatients('${range}')">
                        <i class="fas fa-sync-alt me-2"></i>Try Again
                    </button>
                </div>
            `);
        }
    });
}

// Document ready function
$(document).ready(function() {
    console.log("Document ready");
    
    // Initial load of patients - show Today view first
    loadPatients('today');
    
    // Set Today button as active initially
    $("#today-btn").addClass('active');
    $("#all-btn").removeClass('active');
    
    // Handle time range button clicks
    $('.time-range-toggle .btn').on('click', function() {
        const range = $(this).data('range');
        console.log("Button clicked:", range);
        loadPatients(range);
    });
    
    // Set up auto-refresh
    setInterval(function() {
        console.log("Auto-refreshing with range:", currentRange);
        loadPatients(currentRange);
    }, 10000); // Refresh every 10 seconds
});

function searchPatient() {
    const input = document.getElementById("search").value.toLowerCase();
    const items = document.querySelectorAll(".patient-card");
    
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(input) ? "block" : "none";
    });
    
    // Update count after search
    updatePatientCount();
}

function viewPatient(id) {
    // Remove selected class from all patient cards
    $('.patient-card').removeClass('selected');
    
    // Add selected class to clicked patient card only
    $(`.patient-card[onclick*="${id}"]`).addClass('selected');

    $.ajax({
        url: "doctor_dashboard.php",
        type: "GET",
        data: { patient_id: id },
        success: function(response) {
            let patient = JSON.parse(response);
            let genderIcon = patient.gender.toLowerCase() === 'male' ? 
                '<i class="fas fa-male fa-2x text-primary"></i>' : 
                '<i class="fas fa-female fa-2x text-danger"></i>';
            
            let detailsHTML = `
                <div class="patient-info mb-4">
                    <div class="card shadow-sm border-0">
                        <!-- Patient Header with Gender Icon and Name -->
                        <div class="card-header bg-gradient py-4" style="background: ${patient.gender.toLowerCase() === 'male' ? 'linear-gradient(135deg, #4e73df, #224abe)' : 'linear-gradient(135deg, #e83e8c, #b21e5c)'}">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="gender-icon-circle bg-white p-3 me-4 rounded-circle shadow-sm">
                                        ${genderIcon}
                                    </div>
                                    <div class="patient-header-info">
                                        <h2 class="text-dark mb-1 display-6 fw-bold" style="color: #000 !important;">${patient.name}</h2>
                                        <div class="text-white-50 d-flex align-items-center">
                                            <i class="fas fa-calendar-alt me-2"></i>
                                            <span>Visit Date: ${new Date(patient.visited_at).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="ms-4">
                                    <!-- Removed Mark as Complete button -->
                                </div>
                            </div>
                        </div>

                        <style>
                            .gender-icon-circle {
                                width: 70px;
                                height: 70px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                transition: all 0.3s ease;
                            }

                            .gender-icon-circle:hover {
                                transform: scale(1.05);
                            }

                            .gender-icon-circle i {
                                font-size: 2rem;
                            }

                            .patient-header-info {
                                transition: all 0.3s ease;
                            }

                            .patient-header-info:hover {
                                transform: translateX(5px);
                            }

                            .mark-complete-btn {
                                background: rgba(255, 255, 255, 0.9);
                                color: #2c3e50;
                                border: none;
                                padding: 12px 24px;
                                border-radius: 12px;
                                font-size: 1rem;
                                font-weight: 600;
                                display: flex;
                                align-items: center;
                                gap: 8px;
                                transition: all 0.3s ease;
                                position: relative;
                                overflow: hidden;
                            }

                            .mark-complete-btn::before {
                                content: '';
                                position: absolute;
                                top: 0;
                                left: 0;
                                width: 100%;
                                height: 100%;
                                background: linear-gradient(45deg, #10b981, #059669);
                                opacity: 0;
                                transition: opacity 0.3s ease;
                            }

                            .mark-complete-btn:hover:not(:disabled) {
                                transform: translateY(-2px);
                                color: white;
                            }

                            .mark-complete-btn:hover:not(:disabled)::before {
                                opacity: 1;
                            }

                            .mark-complete-btn:disabled {
                                background: #e5e7eb;
                                color: #9ca3af;
                                cursor: not-allowed;
                            }

                            .mark-complete-btn i,
                            .mark-complete-btn span {
                                position: relative;
                                z-index: 1;
                            }

                            .mark-complete-btn:hover:not(:disabled) i,
                            .mark-complete-btn:hover:not(:disabled) span {
                                color: white;
                            }

                            .card-header {
                                border-radius: 15px 15px 0 0;
                            }

                            .display-6 {
                                font-size: 2.5rem;
                                letter-spacing: -0.5px;
                                text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
                            }

                            @media (max-width: 768px) {
                                .card-header {
                                    padding: 1.5rem;
                                }

                                .display-6 {
                                    font-size: 1.8rem;
                                }

                                .gender-icon-circle {
                                    width: 50px;
                                    height: 50px;
                                }

                                .gender-icon-circle i {
                                    font-size: 1.5rem;
                                }

                                .mark-complete-btn {
                                    padding: 8px 16px;
                                    font-size: 0.9rem;
                                }
                            }
                        </style>
                        
                        <div class="card-body p-4">
                            <!-- Basic Patient Information -->
                            <div class="basic-info-section">
                                <h4 class="mb-3">Basic Information</h4>
                                <div class="basic-info-content">
                                    <div class="info-row mb-2">
                                                <i class="fas fa-phone text-primary"></i>
                                              <h5>phone number:  </h5>
                                        <span class="ms-2">${patient.phone_number}</span>
                                            </div>
                                    <div class="info-row mb-2">
                                        <i class="fas fa-birthday-cake text-primary"></i>
                                        <h5>age:  </h5>
                                        <span class="ms-2">${patient.age} years</span>
                                        </div>
                                    <div class="info-row mb-2">
                                        <i class="fas ${patient.gender.toLowerCase() === 'male' ? 'fa-male' : 'fa-female'} text-primary"></i>
                                        <h5>gender:  </h5>
                                        <span class="ms-2">${patient.gender}</span>
                                    </div>
                                </div>
                            </div>

                            <style>
                                .basic-info-section {
                                    margin-bottom: 20px;
                                }
                                .info-row {
                                    display: flex;
                                    align-items: center;
                                    font-size: 1rem;
                                    color: #333;
                                }
                                .info-row i {
                                    width: 20px;
                                }
                                .basic-info-content {
                                    padding: 10px 0;
                                }
                            </style>

                            <!-- Current Vitals Section -->
                            <div class="mb-4">
                                <div class="section-header">
                                    <div class="icon-circle bg-primary bg-opacity-10">
                                        <i class="fas fa-heartbeat text-primary"></i>
                                    </div>
                                    <div class="section-title">Current Vitals</div>
                                </div>
                                <div class="vitals-scroll-container">
                                    <div class="row vitals-row g-4">
                                        <!-- Weight Card (First) -->
                                        <div class="col-vitals">
                                            <div class="vital-card card border-0 shadow-sm">
                                                <div class="card-body text-center p-3">
                                                    <div class="icon-circle bg-success bg-opacity-10 mx-auto">
                                                        <i class="fas fa-weight text-success"></i>
                                                    </div>
                                                    <h6 class="text-muted mb-2">Weight</h6>
                                                    <h5 class="mb-0 fw-bold text-success">${patient.weight ? patient.weight + ' kg' : 'N/A'}</h5>
                                                    <small class="text-muted">Kilograms</small>
                                                </div>
                                            </div>
                                        </div>

                                    <!-- Blood Pressure Card -->
                                        <div class="col-vitals">
                                        <div class="vital-card card border-0 shadow-sm">
                                            <div class="card-body text-center p-3">
                                                <div class="icon-circle bg-primary bg-opacity-10 mx-auto">
                                                    <i class="fas fa-heart text-primary"></i>
                                                </div>
                                                <h6 class="text-muted mb-2">Blood Pressure</h6>
                                                <h5 class="mb-0 fw-bold text-primary">${patient.blood_pressure || 'N/A'}</h5>
                                                <small class="text-muted">mmHg</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Temperature Card -->
                                        <div class="col-vitals">
                                        <div class="vital-card card border-0 shadow-sm">
                                            <div class="card-body text-center p-3">
                                                <div class="icon-circle bg-warning bg-opacity-10 mx-auto">
                                                    <i class="fas fa-thermometer-half text-warning"></i>
                                                </div>
                                                <h6 class="text-muted mb-2">Temperature</h6>
                                                <h5 class="mb-0 fw-bold text-warning">${patient.temperature ? patient.temperature + '°F' : 'N/A'}</h5>
                                                <small class="text-muted">Fahrenheit</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Pulse Rate Card -->
                                        <div class="col-vitals">
                                        <div class="vital-card card border-0 shadow-sm">
                                            <div class="card-body text-center p-3">
                                                <div class="icon-circle bg-danger bg-opacity-10 mx-auto">
                                                    <i class="fas fa-heartbeat text-danger"></i>
                                                </div>
                                                <h6 class="text-muted mb-2">Pulse Rate</h6>
                                                <h5 class="mb-0 fw-bold text-danger">${patient.pulse_rate ? patient.pulse_rate + ' bpm' : 'N/A'}</h5>
                                                <small class="text-muted">Beats/min</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Respiratory Rate Card -->
                                        <div class="col-vitals">
                                        <div class="vital-card card border-0 shadow-sm">
                                            <div class="card-body text-center p-3">
                                                <div class="icon-circle bg-info bg-opacity-10 mx-auto">
                                                    <i class="fas fa-lungs text-info"></i>
                                                </div>
                                                <h6 class="text-muted mb-2">Respiratory Rate</h6>
                                                <h5 class="mb-0 fw-bold text-info">${patient.respiratory_rate ? patient.respiratory_rate + '/min' : 'N/A'}</h5>
                                                <small class="text-muted">Breaths/min</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Diagnosis Section -->
                            <div class="diagnosis-section">
                                <div class="section-header border-0">
                                    <div class="icon-circle bg-primary bg-opacity-10">
                                        <i class="fas fa-stethoscope text-primary"></i>
                                    </div>
                                    <div class="section-title">Diagnosis</div>
                                </div>
                                <div class="diagnosis-content p-3">
                                    <p class="mb-0 diagnosis-text">${patient.diagnosis || 'No diagnosis recorded'}</p>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="mt-4 d-flex justify-content-end gap-2">
                                <button class="btn btn-primary" onclick="printVisitDetails(${JSON.stringify(patient)})">
                                    <i class="fas fa-print me-2"></i>Print Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Add these styles
            let styles = `
                <style>
                    .info-card {
                        background: #fff;
                        border-radius: 10px;
                        padding: 15px;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                        border: 1px solid #eee;
                        transition: all 0.3s ease;
                    }

                    .info-card:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                    }

                    .info-card .icon-circle {
                        width: 45px;
                        height: 45px;
                        min-width: 45px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        border-radius: 50%;
                    }

                    .info-card .text-start {
                        font-size: 1.1rem;
                        margin-left: 2cm; /* Add 2cm padding between icon and text */
                    }

                    .ms-5 {
                        margin-left: 2cm !important; /* Override Bootstrap's margin with 2cm */
                    }

                    .gender-icon-circle {
                        width: 60px;
                        height: 60px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    }

                    .card-header {
                        border-bottom: none;
                        padding: 1.5rem;
                    }

                    .section-header {
                        margin-bottom: 1.5rem;
                    }

                    .vital-card {
                        transition: transform 0.3s ease, box-shadow 0.3s ease;
                    }

                    .vital-card:hover {
                        transform: translateY(-5px);
                    }
                </style>
            `;
            
            $("#patientDetails").html(detailsHTML).fadeIn();
            
            // Make sure patient_id is available before setting it
            if (patient.patient_id) {
            $("#patient_id").val(patient.patient_id);
                console.log('Set patient_id to:', patient.patient_id);
            } else {
                console.error('Patient ID is missing from the response!', patient);
                // Set patient_id from the original function parameter
                $("#patient_id").val(id);
                console.log('Used function parameter id instead:', id);
            }
            
            $("#prescriptionSection").fadeIn();
            loadPrescription(patient.patient_id || id);
            
            // Automatically load medical history when patient is selected
            loadMedicalHistory(patient.patient_id || id);
        }
    });
}

function loadMedicalHistory(patientId) {
    $.ajax({
        url: "fetch_medical_history.php",
        type: "GET",
        data: { patient_id: patientId },
        success: function(response) {
            $("#medicalHistory").html(response);
        }
    });
}

function loadHistoryDetails(patientId, visitDate) {
    // Show loading state
    $("#patientDetails").html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="mt-2 text-muted">Loading visit details...</div>
        </div>
    `);
    
    // Hide prescription section
    $("#prescriptionSection").hide();

    // Debug information
    console.log('Requesting details for:', {
        patientId: patientId,
        visitDate: visitDate
    });

    // Use Axios for the request
    axios.get('fetch_visit_details.php', {
        params: {
            patient_id: patientId,
            visit_date: visitDate
        }
    })
    .then(function(response) {
        console.log('Raw response:', response);
        
        if (!response.data.success) {
            throw new Error(response.data.error || 'Failed to load data');
        }
        
        const data = response.data.data;
        console.log('Processed data:', data);

        // Update the main panel with the fetched data
        $("#patientDetails").html(`
            <div class="patient-history-details">
                <div class="visit-date">
                    <i class="fas fa-calendar-alt me-2"></i> Visit: ${data.visited_at}
                            </div>
                <div class="vitals-section mt-4">
                    <h4 class="vitals-title"><i class="fas fa-heartbeat me-2"></i>Vitals</h4>
                    <div class="row g-3">
                        ${renderVitalCard('Weight', data.weight + ' kg', 'scale', 'success')}
                        ${renderVitalCard('Blood Pressure', data.blood_pressure, 'heart', 'danger')}
                        ${renderVitalCard('Temperature', data.temperature + ' °C', 'thermometer-half', 'warning')}
                        ${renderVitalCard('Pulse Rate', data.pulse_rate + ' bpm', 'wave-square', 'primary')}
                        ${renderVitalCard('Respiratory Rate', data.respiratory_rate + ' bpm', 'lungs', 'info')}
                            </div>
                        </div>
                        
                <div class="diagnosis-section mt-4">
                    <h4 class="diagnosis-title"><i class="fas fa-stethoscope me-2"></i>Diagnosis</h4>
                    <div class="diagnosis-content p-3 border rounded">
                        ${data.diagnosis}
                                        </div>
                                    </div>
                                    
                <div class="prescription-section mt-4">
                    <h4 class="prescription-title">
                        <i class="fas fa-prescription me-2"></i>Prescriptions
                        <span class="badge bg-primary ms-2">${data.prescriptions.length}</span>
                    </h4>
                    <div class="prescription-list">
                        ${data.has_prescriptions ? renderPrescriptions(data.prescriptions) : '<div class="alert alert-info">No prescriptions recorded for this visit</div>'}
                                                </div>
                                            </div>
                                        </div>
        `);
        
        // Add this function to render the prescriptions
        function renderPrescriptions(prescriptions) {
            return prescriptions.map((p, index) => `
                <div class="prescription-entry mb-3 p-3 border rounded">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="prescription-number fw-bold">Prescription #${index + 1}</span>
                        <span class="prescription-time text-muted"><i class="far fa-clock me-1"></i>${p.time}</span>
                                                </div>
                    <div class="prescription-content">${p.text.replace(/\n/g, '<br>')}</div>
                                            </div>
            `).join('');
        }
    })
    .catch(function(error) {
        console.error('Error details:', error);
        
        $("#patientDetails").html(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                ${error.response?.data?.error || error.message || 'Failed to load visit details. Please try again.'}
            </div>
        `);
    });
}

// Add print function for visit details
function printVisitDetails(data) {
    const printWindow = window.open('', '', 'width=800,height=600');
    const content = `
        <html>
        <head>
            <title>Visit Details - ${data.visited_at}</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
                .section { margin: 20px 0; }
                .section-title { font-weight: bold; margin-bottom: 10px; }
                .info-item { margin: 5px 0; }
                .footer { margin-top: 50px; text-align: right; }
                @media print {
                    body { padding: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>Patient Visit Details</h2>
                <p>Visit Date: ${data.visited_at}</p>
            </div>
            
            <div class="section">
                <div class="section-title">Patient Information</div>
                <div class="info-item">Name: ${data.name}</div>
                <div class="info-item">Gender: ${data.gender}</div>
                <div class="info-item">Age: ${data.age} years</div>
                <div class="info-item">Phone: ${data.phone_number}</div>
                <div class="info-item">Weight: ${data.weight} kg</div>
            </div>
            
            <div class="section">
                <div class="section-title">Vital Signs</div>
                <div class="info-item">Blood Pressure: ${data.blood_pressure}</div>
                <div class="info-item">Temperature: ${data.temperature}°C</div>
                <div class="info-item">Pulse Rate: ${data.pulse_rate} bpm</div>
                <div class="info-item">Respiratory Rate: ${data.respiratory_rate}/min</div>
            </div>
            
            <div class="section">
                <div class="section-title">Diagnosis</div>
                <div class="info-item">${data.diagnosis}</div>
            </div>
            
            <div class="section">
                <div class="section-title">Prescription</div>
                <div class="info-item">${data.prescription_text}</div>
            </div>
            
            <div class="footer">
                <p>Doctor's Signature: _________________</p>
                <p>Date: ${new Date().toLocaleDateString()}</p>
            </div>
            
            <div class="no-print" style="text-align: center; margin-top: 20px;">
                <button onclick="window.print()">Print</button>
            </div>
        </body>
        </html>
    `;
    
    printWindow.document.write(content);
    printWindow.document.close();
}

function formatDate(dateString) {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
}

function printPrescription() {
    const patientId = $("#patient_id").val();
    const prescriptionText = $("#prescription_text").val();
    if (!patientId || !prescriptionText || prescriptionText.trim() === "") {
        alert("Please enter a prescription and select a patient before printing.");
        return;
    }
    // Save prescription first
    $.ajax({
        url: "save_prescription.php",
        type: "POST",
        data: { patient_id: patientId, prescription_text: prescriptionText },
        dataType: "json",
        success: function(saveResponse) {
            if (saveResponse && saveResponse.success) {
                // Now fetch all print data
                $.ajax({
                    url: "get_prescription_print_data.php",
                    type: "GET",
                    data: { patient_id: patientId },
                    dataType: "json",
                    success: function(printData) {
                        if (printData.success) {
                            const d = printData.doctor;
                            const p = printData.patient;
                            const pr = printData.prescription;
                            const h = printData.hospital;
                            const date = pr.prescribed_date ? new Date(pr.prescribed_date).toLocaleDateString() : new Date().toLocaleDateString();
                            // Print layout matching the reference image
                            let content = `
                                <html>
                                <head>
                                    <title>Prescription</title>
                                    <style>
                                        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');
                                        body { font-family: 'Roboto', Arial, sans-serif; background: #f4f8fb; margin: 0; padding: 0; }
                                        .sheet { background: #fff; width: 700px; margin: 30px auto; padding: 40px 50px 30px 50px; border-radius: 12px; box-shadow: 0 4px 24px rgba(44,62,80,0.08); position: relative; }
                                        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #e3eaf1; padding-bottom: 18px; }
                                        .header-left { }
                                        .header-left h2 { color: #2563eb; font-size: 2rem; margin: 0; font-weight: 700; letter-spacing: 1px; }
                                        .header-left .qual { color: #222; font-size: 1.1rem; letter-spacing: 2px; margin-top: 2px; font-weight: 400; }
                                        .header-right { text-align: right; }
                                        .header-right img { width: 60px; margin-bottom: 5px; }
                                        .cert { color: #888; font-size: 0.9rem; margin-top: 8px; }
                                        .rx { color: #2563eb; font-size: 2.5rem; font-weight: 700; margin: 30px 0 10px 0; }
                                        .info-table { width: 100%; margin: 25px 0 10px 0; font-size: 1.05rem; }
                                        .info-table td { padding: 6px 0; }
                                        .info-table .label { color: #888; width: 110px; }
                                        .info-table .value { color: #222; font-weight: 500; }
                                        .diagnosis { margin: 10px 0 20px 0; font-size: 1.05rem; }
                                        .watermark { position: absolute; left: 50%; top: 55%; transform: translate(-50%, -50%); opacity: 0.08; z-index: 0; }
                                        .watermark img { width: 320px; }
                                        .presc-section { position: relative; z-index: 1; }
                                        .presc-label { color: #2563eb; font-size: 1.2rem; font-weight: 700; margin-bottom: 8px; }
                                        .presc-content { font-size: 1.1rem; min-height: 80px; margin-bottom: 30px; white-space: pre-line; }
                                        .footer { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 40px; border-top: 1px solid #e3eaf1; padding-top: 18px; }
                                        .footer-left { font-size: 1rem; color: #888; }
                                        .footer-right { text-align: right; }
                                        .signature { margin-top: 40px; border-top: 1px solid #222; width: 220px; text-align: center; color: #888; font-size: 1rem; padding-top: 6px; }
                                        .hospital-bar { background: linear-gradient(90deg, #2563eb 80%, #fff 100%); color: #fff; padding: 18px 30px; border-radius: 0 0 12px 12px; display: flex; align-items: center; justify-content: space-between; margin: 0 -50px -30px -50px; font-size: 1rem; }
                                        .hospital-bar .left { font-weight: 700; font-size: 1.1rem; }
                                        .hospital-bar .right { display: flex; gap: 18px; align-items: center; }
                                        .hospital-bar i { margin-right: 6px; }
                                        @media print { body { background: #fff; } .sheet { box-shadow: none; margin: 0; } .hospital-bar { margin: 0 -50px 0 -50px; } .no-print { display: none; } }
                                    </style>
                                    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
                                </head>
                                <body>
                                    <div class="sheet">
                                        <div class="header">
                                            <div class="header-left">
                                                <h2>Dr. ${d.full_name || ''}</h2>
                                                <div class="qual">${d.qualifications || ''}</div>
                                                <div class="cert">Certification: 12548-20</div>
                                            </div>
                                            <div class="header-right">
                                                <img src="https://cdn-icons-png.flaticon.com/512/616/616494.png" alt="Caduceus">
                                            </div>
                                        </div>
                                        <table class="info-table">
                                            <tr><td class="label">Patient Name:</td><td class="value">${p.name || ''}</td></tr>
                                            <tr><td class="label">Address:</td><td class="value">${p.address || ''}</td></tr>
                                            <tr><td class="label">Age:</td><td class="value">${p.age || ''}</td><td class="label">Date:</td><td class="value">${date}</td></tr>
                                            <tr><td class="label">Gender:</td><td class="value">${p.gender || ''}</td></tr>
                                            <tr><td class="label">Diagnosis:</td><td class="value">${pr.diagnosis || ''}</td></tr>
                                        </table>
                                        <div class="rx">&#8478;x</div>
                                        <div class="presc-section">
                                            <div class="presc-label">Prescription</div>
                                            <div class="presc-content">${pr.prescription_text || ''}</div>
                                        </div>
                                        <div class="watermark"><img src="https://cdn-icons-png.flaticon.com/512/616/616494.png" alt="Caduceus"></div>
                                        <div class="footer">
                                            <div class="footer-left"></div>
                                            <div class="footer-right">
                                                <div class="signature">SIGNATURE</div>
                                            </div>
                                        </div>
                                        <div class="hospital-bar">
                                            <div class="left">${h.name} <span style="font-weight:400;font-size:0.95rem;">${h.slogan}</span></div>
                                            <div class="right">
                                                <span><i class="fas fa-phone"></i> ${h.phone}</span>
                                                <span><i class="fas fa-envelope"></i> ${h.email}</span>
                                                <span><i class="fas fa-map-marker-alt"></i> ${h.address}</span>
                                                <span><i class="fas fa-globe"></i> ${h.website}</span>
                                            </div>
                                        </div>
                                        <div class="no-print" style="text-align:center;margin-top:20px;">
                                            <button onclick="window.print()" style="padding:10px 20px;background-color:#2563eb;color:white;border:none;border-radius:5px;cursor:pointer;font-size:16px;">Print Prescription</button>
                                        </div>
                                    </div>
                                </body>
                                </html>
                            `;
                            let printWindow = window.open('', '', 'width=900,height=1100');
                            printWindow.document.write(content);
                            printWindow.document.close();
                        } else {
                            alert("Failed to fetch print data: " + (printData.error || "Unknown error"));
                        }
                    },
                    error: function(xhr) {
                        alert("Error fetching print data: " + xhr.responseText);
                    }
                });
            } else {
                alert(saveResponse.error || "Failed to save prescription before printing.");
            }
        },
        error: function(xhr) {
            alert("Error saving prescription: " + xhr.responseText);
        }
    });
}

$("#prescriptionForm").submit(function(event) {
    event.preventDefault();
    
    // Get form data and log it
    const formData = $(this).serialize();
    const patientId = $("#patient_id").val();
    const prescriptionText = $("#prescription_text").val();
    
    console.log("Submitting prescription with data:", {
        patientId: patientId,
        prescriptionText: prescriptionText ? prescriptionText.substring(0, 20) + "..." : "empty",
        formData: formData
    });
    
    if (!patientId) {
        alert("Error: No patient selected! Please select a patient first.");
        return;
    }
    
    if (!prescriptionText || prescriptionText.trim() === "") {
        alert("Error: Prescription text cannot be empty.");
        return;
    }
    
    $.ajax({
        url: "save_prescription.php",
        type: "POST",
        data: formData,
        dataType: "json", // Ensure JSON response is expected
        success: function(response) {
            console.log("Prescription save response:", response);
            
            if (response && response.success) {
                console.log("Prescription saved successfully");
                
                // Show success message with animation
                const successAlert = $(`
                    <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                        <i class="fas fa-check-circle me-2"></i> Prescription saved successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `).prependTo("#prescriptionSection");
                
                // Auto dismiss after 3 seconds
                setTimeout(() => {
                    successAlert.alert('close');
                }, 3000);
                
                // Load the updated prescriptions
                loadPrescription(patientId);
                
                // Clear the textarea for a new prescription
            $("#prescription_text").val("");
            } else {
                console.error("Failed to save prescription:", response);
                alert(response && response.error ? response.error : "Failed to save prescription");
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX error when saving prescription:", {
                status: status,
                error: error,
                response: xhr.responseText
            });
            
            try {
                const errorData = JSON.parse(xhr.responseText);
                alert("Error: " + (errorData.error || "Unknown error occurred"));
            } catch (e) {
                alert("An error occurred while saving the prescription. Please try again.");
            }
        }
    });
});

function loadPrescription(patientId, visitDate) {
    // If no specific date is provided, use today's date
    const date = visitDate || new Date().toISOString().split('T')[0];
    
    $.ajax({
        url: "fetch_prescription.php",
        type: "GET",
        data: { 
            patient_id: patientId,
            visit_date: date
        },
        success: function(response) {
            $("#previousPrescription").html(response);
        },
        error: function() {
            $("#previousPrescription").html('<div class="alert alert-danger">Failed to load prescriptions</div>');
        }
    });
}

// Add this function to handle time range changes
function changeTimeRange(range) {
    // Update active state of buttons
    $('.time-range-toggle .btn').removeClass('active');
    $(`.time-range-toggle .btn[data-range="${range}"]`).addClass('active');
    
    // Show loading state
    $('.patient-list').addClass('loading').css('opacity', '0.5');
    
    // Update URL without page refresh
    const url = new URL(window.location.href);
    url.searchParams.set('range', range);
    window.history.pushState({}, '', url);
    
    // Fetch patients for selected time range
    $.ajax({
        url: window.location.href,
        type: 'GET',
        data: { 
            refresh_list: true,
            time_range: range
        },
        success: function(response) {
            // Update the patient list with fade effect
            $('.patient-list').fadeOut(200, function() {
                $(this).html(response).fadeIn(200);
                // Remove loading state
                $(this).removeClass('loading').css('opacity', '1');
                // Reattach click events
                attachPatientCardEvents();
                // Update the count
                updatePatientCount();
            });
        },
        error: function() {
            // Remove loading state on error
            $('.patient-list').removeClass('loading').css('opacity', '1');
        }
    });
}

// Function to attach events to patient cards
function attachPatientCardEvents() {
    $('.patient-card').off('click').on('click', function() {
        const patientId = $(this).data('patient-id');
        viewPatient(patientId);
    });
}

// Initialize when document is ready
$(document).ready(function() {
    // Get initial range from URL or default to 'all'
    const urlParams = new URLSearchParams(window.location.search);
    const initialRange = urlParams.get('range') || 'all';
    
    // Set initial active state
    $(`.time-range-toggle .btn[data-range="${initialRange}"]`).addClass('active');
    
    // Initial load of patients
    refreshPatientList(initialRange);
    
    // Set up auto-refresh
    setInterval(function() {
        const currentRange = $('.time-range-toggle .btn.active').data('range');
        refreshPatientList(currentRange);
    }, 2000);
    
    // Handle time range button clicks
    $('.time-range-toggle .btn').on('click', function(e) {
        e.preventDefault();
        const range = $(this).data('range');
        changeTimeRange(range);
    });
});

// Update patient count function
function updatePatientCount() {
    const totalPatients = $('.patient-card').length;
    const completedPatients = $('.patient-card.completed').length;
    $('#patientCount').text(`${completedPatients}/${totalPatients}`);
}

// Add loading animation styles
const style = document.createElement('style');
style.textContent = `
    .patient-list.loading {
        position: relative;
        min-height: 100px;
    }
    .patient-list.loading::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 30px;
        height: 30px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        0% { transform: translate(-50%, -50%) rotate(0deg); }
        100% { transform: translate(-50%, -50%) rotate(360deg); }
    }
`;
document.head.appendChild(style);

$(document).ready(function() {
    // Only keep the date change check
            checkDateChange();
});

function checkDateChange() {
    const currentDate = new Date().toLocaleDateString();
    const lastVisitDate = localStorage.getItem('lastVisitDate');
    if (lastVisitDate && lastVisitDate !== currentDate) {
        const currentRange = new URLSearchParams(window.location.search).get('range') || 'today';
        if (currentRange === 'today') {
            changeTimeRange('week');
        }
    }
    localStorage.setItem('lastVisitDate', currentDate);
}

// Add this to your JavaScript section
function markAsComplete(patientId) {
    const patientCard = $(`.patient-card[data-patient-id="${patientId}"]`);
    const completeBtn = $('.mark-complete-btn');
    
    // Add completed class
    patientCard.addClass('completed');
    
    // Add check mark to the visit time
    patientCard.find('.visit-time').append(' <i class="fas fa-check-circle text-success ms-2"></i>');
    
    // Disable the button for this patient only
    completeBtn.prop('disabled', true);
    
    // Move to bottom of list with animation
    const patientList = $('.patient-list');
    patientCard.fadeOut(300, function() {
        patientCard.detach();
        patientList.append(patientCard);
        patientCard.fadeIn(300);
        // Update count after animation
        updatePatientCount();
    });
    
    // Update the completion status in the database
    $.ajax({
        url: window.location.href,
        type: 'POST',
        data: {
            mark_complete: true,
            patient_id: patientId
        },
        success: function(response) {
            if (response.success) {
                // Show success notification
    const notification = $(`
        <div class="notification animate__animated animate__fadeInDown">
                        <i class="fas fa-check-circle me-2"></i>
                        Patient marked as completed
        </div>
    `).appendTo('body');
    
    setTimeout(() => {
        notification.removeClass('animate__fadeInDown').addClass('animate__fadeOutUp');
        setTimeout(() => notification.remove(), 1000);
    }, 3000);
            }
        }
    });
}

// Add this function to update the patient count and button state
function updatePatientCount() {
    const patientCards = document.querySelectorAll('.patient-card');
    const completedPatients = document.querySelectorAll('.patient-card.completed');
    const totalPatients = patientCards.length;
    const completedCount = completedPatients.length;
    
    document.getElementById('patientCount').textContent = `${completedCount}/${totalPatients}`;
    
    // Update button state based on selected patient
    const selectedPatient = $('.patient-card.selected');
    const completeBtn = $('.mark-complete-btn');
    
    if (selectedPatient.length > 0) {
        const isCompleted = selectedPatient.hasClass('completed');
        completeBtn.prop('disabled', isCompleted);
    }
}

// Add this to document ready to initialize count and button state
$(document).ready(function() {
    updatePatientCount();
    
    // Update count and button state when switching time ranges
    $('.time-range-toggle .btn').on('click', function() {
        // Wait for the new data to load
        setTimeout(updatePatientCount, 500);
    });
    
    // Update button state when selecting a patient
    $('.patient-card').on('click', function() {
        setTimeout(updatePatientCount, 100);
    });
});

// Add helper function to render vital cards
function renderVitalCard(title, value, icon, color) {
    return `
        <div class="col-md-4 col-sm-6 mb-3">
            <div class="vital-card bg-white p-3 rounded shadow-sm border-start border-${color} border-3">
                <div class="d-flex align-items-center">
                    <div class="icon-container me-3 bg-${color} bg-opacity-10 rounded-circle p-2">
                        <i class="fas fa-${icon} text-${color}"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted">${title}</h6>
                        <span class="h5 mb-0 fw-bold">${value}</span>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Add this function to fetch and update patient list
function refreshPatientList(range = 'all') {
    $.ajax({
        url: window.location.href,
        type: 'GET',
        data: { 
            refresh_list: true,
            time_range: range
        },
        success: function(response) {
            // Extract the patient list HTML from the response
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = response;
            const newPatientList = $(tempDiv).find('.patient-list').html();
            
            // Update the patient list if there are changes
            if (newPatientList !== $('.patient-list').html()) {
                $('.patient-list').html(newPatientList);
                // Reattach click events to new patient cards
                attachPatientCardEvents();
                // Update the count
                updatePatientCount();
            }
        }
    });
}

// Function to attach events to patient cards
function attachPatientCardEvents() {
    $('.patient-card').off('click').on('click', function() {
        const patientId = $(this).data('patient-id');
        viewPatient(patientId);
    });
}

// Add this to document ready to start auto-refresh
$(document).ready(function() {
    updatePatientCount();
    
    // Start auto-refresh interval
    setInterval(refreshPatientList, 2000);
    
    // Update count and button state when switching time ranges
    $('.time-range-toggle .btn').on('click', function() {
        // Wait for the new data to load
        setTimeout(updatePatientCount, 500);
    });
    
    // Initial attachment of events
    attachPatientCardEvents();
});

// Add this to document ready to initialize with 'all' range
$(document).ready(function() {
    updatePatientCount();
    
    // Start auto-refresh interval with 'all' range
    refreshPatientList('all');
    setInterval(function() {
        refreshPatientList('all');
    }, 2000);
    
    // Handle time range button clicks
    $('.time-range-toggle .btn').on('click', function(e) {
        e.preventDefault();
        $('.time-range-toggle .btn').removeClass('active');
        $(this).addClass('active');
        const range = $(this).data('range');
        changeTimeRange(range);
    });
    
    // Initial attachment of events
    attachPatientCardEvents();
});

// Update the refreshPatientList function to accept a range parameter
function refreshPatientList(range = 'all') {
    $.ajax({
        url: window.location.href,
        type: 'GET',
        data: { 
            refresh_list: true,
            time_range: range
        },
        success: function(response) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = response;
            const newPatientList = $(tempDiv).find('.patient-list').html();
            
            if (newPatientList !== $('.patient-list').html()) {
                $('.patient-list').html(newPatientList);
                attachPatientCardEvents();
                updatePatientCount();
            }
        }
    });
}

// Update the changeTimeRange function
function changeTimeRange(range) {
    refreshPatientList(range);
}

// Add notification for uncompleted patient
$(document).ready(function() {
    // Add warning notification style
    $('head').append(`
        <style id="warning-notification-style">
            .notification.warning {
                background: #fd7e14;
                color: white;
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 25px;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                z-index: 1000;
            }
        </style>
    `);

    // Check for clicks outside patient details
    $('body').on('click', function(e) {
        // Skip if clicking on patient card, details, or prescription section
        if (!$(e.target).closest('#patientDetails, #prescriptionSection, .patient-card, .mark-complete-btn').length) {
            const selectedPatient = $('.patient-card.selected');
            
            if (selectedPatient.length > 0 && !selectedPatient.hasClass('completed')) {
                // Show notification only if a patient is selected and not marked as completed
                const notification = $(`
                    <div class="notification warning animate__animated animate__fadeInDown">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Don't forget to mark the patient as complete!
                    </div>
                `).appendTo('body');
                
                setTimeout(() => {
                    notification.removeClass('animate__fadeInDown').addClass('animate__fadeOutUp');
                    setTimeout(() => notification.remove(), 1000);
                }, 3000);
            }
        }
    });
});

// Add this function in your JavaScript section
function logout() {
    // Clear all session data
    fetch('logout.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '../mainpage.php';
        } else {
            window.location.href = '../mainpage.php';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        window.location.href = '../mainpage.php';
    });
}

function handleLogout() {
    fetch('logout.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '../mainpage.php';
            }
        })
        .catch(error => {
            console.error('Logout error:', error);
            window.location.href = '../mainpage.php';
        });
}
</script>

</body>
</html>
