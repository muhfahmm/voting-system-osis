<?php
session_start();
require '../../../db/db.php';

if (!isset($_SESSION['login'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$admin = $_SESSION['username'];
$message = '';

if (!isset($_GET['kelas_id'])) {
    header("Location: ../4_daftar-voter/daftar-voter.php");
    exit;
}

$kelas_id = (int)$_GET['kelas_id'];

$qKelas = mysqli_query($db, "SELECT nama_kelas FROM tb_kelas WHERE id = $kelas_id");
if (mysqli_num_rows($qKelas) === 0) {
    header("Location: ../4_daftar-voter/daftar-voter.php");
    exit;
}
$kelas = mysqli_fetch_assoc($qKelas)['nama_kelas'];

if (isset($_GET['hapus_token'])) {
    $id_token = (int)$_GET['hapus_token'];
    $check = mysqli_query($db, "SELECT token FROM tb_buat_token WHERE id = $id_token AND kelas_id = $kelas_id");
    if (mysqli_num_rows($check) > 0) {
        $hapus = mysqli_query($db, "DELETE FROM tb_buat_token WHERE id = $id_token");
        $message = $hapus ? "🗑️ Token berhasil dihapus." : "❌ Gagal menghapus token.";
    } else {
        $message = "⚠️ Token tidak ditemukan.";
    }
}

$qToken = mysqli_query($db, "SELECT * FROM tb_buat_token WHERE kelas_id = $kelas_id ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Token - <?= htmlspecialchars($kelas) ?> - Voting OSIS</title>
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
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>input:focus, select:focus, textarea:focus, button:focus { outline: none !important; box-shadow: none !important; }</style>
</head>

<body class="bg-[#0b0f19] text-[#f1f5f9] min-h-screen flex font-sans relative overflow-x-hidden">
    
    
    

    <!-- Sidebar Navigation -->
    <aside class="w-64 bg-slate-900/60 backdrop-blur-xl border-r border-white/5 flex flex-col justify-between shrink-0 min-h-screen z-10">
        <div class="flex flex-col gap-4 p-4">
            <!-- Brand / Header -->
            <div class="flex items-center gap-3 border-b border-white/5 pb-6">
                <div class="w-10 h-10 bg-emerald-600/20 border border-emerald-500/35 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="font-sans font-extrabold text-lg bg-emerald-600 bg-clip-text text-transparent">Admin Panel</h2>
                    <p class="text-xs text-slate-400 font-semibold tracking-wide">E-VOTING SKALSA</p>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex flex-col gap-1.5">
                <a href="../1_dashboard/dashboard.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-400 font-medium transition-all duration-300 group">
                    <i class="bi bi-speedometer2 text-lg group-transition-colors"></i>
                    <span>Dashboard</span>
                </a>
                <a href="../2_hasil-vote/result.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-400 font-medium transition-all duration-300 group">
                    <i class="bi bi-bar-chart-line text-lg group-transition-colors"></i>
                    <span>Hasil Vote</span>
                </a>
                <a href="../3_kandidat/daftar-kandidat.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-400 font-medium transition-all duration-300 group">
                    <i class="bi bi-people text-lg group-transition-colors"></i>
                    <span>Daftar Kandidat</span>
                </a>
                <a href="../4_daftar-voter/daftar-voter.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg bg-emerald-600/10 text-emerald-300 border-l-4 border-emerald-500 font-semibold group">
                    <i class="bi bi-card-checklist text-lg text-emerald-400"></i>
                    <span>Daftar Voter</span>
                </a>
                <a href="../5_token-siswa/token-siswa.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-400 font-medium transition-all duration-300 group">
                    <i class="bi bi-key text-lg group-transition-colors"></i>
                    <span>Token Siswa</span>
                </a>
                <a href="../6_token-guru/token-guru.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-400 font-medium transition-all duration-300 group">
                    <i class="bi bi-shield-lock text-lg group-transition-colors"></i>
                    <span>Token Guru</span>
                </a>
                <a href="../7_daftar-admin/daftar-admin.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-400 font-medium transition-all duration-300 group">
                    <i class="bi bi-person-workspace text-lg group-transition-colors"></i>
                    <span>Daftar Admin</span>
                </a>
            </nav>
        </div>

        <!-- User / Logout -->
        <div class="p-4 border-t border-white/5 bg-slate-950/20 flex flex-col gap-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-slate-850 border border-white/10 rounded-lg flex items-center justify-center text-slate-300 font-sans font-bold text-sm">
                    <?= strtoupper(substr($admin, 0, 2)) ?>
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Logged In As</p>
                    <p class="text-sm text-slate-200 font-bold truncate"><?= htmlspecialchars($admin) ?></p>
                </div>
            </div>
            <a href="../../auth/logout.php" class="flex items-center justify-center gap-2.5 w-full py-3 rounded-xl bg-red-500/10 text-red-200 border border-red-500/10 font-bold text-sm transition-all duration-300">
                <i class="bi bi-box-arrow-right"></i>
                <span>Keluar (Logout)</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 p-5 md:p-8 z-10 flex flex-col gap-4 w-full">
        <!-- Top bar / Welcome -->
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-white/5 pb-6">
            <div>
                <div class="flex items-center gap-3">
                    <a href="../4_daftar-voter/daftar-voter.php" class="w-10 h-10 bg-slate-900/60 border border-white/5 rounded-xl flex items-center justify-center text-slate-400 transition-all duration-300">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="font-sans text-2xl font-bold bg-emerald-600 bg-clip-text text-transparent">Daftar Token Kelas <?= htmlspecialchars($kelas) ?></h1>
                        <p class="text-slate-400 text-sm mt-1">Melihat rincian token voting terbuat beserta status pemakaian kelas terkait</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Message Alert -->
        <?php if (!empty($message)): ?>
            <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-200 p-4 rounded-2xl text-sm flex items-center gap-3">
                <i class="bi bi-info-circle text-emerald-400 flex-shrink-0 text-lg"></i>
                <span><?= $message; ?></span>
            </div>
        <?php endif; ?>

        <!-- Token Table Section -->
        <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-2xl shadow-2xl p-4 md:p-5 flex flex-col gap-4">
            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-white/5 text-xs text-slate-400 font-bold uppercase tracking-wider">
                            <th class="py-2.5 px-3">No</th>
                            <th class="py-2.5 px-3 text-center">Token Voting</th>
                            <th class="py-2.5 px-3 text-center">Status Token</th>
                            <th class="py-2.5 px-3 text-center">Tanggal Pembuatan</th>
                            <th class="py-2.5 px-3 text-center">Aksi Hapus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-sm text-slate-200">
                        <?php if (mysqli_num_rows($qToken) > 0): $no = 1; ?>
                            <?php while ($row = mysqli_fetch_assoc($qToken)): ?>
                                <tr class="transition-colors duration-200">
                                    <td class="py-2.5 px-3 font-semibold text-slate-400"><?= $no++ ?></td>
                                    <td class="py-2.5 px-3 text-center">
                                        <span class="font-mono text-emerald-300 bg-emerald-500/10 border border-emerald-500/20 py-1 px-3.5 rounded-lg text-xs font-bold">
                                            <?= htmlspecialchars($row['token']); ?>
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        <?php
                                        $status = strtolower(trim($row['status_token'] ?? ''));
                                        if (in_array($status, ['used', 'sudah', 'ya', 'true', '1', 'sudah digunakan'])) {
                                            echo '<span class="inline-flex items-center gap-1.5 text-xs font-semibold py-1 px-3 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                                    Sudah Dipakai
                                                  </span>';
                                        } else {
                                            echo '<span class="inline-flex items-center gap-1.5 text-xs font-semibold py-1 px-3 rounded-full bg-red-500/10 border border-red-500/20 text-red-400 animate-pulse">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                                    Belum Dipakai
                                                  </span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="py-2.5 px-3 text-center text-slate-400 font-semibold"><?= htmlspecialchars($row['created_at'] ?? '-'); ?></td>
                                    <td class="py-2.5 px-3 text-center">
                                        <a href="?kelas_id=<?= $kelas_id; ?>&hapus_token=<?= $row['id']; ?>"
                                           class="px-3 py-1.5 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 font-bold text-xs transition-colors"
                                           onclick="return confirm('Hapus token ini?')">Hapus Token</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-500">Belum ada token dibuat untuk kelas ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>

</html>







