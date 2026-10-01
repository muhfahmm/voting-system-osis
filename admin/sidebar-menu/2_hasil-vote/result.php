<?php
session_start();
require '../../../db/db.php';

if (!isset($_SESSION['login'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$admin = $_SESSION['username'];


$dataKelas = [];
$resultKelas = mysqli_query($db, "SELECT nama_kelas, jumlah_siswa FROM tb_kelas ORDER BY nama_kelas ASC");
while ($row = mysqli_fetch_assoc($resultKelas)) {
    $dataKelas[$row['nama_kelas']] = (int)$row['jumlah_siswa'];
}
$total_siswa = array_sum($dataKelas);


$query = mysqli_query($db, "
    SELECT k.nomor_kandidat, k.nama_ketua, k.nama_wakil, k.foto_ketua, k.foto_wakil, COUNT(v.id) AS total_suara
    FROM tb_kandidat k
    LEFT JOIN tb_vote_log v ON k.nomor_kandidat = v.nomor_kandidat
    GROUP BY k.nomor_kandidat, k.nama_ketua, k.nama_wakil
    ORDER BY k.nomor_kandidat ASC
");


$leaderQuery = mysqli_query($db, "
    SELECT k.nomor_kandidat, k.nama_ketua, k.nama_wakil, k.foto_ketua, k.foto_wakil, COUNT(v.id) AS total_suara
    FROM tb_kandidat k
    LEFT JOIN tb_vote_log v ON k.nomor_kandidat = v.nomor_kandidat
    GROUP BY k.nomor_kandidat, k.nama_ketua, k.nama_wakil
    ORDER BY total_suara DESC, k.nomor_kandidat ASC
    LIMIT 1
");
$leader = mysqli_fetch_assoc($leaderQuery);


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


$guruResult = mysqli_query($db, "SELECT COUNT(*) AS total_guru FROM tb_kode_guru");
$totalGuruTarget = (int)mysqli_fetch_assoc($guruResult)['total_guru'];


$totalPartisipasi = $totalSiswaTarget + $totalGuruTarget;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Sementara Premium - Voting OSIS</title>
    <link rel="icon" href="../../assets/img/logo osis.png">
    
    
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
    
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a href="../1_dashboard/dashboard.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-600 font-medium transition-all duration-300 group">
                    <i class="bi bi-speedometer2 text-lg group-transition-colors"></i>
                    <span>Dashboard</span>
                </a>
                <a href="result.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg bg-emerald-50 text-emerald-700 border-l-4 border-emerald-500 font-semibold group">
                    <i class="bi bi-bar-chart-line text-lg text-emerald-500"></i>
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
                <a href="../7_daftar-admin/daftar-admin.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-600 font-medium transition-all duration-300 group">
                    <i class="bi bi-person-workspace text-lg group-transition-colors"></i>
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
            <a href="../../auth/logout.php" class="flex items-center justify-center gap-2.5 w-full py-2.5 rounded-lg bg-red-50 text-red-600 border border-red-200 font-bold text-sm transition-all duration-300">
                <i class="bi bi-box-arrow-right"></i>
                <span>Keluar (Logout)</span>
            </a>
        </div>
    </aside>

    
    <main class="flex-1 p-5 md:p-8 z-10 flex flex-col gap-4 w-full lg:ml-64">
        
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-6">
            <div class="flex items-center gap-3">
                <button type="button" onclick="toggleSidebar()" class="lg:hidden p-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 font-bold text-lg shadow-sm hover:bg-slate-50 flex items-center justify-center">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="font-sans text-2xl md:text-3xl font-bold text-slate-900">Hasil Pemilihan</h1>
                </div>
            </div>
        </header>

        
        <?php if ($leader && $leader['total_suara'] > 0): ?>
            <?php 
            $leaderPercent = $totalVotes > 0 ? round(($leader['total_suara'] / $totalVotes) * 100, 1) : 0;
            ?>
            <section class="bg-white/90 backdrop-blur-md border border-emerald-100 rounded-2xl p-4 sm:p-5 shadow-sm relative overflow-hidden flex flex-col lg:flex-row items-center justify-between gap-4 group">
                <div class="flex flex-col sm:flex-row items-center gap-3 sm:gap-4 z-10 w-full lg:w-auto">
                    <div class="flex gap-2 sm:gap-3 relative shrink-0">
                        <img src="../../uploads/<?= $leader['foto_ketua']; ?>" alt="Foto Ketua Terunggul" class="w-20 h-28 sm:w-28 sm:h-38 object-cover rounded-xl sm:rounded-2xl border-2 border-emerald-200 shadow-md">
                        <img src="../../uploads/<?= $leader['foto_wakil']; ?>" alt="Foto Wakil Terunggul" class="w-20 h-28 sm:w-28 sm:h-38 object-cover rounded-xl sm:rounded-2xl border-2 border-emerald-200 shadow-md">
                        <span class="absolute -top-2.5 -right-2.5 sm:-top-3 sm:-right-3 w-8 h-8 sm:w-10 sm:h-10 bg-emerald-500 border border-emerald-400 text-white rounded-full flex items-center justify-center font-sans font-extrabold text-xs sm:text-sm shadow-md">
                            #<?= $leader['nomor_kandidat']; ?>
                        </span>
                    </div>

                    <div class="text-center sm:text-left flex flex-col gap-1.5 sm:gap-2">
                        <div class="inline-flex mx-auto sm:mx-0 items-center gap-1.5 py-0.5 px-3 rounded-full bg-emerald-100 border border-emerald-200 text-emerald-700 text-[10px] sm:text-xs font-bold uppercase tracking-wider w-fit">
                            <i class="bi bi-award-fill text-emerald-500"></i> Unggul Sementara
                        </div>
                        <h2 class="font-sans text-xl sm:text-2xl md:text-3xl font-black text-slate-900 leading-tight">
                            <?= htmlspecialchars($leader['nama_ketua']) ?> & <?= htmlspecialchars($leader['nama_wakil']) ?>
                        </h2>
                        <p class="text-slate-500 text-xs sm:text-sm">Pasangan Calon Nomor Urut <?= $leader['nomor_kandidat'] ?></p>
                    </div>
                </div>

                <div class="flex items-center justify-center gap-3 sm:gap-4 shrink-0 z-10 w-full sm:w-auto">
                    <div class="flex-1 sm:flex-none text-center bg-white border border-slate-200 py-3 px-4 sm:py-4 sm:px-6 rounded-xl sm:rounded-2xl min-w-[100px] sm:min-w-[120px] shadow-sm">
                        <p class="text-[9px] sm:text-[10px] text-slate-500 font-bold tracking-widest uppercase">JUMLAH SUARA</p>
                        <p class="text-xl sm:text-2xl font-bold text-slate-900 mt-0.5 sm:mt-1"><?= $leader['total_suara'] ?></p>
                    </div>
                    <div class="flex-1 sm:flex-none text-center bg-emerald-50 border border-emerald-200 py-3 px-4 sm:py-4 sm:px-6 rounded-xl sm:rounded-2xl min-w-[100px] sm:min-w-[120px]">
                        <p class="text-[9px] sm:text-[10px] text-emerald-600 font-bold tracking-widest uppercase">PERSENTASE</p>
                        <p class="text-xl sm:text-2xl font-bold text-emerald-600 mt-0.5 sm:mt-1"><?= $leaderPercent ?>%</p>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl p-4 shadow-sm flex flex-col gap-4 relative overflow-hidden group">
                
                <div class="flex items-center gap-4">
                    <div class="w-9 h-9 bg-emerald-100 border border-emerald-200 rounded-2xl flex items-center justify-center text-emerald-600 text-xl">
                        <i class="bi bi-box2-heart"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Total Suara Masuk</p>
                        <p class="text-xl font-bold mt-1 text-slate-900">
                            <?= $totalVotes ?> 
                            <span class="text-xs text-slate-500 font-sans font-medium">Suara</span>
                        </p>
                        <p class="text-xs text-slate-500 mt-1 font-medium">
                            dari <?= $totalPartisipasi ?> partisipasi terdaftar
                        </p>
                    </div>
                </div>
            </div>

            
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl p-4 shadow-sm flex flex-col gap-4 relative overflow-hidden group">
                
                <div class="flex items-center gap-4">
                    <div class="w-9 h-9 bg-emerald-100 border border-emerald-200 rounded-2xl flex items-center justify-center text-emerald-600 text-xl">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Partisipasi Voting Siswa</p>
                        <p class="text-xl font-bold mt-1 text-slate-900"><?= $totalVotesSiswa ?> <span class="text-xs text-slate-500 font-sans font-medium">dari <?= $totalSiswaTarget ?> Siswa</span></p>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <div class="flex justify-between text-xs font-semibold text-slate-500">
                        <span>Belum memilih: <?= max(0, $totalSiswaTarget - $totalVotesSiswa) ?></span>
                        <span><?= $totalSiswaTarget > 0 ? round(($totalVotesSiswa / $totalSiswaTarget) * 100, 1) : 0 ?>%</span>
                    </div>
                    <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden p-0.5 border border-slate-200">
                        <div class="h-full bg-emerald-500 rounded-full" style="width: <?= $totalSiswaTarget > 0 ? ($totalVotesSiswa / $totalSiswaTarget) * 100 : 0 ?>%;"></div>
                    </div>
                </div>
            </div>

            
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl p-4 shadow-sm flex flex-col gap-4 relative overflow-hidden group">
                
                <div class="flex items-center gap-4">
                    <div class="w-9 h-9 bg-emerald-100 border border-emerald-200 rounded-2xl flex items-center justify-center text-emerald-600 text-xl">
                        <i class="bi bi-mortarboard"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Partisipasi Voting Guru / Staff</p>
                        <p class="text-xl font-bold mt-1 text-slate-900"><?= $totalVotesGuru ?> <span class="text-xs text-slate-500 font-sans font-medium">dari <?= $totalGuruTarget ?> Guru</span></p>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <div class="flex justify-between text-xs font-semibold text-slate-500">
                        <span>Belum memilih: <?= max(0, $totalGuruTarget - $totalVotesGuru) ?></span>
                        <span><?= $totalGuruTarget > 0 ? round(($totalVotesGuru / $totalGuruTarget) * 100, 1) : 0 ?>%</span>
                    </div>
                    <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden p-0.5 border border-slate-200">
                        <div class="h-full bg-emerald-500 rounded-full" style="width: <?= $totalGuruTarget > 0 ? ($totalVotesGuru / $totalGuruTarget) * 100 : 0 ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="grid grid-cols-1 xl:grid-cols-5 gap-4">
            <!-- Pie Chart Container -->
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl shadow-sm p-4 sm:p-6 md:p-8 flex flex-col gap-3 items-center xl:col-span-2">
                <h3 class="font-sans text-sm sm:text-base font-bold text-slate-800 self-start">Proporsi Persentase Suara</h3>
                <div class="w-full relative h-[250px] sm:h-[300px] md:h-[340px] flex items-center justify-center mt-2">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>

            <!-- Bar Chart Container -->
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl shadow-sm p-4 sm:p-6 md:p-8 flex flex-col gap-3 xl:col-span-3">
                <h3 class="font-sans text-sm sm:text-base font-bold text-slate-800">Perolehan Suara Paslon</h3>
                <div class="w-full relative h-[250px] sm:h-[300px] md:h-[340px] flex items-center justify-center mt-2">
                    <canvas id="barChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Tabel Perolehan Rinci -->
        <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl shadow-sm p-4 sm:p-6 md:p-8 flex flex-col gap-4">
            <div>
                <h2 class="font-sans text-sm sm:text-base font-bold text-slate-800 flex items-center gap-2.5">
                    <i class="bi bi-bar-chart-steps text-emerald-500"></i>
                    <span>Tabel Perolehan Rinci & Grafik Batang</span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Perolehan suara riil beserta status persentase akurat untuk setiap pasangan calon</p>
            </div>

            <div class="flex flex-col gap-4">
                <?php
                if (mysqli_num_rows($query) > 0):
                    mysqli_data_seek($query, 0);
                    while ($row = mysqli_fetch_assoc($query)):
                        $persentase = $totalVotes > 0 ? round(($row['total_suara'] / $totalVotes) * 100, 2) : 0;
                ?>
                    <div class="flex flex-col gap-2.5">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
                            <div class="flex items-center gap-2.5">
                                <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg sm:rounded-xl bg-emerald-100 border border-emerald-200 text-emerald-600 flex items-center justify-center font-sans font-extrabold text-xs sm:text-sm shrink-0">
                                    <?= htmlspecialchars($row['nomor_kandidat']) ?>
                                </span>
                                <span class="text-slate-800 font-sans font-bold text-xs sm:text-sm md:text-base leading-snug">
                                    <?= htmlspecialchars($row['nama_ketua']) ?> & <?= htmlspecialchars($row['nama_wakil']) ?>
                                </span>
                            </div>

                            <div class="flex items-center justify-between sm:justify-end gap-3 w-full sm:w-auto pt-1 sm:pt-0">
                                <span class="text-[11px] sm:text-xs text-slate-500 font-semibold font-sans uppercase">Total: <?= htmlspecialchars($row['total_suara']) ?> Suara</span>
                                <span class="text-emerald-600 font-sans font-black text-xs sm:text-sm bg-emerald-50 py-1 px-3 rounded-lg border border-emerald-200">
                                    <?= $persentase ?>%
                                </span>
                            </div>
                        </div>

                        <div class="w-full h-9 sm:h-11 bg-slate-100 border border-slate-200 rounded-xl sm:rounded-2xl overflow-hidden relative flex items-center p-1 sm:p-1.5">
                            <div class="h-full bg-emerald-600 rounded-lg sm:rounded-xl transition-all duration-[1200ms] ease-out flex items-center justify-end px-3 sm:px-4 min-w-[20px]" style="width: <?= $persentase ?>%;">
                                <?php if ($persentase >= 12): ?>
                                    <span class="text-[9px] sm:text-xs font-sans font-black text-white bg-slate-900/60 backdrop-blur-sm py-0.5 px-1.5 sm:px-2 rounded border border-white/10 uppercase tracking-wider">
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

        const isMobile = window.innerWidth < 640;

        Chart.defaults.color = '#475569';
        Chart.defaults.borderColor = 'rgba(0, 0, 0, 0.08)';
        Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
        Chart.defaults.font.weight = 600;

        new Chart(document.getElementById('pieChart'), {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: dataVotes,
                    backgroundColor: ['#10b981', '#a855f7', '#ec4899', '#3b82f6', '#eab308'],
                    borderWidth: 2,
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
                            padding: isMobile ? 10 : 20,
                            usePointStyle: true,
                            boxWidth: isMobile ? 8 : 10,
                            font: {
                                size: isMobile ? 10 : 12,
                                weight: 600
                            }
                        }
                    }
                }
            }
        });

        new Chart(document.getElementById('barChart'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Suara',
                    data: dataVotes,
                    backgroundColor: ['#10b981', '#a855f7', '#ec4899', '#3b82f6', '#eab308'],
                    borderRadius: isMobile ? 8 : 12,
                    borderWidth: 0,
                    barPercentage: isMobile ? 0.7 : 0.55
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
                                size: isMobile ? 10 : 11
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxRotation: isMobile ? 45 : 0,
                            minRotation: isMobile ? 45 : 0,
                            font: {
                                size: isMobile ? 9 : 11,
                                weight: 600
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







