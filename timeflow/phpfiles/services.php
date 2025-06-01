<?php
session_start();
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services - Queue-less Appointment Booking System</title>
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
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--light-color);
        }

        .navbar {
            background-color: white;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color);
        }

        .services-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #224abe 100%);
            color: white;
            padding: 4rem 0;
            text-align: center;
        }

        .service-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 30px;
            transition: transform 0.3s ease;
        }

        .service-card:hover {
            transform: translateY(-5px);
        }

        .service-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .service-title {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .service-description {
            color: #858796;
            margin-bottom: 1rem;
        }

        .service-price {
            font-size: 1.25rem;
            color: var(--primary-color);
            font-weight: 600;
        }

        .book-btn {
            background-color: var(--secondary-color);
            border: none;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s;
        }

        .book-btn:hover {
            background-color: #1ab77a;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">TimeFlow</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="services.php">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="registerDropdown" role="button" data-bs-toggle="dropdown">
                            Register
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="customer-register.php">As Customer</a></li>
                            <li><a class="dropdown-item" href="provider-register.php">As Provider</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Services Header -->
    <header class="services-header">
        <div class="container">
            <h1>Our Services</h1>
            <p class="lead">Browse through our wide range of professional services</p>
        </div>
    </header>

    <!-- Services Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <?php
                try {
                    $stmt = $conn->query("SELECT s.*, p.business_name FROM services s 
                                        JOIN providers p ON s.provider_id = p.id 
                                        ORDER BY s.provider_id");
                    while($service = $stmt->fetch(PDO::FETCH_ASSOC)) {
                ?>
                    <div class="col-md-4 mb-4">
                        <div class="service-card p-4">
                            <div class="service-icon">
                                <i class="fas fa-concierge-bell"></i>
                            </div>
                            <h3 class="service-title"><?php echo htmlspecialchars($service['service_name']); ?></h3>
                            <p class="service-description">Provided by: <?php echo htmlspecialchars($service['business_name']); ?></p>
                            <p class="service-description">Duration: <?php echo htmlspecialchars($service['duration']); ?> minutes</p>
                            <p class="service-price mb-3">$<?php echo number_format($service['price'], 2); ?></p>
                            <a href="book-appointment.php?service_id=<?php echo $service['id']; ?>" 
                               class="btn book-btn text-white">Book Now</a>
                        </div>
                    </div>
                <?php
                    }
                } catch(PDOException $e) {
                    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2023 QueueLess. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-decoration-none text-dark me-3">Privacy Policy</a>
                    <a href="#" class="text-decoration-none text-dark">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 