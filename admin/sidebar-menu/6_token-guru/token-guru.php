<?php
session_start();
require '../../../db/db.php';

if (!isset($_SESSION['login'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$admin = $_SESSION['username'];
$message = '';

function generateRandomString($length = 6)
{
    $characters = 'abcdefghijklmnopqrstuvwxyz';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

if (isset($_POST['add_manual'])) {
    $kode_manual = strtolower(trim($_POST['kode_manual']));

    if (empty($kode_manual)) {
        $message = '<i class="bi bi-exclamation-triangle"></i> Token tidak boleh kosong!';
    } elseif (!preg_match('/^[a-z]+$/', $kode_manual)) {
        $message = '<i class="bi bi-exclamation-triangle"></i> Token hanya boleh berisi huruf!';
    } else {
        $kode_esc = mysqli_real_escape_string($db, $kode_manual);
        $check = mysqli_query($db, "SELECT id FROM tb_kode_guru WHERE kode = '$kode_esc'");
        if (mysqli_num_rows($check) > 0) {
            $message = '<i class="bi bi-exclamation-triangle"></i> Token sudah terdaftar!';
        } else {
            $insert = mysqli_query($db, "INSERT INTO tb_kode_guru (kode) VALUES ('$kode_esc')");
            if ($insert) {
                $message = '<i class="bi bi-check-circle"></i> Token manual berhasil ditambahkan: ' . htmlspecialchars($kode_manual);
                header("Location: " . preg_replace('/(\?.*)?$/', '', $_SERVER['REQUEST_URI']));
                exit;
            } else {
                $message = '<i class="bi bi-x-circle"></i> Gagal menambahkan token.';
            }
        }
    }
}

if (isset($_POST['update_token_guru'])) {
    $id = (int)$_POST['id'];
    $kode_baru = strtolower(trim($_POST['kode_baru']));

    if (empty($kode_baru)) {
        $message = '<i class="bi bi-exclamation-triangle"></i> Token tidak boleh kosong!';
    } elseif (!preg_match('/^[a-z]+$/', $kode_baru)) {
        $message = '<i class="bi bi-exclamation-triangle"></i> Token hanya boleh berisi huruf!';
    } else {
        $kode_esc = mysqli_real_escape_string($db, $kode_baru);
        $check = mysqli_query($db, "SELECT id FROM tb_kode_guru WHERE kode = '$kode_esc' AND id != $id");
        if (mysqli_num_rows($check) > 0) {
            $message = '<i class="bi bi-exclamation-triangle"></i> Token sudah terdaftar!';
        } else {
            $update = mysqli_query($db, "UPDATE tb_kode_guru SET kode = '$kode_esc' WHERE id = $id");
            if ($update) {
                $message = '<i class="bi bi-check-circle"></i> Token berhasil diperbarui: ' . htmlspecialchars($kode_baru);
                header("Location: " . preg_replace('/(\?.*)?$/', '', $_SERVER['REQUEST_URI']));
                exit;
            } else {
                $message = '<i class="bi bi-x-circle"></i> Gagal memperbarui token.';
            }
        }
    }
}

if (isset($_GET['edit_guru'])) {
    $id = (int)$_GET['edit_guru'];
    $q = mysqli_query($db, "SELECT * FROM tb_kode_guru WHERE id = $id");
    $editRowGuru = mysqli_fetch_assoc($q);

    if (!$editRowGuru) {
        $message = '<i class="bi bi-exclamation-triangle"></i> Token guru tidak ditemukan untuk diedit.';
    }
}

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];

    $checkUsed = mysqli_query($db, "
        SELECT v.id 
        FROM tb_voter v
        JOIN tb_kode_guru g ON v.nama_voter = g.kode
        WHERE g.id = $id
    ");

    if (mysqli_num_rows($checkUsed) > 0) {
        $message = '<i class="bi bi-x-circle"></i> Token tidak dapat dihapus karena sudah digunakan untuk memilih!';
    } else {
        $delete = mysqli_query($db, "DELETE FROM tb_kode_guru WHERE id = $id");
        if ($delete) {
            $message = '<i class="bi bi-trash"></i> Token berhasil dihapus.';
            header("Location: " . preg_replace('/(\?.*)?$/', '', $_SERVER['REQUEST_URI']));
            exit;
        } else {
            $message = '<i class="bi bi-x-circle"></i> Gagal menghapus token.';
        }
    }
}

if (isset($_POST['reset_unused'])) {
    $deleteUnused = mysqli_query($db, "
        DELETE g FROM tb_kode_guru g
        LEFT JOIN tb_voter v ON g.kode = v.nama_voter
        WHERE v.id IS NULL
    ");

    if ($deleteUnused) {
        $message = '<i class="bi bi-trash"></i> Seluruh token yang belum digunakan berhasil di-reset/dihapus.';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    } else {
        $message = '<i class="bi bi-x-circle"></i> Gagal me-reset token.';
    }
}

if (isset($_POST['generate'])) {
    $jumlah = (int)$_POST['jumlah'];

    if ($jumlah > 0 && $jumlah <= 500) {
        $berhasil = 0;
        for ($i = 0; $i < $jumlah; $i++) {
            do {
                $token = generateRandomString(6);
                $token_esc = mysqli_real_escape_string($db, $token);
                $check = mysqli_query($db, "SELECT id FROM tb_kode_guru WHERE kode = '$token_esc'");
            } while (mysqli_num_rows($check) > 0);

            if (mysqli_query($db, "INSERT INTO tb_kode_guru (kode) VALUES ('$token_esc')")) {
                $berhasil++;
            }
        }
        $message = '<i class="bi bi-check-circle"></i> Berhasil membuat ' . $berhasil . ' token guru otomatis.';
        header("Location: " . preg_replace('/(\?.*)?$/', '', $_SERVER['REQUEST_URI']));
        exit;
    } else {
        $message = '<i class="bi bi-exclamation-triangle"></i> Jumlah guru harus antara 1 sampai 500!';
    }
}

if (isset($_GET['reset_token'])) {
    $id = (int)$_GET['reset_token'];
    
    $qGuru = mysqli_query($db, "SELECT kode FROM tb_kode_guru WHERE id = $id");
    if ($rowG = mysqli_fetch_assoc($qGuru)) {
        $kode = mysqli_real_escape_string($db, $rowG['kode']);
        
        mysqli_begin_transaction($db);
        try {
            $voterQuery = mysqli_query($db, "SELECT id FROM tb_voter WHERE kode_guru_id = $id OR nama_voter = '$kode'");
            while ($v = mysqli_fetch_assoc($voterQuery)) {
                $voter_id = $v['id'];
                mysqli_query($db, "DELETE FROM tb_vote_log WHERE voter_id = $voter_id");
            }
            mysqli_query($db, "DELETE FROM tb_voter WHERE kode_guru_id = $id OR nama_voter = '$kode'");
            mysqli_commit($db);
            $message = '<i class="bi bi-arrow-counterclockwise"></i> Token berhasil di-reset menjadi Belum Digunakan.';
            header("Location: " . preg_replace('/(\?.*)?$/', '', $_SERVER['REQUEST_URI']));
            exit;
        } catch (Exception $e) {
            mysqli_rollback($db);
            $message = '<i class="bi bi-x-circle"></i> Gagal me-reset token: ' . $e->getMessage();
        }
    }
}

if (isset($_POST['reset_all_used'])) {
    mysqli_begin_transaction($db);
    try {
        $usedTokens = mysqli_query($db, "
            SELECT DISTINCT g.id, g.kode 
            FROM tb_kode_guru g
            JOIN tb_voter v ON g.kode = v.nama_voter OR g.id = v.kode_guru_id
        ");
        
        while ($t = mysqli_fetch_assoc($usedTokens)) {
            $id = $t['id'];
            $kode = mysqli_real_escape_string($db, $t['kode']);
            
            $voterQuery = mysqli_query($db, "SELECT id FROM tb_voter WHERE kode_guru_id = $id OR nama_voter = '$kode'");
            while ($v = mysqli_fetch_assoc($voterQuery)) {
                $voter_id = $v['id'];
                mysqli_query($db, "DELETE FROM tb_vote_log WHERE voter_id = $voter_id");
            }
            mysqli_query($db, "DELETE FROM tb_voter WHERE kode_guru_id = $id OR nama_voter = '$kode'");
        }
        mysqli_commit($db);
        $message = '<i class="bi bi-arrow-counterclockwise"></i> Seluruh token yang terpakai berhasil di-reset.';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    } catch (Exception $e) {
        mysqli_rollback($db);
        $message = '<i class="bi bi-x-circle"></i> Gagal me-reset token: ' . $e->getMessage();
    }
}

if (isset($_POST['clear_all_tokens'])) {
    mysqli_begin_transaction($db);
    try {
        mysqli_query($db, "DELETE FROM tb_vote_log");
        mysqli_query($db, "DELETE FROM tb_voter");
        mysqli_query($db, "DELETE FROM tb_kode_guru");
        mysqli_commit($db);
        $message = '<i class="bi bi-trash"></i> Semua token guru berhasil dihapus.';
        header("Location: " . preg_replace('/(\?.*)?$/', '', $_SERVER['REQUEST_URI']));
        exit;
    } catch (Exception $e) {
        mysqli_rollback($db);
        $message = '<i class="bi bi-x-circle"></i> Gagal menghapus semua token guru: ' . $e->getMessage();
    }
}

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($db, trim($_GET['search'])) : '';
$whereClause = '';
if ($search !== '') {
    $whereClause = "WHERE g.kode LIKE '%$search%'";
}

$totalQuery = mysqli_query($db, "SELECT COUNT(*) as total FROM tb_kode_guru g $whereClause");
$totalRow = mysqli_fetch_assoc($totalQuery);
$totalData = isset($totalRow['total']) ? (int)$totalRow['total'] : 0;
$totalPages = max(1, ceil($totalData / $limit));

$queryTokens = mysqli_query($db, "
    SELECT 
        g.*,
        CASE 
            WHEN v.id IS NOT NULL THEN 'sudah'
            ELSE 'belum'
        END AS status_penggunaan
    FROM tb_kode_guru g
    LEFT JOIN tb_voter v ON g.kode = v.nama_voter
    $whereClause
    ORDER BY g.id DESC
    LIMIT $limit OFFSET $offset
");

$qStatTotal = mysqli_query($db, "SELECT COUNT(*) as total FROM tb_kode_guru");
$statTotal = mysqli_fetch_assoc($qStatTotal)['total'] ?? 0;

$qStatUsed = mysqli_query($db, "
    SELECT COUNT(DISTINCT v.id) as total 
    FROM tb_voter v
    JOIN tb_kode_guru g ON v.nama_voter = g.kode
");
$statUsed = mysqli_fetch_assoc($qStatUsed)['total'] ?? 0;

$statUnused = $statTotal - $statUsed;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Token Guru & Karyawan Real-Time - Voting OSIS</title>
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
    
    <!-- Mobile Overlay Backdrop -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm z-30 hidden lg:hidden transition-opacity duration-300 opacity-0" onclick="toggleSidebar()"></div>

    <!-- Aside Sidebar Modular -->
    <?php 
    $active = 'token_guru';
    $baseMenu = '../';
    require '../sidebar.php'; 
    ?>

    <main class="flex-1 p-5 md:p-8 z-10 flex flex-col gap-4 w-full lg:ml-64">
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-6">
            <div class="flex items-center gap-3">
                <button type="button" onclick="toggleSidebar()" class="lg:hidden p-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 font-bold text-lg shadow-sm hover:bg-slate-50 flex items-center justify-center">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="font-sans text-2xl font-bold text-slate-900">Token Guru & Karyawan</h1>
                        <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold shadow-xs">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>REALTIME</span>
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                        <span>Status dan statistik terupdate otomatis tiap 2 detik</span>
                        <span>&bull;</span>
                        <span class="text-slate-400">Sinkron: <span id="lastSyncTime"><?= date('H:i:s') ?></span></span>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="fetchRealtimeTokenGuru(true)" id="btnManualSync" class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 font-semibold text-xs shadow-xs hover:bg-slate-50 transition-all">
                    <i class="bi bi-arrow-clockwise text-emerald-600 font-bold text-sm" id="iconSync"></i>
                    <span>Sinkronkan Sekarang</span>
                </button>
            </div>
        </header>
        
        <?php if (!empty($message)): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 p-4 rounded-2xl text-sm flex items-center gap-3">
                <i class="bi bi-info-circle text-emerald-500 flex-shrink-0 text-lg"></i>
                <span><?= $message; ?></span>
            </div>
        <?php endif; ?>

        <!-- Stat Cards Real-time -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl p-4 shadow-sm relative overflow-hidden group">
                <div class="flex items-center gap-4">
                    <div class="w-9 h-9 bg-emerald-100 border border-emerald-200 rounded-2xl flex items-center justify-center text-emerald-600 text-xl">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Total Token Guru</p>
                        <p id="statTotalText" class="text-xl font-bold mt-1 text-slate-900"><?= $statTotal ?></p>
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
                        <p id="statUsedText" class="text-xl font-bold mt-1 text-emerald-600"><?= $statUsed ?></p>
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
                        <p id="statUnusedText" class="text-xl font-bold mt-1 text-red-600"><?= $statUnused ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl shadow-sm p-4 md:p-5 flex flex-col gap-5 h-fit lg:col-span-1">
                <div>
                    <h3 class="font-sans text-sm font-bold text-slate-800 flex items-center gap-2">
                        <i class="bi bi-plus-circle text-emerald-500"></i>
                        <span>Buat Token Baru</span>
                    </h3>
                </div>

                <form method="POST" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="font-sans font-semibold text-xs text-slate-600 tracking-wider" for="kode_manual">Buat Token Manual</label>
                        <input type="text" id="kode_manual" name="kode_manual" placeholder="masukkan token guru manual" pattern="[a-zA-Z]+" class="py-3 px-4 rounded-xl bg-white border border-slate-200 font-sans text-sm text-slate-700 w-full focus:outline-none lowercase" required autocomplete="off">
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

                <form method="POST" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="font-sans font-semibold text-xs text-slate-600 tracking-wider" for="jumlah">Jumlah Guru</label>
                        <input type="number" id="jumlah" name="jumlah" min="1" max="500" value="1" placeholder="Contoh: 10" class="py-3 px-4 rounded-xl bg-white border border-slate-200 font-sans text-sm text-slate-700 w-full focus:outline-none" required autocomplete="off">
                    </div>
                    <button type="submit" name="generate" class="w-full py-3 rounded-xl bg-emerald-600 border border-emerald-500 font-bold text-sm tracking-wide text-white transition-all duration-300">
                        Generate Token Otomatis
                    </button>
                </form>

                <div class="mt-4 pt-5 border-t border-slate-200 flex flex-col gap-4">
                    <div>
                        <h4 class="text-xs font-bold text-red-600 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="bi bi-exclamation-triangle"></i>
                            <span>Reset</span>
                        </h4>
                    </div>
                    <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus SELURUH token guru? Tindakan ini akan menghapus semua token guru, data voter, dan log vote.')">
                        <button type="submit" name="clear_all_tokens" class="w-full py-3 rounded-xl bg-red-600 border border-red-500 text-white font-bold text-xs transition-colors">
                            Hapus Semua Token
                        </button>
                    </form>
                </div>
            </div>

            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl shadow-sm p-4 md:p-5 flex flex-col gap-4 lg:col-span-2">
                <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                    <h3 class="font-sans text-sm font-bold text-slate-800 flex items-center gap-2">
                        <i class="bi bi-list-task text-emerald-500"></i>
                        <span>Token Guru Terdaftar</span>
                    </h3>

                    <form method="GET" class="relative">
                        <input type="text" id="searchInput" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Cari token..." class="py-2 px-3 pl-8 rounded-xl bg-white border border-slate-200 text-xs text-slate-700 focus:outline-none w-44 sm:w-56 font-medium">
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    </form>
                </div>

                <div class="overflow-x-auto w-full">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 text-xs text-slate-500 font-bold uppercase tracking-wider">
                                <th class="py-2.5 px-3 text-left">No</th>
                                <th class="py-2.5 px-3 text-center">Token</th>
                                <th class="py-2.5 px-3 text-center">Status Token</th>
                                <th class="py-2.5 px-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tokenTableBody" class="divide-y divide-slate-200 text-sm text-slate-700">
                            <?php if ($totalData > 0): $no = $offset + 1;
                                while ($row = mysqli_fetch_assoc($queryTokens)): ?>
                                    <tr class="transition-colors duration-200" data-token-id="<?= $row['id']; ?>">
                                        <td class="py-2.5 px-3 font-semibold text-slate-500"><?= $no++; ?></td>
                                        <td class="py-2.5 px-3 text-center">
                                            <span class="font-mono text-emerald-600 bg-emerald-50 border border-emerald-200 py-1 px-3.5 rounded-lg text-xs font-bold">
                                                <?= htmlspecialchars($row['kode']); ?>
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-3 text-center status-col">
                                            <?php if ($row['status_penggunaan'] === 'sudah'): ?>
                                                <span class="inline-flex items-center text-xs font-semibold py-1 px-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700">
                                                    Sudah Dipakai
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center text-xs font-semibold py-1 px-3 rounded-lg bg-red-50 border border-red-200 text-red-600">
                                                    Belum Dipakai
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-2.5 px-3 text-center action-col">
                                            <div class="flex items-center justify-center gap-2">
                                                <a href="?edit_guru=<?= $row['id']; ?>" class="px-3 py-1.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 font-bold text-xs transition-all duration-300 flex items-center gap-1">
                                                    <i class="bi bi-pencil-square"></i>
                                                    <span>Edit</span>
                                                </a>
                                                <?php if ($row['status_penggunaan'] === 'sudah'): ?>
                                                    <a href="?reset_token=<?= $row['id']; ?>" class="btn-reset px-3 py-1.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 font-bold text-xs transition-all duration-300 flex items-center gap-1" onclick="return confirm('Apakah Anda yakin ingin me-reset status penggunaan token guru ini agar dapat digunakan kembali?')">
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
                                    <td colspan="4" class="py-8 text-center text-slate-500">Tidak ada token guru terdaftar.</td>
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
                        <a href="../token/export_token_guru.php" class="flex items-center gap-2 px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 border border-emerald-500 text-white font-bold text-xs transition-all duration-300 shadow-sm">
                            <i class="bi bi-printer"></i>
                            <span>Cetak / Ekspor PDF Token Guru</span>
                        </a>

                        <form method="POST" action="" onsubmit="return confirm('Apakah Anda yakin ingin me-reset SEMUA token guru yang telah digunakan? Tindakan ini akan menghapus data voter dan log vote dari token guru terkait.')">
                            <button type="submit" name="reset_all_used" class="flex items-center gap-2 px-5 py-3 rounded-xl bg-amber-600 border border-amber-500 text-white font-bold text-xs transition-all duration-300">
                                <i class="bi bi-arrow-counterclockwise"></i>
                                <span>Reset Semua Token Terpakai</span>
                            </button>
                        </form>
                    </div>

                    <a href="http://localhost/phpmyadmin/index.php?route=/sql&pos=0&db=db_vote_osis_generate_token&table=tb_kode_guru" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 text-slate-600 text-xs border border-slate-200 transition-all duration-300">
                        <i class="bi bi-database"></i>
                        <span>Buka Database tb_kode_guru</span>
                    </a>
                </div>
            </div>
        </div>
    
        <footer class="mt-auto pt-6 pb-2 text-center text-xs text-slate-500 flex flex-col gap-1 border-t border-slate-200">
            <p>&copy; <?php echo date('Y'); ?> Panitia Pemilihan OSIS Skalsa. All Rights Reserved.</p>
            <p>Dikembangkan oleh: [Fahiim]</p>
        </footer>
    </main>

<?php if (isset($editRowGuru)): ?>
<div id="modalEditGuru" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-[1050] flex items-center justify-center p-4">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-2xl w-full max-w-md p-6 flex flex-col gap-5 relative">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <h3 class="font-sans text-base font-bold text-slate-800 flex items-center gap-2">
                <i class="bi bi-pencil-square text-amber-500 text-lg"></i>
                <span>Edit Token Guru</span>
            </h3>
            <a href="token-guru.php" class="text-slate-400 hover:text-slate-600 text-xl font-bold transition-colors">&times;</a>
        </div>

        <form method="POST" class="flex flex-col gap-4">
            <input type="hidden" name="id" value="<?= $editRowGuru['id']; ?>">
            
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-slate-600">Kode Token Guru</label>
                <input type="text" name="kode_baru" value="<?= htmlspecialchars($editRowGuru['kode']); ?>" pattern="[a-zA-Z]+" minlength="2" maxlength="100" class="py-2.5 px-3.5 rounded-xl bg-white border border-slate-200 font-mono text-sm text-slate-800 focus:outline-none focus:border-amber-500 lowercase" required autocomplete="off">
                <p class="text-[11px] text-slate-400">Token hanya boleh berisi huruf (a-z).</p>
            </div>

            <div class="flex gap-3 pt-3 border-t border-slate-100">
                <a href="token-guru.php" class="flex-1 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs hover:bg-slate-200 transition-colors flex items-center justify-center">
                    Batal
                </a>
                <button type="submit" name="update_token_guru" class="flex-1 py-2.5 rounded-xl bg-emerald-600 text-white font-bold text-xs hover:bg-emerald-700 transition-colors shadow-sm flex items-center justify-center gap-1.5">
                    <i class="bi bi-check-lg text-sm"></i>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

    <script>
        const currentPage = <?= $page ?>;
        const currentSearch = <?= json_encode($search) ?>;

        let isFetchingGuru = false;

        async function fetchRealtimeTokenGuru(manual = false) {
            if (isFetchingGuru) return;
            isFetchingGuru = true;

            const iconSync = document.getElementById('iconSync');
            if (manual && iconSync) {
                iconSync.classList.add('animate-spin');
            }

            try {
                const params = new URLSearchParams({
                    page: currentPage,
                    search: currentSearch,
                    _: new Date().getTime()
                });

                const res = await fetch('get_realtime_token_guru.php?' + params.toString(), {
                    cache: 'no-cache',
                    headers: { 'Accept': 'application/json' }
                });

                if (!res.ok) throw new Error('Network error');
                const data = await res.json();

                if (data.status === 'success') {
                    // Update timestamp
                    const syncEl = document.getElementById('lastSyncTime');
                    if (syncEl && data.timestamp) syncEl.textContent = data.timestamp;

                    // Update Statistik
                    const stTot = document.getElementById('statTotalText');
                    if (stTot) stTot.textContent = data.statTotal;

                    const stUsed = document.getElementById('statUsedText');
                    if (stUsed) stUsed.textContent = data.statUsed;

                    const stUnused = document.getElementById('statUnusedText');
                    if (stUnused) stUnused.textContent = data.statUnused;

                    // Update baris tabel secara halus
                    if (data.tokens && Array.isArray(data.tokens)) {
                        data.tokens.forEach(tok => {
                            const tr = document.querySelector(`tr[data-token-id="${tok.id}"]`);
                            if (tr) {
                                const statusCol = tr.querySelector('.status-col');
                                const actionCol = tr.querySelector('.action-col .flex');

                                if (tok.status_penggunaan === 'sudah') {
                                    if (statusCol && !statusCol.innerHTML.includes('Sudah Dipakai')) {
                                        statusCol.innerHTML = `
                                            <span class="inline-flex items-center text-xs font-semibold py-1 px-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700">
                                                Sudah Dipakai
                                            </span>
                                        `;
                                    }
                                    if (actionCol && !actionCol.querySelector('.btn-reset')) {
                                        const editBtn = actionCol.querySelector('a[href*="edit_guru"]');
                                        const resetHtml = `
                                            <a href="?reset_token=${tok.id}" class="btn-reset px-3 py-1.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 font-bold text-xs transition-all duration-300 flex items-center gap-1" onclick="return confirm('Apakah Anda yakin ingin me-reset status penggunaan token guru ini agar dapat digunakan kembali?')">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                                <span>Reset</span>
                                            </a>
                                        `;
                                        if (editBtn) {
                                            editBtn.insertAdjacentHTML('afterend', resetHtml);
                                        }
                                    }
                                } else {
                                    if (statusCol && !statusCol.innerHTML.includes('Belum Dipakai')) {
                                        statusCol.innerHTML = `
                                            <span class="inline-flex items-center text-xs font-semibold py-1 px-3 rounded-lg bg-red-50 border border-red-200 text-red-600">
                                                Belum Dipakai
                                            </span>
                                        `;
                                    }
                                    if (actionCol) {
                                        const resetBtn = actionCol.querySelector('.btn-reset');
                                        if (resetBtn) resetBtn.remove();
                                    }
                                }
                            }
                        });
                    }
                }
            } catch (err) {
                console.warn('Realtime Token Guru warning:', err);
            } finally {
                isFetchingGuru = false;
                if (manual && iconSync) {
                    setTimeout(() => iconSync.classList.remove('animate-spin'), 600);
                }
            }
        }

        // Interval realtime polling tiap 2 detik
        setInterval(() => {
            fetchRealtimeTokenGuru(false);
        }, 2000);
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
