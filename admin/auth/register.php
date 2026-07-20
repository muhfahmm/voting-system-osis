<?php
session_start();
require '../../db/db.php';

if (isset($_SESSION['login'])) {
    header("Location: ../sidebar-menu/1_dashboard/dashboard.php");
    exit;
}

if (isset($_POST['register'])) {
    $username = mysqli_real_escape_string($db, $_POST['username']);
    $password1 = $_POST['password1'];
    $password2 = $_POST['password2'];

    $result = mysqli_query($db, "SELECT username FROM tb_admin WHERE username = '$username'");
    if (mysqli_fetch_assoc($result)) {
        echo "<script>alert('Username sudah terdaftar');</script>";
    } elseif ($password1 !== $password2) {
        echo "<script>alert('Password tidak sama');</script>";
    } else {
        $password = password_hash($password1, PASSWORD_DEFAULT);
        mysqli_query($db, "INSERT INTO tb_admin VALUES('', '$username', '$password')");
        echo "<script>alert('Registrasi berhasil! Silakan login.'); document.location.href='login.php';</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Admin - Voting OSIS</title>
    <link rel="icon" href="../assets/img/logo osis.png">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
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

<body class="bg-[#f8fafc] text-slate-800 min-h-screen flex flex-col items-center justify-center relative overflow-hidden font-sans p-4">
    <!-- Ambient Glow Backdrops -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[550px] h-[550px] bg-indigo-200/70 rounded-full blur-[140px] pointer-events-none z-0"></div>
    <div class="absolute top-[20%] right-[10%] w-[350px] h-[350px] bg-purple-200/70 rounded-full blur-[120px] pointer-events-none z-0"></div>

    <!-- Glass Container -->
    <div class="w-full max-w-[420px] bg-white/80 backdrop-blur-xl border border-slate-200 p-9 rounded-[28px] shadow-sm z-10 flex flex-col gap-6 relative">
        <!-- Header -->
        <div class="text-center flex flex-col items-center gap-3">
            <div class="w-16 h-16 bg-indigo-100 border border-indigo-200 rounded-2xl flex items-center justify-center shadow-sm">
                <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
            </div>
            <h1 class="font-outfit text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight mt-2">Admin Register</h1>
            <p class="text-slate-500 text-sm">Create an account to manage the E-Voting</p>
        </div>

        <form class="register-form flex flex-col gap-5" action="" method="post">
            <input type="hidden" name="register" value="1">
            
            <!-- Username -->
            <div class="flex flex-col gap-2">
                <label class="font-outfit font-semibold text-xs text-slate-600 tracking-wider uppercase" for="username">Username</label>
                <div class="relative">
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <input type="text" id="username" name="username" 
                           class="py-3.5 px-4 pl-11 rounded-xl bg-white border border-slate-200 font-sans text-sm text-slate-700 w-full transition-all duration-300 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:shadow-[0_0_0_4px_rgba(99,102,241,0.15)]" 
                           placeholder="Enter username" required autocomplete="off" autofocus>
                </div>
            </div>

            <!-- Password -->
            <div class="flex flex-col gap-2">
                <label class="font-outfit font-semibold text-xs text-slate-600 tracking-wider uppercase" for="password1">Password</label>
                <div class="relative">
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <input type="password" id="password1" name="password1" 
                           class="py-3.5 px-4 pl-11 pr-11 rounded-xl bg-white border border-slate-200 font-sans text-sm text-slate-700 w-full transition-all duration-300 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:shadow-[0_0_0_4px_rgba(99,102,241,0.15)]" 
                           placeholder="Enter password" required>
                    
                    <button type="button" id="togglePassword1Btn" 
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-indigo-600 cursor-pointer transition-colors duration-200"
                            onclick="togglePassword('password1', 'togglePassword1Btn')">
                        <span id="eyeIcon1" class="w-5 h-5">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </span>
                        <span id="eyeSlashIcon1" class="w-5 h-5 hidden">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </span>
                    </button>
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="flex flex-col gap-2">
                <label class="font-outfit font-semibold text-xs text-slate-600 tracking-wider uppercase" for="password2">Confirm Password</label>
                <div class="relative">
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <input type="password" id="password2" name="password2" 
                           class="py-3.5 px-4 pl-11 pr-11 rounded-xl bg-white border border-slate-200 font-sans text-sm text-slate-700 w-full transition-all duration-300 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:shadow-[0_0_0_4px_rgba(99,102,241,0.15)]" 
                           placeholder="Confirm password" required>
                    
                    <button type="button" id="togglePassword2Btn" 
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-indigo-600 cursor-pointer transition-colors duration-200"
                            onclick="togglePassword('password2', 'togglePassword2Btn')">
                        <span id="eyeIcon2" class="w-5 h-5">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </span>
                        <span id="eyeSlashIcon2" class="w-5 h-5 hidden">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </span>
                    </button>
                </div>
            </div>

            <button type="submit" name="register" 
                    class="w-full mt-2 py-3.5 px-6 rounded-xl bg-indigo-600 border border-indigo-500 hover:bg-indigo-700 hover:border-indigo-600 hover:shadow-[0_8px_20px_rgba(99,102,241,0.35)] active:translate-y-0.5 text-white font-bold text-sm tracking-wide transition-all duration-300" id="registerBtn">
                Daftar Akun
            </button>
        </form>

        <div class="text-center text-sm text-slate-500 border-t border-slate-200 pt-5">
            <p>Sudah punya akun? <a href="login.php" class="text-indigo-600 hover:text-indigo-700 font-semibold transition-colors duration-200">Login</a></p>
        </div>
    </div>

    <div class="absolute bottom-6 text-center text-xs text-slate-400 z-5">
        Voting OSIS System &copy; <?= date('Y') ?>
    </div>

    <script>
        // Toggle password visibility dengan parameter inputId dan btnId
        function togglePassword(inputId, btnId) {
            const passwordInput = document.getElementById(inputId);
            const button = document.getElementById(btnId);
            const eyeIcon = button.querySelector('span:first-child');   // eye icon
            const eyeSlashIcon = button.querySelector('span:last-child'); // eye slash icon
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.add('hidden');
                eyeSlashIcon.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('hidden');
                eyeSlashIcon.classList.add('hidden');
            }
        }

        // Form submission loading state
        document.querySelector('.register-form').addEventListener('submit', function(e) {
            const submitButton = document.getElementById('registerBtn');
            submitButton.disabled = true;
            submitButton.classList.add('opacity-75', 'cursor-not-allowed');
            submitButton.innerHTML = `<span class="inline-block w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin mr-2 align-middle"></span> Mendaftarkan...`;
        });

        document.getElementById('username').focus();
    </script>
</body>

</html>