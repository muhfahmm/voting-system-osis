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
        echo "<script>alert('Username sudah terdaftar!');</script>";
    } elseif ($password1 !== $password2) {
        echo "<script>alert('Konfirmasi password tidak cocok!');</script>";
    } else {
        $password = password_hash($password1, PASSWORD_DEFAULT);
        mysqli_query($db, "INSERT INTO tb_admin VALUES('', '$username', '$password')");
        echo "<script>alert('Registrasi akun admin berhasil! Silakan login.'); document.location.href='login.php';</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Admin - Voting OSIS</title>
    <link rel="icon" href="../assets/img/logo osis.png">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>input:focus, select:focus, textarea:focus, button:focus { outline: none !important; box-shadow: none !important; }</style>
</head>

<body class="bg-slate-100 text-slate-800 min-h-screen flex flex-col items-center justify-center font-sans p-4">

    <!-- Container Register Card -->
    <div class="w-full max-w-md bg-white border border-slate-200 rounded-2xl shadow-sm p-8 flex flex-col gap-6 relative">

        <!-- Header Logos & Titles -->
        <div class="text-center flex flex-col items-center gap-3">
            <div class="flex items-center gap-4 justify-center">
                <img src="../assets/img/logo osis.png" alt="Logo OSIS" class="h-14 object-contain">
                <img src="../assets/img/logo sekolah.png" alt="Logo Sekolah" class="h-14 object-contain">
            </div>
            <div>
                <h1 class="font-sans text-2xl font-bold text-slate-900 tracking-tight">Daftar Akun Admin</h1>
            </div>
        </div>

        <form class="register-form flex flex-col gap-4" action="" method="post">
            <input type="hidden" name="register" value="1">
            
            <!-- Username -->
            <div class="flex flex-col gap-1.5">
                <label class="font-sans font-semibold text-xs text-slate-700 uppercase tracking-wider" for="username">Username</label>
                <div class="relative">
                    <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <input type="text" id="username" name="username" 
                           class="py-2.5 px-3.5 pl-10 rounded-lg bg-slate-50 border border-slate-300 font-sans text-sm text-slate-800 w-full focus:outline-none transition-colors placeholder-slate-400" 
                           placeholder="Masukkan username baru" required autocomplete="off" autofocus>
                </div>
            </div>

            <!-- Password -->
            <div class="flex flex-col gap-1.5">
                <label class="font-sans font-semibold text-xs text-slate-700 uppercase tracking-wider" for="password1">Password</label>
                <div class="relative">
                    <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <input type="password" id="password1" name="password1" 
                           class="py-2.5 px-3.5 pl-10 pr-10 rounded-lg bg-slate-50 border border-slate-300 font-sans text-sm text-slate-800 w-full focus:outline-none transition-colors placeholder-slate-400" 
                           placeholder="Masukkan password" required>
                    
                    <button type="button" id="togglePassword1Btn" 
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 cursor-pointer"
                            onclick="togglePassword('password1', 'togglePassword1Btn')">
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </span>
                        <span class="hidden">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </span>
                    </button>
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="flex flex-col gap-1.5">
                <label class="font-sans font-semibold text-xs text-slate-700 uppercase tracking-wider" for="password2">Konfirmasi Password</label>
                <div class="relative">
                    <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <input type="password" id="password2" name="password2" 
                           class="py-2.5 px-3.5 pl-10 pr-10 rounded-lg bg-slate-50 border border-slate-300 font-sans text-sm text-slate-800 w-full focus:outline-none transition-colors placeholder-slate-400" 
                           placeholder="Ulangi password" required>
                    
                    <button type="button" id="togglePassword2Btn" 
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 cursor-pointer"
                            onclick="togglePassword('password2', 'togglePassword2Btn')">
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </span>
                        <span class="hidden">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </span>
                    </button>
                </div>
            </div>

            <button type="submit" name="register" 
                    class="w-full mt-2 py-2.5 px-4 rounded-lg bg-emerald-600 text-white font-semibold text-sm tracking-wide cursor-pointer border border-emerald-600" id="registerBtn">
                Daftar Akun Admin
            </button>
        </form>

        <div class="text-center text-xs text-slate-500 border-t border-slate-200 pt-4">
            <p>Sudah memiliki akun? <a href="login.php" class="text-emerald-700 font-semibold underline">Login di sini</a></p>
        </div>
    </div>

    <script>
        function togglePassword(inputId, btnId) {
            const passwordInput = document.getElementById(inputId);
            const button = document.getElementById(btnId);
            const eyeIcon = button.querySelector('span:first-child');
            const eyeSlashIcon = button.querySelector('span:last-child');
            
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

        document.querySelector('.register-form').addEventListener('submit', function(e) {
            const submitButton = document.getElementById('registerBtn');
            submitButton.disabled = true;
            submitButton.classList.add('opacity-75', 'cursor-not-allowed');
            submitButton.innerHTML = `<span class="inline-block w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin mr-2 align-middle"></span> Memproses...`;
        });

        document.getElementById('username').focus();
    </script>
</body>

</html>


