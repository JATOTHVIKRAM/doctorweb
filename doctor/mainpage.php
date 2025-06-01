<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>V-Care</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Alex+Brush&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
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
        
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 3;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
        }
        
        .logo-container {
            z-index: 3;
        }
        
        .logo {
            width: 180px;
            height: auto;
        }
        
        .login-options {
            display: flex;
            gap: 1.5rem;
            z-index: 3;
        }
        
        .login-btn {
            padding: 0.8rem 1.8rem;
            font-size: 1.1rem;
            border: 3px solid #1976D2;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #1976D2;
            font-weight: bold;
            background-color: rgba(255, 255, 255, 0.9);
        }
        
        .login-btn:hover, .login-btn:active {
            background-color: #1976D2;
            color: white;
            transform: translateY(-2px);
        }
        
        .doctor-btn {
            border-color: #1976D2;
            color: #1976D2;
        }
        .doctor-btn:hover, .doctor-btn:active {
            background-color: #1976D2;
            color: white;
        }
        .admin-btn {
            border-color: #1976D2;
            color: #1976D2;
        }
        .admin-btn:hover, .admin-btn:active {
            background-color: #1976D2;
            color: white;
        }
        .quote-container {
            position: absolute;
            top: 65%;
            left: 35%;
            transform: translate(-50%, -50%);
            text-align: left;
            z-index: 2;
            width: 90%;
            max-width: 800px;
            padding: 2rem;
            background: transparent;
            border-radius: 15px;
        }
        .quote {
            font-family: 'Great Vibes', cursive;
            font-size: 5rem;
            color: #1976D2;
            margin-bottom: 1rem;
            line-height: 1.4;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transform: translateY(20px);
            position: absolute;
            width: 100%;
            left: 0;
            top: 0;
            transition: all 0.5s ease;
            text-transform: none;
            letter-spacing: 1px;
        }
        .quote.active {
            opacity: 1;
            transform: translateY(0);
        }
        .quote span {
            display: inline-block;
            animation: wave 0.5s ease-in-out;
            animation-fill-mode: backwards;
        }
        @keyframes wave {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
            100% {
                transform: translateY(0);
            }
        }
        .quote-author {
            font-family: 'Alex Brush', cursive;
            font-size: 2rem;
            color: #1976D2;
            margin-top: 150px;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease 0.3s;
            letter-spacing: 1px;
            font-weight: normal;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }
        .quote-author.active {
            opacity: 1;
            transform: translateY(0);
        }
        .login-card {
            display: none;
            position: fixed;
            top: 100px;
            right: 40px;
            width: 400px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            padding: 2rem;
            animation: slideIn 0.3s ease-out;
            backdrop-filter: blur(5px);
            z-index: 3;
        }
        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        .login-card h3 {
            color: #1976D2;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        .form-floating {
            margin-bottom: 1rem;
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
        .login-submit-btn {
            background-color: #1976D2;
            color: white;
            border: none;
            padding: 0.8rem;
            border-radius: 25px;
            width: 100%;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .login-submit-btn:hover {
            background-color: #1565C0;
            transform: translateY(-2px);
        }
        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            color: #666;
            font-size: 1.5rem;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        .close-btn:hover {
            color: #1976D2;
        }
        @media (max-width: 1200px) {
            .quote-container {
                left: 40%;
            }
        }
        @media (max-width: 768px) {
            .quote-container {
                left: 50%;
                top: 60%;
                text-align: center;
            }
            .quote, .quote-author {
                padding-left: 0;
            }
            .quote {
                font-size: 4rem;
            }
            .quote-author {
                font-size: 1.8rem;
            }
        }
        @media (max-width: 480px) {
            .quote-container {
                top: 55%;
            }
            .quote {
                font-size: 3rem;
            }
            .quote-author {
                font-size: 1.5rem;
            }
        }
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: -0.5rem;
            margin-bottom: 1rem;
            display: none;
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .form-control.is-invalid {
            border-color: #dc3545;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        .shake {
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        }
        @keyframes shake {
            10%, 90% {
                transform: translate3d(-1px, 0, 0);
            }
            20%, 80% {
                transform: translate3d(2px, 0, 0);
            }
            30%, 50%, 70% {
                transform: translate3d(-4px, 0, 0);
            }
            40%, 60% {
                transform: translate3d(4px, 0, 0);
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo-container">
            <img src="v-care logo.png" alt="V-CARE" class="logo">
        </div>
        <div class="login-options">
            <button onclick="showDoctorLogin()" class="login-btn doctor-btn">Doctor Login</button>
            <button type="button" class="login-btn admin-btn" data-bs-toggle="modal" data-bs-target="#adminLoginModal">Admin Login</button>
        </div>
    </header>

    <!-- Doctor Login Card -->
    <div id="doctorLogin" class="login-card">
        <button class="close-btn" onclick="hideDoctorLogin()">
            <i class="fas fa-times"></i>
        </button>
        <h3><i class="fas fa-user-md me-2"></i>Doctor Login</h3>
        <form id="doctorLoginForm" onsubmit="handleDoctorLogin(event)">
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="doctorUsername" name="username" placeholder="Username" required>
                <label for="doctorUsername">Username</label>
            </div>
            <div class="form-floating mb-4">
                <input type="password" class="form-control" id="doctorPassword" name="password" placeholder="Password" required>
                <label for="doctorPassword">Password</label>
            </div>
            <div class="error-message" id="doctorErrorMessage">
                <i class="fas fa-exclamation-circle me-1"></i>
                <span id="errorText">Invalid credentials. Please try again.</span>
            </div>
            <button type="submit" class="login-submit-btn">
                <span>Login</span> <i class="fas fa-arrow-right ms-2"></i>
            </button>
        </form>
    </div>

    <!-- Admin Login Card -->
    <div id="adminLogin" class="login-card">
        <button class="close-btn" onclick="hideAdminLogin()">
            <i class="fas fa-times"></i>
        </button>
        <h3><i class="fas fa-user-shield me-2"></i>Admin Login</h3>
        <form id="adminLoginForm" onsubmit="handleAdminLogin(event)">
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="adminUsername" placeholder="Username" required>
                <label for="adminUsername">Username</label>
            </div>
            <div class="form-floating mb-4">
                <input type="password" class="form-control" id="adminPassword" placeholder="Password" required>
                <label for="adminPassword">Password</label>
            </div>
            <button type="submit" class="login-submit-btn">
                Login <i class="fas fa-arrow-right ms-2"></i>
            </button>
        </form>
    </div>

    <div class="quote-container">
        <div class="quote" data-index="0">
            "The greatest wealth is Health"
        </div>
        <div class="quote" data-index="1">
            "A healthy outside starts from the inside"
        </div>
        <div class="quote" data-index="2">
            "Health is not valued till sickness comes"
        </div>
        <div class="quote" data-index="3">
            "Take care of your body, it's the only place you have to live"
        </div>
        <div class="quote" data-index="4">
            "Your health is an investment, not an expense"
        </div>
        <div class="quote-author">
            - Words of Wisdom
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showDoctorLogin() {
            hideAdminLogin(); // Hide admin login if it's open
            document.getElementById('doctorLogin').style.display = 'block';
            resetLoginForm('doctor');
        }

        function hideDoctorLogin() {
            document.getElementById('doctorLogin').style.display = 'none';
            resetLoginForm('doctor');
        }

        function showAdminLogin() {
            hideDoctorLogin(); // Hide doctor login if it's open
            document.getElementById('adminLogin').style.display = 'block';
            resetLoginForm('admin');
        }

        function hideAdminLogin() {
            document.getElementById('adminLogin').style.display = 'none';
            resetLoginForm('admin');
        }

        function resetLoginForm(type) {
            const form = document.getElementById(type + 'LoginForm');
            const errorMessage = document.getElementById(type + 'ErrorMessage');
            const inputs = form.querySelectorAll('input');
            
            form.reset();
            if (errorMessage) {
                errorMessage.style.display = 'none';
            }
            inputs.forEach(input => {
                input.classList.remove('is-invalid');
            });
        }

        function showError(type, message) {
            const errorMessage = document.getElementById(type + 'ErrorMessage');
            const errorText = document.getElementById('errorText');
            const form = document.getElementById(type + 'LoginForm');
            const inputs = form.querySelectorAll('input');
            
            errorText.textContent = message;
            errorMessage.style.display = 'block';
            inputs.forEach(input => {
                input.classList.add('is-invalid');
            });
            
            form.classList.add('shake');
            setTimeout(() => {
                form.classList.remove('shake');
            }, 500);
        }

        async function handleDoctorLogin(event) {
            event.preventDefault();
            const username = document.getElementById('doctorUsername').value;
            const password = document.getElementById('doctorPassword').value;
            const submitBtn = event.target.querySelector('button[type="submit"]');

            try {
                // Only disable the button during login
                submitBtn.disabled = true;

                const response = await fetch('doctor_auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ username, password })
                });

                const data = await response.json();
                
                if (data.success) {
                    window.location.href = 'doctor_dashboard.php';
                } else {
                    showError('doctor', data.message || 'Invalid credentials. Please try again.');
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                showError('doctor', 'An error occurred. Please try again.');
                submitBtn.disabled = false;
            }
        }

        async function handleAdminLogin(event) {
            event.preventDefault();
            // No need to check credentials, just redirect to admin dashboard
            window.location.href = 'admin_dashboard.php';
        }

        // Update login buttons in header
        document.querySelector('.doctor-btn').onclick = showDoctorLogin;
        document.querySelector('.admin-btn').onclick = showAdminLogin;

        // Quote animation
        document.addEventListener('DOMContentLoaded', function() {
            const quotes = document.querySelectorAll('.quote');
            const author = document.querySelector('.quote-author');
            let currentQuote = 0;

            function animateText(element) {
                const text = element.textContent;
                element.textContent = '';
                for (let i = 0; i < text.length; i++) {
                    const span = document.createElement('span');
                    span.textContent = text[i];
                    span.style.animationDelay = `${i * 0.05}s`;
                    element.appendChild(span);
                }
            }

            function showQuote(index) {
                quotes.forEach(quote => {
                    quote.classList.remove('active');
                });
                author.classList.remove('active');

                setTimeout(() => {
                    quotes[index].classList.add('active');
                    author.classList.add('active');
                    animateText(quotes[index]);
                }, 100);
            }

            function rotateQuotes() {
                showQuote(currentQuote);
                currentQuote = (currentQuote + 1) % quotes.length;
            }

            // Initial display
            rotateQuotes();
            // Rotate quotes every 5 seconds
            setInterval(rotateQuotes, 5000);
        });
    </script>
</body>
</html> 