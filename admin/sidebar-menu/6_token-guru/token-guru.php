<?php
session_start();
require '../../../db/db.php';

if (!isset($_SESSION['login'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$admin = $_SESSION['username'];


function generateUniqueKodeGuru($db)
{
    $chars = 'abcdefghijklmnopqrstuvwxyz';
    do {
        $kode = 'gr';
        for ($i = 0; $i < 5; $i++) {
            $kode .= $chars[rand(0, strlen($chars) - 1)];
        }

        
        $check = mysqli_query($db, "SELECT id FROM tb_kode_guru WHERE kode = '$kode'");
    } while (mysqli_num_rows($check) > 0);

    return $kode;
}

$message = '';


if (isset($_POST['add_manual'])) {
    $kode = strtolower(trim($_POST['kode_manual'] ?? ''));

    if ($kode === '') {
        $message = '<i class="bi bi-exclamation-triangle"></i> Token tidak boleh kosong!';
    } elseif (!preg_match('/^[a-z]+$/', $kode)) {
        $message = '<i class="bi bi-exclamation-triangle"></i> Token hanya boleh berisi huruf (a-z)!';
    } elseif (strlen($kode) < 2 || strlen($kode) > 100) {
        $message = '<i class="bi bi-exclamation-triangle"></i> Token harus antara 2 sampai 100 karakter!';
    } else {
        $kode_esc = mysqli_real_escape_string($db, $kode);
        $check = mysqli_query($db, "SELECT id FROM tb_kode_guru WHERE kode = '$kode_esc'");
        if (mysqli_num_rows($check) > 0) {
            $message = '<i class="bi bi-exclamation-triangle"></i> Token <b>' . $kode . '</b> sudah terdaftar!';
        } else {
            $query = mysqli_query($db, "INSERT INTO tb_kode_guru (kode) VALUES ('$kode_esc')");
            if ($query) {
                $message = '<i class="bi bi-check-circle"></i> Token Guru manual berhasil ditambahkan: <b>' . $kode . '</b>';
                header("Location: " . preg_replace('/(\?.*)?$/', '', $_SERVER['REQUEST_URI']));
                exit;
            } else {
                $message = '<i class="bi bi-x-circle"></i> Gagal menambahkan token guru.';
            }
        }
    }
}


if (isset($_POST['generate'])) {
    $jumlah = (int)($_POST['jumlah'] ?? 0);

    if ($jumlah > 0 && $jumlah <= 100) {
        $berhasil = 0;
        for ($i = 0; $i < $jumlah; $i++) {
            $kode = generateUniqueKodeGuru($db);
            $kode_esc = mysqli_real_escape_string($db, $kode);
            if (mysqli_query($db, "INSERT INTO tb_kode_guru (kode) VALUES ('$kode_esc')")) {
                $berhasil++;
            }
        }
        $message = '<i class="bi bi-check-circle"></i> Berhasil membuat ' . $berhasil . ' token guru otomatis.';
        header("Location: " . preg_replace('/(\?.*)?$/', '', $_SERVER['REQUEST_URI']));
        exit;
    } else {
        $message = '<i class="bi bi-exclamation-triangle"></i> Jumlah guru/token harus antara 1 sampai 100!';
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


if (isset($_GET['reset_token'])) {
    $id = (int)$_GET['reset_token'];
    
    
    $check = mysqli_query($db, "SELECT kode FROM tb_kode_guru WHERE id = $id");
    if (mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        $kode = mysqli_real_escape_string($db, $row['kode']);
        
        mysqli_begin_transaction($db);
        try {
            
            $voterQuery = mysqli_query($db, "SELECT id FROM tb_voter WHERE kode_guru_id = $id OR nama_voter = '$kode'");
            while ($v = mysqli_fetch_assoc($voterQuery)) {
                $voter_id = $v['id'];
                
                mysqli_query($db, "DELETE FROM tb_vote_log WHERE voter_id = $voter_id");
            }
            
            mysqli_query($db, "DELETE FROM tb_voter WHERE kode_guru_id = $id OR nama_voter = '$kode'");
            
            
            mysqli_query($db, "UPDATE tb_kode_guru SET status_kode = 'belum' WHERE id = $id");
            
            mysqli_commit($db);
            $message = '<i class="bi bi-arrow-counterclockwise"></i> Penggunaan token guru berhasil di-reset. Token sekarang dapat digunakan kembali untuk memilih.';
            header("Location: " . preg_replace('/(\?.*)?$/', '', $_SERVER['REQUEST_URI']));
            exit;
        } catch (Exception $e) {
            mysqli_rollback($db);
            $message = '<i class="bi bi-x-circle"></i> Gagal me-reset token guru: ' . $e->getMessage();
        }
    } else {
        $message = '<i class="bi bi-exclamation-triangle"></i> Token tidak ditemukan.';
    }
}


if (isset($_POST['reset_all_used'])) {
    mysqli_begin_transaction($db);
    try {
        
        $tokenQuery = mysqli_query($db, "
            SELECT g.id, g.kode 
            FROM tb_kode_guru g
            JOIN tb_voter v ON g.kode = v.nama_voter OR g.id = v.kode_guru_id
        ");
        
        $reset_count = 0;
        while ($row = mysqli_fetch_assoc($tokenQuery)) {
            $id = $row['id'];
            $kode = mysqli_real_escape_string($db, $row['kode']);
            
            $voterQuery = mysqli_query($db, "SELECT id FROM tb_voter WHERE kode_guru_id = $id OR nama_voter = '$kode'");
            while ($v = mysqli_fetch_assoc($voterQuery)) {
                $voter_id = $v['id'];
                mysqli_query($db, "DELETE FROM tb_vote_log WHERE voter_id = $voter_id");
            }
            mysqli_query($db, "DELETE FROM tb_voter WHERE kode_guru_id = $id OR nama_voter = '$kode'");
            mysqli_query($db, "UPDATE tb_kode_guru SET status_kode = 'belum' WHERE id = $id");
            $reset_count++;
        }
        
        mysqli_commit($db);
        $message = '<i class="bi bi-arrow-counterclockwise"></i> Berhasil me-reset ' . $reset_count . ' token guru yang telah digunakan.';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    } catch (Exception $e) {
        mysqli_rollback($db);
        $message = '<i class="bi bi-x-circle"></i> Gagal me-reset token guru: ' . $e->getMessage();
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
    <title>Token Guru & Karyawan - Voting OSIS</title>
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
                <a href="../5_token-siswa/token-siswa.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-600 font-medium transition-all duration-300 group">
                    <i class="bi bi-key text-lg group-transition-colors"></i>
                    <span>Token Siswa</span>
                </a>
                <a href="token-guru.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg bg-emerald-50 text-emerald-700 border-l-4 border-emerald-500 font-semibold group">
                    <i class="bi bi-shield-lock text-lg text-emerald-500"></i>
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
                <h1 class="font-sans text-2xl font-bold text-slate-900">Token Guru & Karyawan</h1>
            </div>
        </header>

        
        <?php if (!empty($message)): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 p-4 rounded-2xl text-sm flex items-center gap-3">
                <i class="bi bi-info-circle text-emerald-500 flex-shrink-0 text-lg"></i>
                <span><?= $message; ?></span>
            </div>
        <?php endif; ?>

        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl p-4 shadow-sm relative overflow-hidden group">
                
                <div class="flex items-center gap-4">
                    <div class="w-9 h-9 bg-emerald-100 border border-emerald-200 rounded-2xl flex items-center justify-center text-emerald-600 text-xl">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Total Token Guru</p>
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
                        <input type="text" id="kode_manual" name="kode_manual" placeholder="Masukkan token guru manual" pattern="[a-zA-Z]+" minlength="2" maxlength="100" class="py-3 px-4 rounded-xl bg-white border border-slate-200 font-mono text-sm text-slate-700 w-full focus:outline-none (16, 185, 129,0.15)] lowercase" required autocomplete="off">
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
                        <input type="number" id="jumlah" name="jumlah" min="1" max="100" value="1" placeholder="Contoh: 10" class="py-3 px-4 rounded-xl bg-white border border-slate-200 font-sans text-sm text-slate-700 w-full focus:outline-none (16, 185, 129,0.15)]" required autocomplete="off">
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
                    <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus SELURUH token guru? Tindakan ini akan menghapus semua token, data voter, dan log vote.')">
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
                        <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Cari token..." class="py-2 px-3 pl-8 rounded-xl bg-white border border-slate-200 text-xs text-slate-700 focus:outline-none w-44 sm:w-56 font-medium">
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
                        <tbody class="divide-y divide-slate-200 text-sm text-slate-700">
                            <?php if ($totalData > 0): $no = $offset + 1;
                                while ($row = mysqli_fetch_assoc($queryTokens)): ?>
                                    <tr class="transition-colors duration-200">
                                        <td class="py-2.5 px-3 font-semibold text-slate-500"><?= $no++; ?></td>
                                        <td class="py-2.5 px-3 text-center">
                                            <span class="font-mono text-emerald-600 bg-emerald-50 border border-emerald-200 py-1 px-3.5 rounded-lg text-xs font-bold">
                                                <?= htmlspecialchars($row['kode']); ?>
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
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
                                        <td class="py-2.5 px-3 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <?php if ($row['status_penggunaan'] === 'sudah'): ?>
                                                    <a href="?reset_token=<?= $row['id']; ?>" class="px-3 py-1.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 font-bold text-xs transition-all duration-300 flex items-center gap-1" onclick="return confirm('Apakah Anda yakin ingin me-reset status penggunaan token guru ini agar dapat digunakan kembali?')">
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
                        <form method="POST" action="../token/export_token_guru.php">
                            <button type="submit" class="flex items-center gap-2 px-5 py-3 rounded-xl bg-emerald-600 border border-emerald-500 (16,185,129,0.25)] text-white font-bold text-xs transition-all duration-300">
                                <i class="bi bi-file-earmark-excel"></i>
                                <span>Ekspor Token ke Excel</span>
                            </button>
                        </form>

                        <form method="POST" action="" onsubmit="return confirm('Apakah Anda yakin ingin me-reset SEMUA token guru yang telah digunakan? Tindakan ini akan menghapus data voter dan log vote dari token guru terkait.')">
                            <button type="submit" name="reset_all_used" class="flex items-center gap-2 px-5 py-3 rounded-xl bg-amber-600 border border-amber-500 (245,158,11,0.25)] text-white font-bold text-xs transition-all duration-300">
                                <i class="bi bi-arrow-counterclockwise"></i>
                                <span>Reset Semua Token Terpakai</span>
                            </button>
                        </form>
                    </div>

                    <a href="http://localhost/phpmyadmin/index.php?route=/sql&pos=0&db=db_vote_osis_generate_token&table=tb_kode_guru" target="_blank" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 text-slate-600 text-xs border border-slate-200 transition-all duration-300">
                        <i class="bi bi-database"></i>
                        <span>Buka Database tb_kode_guru</span>
                    </a>
                </div>
            </div>
        </div>
    </main>
</body>

</html>








