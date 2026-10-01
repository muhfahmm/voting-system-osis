<?php
session_start();
require '../../../db/db.php';

if (!isset($_SESSION['login'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$admin = $_SESSION['username'];

$query = mysqli_query($db, "
    SELECT k.nomor_kandidat, k.nama_ketua, k.nama_wakil, COUNT(v.id) AS total_suara
    FROM tb_kandidat k
    LEFT JOIN tb_vote_log v ON k.nomor_kandidat = v.nomor_kandidat
    GROUP BY k.nomor_kandidat, k.nama_ketua, k.nama_wakil
    ORDER BY k.nomor_kandidat ASC
");

$totalQuery = mysqli_query($db, "SELECT COUNT(*) AS total FROM tb_vote_log");
$totalRow = mysqli_fetch_assoc($totalQuery);
$totalVotes = $totalRow['total'];

$totalAdminQuery = mysqli_query($db, "SELECT COUNT(*) AS total FROM tb_admin");
$totalAdminRow = mysqli_fetch_assoc($totalAdminQuery);
$totalAdminCount = $totalAdminRow['total'];

$kandidatCountQuery = mysqli_query($db, "SELECT COUNT(*) AS total FROM tb_kandidat");
$kandidatCountRow = mysqli_fetch_assoc($kandidatCountQuery);
$totalKandidat = $kandidatCountRow['total'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Voting OSIS</title>
    <link rel="icon" href="../../assets/img/logo osis.png">
    
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        darkbg: '#0b0f19',
                    },
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

    
    <!-- Mobile Overlay Backdrop -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm z-30 hidden lg:hidden transition-opacity duration-300 opacity-0" onclick="toggleSidebar()"></div>

    <!-- Aside Sidebar -->
    <aside id="sidebarMenu" class="w-64 bg-white/95 lg:bg-white/80 backdrop-blur-xl border-r border-slate-200 shadow-xl lg:shadow-sm flex flex-col fixed top-0 left-0 z-40 h-screen transition-transform duration-300 -translate-x-full lg:translate-x-0">
        
        <div class="flex flex-col gap-4 p-4 flex-1 overflow-y-auto">
            
            <div class="flex items-center justify-between border-b border-slate-200 pb-6">
                <div class="flex items-center gap-3">
                    <img src="../../assets/img/logo osis.png" alt="Logo OSIS" class="h-9 object-contain">
                    <div>
                        <h2 class="font-sans font-extrabold text-lg text-slate-900">Admin Panel</h2>
                        <p class="text-xs text-slate-500 font-semibold tracking-wide">E-VOTING SKALSA</p>
                    </div>
                </div>
                <button type="button" onclick="toggleSidebar()" class="lg:hidden p-2 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors">
                    <i class="bi bi-x-lg text-lg"></i>
                </button>
            </div>

            
            <nav class="flex flex-col gap-1.5">
                <a href="dashboard.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg bg-emerald-50 text-emerald-700 border-l-4 border-emerald-500 font-semibold group">
                    <i class="bi bi-speedometer2 text-lg text-emerald-500"></i>
                    <span>Dashboard</span>
                </a>
                <a href="../2_hasil-vote/result.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-600 font-medium transition-all duration-300 group hover:bg-slate-100/80">
                    <i class="bi bi-bar-chart-line text-lg group-hover:text-emerald-600 transition-colors"></i>
                    <span>Hasil Vote</span>
                </a>
                <a href="../3_kandidat/daftar-kandidat.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-600 font-medium transition-all duration-300 group hover:bg-slate-100/80">
                    <i class="bi bi-people text-lg group-hover:text-emerald-600 transition-colors"></i>
                    <span>Daftar Kandidat</span>
                </a>
                <a href="../4_daftar-voter/daftar-voter.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-600 font-medium transition-all duration-300 group hover:bg-slate-100/80">
                    <i class="bi bi-card-checklist text-lg group-hover:text-emerald-600 transition-colors"></i>
                    <span>Daftar Voter</span>
                </a>
                <a href="../5_token-siswa/token-siswa.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-600 font-medium transition-all duration-300 group hover:bg-slate-100/80">
                    <i class="bi bi-key text-lg group-hover:text-emerald-600 transition-colors"></i>
                    <span>Token Siswa</span>
                </a>
                <a href="../6_token-guru/token-guru.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-600 font-medium transition-all duration-300 group hover:bg-slate-100/80">
                    <i class="bi bi-shield-lock text-lg group-hover:text-emerald-600 transition-colors"></i>
                    <span>Token Guru</span>
                </a>
                <a href="../7_daftar-admin/daftar-admin.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-600 font-medium transition-all duration-300 group hover:bg-slate-100/80">
                    <i class="bi bi-person-workspace text-lg group-hover:text-emerald-600 transition-colors"></i>
                    <span>Daftar Admin</span>
                </a>
            </nav>
        </div>

        
        <div class="p-4 border-t border-slate-200 bg-slate-50 flex flex-col gap-4 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-white border border-slate-200 rounded-lg flex items-center justify-center text-slate-700 font-sans font-bold text-sm">
                    <?= strtoupper(substr($admin, 0, 2)) ?>
                </div>
                <div class="overflow-hidden">
                    <p class="text-sm text-slate-700 font-bold truncate"><?= htmlspecialchars($admin) ?></p>
                </div>
            </div>
            <a href="../../auth/logout.php" class="flex items-center justify-center gap-2.5 w-full py-2.5 rounded-lg bg-red-50 text-red-600 border border-red-200 font-bold text-sm transition-all duration-300 hover:bg-red-100">
                <i class="bi bi-box-arrow-right"></i>
                <span>Keluar (Logout)</span>
            </a>
        </div>
    </aside>

    
    <main class="flex-1 p-5 md:p-8 z-10 flex flex-col gap-4 w-full lg:ml-64">
        
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-6">
            <div class="flex items-center justify-between w-full sm:w-auto">
                <div class="flex items-center gap-3">
                    <button type="button" onclick="toggleSidebar()" class="lg:hidden p-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 font-bold text-lg shadow-sm hover:bg-slate-50 flex items-center justify-center">
                        <i class="bi bi-list"></i>
                    </button>
                    <div>
                        <h1 class="font-sans text-xl sm:text-2xl font-bold text-slate-900">Dashboard Admin</h1>
                        <p class="text-slate-600 text-xs sm:text-sm mt-0.5">Selamat datang <b class="text-emerald-600"><?= htmlspecialchars($admin) ?></b> <i class="bi bi-person-fill"></i></p>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="../../../index.php" target="_blank" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 font-semibold text-sm transition-all duration-300 shadow-sm hover:bg-slate-50">
                    <i class="bi bi-house"></i>
                    <span>Homepage</span>
                </a>
                <a href="http://localhost/phpmyadmin/index.php?route=/database/structure&db=db_vote_osis_generate_token" target="_blank" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 border border-emerald-500 text-white font-bold text-sm transition-all duration-300 shadow-sm hover:bg-emerald-700">
                    <i class="bi bi-database"></i>
                    <span>Buka Database</span>
                </a>
            </div>
        </header>

        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl p-4 shadow-sm relative overflow-hidden group">
                <div class="flex items-center gap-4">
                    <div class="w-9 h-9 bg-emerald-100 border border-emerald-200 rounded-2xl flex items-center justify-center text-emerald-600 text-xl">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Total Suara Masuk</p>
                        <p class="text-xl font-bold mt-1 text-slate-900"><?= $totalVotes ?> <span class="text-xs text-slate-500 font-sans font-medium">Suara</span></p>
                    </div>
                </div>
            </div>

            
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl p-4 shadow-sm relative overflow-hidden group">
                <div class="flex items-center gap-4">
                    <div class="w-9 h-9 bg-emerald-100 border border-emerald-200 rounded-2xl flex items-center justify-center text-emerald-600 text-xl">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Pasangan Kandidat</p>
                        <p class="text-xl font-bold mt-1 text-slate-900"><?= $totalKandidat ?> <span class="text-xs text-slate-500 font-sans font-medium">Paslon</span></p>
                    </div>
                </div>
            </div>

            
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl p-4 shadow-sm relative overflow-hidden group">
                <div class="flex items-center gap-4">
                    <div class="w-9 h-9 bg-emerald-100 border border-emerald-200 rounded-2xl flex items-center justify-center text-emerald-600 text-xl">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Total Administrator</p>
                        <p class="text-xl font-bold mt-1 text-slate-900"><?= $totalAdminCount ?> <span class="text-xs text-slate-500 font-sans font-medium">Admin</span></p>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl shadow-sm p-4 md:p-5 flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h2 class="font-sans text-base font-bold text-slate-800 flex items-center gap-2">
                    <i class="bi bi-bar-chart-fill text-emerald-500"></i>
                    <span>Perolehan Suara Sementara</span>
                </h2>
                <a href="../2_hasil-vote/result.php" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 flex items-center gap-1">
                    <span>Lihat Detail Realtime</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs text-slate-500 font-bold uppercase tracking-wider">
                            <th class="py-2.5 px-3">No Paslon</th>
                            <th class="py-2.5 px-3">Ketua & Wakil</th>
                            <th class="py-2.5 px-3">Total Suara</th>
                            <th class="py-2.5 px-3">Persentase</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-sm text-slate-700">
                        <?php 
                        if (mysqli_num_rows($query) > 0):
                            while ($row = mysqli_fetch_assoc($query)):
                                $percentage = ($totalVotes > 0) ? round(($row['total_suara'] / $totalVotes) * 100, 1) : 0;
                        ?>
                            <tr class="transition-colors duration-200 hover:bg-slate-50">
                                <td class="py-2.5 px-3 font-bold text-slate-900">
                                    <span class="w-7 h-7 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center justify-center text-xs font-bold">
                                        <?= $row['nomor_kandidat'] ?>
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 font-semibold text-slate-800">
                                    <?= htmlspecialchars($row['nama_ketua']) ?> & <?= htmlspecialchars($row['nama_wakil']) ?>
                                </td>
                                <td class="py-2.5 px-3 font-bold text-slate-900">
                                    <?= number_format($row['total_suara']) ?> Suara
                                </td>
                                <td class="py-2.5 px-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 bg-slate-100 rounded-full h-2 overflow-hidden max-w-[120px]">
                                            <div class="bg-emerald-500 h-full rounded-full" style="width: <?= $percentage ?>%"></div>
                                        </div>
                                        <span class="text-xs font-bold text-slate-600"><?= $percentage ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <tr>
                                <td colspan="4" class="py-8 px-4 text-center text-slate-500">Belum ada data kandidat.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebarMenu');
            const overlay = document.getElementById('sidebarOverlay');
            if (!sidebar || !overlay) return;

            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                setTimeout(() => overlay.classList.remove('opacity-0'), 10);
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('opacity-0');
                setTimeout(() => overlay.classList.add('hidden'), 300);
            }
        }
    </script>
</body>

</html>
