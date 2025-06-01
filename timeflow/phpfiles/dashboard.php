<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user information based on user type
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$table = ($user_type === 'provider') ? 'providers' : 'customers';

try {
    $stmt = $conn->prepare("SELECT * FROM $table WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TimeFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #1cc88a;
            --dark-color: #5a5c69;
            --light-color: #f8f9fc;
        }

        body {
            background-color: var(--light-color);
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .sidebar {
            min-height: 100vh;
            background-color: var(--primary-color);
            color: white;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 1rem;
        }

        .sidebar .nav-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.2);
        }

        .main-content {
            padding: 2rem;
        }

        .card {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3">
                    <h4 class="mb-4">TimeFlow</h4>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-home me-2"></i> Dashboard
                            </a>
                        </li>
                        <?php if($user_type === 'customer'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="book-appointment.php">
                                    <i class="fas fa-calendar-plus me-2"></i> Book Appointment
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="my-appointments.php">
                                    <i class="fas fa-calendar-check me-2"></i> My Appointments
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="add-service.php">
                                    <i class="fas fa-plus-circle me-2"></i> Add Service
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="manage-appointments.php">
                                    <i class="fas fa-calendar-alt me-2"></i> Manage Appointments
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user me-2"></i> Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-2"></i>Account
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>

                <?php if($user_type === 'customer'): ?>
                    <!-- Customer Dashboard Content -->
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Upcoming Appointments</h5>
                                    <?php
                                    try {
                                        $stmt = $conn->prepare("SELECT a.*, s.service_name, p.business_name 
                                                             FROM appointments a 
                                                             JOIN services s ON a.service_id = s.id 
                                                             JOIN providers p ON s.provider_id = p.id 
                                                             WHERE a.customer_id = ? AND a.status = 'scheduled' 
                                                             ORDER BY a.appointment_date ASC LIMIT 3");
                                        $stmt->execute([$user_id]);
                                        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        if (count($appointments) > 0) {
                                            foreach ($appointments as $appointment) {
                                                echo "<div class='mb-3'>";
                                                echo "<h6>{$appointment['service_name']}</h6>";
                                                echo "<p class='mb-1'>Provider: {$appointment['business_name']}</p>";
                                                echo "<p class='mb-1'>Date: " . date('M d, Y', strtotime($appointment['appointment_date'])) . "</p>";
                                                echo "<p>Time: " . date('h:i A', strtotime($appointment['appointment_time'])) . "</p>";
                                                echo "</div>";
                                            }
                                        } else {
                                            echo "<p>No upcoming appointments</p>";
                                        }
                                    } catch(PDOException $e) {
                                        echo "<p>Error loading appointments</p>";
                                    }
                                    ?>
                                    <a href="my-appointments.php" class="btn btn-primary">View All Appointments</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Quick Actions</h5>
                                    <a href="book-appointment.php" class="btn btn-primary mb-2">Book New Appointment</a>
                                    <a href="services.php" class="btn btn-secondary mb-2">Browse Services</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Provider Dashboard Content -->
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Today's Appointments</h5>
                                    <?php
                                    try {
                                        $stmt = $conn->prepare("SELECT a.*, s.service_name, c.name as customer_name 
                                                             FROM appointments a 
                                                             JOIN services s ON a.service_id = s.id 
                                                             JOIN customers c ON a.customer_id = c.id 
                                                             WHERE s.provider_id = ? AND DATE(a.appointment_date) = CURDATE() 
                                                             ORDER BY a.appointment_time ASC");
                                        $stmt->execute([$user_id]);
                                        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        if (count($appointments) > 0) {
                                            foreach ($appointments as $appointment) {
                                                echo "<div class='mb-3'>";
                                                echo "<h6>{$appointment['service_name']}</h6>";
                                                echo "<p class='mb-1'>Customer: {$appointment['customer_name']}</p>";
                                                echo "<p class='mb-1'>Time: " . date('h:i A', strtotime($appointment['appointment_time'])) . "</p>";
                                                echo "<p>Status: " . ucfirst($appointment['status']) . "</p>";
                                                echo "</div>";
                                            }
                                        } else {
                                            echo "<p>No appointments scheduled for today</p>";
                                        }
                                    } catch(PDOException $e) {
                                        echo "<p>Error loading appointments</p>";
                                    }
                                    ?>
                                    <a href="manage-appointments.php" class="btn btn-primary">Manage Appointments</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Quick Actions</h5>
                                    <a href="add-service.php" class="btn btn-primary mb-2">Add New Service</a>
                                    <a href="manage-services.php" class="btn btn-secondary mb-2">Manage Services</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 