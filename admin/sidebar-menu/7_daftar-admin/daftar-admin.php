<?php
session_start();
require '../../../db/db.php';

if (!isset($_SESSION['login'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$admin = $_SESSION['username'];

// Ambil data admin
$adminsQuery = mysqli_query($db, "SELECT id, username FROM tb_admin ORDER BY id ASC");
$totalAdmins = mysqli_num_rows($adminsQuery);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Admin - Voting OSIS</title>
    <link rel="icon" href="../../assets/img/logo osis.png">
    
    <!-- Tailwind Play CDN -->
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>input:focus, select:focus, textarea:focus, button:focus { outline: none !important; box-shadow: none !important; }</style>
</head>

<body class="bg-[#f8fafc] text-slate-800 min-h-screen flex font-sans relative overflow-x-hidden">
    
    
    

    <!-- Sidebar Navigation -->
    <aside class="w-64 bg-white/80 backdrop-blur-xl border-r border-slate-200 shadow-sm flex flex-col justify-between shrink-0 min-h-screen z-10">
        <div class="flex flex-col gap-4 p-4">
            <!-- Brand / Header -->
            <div class="flex items-center gap-3 border-b border-slate-200 pb-6">
                <img src="../../assets/img/logo osis.png" alt="Logo OSIS" class="h-9 object-contain">
                <div>
                    <h2 class="font-sans font-extrabold text-lg text-slate-900">Admin Panel</h2>
                    <p class="text-xs text-slate-500 font-semibold tracking-wide">E-VOTING SKALSA</p>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex flex-col gap-1.5">
                <a href="../1_dashboard/dashboard.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-600 font-medium transition-all duration-300 group">
                    <i class="bi bi-speedometer2 text-lg group-transition-colors"></i>
                    <span>Dashboard</span>
                </a>
                <a href="../2_hasil-vote/result.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-600 font-medium transition-all duration-300 group">
                    <i class="bi bi-bar-chart-line text-lg group-transition-colors"></i>
                    <span>Hasil Vote</span>
                </a>
                <a href="../3_kandidat/daftar-kandidat.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-600 font-medium transition-all duration-300 group">
                    <i class="bi bi-people text-lg group-transition-colors"></i>
                    <span>Daftar Kandidat</span>
                </a>
                <a href="../4_daftar-voter/daftar-voter.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-600 font-medium transition-all duration-300 group">
                    <i class="bi bi-card-checklist text-lg group-transition-colors"></i>
                    <span>Daftar Voter</span>
                </a>
                <a href="../5_token-siswa/token-siswa.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-600 font-medium transition-all duration-300 group">
                    <i class="bi bi-key text-lg group-transition-colors"></i>
                    <span>Token Siswa</span>
                </a>
                <a href="../6_token-guru/token-guru.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-600 font-medium transition-all duration-300 group">
                    <i class="bi bi-shield-lock text-lg group-transition-colors"></i>
                    <span>Token Guru</span>
                </a>
                <a href="daftar-admin.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg bg-emerald-50 text-emerald-700 border-l-4 border-emerald-500 font-semibold group">
                    <i class="bi bi-person-workspace text-lg text-emerald-500"></i>
                    <span>Daftar Admin</span>
                </a>
            </nav>
        </div>

        <!-- User / Logout -->
        <div class="p-4 border-t border-slate-200 bg-slate-50 flex flex-col gap-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-white border border-slate-200 rounded-lg flex items-center justify-center text-slate-700 font-sans font-bold text-sm">
                    <?= strtoupper(substr($admin, 0, 2)) ?>
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Logged In As</p>
                    <p class="text-sm text-slate-700 font-bold truncate"><?= htmlspecialchars($admin) ?></p>
                </div>
            </div>
            <a href="../../auth/logout.php" class="flex items-center justify-center gap-2.5 w-full py-2.5 rounded-lg bg-red-50 text-red-600 border border-red-200 font-bold text-sm transition-all duration-300">
                <i class="bi bi-box-arrow-right"></i>
                <span>Keluar (Logout)</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 p-5 md:p-8 z-10 flex flex-col gap-4 w-full">
        <!-- Top bar / Welcome -->
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-6">
            <div>
                <h1 class="font-sans text-2xl font-bold text-slate-900">Daftar Administrator</h1>
            </div>
            <div class="flex gap-3">
                <a href="../../auth/register.php" target="_blank" class="flex items-center gap-2 px-5 py-3 rounded-xl bg-emerald-600 border border-emerald-500 (16, 185, 129,0.25)] text-white font-bold text-sm tracking-wide transition-all duration-300">
                    <i class="bi bi-person-plus"></i>
                    <span>Tambah Admin Baru</span>
                </a>
            </div>
        </header>

        <!-- Stats Widgets -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl p-4 shadow-sm relative overflow-hidden group">
                
                <div class="flex items-center gap-4">
                    <div class="w-9 h-9 bg-emerald-100 border border-emerald-200 rounded-2xl flex items-center justify-center text-emerald-600 text-xl">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Total Administrator</p>
                        <p class="text-xl font-bold mt-1 text-slate-900"><?= $totalAdmins ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admins Table Section -->
        <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl shadow-sm p-4 md:p-5 flex flex-col gap-4">
            <h2 class="font-sans text-base font-bold text-slate-800 flex items-center gap-2">
                <i class="bi bi-list-task text-emerald-500"></i>
                <span>Data Administrator Terdaftar</span>
            </h2>

            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs text-slate-500 font-bold uppercase tracking-wider">
                            <th class="py-2.5 px-3">No</th>
                            <th class="py-2.5 px-3">ID Admin</th>
                            <th class="py-2.5 px-3">Username</th>
                            <th class="py-2.5 px-3">Tingkat Akses (Role)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-sm text-slate-700">
                        <?php 
                        $no = 1;
                        if ($totalAdmins > 0): 
                            mysqli_data_seek($adminsQuery, 0);
                            while ($row = mysqli_fetch_assoc($adminsQuery)):
                        ?>
                            <tr class="transition-colors duration-200">
                                <td class="py-2.5 px-3 font-semibold text-slate-500"><?= $no++ ?></td>
                                <td class="py-2.5 px-3">
                                    <span class="font-mono text-emerald-600 bg-emerald-50 border border-emerald-200 py-1 px-2.5 rounded-lg text-xs">
                                        ADM-<?= str_pad($row['id'], 3, '0', STR_PAD_LEFT) ?>
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 font-bold flex items-center gap-2 text-slate-900">
                                    <i class="bi bi-person text-slate-400"></i>
                                    <span><?= htmlspecialchars($row['username']) ?></span>
                                    <?php if ($row['username'] === $admin): ?>
                                        <span class="bg-emerald-100 border border-emerald-200 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded-full ml-1">Anda</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2.5 px-3">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold py-1 px-3 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Full Administrator
                                    </span>
                                </td>
                            </tr>
                        <?php 
                            endwhile;
                        else: 
                        ?>
                            <tr>
                                <td colspan="4" class="py-8 px-4 text-center text-slate-500">Belum ada administrator terdaftar.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>

</html>








