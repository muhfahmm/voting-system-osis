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
    <link class="icon" href="../../assets/img/logo osis.png">
    
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body class="bg-[#0b0f19] text-[#f1f5f9] min-h-screen flex font-sans relative overflow-x-hidden">
    <!-- Ambient Glow Backdrops -->
    <div class="absolute top-[-10%] left-[-10%] w-[500px] h-[500px] bg-indigo-500/5 rounded-full blur-[140px] pointer-events-none z-0"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[500px] h-[500px] bg-purple-500/5 rounded-full blur-[140px] pointer-events-none z-0"></div>

    <!-- Sidebar Navigation -->
    <aside class="w-72 bg-slate-900/60 backdrop-blur-xl border-r border-white/5 flex flex-col justify-between shrink-0 min-h-screen z-10">
        <div class="flex flex-col gap-8 p-6">
            <!-- Brand / Header -->
            <div class="flex items-center gap-3 border-b border-white/5 pb-6">
                <div class="w-10 h-10 bg-indigo-600/20 border border-indigo-500/35 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="font-outfit font-extrabold text-lg bg-gradient-to-r from-white to-indigo-200 bg-clip-text text-transparent">Admin Panel</h2>
                    <p class="text-xs text-slate-400 font-semibold tracking-wide">E-VOTING SKALSA</p>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex flex-col gap-1.5">
                <a href="../1_dashboard/dashboard.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-slate-400 font-medium transition-all duration-300 hover:bg-slate-800/40 hover:text-white group">
                    <i class="bi bi-speedometer2 text-lg group-hover:text-indigo-400 transition-colors"></i>
                    <span>Dashboard</span>
                </a>
                <a href="../2_hasil-vote/result.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-slate-400 font-medium transition-all duration-300 hover:bg-slate-800/40 hover:text-white group">
                    <i class="bi bi-bar-chart-line text-lg group-hover:text-indigo-400 transition-colors"></i>
                    <span>Hasil Vote</span>
                </a>
                <a href="../3_kandidat/daftar-kandidat.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-slate-400 font-medium transition-all duration-300 hover:bg-slate-800/40 hover:text-white group">
                    <i class="bi bi-people text-lg group-hover:text-indigo-400 transition-colors"></i>
                    <span>Daftar Kandidat</span>
                </a>
                <a href="../4_daftar-voter/daftar-voter.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-slate-400 font-medium transition-all duration-300 hover:bg-slate-800/40 hover:text-white group">
                    <i class="bi bi-card-checklist text-lg group-hover:text-indigo-400 transition-colors"></i>
                    <span>Daftar Voter</span>
                </a>
                <a href="../5_token-siswa/token-siswa.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-slate-400 font-medium transition-all duration-300 hover:bg-slate-800/40 hover:text-white group">
                    <i class="bi bi-key text-lg group-hover:text-indigo-400 transition-colors"></i>
                    <span>Token Siswa</span>
                </a>
                <a href="../6_token-guru/token-guru.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-slate-400 font-medium transition-all duration-300 hover:bg-slate-800/40 hover:text-white group">
                    <i class="bi bi-shield-lock text-lg group-hover:text-indigo-400 transition-colors"></i>
                    <span>Token Guru</span>
                </a>
                <a href="daftar-admin.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl bg-indigo-600/10 text-indigo-300 border-l-4 border-indigo-500 font-semibold group">
                    <i class="bi bi-person-workspace text-lg text-indigo-400"></i>
                    <span>Daftar Admin</span>
                </a>
            </nav>
        </div>

        <!-- User / Logout -->
        <div class="p-6 border-t border-white/5 bg-slate-950/20 flex flex-col gap-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-slate-850 border border-white/10 rounded-lg flex items-center justify-center text-slate-300 font-outfit font-bold text-sm">
                    <?= strtoupper(substr($admin, 0, 2)) ?>
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Logged In As</p>
                    <p class="text-sm text-slate-200 font-bold truncate"><?= htmlspecialchars($admin) ?></p>
                </div>
            </div>
            <a href="../../auth/logout.php" class="flex items-center justify-center gap-2.5 w-full py-3 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-200 border border-red-500/10 hover:border-red-500/30 font-bold text-sm transition-all duration-300">
                <i class="bi bi-box-arrow-right"></i>
                <span>Keluar (Logout)</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 p-8 lg:p-12 z-10 flex flex-col gap-8 w-full">
        <!-- Top bar / Welcome -->
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-white/5 pb-6">
            <div>
                <h1 class="font-outfit text-3xl font-extrabold bg-gradient-to-r from-white to-indigo-200 bg-clip-text text-transparent">Daftar Administrator</h1>
                <p class="text-slate-400 text-sm mt-1">Mengelola hak akses dan pengguna panel administratif</p>
            </div>
            <div class="flex gap-3">
                <a href="../../auth/register.php" target="_blank" class="flex items-center gap-2 px-5 py-3 rounded-xl bg-indigo-600 border border-indigo-500 hover:bg-indigo-500 hover:border-indigo-400 hover:shadow-[0_8px_20px_rgba(99,102,241,0.25)] active:translate-y-0.5 text-white font-bold text-sm tracking-wide transition-all duration-300">
                    <i class="bi bi-person-plus"></i>
                    <span>Tambah Admin Baru</span>
                </a>
            </div>
        </header>

        <!-- Stats Widgets -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-3xl p-6 shadow-2xl relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 w-28 h-28 bg-indigo-500/5 rounded-full group-hover:scale-125 transition-transform duration-500"></div>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-indigo-500/10 border border-indigo-500/20 rounded-2xl flex items-center justify-center text-indigo-400 text-xl">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Total Administrator</p>
                        <p class="text-3xl font-outfit font-extrabold mt-1 text-white"><?= $totalAdmins ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admins Table Section -->
        <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-[28px] shadow-2xl p-7 flex flex-col gap-6">
            <h2 class="font-outfit text-xl font-bold text-slate-200 flex items-center gap-2">
                <i class="bi bi-list-task text-indigo-400"></i>
                <span>Data Administrator Terdaftar</span>
            </h2>

            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-white/5 text-xs text-slate-400 font-bold uppercase tracking-wider">
                            <th class="py-4 px-4">No</th>
                            <th class="py-4 px-4">ID Admin</th>
                            <th class="py-4 px-4">Username</th>
                            <th class="py-4 px-4">Tingkat Akses (Role)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-sm text-slate-200">
                        <?php 
                        $no = 1;
                        if ($totalAdmins > 0): 
                            while ($row = mysqli_fetch_assoc($adminsQuery)):
                        ?>
                            <tr class="hover:bg-white/[0.02] transition-colors duration-200">
                                <td class="py-4 px-4 font-semibold text-slate-400"><?= $no++ ?></td>
                                <td class="py-4 px-4">
                                    <span class="font-mono text-indigo-300 bg-indigo-500/10 border border-indigo-500/20 py-1 px-2.5 rounded-lg text-xs">
                                        ADM-<?= str_pad($row['id'], 3, '0', STR_PAD_LEFT) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-4 font-bold flex items-center gap-2 text-white">
                                    <i class="bi bi-person text-slate-500"></i>
                                    <span><?= htmlspecialchars($row['username']) ?></span>
                                    <?php if ($row['username'] === $admin): ?>
                                        <span class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[10px] font-bold px-2 py-0.5 rounded-full ml-1 animate-pulse">Anda</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold py-1 px-3 rounded-full bg-slate-800 text-slate-300 border border-slate-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
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
