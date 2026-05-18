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
    SELECT v.id, v.nama_voter, v.kelas, v.role, l.nomor_kandidat
    FROM tb_voter v
    JOIN tb_vote_log l ON v.id = l.voter_id
    WHERE v.role = 'siswa'
    ORDER BY v.kelas, v.nama_voter
    LIMIT $limit OFFSET $offsetSiswa
");
while ($row = mysqli_fetch_assoc($votedSiswaQuery)) {
    $votersSiswa[] = $row;
}

$totalGuruTargetQuery = mysqli_query($db, "SELECT COUNT(*) as total FROM tb_kode_guru");
$totalGuruTargetRow = mysqli_fetch_assoc($totalGuruTargetQuery);
$totalGuruTarget = isset($totalGuruTargetRow['total']) ? (int)$totalGuruTargetRow['total'] : 0;

$totalGuruVotedQuery = mysqli_query($db, "
    SELECT COUNT(DISTINCT v.id) as total
    FROM tb_voter v
    JOIN tb_vote_log l ON v.id = l.voter_id
    JOIN tb_kode_guru g ON v.nama_voter = g.kode /* RELASI UTAMA */
    WHERE v.role = 'guru'
");
$totalGuruRow = mysqli_fetch_assoc($totalGuruVotedQuery);
$totalGuru = isset($totalGuruRow['total']) ? (int)$totalGuruRow['total'] : 0;
$totalPagesGuru = $totalGuru > 0 ? ceil($totalGuru / $limit) : 1;

$votedGuru = $totalGuru;

$votedGuruQuery = mysqli_query($db, "
    SELECT v.id, v.nama_voter, v.kelas, v.role, l.nomor_kandidat
    FROM tb_voter v
    JOIN tb_vote_log l ON v.id = l.voter_id
    JOIN tb_kode_guru g ON v.nama_voter = g.kode /* RELASI UTAMA */
    WHERE v.role = 'guru'
    ORDER BY v.nama_voter
    LIMIT $limit OFFSET $offsetGuru
");
while ($row = mysqli_fetch_assoc($votedGuruQuery)) {
    $votersGuru[] = $row;
}

$dataKelas = [];
$qKelas = mysqli_query($db, "
    SELECT k.id AS id_kelas, k.nama_kelas, k.jumlah_siswa, t.kelas_id
    FROM tb_kelas k
    LEFT JOIN tb_buat_token t ON t.kelas_id = k.id
    ORDER BY k.id ASC
");
while ($row = mysqli_fetch_assoc($qKelas)) {
    $dataKelas[$row['nama_kelas']] = [
        'id_kelas' => (int)$row['id_kelas'],
        'kelas_id' => (int)$row['kelas_id'],
        'jumlah_siswa' => (int)$row['jumlah_siswa']
    ];
}

$kelasSummary = [];
foreach ($dataKelas as $kelas => $target) {
    $q = mysqli_query($db, "
        SELECT COUNT(*) as jumlah 
        FROM tb_voter v 
        JOIN tb_vote_log l ON v.id=l.voter_id 
        WHERE v.kelas='" . mysqli_real_escape_string($db, $kelas) . "'
          AND v.role='siswa'
    ");
    $row = mysqli_fetch_assoc($q);
    $kelasSummary[$kelas] = [
        "voted" => isset($row['jumlah']) ? (int)$row['jumlah'] : 0,
        "target" => (int)$target
    ];
}

$hasilKandidat = [];
$q = mysqli_query($db, "
    SELECT v.kelas, l.nomor_kandidat, COUNT(*) as total_suara
    FROM tb_vote_log l
    JOIN tb_voter v ON l.voter_id = v.id
    WHERE v.role = 'siswa'
    GROUP BY v.kelas, l.nomor_kandidat
");

while ($row = mysqli_fetch_assoc($q)) {
    $kelas = $row['kelas'];
    $nomor = $row['nomor_kandidat'];
    $jumlah = $row['total_suara'];

    if (!isset($hasilKandidat[$kelas])) {
        $hasilKandidat[$kelas] = [
            "total" => 0,
            "kandidat" => []
        ];
    }

    $hasilKandidat[$kelas]["kandidat"][$nomor] = $jumlah;
    $hasilKandidat[$kelas]["total"] += $jumlah;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Voter - Voting OSIS</title>
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
                <a href="daftar-voter.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl bg-indigo-600/10 text-indigo-300 border-l-4 border-indigo-500 font-semibold group">
                    <i class="bi bi-card-checklist text-lg text-indigo-400"></i>
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
                <h1 class="font-outfit text-3xl font-extrabold bg-gradient-to-r from-white to-indigo-200 bg-clip-text text-transparent">Daftar Voter Terdaftar</h1>
                <p class="text-slate-400 text-sm mt-1">Mengawasi logs pemilih aktif (Siswa & Guru) beserta detail kelas masing-masing</p>
            </div>
        </header>

        <!-- voter Siswa Section -->
        <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-[28px] shadow-2xl p-7 flex flex-col gap-6">
            <div class="flex justify-between items-center">
                <h2 class="font-outfit text-xl font-bold text-slate-200 flex items-center gap-2">
                    <i class="bi bi-mortarboard text-indigo-400"></i>
                    <span>Daftar Voter Khusus Siswa</span>
                </h2>
                <span class="bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 font-bold text-xs px-3.5 py-1.5 rounded-full">
                    Total: <?= $totalSiswa ?> Siswa
                </span>
            </div>

            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-white/5 text-xs text-slate-400 font-bold uppercase tracking-wider">
                            <th class="py-4 px-4">No</th>
                            <th class="py-4 px-4">Nama Siswa</th>
                            <th class="py-4 px-4">Kelas</th>
                            <th class="py-4 px-4 text-center">Pilihan Kandidat</th>
                            <th class="py-4 px-4">Peran (Role)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-sm text-slate-200">
                        <?php if (count($votersSiswa) > 0): ?>
                            <?php foreach ($votersSiswa as $i => $v): ?>
                                <tr class="hover:bg-white/[0.01] transition-colors duration-200">
                                    <td class="py-4 px-4 font-semibold text-slate-400"><?= $offsetSiswa + $i + 1 ?></td>
                                    <td class="py-4 px-4 font-bold text-white"><?= htmlspecialchars($v['nama_voter']) ?></td>
                                    <td class="py-4 px-4"><?= htmlspecialchars($v['kelas']) ?></td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-block py-1 px-3 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 font-outfit font-extrabold text-xs">
                                            Kandidat <?= htmlspecialchars($v['nomor_kandidat']) ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-xs font-semibold uppercase text-slate-400"><?= htmlspecialchars($v['role']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-500">Belum ada siswa yang memilih.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Siswa -->
            <?php if ($totalPagesSiswa > 1): ?>
                <div class="flex justify-center gap-1.5 mt-4">
                    <?php for ($p = 1; $p <= $totalPagesSiswa; $p++): ?>
                        <a href="?page_siswa=<?= $p ?>&page_guru=<?= $pageGuru ?>" class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs transition-all duration-300 <?= $p == $pageSiswa ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-800 text-slate-400 border border-white/5 hover:bg-slate-750 hover:text-white' ?>"><?= $p ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Ringkasan Voting Kelas Grid -->
        <div class="flex flex-col gap-4 mt-4">
            <div>
                <h3 class="font-outfit text-xl font-bold text-slate-200 flex items-center gap-2">
                    <i class="bi bi-grid-3x3-gap text-indigo-400"></i>
                    <span>Ringkasan Partisipasi per Kelas</span>
                </h3>
                <p class="text-xs text-slate-400 mt-1">Klik pada kartu kelas untuk melihat rincian pemilih per kelas</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($dataKelas as $kelas => $info): ?>
                    <?php
                    $idKelas = $info['id_kelas'];
                    $kelasIDToken = $info['kelas_id'];
                    $target = $info['jumlah_siswa'];
                    $voted = $kelasSummary[$kelas]['voted'];
                    $percent = $target > 0 ? round(($voted / $target) * 100, 2) : 0;
                    ?>
                    <a href="../daftar voter/per-kelas.php?kelas_id=<?= $kelasIDToken ?>" class="block bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-[24px] p-6 shadow-2xl transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_12px_24px_-8px_rgba(0,0,0,0.6)]">
                        <div class="flex justify-between items-start gap-4">
                            <h4 class="font-outfit text-base font-extrabold text-white"><?= $kelas ?></h4>
                            <span class="text-[10px] font-extrabold text-indigo-300 bg-indigo-500/10 px-2 py-0.5 rounded border border-indigo-500/20"><?= $percent ?>%</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1 font-medium"><?= $voted ?> dari <?= $target ?> siswa telah memilih</p>
                        
                        <div class="w-full h-2 bg-slate-950 rounded-full overflow-hidden p-0.5 border border-white/5 mt-3">
                            <div class="h-full bg-indigo-500 rounded-full shadow-[0_0_8px_rgba(99,102,241,0.3)]" style="width: <?= $percent ?>%;"></div>
                        </div>

                        <!-- Mini list per kandidat -->
                        <div class="flex flex-col gap-2 mt-4 pt-4 border-t border-white/5">
                            <?php if (isset($hasilKandidat[$kelas])): ?>
                                <?php foreach ($hasilKandidat[$kelas]["kandidat"] as $nomor => $jumlah): ?>
                                    <?php
                                    $persen = $hasilKandidat[$kelas]["total"] > 0
                                        ? round(($jumlah / $hasilKandidat[$kelas]["total"]) * 100, 2)
                                        : 0;
                                    ?>
                                    <div class="flex flex-col gap-1">
                                        <div class="flex justify-between text-[11px] font-semibold text-slate-400">
                                            <span>Paslon #<?= $nomor ?></span>
                                            <span class="text-slate-300 font-bold"><?= $jumlah ?> suara (<?= $persen ?>%)</span>
                                        </div>
                                        <div class="w-full h-1 bg-slate-950 rounded-full overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500" style="width: <?= $persen ?>%;"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <i class="text-xs text-slate-500 italic block">Belum ada suara di kelas ini.</i>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Voter Guru Section -->
        <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-[28px] shadow-2xl p-7 flex flex-col gap-6 mt-4">
            <div class="flex justify-between items-center">
                <h2 class="font-outfit text-xl font-bold text-slate-200 flex items-center gap-2">
                    <i class="bi bi-mortarboard text-purple-400"></i>
                    <span>Daftar Voter Khusus Guru / Staff</span>
                </h2>
                <span class="bg-purple-500/10 border border-purple-500/20 text-purple-300 font-bold text-xs px-3.5 py-1.5 rounded-full">
                    Total: <?= $totalGuru ?> Guru
                </span>
            </div>

            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-white/5 text-xs text-slate-400 font-bold uppercase tracking-wider">
                            <th class="py-4 px-4">No</th>
                            <th class="py-4 px-4">Nama Guru / Staff</th>
                            <th class="py-4 px-4 text-center">Pilihan Kandidat</th>
                            <th class="py-4 px-4">Peran (Role)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-sm text-slate-200">
                        <?php if (count($votersGuru) > 0): ?>
                            <?php foreach ($votersGuru as $i => $v): ?>
                                <tr class="hover:bg-white/[0.01] transition-colors duration-200">
                                    <td class="py-4 px-4 font-semibold text-slate-400"><?= $offsetGuru + $i + 1 ?></td>
                                    <td class="py-4 px-4 font-bold text-white"><?= htmlspecialchars($v['nama_voter']) ?></td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-block py-1 px-3 rounded-full bg-purple-500/10 border border-purple-500/20 text-purple-300 font-outfit font-extrabold text-xs">
                                            Kandidat <?= htmlspecialchars($v['nomor_kandidat']) ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-xs font-semibold uppercase text-slate-400"><?= htmlspecialchars($v['role']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="py-8 text-center text-slate-500">Belum ada guru yang memilih, atau data guru sudah dihapus dari manajemen.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Guru -->
            <?php if ($totalPagesGuru > 1): ?>
                <div class="flex justify-center gap-1.5 mt-4">
                    <?php for ($p = 1; $p <= $totalPagesGuru; $p++): ?>
                        <a href="?page_guru=<?= $p ?>&page_siswa=<?= $pageSiswa ?>" class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs transition-all duration-300 <?= $p == $pageGuru ? 'bg-purple-600 text-white shadow-md' : 'bg-slate-800 text-slate-400 border border-white/5 hover:bg-slate-750 hover:text-white' ?>"><?= $p ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Ringkasan Voting Guru -->
        <?php
        $guruSummary = [
            "total" => 0,
            "kandidat" => []
        ];

        $qGuru = mysqli_query($db, "
            SELECT l.nomor_kandidat, COUNT(*) as total_suara
            FROM tb_vote_log l
            JOIN tb_voter v ON l.voter_id = v.id
            JOIN tb_kode_guru g ON v.nama_voter = g.kode /* RELASI UTAMA */
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
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
            <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-[24px] p-6 shadow-2xl flex flex-col gap-4">
                <div class="flex justify-between items-start gap-4">
                    <h4 class="font-outfit text-base font-extrabold text-white">Ringkasan Voting Guru</h4>
                    <span class="text-[10px] font-extrabold text-purple-300 bg-purple-500/10 px-2 py-0.5 rounded border border-purple-500/20"><?= $percentGuru ?>%</span>
                </div>
                <p class="text-xs text-slate-400 font-medium"><?= $votedGuru ?> dari <?= $totalGuruTarget ?> guru telah memilih</p>
                
                <div class="w-full h-2 bg-slate-950 rounded-full overflow-hidden p-0.5 border border-white/5">
                    <div class="h-full bg-purple-500 rounded-full shadow-[0_0_8px_rgba(168,85,247,0.3)]" style="width: <?= $percentGuru ?>%;"></div>
                </div>

                <div class="flex flex-col gap-2 mt-2">
                    <?php if (!empty($guruSummary["kandidat"])): ?>
                        <?php foreach ($guruSummary["kandidat"] as $nomor => $jumlah): ?>
                            <?php
                            $persen = $guruSummary["total"] > 0
                                ? round(($jumlah / $guruSummary["total"]) * 100, 2)
                                : 0;
                            ?>
                            <div class="flex flex-col gap-1">
                                <div class="flex justify-between text-[11px] font-semibold text-slate-400">
                                    <span>Paslon #<?= $nomor ?></span>
                                    <span class="text-slate-300 font-bold"><?= $jumlah ?> suara (<?= $persen ?>%)</span>
                                </div>
                                <div class="w-full h-1 bg-slate-950 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-purple-500 to-pink-500" style="width: <?= $persen ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <i class="text-xs text-slate-500 italic block">Belum ada suara dari guru.</i>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="flex items-center justify-start">
                <a href="http://localhost/phpmyadmin/index.php?route=/sql&pos=0&db=db_vote_osis_generate_token&table=tb_voter" target="_blank" class="flex items-center gap-2 px-5 py-3.5 rounded-xl bg-slate-850 hover:bg-slate-800 text-slate-300 font-bold text-xs border border-white/5 transition-all duration-300">
                    <i class="bi bi-database"></i>
                    <span>Buka Database tb_voter</span>
                </a>
            </div>
        </div>
    </main>
</body>

</html>
