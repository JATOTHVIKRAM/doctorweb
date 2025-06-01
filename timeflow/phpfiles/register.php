<?php
session_start();
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Queue-less Appointment Booking System</title>image.png
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ... existing styles ... */
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">QueueLess</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="services.php">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary text-white ms-2 px-3" href="login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Register Section -->
    <section class="register-container">
        <div class="container">
            <div class="register-card">
                <div class="register-header">
                    <h1>Create an Account</h1>
                    <p>Join QueueLess to book appointments without waiting in line</p>
                </div>
                <div class="register-body">
                    <div class="user-type-selector">
                        <button class="user-type-btn active" id="customerBtn">Customer</button>
                        <button class="user-type-btn" id="providerBtn">Service Provider</button>
                    </div>
                    
                    <!-- Customer Registration Form -->
                    <form id="registrationForm" class="mt-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="first_name" placeholder="First Name" required>
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="last_name" placeholder="Last Name" required>
                            </div>
                            <div class="col-12">
                                <input type="email" class="form-control" name="email" placeholder="Email address" required>
                            </div>
                            <div class="col-12">
                                <input type="tel" class="form-control" name="phone" placeholder="Phone Number" required>
                            </div>
                            <div class="col-12">
                                <input type="password" class="form-control" name="password" placeholder="Password" required>
                            </div>
                            <div class="col-12">
                                <input type="password" class="form-control" name="confirm_password" placeholder="Confirm Password" required>
                            </div>
                        </div>

                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the Terms of Service and Privacy Policy
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mt-3">Register</button>
                    </form>
                    
                    <!-- Provider Registration Form -->
                    <form id="providerRegisterForm" action="provider-dashboard.php" style="display: none;">
                        <!-- ... provider form fields ... -->
                    </form>
                    
                    <div class="register-footer">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
                        
                        <div class="social-register">
                            <a href="#" class="social-btn facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="social-btn google"><i class="fab fa-google"></i></a>
                            <a href="#" class="social-btn twitter"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
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
    
    <!-- Register Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const customerBtn = document.getElementById('customerBtn');
            const providerBtn = document.getElementById('providerBtn');
            const customerForm = document.getElementById('registrationForm');
            const providerForm = document.getElementById('providerRegisterForm');
            
            customerBtn.addEventListener('click', function() {
                customerBtn.classList.add('active');
                providerBtn.classList.remove('active');
                customerForm.style.display = 'block';
                providerForm.style.display = 'none';
            });
            
            providerBtn.addEventListener('click', function() {
                providerBtn.classList.add('active');
                customerBtn.classList.remove('active');
                providerForm.style.display = 'block';
                customerForm.style.display = 'none';
            });
        });

        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('customer-register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Registration successful! Please login.');
                    window.location.href = 'login.php';
                } else {
                    alert('Registration failed: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred during registration. Please try again.');
            });
        });
    </script>
</body>
</html> 