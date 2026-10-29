<?php
session_start();
require '../../../db/db.php';

if (!isset($_SESSION['login'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$admin = $_SESSION['username'];

$limit = 15;

$pageSiswa = isset($_GET['page_siswa']) ? (int)$_GET['page_siswa'] : 1;
if ($pageSiswa < 1) $pageSiswa = 1;
$offsetSiswa = ($pageSiswa - 1) * $limit;

$pageGuru = isset($_GET['page_guru']) ? (int)$_GET['page_guru'] : 1;
if ($pageGuru < 1) $pageGuru = 1;
$offsetGuru = ($pageGuru - 1) * $limit;

$votersSiswa = [];
$votersGuru = [];

// Total Token Siswa dibuat (Target)
$totalSiswaTargetQuery = mysqli_query($db, "SELECT COUNT(*) as total FROM tb_buat_token");
$totalSiswaTargetRow = mysqli_fetch_assoc($totalSiswaTargetQuery);
$totalSiswaTarget = isset($totalSiswaTargetRow['total']) ? (int)$totalSiswaTargetRow['total'] : 0;

// Total Siswa yang sudah vote
$totalSiswaQuery = mysqli_query($db, "
    SELECT COUNT(*) as total
    FROM tb_voter v
    JOIN tb_vote_log l ON v.id = l.voter_id
    WHERE v.role = 'siswa'
");
$totalSiswaRow = mysqli_fetch_assoc($totalSiswaQuery);
$totalSiswa = isset($totalSiswaRow['total']) ? (int)$totalSiswaRow['total'] : 0;
$totalPagesSiswa = $totalSiswa > 0 ? ceil($totalSiswa / $limit) : 1;

$votedSiswaQuery = mysqli_query($db, "
    SELECT v.id, v.nama_voter, v.role, l.nomor_kandidat
    FROM tb_voter v
    JOIN tb_vote_log l ON v.id = l.voter_id
    WHERE v.role = 'siswa'
    ORDER BY v.id DESC
    LIMIT $limit OFFSET $offsetSiswa
");
while ($row = mysqli_fetch_assoc($votedSiswaQuery)) {
    $votersSiswa[] = $row;
}

// Total Token Guru dibuat (Target)
$totalGuruTargetQuery = mysqli_query($db, "SELECT COUNT(*) as total FROM tb_kode_guru");
$totalGuruTargetRow = mysqli_fetch_assoc($totalGuruTargetQuery);
$totalGuruTarget = isset($totalGuruTargetRow['total']) ? (int)$totalGuruTargetRow['total'] : 0;

// Total Guru yang sudah vote
$totalGuruVotedQuery = mysqli_query($db, "
    SELECT COUNT(DISTINCT v.id) as total
    FROM tb_voter v
    JOIN tb_vote_log l ON v.id = l.voter_id
    JOIN tb_kode_guru g ON v.nama_voter = g.kode
    WHERE v.role = 'guru'
");
$totalGuruRow = mysqli_fetch_assoc($totalGuruVotedQuery);
$totalGuru = isset($totalGuruRow['total']) ? (int)$totalGuruRow['total'] : 0;
$totalPagesGuru = $totalGuru > 0 ? ceil($totalGuru / $limit) : 1;

$votedGuru = $totalGuru;

$votedGuruQuery = mysqli_query($db, "
    SELECT v.id, v.nama_voter, v.role, l.nomor_kandidat
    FROM tb_voter v
    JOIN tb_vote_log l ON v.id = l.voter_id
    JOIN tb_kode_guru g ON v.nama_voter = g.kode
    WHERE v.role = 'guru'
    ORDER BY v.id DESC
    LIMIT $limit OFFSET $offsetGuru
");
while ($row = mysqli_fetch_assoc($votedGuruQuery)) {
    $votersGuru[] = $row;
}

// Ringkasan Suara Siswa per Paslon
$siswaSummary = [
    "total" => 0,
    "kandidat" => []
];

$qSiswa = mysqli_query($db, "
    SELECT l.nomor_kandidat, COUNT(*) as total_suara
    FROM tb_vote_log l
    JOIN tb_voter v ON l.voter_id = v.id
    WHERE v.role = 'siswa'
    GROUP BY l.nomor_kandidat
");

while ($row = mysqli_fetch_assoc($qSiswa)) {
    $nomor = $row['nomor_kandidat'];
    $jumlah = $row['total_suara'];
    $siswaSummary["kandidat"][$nomor] = $jumlah;
    $siswaSummary["total"] += $jumlah;
}

$percentSiswa = $totalSiswaTarget > 0 ? round(($totalSiswa / $totalSiswaTarget) * 100, 2) : 0;

// Ringkasan Suara Guru per Paslon
$guruSummary = [
    "total" => 0,
    "kandidat" => []
];

$qGuru = mysqli_query($db, "
    SELECT l.nomor_kandidat, COUNT(*) as total_suara
    FROM tb_vote_log l
    JOIN tb_voter v ON l.voter_id = v.id
    JOIN tb_kode_guru g ON v.nama_voter = g.kode
    WHERE v.role = 'guru'
    GROUP BY l.nomor_kandidat
");

while ($row = mysqli_fetch_assoc($qGuru)) {
    $nomor = $row['nomor_kandidat'];
    $jumlah = $row['total_suara'];
    $guruSummary["kandidat"][$nomor] = $jumlah;
    $guruSummary["total"] += $jumlah;
}

$percentGuru = $totalGuruTarget > 0 ? round(($votedGuru / $totalGuruTarget) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Voter - Voting OSIS</title>
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
    <style>input:focus, select:focus, textarea:focus, button:focus { outline: none !important; box-shadow: none !important; }</style>
</head>

<body class="bg-[#f8fafc] text-slate-800 min-h-screen flex font-sans relative overflow-x-hidden">
    
    <?php 
    $active = 'daftar_voter';
    $baseMenu = '../';
    require '../sidebar.php'; 
    ?>

    <main class="flex-1 p-5 md:p-8 z-10 flex flex-col gap-5 w-full lg:ml-64">
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-6">
            <div class="flex items-center gap-3">
                <button type="button" onclick="toggleSidebar()" class="lg:hidden p-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 font-bold text-lg shadow-sm hover:bg-slate-50 flex items-center justify-center">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="font-sans text-2xl font-bold text-slate-900">Daftar Voter Terdaftar</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Daftar pemilih yang telah menggunakan hak suaranya.</p>
                </div>
            </div>
        </header>

        <!-- Ringkasan Partisipasi Siswa & Guru -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Card Partisipasi Siswa -->
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl p-5 shadow-sm flex flex-col gap-4">
                <div class="flex justify-between items-start gap-4">
                    <div>
                        <h4 class="font-sans text-base font-extrabold text-slate-900 flex items-center gap-2">
                            <i class="bi bi-mortarboard text-emerald-500"></i>
                            <span>Partisipasi Voter Siswa</span>
                        </h4>
                        <p class="text-xs text-slate-500 mt-1 font-medium"><?= $totalSiswa ?> dari <?= $totalSiswaTarget ?> siswa telah memilih</p>
                    </div>
                    <span class="text-xs font-extrabold text-emerald-700 bg-emerald-100 px-2.5 py-1 rounded-lg border border-emerald-200"><?= $percentSiswa ?>%</span>
                </div>
                
                <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden p-0.5 border border-slate-200">
                    <div class="h-full bg-emerald-500 rounded-full transition-all duration-500" style="width: <?= $percentSiswa ?>%;"></div>
                </div>

                <div class="flex flex-col gap-2 mt-1">
                    <?php if (!empty($siswaSummary["kandidat"])): ?>
                        <?php foreach ($siswaSummary["kandidat"] as $nomor => $jumlah): ?>
                            <?php
                            $persen = $siswaSummary["total"] > 0
                                ? round(($jumlah / $siswaSummary["total"]) * 100, 2)
                                : 0;
                            ?>
                            <div class="flex flex-col gap-1">
                                <div class="flex justify-between text-xs font-semibold text-slate-500">
                                    <span>Paslon #<?= $nomor ?></span>
                                    <span class="text-slate-700 font-bold"><?= $jumlah ?> suara (<?= $persen ?>%)</span>
                                </div>
                                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-600 rounded-full" style="width: <?= $persen ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <i class="text-xs text-slate-400 italic block">Belum ada suara dari siswa.</i>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Card Partisipasi Guru -->
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl p-5 shadow-sm flex flex-col gap-4">
                <div class="flex justify-between items-start gap-4">
                    <div>
                        <h4 class="font-sans text-base font-extrabold text-slate-900 flex items-center gap-2">
                            <i class="bi bi-person-badge text-emerald-500"></i>
                            <span>Partisipasi Voter Guru</span>
                        </h4>
                        <p class="text-xs text-slate-500 font-medium mt-1"><?= $votedGuru ?> dari <?= $totalGuruTarget ?> guru telah memilih</p>
                    </div>
                    <span class="text-xs font-extrabold text-emerald-700 bg-emerald-100 px-2.5 py-1 rounded-lg border border-emerald-200"><?= $percentGuru ?>%</span>
                </div>
                
                <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden p-0.5 border border-slate-200">
                    <div class="h-full bg-emerald-500 rounded-full transition-all duration-500" style="width: <?= $percentGuru ?>%;"></div>
                </div>

                <div class="flex flex-col gap-2 mt-1">
                    <?php if (!empty($guruSummary["kandidat"])): ?>
                        <?php foreach ($guruSummary["kandidat"] as $nomor => $jumlah): ?>
                            <?php
                            $persen = $guruSummary["total"] > 0
                                ? round(($jumlah / $guruSummary["total"]) * 100, 2)
                                : 0;
                            ?>
                            <div class="flex flex-col gap-1">
                                <div class="flex justify-between text-xs font-semibold text-slate-500">
                                    <span>Paslon #<?= $nomor ?></span>
                                    <span class="text-slate-700 font-bold"><?= $jumlah ?> suara (<?= $persen ?>%)</span>
                                </div>
                                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-600 rounded-full" style="width: <?= $persen ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <i class="text-xs text-slate-400 italic block">Belum ada suara dari guru.</i>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Tabel Voter Siswa -->
        <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl shadow-sm p-4 md:p-5 flex flex-col gap-4">
            <div class="flex justify-between items-center">
                <h2 class="font-sans text-base font-bold text-slate-800 flex items-center gap-2">
                    <i class="bi bi-mortarboard text-emerald-500"></i>
                    <span>Daftar Voter Siswa</span>
                </h2>
                <span class="bg-emerald-100 border border-emerald-200 text-emerald-700 font-bold text-xs px-3.5 py-1.5 rounded-full">
                    Total: <?= $totalSiswa ?> Siswa
                </span>
            </div>

            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs text-slate-500 font-bold uppercase tracking-wider">
                            <th class="py-2.5 px-3">No</th>
                            <th class="py-2.5 px-3">Token Siswa</th>
                            <th class="py-2.5 px-3 text-center">Pilihan Kandidat</th>
                            <th class="py-2.5 px-3">Peran (Role)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-sm text-slate-700">
                        <?php if (count($votersSiswa) > 0): ?>
                            <?php foreach ($votersSiswa as $i => $v): ?>
                                <tr class="transition-colors duration-200 hover:bg-slate-50/50">
                                    <td class="py-2.5 px-3 font-semibold text-slate-500"><?= $offsetSiswa + $i + 1 ?></td>
                                    <td class="py-2.5 px-3 font-mono font-bold text-slate-900"><?= htmlspecialchars($v['nama_voter']) ?></td>
                                    <td class="py-2.5 px-3 text-center">
                                        <span class="inline-block py-1 px-3 rounded-full bg-emerald-100 border border-emerald-200 text-emerald-700 font-sans font-extrabold text-xs">
                                            Kandidat <?= htmlspecialchars($v['nomor_kandidat']) ?>
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-xs font-semibold uppercase text-slate-500"><?= htmlspecialchars($v['role']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="py-8 text-center text-slate-500">Belum ada siswa yang memilih.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPagesSiswa > 1): ?>
                <div class="flex justify-center gap-1.5 mt-4">
                    <?php for ($p = 1; $p <= $totalPagesSiswa; $p++): ?>
                        <a href="?page_siswa=<?= $p ?>&page_guru=<?= $pageGuru ?>" class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs transition-all duration-300 <?= $p == $pageSiswa ? 'bg-emerald-600 text-white shadow-md' : 'bg-slate-100 text-slate-600 border border-slate-200 hover:bg-slate-200' ?>"><?= $p ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tabel Voter Guru -->
        <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl shadow-sm p-4 md:p-5 flex flex-col gap-4 mt-2">
            <div class="flex justify-between items-center">
                <h2 class="font-sans text-base font-bold text-slate-800 flex items-center gap-2">
                    <i class="bi bi-person-badge text-emerald-500"></i>
                    <span>Daftar Voter Guru / Staff</span>
                </h2>
                <span class="bg-emerald-100 border border-emerald-200 text-emerald-700 font-bold text-xs px-3.5 py-1.5 rounded-full">
                    Total: <?= $totalGuru ?> Guru
                </span>
            </div>

            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs text-slate-500 font-bold uppercase tracking-wider">
                            <th class="py-2.5 px-3">No</th>
                            <th class="py-2.5 px-3">Token Guru / Staff</th>
                            <th class="py-2.5 px-3 text-center">Pilihan Kandidat</th>
                            <th class="py-2.5 px-3">Peran (Role)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-sm text-slate-700">
                        <?php if (count($votersGuru) > 0): ?>
                            <?php foreach ($votersGuru as $i => $v): ?>
                                <tr class="transition-colors duration-200 hover:bg-slate-50/50">
                                    <td class="py-2.5 px-3 font-semibold text-slate-500"><?= $offsetGuru + $i + 1 ?></td>
                                    <td class="py-2.5 px-3 font-mono font-bold text-slate-900"><?= htmlspecialchars($v['nama_voter']) ?></td>
                                    <td class="py-2.5 px-3 text-center">
                                        <span class="inline-block py-1 px-3 rounded-full bg-emerald-100 border border-emerald-200 text-emerald-700 font-sans font-extrabold text-xs">
                                            Kandidat <?= htmlspecialchars($v['nomor_kandidat']) ?>
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-xs font-semibold uppercase text-slate-500"><?= htmlspecialchars($v['role']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="py-8 text-center text-slate-500">Belum ada guru yang memilih.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPagesGuru > 1): ?>
                <div class="flex justify-center gap-1.5 mt-4">
                    <?php for ($p = 1; $p <= $totalPagesGuru; $p++): ?>
                        <a href="?page_guru=<?= $p ?>&page_siswa=<?= $pageSiswa ?>" class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs transition-all duration-300 <?= $p == $pageGuru ? 'bg-emerald-600 text-white shadow-md' : 'bg-slate-100 text-slate-600 border border-slate-200 hover:bg-slate-200' ?>"><?= $p ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="flex items-center justify-start mt-2">
            <a href="http://localhost/phpmyadmin/index.php?route=/sql&pos=0&db=db_vote_osis_generate_token&table=tb_voter" target="_blank" class="flex items-center gap-2 px-5 py-3.5 rounded-xl bg-slate-100 text-slate-600 font-bold text-xs border border-slate-200 hover:bg-slate-200 transition-all duration-300">
                <i class="bi bi-database"></i>
                <span>Buka Database tb_voter</span>
            </a>
        </div>
    
        <footer class="mt-8 pt-6 pb-4 text-center text-xs text-slate-500 flex flex-col gap-1 border-t border-slate-200">
            <p>&copy; <?php echo date('Y'); ?> Gradasiweb. All Rights Reserved.</p>
            <p>Dikembangkan oleh: [Fahiim]</p>
        </footer>
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
