<?php
session_start();
require '../../../db/db.php';

if (!isset($_SESSION['login'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$admin = $_SESSION['username'];

// Fungsi untuk men-generate kode guru unik
function generateUniqueKodeGuru($db)
{
    $chars = 'abcdefghijklmnopqrstuvwxyz';
    do {
        $kode = 'gr';
        for ($i = 0; $i < 5; $i++) {
            $kode .= $chars[rand(0, strlen($chars) - 1)];
        }

        // Cek apakah kode sudah ada di database
        $check = mysqli_query($db, "SELECT id FROM tb_kode_guru WHERE kode = '$kode'");
    } while (mysqli_num_rows($check) > 0);

    return $kode;
}

$message = '';

// Aksi Buat Token Manual (admin mengetik sendiri)
if (isset($_POST['add_manual'])) {
    $kode = strtolower(trim($_POST['kode_manual'] ?? ''));

    if ($kode === '') {
        $message = "⚠️ Token tidak boleh kosong!";
    } elseif (!preg_match('/^[a-z]+$/', $kode)) {
        $message = "⚠️ Token hanya boleh berisi huruf (a-z)!";
    } elseif (strlen($kode) < 3 || strlen($kode) > 100) {
        $message = "⚠️ Token harus antara 3 sampai 100 karakter!";
    } else {
        $kode_esc = mysqli_real_escape_string($db, $kode);
        $check = mysqli_query($db, "SELECT id FROM tb_kode_guru WHERE kode = '$kode_esc'");
        if (mysqli_num_rows($check) > 0) {
            $message = "⚠️ Token <b>$kode</b> sudah terdaftar!";
        } else {
            $query = mysqli_query($db, "INSERT INTO tb_kode_guru (kode) VALUES ('$kode_esc')");
            if ($query) {
                $message = "✅ Token Guru manual berhasil ditambahkan: <b>$kode</b>";
                header("Location: " . preg_replace('/(\?.*)?$/', '', $_SERVER['REQUEST_URI']));
                exit;
            } else {
                $message = "❌ Gagal menambahkan token guru.";
            }
        }
    }
}

// Aksi Generate Token Otomatis (sesuai jumlah guru yang diinput)
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
        $message = "✅ Berhasil membuat $berhasil token guru otomatis.";
        header("Location: " . preg_replace('/(\?.*)?$/', '', $_SERVER['REQUEST_URI']));
        exit;
    } else {
        $message = "⚠️ Jumlah guru/token harus antara 1 sampai 100!";
    }
}

// Aksi Hapus Token Tunggal
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];

    // Cek apakah token sudah digunakan untuk memilih
    $checkUsed = mysqli_query($db, "
        SELECT v.id 
        FROM tb_voter v
        JOIN tb_kode_guru g ON v.nama_voter = g.kode
        WHERE g.id = $id
    ");

    if (mysqli_num_rows($checkUsed) > 0) {
        $message = "❌ Token tidak dapat dihapus karena sudah digunakan untuk memilih!";
    } else {
        $delete = mysqli_query($db, "DELETE FROM tb_kode_guru WHERE id = $id");
        if ($delete) {
            $message = "🗑️ Token berhasil dihapus.";
            header("Location: " . preg_replace('/(\?.*)?$/', '', $_SERVER['REQUEST_URI']));
            exit;
        } else {
            $message = "❌ Gagal menghapus token.";
        }
    }
}

// Aksi Reset/Hapus Semua Token yang Belum Dipakai
if (isset($_POST['reset_unused'])) {
    // Cari token yang tidak berelasi ke tb_voter (belum digunakan)
    $deleteUnused = mysqli_query($db, "
        DELETE g FROM tb_kode_guru g
        LEFT JOIN tb_voter v ON g.kode = v.nama_voter
        WHERE v.id IS NULL
    ");

    if ($deleteUnused) {
        $message = "🗑️ Seluruh token yang belum digunakan berhasil di-reset/dihapus.";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    } else {
        $message = "❌ Gagal me-reset token.";
    }
}

// Aksi Reset Token Guru Tunggal
if (isset($_GET['reset_token'])) {
    $id = (int)$_GET['reset_token'];
    
    // Cek apakah token ada
    $check = mysqli_query($db, "SELECT kode FROM tb_kode_guru WHERE id = $id");
    if (mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        $kode = mysqli_real_escape_string($db, $row['kode']);
        
        mysqli_begin_transaction($db);
        try {
            // Dapatkan voter_id dari tb_voter menggunakan kode guru
            $voterQuery = mysqli_query($db, "SELECT id FROM tb_voter WHERE kode_guru_id = $id OR nama_voter = '$kode'");
            while ($v = mysqli_fetch_assoc($voterQuery)) {
                $voter_id = $v['id'];
                // Hapus log vote terkait
                mysqli_query($db, "DELETE FROM tb_vote_log WHERE voter_id = $voter_id");
            }
            // Hapus voter terkait
            mysqli_query($db, "DELETE FROM tb_voter WHERE kode_guru_id = $id OR nama_voter = '$kode'");
            
            // Set status_kode menjadi 'belum'
            mysqli_query($db, "UPDATE tb_kode_guru SET status_kode = 'belum' WHERE id = $id");
            
            mysqli_commit($db);
            $message = "🔄 Penggunaan token guru berhasil di-reset. Token sekarang dapat digunakan kembali untuk memilih.";
            header("Location: " . preg_replace('/(\?.*)?$/', '', $_SERVER['REQUEST_URI']));
            exit;
        } catch (Exception $e) {
            mysqli_rollback($db);
            $message = "❌ Gagal me-reset token guru: " . $e->getMessage();
        }
    } else {
        $message = "⚠️ Token tidak ditemukan.";
    }
}

// Aksi Reset Semua Token Guru Terpakai
if (isset($_POST['reset_all_used'])) {
    mysqli_begin_transaction($db);
    try {
        // Dapatkan semua token guru yang sudah terpakai
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
        $message = "🔄 Berhasil me-reset $reset_count token guru yang telah digunakan.";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    } catch (Exception $e) {
        mysqli_rollback($db);
        $message = "❌ Gagal me-reset token guru: " . $e->getMessage();
    }
}

// Aksi Kosongkan Semua Token Guru
if (isset($_POST['clear_all_tokens'])) {
    mysqli_begin_transaction($db);
    try {
        mysqli_query($db, "DELETE FROM tb_vote_log");
        mysqli_query($db, "DELETE FROM tb_voter");
        mysqli_query($db, "DELETE FROM tb_kode_guru");
        mysqli_commit($db);
        $message = "🗑️ Semua token guru berhasil dihapus.";
        header("Location: " . preg_replace('/(\?.*)?$/', '', $_SERVER['REQUEST_URI']));
        exit;
    } catch (Exception $e) {
        mysqli_rollback($db);
        $message = "❌ Gagal menghapus semua token guru: " . $e->getMessage();
    }
}


// Paginasi & Pencarian
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($db, trim($_GET['search'])) : '';
$whereClause = '';
if ($search !== '') {
    $whereClause = "WHERE g.kode LIKE '%$search%'";
}

// Hitung total data
$totalQuery = mysqli_query($db, "SELECT COUNT(*) as total FROM tb_kode_guru g $whereClause");
$totalRow = mysqli_fetch_assoc($totalQuery);
$totalData = isset($totalRow['total']) ? (int)$totalRow['total'] : 0;
$totalPages = max(1, ceil($totalData / $limit));

// Ambil data token guru beserta status penggunaannya
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
    ORDER BY g.created_at DESC
    LIMIT $limit OFFSET $offset
");

// Total Token Keseluruhan
$qStatTotal = mysqli_query($db, "SELECT COUNT(*) as total FROM tb_kode_guru");
$statTotal = mysqli_fetch_assoc($qStatTotal)['total'] ?? 0;

// Total Token Sudah Digunakan
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
                <a href="../4_daftar-voter/daftar-voter.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-slate-400 font-medium transition-all duration-300 hover:bg-slate-800/40 hover:text-white group">
                    <i class="bi bi-card-checklist text-lg group-hover:text-indigo-400 transition-colors"></i>
                    <span>Daftar Voter</span>
                </a>
                <a href="../5_token-siswa/token-siswa.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-slate-400 font-medium transition-all duration-300 hover:bg-slate-800/40 hover:text-white group">
                    <i class="bi bi-key text-lg group-hover:text-indigo-400 transition-colors"></i>
                    <span>Token Siswa</span>
                </a>
                <a href="token-guru.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl bg-indigo-600/10 text-indigo-300 border-l-4 border-indigo-500 font-semibold group">
                    <i class="bi bi-shield-lock text-lg text-indigo-400"></i>
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
                <h1 class="font-outfit text-3xl font-extrabold bg-gradient-to-r from-white to-indigo-200 bg-clip-text text-transparent">Token Guru & Karyawan</h1>
                <p class="text-slate-400 text-sm mt-1">Mengelola hak akses, me-generate, ekspor, dan me-reset kode unik voting guru / staff</p>
            </div>
        </header>

        <!-- Message Alert -->
        <?php if (!empty($message)): ?>
            <div class="bg-indigo-500/10 border border-indigo-500/20 text-indigo-200 p-4 rounded-2xl text-sm flex items-center gap-3">
                <i class="bi bi-info-circle text-indigo-400 flex-shrink-0 text-lg"></i>
                <span><?= $message; ?></span>
            </div>
        <?php endif; ?>

        <!-- Stats Widgets -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-3xl p-6 shadow-2xl relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 w-28 h-28 bg-indigo-500/5 rounded-full group-hover:scale-125 transition-transform duration-500"></div>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-indigo-500/10 border border-indigo-500/20 rounded-2xl flex items-center justify-center text-indigo-400 text-xl">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Total Token Guru</p>
                        <p class="text-3xl font-outfit font-extrabold mt-1 text-white"><?= $statTotal ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-3xl p-6 shadow-2xl relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 w-28 h-28 bg-emerald-500/5 rounded-full group-hover:scale-125 transition-transform duration-500"></div>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl flex items-center justify-center text-emerald-400 text-xl">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Sudah Digunakan</p>
                        <p class="text-3xl font-outfit font-extrabold mt-1 text-emerald-400"><?= $statUsed ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-3xl p-6 shadow-2xl relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 w-28 h-28 bg-red-500/5 rounded-full group-hover:scale-125 transition-transform duration-500"></div>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-red-500/10 border border-red-500/20 rounded-2xl flex items-center justify-center text-red-400 text-xl">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Belum Digunakan</p>
                        <p class="text-3xl font-outfit font-extrabold mt-1 text-red-400"><?= $statUnused ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Forms Grid Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Generate Token Card -->
            <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-[28px] shadow-2xl p-7 flex flex-col gap-5 h-fit lg:col-span-1">
                <div>
                    <h3 class="font-outfit text-lg font-bold text-slate-200 flex items-center gap-2">
                        <i class="bi bi-plus-circle text-indigo-400"></i>
                        <span>Buat Token Baru</span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-1">Tambah token guru secara manual atau generate otomatis</p>
                </div>

                <!-- Manual -->
                <form method="POST" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="font-outfit font-semibold text-xs text-slate-400 tracking-wider" for="kode_manual">Token Manual</label>
                        <input type="text" id="kode_manual" name="kode_manual" placeholder="Masukkan token guru manual" pattern="[a-zA-Z]+" minlength="3" maxlength="100" class="py-3 px-4 rounded-xl bg-slate-950/65 border border-white/10 font-mono text-sm text-white w-full focus:outline-none focus:border-indigo-500 focus:bg-slate-950/85 lowercase" required autocomplete="off">
                        <p class="text-[11px] text-slate-500">Huruf saja (a-z), 3–100 karakter. Disimpan otomatis huruf kecil.</p>
                    </div>
                    <button type="submit" name="add_manual" class="w-full py-3 rounded-xl bg-purple-600 border border-purple-500 hover:bg-purple-500 hover:border-purple-400 font-bold text-sm tracking-wide text-white transition-all duration-300">
                        Tambah Token Manual
                    </button>
                </form>

                <div class="flex items-center gap-3 text-xs text-slate-500">
                    <span class="flex-1 h-px bg-white/10"></span>
                    <span>atau</span>
                    <span class="flex-1 h-px bg-white/10"></span>
                </div>

                <!-- Otomatis -->
                <form method="POST" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="font-outfit font-semibold text-xs text-slate-400 tracking-wider" for="jumlah">Jumlah Guru</label>
                        <input type="number" id="jumlah" name="jumlah" min="1" max="100" value="1" placeholder="Contoh: 10" class="py-3 px-4 rounded-xl bg-slate-950/65 border border-white/10 font-sans text-sm text-white w-full focus:outline-none focus:border-indigo-500 focus:bg-slate-950/85" required autocomplete="off">
                        <p class="text-[11px] text-slate-500">Masukkan berapa token guru yang ingin dibuat sekaligus (maks. 100).</p>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Format otomatis: prefix <span class="font-mono text-purple-300">gr</span> + 5 huruf acak (contoh: grabcde).
                    </p>
                    <button type="submit" name="generate" class="w-full py-3 rounded-xl bg-indigo-600 border border-indigo-500 hover:bg-indigo-500 hover:border-indigo-400 font-bold text-sm tracking-wide text-white transition-all duration-300">
                        Generate Token Otomatis
                    </button>
                </form>

                <!-- Danger / Reset Section -->
                <div class="mt-4 pt-5 border-t border-white/5 flex flex-col gap-4">
                    <div>
                        <h4 class="text-xs font-bold text-red-400 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="bi bi-exclamation-triangle"></i>
                            <span>Danger Zone (Reset)</span>
                        </h4>
                        <p class="text-[11px] text-slate-400 mt-1">Menghapus/me-reset SELURUH token yang BELUM digunakan dari database.</p>
                    </div>
                    <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus SELURUH token guru yang BELUM digunakan?')">
                        <button type="submit" name="reset_unused" class="w-full py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 hover:bg-red-500/20 font-bold text-xs transition-colors">
                            Hapus Semua Token Unused
                        </button>
                    </form>
                    <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus SELURUH token guru? Tindakan ini akan menghapus semua token, data voter, dan log vote.')">
                        <button type="submit" name="clear_all_tokens" class="w-full py-3 rounded-xl bg-red-600 border border-red-500 hover:bg-red-500 hover:border-red-400 text-white font-bold text-xs transition-colors">
                            Hapus Semua Token
                        </button>
                    </form>
                </div>
            </div>

            <!-- Token List Table Card -->
            <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-[28px] shadow-2xl p-7 flex flex-col gap-6 lg:col-span-2">
                <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                    <h3 class="font-outfit text-lg font-bold text-slate-200 flex items-center gap-2">
                        <i class="bi bi-list-task text-indigo-400"></i>
                        <span>Token Guru Terdaftar</span>
                    </h3>

                    <!-- Search Form -->
                    <form method="GET" class="relative">
                        <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Cari token..." class="py-2 px-3 pl-8 rounded-xl bg-slate-950/65 border border-white/10 text-xs text-white focus:outline-none focus:border-indigo-500 w-44 sm:w-56 font-medium">
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
                    </form>
                </div>

                <!-- Token List Table -->
                <div class="overflow-x-auto w-full">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-white/5 text-xs text-slate-400 font-bold uppercase tracking-wider">
                                <th class="py-4 px-4 text-left">No</th>
                                <th class="py-4 px-4 text-center">Token Guru</th>
                                <th class="py-4 px-4 text-center">Status Token</th>
                                <th class="py-4 px-4 text-center">Dibuat Pada</th>
                                <th class="py-4 px-4 text-center">Aksi / Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-sm text-slate-200">
                            <?php if ($totalData > 0): $no = $offset + 1;
                                while ($row = mysqli_fetch_assoc($queryTokens)): ?>
                                    <tr class="hover:bg-white/[0.01] transition-colors duration-200">
                                        <td class="py-4 px-4 font-semibold text-slate-400"><?= $no++; ?></td>
                                        <td class="py-4 px-4 text-center">
                                            <span class="font-mono text-purple-300 bg-purple-500/10 border border-purple-500/20 py-1 px-3.5 rounded-lg text-xs font-bold">
                                                <?= htmlspecialchars($row['kode']); ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-center">
                                            <?php if ($row['status_penggunaan'] === 'sudah'): ?>
                                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold py-1 px-3 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                                    Sudah Dipakai
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold py-1 px-3 rounded-full bg-red-500/10 border border-red-500/20 text-red-400 animate-pulse">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                                    Belum Dipakai
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 px-4 text-center text-slate-400 font-semibold text-xs"><?= htmlspecialchars($row['created_at']); ?></td>
                                        <td class="py-4 px-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <?php if ($row['status_penggunaan'] === 'sudah'): ?>
                                                    <a href="?reset_token=<?= $row['id']; ?>" class="px-3 py-1.5 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-400 font-bold text-xs hover:bg-amber-500/20 transition-all duration-300 flex items-center gap-1" onclick="return confirm('Apakah Anda yakin ingin me-reset status penggunaan token guru ini agar dapat digunakan kembali?')">
                                                        <i class="bi bi-arrow-counterclockwise"></i>
                                                        <span>Reset</span>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="?hapus=<?= $row['id']; ?>" class="px-3 py-1.5 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 font-bold text-xs hover:bg-red-500/20 transition-all duration-300 flex items-center gap-1" onclick="return confirm('Hapus token ini?')">
                                                    <i class="bi bi-trash"></i>
                                                    <span>Hapus</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-500">Tidak ada token guru terdaftar.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="flex justify-center gap-1.5 mt-2">
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>" class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs transition-all duration-300 <?= $p == $page ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-800 text-slate-400 border border-white/5 hover:bg-slate-750 hover:text-white' ?>"><?= $p ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>

                <!-- Excel Export & DB Actions -->
                <div class="flex flex-wrap gap-4 border-t border-white/5 pt-5 justify-between items-center mt-2">
                    <div class="flex flex-wrap gap-3">
                        <form method="POST" action="../token/export_token_guru.php">
                            <button type="submit" class="flex items-center gap-2 px-5 py-3 rounded-xl bg-emerald-600 border border-emerald-500 hover:bg-emerald-500 hover:border-emerald-400 hover:shadow-[0_8px_20px_rgba(16,185,129,0.25)] text-white font-bold text-xs transition-all duration-300">
                                <i class="bi bi-file-earmark-excel"></i>
                                <span>Ekspor Token ke Excel</span>
                            </button>
                        </form>

                        <form method="POST" action="" onsubmit="return confirm('Apakah Anda yakin ingin me-reset SEMUA token guru yang telah digunakan? Tindakan ini akan menghapus data voter dan log vote dari token guru terkait.')">
                            <button type="submit" name="reset_all_used" class="flex items-center gap-2 px-5 py-3 rounded-xl bg-amber-600 border border-amber-500 hover:bg-amber-500 hover:border-amber-400 hover:shadow-[0_8px_20px_rgba(245,158,11,0.25)] text-white font-bold text-xs transition-all duration-300">
                                <i class="bi bi-arrow-counterclockwise"></i>
                                <span>Reset Semua Token Terpakai</span>
                            </button>
                        </form>
                    </div>

                    <a href="http://localhost/phpmyadmin/index.php?route=/sql&pos=0&db=db_vote_osis_generate_token&table=tb_kode_guru" target="_blank" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-850 hover:bg-slate-800 text-slate-300 text-xs border border-white/5 transition-all duration-300">
                        <i class="bi bi-database"></i>
                        <span>Buka Database tb_kode_guru</span>
                    </a>
                </div>
            </div>
        </div>
    </main>
</body>

</html>
