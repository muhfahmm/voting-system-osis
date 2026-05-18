<?php
session_start();
require '../../../db/db.php';

if (!isset($_SESSION['login'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$admin = $_SESSION['username'];

// Ambil data target kelas & jumlah siswa
$dataKelas = [];
$resultKelas = mysqli_query($db, "SELECT nama_kelas, jumlah_siswa FROM tb_kelas ORDER BY nama_kelas ASC");
while ($row = mysqli_fetch_assoc($resultKelas)) {
    $dataKelas[$row['nama_kelas']] = (int)$row['jumlah_siswa'];
}
$total_siswa = array_sum($dataKelas);

// Ambil kueri kandidat dan jumlah suara real-time
$query = mysqli_query($db, "
    SELECT k.nomor_kandidat, k.nama_ketua, k.nama_wakil, k.foto_ketua, k.foto_wakil, COUNT(v.id) AS total_suara
    FROM tb_kandidat k
    LEFT JOIN tb_vote_log v ON k.nomor_kandidat = v.nomor_kandidat
    GROUP BY k.nomor_kandidat, k.nama_ketua, k.nama_wakil
    ORDER BY k.nomor_kandidat ASC
");

// Cari Pemimpin Perolehan Suara Terbanyak (Leader)
$leaderQuery = mysqli_query($db, "
    SELECT k.nomor_kandidat, k.nama_ketua, k.nama_wakil, k.foto_ketua, k.foto_wakil, COUNT(v.id) AS total_suara
    FROM tb_kandidat k
    LEFT JOIN tb_vote_log v ON k.nomor_kandidat = v.nomor_kandidat
    GROUP BY k.nomor_kandidat, k.nama_ketua, k.nama_wakil
    ORDER BY total_suara DESC, k.nomor_kandidat ASC
    LIMIT 1
");
$leader = mysqli_fetch_assoc($leaderQuery);

// Ambil data untuk Chart.js
$labels = [];
$dataVotes = [];
while ($row = mysqli_fetch_assoc($query)) {
    $labels[] = "Paslon #" . $row['nomor_kandidat'] . " (" . $row['nama_ketua'] . ")";
    $dataVotes[] = (int)$row['total_suara'];
}

mysqli_data_seek($query, 0);

// Hitung total pemilih masuk per role
$totalVotesSiswaQuery = mysqli_query($db, "
    SELECT COUNT(DISTINCT v.id) AS total 
    FROM tb_voter v
    JOIN tb_vote_log l ON v.id = l.voter_id
    WHERE v.role = 'siswa'
");
$totalVotesSiswa = (int)mysqli_fetch_assoc($totalVotesSiswaQuery)['total'];

$totalVotesGuruQuery = mysqli_query($db, "
    SELECT COUNT(DISTINCT v.id) AS total 
    FROM tb_voter v
    JOIN tb_vote_log l ON v.id = l.voter_id
    WHERE v.role = 'guru'
");
$totalVotesGuru = (int)mysqli_fetch_assoc($totalVotesGuruQuery)['total'];

$totalQuery = mysqli_query($db, "SELECT COUNT(*) AS total FROM tb_vote_log");
$totalVotes = (int)mysqli_fetch_assoc($totalQuery)['total'];

$totalSiswaTarget = $total_siswa;

// total guru dari tb_kode_guru
$guruResult = mysqli_query($db, "SELECT COUNT(*) AS total_guru FROM tb_kode_guru");
$totalGuruTarget = (int)mysqli_fetch_assoc($guruResult)['total_guru'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Sementara Premium - Voting OSIS</title>
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
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-[#0b0f19] text-[#f1f5f9] min-h-screen flex font-sans relative overflow-x-hidden">
    <!-- Ambient Glow Backdrops (Enhanced) -->
    <div class="absolute top-[-10%] left-[-10%] w-[600px] h-[600px] bg-indigo-500/10 rounded-full blur-[160px] pointer-events-none z-0"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[600px] h-[600px] bg-purple-500/10 rounded-full blur-[160px] pointer-events-none z-0"></div>
    <div class="absolute top-[30%] right-[20%] w-[500px] h-[500px] bg-pink-500/5 rounded-full blur-[150px] pointer-events-none z-0"></div>

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
                <a href="result.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl bg-indigo-600/10 text-indigo-300 border-l-4 border-indigo-500 font-semibold group">
                    <i class="bi bi-bar-chart-line text-lg text-indigo-400"></i>
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
    <main class="flex-1 p-8 lg:p-12 z-10 flex flex-col gap-10 w-full">
        <!-- Top bar / Welcome -->
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-white/5 pb-6">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                    <span class="text-xs font-bold text-emerald-400 tracking-widest uppercase">LIVE ELECTION RESULTS</span>
                </div>
                <h1 class="font-outfit text-4xl lg:text-5xl font-extrabold bg-gradient-to-r from-white via-indigo-100 to-indigo-300 bg-clip-text text-transparent mt-2">Dinding Hasil Pemilihan</h1>
                <p class="text-slate-400 text-sm mt-1.5">Tampilan data statistik voting interaktif berskala besar & real-time</p>
            </div>
        </header>

        <!-- LEADERS HERO FEATURE DISPLAY (Spectacular!) -->
        <?php if ($leader && $leader['total_suara'] > 0): ?>
            <?php 
            $leaderPercent = $totalVotes > 0 ? round(($leader['total_suara'] / $totalVotes) * 100, 1) : 0;
            ?>
            <section class="bg-gradient-to-r from-indigo-900/30 via-purple-900/20 to-slate-900/40 backdrop-blur-md border border-indigo-500/10 rounded-[32px] p-8 lg:p-10 shadow-2xl relative overflow-hidden flex flex-col lg:flex-row items-center justify-between gap-8 group">
                <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-500/10 rounded-full blur-[120px] pointer-events-none"></div>
                <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-purple-500/5 rounded-full blur-[100px] pointer-events-none"></div>

                <!-- Left side: Visual -->
                <div class="flex flex-col sm:flex-row items-center gap-6 z-10">
                    <div class="flex gap-3 relative shrink-0">
                        <img src="../../uploads/<?= $leader['foto_ketua']; ?>" alt="Foto Ketua Terunggul" class="w-24 h-32 sm:w-28 sm:h-38 object-cover rounded-2xl border-2 border-indigo-500/40 shadow-xl group-hover:scale-105 transition-transform duration-500">
                        <img src="../../uploads/<?= $leader['foto_wakil']; ?>" alt="Foto Wakil Terunggul" class="w-24 h-32 sm:w-28 sm:h-38 object-cover rounded-2xl border-2 border-indigo-500/40 shadow-xl group-hover:scale-105 transition-transform duration-500">
                        <span class="absolute -top-3 -right-3 w-10 h-10 bg-indigo-500 border border-indigo-400 text-white rounded-full flex items-center justify-center font-outfit font-extrabold text-sm shadow-[0_0_15px_rgba(99,102,241,0.5)]">
                            #<?= $leader['nomor_kandidat']; ?>
                        </span>
                    </div>

                    <div class="text-center sm:text-left flex flex-col gap-2">
                        <div class="inline-flex mx-auto sm:mx-0 items-center gap-1.5 py-1 px-3.5 rounded-full bg-indigo-500/15 border border-indigo-500/30 text-indigo-300 text-xs font-bold uppercase tracking-wider w-fit">
                            <i class="bi bi-award-fill text-indigo-400"></i> Unggul Sementara
                        </div>
                        <h2 class="font-outfit text-2xl sm:text-3xl font-black text-white leading-tight">
                            <?= htmlspecialchars($leader['nama_ketua']) ?> & <?= htmlspecialchars($leader['nama_wakil']) ?>
                        </h2>
                        <p class="text-slate-400 text-xs sm:text-sm">Pasangan Calon Nomor Urut <?= $leader['nomor_kandidat'] ?></p>
                    </div>
                </div>

                <!-- Right side: Big Numbers -->
                <div class="flex items-center gap-8 shrink-0 z-10">
                    <div class="text-center bg-slate-950/40 border border-white/5 py-4 px-6 rounded-2xl min-w-[120px]">
                        <p class="text-[10px] text-slate-400 font-bold tracking-widest uppercase">JUMLAH SUARA</p>
                        <p class="text-3xl sm:text-4xl font-outfit font-black text-white mt-1"><?= $leader['total_suara'] ?></p>
                    </div>
                    <div class="text-center bg-indigo-500/10 border border-indigo-500/20 py-4 px-6 rounded-2xl min-w-[120px]">
                        <p class="text-[10px] text-indigo-300 font-bold tracking-widest uppercase">PERSENTASE</p>
                        <p class="text-3xl sm:text-4xl font-outfit font-black text-indigo-300 mt-1"><?= $leaderPercent ?>%</p>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- Massive Vote Logs Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Total Masuk -->
            <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-[28px] p-6 shadow-2xl flex flex-col gap-4 relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 w-28 h-28 bg-indigo-500/5 rounded-full group-hover:scale-125 transition-transform duration-500"></div>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-indigo-500/10 border border-indigo-500/20 rounded-2xl flex items-center justify-center text-indigo-400 text-xl">
                        <i class="bi bi-box2-heart"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Total Suara Masuk</p>
                        <p class="text-3xl font-outfit font-extrabold mt-1 text-white"><?= $totalVotes ?> <span class="text-xs text-slate-400 font-sans font-medium">Suara Sah</span></p>
                    </div>
                </div>
            </div>

            <!-- Siswa Card -->
            <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-[28px] p-6 shadow-2xl flex flex-col gap-4 relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 w-28 h-28 bg-indigo-500/5 rounded-full group-hover:scale-125 transition-transform duration-500"></div>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-indigo-500/10 border border-indigo-500/20 rounded-2xl flex items-center justify-center text-indigo-400 text-xl">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Partisipasi Voting Siswa</p>
                        <p class="text-3xl font-outfit font-extrabold mt-1 text-white"><?= $totalVotesSiswa ?> <span class="text-xs text-slate-400 font-sans font-medium">dari <?= $totalSiswaTarget ?> Siswa</span></p>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <div class="flex justify-between text-xs font-semibold text-slate-400">
                        <span>Belum memilih: <?= max(0, $totalSiswaTarget - $totalVotesSiswa) ?></span>
                        <span><?= $totalSiswaTarget > 0 ? round(($totalVotesSiswa / $totalSiswaTarget) * 100, 1) : 0 ?>%</span>
                    </div>
                    <div class="w-full h-2.5 bg-slate-950 rounded-full overflow-hidden p-0.5 border border-white/5">
                        <div class="h-full bg-indigo-500 rounded-full shadow-[0_0_10px_rgba(99,102,241,0.4)]" style="width: <?= $totalSiswaTarget > 0 ? ($totalVotesSiswa / $totalSiswaTarget) * 100 : 0 ?>%;"></div>
                    </div>
                </div>
            </div>

            <!-- Guru Card -->
            <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-[28px] p-6 shadow-2xl flex flex-col gap-4 relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 w-28 h-28 bg-purple-500/5 rounded-full group-hover:scale-125 transition-transform duration-500"></div>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-purple-500/10 border border-purple-500/20 rounded-2xl flex items-center justify-center text-purple-400 text-xl">
                        <i class="bi bi-mortarboard"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Partisipasi Voting Guru / Staff</p>
                        <p class="text-3xl font-outfit font-extrabold mt-1 text-white"><?= $totalVotesGuru ?> <span class="text-xs text-slate-400 font-sans font-medium">dari <?= $totalGuruTarget ?> Guru</span></p>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <div class="flex justify-between text-xs font-semibold text-slate-400">
                        <span>Belum memilih: <?= max(0, $totalGuruTarget - $totalVotesGuru) ?></span>
                        <span><?= $totalGuruTarget > 0 ? round(($totalVotesGuru / $totalGuruTarget) * 100, 1) : 0 ?>%</span>
                    </div>
                    <div class="w-full h-2.5 bg-slate-950 rounded-full overflow-hidden p-0.5 border border-white/5">
                        <div class="h-full bg-purple-500 rounded-full shadow-[0_0_10px_rgba(168,85,247,0.4)]" style="width: <?= $totalGuruTarget > 0 ? ($totalVotesGuru / $totalGuruTarget) * 100 : 0 ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Huge Charts Area (Highly Immersive and Large) -->
        <div class="grid grid-cols-1 xl:grid-cols-5 gap-8">
            <!-- Pie Chart Card (xl:col-span-2) -->
            <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-[32px] shadow-2xl p-8 flex flex-col gap-6 items-center xl:col-span-2">
                <h3 class="font-outfit text-xl font-bold text-slate-200 self-start">Proporsi Persentase Suara</h3>
                <div class="w-full max-w-[380px] aspect-square flex items-center justify-center mt-4">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>

            <!-- Bar Chart Card (xl:col-span-3) -->
            <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-[32px] shadow-2xl p-8 flex flex-col gap-6 xl:col-span-3">
                <h3 class="font-outfit text-xl font-bold text-slate-200">Perolehan Suara Paslon</h3>
                <div class="w-full h-full min-h-[380px] flex items-center justify-center mt-4">
                    <canvas id="barChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Standings Grafik Batang Rinci (Extra High & Gorgeous) -->
        <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-[32px] shadow-2xl p-8 flex flex-col gap-8">
            <div>
                <h2 class="font-outfit text-2xl font-extrabold text-slate-200 flex items-center gap-3">
                    <i class="bi bi-bar-chart-steps text-indigo-400"></i>
                    <span>Tabel Perolehan Rinci & Grafik Batang Glowing</span>
                </h2>
                <p class="text-xs text-slate-400 mt-1">Perolehan suara riil beserta status persentase akurat untuk setiap pasangan calon</p>
            </div>

            <div class="flex flex-col gap-6">
                <?php
                if (mysqli_num_rows($query) > 0):
                    mysqli_data_seek($query, 0);
                    while ($row = mysqli_fetch_assoc($query)):
                        $persentase = $totalVotes > 0 ? round(($row['total_suara'] / $totalVotes) * 100, 2) : 0;
                ?>
                    <div class="flex flex-col gap-3">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
                            <!-- Candidate Names -->
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-xl bg-indigo-600/20 border border-indigo-500/35 text-indigo-400 flex items-center justify-center font-outfit font-extrabold text-sm">
                                    <?= htmlspecialchars($row['nomor_kandidat']) ?>
                                </span>
                                <span class="text-slate-200 font-outfit font-extrabold text-sm sm:text-base">
                                    <?= htmlspecialchars($row['nama_ketua']) ?> & <?= htmlspecialchars($row['nama_wakil']) ?>
                                </span>
                            </div>

                            <!-- Score details -->
                            <div class="flex items-center gap-3 self-end sm:self-auto">
                                <span class="text-xs text-slate-400 font-semibold font-sans uppercase">Total: <?= htmlspecialchars($row['total_suara']) ?> Suara</span>
                                <span class="text-indigo-300 font-outfit font-black text-sm bg-indigo-500/10 py-1.5 px-3.5 rounded-xl border border-indigo-500/25 shadow-lg">
                                    <?= $persentase ?>%
                                </span>
                            </div>
                        </div>

                        <!-- Progress Bar Fill (Taller & Glowing) -->
                        <div class="w-full h-11 bg-slate-950/80 border border-white/5 rounded-2xl overflow-hidden relative flex items-center p-1.5">
                            <div class="h-full bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 rounded-xl shadow-[0_0_20px_rgba(99,102,241,0.45)] transition-all duration-[1200ms] ease-out flex items-center justify-end px-4 min-w-[20px]" style="width: <?= $persentase ?>%;">
                                <?php if ($persentase >= 10): ?>
                                    <span class="text-[10px] sm:text-xs font-outfit font-black text-white bg-slate-900/60 backdrop-blur-sm py-0.5 px-2 rounded-md border border-white/10 uppercase tracking-widest shadow-lg">
                                        <?= $persentase ?>%
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php 
                    endwhile;
                endif; 
                ?>
            </div>
        </div>
    </main>

    <script>
        const labels = <?= json_encode($labels); ?>;
        const dataVotes = <?= json_encode($dataVotes); ?>;

        // Configure Chart.js global dark theme settings (Spacious & Clean)
        Chart.defaults.color = '#94a3b8'; // Slate 400
        Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.04)';
        Chart.defaults.font.family = 'Plus Jakarta Sans, system-ui, sans-serif';
        Chart.defaults.font.weight = 600;

        // Pie Chart
        new Chart(document.getElementById('pieChart'), {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: dataVotes,
                    backgroundColor: ['#6366f1', '#a855f7', '#ec4899', '#3b82f6', '#eab308'],
                    borderWidth: 3,
                    borderColor: '#111827'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                size: 12,
                                weight: 700
                            }
                        }
                    }
                }
            }
        });

        // Bar Chart
        new Chart(document.getElementById('barChart'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Suara',
                    data: dataVotes,
                    backgroundColor: ['#6366f1', '#a855f7', '#ec4899', '#3b82f6', '#eab308'],
                    borderRadius: 14,
                    borderWidth: 0,
                    barPercentage: 0.55
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.04)'
                        },
                        ticks: {
                            precision: 0,
                            font: {
                                size: 11
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 11,
                                weight: 700
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>

</html>
