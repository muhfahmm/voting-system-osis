<?php
session_start();
require '../../db/db.php';

if (isset($_SESSION['login'])) {
    header("Location: ../index.php");
    exit;
}

$error = "";

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($db, $_POST['username']);
    $password = $_POST['password'];

    $result = mysqli_query($db, "SELECT * FROM tb_admin WHERE username = '$username'");

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);

        if (password_verify($password, $row['password'])) {
            // simpan session
            $_SESSION['login'] = true;
            $_SESSION['username'] = $row['username'];

            header("Location: ../index.php");
            exit;
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Voting OSIS</title>
    <link rel="icon" href="../assets/img/logo osis.png">
    <style>
        /* Reset & Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, 
                #667eea 0%, 
                #764ba2 25%, 
                #f093fb 50%, 
                #f5576c 75%, 
                #4facfe 100%);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Background Blobs */
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(40px);
            opacity: 0.6;
            animation: float 20s infinite ease-in-out;
        }

        .blob:nth-child(1) {
            width: 300px;
            height: 300px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }

        .blob:nth-child(2) {
            width: 250px;
            height: 250px;
            background: linear-gradient(45deg, #f093fb, #f5576c);
            bottom: -80px;
            right: -80px;
            animation-delay: 5s;
        }

        .blob:nth-child(3) {
            width: 200px;
            height: 200px;
            background: linear-gradient(45deg, #4facfe, #00f2fe);
            top: 50%;
            right: -50px;
            animation-delay: 10s;
        }

        /* Glass Container */
        .glass-container {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.2),
                0 0 0 1px rgba(255, 255, 255, 0.1);
            padding: 40px 35px;
            position: relative;
            z-index: 10;
            transform-style: preserve-3d;
            perspective: 1000px;
            animation: glassFloat 6s ease-in-out infinite;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 35px;
            position: relative;
        }

        .logo {
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.05));
            backdrop-filter: blur(10px);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.2);
        }

        .logo i {
            font-size: 32px;
            background: linear-gradient(45deg, #fff, #e6e6e6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(45deg, #fff, #e6e6e6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            font-weight: 300;
            letter-spacing: 0.3px;
        }

        /* Form */
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
            font-size: 18px;
            z-index: 1;
        }

        .form-input {
            width: 100%;
            padding: 16px 20px 16px 52px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            color: #fff;
            font-size: 16px;
            font-weight: 400;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .form-input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.4);
            box-shadow: 
                0 0 0 3px rgba(255, 255, 255, 0.1),
                inset 0 2px 4px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .form-input:hover {
            border-color: rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.12);
        }

        /* Password Toggle */
        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            font-size: 18px;
            padding: 5px;
            transition: all 0.3s ease;
            z-index: 2;
        }

        .password-toggle:hover {
            color: rgba(255, 255, 255, 1);
            transform: translateY(-50%) scale(1.1);
        }

        /* Error Message */
        .error-message {
            background: rgba(255, 87, 108, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 87, 108, 0.3);
            color: rgba(255, 255, 255, 0.9);
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.5s ease-out;
        }

        .error-message::before {
            content: "⚠";
            font-size: 18px;
            color: #ff6b6b;
        }

        /* Login Button */
        .login-button {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, 
                rgba(102, 126, 234, 0.9), 
                rgba(118, 75, 162, 0.9));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 16px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-top: 10px;
            position: relative;
            overflow: hidden;
        }

        .login-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(255, 255, 255, 0.2), 
                transparent);
            transition: 0.5s;
        }

        .login-button:hover {
            transform: translateY(-3px);
            box-shadow: 
                0 10px 25px rgba(102, 126, 234, 0.4),
                0 0 0 1px rgba(255, 255, 255, 0.2);
        }

        .login-button:hover::before {
            left: 100%;
        }

        .login-button:active {
            transform: translateY(-1px);
        }

        /* Register Link */
        .register-link {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
        }

        .register-link p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            font-weight: 300;
        }

        .register-link a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-weight: 600;
            position: relative;
            padding: 2px 4px;
            transition: all 0.3s ease;
        }

        .register-link a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, #fff, transparent);
            transition: width 0.3s ease;
        }

        .register-link a:hover {
            color: #fff;
        }

        .register-link a:hover::after {
            width: 100%;
        }

        /* Animations */
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(20px, -20px) rotate(90deg); }
            50% { transform: translate(0, 20px) rotate(180deg); }
            75% { transform: translate(-20px, 0) rotate(270deg); }
        }

        @keyframes glassFloat {
            0%, 100% { transform: translateY(0) rotateX(0deg); }
            50% { transform: translateY(-10px) rotateX(1deg); }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .glass-container {
                padding: 30px 25px;
                margin: 0 15px;
                border-radius: 20px;
            }

            .header h1 {
                font-size: 24px;
            }

            .form-input {
                padding: 14px 18px 14px 48px;
                font-size: 15px;
            }

            .login-button {
                padding: 16px;
                font-size: 15px;
            }

            .blob {
                display: none;
            }
        }

        /* Loading State */
        .login-button.loading {
            color: transparent;
            pointer-events: none;
        }

        .login-button.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }

        /* Floating Particles */
        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            animation: particleFloat 15s infinite linear;
        }

        @keyframes particleFloat {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }

        /* Footer */
        .footer {
            position: absolute;
            bottom: 20px;
            width: 100%;
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
            font-size: 12px;
            z-index: 5;
        }
    </style>
</head>

<body>
    <!-- Background Blobs -->
    <div class="blob"></div>
    <div class="blob"></div>
    <div class="blob"></div>

    <!-- Floating Particles -->
    <div class="particles" id="particles"></div>

    <!-- Glass Container -->
    <div class="glass-container">
        <div class="header">
            <div class="logo">
                <i>🔒</i>
            </div>
            <h1>Admin Login</h1>
            <p>Access the Voting System Dashboard</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form class="login-form" action="" method="post">
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <div class="input-wrapper">
                    <div class="input-icon">👤</div>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-input" 
                           placeholder="Enter your username" 
                           required
                           autocomplete="username"
                           autofocus>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-wrapper">
                    <div class="input-icon">🔑</div>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-input" 
                           placeholder="Enter your password" 
                           required
                           autocomplete="current-password">
                    <button type="button" class="password-toggle" onclick="togglePassword()">👁️</button>
                </div>
            </div>

            <button type="submit" name="login" class="login-button" id="loginBtn">
                Login to Dashboard
            </button>
        </form>

        <div class="register-link">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>

    <div class="footer">
        Voting OSIS System © 2024
    </div>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.querySelector('.password-toggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleButton.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleButton.textContent = '👁️';
            }
        }

        // Form submission loading state
        document.querySelector('.login-form').addEventListener('submit', function(e) {
            const submitButton = document.getElementById('loginBtn');
            submitButton.classList.add('loading');
            
            // Remove loading state after 5 seconds (safety net)
            setTimeout(() => {
                submitButton.classList.remove('loading');
            }, 5000);
        });

        // Create floating particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 50;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Random position
                particle.style.left = Math.random() * 100 + 'vw';
                particle.style.top = Math.random() * 100 + 'vh';
                
                // Random size
                const size = Math.random() * 3 + 1;
                particle.style.width = size + 'px';
                particle.style.height = size + 'px';
                
                // Random opacity
                particle.style.opacity = Math.random() * 0.5 + 0.2;
                
                // Random animation delay and duration
                const delay = Math.random() * 5;
                const duration = Math.random() * 10 + 10;
                particle.style.animationDelay = delay + 's';
                particle.style.animationDuration = duration + 's';
                
                particlesContainer.appendChild(particle);
            }
        }

        // Auto focus username field
        document.getElementById('username').focus();

        // Initialize particles when page loads
        window.addEventListener('load', createParticles);

        // Add focus effect to inputs
        const inputs = document.querySelectorAll('.form-input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>

</html>