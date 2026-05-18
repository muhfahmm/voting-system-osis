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
                <a href="dashboard.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl bg-indigo-600/10 text-indigo-300 border-l-4 border-indigo-500 font-semibold group">
                    <i class="bi bi-speedometer2 text-lg text-indigo-400"></i>
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
                <a href="../7_daftar-admin/daftar-admin.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-slate-400 font-medium transition-all duration-300 hover:bg-slate-800/40 hover:text-white group">
                    <i class="bi bi-person-workspace text-lg group-hover:text-indigo-400 transition-colors"></i>
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
                <h1 class="font-outfit text-3xl font-extrabold bg-gradient-to-r from-white to-indigo-200 bg-clip-text text-transparent">Dashboard Admin</h1>
                <p class="text-slate-400 text-sm mt-1">Selamat datang kembali, <b class="text-indigo-300"><?= htmlspecialchars($admin) ?></b> 👤</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="../../../index.php" target="_blank" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 border border-white/10 hover:bg-slate-750 text-slate-200 font-semibold text-sm transition-all duration-300">
                    <i class="bi bi-house"></i>
                    <span>Homepage</span>
                </a>
                <a href="http://localhost/phpmyadmin/index.php?route=/database/structure&db=db_vote_osis_generate_token" target="_blank" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600/25 border border-indigo-500/30 hover:bg-indigo-600/40 text-indigo-200 font-bold text-sm transition-all duration-300">
                    <i class="bi bi-database"></i>
                    <span>Buka Database</span>
                </a>
            </div>
        </header>

        <!-- Stats Widgets -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Widget 1: Total Suara -->
            <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-3xl p-6 shadow-2xl relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 w-28 h-28 bg-indigo-500/5 rounded-full group-hover:scale-125 transition-transform duration-500"></div>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-indigo-500/10 border border-indigo-500/20 rounded-2xl flex items-center justify-center text-indigo-400 text-xl">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Total Suara Masuk</p>
                        <p class="text-3xl font-outfit font-extrabold mt-1 text-white"><?= $totalVotes ?> <span class="text-xs text-slate-400 font-sans font-medium">Suara</span></p>
                    </div>
                </div>
            </div>

            <!-- Widget 2: Total Kandidat -->
            <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-3xl p-6 shadow-2xl relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 w-28 h-28 bg-purple-500/5 rounded-full group-hover:scale-125 transition-transform duration-500"></div>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-purple-500/10 border border-purple-500/20 rounded-2xl flex items-center justify-center text-purple-400 text-xl">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Total Paslon Kandidat</p>
                        <p class="text-3xl font-outfit font-extrabold mt-1 text-white"><?= $totalKandidat ?> <span class="text-xs text-slate-400 font-sans font-medium">Paslon</span></p>
                    </div>
                </div>
            </div>

            <!-- Widget 3: Total Admin -->
            <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-3xl p-6 shadow-2xl relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 w-28 h-28 bg-emerald-500/5 rounded-full group-hover:scale-125 transition-transform duration-500"></div>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl flex items-center justify-center text-emerald-400 text-xl">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Total Administrator</p>
                        <p class="text-3xl font-outfit font-extrabold mt-1 text-white"><?= $totalAdminCount ?> <span class="text-xs text-slate-400 font-sans font-medium">Admin</span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Standings Table Details -->
        <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-[28px] shadow-2xl p-7 flex flex-col gap-6">
            <h2 class="font-outfit text-xl font-bold text-slate-200 flex items-center gap-2">
                <i class="bi bi-trophy text-indigo-400"></i>
                <span>Perolehan Suara Pasangan Calon</span>
            </h2>

            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-white/5 text-xs text-slate-400 font-bold uppercase tracking-wider">
                            <th class="py-4 px-4">No</th>
                            <th class="py-4 px-4">Pasangan Kandidat</th>
                            <th class="py-4 px-4 text-center">Nomor Urut</th>
                            <th class="py-4 px-4">Jumlah Suara</th>
                            <th class="py-4 px-4">Persentase & Visualisasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-sm text-slate-200">
                        <?php
                        $no = 1;
                        if (mysqli_num_rows($query) > 0):
                            while ($row = mysqli_fetch_assoc($query)):
                                $persentase = $totalVotes > 0 ? round(($row['total_suara'] / $totalVotes) * 100, 2) : 0;
                        ?>
                            <tr class="hover:bg-white/[0.01] transition-colors duration-200">
                                <td class="py-5 px-4 font-semibold text-slate-400"><?= $no++ ?></td>
                                <td class="py-5 px-4">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="font-bold text-white"><?= htmlspecialchars($row['nama_ketua']) ?></span>
                                        <span class="text-xs text-slate-400 font-medium">Wakil: <?= htmlspecialchars($row['nama_wakil']) ?></span>
                                    </div>
                                </td>
                                <td class="py-5 px-4 text-center">
                                    <span class="inline-block w-8 h-8 rounded-full bg-indigo-500/10 border border-indigo-500/35 text-indigo-300 font-outfit font-extrabold text-sm leading-8">
                                        <?= htmlspecialchars($row['nomor_kandidat']) ?>
                                    </span>
                                </td>
                                <td class="py-5 px-4">
                                    <span class="font-bold text-slate-100"><?= htmlspecialchars($row['total_suara']) ?></span>
                                    <span class="text-xs text-slate-500 ml-1">pemilih</span>
                                </td>
                                <td class="py-5 px-4">
                                    <div class="flex items-center gap-4 min-w-[200px] lg:min-w-[300px]">
                                        <div class="flex-1 h-3 bg-slate-950 rounded-full overflow-hidden border border-white/5 p-0.5">
                                            <div class="h-full bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 rounded-full shadow-[0_0_10px_rgba(99,102,241,0.5)] transition-all duration-1000" style="width: <?= $persentase ?>%;"></div>
                                        </div>
                                        <span class="font-bold text-indigo-300 font-outfit text-sm w-12 text-right"><?= $persentase ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                            <tr>
                                <td colspan="5" class="py-12 text-center text-slate-500">Belum ada data kandidat terdaftar.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Visual Bar Chart Details -->
        <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-[28px] shadow-2xl p-7 flex flex-col gap-6">
            <h2 class="font-outfit text-xl font-bold text-slate-200 flex items-center gap-2">
                <i class="bi bi-graph-up-arrow text-indigo-400"></i>
                <span>Standings Grafik Batang</span>
            </h2>

            <div class="flex flex-col gap-5">
                <?php
                if (mysqli_num_rows($query) > 0):
                    mysqli_data_seek($query, 0);
                    while ($row = mysqli_fetch_assoc($query)):
                        $persentase = $totalVotes > 0 ? round(($row['total_suara'] / $totalVotes) * 100, 2) : 0;
                ?>
                    <div class="flex flex-col gap-2">
                        <div class="flex justify-between items-center text-xs lg:text-sm font-semibold">
                            <span class="text-slate-300 font-outfit font-bold"><?= htmlspecialchars($row['nama_ketua']) ?> & <?= htmlspecialchars($row['nama_wakil']) ?> (Paslon #<?= htmlspecialchars($row['nomor_kandidat']) ?>)</span>
                            <span class="text-indigo-300 font-outfit font-bold bg-indigo-500/10 py-1 px-2.5 rounded-lg border border-indigo-500/25"><?= $persentase ?>% (<?= htmlspecialchars($row['total_suara']) ?> Suara)</span>
                        </div>
                        <div class="w-full h-8 bg-slate-950/80 border border-white/5 rounded-xl overflow-hidden relative flex items-center p-1">
                            <div class="h-full bg-gradient-to-r from-indigo-600 to-indigo-400 rounded-lg shadow-[0_0_15px_rgba(99,102,241,0.3)] transition-all duration-1000" style="width: <?= $persentase ?>%;"></div>
                        </div>
                    </div>
                <?php 
                    endwhile;
                endif; 
                ?>
            </div>
        </div>
    </main>
</body>

</html>
