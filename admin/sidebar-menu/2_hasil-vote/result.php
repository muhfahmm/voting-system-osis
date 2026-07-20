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
$resultForChart = mysqli_query($db, "
    SELECT k.nomor_kandidat, k.nama_ketua, COUNT(v.id) AS total_suara
    FROM tb_kandidat k
    LEFT JOIN tb_vote_log v ON k.nomor_kandidat = v.nomor_kandidat
    GROUP BY k.nomor_kandidat, k.nama_ketua
    ORDER BY k.nomor_kandidat ASC
");
while ($row = mysqli_fetch_assoc($resultForChart)) {
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

<body class="bg-[#f8fafc] text-slate-800 min-h-screen flex font-sans relative overflow-x-hidden">
    <!-- Ambient Glow Backdrops (Enhanced) -->
    <div class="absolute top-[-10%] left-[-10%] w-[600px] h-[600px] bg-indigo-200/70 rounded-full blur-[160px] pointer-events-none z-0"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[600px] h-[600px] bg-purple-200/70 rounded-full blur-[160px] pointer-events-none z-0"></div>

    <!-- Sidebar Navigation -->
    <aside class="w-72 bg-white/80 backdrop-blur-xl border-r border-slate-200 shadow-sm flex flex-col justify-between shrink-0 min-h-screen z-10">
        <div class="flex flex-col gap-8 p-6">
            <!-- Brand / Header -->
            <div class="flex items-center gap-3 border-b border-slate-200 pb-6">
                <div class="w-10 h-10 bg-indigo-100 border border-indigo-200 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="font-outfit font-extrabold text-lg text-slate-900">Admin Panel</h2>
                    <p class="text-xs text-slate-500 font-semibold tracking-wide">E-VOTING SKALSA</p>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex flex-col gap-1.5">
                <a href="../1_dashboard/dashboard.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-slate-600 font-medium transition-all duration-300 hover:bg-slate-100 hover:text-slate-900 group">
                    <i class="bi bi-speedometer2 text-lg group-hover:text-indigo-500 transition-colors"></i>
                    <span>Dashboard</span>
                </a>
                <a href="result.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl bg-indigo-50 text-indigo-700 border-l-4 border-indigo-500 font-semibold group">
                    <i class="bi bi-bar-chart-line text-lg text-indigo-500"></i>
                    <span>Hasil Vote</span>
                </a>
                <a href="../3_kandidat/daftar-kandidat.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-slate-600 font-medium transition-all duration-300 hover:bg-slate-100 hover:text-slate-900 group">
                    <i class="bi bi-people text-lg group-hover:text-indigo-500 transition-colors"></i>
                    <span>Daftar Kandidat</span>
                </a>
                <a href="../4_daftar-voter/daftar-voter.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-slate-600 font-medium transition-all duration-300 hover:bg-slate-100 hover:text-slate-900 group">
                    <i class="bi bi-card-checklist text-lg group-hover:text-indigo-500 transition-colors"></i>
                    <span>Daftar Voter</span>
                </a>
                <a href="../5_token-siswa/token-siswa.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-slate-600 font-medium transition-all duration-300 hover:bg-slate-100 hover:text-slate-900 group">
                    <i class="bi bi-key text-lg group-hover:text-indigo-500 transition-colors"></i>
                    <span>Token Siswa</span>
                </a>
                <a href="../6_token-guru/token-guru.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-slate-600 font-medium transition-all duration-300 hover:bg-slate-100 hover:text-slate-900 group">
                    <i class="bi bi-shield-lock text-lg group-hover:text-indigo-500 transition-colors"></i>
                    <span>Token Guru</span>
                </a>
                <a href="../7_daftar-admin/daftar-admin.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-slate-600 font-medium transition-all duration-300 hover:bg-slate-100 hover:text-slate-900 group">
                    <i class="bi bi-person-workspace text-lg group-hover:text-indigo-500 transition-colors"></i>
                    <span>Daftar Admin</span>
                </a>
            </nav>
        </div>

        <!-- User / Logout -->
        <div class="p-6 border-t border-slate-200 bg-slate-50 flex flex-col gap-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-white border border-slate-200 rounded-lg flex items-center justify-center text-slate-700 font-outfit font-bold text-sm">
                    <?= strtoupper(substr($admin, 0, 2)) ?>
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Logged In As</p>
                    <p class="text-sm text-slate-700 font-bold truncate"><?= htmlspecialchars($admin) ?></p>
                </div>
            </div>
            <a href="../../auth/logout.php" class="flex items-center justify-center gap-2.5 w-full py-3 rounded-xl bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 font-bold text-sm transition-all duration-300">
                <i class="bi bi-box-arrow-right"></i>
                <span>Keluar (Logout)</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 p-8 lg:p-12 z-10 flex flex-col gap-10 w-full">
        <!-- Top bar / Welcome -->
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-6">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                    <span class="text-xs font-bold text-emerald-600 tracking-widest uppercase">LIVE ELECTION RESULTS</span>
                </div>
                <h1 class="font-outfit text-4xl lg:text-5xl font-extrabold text-slate-900 mt-2">Hasil Pemilihan</h1>
            </div>
        </header>

        <!-- LEADERS HERO FEATURE DISPLAY -->
        <?php if ($leader && $leader['total_suara'] > 0): ?>
            <?php 
            $leaderPercent = $totalVotes > 0 ? round(($leader['total_suara'] / $totalVotes) * 100, 1) : 0;
            ?>
            <section class="bg-white/90 backdrop-blur-md border border-indigo-100 rounded-[32px] p-8 lg:p-10 shadow-sm relative overflow-hidden flex flex-col lg:flex-row items-center justify-between gap-8 group">
                <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-100 rounded-full blur-[120px] pointer-events-none"></div>
                <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-purple-100 rounded-full blur-[100px] pointer-events-none"></div>

                <!-- Left side: Visual -->
                <div class="flex flex-col sm:flex-row items-center gap-6 z-10">
                    <div class="flex gap-3 relative shrink-0">
                        <img src="../../uploads/<?= $leader['foto_ketua']; ?>" alt="Foto Ketua Terunggul" class="w-24 h-32 sm:w-28 sm:h-38 object-cover rounded-2xl border-2 border-indigo-200 shadow-md group-hover:scale-105 transition-transform duration-500">
                        <img src="../../uploads/<?= $leader['foto_wakil']; ?>" alt="Foto Wakil Terunggul" class="w-24 h-32 sm:w-28 sm:h-38 object-cover rounded-2xl border-2 border-indigo-200 shadow-md group-hover:scale-105 transition-transform duration-500">
                        <span class="absolute -top-3 -right-3 w-10 h-10 bg-indigo-500 border border-indigo-400 text-white rounded-full flex items-center justify-center font-outfit font-extrabold text-sm shadow-md">
                            #<?= $leader['nomor_kandidat']; ?>
                        </span>
                    </div>

                    <div class="text-center sm:text-left flex flex-col gap-2">
                        <div class="inline-flex mx-auto sm:mx-0 items-center gap-1.5 py-1 px-3.5 rounded-full bg-indigo-100 border border-indigo-200 text-indigo-700 text-xs font-bold uppercase tracking-wider w-fit">
                            <i class="bi bi-award-fill text-indigo-500"></i> Unggul Sementara
                        </div>
                        <h2 class="font-outfit text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
                            <?= htmlspecialchars($leader['nama_ketua']) ?> & <?= htmlspecialchars($leader['nama_wakil']) ?>
                        </h2>
                        <p class="text-slate-500 text-xs sm:text-sm">Pasangan Calon Nomor Urut <?= $leader['nomor_kandidat'] ?></p>
                    </div>
                </div>

                <!-- Right side: Big Numbers -->
                <div class="flex items-center gap-8 shrink-0 z-10">
                    <div class="text-center bg-white border border-slate-200 py-4 px-6 rounded-2xl min-w-[120px] shadow-sm">
                        <p class="text-[10px] text-slate-500 font-bold tracking-widest uppercase">JUMLAH SUARA</p>
                        <p class="text-3xl sm:text-4xl font-outfit font-black text-slate-900 mt-1"><?= $leader['total_suara'] ?></p>
                    </div>
                    <div class="text-center bg-indigo-50 border border-indigo-200 py-4 px-6 rounded-2xl min-w-[120px]">
                        <p class="text-[10px] text-indigo-600 font-bold tracking-widest uppercase">PERSENTASE</p>
                        <p class="text-3xl sm:text-4xl font-outfit font-black text-indigo-600 mt-1"><?= $leaderPercent ?>%</p>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- Massive Vote Logs Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Total Masuk -->
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-[28px] p-6 shadow-sm flex flex-col gap-4 relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 w-28 h-28 bg-indigo-100 rounded-full group-hover:scale-125 transition-transform duration-500"></div>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-indigo-100 border border-indigo-200 rounded-2xl flex items-center justify-center text-indigo-600 text-xl">
                        <i class="bi bi-box2-heart"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Total Suara Masuk</p>
                        <p class="text-3xl font-outfit font-extrabold mt-1 text-slate-900"><?= $totalVotes ?> <span class="text-xs text-slate-500 font-sans font-medium">Suara Sah</span></p>
                    </div>
                </div>
            </div>

            <!-- Siswa Card -->
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-[28px] p-6 shadow-sm flex flex-col gap-4 relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 w-28 h-28 bg-indigo-100 rounded-full group-hover:scale-125 transition-transform duration-500"></div>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-indigo-100 border border-indigo-200 rounded-2xl flex items-center justify-center text-indigo-600 text-xl">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Partisipasi Voting Siswa</p>
                        <p class="text-3xl font-outfit font-extrabold mt-1 text-slate-900"><?= $totalVotesSiswa ?> <span class="text-xs text-slate-500 font-sans font-medium">dari <?= $totalSiswaTarget ?> Siswa</span></p>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <div class="flex justify-between text-xs font-semibold text-slate-500">
                        <span>Belum memilih: <?= max(0, $totalSiswaTarget - $totalVotesSiswa) ?></span>
                        <span><?= $totalSiswaTarget > 0 ? round(($totalVotesSiswa / $totalSiswaTarget) * 100, 1) : 0 ?>%</span>
                    </div>
                    <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden p-0.5 border border-slate-200">
                        <div class="h-full bg-indigo-500 rounded-full" style="width: <?= $totalSiswaTarget > 0 ? ($totalVotesSiswa / $totalSiswaTarget) * 100 : 0 ?>%;"></div>
                    </div>
                </div>
            </div>

            <!-- Guru Card -->
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-[28px] p-6 shadow-sm flex flex-col gap-4 relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 w-28 h-28 bg-purple-100 rounded-full group-hover:scale-125 transition-transform duration-500"></div>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-purple-100 border border-purple-200 rounded-2xl flex items-center justify-center text-purple-600 text-xl">
                        <i class="bi bi-mortarboard"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Partisipasi Voting Guru / Staff</p>
                        <p class="text-3xl font-outfit font-extrabold mt-1 text-slate-900"><?= $totalVotesGuru ?> <span class="text-xs text-slate-500 font-sans font-medium">dari <?= $totalGuruTarget ?> Guru</span></p>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <div class="flex justify-between text-xs font-semibold text-slate-500">
                        <span>Belum memilih: <?= max(0, $totalGuruTarget - $totalVotesGuru) ?></span>
                        <span><?= $totalGuruTarget > 0 ? round(($totalVotesGuru / $totalGuruTarget) * 100, 1) : 0 ?>%</span>
                    </div>
                    <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden p-0.5 border border-slate-200">
                        <div class="h-full bg-purple-500 rounded-full" style="width: <?= $totalGuruTarget > 0 ? ($totalVotesGuru / $totalGuruTarget) * 100 : 0 ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Huge Charts Area -->
        <div class="grid grid-cols-1 xl:grid-cols-5 gap-8">
            <!-- Pie Chart Card -->
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-[32px] shadow-sm p-8 flex flex-col gap-6 items-center xl:col-span-2">
                <h3 class="font-outfit text-xl font-bold text-slate-800 self-start">Proporsi Persentase Suara</h3>
                <div class="w-full max-w-[380px] aspect-square flex items-center justify-center mt-4">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>

            <!-- Bar Chart Card -->
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-[32px] shadow-sm p-8 flex flex-col gap-6 xl:col-span-3">
                <h3 class="font-outfit text-xl font-bold text-slate-800">Perolehan Suara Paslon</h3>
                <div class="w-full h-full min-h-[380px] flex items-center justify-center mt-4">
                    <canvas id="barChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Standings Grafik Batang Rinci -->
        <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-[32px] shadow-sm p-8 flex flex-col gap-8">
            <div>
                <h2 class="font-outfit text-2xl font-extrabold text-slate-800 flex items-center gap-3">
                    <i class="bi bi-bar-chart-steps text-indigo-500"></i>
                    <span>Tabel Perolehan Rinci & Grafik Batang</span>
                </h2>
                <p class="text-xs text-slate-500 mt-1">Perolehan suara riil beserta status persentase akurat untuk setiap pasangan calon</p>
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
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-xl bg-indigo-100 border border-indigo-200 text-indigo-600 flex items-center justify-center font-outfit font-extrabold text-sm">
                                    <?= htmlspecialchars($row['nomor_kandidat']) ?>
                                </span>
                                <span class="text-slate-700 font-outfit font-extrabold text-sm sm:text-base">
                                    <?= htmlspecialchars($row['nama_ketua']) ?> & <?= htmlspecialchars($row['nama_wakil']) ?>
                                </span>
                            </div>

                            <div class="flex items-center gap-3 self-end sm:self-auto">
                                <span class="text-xs text-slate-500 font-semibold font-sans uppercase">Total: <?= htmlspecialchars($row['total_suara']) ?> Suara</span>
                                <span class="text-indigo-600 font-outfit font-black text-sm bg-indigo-50 py-1.5 px-3.5 rounded-xl border border-indigo-200">
                                    <?= $persentase ?>%
                                </span>
                            </div>
                        </div>

                        <div class="w-full h-11 bg-slate-100 border border-slate-200 rounded-2xl overflow-hidden relative flex items-center p-1.5">
                            <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-xl transition-all duration-[1200ms] ease-out flex items-center justify-end px-4 min-w-[20px]" style="width: <?= $persentase ?>%;">
                                <?php if ($persentase >= 10): ?>
                                    <span class="text-[10px] sm:text-xs font-outfit font-black text-white bg-slate-900/60 backdrop-blur-sm py-0.5 px-2 rounded-md border border-white/10 uppercase tracking-widest">
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

        Chart.defaults.color = '#475569';
        Chart.defaults.borderColor = 'rgba(0, 0, 0, 0.08)';
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
                    borderColor: '#ffffff'
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
                            color: 'rgba(0, 0, 0, 0.06)'
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