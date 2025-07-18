<?php
session_start();
require_once 'config.php';

// Check if already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provider Registration - QueueLess</title>
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
            color: var(--dark-color);
        }
        
        .registration-form {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            padding: 2rem;
            margin: 2rem auto;
            max-width: 800px;
        }
        
        .form-title {
            font-weight: 700;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }
        
        .service-category {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 1px solid #e3e6f0;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .service-category:hover {
            border-color: var(--primary-color);
            background-color: rgba(78, 115, 223, 0.05);
        }
        
        .service-category.selected {
            border-color: var(--primary-color);
            background-color: rgba(78, 115, 223, 0.1);
        }
        
        .service-icon {
            width: 40px;
            height: 40px;
            background-color: rgba(78, 115, 223, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }
        
        .service-icon i {
            color: var(--primary-color);
            font-size: 1.25rem;
        }
        
        .service-details {
            flex: 1;
        }
        
        .service-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .service-description {
            color: #858796;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="registration-form">
            <h2 class="form-title">Provider Registration</h2>
            <?php if(isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form id="providerRegistrationForm" method="POST" action="process-registration.php">
                <!-- Business Information -->
                <div class="mb-4">
                    <h4 class="mb-3">Business Information</h4>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Business Name</label>
                            <input type="text" class="form-control" name="business_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Business Type</label>
                            <select class="form-select" name="business_type" required>
                                <option value="">Select Business Type</option>
                                <option value="salon">Salon</option>
                                <option value="medical">Medical Clinic</option>
                                <option value="dental">Dental Clinic</option>
                                <option value="spa">Spa</option>
                                <option value="fitness">Fitness Center</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Business Address</label>
                            <input type="text" class="form-control" name="address" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" name="city" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">State</label>
                            <input type="text" class="form-control" name="state" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ZIP Code</label>
                            <input type="text" class="form-control" name="zip_code" required>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Information -->
                <div class="mb-4">
                    <h4 class="mb-3">Contact Information</h4>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Contact Person</label>
                            <input type="text" class="form-control" name="contact_person" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="phone" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                </div>
                
                <!-- Service Categories -->
                <div class="mb-4">
                    <h4 class="mb-3">Service Categories</h4>
                    <div id="serviceCategories">
                        <div class="service-category" data-category="hair">
                            <div class="service-icon">
                                <i class="fas fa-cut"></i>
                            </div>
                            <div class="service-details">
                                <div class="service-name">Hair Services</div>
                                <div class="service-description">Haircuts, styling, coloring, and treatments</div>
                            </div>
                        </div>
                        
                        <div class="service-category" data-category="nails">
                            <div class="service-icon">
                                <i class="fas fa-hand-sparkles"></i>
                            </div>
                            <div class="service-details">
                                <div class="service-name">Nail Services</div>
                                <div class="service-description">Manicures, pedicures, and nail treatments</div>
                            </div>
                        </div>
                        
                        <div class="service-category" data-category="skin">
                            <div class="service-icon">
                                <i class="fas fa-spa"></i>
                            </div>
                            <div class="service-details">
                                <div class="service-name">Skin Care</div>
                                <div class="service-description">Facials, treatments, and skincare services</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Business Hours -->
                <div class="mb-4">
                    <h4 class="mb-3">Business Hours</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Opening Time</label>
                            <input type="time" class="form-control" name="opening_time" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Closing Time</label>
                            <input type="time" class="form-control" name="closing_time" required>
                        </div>
                    </div>
                </div>
                
                <!-- Terms and Conditions -->
                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the terms and conditions and privacy policy
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Register Business</button>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Registration Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle service category selection
            const serviceCategories = document.querySelectorAll('.service-category');
            
            serviceCategories.forEach(category => {
                category.addEventListener('click', function() {
                    this.classList.toggle('selected');
                });
            });
            
            // Handle form submission
            const form = document.getElementById('providerRegistrationForm');
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Get selected categories
                const selectedCategories = Array.from(document.querySelectorAll('.service-category.selected'))
                    .map(category => category.getAttribute('data-category'));
                
                if (selectedCategories.length === 0) {
                    alert('Please select at least one service category');
                    return;
                }
                
                // Get form data
                const formData = new FormData(form);
                
                // Add selected categories to form data
                formData.append('categories', JSON.stringify(selectedCategories));
                
                // Submit form data
                fetch('process-registration.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Registration successful! You will receive a confirmation email shortly.');
                        window.location.href = '../provider-dashboard.php';
                    } else {
                        alert('Registration failed: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred during registration. Please try again.');
                });
            });
        });
    </script>
</body>
</html> 