<?php
session_start();
require '../../../db/db.php';

if (!isset($_SESSION['login'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$admin = $_SESSION['username'];

function kelasToPrefix($kelas)
{
    $letters = preg_replace('/[^a-zA-Z]/', '', $kelas);
    $prefix = strtolower($letters);
    return $prefix === '' ? 'k' : $prefix;
}

function generateTokenByPrefixAndNumber($prefix, $classNum, $db)
{
    $like = mysqli_real_escape_string($db, $prefix . $classNum . '%');
    $q = mysqli_query($db, "SELECT COUNT(*) AS jumlah FROM tb_buat_token WHERE token LIKE '{$like}'");
    $row = mysqli_fetch_assoc($q);
    $jumlah = (int)$row['jumlah'] + 1;

    return $prefix . $classNum . str_pad($jumlah, 3, '0', STR_PAD_LEFT);
}

$kelasQuery = mysqli_query($db, "SELECT * FROM tb_kelas ORDER BY id ASC");
$kelasList = [];
while ($r = mysqli_fetch_assoc($kelasQuery)) {
    $kelasList[] = $r;
}
$totalKelas = count($kelasList);

$classNumberMap = [];
$prefixCounters = [];
foreach ($kelasList as $k) {
    $id = (int)$k['id'];
    $nama = $k['nama_kelas'];
    if (preg_match('/(\d+)/', $nama, $m)) {
        $classNum = (int)$m[1];
    } else {
        $prefix = kelasToPrefix($nama);
        if (!isset($prefixCounters[$prefix])) $prefixCounters[$prefix] = 0;
        $prefixCounters[$prefix]++;
        $classNum = $prefixCounters[$prefix];
    }
    $classNumberMap[$id] = $classNum;
}


$showExceedModal = false;

if (isset($_POST['add_class'])) {
    $kelas_input = trim($_POST['kelas']);
    $jumlah_siswa = (int)$_POST['jumlah_siswa'];

    if ($kelas_input !== '' && $jumlah_siswa > 0) {
        $kelas_esc = mysqli_real_escape_string($db, $kelas_input);
        $exists = mysqli_query($db, "SELECT * FROM tb_kelas WHERE nama_kelas='$kelas_esc'");
        if (mysqli_num_rows($exists) == 0) {
            mysqli_query($db, "INSERT INTO tb_kelas (nama_kelas, jumlah_siswa) VALUES ('$kelas_esc', $jumlah_siswa)");
            $message = '<i class="bi bi-check-circle"></i> Kelas \'' . $kelas_input . '\' berhasil ditambahkan.';
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $message = '<i class="bi bi-exclamation-triangle"></i> Kelas \'' . $kelas_input . '\' sudah ada.';
        }
    } else {
        $message = '<i class="bi bi-exclamation-triangle"></i> Nama kelas atau jumlah siswa tidak boleh kosong!';
    }
}

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $q = mysqli_query($db, "SELECT nama_kelas FROM tb_kelas WHERE id=$id");
    $r = mysqli_fetch_assoc($q);
    $kelas = $r ? $r['nama_kelas'] : null;

    if ($kelas) {
        $prefix = kelasToPrefix($kelas);
        mysqli_query($db, "DELETE FROM tb_kelas WHERE id=$id");

        $like = mysqli_real_escape_string($db, $prefix . '%');
        mysqli_query($db, "DELETE FROM tb_buat_token WHERE token LIKE '{$like}'");

        $message = '<i class="bi bi-trash"></i> Kelas \'' . $kelas . '\' dan token terkait berhasil dihapus.';
        header("Location: " . preg_replace('/(\?.*)?$/', '', $_SERVER['REQUEST_URI']));
        exit;
    }
}

if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $q = mysqli_query($db, "SELECT * FROM tb_kelas WHERE id = $id");
    $editRow = mysqli_fetch_assoc($q);

    if (!$editRow) {
        $message = '<i class="bi bi-exclamation-triangle"></i> Kelas tidak ditemukan untuk diedit.';
    }
}

if (isset($_POST['update_class'])) {
    $id = (int)$_POST['id'];
    $kelas_baru = trim($_POST['kelas_baru']);
    $jumlah_siswa_baru = (int)$_POST['jumlah_siswa_baru'];

    if ($kelas_baru !== '' && $jumlah_siswa_baru > 0) {
        $kelas_esc = mysqli_real_escape_string($db, $kelas_baru);
        $exists = mysqli_query($db, "SELECT * FROM tb_kelas WHERE nama_kelas='$kelas_esc' AND id != $id");

        if (mysqli_num_rows($exists) == 0) {
            mysqli_query($db, "UPDATE tb_kelas 
                                SET nama_kelas='$kelas_esc', jumlah_siswa=$jumlah_siswa_baru 
                                WHERE id=$id");
            $message = '<i class="bi bi-check-circle"></i> Data kelas berhasil diperbarui.';
            header("Location: " . preg_replace('/(\?.*)?$/', '', $_SERVER['REQUEST_URI']));
            exit;
        } else {
            $message = '<i class="bi bi-exclamation-triangle"></i> Nama kelas \'' . $kelas_baru . '\' sudah digunakan.';
        }
    } else {
        $message = '<i class="bi bi-exclamation-triangle"></i> Nama kelas atau jumlah siswa tidak boleh kosong!';
    }
}

if (isset($_GET['hapus_token'])) {
    $id_token = (int)$_GET['hapus_token'];
    $check = mysqli_query($db, "SELECT token FROM tb_buat_token WHERE id = $id_token");
    if (mysqli_num_rows($check) > 0) {
        $hapus = mysqli_query($db, "DELETE FROM tb_buat_token WHERE id = $id_token");
        $message = $hapus ? '<i class="bi bi-trash"></i> Token dan voter terkait berhasil dihapus.' : '<i class="bi bi-x-circle"></i> Gagal menghapus token.';
    } else {
        $message = '<i class="bi bi-exclamation-triangle"></i> Token tidak ditemukan.';
    }
}

if (isset($_GET['reset_token'])) {
    $id_token = (int)$_GET['reset_token'];
    $check = mysqli_query($db, "SELECT token FROM tb_buat_token WHERE id = $id_token");
    if (mysqli_num_rows($check) > 0) {
        mysqli_begin_transaction($db);
        try {
            $voterQuery = mysqli_query($db, "SELECT id FROM tb_voter WHERE token_id = $id_token");
            while ($v = mysqli_fetch_assoc($voterQuery)) {
                $voter_id = $v['id'];
                mysqli_query($db, "DELETE FROM tb_vote_log WHERE voter_id = $voter_id");
            }
            mysqli_query($db, "DELETE FROM tb_voter WHERE token_id = $id_token");
            $reset = mysqli_query($db, "UPDATE tb_buat_token SET status_token = 'belum' WHERE id = $id_token");
            mysqli_commit($db);
            $message = '<i class="bi bi-arrow-counterclockwise"></i> Penggunaan token berhasil di-reset. Token sekarang dapat digunakan kembali untuk memilih.';
        } catch (Exception $e) {
            mysqli_rollback($db);
            $message = '<i class="bi bi-x-circle"></i> Gagal me-reset token: ' . $e->getMessage();
        }
    } else {
        $message = '<i class="bi bi-exclamation-triangle"></i> Token tidak ditemukan.';
    }
}

if (isset($_POST['reset_all_used'])) {
    $kelas_id = (int)$_POST['kelas_id'];
    mysqli_begin_transaction($db);
    try {
        $tokenQuery = mysqli_query($db, "SELECT id FROM tb_buat_token WHERE kelas_id = $kelas_id AND status_token = 'sudah'");
        $reset_count = 0;
        while ($t = mysqli_fetch_assoc($tokenQuery)) {
            $id_token = $t['id'];
            $voterQuery = mysqli_query($db, "SELECT id FROM tb_voter WHERE token_id = $id_token");
            while ($v = mysqli_fetch_assoc($voterQuery)) {
                $voter_id = $v['id'];
                mysqli_query($db, "DELETE FROM tb_vote_log WHERE voter_id = $voter_id");
            }
            mysqli_query($db, "DELETE FROM tb_voter WHERE token_id = $id_token");
            mysqli_query($db, "UPDATE tb_buat_token SET status_token = 'belum' WHERE id = $id_token");
            $reset_count++;
        }
        mysqli_commit($db);
        $message = '<i class="bi bi-arrow-counterclockwise"></i> Berhasil me-reset ' . $reset_count . ' token yang telah digunakan di kelas ini.';
    } catch (Exception $e) {
        mysqli_rollback($db);
        $message = '<i class="bi bi-x-circle"></i> Gagal me-reset token: ' . $e->getMessage();
    }
}
if (isset($_POST['clear_tokens'])) {
    $kelas_id = (int)$_POST['kelas_id'];
    mysqli_begin_transaction($db);
    try {
        $tokenIdsRes = mysqli_query($db, "SELECT id FROM tb_buat_token WHERE kelas_id = $kelas_id");
        while ($t = mysqli_fetch_assoc($tokenIdsRes)) {
            $tid = $t['id'];
            $voterQuery = mysqli_query($db, "SELECT id FROM tb_voter WHERE token_id = $tid");
            while ($v = mysqli_fetch_assoc($voterQuery)) {
                $vid = $v['id'];
                mysqli_query($db, "DELETE FROM tb_vote_log WHERE voter_id = $vid");
            }
            mysqli_query($db, "DELETE FROM tb_voter WHERE token_id = $tid");
        }
        mysqli_query($db, "DELETE FROM tb_buat_token WHERE kelas_id = $kelas_id");
        mysqli_commit($db);
        $message = '<i class="bi bi-trash"></i> Semua token untuk kelas ini telah dihapus.';
    } catch (Exception $e) {
        mysqli_rollback($db);
        $message = '<i class="bi bi-x-circle"></i> Gagal mengosongkan token: ' . $e->getMessage();
    }
}


if (isset($_POST['generate'])) {
    $kelas_id = (int)$_POST['kelas_id'];
    $q = mysqli_query($db, "SELECT nama_kelas, jumlah_siswa FROM tb_kelas WHERE id = $kelas_id LIMIT 1");
    $r = mysqli_fetch_assoc($q);
    
    if ($r) {
        $kelas_nama = $r['nama_kelas'];
        $jumlah_siswa = (int)$r['jumlah_siswa'];

        $countRes = mysqli_query($db, "SELECT COUNT(*) as total FROM tb_buat_token WHERE kelas_id = $kelas_id");
        $currentTokenCount = (int)mysqli_fetch_assoc($countRes)['total'];

        if ($currentTokenCount >= $jumlah_siswa) {
            $showExceedModal = true;
            $kelasTerpilih = $kelas_id;
            $message = '<i class="bi bi-exclamation-triangle"></i> Jumlah token untuk kelas <b>' . $kelas_nama . '</b> sudah mencapai batas jumlah siswa (' . $jumlah_siswa . '). Token baru tidak dibuat.';
        } else {
            $prefix = kelasToPrefix($kelas_nama);
            $classNum = $classNumberMap[$kelas_id] ?? 0;
            $token = generateTokenByPrefixAndNumber($prefix, $classNum, $db);
            $token_esc = mysqli_real_escape_string($db, $token);
            mysqli_query($db, "INSERT INTO tb_buat_token (token, kelas_id, created_by) VALUES ('$token_esc', $kelas_id, '$admin')");
            $message = '<i class="bi bi-check-circle"></i> Token dibuat untuk <b>' . $kelas_nama . '</b>: <b>' . $token . '</b>';
            header("Location: token-siswa.php?kelas_id=$kelas_id");
            exit;
        }
    } else {
        $message = '<i class="bi bi-exclamation-triangle"></i> Kelas tidak ditemukan.';
    }
}

if (isset($_POST['generate_bulk'])) {
    $kelas_id = (int)$_POST['kelas_id'];
    $jumlah = (int)($_POST['jumlah'] ?? 0);

    if ($jumlah < 1 || $jumlah > 100) {
        $message = '<i class="bi bi-exclamation-triangle"></i> Jumlah token harus antara 1 sampai 100!';
        $kelasTerpilih = $kelas_id;
    } else {
        $q = mysqli_query($db, "SELECT nama_kelas, jumlah_siswa FROM tb_kelas WHERE id = $kelas_id LIMIT 1");
        $r = mysqli_fetch_assoc($q);

        if ($r) {
            $kelas_nama = $r['nama_kelas'];
            $jumlah_siswa = (int)$r['jumlah_siswa'];
            $countRes = mysqli_query($db, "SELECT COUNT(*) as total FROM tb_buat_token WHERE kelas_id = $kelas_id");
            $currentTokenCount = (int)mysqli_fetch_assoc($countRes)['total'];
            $remaining = $jumlah_siswa - $currentTokenCount;

            if ($remaining <= 0) {
                $showExceedModal = true;
                $kelasTerpilih = $kelas_id;
                $message = '<i class="bi bi-exclamation-triangle"></i> Jumlah token untuk kelas <b>' . $kelas_nama . '</b> sudah mencapai batas jumlah siswa (' . $jumlah_siswa . '). Token baru tidak dibuat.';
            } else {
                $toCreate = min($jumlah, $remaining);
                $prefix = kelasToPrefix($kelas_nama);
                $classNum = $classNumberMap[$kelas_id] ?? 0;
                $berhasil = 0;

                for ($i = 0; $i < $toCreate; $i++) {
                    $token = generateTokenByPrefixAndNumber($prefix, $classNum, $db);
                    $token_esc = mysqli_real_escape_string($db, $token);
                    if (mysqli_query($db, "INSERT INTO tb_buat_token (token, kelas_id) VALUES ('$token_esc', $kelas_id)")) {
                        $berhasil++;
                    }
                }

                if ($toCreate < $jumlah) {
                    $message = '<i class="bi bi-exclamation-triangle"></i> Hanya <b>' . $berhasil . '</b> token dibuat untuk <b>' . $kelas_nama . '</b> (batas siswa: ' . $jumlah_siswa . ', sisa slot: ' . $remaining . ').';
                } else {
                    $message = '<i class="bi bi-check-circle"></i> Berhasil membuat <b>' . $berhasil . '</b> token otomatis untuk kelas <b>' . $kelas_nama . '</b>.';
                }
                header("Location: token-siswa.php?kelas_id=$kelas_id");
                exit;
            }
        } else {
            $message = '<i class="bi bi-exclamation-triangle"></i> Kelas tidak ditemukan.';
        }
    }
}

$kelasTerpilih = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
if ($kelasTerpilih === 0 && isset($_POST['kelas_id'])) {
    $kelasTerpilih = (int)$_POST['kelas_id'];
}
if ($kelasTerpilih === 0 && !empty($kelasList)) {
    $kelasTerpilih = (int)$kelasList[0]['id'];
}

$kelasData = mysqli_query($db, "SELECT * FROM tb_kelas WHERE id = $kelasTerpilih");
$kelasRow = mysqli_fetch_assoc($kelasData);

$limitToken = 10;

$pageToken = isset($_GET['page_token']) ? (int)$_GET['page_token'] : 1;
if ($pageToken < 1) $pageToken = 1;
$offsetToken = ($pageToken - 1) * $limitToken;

$totalTokenResult = mysqli_query($db, "SELECT COUNT(*) AS total FROM tb_buat_token WHERE kelas_id = $kelasTerpilih");
$totalToken = mysqli_fetch_assoc($totalTokenResult)['total'] ?? 0;
$totalPagesToken = max(1, ceil($totalToken / $limitToken));

$tokens = mysqli_query($db, "
    SELECT t.*, k.nama_kelas 
    FROM tb_buat_token t
    LEFT JOIN tb_kelas k ON t.kelas_id = k.id
    WHERE t.kelas_id = $kelasTerpilih
    ORDER BY t.id ASC
    LIMIT $limitToken OFFSET $offsetToken
");

$tokenCountQuery = mysqli_query($db, "
    SELECT kelas_id, COUNT(*) AS total_token 
    FROM tb_buat_token 
    GROUP BY kelas_id
");
$tokenCountMap = [];
while ($row = mysqli_fetch_assoc($tokenCountQuery)) {
    $tokenCountMap[$row['kelas_id']] = (int)$row['total_token'];
}

$usedTokenQuery = mysqli_query($db, "
    SELECT kelas_id, COUNT(*) AS used_token 
    FROM tb_buat_token 
    WHERE status_token IN ('sudah', 'used', 'ya', '1', 'true') 
    GROUP BY kelas_id
");
$usedTokenMap = [];
while ($row = mysqli_fetch_assoc($usedTokenQuery)) {
    $usedTokenMap[$row['kelas_id']] = (int)$row['used_token'];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kelas & Token Siswa - Voting OSIS</title>
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
                <a href="../4_daftar-voter/daftar-voter.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-600 font-medium transition-all duration-300 group">
                    <i class="bi bi-card-checklist text-lg group-transition-colors"></i>
                    <span>Daftar Voter</span>
                </a>
                <a href="token-siswa.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg bg-emerald-50 text-emerald-700 border-l-4 border-emerald-500 font-semibold group">
                    <i class="bi bi-key text-lg text-emerald-500"></i>
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

    
    <main class="flex-1 p-5 md:p-8 z-10 flex flex-col gap-4 w-full ml-64">
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-6">
            <div>
                <h1 class="font-sans text-2xl font-bold text-slate-900">Manajemen Kelas & Token Siswa</h1>
            </div>
        </header>

        
        <?php if (!empty($message)): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 p-4 rounded-2xl text-sm flex items-center gap-3">
                <i class="bi bi-info-circle text-emerald-500 flex-shrink-0 text-lg"></i>
                <span><?= $message; ?></span>
            </div>
        <?php endif; ?>
<?php if ($showExceedModal): ?>
    <?php include __DIR__ . '/modals/modal_exceed.php'; ?>
<?php endif; ?>

        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl shadow-sm p-4 md:p-5 flex flex-col gap-5 lg:col-span-1 h-fit">
                <div>
                    <h3 class="font-sans text-sm font-bold text-slate-800 flex items-center gap-2">
                        <i class="bi bi-folder-plus text-emerald-500"></i>
                        <span>Tambah Kelas Baru</span>
                    </h3>
                </div>

                <form method="POST" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="font-sans font-semibold text-xs text-slate-600 tracking-wider" for="kelas">Nama Kelas</label>
                        <input type="text" id="kelas" name="kelas" placeholder="Masukkan kelas" class="py-3 px-4 rounded-xl bg-white border border-slate-200 font-sans text-sm text-slate-700 w-full focus:outline-none (16, 185, 129,0.15)]" required autocomplete="off">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="font-sans font-semibold text-xs text-slate-600 tracking-wider" for="jumlah_siswa">Jumlah Siswa</label>
                        <input type="number" id="jumlah_siswa" name="jumlah_siswa" placeholder="Jumlah siswa..." min="1" class="py-3 px-4 rounded-xl bg-white border border-slate-200 font-sans text-sm text-slate-700 w-full focus:outline-none (16, 185, 129,0.15)]" required>
                    </div>
                    <button type="submit" name="add_class" class="w-full mt-2 py-3 rounded-xl bg-emerald-600 border border-emerald-500 font-bold text-sm tracking-wide text-white transition-all duration-300">
                        Tambah Kelas
                    </button>
                </form>
            </div>

            
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl shadow-sm p-4 md:p-5 flex flex-col gap-4 lg:col-span-2">
                <h3 class="font-sans text-sm font-bold text-slate-800 flex items-center gap-2">
                    <i class="bi bi-list-stars text-emerald-500"></i>
                    <span>Daftar Kelas Terdaftar</span>
                </h3>

                <div class="overflow-x-auto w-full">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 text-xs text-slate-500 font-bold uppercase tracking-wider">
                                <th class="py-2.5 px-3 text-left">No</th>
                                <th class="py-2.5 px-3 text-left">Nama Kelas</th>
                                <th class="py-2.5 px-3 text-center">Jumlah Siswa</th>
                                <th class="py-2.5 px-3 text-center">Jumlah Token</th>
                                <th class="py-2.5 px-3 text-center">Token Digunakan</th>
                                <th class="py-2.5 px-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-sm text-slate-700">
                            <?php if ($totalKelas > 0): $no = 1;
                                foreach ($kelasList as $k): ?>
                                    <tr class="transition-colors duration-200">
                                        <td class="py-2.5 px-3 font-semibold text-slate-500"><?= $no++; ?></td>
                                        <td class="py-2.5 px-3 font-bold text-slate-900"><?= htmlspecialchars($k['nama_kelas']); ?></td>
                                        <td class="py-2.5 px-3 text-center"><?= htmlspecialchars($k['jumlah_siswa']); ?></td>
                                        <td class="py-2.5 px-3 text-center text-emerald-600 font-bold"><?= $tokenCountMap[$k['id']] ?? 0; ?></td>
                                        <td class="py-2.5 px-3 text-center text-emerald-600 font-bold"><?= $usedTokenMap[$k['id']] ?? 0; ?></td>
                                        <td class="py-2.5 px-3">
                                            <div class="flex flex-wrap gap-2 justify-center">
                                                <a href="?edit=<?= $k['id']; ?>" class="px-3 py-1.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 font-bold text-xs transition-colors">Edit</a>
                                                <a href="?hapus=<?= $k['id']; ?>" class="px-3 py-1.5 rounded-lg bg-red-50 border border-red-200 text-red-600 font-bold text-xs transition-colors" onclick="return confirm('Yakin ingin menghapus kelas ini?')">Hapus</a>
                                            </div>
                                            
                                            <?php if (isset($editRow) && $editRow['id'] == $k['id']): ?>
                                                <div class="mt-4 p-4 rounded-xl border border-amber-200 bg-amber-50/50 text-left flex flex-col gap-3 shadow-sm">
                                                    <div class="flex items-center gap-2 text-amber-700 font-bold text-xs">
                                                        <i class="bi bi-pencil-square"></i>
                                                        <span>Edit Data Kelas</span>
                                                    </div>
                                                    <form method="POST" class="flex flex-col gap-3">
                                                        <input type="hidden" name="id" value="<?= $editRow['id']; ?>">
                                                        <div class="flex flex-col gap-1">
                                                            <label class="text-[11px] font-semibold text-slate-600">Nama Kelas</label>
                                                            <input type="text" name="kelas_baru" value="<?= htmlspecialchars($editRow['nama_kelas']); ?>" class="py-2 px-3 rounded-lg bg-white border border-slate-200 font-sans text-xs text-slate-800 focus:outline-none focus:border-amber-500" required>
                                                        </div>
                                                        <div class="flex flex-col gap-1">
                                                            <label class="text-[11px] font-semibold text-slate-600">Jumlah Siswa</label>
                                                            <input type="number" name="jumlah_siswa_baru" value="<?= htmlspecialchars($editRow['jumlah_siswa']); ?>" min="1" class="py-2 px-3 rounded-lg bg-white border border-slate-200 font-sans text-xs text-slate-800 focus:outline-none focus:border-amber-500" required>
                                                        </div>
                                                        <div class="flex gap-2 pt-1">
                                                            <button type="submit" name="update_class" class="flex-1 py-2 rounded-lg bg-emerald-600 text-white font-bold text-xs hover:bg-emerald-700 transition-colors flex items-center justify-center gap-1.5">
                                                                <i class="bi bi-check-lg"></i>
                                                                <span>Simpan</span>
                                                            </button>
                                                            <a href="token-siswa.php" class="px-3 py-2 rounded-lg bg-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-300 transition-colors flex items-center justify-center">
                                                                Batal
                                                            </a>
                                                        </div>
                                                    </form>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach;
                            else: ?>
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-500">Belum ada data kelas terdaftar.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        
        <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl shadow-sm p-4 md:p-5 flex flex-col gap-4 mt-4">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                <h3 class="font-sans text-base font-bold text-slate-800 flex items-center gap-2">
                    <i class="bi bi-key text-emerald-500"></i>
                    <span>Daftar Token Terbuat</span>
                </h3>

                <form method="GET" class="flex items-center gap-3">
                    <label for="kelas_id" class="text-xs font-bold text-slate-600 uppercase tracking-wide">Filter Kelas:</label>
                    <select name="kelas_id" id="kelas_id" onchange="this.form.submit()" class="py-2 px-3 rounded-xl bg-white border border-slate-200 text-xs font-semibold text-slate-700 focus:outline-none cursor-pointer">
                        <?php foreach ($kelasList as $k): ?>
                            <option value="<?= $k['id']; ?>" <?= $k['id'] == $kelasTerpilih ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($k['nama_kelas']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <?php if ($kelasRow): ?>
                <?php
                    $prefixFilter = kelasToPrefix($kelasRow['nama_kelas']);
                    $classNumFilter = $classNumberMap[$kelasTerpilih] ?? 0;
                    $contohFilter = $prefixFilter . $classNumFilter . '001';
                    $sisaSlotFilter = max(0, (int)$kelasRow['jumlah_siswa'] - (int)$totalToken);
                ?>
                <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50 flex flex-col sm:flex-row sm:items-center gap-3 justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">Kelas <strong class="text-emerald-700"><?= htmlspecialchars($kelasRow['nama_kelas']); ?></strong></p>
                        <p class="text-xs text-slate-500 mt-0.5">Sisa slot: <span class="font-bold text-slate-700"><?= $sisaSlotFilter ?></span> dari <?= (int)$kelasRow['jumlah_siswa']; ?> siswa</p>
                    </div>
                    <form method="POST" class="flex items-center gap-2 shrink-0">
                        <input type="hidden" name="kelas_id" value="<?= $kelasTerpilih; ?>">
                        <div class="flex items-center gap-2">
                            <label for="jumlah_bulk" class="text-xs text-slate-600 font-medium">Jumlah:</label>
                            <input type="number" id="jumlah_bulk" name="jumlah" min="1" max="100" value="1" class="w-16 py-1.5 px-2.5 rounded-lg bg-white border border-slate-300 text-xs font-semibold text-slate-800 text-center focus:outline-none" required>
                        </div>
                        <button type="submit" name="generate_bulk" class="px-4 py-2 rounded-lg bg-emerald-600 border border-emerald-600 font-semibold text-xs text-white">
                            Generate Otomatis
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            
            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs text-slate-500 font-bold uppercase tracking-wider">
                            <th class="py-2.5 px-3 text-left">No</th>
                            <th class="py-2.5 px-3 text-left">Kelas</th>
                            <th class="py-2.5 px-3 text-center">Token</th>
                            <th class="py-2.5 px-3 text-center">Status Token</th>
                            <th class="py-2.5 px-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-sm text-slate-700">
                        <?php if ($totalToken > 0): $no = $offsetToken + 1;
                            while ($row = mysqli_fetch_assoc($tokens)): ?>
                                <tr class="transition-colors duration-200">
                                    <td class="py-2.5 px-3 font-semibold text-slate-500"><?= $no++; ?></td>
                                    <td class="py-2.5 px-3 font-bold text-slate-900"><?= htmlspecialchars($row['nama_kelas'] ?? '-'); ?></td>
                                    <td class="py-2.5 px-3 text-center">
                                        <span class="font-mono text-emerald-600 bg-emerald-50 border border-emerald-200 py-1 px-3.5 rounded-lg text-xs font-bold">
                                            <?= htmlspecialchars($row['token']); ?>
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        <?php if ($row['status_token'] === 'sudah'): ?>
                                            <span class="inline-flex items-center text-xs font-semibold py-1 px-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700">
                                                Sudah Dipakai
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center text-xs font-semibold py-1 px-3 rounded-lg bg-red-50 border border-red-200 text-red-600">
                                                Belum Dipakai
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <?php if ($row['status_token'] === 'sudah'): ?>
                                                <a href="?reset_token=<?= $row['id']; ?>&kelas_id=<?= $kelasTerpilih ?>" class="px-3 py-1.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 font-bold text-xs transition-all duration-300 flex items-center gap-1" onclick="return confirm('Apakah Anda yakin ingin me-reset status penggunaan token ini agar dapat digunakan kembali?')">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                    <span>Reset</span>
                                                </a>
                                            <?php endif; ?>
                                            <a href="?hapus_token=<?= $row['id']; ?>&kelas_id=<?= $kelasTerpilih ?>" class="px-3 py-1.5 rounded-lg bg-red-50 border border-red-200 text-red-600 font-bold text-xs transition-all duration-300 flex items-center gap-1" onclick="return confirm('Hapus token ini?')">
                                                <i class="bi bi-trash"></i>
                                                <span>Hapus</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile;
                        else: ?>
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-500">Belum ada token dibuat di kelas ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            
            <?php if ($totalPagesToken > 1): ?>
                <div class="flex justify-center gap-1.5 mt-2">
                    <?php for ($p = 1; $p <= $totalPagesToken; $p++): ?>
                        <a href="?page_token=<?= $p ?>&kelas_id=<?= $kelasTerpilih ?>" class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs transition-all duration-300 <?= $p == $pageToken ? 'bg-emerald-600 text-white shadow-md' : 'bg-slate-100 text-slate-600 border border-slate-200 ' ?>"><?= $p ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

            
            <div class="flex flex-wrap gap-4 border-t border-slate-200 pt-5 justify-between items-center">
                <div class="flex flex-wrap gap-3">
                    <form method="POST" action="../token/export_token_siswa.php">
                        <input type="hidden" name="kelas_id" value="<?= $kelasTerpilih; ?>">
                        <button type="submit" class="flex items-center gap-2 px-5 py-3 rounded-xl bg-emerald-600 border border-emerald-500 (16,185,129,0.25)] text-white font-bold text-xs transition-all duration-300">
                            <i class="bi bi-file-earmark-excel"></i>
                            <span>Ekspor Token ke Excel</span>
                        </button>
                    </form>

                    <form method="POST" action="" onsubmit="return confirm('Apakah Anda yakin ingin me-reset SEMUA token terpakai di kelas ini? Tindakan ini akan menghapus data voter dan log vote dari token-token terkait.')">
                        <input type="hidden" name="kelas_id" value="<?= $kelasTerpilih; ?>">
                        <button type="submit" name="reset_all_used" class="flex items-center gap-2 px-5 py-3 rounded-xl bg-amber-600 border border-amber-500 (245,158,11,0.25)] text-white font-bold text-xs transition-all duration-300">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            <span>Reset Semua Token Terpakai</span>
                        </button>
                    </form>

                    <form method="POST" action="" onsubmit="return confirm('PERHATIAN! Ini akan menghapus SEMUA token kelas ini (termasuk yang belum dipakai). Data voter dan log vote juga akan ikut dihapus. Lanjutkan?')">
                        <input type="hidden" name="kelas_id" value="<?= $kelasTerpilih; ?>">
                        <button type="submit" name="clear_tokens" class="flex items-center gap-2 px-5 py-3 rounded-xl bg-red-600 border border-red-500 (220,38,38,0.25)] text-white font-bold text-xs transition-all duration-300">
                            <i class="bi bi-trash3"></i>
                            <span>Kosongkan Token</span>
                        </button>
                    </form>
                </div>

                <a href="http://localhost/phpmyadmin/index.php?route=/sql&pos=0&db=db_vote_osis_generate_token&table=tb_buat_token" target="_blank" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 text-slate-600 text-xs border border-slate-200 transition-all duration-300">
                    <i class="bi bi-database"></i>
                    <span>Buka Database tb_buat_token</span>
                </a>
            </div>
        </div>
    </main>

<?php if ($showExceedModal): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var modal = document.getElementById('exceedModal');
        var closeBtn = document.getElementById('closeExceedModal');
        if (modal) {
            modal.classList.remove('hidden');
        }
        if (modal && closeBtn) {
            closeBtn.addEventListener('click', function() {
                modal.classList.add('hidden');
            });
        }
    });
</script>
<?php endif; ?>
</body>

</html>








