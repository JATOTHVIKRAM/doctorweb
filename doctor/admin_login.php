<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - V-Care</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background-image: url('doctor_bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header img {
            height: 60px;
            margin-bottom: 1rem;
        }
        
        .form-control {
            background-color: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(0, 0, 0, 0.1);
            padding: 0.75rem 1rem;
        }
        
        .form-control:focus {
            background-color: rgba(255, 255, 255, 0.95);
            border-color: #1976D2;
            box-shadow: 0 0 0 0.25rem rgba(25, 118, 210, 0.25);
        }
        
        .btn-primary {
            background-color: #1976D2;
            border-color: #1976D2;
            padding: 0.75rem;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background-color: #1565C0;
            border-color: #1565C0;
        }
        
        .alert {
            display: none;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="v-care logo.png" alt="V-CARE">
            <h2>Admin Login</h2>
        </div>
        
        <form id="adminLoginForm">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <div class="alert alert-danger" id="errorAlert" role="alert"></div>
            
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>

    <script>
        document.getElementById('adminLoginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const errorAlert = document.getElementById('errorAlert');
            
            try {
                const response = await fetch('admin_auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ username, password })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Redirect to admin dashboard on successful login
                    window.location.href = 'admin_dashboard.php';
                } else {
                    // Show error message
                    errorAlert.textContent = data.message;
                    errorAlert.style.display = 'block';
                }
            } catch (error) {
                errorAlert.textContent = 'An error occurred. Please try again.';
                errorAlert.style.display = 'block';
            }
        });
    </script>
</body>
</html> 