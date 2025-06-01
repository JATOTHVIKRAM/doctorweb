<?php
session_start();

// Remove authentication check
if (!isset($_SESSION['admin_logged_in'])) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['user_type'] = 'admin';
    $_SESSION['admin_username'] = 'admin';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - V-Care</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background-image: url('doctor_bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(248, 249, 250, 0.4);
            z-index: 0;
        }
        
        .sidebar {
            width: 280px;
            background-color: rgba(25, 118, 210, 0.9);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding-top: 20px;
            color: white;
            z-index: 1;
            backdrop-filter: blur(5px);
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.9);
            padding: 12px 20px;
            margin: 4px 16px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .sidebar .nav-link.active {
            background-color: white;
            color: #1976D2;
        }
        
        .sidebar .nav-link i {
            width: 24px;
            text-align: center;
            margin-right: 8px;
        }
        
        .main-content {
            margin-left: 280px;
            padding: 20px;
            position: relative;
            z-index: 1;
            margin-bottom: 60px; /* Add space for notification bar */
        }
        
        .content-section {
            display: none;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(5px);
        }
        
        .content-section.active {
            display: block;
        }
        
        .form-section {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .section-title {
            color: #1976D2;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 2px solid #1976D2;
        }
        
        .btn-primary {
            background-color: #1976D2;
            border-color: #1976D2;
        }
        
        .btn-primary:hover {
            background-color: #1565C0;
            border-color: #1565C0;
        }
        
        .form-control {
            background-color: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .form-control:focus {
            background-color: rgba(255, 255, 255, 0.95);
            border-color: #1976D2;
            box-shadow: 0 0 0 0.25rem rgba(25, 118, 210, 0.25);
        }
        
        .table {
            background-color: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
        }
        
        .table th {
            background-color: rgba(25, 118, 210, 0.1);
        }
        
        .table td {
            background-color: rgba(255, 255, 255, 0.6);
        }

        .form-section-title {
            color: #1976D2;
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #1976D2;
        }

        .input-group {
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .input-group-text {
            background-color: #1976D2;
            color: white;
            border: none;
            min-width: 45px;
            justify-content: center;
        }

        .form-control, .form-select {
            border-left: none;
            padding-left: 0;
        }

        .form-control:focus, .form-select:focus {
            border-color: #1976D2;
            box-shadow: 0 0 0 0.25rem rgba(25, 118, 210, 0.25);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .btn-primary {
            padding: 0.8rem;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Add smooth transitions */
        .form-control, .form-select, .input-group-text {
            transition: all 0.3s ease;
        }

        /* Add hover effects */
        .input-group:hover .input-group-text {
            background-color: #1565C0;
        }

        /* Add focus effects */
        .form-control:focus + .input-group-text {
            background-color: #1565C0;
        }

        /* Add placeholder styling */
        ::placeholder {
            color: #6c757d;
            opacity: 0.7;
        }

        /* Add validation styling */
        .form-control:invalid {
            border-color: #dc3545;
        }

        .form-control:valid {
            border-color: #198754;
        }

        /* Updated notification bar styles */
        .notification-bar {
            position: fixed;
            bottom: 0;
            left: 280px; /* Same as sidebar width */
            right: 0;
            padding: 15px;
            color: white;
            text-align: center;
            transform: translateY(100%);
            transition: transform 0.3s ease-in-out;
            z-index: 1000;
            font-size: 16px;
            font-weight: 500;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }

        .notification-bar.success {
            background-color: #28a745;
            transform: translateY(0);
        }

        .notification-bar.error {
            background-color: #dc3545;
            transform: translateY(0);
        }

        .table {
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        .table thead th {
            background-color: #1976D2 !important;
            color: white;
            font-weight: 500;
            border: none;
            padding: 15px;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: rgba(25, 118, 210, 0.05);
            transform: scale(1.001);
        }

        .table td {
            padding: 12px 15px;
            vertical-align: middle;
            border-color: #e9ecef;
        }

        .btn-group-action {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        .btn-group-action .btn {
            padding: 6px 12px;
            transition: all 0.3s ease;
        }

        .btn-group-action .btn:hover {
            transform: translateY(-2px);
        }

        .badge {
            padding: 8px 16px;
            font-size: 0.9rem;
        }

        .input-group {
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            border-radius: 8px;
            overflow: hidden;
        }

        .input-group-text {
            background-color: #1976D2;
            color: white;
            border: none;
            padding: 0 15px;
        }

        .input-group .form-control {
            border: none;
            padding: 12px 15px;
        }

        .input-group .form-control:focus {
            box-shadow: none;
            border-color: transparent;
        }

        #noDoctor, #noNurse {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .table-container {
            background: transparent;
            border: none;
        }
        
        .table-header {
            background: #1976D2;
            color: white;
            font-weight: 500;
            border-radius: 8px;
            margin-bottom: 1px;
        }

        .table-header i {
            margin-right: 8px;
        }
        
        .table-body-container .table {
            margin: 0;
            border: none;
            background: transparent;
        }
        
        .table-body-container .table td {
            padding: 12px 20px;
            vertical-align: middle;
            border: none;
            background: transparent;
        }
        
        .table-body-container::-webkit-scrollbar {
            width: 6px;
        }
        
        .table-body-container::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .table-body-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }
        
        .table-body-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        #patientsTableBody tr {
            border-bottom: 1px solid rgba(222, 226, 230, 0.3);
        }

        #patientsTableBody tr:hover {
            background: rgba(233, 236, 239, 0.1);
        }

        .gender-text {
            font-weight: 500;
            font-size: 14px;
        }

        .gender-male {
            color: #00BCD4;
        }

        .gender-female {
            color: #E91E63;
        }

        .gender-other {
            color: #FFC107;
        }

        .visits-badge {
            background: #2E7D32;
            color: white;
            padding: 6px 16px;
            border-radius: 4px;
            font-weight: 500;
        }
        
        .patient-number {
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Move notification bar to bottom -->
    <div id="notificationBar" class="notification-bar">
        <span id="notificationMessage"></span>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="text-center mb-4">
            <img src="v-care logo.png" alt="V-CARE" height="60">
        </div>
        <nav class="nav flex-column">
            <a class="nav-link active" href="#" onclick="showSection('registerDoctor')">
                <i class="fas fa-user-md"></i> Register Doctor
            </a>
            <a class="nav-link" href="#" onclick="showSection('registerNurse')">
                <i class="fas fa-user-nurse"></i> Register Nurse
            </a>
            <a class="nav-link" href="#" onclick="showSection('viewDoctors')">
                <i class="fas fa-list"></i> View Doctors
            </a>
            <a class="nav-link" href="#" onclick="showSection('viewNurses')">
                <i class="fas fa-list"></i> View Nurses
            </a>
            <a class="nav-link" href="#" onclick="showSection('departments')">
                <i class="fas fa-hospital"></i> Departments
            </a>
            <a class="nav-link" href="#" onclick="showSection('appointments')">
                <i class="fas fa-calendar-check"></i> Appointments
            </a>
            <a class="nav-link" href="#" onclick="showSection('patients')">
                <i class="fas fa-users"></i> Patients
            </a>
            <a class="nav-link" href="mainpage.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Register Doctor Section -->
        <div id="registerDoctor" class="content-section active">
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-user-md"></i> Register New Doctor</h2>
                <form id="doctorRegistrationForm" onsubmit="registerDoctor(event)">
                    <div class="row g-3">
                        <!-- Personal Information -->
                        <div class="col-12">
                            <h4 class="form-section-title"><i class="fas fa-user-circle"></i> Personal Information</h4>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" name="full_name" placeholder="Full Name" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" name="email" placeholder="Email Address" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="tel" class="form-control" name="phone" placeholder="Phone Number" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                                <select class="form-select" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <!-- Professional Information -->
                        <div class="col-12 mt-4">
                            <h4 class="form-section-title"><i class="fas fa-briefcase-medical"></i> Professional Information</h4>
                        </div>

                        <div class="col-12">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-graduation-cap"></i></span>
                                <textarea class="form-control" name="qualifications" rows="3" placeholder="Medical Qualifications" required></textarea>
                            </div>
                        </div>

                        <!-- Account Information -->
                        <div class="col-12 mt-4">
                            <h4 class="form-section-title"><i class="fas fa-user-shield"></i> Account Information</h4>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                <input type="text" class="form-control" name="username" placeholder="Username" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" name="password" placeholder="Password" required>
                            </div>
                        </div>
                        
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-user-plus me-2"></i> Register Doctor
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Register Nurse Section -->
        <div id="registerNurse" class="content-section">
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-user-nurse"></i> Register New Nurse</h2>
                <form id="nurseRegistrationForm" onsubmit="registerNurse(event)">
                    <div class="row g-3">
                        <!-- Personal Information -->
                        <div class="col-12">
                            <h4 class="form-section-title"><i class="fas fa-user-circle"></i> Personal Information</h4>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" name="full_name" placeholder="Full Name" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" name="email" placeholder="Email Address" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="tel" class="form-control" name="phone" placeholder="Phone Number" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                                <select class="form-select" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-birthday-cake"></i></span>
                                <input type="date" class="form-control" name="date_of_birth" required>
                            </div>
                        </div>

                        <!-- Professional Information -->
                        <div class="col-12 mt-4">
                            <h4 class="form-section-title"><i class="fas fa-briefcase-medical"></i> Professional Information</h4>
                        </div>
                        
                        <div class="col-12">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-graduation-cap"></i></span>
                                <textarea class="form-control" name="nursing_qualifications" rows="3" placeholder="Nursing Qualifications" required></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                <input type="number" class="form-control" name="years_of_experience" placeholder="Years of Experience" required min="0">
                            </div>
                        </div>

                        <!-- Account Information -->
                        <div class="col-12 mt-4">
                            <h4 class="form-section-title"><i class="fas fa-user-shield"></i> Account Information</h4>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                <input type="text" class="form-control" name="username" placeholder="Username" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" name="password" placeholder="Password" required>
                            </div>
                        </div>
                        
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-user-plus me-2"></i> Register Nurse
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- View Doctors Section -->
        <div id="viewDoctors" class="content-section">
            <h2 class="section-title"><i class="fas fa-user-md"></i> Registered Doctors</h2>
            <div class="mb-4">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="doctorSearch" placeholder="Search doctors..." onkeyup="filterDoctors()">
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                        <span class="badge bg-primary" id="doctorCount">0 Doctors</span>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="table-primary">
                        <tr>
                            <th class="text-center" style="width: 60px;">ID</th>
                            <th>
                                <i class="fas fa-user me-2"></i>Full Name
                            </th>
                            <th>
                                <i class="fas fa-envelope me-2"></i>Email
                            </th>
                            <th>
                                <i class="fas fa-phone me-2"></i>Phone
                            </th>
                            <th>
                                <i class="fas fa-venus-mars me-2"></i>Gender
                            </th>
                            <th>
                                <i class="fas fa-graduation-cap me-2"></i>Qualifications
                            </th>
                            <th>
                                <i class="fas fa-user-tag me-2"></i>Username
                            </th>
                            <th class="text-center" style="width: 120px;">
                                <i class="fas fa-cogs me-2"></i>Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody id="doctorsTableBody">
                        <!-- Will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
            <div id="noDoctor" class="text-center p-4 d-none">
                <i class="fas fa-user-md fa-3x text-muted mb-3"></i>
                <h5>No doctors found</h5>
                <p class="text-muted">Try adjusting your search criteria</p>
            </div>
        </div>

        <!-- View Nurses Section -->
        <div id="viewNurses" class="content-section">
            <h2 class="section-title"><i class="fas fa-user-nurse"></i> Registered Nurses</h2>
            <div class="mb-4">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="nurseSearch" placeholder="Search nurses..." onkeyup="filterNurses()">
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                        <span class="badge bg-primary" id="nurseCount">0 Nurses</span>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="table-primary">
                        <tr>
                            <th>
                                <i class="fas fa-user me-2"></i>Full Name
                            </th>
                            <th>
                                <i class="fas fa-envelope me-2"></i>Email
                            </th>
                            <th>
                                <i class="fas fa-phone me-2"></i>Phone
                            </th>
                            <th>
                                <i class="fas fa-venus-mars me-2"></i>Gender
                            </th>
                            <th>
                                <i class="fas fa-birthday-cake me-2"></i>Date of Birth
                            </th>
                            <th>
                                <i class="fas fa-graduation-cap me-2"></i>Qualifications
                            </th>
                            <th>
                                <i class="fas fa-clock me-2"></i>Experience
                            </th>
                            <th class="text-center" style="width: 120px;">
                                <i class="fas fa-cogs me-2"></i>Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody id="nursesTableBody">
                        <!-- Will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
            <div id="noNurse" class="text-center p-4 d-none">
                <i class="fas fa-user-nurse fa-3x text-muted mb-3"></i>
                <h5>No nurses found</h5>
                <p class="text-muted">Try adjusting your search criteria</p>
            </div>
        </div>

        <!-- Patients Section -->
        <div id="patients" class="content-section">
            <h2 class="section-title"><i class="fas fa-users"></i> Registered Patients</h2>
            <div class="mb-4">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="patientSearch" placeholder="Search patients..." onkeyup="filterPatients()">
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                        <span class="badge bg-primary" id="patientCount">0 Patients</span>
                    </div>
                </div>
            </div>
            <div class="table-container" style="position: relative; height: calc(100vh - 250px); overflow: hidden;">
                <div class="table-header" style="background: #1976D2; border-radius: 8px 8px 0 0; padding: 15px 20px;">
                    <div class="row align-items-center m-0">
                        <div class="col-2" style="color: white;">
                            <i class="fas fa-hashtag"></i> Patient ID
                        </div>
                        <div class="col-4" style="color: white;">
                            <i class="fas fa-user"></i> Name
                        </div>
                        <div class="col-2 text-center" style="color: white;">
                            <i class="fas fa-venus-mars"></i> Gender
                        </div>
                        <div class="col-2 text-center" style="color: white;">
                            <i class="fas fa-history"></i> Total Visits
                        </div>
                        <div class="col-2 text-center" style="color: white;">
                            <i class="fas fa-cogs"></i> Actions
                        </div>
                    </div>
                </div>
                <div class="table-body-container" style="height: calc(100% - 54px); overflow-y: auto;">
                    <table class="table table-hover mb-0">
                        <tbody id="patientsTableBody">
                            <!-- Will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div id="noPatient" class="text-center p-4 d-none">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5>No patients found</h5>
                <p class="text-muted">Try adjusting your search criteria</p>
            </div>
        </div>

        <!-- Patient Details Modal -->
        <div class="modal fade" id="patientDetailsModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-user-circle me-2"></i> Patient Details</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="patientDetailsContent">
                        <!-- Patient details will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Update active state in navigation
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('onclick') && link.getAttribute('onclick').includes(sectionId)) {
                    link.classList.add('active');
                }
            });

            // Load data for specific sections
            if (sectionId === 'patients') {
                loadPatients();
            } else if (sectionId === 'viewDoctors') {
                loadDoctors();
            } else if (sectionId === 'viewNurses') {
                loadNurses();
            }
        }

        async function registerDoctor(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            // Check if we're updating or creating
            const editDoctorId = sessionStorage.getItem('editDoctorId');
            const isUpdate = editDoctorId !== null;
            
            try {
                // Choose endpoint based on whether we're updating or creating
                const endpoint = isUpdate ? 'update_doctor.php' : 'register_doctor.php';
                
                // If updating, add the doctor_id to the data
                if (isUpdate) {
                    data.doctor_id = editDoctorId;
                }
                
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                if (result.success) {
                    showNotification(isUpdate ? 'Doctor updated successfully' : 'Doctor registered successfully', 'success');
                    form.reset();
                    
                    // Clear the edit state
                    sessionStorage.removeItem('editDoctorId');
                    
                    // Reset the submit button text
                    const submitBtn = form.querySelector('button[type="submit"]');
                    submitBtn.innerHTML = '<i class="fas fa-user-plus me-2"></i> Register Doctor';
                    
                    // Show password field again
                    const passwordField = form.querySelector('input[name="password"]');
                    const passwordGroup = passwordField.closest('.col-md-6');
                    passwordGroup.style.display = '';
                    passwordField.setAttribute('required', 'required');
                    
                    // Switch to view doctors section after successful operation
                    setTimeout(() => {
                        showSection('viewDoctors');
                        loadDoctors(); // Refresh the doctors list
                    }, 2000);
                } else {
                    showNotification(result.message || result.error, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('An error occurred while processing the request', 'error');
            }
        }

        async function registerNurse(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            // Check if we're updating or creating
            const editNurseId = sessionStorage.getItem('editNurseId');
            const isUpdate = editNurseId !== null;
            
            try {
                // Choose endpoint based on whether we're updating or creating
                const endpoint = isUpdate ? 'update_nurse.php' : 'register_nurse.php';
                
                // If updating, add the nurse_id to the data
                if (isUpdate) {
                    data.nurse_id = editNurseId;
                }
                
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                if (result.success) {
                    showNotification(isUpdate ? 'Nurse updated successfully' : 'Nurse registered successfully', 'success');
                    form.reset();
                    
                    // Clear the edit state
                    sessionStorage.removeItem('editNurseId');
                    
                    // Reset the submit button text
                    const submitBtn = form.querySelector('button[type="submit"]');
                    submitBtn.innerHTML = '<i class="fas fa-user-plus me-2"></i> Register Nurse';
                    
                    // Show password field again
                    const passwordField = form.querySelector('input[name="password"]');
                    const passwordGroup = passwordField.closest('.col-md-6');
                    passwordGroup.style.display = '';
                    passwordField.setAttribute('required', 'required');
                    
                    // Switch to view nurses section after successful operation
                    setTimeout(() => {
                        showSection('viewNurses');
                        loadNurses(); // Refresh the nurses list
                    }, 2000);
                } else {
                    showNotification(result.message || result.error, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('An error occurred while processing the request', 'error');
            }
        }

        function showNotification(message, type) {
            const notificationBar = document.getElementById('notificationBar');
            const notificationMessage = document.getElementById('notificationMessage');
            
            notificationBar.className = 'notification-bar';
            void notificationBar.offsetWidth; // Trigger reflow
            
            notificationMessage.textContent = message;
            notificationBar.classList.add(type);
            
            // Hide notification after 3 seconds
            setTimeout(() => {
                notificationBar.classList.remove(type);
            }, 3000);
        }

        // Load doctors and nurses data when respective sections are shown
        document.querySelector('[onclick="showSection(\'viewDoctors\')"]').addEventListener('click', loadDoctors);
        document.querySelector('[onclick="showSection(\'viewNurses\')"]').addEventListener('click', loadNurses);

        async function loadDoctors() {
            try {
                const response = await fetch('get_doctors.php');
                const doctors = await response.json();
                
                if (Array.isArray(doctors)) {
                    const tbody = document.getElementById('doctorsTableBody');
                    document.getElementById('doctorCount').textContent = `${doctors.length} Doctors`;
                    
                    if (doctors.length === 0) {
                        document.getElementById('noDoctor').classList.remove('d-none');
                        tbody.innerHTML = '';
                    } else {
                        document.getElementById('noDoctor').classList.add('d-none');
                        tbody.innerHTML = doctors.map(doctor => `
                            <tr>
                                <td class="text-center">${doctor.doctor_id}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary rounded-circle text-white d-flex align-items-center justify-content-center me-2" 
                                             style="width: 32px; height: 32px;">
                                            ${doctor.full_name.charAt(0).toUpperCase()}
                                        </div>
                                        ${doctor.full_name}
                                    </div>
                                </td>
                                <td>${doctor.email}</td>
                                <td>${doctor.phone}</td>
                                <td>
                                    <span class="badge bg-${doctor.gender === 'Male' ? 'info' : 'danger'}">${doctor.gender}</span>
                                </td>
                                <td>${doctor.qualifications}</td>
                                <td>${doctor.username}</td>
                                <td>
                                    <div class="btn-group-action">
                                        <button class="btn btn-sm btn-primary" onclick="editDoctor(${doctor.doctor_id})" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteDoctor(${doctor.doctor_id})" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `).join('');
                    }
                } else {
                    showNotification('Failed to load doctors data', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error loading doctors data', 'error');
            }
        }

        async function loadNurses() {
            try {
                const response = await fetch('get_nurses.php');
                const nurses = await response.json();
                
                if (Array.isArray(nurses)) {
                    const tbody = document.getElementById('nursesTableBody');
                    document.getElementById('nurseCount').textContent = `${nurses.length} Nurses`;
                    
                    if (nurses.length === 0) {
                        document.getElementById('noNurse').classList.remove('d-none');
                        tbody.innerHTML = '';
                    } else {
                        document.getElementById('noNurse').classList.add('d-none');
                        tbody.innerHTML = nurses.map(nurse => `
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary rounded-circle text-white d-flex align-items-center justify-content-center me-2" 
                                             style="width: 32px; height: 32px;">
                                            ${nurse.full_name.charAt(0).toUpperCase()}
                                        </div>
                                        ${nurse.full_name}
                                    </div>
                                </td>
                                <td>${nurse.email}</td>
                                <td>${nurse.phone}</td>
                                <td>
                                    <span class="badge bg-${nurse.gender === 'Male' ? 'info' : 'danger'}">${nurse.gender}</span>
                                </td>
                                <td>${nurse.date_of_birth}</td>
                                <td>${nurse.nursing_qualifications}</td>
                                <td>
                                    <span class="badge bg-success">${nurse.years_of_experience} years</span>
                                </td>
                                <td>
                                    <div class="btn-group-action">
                                        <button class="btn btn-sm btn-primary" onclick="editNurse(${nurse.nurse_id})" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteNurse(${nurse.nurse_id})" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `).join('');
                    }
                } else {
                    showNotification('Failed to load nurses data', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error loading nurses data', 'error');
            }
        }

        // Add delete doctor function
        async function deleteDoctor(doctorId) {
            if (confirm('Are you sure you want to delete this doctor?')) {
                try {
                    const response = await fetch('delete_doctor.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ doctor_id: doctorId })
                    });

                    const result = await response.json();
                    if (result.success) {
                        showNotification('Doctor deleted successfully', 'success');
                        loadDoctors(); // Refresh the list
                    } else {
                        showNotification(result.message || 'Failed to delete doctor', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showNotification('Error deleting doctor', 'error');
                }
            }
        }

        // Add edit doctor function
        async function editDoctor(doctorId) {
            try {
                // Fetch doctor data
                const response = await fetch(`get_doctor.php?id=${doctorId}`);
                const doctor = await response.json();
                
                if (doctor) {
                    // Switch to register doctor section
                    showSection('registerDoctor');
                    
                    // Store the doctor ID for editing
                    sessionStorage.setItem('editDoctorId', doctorId);
                    
                    // Fill the form with doctor data
                    const form = document.getElementById('doctorRegistrationForm');
                    form.full_name.value = doctor.full_name;
                    form.email.value = doctor.email;
                    form.phone.value = doctor.phone;
                    form.gender.value = doctor.gender;
                    form.qualifications.value = doctor.qualifications;
                    form.username.value = doctor.username;
                    
                    // Update the submit button text
                    const submitBtn = form.querySelector('button[type="submit"]');
                    submitBtn.innerHTML = '<i class="fas fa-save me-2"></i> Update Doctor';
                    
                    // Hide password field for editing
                    const passwordField = form.querySelector('input[name="password"]');
                    const passwordGroup = passwordField.closest('.col-md-6');
                    passwordGroup.style.display = 'none';
                    passwordField.removeAttribute('required');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error loading doctor data', 'error');
            }
        }

        async function editNurse(nurseId) {
            try {
                // Fetch nurse data
                const response = await fetch(`get_nurse.php?id=${nurseId}`);
                const nurse = await response.json();
                
                if (nurse) {
                    // Switch to register nurse section
                    showSection('registerNurse');
                    
                    // Store the nurse ID for editing
                    sessionStorage.setItem('editNurseId', nurseId);
                    
                    // Fill the form with nurse data
                    const form = document.getElementById('nurseRegistrationForm');
                    form.full_name.value = nurse.full_name;
                    form.email.value = nurse.email;
                    form.phone.value = nurse.phone;
                    form.gender.value = nurse.gender;
                    form.date_of_birth.value = nurse.date_of_birth;
                    form.nursing_qualifications.value = nurse.nursing_qualifications;
                    form.years_of_experience.value = nurse.years_of_experience;
                    form.username.value = nurse.username;
                    
                    // Update the submit button text
                    const submitBtn = form.querySelector('button[type="submit"]');
                    submitBtn.innerHTML = '<i class="fas fa-save me-2"></i> Update Nurse';
                    
                    // Hide password field for editing
                    const passwordField = form.querySelector('input[name="password"]');
                    const passwordGroup = passwordField.closest('.col-md-6');
                    passwordGroup.style.display = 'none';
                    passwordField.removeAttribute('required');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error loading nurse data', 'error');
            }
        }

        // Add delete nurse function
        async function deleteNurse(nurseId) {
            if (confirm('Are you sure you want to delete this nurse?')) {
                try {
                    const response = await fetch('delete_nurse.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ nurse_id: nurseId })
                    });

                    const result = await response.json();
                    if (result.success) {
                        showNotification('Nurse deleted successfully', 'success');
                        loadNurses(); // Refresh the list
                    } else {
                        showNotification(result.message || 'Failed to delete nurse', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showNotification('Error deleting nurse', 'error');
                }
            }
        }

        function filterDoctors() {
            const input = document.getElementById('doctorSearch');
            const filter = input.value.toLowerCase();
            const tbody = document.getElementById('doctorsTableBody');
            const rows = tbody.getElementsByTagName('tr');
            let visibleCount = 0;

            for (let row of rows) {
                const text = row.textContent || row.innerText;
                if (text.toLowerCase().indexOf(filter) > -1) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            }

            document.getElementById('noDoctor').classList.toggle('d-none', visibleCount > 0);
            document.getElementById('doctorCount').textContent = `${visibleCount} Doctors`;
        }

        function filterNurses() {
            const input = document.getElementById('nurseSearch');
            const filter = input.value.toLowerCase();
            const tbody = document.getElementById('nursesTableBody');
            const rows = tbody.getElementsByTagName('tr');
            let visibleCount = 0;

            for (let row of rows) {
                const text = row.textContent || row.innerText;
                if (text.toLowerCase().indexOf(filter) > -1) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            }

            document.getElementById('noNurse').classList.toggle('d-none', visibleCount > 0);
            document.getElementById('nurseCount').textContent = `${visibleCount} Nurses`;
        }

        async function loadPatients() {
            try {
                const response = await fetch('get_patients.php');
                const patients = await response.json();
                
                const tableBody = document.getElementById('patientsTableBody');
                document.getElementById('patientCount').textContent = `${patients.length} Patients`;
                
                if (patients.length === 0) {
                    document.getElementById('noPatient').classList.remove('d-none');
                    tableBody.innerHTML = '';
                } else {
                    document.getElementById('noPatient').classList.add('d-none');
                    tableBody.innerHTML = patients.map(patient => `
                        <tr>
                            <td class="col-2">
                                <span class="patient-number">#${patient.patient_id}</span>
                            </td>
                            <td class="col-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary rounded-circle text-white d-flex align-items-center justify-content-center me-2" 
                                         style="width: 32px; height: 32px;">
                                        ${patient.name.charAt(0).toUpperCase()}
                                    </div>
                                    ${patient.name}
                                </div>
                            </td>
                            <td class="col-2 text-center">
                                <span class="gender-text gender-${patient.gender.toLowerCase()}">${patient.gender}</span>
                            </td>
                            <td class="col-2 text-center">
                                <span class="visits-badge">${patient.total_visits} visits</span>
                            </td>
                            <td class="col-2 text-center">
                                <button class="btn btn-sm btn-danger" onclick="deletePatient(${patient.patient_id})" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('');
                }
            } catch (error) {
                console.error('Error loading patients:', error);
                showNotification('Error loading patients', 'error');
            }
        }

        async function viewPatientDetails(patientId) {
            try {
                const response = await fetch(`get_patient_details.php?id=${patientId}`);
                const details = await response.json();
                
                const content = document.getElementById('patientDetailsContent');
                content.innerHTML = `
                    <div class="patient-info">
                        <h4 class="mb-4">${details.name}</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Gender:</strong> ${details.gender}</p>
                                <p><strong>Age:</strong> ${details.age}</p>
                                <p><strong>Phone:</strong> ${details.phone_number}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Total Visits:</strong> ${details.total_visits}</p>
                                <p><strong>Last Visit:</strong> ${new Date(details.last_visit).toLocaleDateString()}</p>
                            </div>
                        </div>
                        <h5 class="mt-4">Visit History</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Diagnosis</th>
                                        <th>Blood Pressure</th>
                                        <th>Temperature</th>
                                        <th>Weight</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${details.visits.map(visit => `
                                        <tr>
                                            <td>${new Date(visit.visited_at).toLocaleDateString()}</td>
                                            <td>${visit.diagnosis || '-'}</td>
                                            <td>${visit.blood_pressure || '-'}</td>
                                            <td>${visit.temperature || '-'}</td>
                                            <td>${visit.weight || '-'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
                
                new bootstrap.Modal(document.getElementById('patientDetailsModal')).show();
            } catch (error) {
                console.error('Error loading patient details:', error);
                showNotification('Error loading patient details', 'error');
            }
        }

        function filterPatients() {
            const input = document.getElementById('patientSearch');
            const filter = input.value.toLowerCase();
            const tbody = document.getElementById('patientsTableBody');
            const rows = tbody.getElementsByTagName('tr');
            let visibleCount = 0;

            for (let row of rows) {
                const text = row.textContent || row.innerText;
                if (text.toLowerCase().indexOf(filter) > -1) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            }

            document.getElementById('noPatient').classList.toggle('d-none', visibleCount > 0);
            document.getElementById('patientCount').textContent = `${visibleCount} Patients`;
        }

        async function deletePatient(patientId) {
            if (confirm('Are you sure you want to delete this patient? This action cannot be undone.')) {
                try {
                    const response = await fetch('delete_patient.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ patient_id: patientId })
                    });

                    const result = await response.json();
                    if (result.success) {
                        showNotification('Patient deleted successfully', 'success');
                        loadPatients(); // Refresh the list
                    } else {
                        showNotification(result.message || 'Failed to delete patient', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showNotification('Error deleting patient', 'error');
                }
            }
        }

        // Add this to ensure patients are loaded when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            // If we're on the patients section, load the patients
            if (document.querySelector('#patients.content-section.active')) {
                loadPatients();
            }
        });
    </script>
</body>
</html> 