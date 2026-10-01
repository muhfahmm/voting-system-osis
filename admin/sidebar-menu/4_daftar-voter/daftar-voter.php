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
        "target" => (int)$target['jumlah_siswa']
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
    
    
    

    
    <aside class="w-64 bg-white/80 backdrop-blur-xl border-r border-slate-200 shadow-sm flex flex-col fixed top-0 left-0 z-20 h-screen">
        
        <div class="flex flex-col gap-4 p-4 flex-1 overflow-y-auto">
            <div class="flex items-center gap-3 border-b border-slate-200 pb-6">
                <img src="../../assets/img/logo osis.png" alt="Logo OSIS" class="h-9 object-contain">
                <div>
                    <h2 class="font-sans font-extrabold text-lg text-slate-900">Admin Panel</h2>
                    <p class="text-xs text-slate-500 font-semibold tracking-wide">E-VOTING SKALSA</p>
                </div>
            </div>

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
                <a href="daftar-voter.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg bg-emerald-50 text-emerald-700 border-l-4 border-emerald-500 font-semibold group">
                    <i class="bi bi-card-checklist text-lg text-emerald-500"></i>
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

    
    <main class="flex-1 p-5 md:p-8 z-10 flex flex-col gap-4 w-full ml-64">
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-6">
            <div>
                <h1 class="font-sans text-2xl font-bold text-slate-900">Daftar Voter Terdaftar</h1>
            </div>
        </header>

        
        <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl shadow-sm p-4 md:p-5 flex flex-col gap-4">
            <div class="flex justify-between items-center">
                <h2 class="font-sans text-base font-bold text-slate-800 flex items-center gap-2">
                    <i class="bi bi-mortarboard text-emerald-500"></i>
                    <span>Daftar Voter Khusus Siswa</span>
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
                            <th class="py-2.5 px-3">Nama Siswa</th>
                            <th class="py-2.5 px-3">Kelas</th>
                            <th class="py-2.5 px-3 text-center">Pilihan Kandidat</th>
                            <th class="py-2.5 px-3">Peran (Role)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-sm text-slate-700">
                        <?php if (count($votersSiswa) > 0): ?>
                            <?php foreach ($votersSiswa as $i => $v): ?>
                                <tr class="transition-colors duration-200">
                                    <td class="py-2.5 px-3 font-semibold text-slate-500"><?= $offsetSiswa + $i + 1 ?></td>
                                    <td class="py-2.5 px-3 font-bold text-slate-900"><?= htmlspecialchars($v['nama_voter']) ?></td>
                                    <td class="py-2.5 px-3"><?= htmlspecialchars($v['kelas']) ?></td>
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
                                <td colspan="5" class="py-8 text-center text-slate-500">Belum ada siswa yang memilih.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPagesSiswa > 1): ?>
                <div class="flex justify-center gap-1.5 mt-4">
                    <?php for ($p = 1; $p <= $totalPagesSiswa; $p++): ?>
                        <a href="?page_siswa=<?= $p ?>&page_guru=<?= $pageGuru ?>" class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs transition-all duration-300 <?= $p == $pageSiswa ? 'bg-emerald-600 text-white shadow-md' : 'bg-slate-100 text-slate-600 border border-slate-200 ' ?>"><?= $p ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>

        
        <div class="flex flex-col gap-4 mt-4">
            <div>
                <h3 class="font-sans text-base font-bold text-slate-800 flex items-center gap-2">
                    <i class="bi bi-grid-3x3-gap text-emerald-500"></i>
                    <span>Ringkasan Partisipasi per Kelas</span>
                </h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($dataKelas as $kelas => $info): ?>
                    <?php
                    $idKelas = $info['id_kelas'];
                    $kelasIDToken = $info['kelas_id'];
                    $target = $info['jumlah_siswa'];
                    $voted = $kelasSummary[$kelas]['voted'];
                    $percent = $target > 0 ? round(($voted / $target) * 100, 2) : 0;
                    ?>
                    <a href="../daftar voter/per-kelas.php?kelas_id=<?= $kelasIDToken ?>" class="block bg-white/90 backdrop-blur-md border border-slate-200 rounded-xl p-4 shadow-sm transition-all duration-300 ">
                        <div class="flex justify-between items-start gap-4">
                            <h4 class="font-sans text-base font-extrabold text-slate-900"><?= $kelas ?></h4>
                            <span class="text-[10px] font-extrabold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded border border-emerald-200"><?= $percent ?>%</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1 font-medium"><?= $voted ?> dari <?= $target ?> siswa telah memilih</p>
                        
                        <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden p-0.5 border border-slate-200 mt-3">
                            <div class="h-full bg-emerald-500 rounded-full" style="width: <?= $percent ?>%;"></div>
                        </div>

                        <div class="flex flex-col gap-2 mt-4 pt-4 border-t border-slate-200">
                            <?php if (isset($hasilKandidat[$kelas])): ?>
                                <?php foreach ($hasilKandidat[$kelas]["kandidat"] as $nomor => $jumlah): ?>
                                    <?php
                                    $persen = $hasilKandidat[$kelas]["total"] > 0
                                        ? round(($jumlah / $hasilKandidat[$kelas]["total"]) * 100, 2)
                                        : 0;
                                    ?>
                                    <div class="flex flex-col gap-1">
                                        <div class="flex justify-between text-[11px] font-semibold text-slate-500">
                                            <span>Paslon #<?= $nomor ?></span>
                                            <span class="text-slate-700 font-bold"><?= $jumlah ?> suara (<?= $persen ?>%)</span>
                                        </div>
                                        <div class="w-full h-1 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full bg-emerald-600" style="width: <?= $persen ?>%;"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <i class="text-xs text-slate-400 italic block">Belum ada suara di kelas ini.</i>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        
        <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl shadow-sm p-4 md:p-5 flex flex-col gap-4 mt-4">
            <div class="flex justify-between items-center">
                <h2 class="font-sans text-base font-bold text-slate-800 flex items-center gap-2">
                    <i class="bi bi-mortarboard text-emerald-500"></i>
                    <span>Daftar Voter Khusus Guru / Staff</span>
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
                            <th class="py-2.5 px-3">Nama Guru / Staff</th>
                            <th class="py-2.5 px-3 text-center">Pilihan Kandidat</th>
                            <th class="py-2.5 px-3">Peran (Role)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-sm text-slate-700">
                        <?php if (count($votersGuru) > 0): ?>
                            <?php foreach ($votersGuru as $i => $v): ?>
                                <tr class="transition-colors duration-200">
                                    <td class="py-2.5 px-3 font-semibold text-slate-500"><?= $offsetGuru + $i + 1 ?></td>
                                    <td class="py-2.5 px-3 font-bold text-slate-900"><?= htmlspecialchars($v['nama_voter']) ?></td>
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
                        <a href="?page_guru=<?= $p ?>&page_siswa=<?= $pageSiswa ?>" class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs transition-all duration-300 <?= $p == $pageGuru ? 'bg-emerald-600 text-white shadow-md' : 'bg-slate-100 text-slate-600 border border-slate-200 ' ?>"><?= $p ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>

        
        <?php
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
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-xl p-4 shadow-sm flex flex-col gap-4">
                <div class="flex justify-between items-start gap-4">
                    <h4 class="font-sans text-base font-extrabold text-slate-900">Ringkasan Voting Guru</h4>
                    <span class="text-[10px] font-extrabold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded border border-emerald-200"><?= $percentGuru ?>%</span>
                </div>
                <p class="text-xs text-slate-500 font-medium"><?= $votedGuru ?> dari <?= $totalGuruTarget ?> guru telah memilih</p>
                
                <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden p-0.5 border border-slate-200">
                    <div class="h-full bg-emerald-500 rounded-full" style="width: <?= $percentGuru ?>%;"></div>
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
                                <div class="flex justify-between text-[11px] font-semibold text-slate-500">
                                    <span>Paslon #<?= $nomor ?></span>
                                    <span class="text-slate-700 font-bold"><?= $jumlah ?> suara (<?= $persen ?>%)</span>
                                </div>
                                <div class="w-full h-1 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-600" style="width: <?= $persen ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <i class="text-xs text-slate-400 italic block">Belum ada suara dari guru.</i>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="flex items-center justify-start">
                <a href="http://localhost/phpmyadmin/index.php?route=/sql&pos=0&db=db_vote_osis_generate_token&table=tb_voter" target="_blank" class="flex items-center gap-2 px-5 py-3.5 rounded-xl bg-slate-100 text-slate-600 font-bold text-xs border border-slate-200 transition-all duration-300">
                    <i class="bi bi-database"></i>
                    <span>Buka Database tb_voter</span>
                </a>
            </div>
        </div>
    </main>
</body>

</html>








