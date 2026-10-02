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

    
    <div class="w-full max-w-md bg-white border border-slate-200 rounded-2xl shadow-sm p-8 flex flex-col gap-6 relative">

        
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
            
            
            <div class="flex flex-col gap-1.5">
                <label class="font-sans font-semibold text-xs text-slate-700 uppercase tracking-wider" for="username">Username</label>
                <div class="relative">
                    <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 flex items-center justify-center">
                        <i class="bi bi-person text-base"></i>
                    </div>
                    <input type="text" id="username" name="username" 
                           class="py-2.5 px-3.5 pl-10 rounded-lg bg-slate-50 border border-slate-300 font-sans text-sm text-slate-800 w-full focus:outline-none transition-colors placeholder-slate-400" 
                           placeholder="Masukkan username baru" required autocomplete="off" autofocus>
                </div>
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="font-sans font-semibold text-xs text-slate-700 uppercase tracking-wider" for="password1">Password</label>
                <div class="relative">
                    <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 flex items-center justify-center">
                        <i class="bi bi-lock text-base"></i>
                    </div>
                    <input type="password" id="password1" name="password1" 
                           class="py-2.5 px-3.5 pl-10 pr-10 rounded-lg bg-slate-50 border border-slate-300 font-sans text-sm text-slate-800 w-full focus:outline-none transition-colors placeholder-slate-400" 
                           placeholder="Masukkan password" required>
                    
                    <button type="button" id="togglePassword1Btn" 
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 cursor-pointer flex items-center justify-center"
                            onclick="togglePassword('password1', 'togglePassword1Btn')">
                        <span>
                            <i class="bi bi-eye text-base"></i>
                        </span>
                        <span class="hidden">
                            <i class="bi bi-eye-slash text-base"></i>
                        </span>
                    </button>
                </div>
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="font-sans font-semibold text-xs text-slate-700 uppercase tracking-wider" for="password2">Konfirmasi Password</label>
                <div class="relative">
                    <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 flex items-center justify-center">
                        <i class="bi bi-shield-check text-base"></i>
                    </div>
                    <input type="password" id="password2" name="password2" 
                           class="py-2.5 px-3.5 pl-10 pr-10 rounded-lg bg-slate-50 border border-slate-300 font-sans text-sm text-slate-800 w-full focus:outline-none transition-colors placeholder-slate-400" 
                           placeholder="Ulangi password" required>
                    
                    <button type="button" id="togglePassword2Btn" 
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 cursor-pointer flex items-center justify-center"
                            onclick="togglePassword('password2', 'togglePassword2Btn')">
                        <span>
                            <i class="bi bi-eye text-base"></i>
                        </span>
                        <span class="hidden">
                            <i class="bi bi-eye-slash text-base"></i>
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