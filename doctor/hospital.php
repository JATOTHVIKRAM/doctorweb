<?php
session_start();
$conn = new mysqli("localhost", "root", "Vikram@2005", "hospital_database");
if ($conn->connect_error) die("Database Connection Failed: " . $conn->connect_error);

// Secure Login Handling
if (isset($_POST['login'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $table = ($role == "admin") ? "Administrators" : "Doctors";
    
    $stmt = $conn->prepare("SELECT * FROM $table WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;
            $_SESSION['role'] = $role;
        } else echo "Invalid Password!";
    } else echo "User Not Found!";
}

// Register Doctor with Secure Input
if (isset($_POST['register_doctor'])) {
    if ($_SESSION['role'] != "admin") die("Unauthorized Access!");

    $stmt = $conn->prepare("INSERT INTO Doctors (admin_id, full_name, email, phone, gender, date_of_birth, profile_picture, license_number, specialization, years_of_experience, qualifications, username, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    //$hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $original_password = $_POST['password'];

    $stmt->bind_param(
        "issssssssisss", 
        $_SESSION['user']['admin_id'], 
        $_POST['full_name'], 
        $_POST['email'], 
        $_POST['phone'], 
        $_POST['gender'], 
        $_POST['date_of_birth'], 
        $_POST['profile_picture'], 
        $_POST['license_number'], 
        $_POST['specialization'], 
        $_POST['years_of_experience'], 
        $_POST['qualifications'], 
        $_POST['username'], 
        $original_password
    );
    
    if ($stmt->execute()) {
        echo "Doctor Registered Successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
}

// Fetch Patients for Doctor
$patients = $conn->query("SELECT * FROM Patients");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Hospital Management</title>
    <link rel="shortcut icon" type="image/x-icon" href="vcarelogo.ico" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <?php if (!isset($_SESSION['user'])) { ?>
            <form method="post">
                <h2>Login</h2>
                <input type="text" name="username" placeholder="Username" class="form-control mb-2" required>
                <input type="password" name="password" placeholder="Password" class="form-control mb-2" required>
                <select name="role" class="form-control mb-2">
                    <option value="admin">Admin</option>
                    <option value="doctor">Doctor</option>
                </select>
                <button type="submit" name="login" class="btn btn-primary">Login</button>
            </form>
        <?php } else { ?>
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user']['username']); ?></h2>
            
            <?php if ($_SESSION['role'] == "admin") { ?>
                <h3>Register Doctor</h3>
                <form method="post">
                    <input type="text" name="full_name" placeholder="Full Name" class="form-control mb-2" required>
                    <input type="email" name="email" placeholder="Email" class="form-control mb-2" required>
                    <input type="text" name="phone" placeholder="Phone" class="form-control mb-2" required>
                    <input type="text" name="specialization" placeholder="Specialization" class="form-control mb-2" required>
                    <input type="text" name="license_number" placeholder="License Number" class="form-control mb-2" required>
                    <input type="number" name="years_of_experience" placeholder="Years of Experience" class="form-control mb-2" required>
                    <input type="text" name="username" placeholder="Username" class="form-control mb-2" required>
                    <input type="password" name="password" placeholder="Password" class="form-control mb-2" required>
                    <button type="submit" name="register_doctor" class="btn btn-success">Register Doctor</button>
                </form>
            <?php } else { ?>
                <h3>Patients</h3>
                <input type="text" id="search" placeholder="Search Patient" class="form-control mb-2" onkeyup="searchPatient()">
                <ul id="patientList" class="list-group">
                    <?php while ($row = $patients->fetch_assoc()) { ?>
                        <li class="list-group-item" onclick="viewPatient(<?php echo $row['patient_id']; ?>)"><?php echo htmlspecialchars($row['name']); ?></li>
                    <?php } ?>
                </ul>
                <div id="patientDetails" class="mt-3"></div>
            <?php } ?>
        <?php } ?>
    </div>

    <script>
    function searchPatient() {
        let input = document.getElementById("search").value.toLowerCase();
        let items = document.querySelectorAll("#patientList li");
        items.forEach(item => {
            if (item.innerText.toLowerCase().includes(input)) item.style.display = "block";
            else item.style.display = "none";
        });
    }
    
    function viewPatient(id) {
        fetch("get_patient.php?patient_id=" + id)
            .then(response => response.text())
            .then(data => document.getElementById("patientDetails").innerHTML = data);
    }
    </script>
</body>
</html>
