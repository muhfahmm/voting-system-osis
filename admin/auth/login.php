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
    
    <!-- Tailwind Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        darkbg: '#0b0f19',
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
                        outfit: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body class="bg-[#0b0f19] text-[#f1f5f9] min-h-screen flex flex-col items-center justify-center relative overflow-hidden font-sans p-4">
    <!-- Ambient Glow Backdrops -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[550px] h-[550px] bg-indigo-500/10 rounded-full blur-[140px] pointer-events-none z-0"></div>
    <div class="absolute top-[20%] right-[10%] w-[350px] h-[350px] bg-purple-500/5 rounded-full blur-[120px] pointer-events-none z-0"></div>

    <!-- Glass Container -->
    <div class="w-full max-w-[420px] bg-slate-800/35 backdrop-blur-[24px] border border-white/5 p-9 rounded-[28px] shadow-[0_25px_50px_-12px_rgba(0,0,0,0.5)] z-10 flex flex-col gap-6 relative">
        <!-- Header -->
        <div class="text-center flex flex-col items-center gap-3">
            <div class="w-16 h-16 bg-slate-900/40 backdrop-blur-md rounded-2xl border border-white/5 flex items-center justify-center shadow-lg">
                <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h1 class="font-outfit text-2xl lg:text-3xl font-extrabold bg-gradient-to-r from-white to-indigo-200 bg-clip-text text-transparent tracking-tight mt-2">Admin Login</h1>
            <p class="text-slate-400 text-sm">Access the Voting System Dashboard</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-200 p-4 rounded-xl text-sm flex items-center gap-3 animate-pulse">
                <svg class="w-5 h-5 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form class="login-form flex flex-col gap-5" action="" method="post">
            <input type="hidden" name="login" value="1">
            <div class="flex flex-col gap-2">
                <label class="font-outfit font-semibold text-xs text-slate-300 tracking-wider uppercase" for="username">Username</label>
                <div class="relative">
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="py-3.5 px-4 pl-11 rounded-xl bg-slate-950/65 border border-white/10 font-sans text-sm text-white w-full transition-all duration-300 placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:bg-slate-950/85 focus:shadow-[0_0_0_4px_rgba(99,102,241,0.15)]" 
                           placeholder="Enter your username" 
                           required
                           autocomplete="username"
                           autofocus>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <label class="font-outfit font-semibold text-xs text-slate-300 tracking-wider uppercase" for="password">Password</label>
                <div class="relative">
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="py-3.5 px-4 pl-11 pr-11 rounded-xl bg-slate-950/65 border border-white/10 font-sans text-sm text-white w-full transition-all duration-300 placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:bg-slate-950/85 focus:shadow-[0_0_0_4px_rgba(99,102,241,0.15)]" 
                           placeholder="Enter your password" 
                           required
                           autocomplete="current-password">
                    <button type="button" id="togglePasswordBtn" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white cursor-pointer select-none font-sans text-sm" onclick="togglePassword()">👁️</button>
                </div>
            </div>

            <button type="submit" name="login" class="w-full mt-2 py-3.5 px-6 rounded-xl bg-indigo-600 border border-indigo-500 hover:bg-indigo-500 hover:border-indigo-400 hover:shadow-[0_8px_20px_rgba(99,102,241,0.35)] active:translate-y-0.5 text-white font-bold text-sm tracking-wide transition-all duration-300" id="loginBtn">
                Login to Dashboard
            </button>
        </form>

        <div class="text-center text-sm text-slate-400 border-t border-white/5 pt-5">
            <p>Don't have an account? <a href="register.php" class="text-indigo-400 hover:text-indigo-300 font-semibold transition-colors duration-200">Register here</a></p>
        </div>
    </div>

    <div class="absolute bottom-6 text-center text-xs text-slate-600 z-5">
        Voting OSIS System &copy; <?= date('Y') ?>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('togglePasswordBtn');
            
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
            submitButton.disabled = true;
            submitButton.classList.add('opacity-75', 'cursor-not-allowed');
            submitButton.innerHTML = `<span class="inline-block w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin mr-2 align-middle"></span> Logging in...`;
        });

        // Auto focus username field
        document.getElementById('username').focus();
    </script>
</body>

</html>