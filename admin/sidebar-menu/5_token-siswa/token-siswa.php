<?php
session_start();
require '../../../db/db.php';

if (!isset($_SESSION['login'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$admin = $_SESSION['username'];
$message = '';

function generateUniqueTokenPemilih($db)
{
    $q = mysqli_query($db, "SELECT token FROM tb_buat_token WHERE token LIKE 'PEM%' ORDER BY id DESC LIMIT 1");
    $nextNum = 1;
    if ($row = mysqli_fetch_assoc($q)) {
        if (preg_match('/^PEM(\d+)$/i', $row['token'], $m)) {
            $nextNum = (int)$m[1] + 1;
        } else {
            $countQ = mysqli_query($db, "SELECT COUNT(*) AS total FROM tb_buat_token WHERE token LIKE 'PEM%'");
            $nextNum = ((int)mysqli_fetch_assoc($countQ)['total']) + 1;
        }
    } else {
        $countQ = mysqli_query($db, "SELECT COUNT(*) AS total FROM tb_buat_token");
        $nextNum = ((int)mysqli_fetch_assoc($countQ)['total']) + 1;
    }

    do {
        $candidate = 'PEM' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
        $check = mysqli_query($db, "SELECT id FROM tb_buat_token WHERE token = '$candidate' LIMIT 1");
        if (mysqli_num_rows($check) == 0) {
            return $candidate;
        }
        $nextNum++;
    } while (true);
}

// Tambah token manual
if (isset($_POST['add_manual'])) {
    $token_manual = strtoupper(trim($_POST['token_manual'] ?? ''));

    if ($token_manual === '') {
        $message = '<i class="bi bi-exclamation-triangle"></i> Token tidak boleh kosong!';
    } elseif (strlen($token_manual) < 2 || strlen($token_manual) > 100) {
        $message = '<i class="bi bi-exclamation-triangle"></i> Token harus antara 2 sampai 100 karakter!';
    } else {
        $token_esc = mysqli_real_escape_string($db, $token_manual);
        $check = mysqli_query($db, "SELECT id FROM tb_buat_token WHERE token = '$token_esc' LIMIT 1");
        if (mysqli_num_rows($check) > 0) {
            $message = '<i class="bi bi-exclamation-triangle"></i> Token <b>' . htmlspecialchars($token_manual) . '</b> sudah terdaftar!';
        } else {
            if (mysqli_query($db, "INSERT INTO tb_buat_token (token, status_token) VALUES ('$token_esc', 'belum')")) {
                $message = '<i class="bi bi-check-circle"></i> Token Siswa manual berhasil ditambahkan: <b>' . htmlspecialchars($token_manual) . '</b>';
                header("Location: " . preg_replace('/(\?.*)?$/', '', $_SERVER['REQUEST_URI']));
                exit;
            } else {
                $message = '<i class="bi bi-x-circle"></i> Gagal menambahkan token.';
            }
        }
    }
}

// Update token manual hasil edit
if (isset($_POST['update_token_siswa'])) {
    $id = (int)$_POST['id'];
    $token_baru = strtoupper(trim($_POST['token_baru'] ?? ''));

    if ($token_baru === '') {
        $message = '<i class="bi bi-exclamation-triangle"></i> Token siswa tidak boleh kosong!';
    } elseif (strlen($token_baru) < 2 || strlen($token_baru) > 100) {
        $message = '<i class="bi bi-exclamation-triangle"></i> Token siswa harus antara 2 sampai 100 karakter!';
    } else {
        $token_esc = mysqli_real_escape_string($db, $token_baru);
        $check = mysqli_query($db, "SELECT id FROM tb_buat_token WHERE token = '$token_esc' AND id != $id");
        if (mysqli_num_rows($check) > 0) {
            $message = '<i class="bi bi-exclamation-triangle"></i> Token <b>' . htmlspecialchars($token_baru) . '</b> sudah terdaftar!';
        } else {
            $update = mysqli_query($db, "UPDATE tb_buat_token SET token = '$token_esc' WHERE id = $id");
            if ($update) {
                $message = '<i class="bi bi-check-circle"></i> Token Siswa berhasil diperbarui: <b>' . htmlspecialchars($token_baru) . '</b>';
                header("Location: " . preg_replace('/(\?.*)?$/', '', $_SERVER['REQUEST_URI']));
                exit;
            } else {
                $message = '<i class="bi bi-x-circle"></i> Gagal memperbarui token siswa.';
            }
        }
    }
}

if (isset($_GET['edit_siswa'])) {
    $id = (int)$_GET['edit_siswa'];
    $q = mysqli_query($db, "SELECT * FROM tb_buat_token WHERE id = $id");
    $editRowSiswa = mysqli_fetch_assoc($q);

    if (!$editRowSiswa) {
        $message = '<i class="bi bi-exclamation-triangle"></i> Token siswa tidak ditemukan untuk diedit.';
    }
}

// Generate bulk token otomatis
if (isset($_POST['generate'])) {
    $jumlah = (int)($_POST['jumlah'] ?? 0);

    if ($jumlah > 0 && $jumlah <= 500) {
        $berhasil = 0;
        for ($i = 0; $i < $jumlah; $i++) {
            $token = generateUniqueTokenPemilih($db);
            $token_esc = mysqli_real_escape_string($db, $token);
            if (mysqli_query($db, "INSERT INTO tb_buat_token (token, status_token) VALUES ('$token_esc', 'belum')")) {
                $berhasil++;
            }
        }
        $message = '<i class="bi bi-check-circle"></i> Berhasil membuat ' . $berhasil . ' token siswa otomatis.';
        header("Location: " . preg_replace('/(\?.*)?$/', '', $_SERVER['REQUEST_URI']));
        exit;
    } else {
        $message = '<i class="bi bi-exclamation-triangle"></i> Jumlah siswa/token harus antara 1 sampai 500!';
    }
}

// Hapus satu token
if (isset($_GET['hapus'])) {
    $id_token = (int)$_GET['hapus'];
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
            mysqli_query($db, "DELETE FROM tb_buat_token WHERE id = $id_token");
            mysqli_commit($db);
            $message = '<i class="bi bi-trash"></i> Token berhasil dihapus.';
            header("Location: " . preg_replace('/(\?.*)?$/', '', $_SERVER['REQUEST_URI']));
            exit;
        } catch (Exception $e) {
            mysqli_rollback($db);
            $message = '<i class="bi bi-x-circle"></i> Gagal menghapus token: ' . $e->getMessage();
        }
    } else {
        $message = '<i class="bi bi-exclamation-triangle"></i> Token tidak ditemukan.';
    }
}

// Reset status satu token
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
            mysqli_query($db, "UPDATE tb_buat_token SET status_token = 'belum' WHERE id = $id_token");
            mysqli_commit($db);
            $message = '<i class="bi bi-arrow-counterclockwise"></i> Penggunaan token siswa berhasil di-reset. Token sekarang dapat digunakan kembali untuk memilih.';
            header("Location: " . preg_replace('/(\?.*)?$/', '', $_SERVER['REQUEST_URI']));
            exit;
        } catch (Exception $e) {
            mysqli_rollback($db);
            $message = '<i class="bi bi-x-circle"></i> Gagal me-reset token: ' . $e->getMessage();
        }
    } else {
        $message = '<i class="bi bi-exclamation-triangle"></i> Token tidak ditemukan.';
    }
}

// Reset semua token terpakai
if (isset($_POST['reset_all_used'])) {
    mysqli_begin_transaction($db);
    try {
        $tokenQuery = mysqli_query($db, "SELECT id FROM tb_buat_token WHERE status_token = 'sudah'");
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
        $message = '<i class="bi bi-arrow-counterclockwise"></i> Berhasil me-reset ' . $reset_count . ' token siswa yang telah digunakan.';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    } catch (Exception $e) {
        mysqli_rollback($db);
        $message = '<i class="bi bi-x-circle"></i> Gagal me-reset token siswa: ' . $e->getMessage();
    }
}

// Hapus semua token siswa
if (isset($_POST['clear_all_tokens'])) {
    mysqli_begin_transaction($db);
    try {
        $tokenIdsRes = mysqli_query($db, "SELECT id FROM tb_buat_token");
        while ($t = mysqli_fetch_assoc($tokenIdsRes)) {
            $tid = $t['id'];
            $voterQuery = mysqli_query($db, "SELECT id FROM tb_voter WHERE token_id = $tid");
            while ($v = mysqli_fetch_assoc($voterQuery)) {
                $vid = $v['id'];
                mysqli_query($db, "DELETE FROM tb_vote_log WHERE voter_id = $vid");
            }
            mysqli_query($db, "DELETE FROM tb_voter WHERE token_id = $tid");
        }
        mysqli_query($db, "DELETE FROM tb_buat_token");
        mysqli_commit($db);
        $message = '<i class="bi bi-trash"></i> Semua token siswa berhasil dihapus.';
        header("Location: " . preg_replace('/(\?.*)?$/', '', $_SERVER['REQUEST_URI']));
        exit;
    } catch (Exception $e) {
        mysqli_rollback($db);
        $message = '<i class="bi bi-x-circle"></i> Gagal menghapus semua token siswa: ' . $e->getMessage();
    }
}

// Statistik Keseluruhan
$qStatTotal = mysqli_query($db, "SELECT COUNT(*) as total FROM tb_buat_token");
$statTotal = (int)mysqli_fetch_assoc($qStatTotal)['total'];

$qStatUsed = mysqli_query($db, "SELECT COUNT(*) as total FROM tb_buat_token WHERE status_token = 'sudah'");
$statUsed = (int)mysqli_fetch_assoc($qStatUsed)['total'];

$statUnused = $statTotal - $statUsed;

// Pagination & Pencarian (sama persis dengan token guru: limit 10)
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($db, trim($_GET['search'])) : '';
$whereClause = '';
if ($search !== '') {
    $whereClause = "WHERE token LIKE '%$search%'";
}

$totalQuery = mysqli_query($db, "SELECT COUNT(*) as total FROM tb_buat_token $whereClause");
$totalRow = mysqli_fetch_assoc($totalQuery);
$totalData = isset($totalRow['total']) ? (int)$totalRow['total'] : 0;
$totalPages = max(1, ceil($totalData / $limit));

$queryTokens = mysqli_query($db, "
    SELECT * 
    FROM tb_buat_token 
    $whereClause
    ORDER BY id DESC
    LIMIT $limit OFFSET $offset
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Token Siswa - Voting OSIS</title>
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

    <!-- Include Shared Sidebar -->
    <?php 
    $activeMenu = 'token_siswa';
    include '../sidebar.php'; 
    ?>

    <main class="flex-1 p-5 md:p-8 z-10 flex flex-col gap-4 w-full lg:ml-64">
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-6">
            <div class="flex items-center gap-3">
                <button type="button" onclick="toggleSidebar()" class="lg:hidden p-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 font-bold text-lg shadow-sm hover:bg-slate-50 flex items-center justify-center">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="font-sans text-2xl font-bold text-slate-900">Manajemen Token Siswa</h1>
                </div>
            </div>
        </header>
        
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-6">
            <div>
                <h1 class="font-sans text-2xl font-bold text-slate-900">Token Siswa</h1>
            </div>
        </header>

        <?php if (!empty($message)): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 p-4 rounded-2xl text-sm flex items-center gap-3">
                <i class="bi bi-info-circle text-emerald-500 flex-shrink-0 text-lg"></i>
                <span><?= $message; ?></span>
            </div>
        <?php endif; ?>

        <!-- Stat Cards (Sama persis dengan Token Guru) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl p-4 shadow-sm relative overflow-hidden group">
                <div class="flex items-center gap-4">
                    <div class="w-9 h-9 bg-emerald-100 border border-emerald-200 rounded-2xl flex items-center justify-center text-emerald-600 text-xl">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Total Token Siswa</p>
                        <p class="text-xl font-bold mt-1 text-slate-900"><?= $statTotal ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl p-4 shadow-sm relative overflow-hidden group">
                <div class="flex items-center gap-4">
                    <div class="w-9 h-9 bg-emerald-100 border border-emerald-200 rounded-2xl flex items-center justify-center text-emerald-600 text-xl">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Sudah Digunakan</p>
                        <p class="text-xl font-bold mt-1 text-emerald-600"><?= $statUsed ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl p-4 shadow-sm relative overflow-hidden group">
                <div class="flex items-center gap-4">
                    <div class="w-9 h-9 bg-red-100 border border-red-200 rounded-2xl flex items-center justify-center text-red-600 text-xl">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Belum Digunakan</p>
                        <p class="text-xl font-bold mt-1 text-red-600"><?= $statUnused ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2 Kolom Layout: Kiri Buat Token Baru (Manual + Otomatis + Reset), Kanan Tabel Token -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            
            <!-- Kolom Kiri: Buat Token Baru -->
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl shadow-sm p-4 md:p-5 flex flex-col gap-5 h-fit lg:col-span-1">
                <div>
                    <h3 class="font-sans text-sm font-bold text-slate-800 flex items-center gap-2">
                        <i class="bi bi-plus-circle text-emerald-500"></i>
                        <span>Buat Token Baru</span>
                    </h3>
                </div>

                <!-- Form Buat Token Manual -->
                <form method="POST" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="font-sans font-semibold text-xs text-slate-600 tracking-wider" for="token_manual">Buat Token Manual</label>
                        <input type="text" id="token_manual" name="token_manual" placeholder="masukkan token siswa manual" minlength="2" maxlength="100" class="py-3 px-4 rounded-xl bg-white border border-slate-200 font-mono text-sm text-slate-700 w-full focus:outline-none uppercase" required autocomplete="off">
                    </div>
                    <button type="submit" name="add_manual" class="w-full py-3 rounded-xl bg-emerald-600 border border-emerald-500 font-bold text-sm tracking-wide text-white transition-all duration-300">
                        Tambah Token Manual
                    </button>
                </form>

                <div class="flex items-center gap-3 text-xs text-slate-400">
                    <span class="flex-1 h-px bg-slate-200"></span>
                    <span>atau</span>
                    <span class="flex-1 h-px bg-slate-200"></span>
                </div>

                <!-- Form Generate Token Otomatis -->
                <form method="POST" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="font-sans font-semibold text-xs text-slate-600 tracking-wider" for="jumlah">Jumlah Siswa</label>
                        <input type="number" id="jumlah" name="jumlah" min="1" max="500" value="1" placeholder="Contoh: 10" class="py-3 px-4 rounded-xl bg-white border border-slate-200 font-sans text-sm text-slate-700 w-full focus:outline-none" required autocomplete="off">
                    </div>
                    <button type="submit" name="generate" class="w-full py-3 rounded-xl bg-emerald-600 border border-emerald-500 font-bold text-sm tracking-wide text-white transition-all duration-300">
                        Generate Token Otomatis
                    </button>
                </form>

                <!-- Reset Section -->
                <div class="mt-4 pt-5 border-t border-slate-200 flex flex-col gap-4">
                    <div>
                        <h4 class="text-xs font-bold text-red-600 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="bi bi-exclamation-triangle"></i>
                            <span>Reset</span>
                        </h4>
                    </div>
                    <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus SELURUH token siswa? Tindakan ini akan menghapus semua token, data voter, dan log vote.')">
                        <button type="submit" name="clear_all_tokens" class="w-full py-3 rounded-xl bg-red-600 border border-red-500 text-white font-bold text-xs transition-colors">
                            Hapus Semua Token
                        </button>
                    </form>
                </div>
            </div>

            <!-- Kolom Kanan: Token Siswa Terdaftar (Layout th td sama persis dengan Guru) -->
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl shadow-sm p-4 md:p-5 flex flex-col gap-4 lg:col-span-2">
                <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                    <h3 class="font-sans text-sm font-bold text-slate-800 flex items-center gap-2">
                        <i class="bi bi-list-task text-emerald-500"></i>
                        <span>Token Siswa Terdaftar</span>
                    </h3>

                    <form method="GET" class="relative">
                        <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Cari token..." class="py-2 px-3 pl-8 rounded-xl bg-white border border-slate-200 text-xs text-slate-700 focus:outline-none w-44 sm:w-56 font-medium">
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    </form>
                </div>

                <div class="overflow-x-auto w-full">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 text-xs text-slate-500 font-bold uppercase tracking-wider">
                                <th class="py-2.5 px-3 text-left">NO</th>
                                <th class="py-2.5 px-3 text-center">TOKEN</th>
                                <th class="py-2.5 px-3 text-center">STATUS TOKEN</th>
                                <th class="py-2.5 px-3 text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-sm text-slate-700">
                            <?php if ($totalData > 0): $no = $offset + 1;
                                while ($row = mysqli_fetch_assoc($queryTokens)): ?>
                                    <tr class="transition-colors duration-200">
                                        <td class="py-2.5 px-3 font-semibold text-slate-500"><?= $no++; ?></td>
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
                                                <a href="?edit_siswa=<?= $row['id']; ?>" class="px-3 py-1.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 font-bold text-xs transition-all duration-300 flex items-center gap-1">
                                                    <i class="bi bi-pencil-square"></i>
                                                    <span>Edit</span>
                                                </a>
                                                <?php if ($row['status_token'] === 'sudah'): ?>
                                                    <a href="?reset_token=<?= $row['id']; ?>" class="px-3 py-1.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 font-bold text-xs transition-all duration-300 flex items-center gap-1" onclick="return confirm('Apakah Anda yakin ingin me-reset status penggunaan token siswa ini agar dapat digunakan kembali?')">
                                                        <i class="bi bi-arrow-counterclockwise"></i>
                                                        <span>Reset</span>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="?hapus=<?= $row['id']; ?>" class="px-3 py-1.5 rounded-lg bg-red-50 border border-red-200 text-red-600 font-bold text-xs transition-all duration-300 flex items-center gap-1" onclick="return confirm('Hapus token ini?')">
                                                    <i class="bi bi-trash"></i>
                                                    <span>Hapus</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-slate-500">Tidak ada token siswa terdaftar.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="flex justify-center gap-1.5 mt-2">
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>" class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs transition-all duration-300 <?= $p == $page ? 'bg-emerald-600 text-white shadow-md' : 'bg-slate-100 text-slate-600 border border-slate-200 ' ?>"><?= $p ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>

                <div class="flex flex-wrap gap-4 border-t border-slate-200 pt-5 justify-between items-center mt-2">
                    <div class="flex flex-wrap gap-3">
                        <a href="../token/export_token_siswa.php" class="flex items-center gap-2 px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 border border-emerald-500 text-white font-bold text-xs transition-all duration-300 shadow-sm">
                            <i class="bi bi-printer"></i>
                            <span>Cetak / Ekspor PDF Token Siswa</span>
                        </a>

                        <form method="POST" action="" onsubmit="return confirm('Apakah Anda yakin ingin me-reset SEMUA token siswa yang telah digunakan? Tindakan ini akan menghapus data voter dan log vote dari token siswa terkait.')">
                            <button type="submit" name="reset_all_used" class="flex items-center gap-2 px-5 py-3 rounded-xl bg-amber-600 border border-amber-500 text-white font-bold text-xs transition-all duration-300">
                                <i class="bi bi-arrow-counterclockwise"></i>
                                <span>Reset Semua Token Terpakai</span>
                            </button>
                        </form>
                    </div>

                    <a href="http://localhost/phpmyadmin/index.php?route=/sql&pos=0&db=db_vote_osis_generate_token&table=tb_buat_token" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 text-slate-600 text-xs border border-slate-200 transition-all duration-300">
                        <i class="bi bi-database"></i>
                        <span>Buka Database tb_buat_token</span>
                    </a>
                </div>
            </div>
        </div>
    
        <footer class="mt-auto pt-6 pb-2 text-center text-xs text-slate-500 flex flex-col gap-1 border-t border-slate-200">
            <p>&copy; <?php echo date('Y'); ?> Panitia Pemilihan OSIS Skalsa. All Rights Reserved.</p>
            <p>Dikembangkan oleh: [Fahiim]</p>
        </footer>
    </main>

<?php if (isset($editRowSiswa)): ?>
<div id="modalEditSiswa" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-[1050] flex items-center justify-center p-4">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-2xl w-full max-w-md p-6 flex flex-col gap-5 relative">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <h3 class="font-sans text-base font-bold text-slate-800 flex items-center gap-2">
                <i class="bi bi-pencil-square text-amber-500 text-lg"></i>
                <span>Edit Token Siswa</span>
            </h3>
            <a href="token-siswa.php" class="text-slate-400 hover:text-slate-600 text-xl font-bold transition-colors">&times;</a>
        </div>

        <form method="POST" class="flex flex-col gap-4">
            <input type="hidden" name="id" value="<?= $editRowSiswa['id']; ?>">
            
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-slate-600">Kode Token Siswa</label>
                <input type="text" name="token_baru" value="<?= htmlspecialchars($editRowSiswa['token']); ?>" class="py-2.5 px-3.5 rounded-xl bg-white border border-slate-200 font-mono text-sm text-slate-800 focus:outline-none focus:border-amber-500 uppercase" required>
            </div>

            <div class="flex gap-3 pt-3 border-t border-slate-100">
                <a href="token-siswa.php" class="flex-1 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs hover:bg-slate-200 transition-colors flex items-center justify-center">
                    Batal
                </a>
                <button type="submit" name="update_token_siswa" class="flex-1 py-2.5 rounded-xl bg-emerald-600 text-white font-bold text-xs hover:bg-emerald-700 transition-colors shadow-sm flex items-center justify-center gap-1.5">
                    <i class="bi bi-check-lg text-sm"></i>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

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
