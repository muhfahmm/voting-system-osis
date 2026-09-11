<?php
session_start();
require '../../../db/db.php';

if (!isset($_SESSION['login'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$admin = $_SESSION['username'];
$query = mysqli_query($db, "SELECT * FROM tb_kandidat ORDER BY nomor_kandidat ASC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Kandidat - Voting OSIS</title>
    <link rel="icon" href="../../assets/img/logo osis.png">
    
    <!-- Tailwind Play CDN -->
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
</head>

<body class="bg-[#f8fafc] text-slate-800 min-h-screen flex font-sans relative overflow-x-hidden">
    
    
    

    <!-- Sidebar Navigation (FIXED) -->
    <aside class="w-72 bg-white/80 backdrop-blur-xl border-r border-slate-200 shadow-sm flex flex-col fixed top-0 left-0 z-20 h-screen">
        <!-- Bagian Atas: Brand & Navigasi (Bisa di-scroll dalam sidebar) -->
        <div class="flex flex-col gap-8 p-6 flex-1 overflow-y-auto">
            <div class="flex items-center gap-3 border-b border-slate-200 pb-6">
                <img src="../../assets/img/logo osis.png" alt="Logo OSIS" class="h-9 object-contain">
                <div>
                    <h2 class="font-sans font-extrabold text-lg text-slate-900">Admin Panel</h2>
                    <p class="text-xs text-slate-500 font-semibold tracking-wide">E-VOTING SKALSA</p>
                </div>
            </div>

            <nav class="flex flex-col gap-1.5">
                <a href="../1_dashboard/dashboard.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-slate-600 font-medium transition-all duration-300 group">
                    <i class="bi bi-speedometer2 text-lg group-transition-colors"></i>
                    <span>Dashboard</span>
                </a>
                <a href="../2_hasil-vote/result.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-slate-600 font-medium transition-all duration-300 group">
                    <i class="bi bi-bar-chart-line text-lg group-transition-colors"></i>
                    <span>Hasil Vote</span>
                </a>
                <a href="daftar-kandidat.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl bg-emerald-50 text-emerald-700 border-l-4 border-emerald-500 font-semibold group">
                    <i class="bi bi-people text-lg text-emerald-500"></i>
                    <span>Daftar Kandidat</span>
                </a>
                <a href="../4_daftar-voter/daftar-voter.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-slate-600 font-medium transition-all duration-300 group">
                    <i class="bi bi-card-checklist text-lg group-transition-colors"></i>
                    <span>Daftar Voter</span>
                </a>
                <a href="../5_token-siswa/token-siswa.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-slate-600 font-medium transition-all duration-300 group">
                    <i class="bi bi-key text-lg group-transition-colors"></i>
                    <span>Token Siswa</span>
                </a>
                <a href="../6_token-guru/token-guru.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-slate-600 font-medium transition-all duration-300 group">
                    <i class="bi bi-shield-lock text-lg group-transition-colors"></i>
                    <span>Token Guru</span>
                </a>
                <a href="../7_daftar-admin/daftar-admin.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-slate-600 font-medium transition-all duration-300 group">
                    <i class="bi bi-person-workspace text-lg group-transition-colors"></i>
                    <span>Daftar Admin</span>
                </a>
            </nav>
        </div>

        <!-- Bagian Bawah: User / Logout (Tetap terlihat) -->
        <div class="p-6 border-t border-slate-200 bg-slate-50 flex flex-col gap-4 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-white border border-slate-200 rounded-lg flex items-center justify-center text-slate-700 font-sans font-bold text-sm">
                    <?= strtoupper(substr($admin, 0, 2)) ?>
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Logged In As</p>
                    <p class="text-sm text-slate-700 font-bold truncate"><?= htmlspecialchars($admin) ?></p>
                </div>
            </div>
            <a href="../../auth/logout.php" class="flex items-center justify-center gap-2.5 w-full py-3 rounded-xl bg-red-50 text-red-600 border border-red-200 font-bold text-sm transition-all duration-300">
                <i class="bi bi-box-arrow-right"></i>
                <span>Keluar (Logout)</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Area (Diberi margin kiri agar tidak tertutup sidebar) -->
    <main class="flex-1 p-8 lg:p-12 z-10 flex flex-col gap-8 w-full ml-72">
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-6">
            <div>
                <h1 class="font-sans text-3xl font-extrabold text-slate-900">Daftar Kandidat OSIS</h1>
            </div>
            <div class="flex gap-3">
                <a href="#tambahKandidatSec" class="flex items-center gap-2 px-5 py-3 rounded-xl bg-emerald-600 border border-emerald-500 (16, 185, 129,0.25)] text-white font-bold text-sm tracking-wide transition-all duration-300">
                    <i class="bi bi-plus-lg"></i>
                    <span>Tambah Kandidat</span>
                </a>
            </div>
        </header>

        <!-- Candidate Cards Container -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (mysqli_num_rows($query) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($query)): ?>
                    <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-[28px] p-6 shadow-sm flex flex-col gap-5 transition-all duration-300 ">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="flex flex-col items-center gap-1.5">
                                <img src="../../uploads/<?= $row['foto_ketua']; ?>" alt="Foto Ketua" class="w-full aspect-[3/4] object-cover rounded-2xl border border-slate-200 shadow-sm">
                                <span class="text-[10px] font-bold text-slate-500 tracking-wider uppercase">Ketua</span>
                            </div>
                            <div class="flex flex-col items-center gap-1.5">
                                <img src="../../uploads/<?= $row['foto_wakil']; ?>" alt="Foto Wakil" class="w-full aspect-[3/4] object-cover rounded-2xl border border-slate-200 shadow-sm">
                                <span class="text-[10px] font-bold text-slate-500 tracking-wider uppercase">Wakil</span>
                            </div>
                        </div>

                        <div class="text-center flex flex-col gap-2">
                            <span class="inline-block mx-auto px-3.5 py-1 rounded-full bg-emerald-100 border border-emerald-200 text-emerald-700 font-sans font-extrabold text-xs">
                                Pasangan Nomor <?= $row['nomor_kandidat']; ?>
                            </span>
                            <h3 class="font-sans text-base font-extrabold text-slate-900 mt-1 leading-snug truncate">
                                <?= htmlspecialchars($row['nama_ketua']); ?> & <?= htmlspecialchars($row['nama_wakil']); ?>
                            </h3>
                        </div>

                        <div class="grid grid-cols-2 gap-3 border-t border-slate-200 pt-4">
                            <a href="edit.php?id=<?= $row['id']; ?>" class="flex items-center justify-center gap-1.5 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs transition-colors duration-200">
                                <i class="bi bi-pencil-square"></i>
                                <span>Edit</span>
                            </a>
                            <a href="hapus.php?id=<?= $row['id']; ?>" class="flex items-center justify-center gap-1.5 py-2.5 rounded-xl bg-red-50 border border-red-200 text-red-600 font-bold text-xs transition-colors duration-200" onclick="return confirm('Yakin ingin menghapus kandidat ini?')">
                                <i class="bi bi-trash"></i>
                                <span>Hapus</span>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-full py-12 text-center text-slate-500">Belum ada kandidat ditambahkan.</div>
            <?php endif; ?>
        </div>

        <!-- Add Candidate Section -->
        <div id="tambahKandidatSec" class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-[28px] shadow-sm p-8 flex flex-col gap-6 max-w-2xl mt-4">
            <div>
                <h2 class="font-sans text-2xl font-extrabold text-slate-800 flex items-center gap-2.5">
                    <i class="bi bi-person-plus text-emerald-500"></i>
                    <span>Tambah Kandidat Baru</span>
                </h2>
                <p class="text-xs text-slate-500 mt-1">Lengkapi formulir di bawah untuk menambahkan pasangan calon kandidat</p>
            </div>

            <form action="api/proses-tambah.php" method="post" enctype="multipart/form-data" class="flex flex-col gap-5">
                <div class="flex flex-col gap-2">
                    <label class="font-sans font-semibold text-xs text-slate-600 tracking-wider uppercase" for="nomor_kandidat">Nomor Urut Kandidat</label>
                    <input type="number" id="nomor_kandidat" name="nomor_kandidat" class="py-3 px-4 rounded-xl bg-white border border-slate-200 font-sans text-sm text-slate-700 w-full transition-all duration-300 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:shadow-[0_0_0_4px_rgba(16, 185, 129,0.15)]" placeholder="Masukkan nomor urut..." required autocomplete="off">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 border-t border-slate-200 pt-4">
                    <div class="flex flex-col gap-4">
                        <span class="font-sans font-bold text-sm text-emerald-600 uppercase tracking-wider">Calon Ketua OSIS</span>
                        
                        <div class="flex flex-col gap-2">
                            <label class="font-sans font-semibold text-xs text-slate-500 tracking-wider" for="nama_ketua">Nama Ketua</label>
                            <input type="text" id="nama_ketua" name="nama_ketua" class="py-3 px-4 rounded-xl bg-white border border-slate-200 font-sans text-sm text-slate-700 w-full transition-all duration-300 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:shadow-[0_0_0_4px_rgba(16, 185, 129,0.15)]" placeholder="Nama lengkap ketua..." required autocomplete="off">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="font-sans font-semibold text-xs text-slate-500 tracking-wider" for="kelas_ketua">Kelas Ketua</label>
                            <input type="text" id="kelas_ketua" name="kelas_ketua" class="py-3 px-4 rounded-xl bg-white border border-slate-200 font-sans text-sm text-slate-700 w-full transition-all duration-300 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:shadow-[0_0_0_4px_rgba(16, 185, 129,0.15)]" placeholder="Kelas ketua (misal: XII RPL 1)..." required autocomplete="off">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="font-sans font-semibold text-xs text-slate-500 tracking-wider" for="foto_ketua">Foto Ketua</label>
                            <input type="file" id="foto_ketua" name="foto_ketua" class="py-2.5 px-4 rounded-xl bg-white border border-slate-200 font-sans text-xs text-slate-600 w-full focus:outline-none focus:border-emerald-500" required>
                        </div>
                    </div>

                    <div class="flex flex-col gap-4 border-t md:border-t-0 md:border-l border-slate-200 pt-4 md:pt-0 md:pl-5">
                        <span class="font-sans font-bold text-sm text-emerald-600 uppercase tracking-wider">Calon Wakil Ketua OSIS</span>
                        
                        <div class="flex flex-col gap-2">
                            <label class="font-sans font-semibold text-xs text-slate-500 tracking-wider" for="nama_wakil">Nama Wakil</label>
                            <input type="text" id="nama_wakil" name="nama_wakil" class="py-3 px-4 rounded-xl bg-white border border-slate-200 font-sans text-sm text-slate-700 w-full transition-all duration-300 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:shadow-[0_0_0_4px_rgba(16, 185, 129,0.15)]" placeholder="Nama lengkap wakil..." required autocomplete="off">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="font-sans font-semibold text-xs text-slate-500 tracking-wider" for="kelas_wakil">Kelas Wakil</label>
                            <input type="text" id="kelas_wakil" name="kelas_wakil" class="py-3 px-4 rounded-xl bg-white border border-slate-200 font-sans text-sm text-slate-700 w-full transition-all duration-300 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:shadow-[0_0_0_4px_rgba(16, 185, 129,0.15)]" placeholder="Kelas wakil..." required autocomplete="off">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="font-sans font-semibold text-xs text-slate-500 tracking-wider" for="foto_wakil">Foto Wakil</label>
                            <input type="file" id="foto_wakil" name="foto_wakil" class="py-2.5 px-4 rounded-xl bg-white border border-slate-200 font-sans text-xs text-slate-600 w-full focus:outline-none focus:border-emerald-500" required>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-4 border-t border-slate-200 pt-5">
                    <button type="submit" class="w-full py-3.5 px-6 rounded-xl bg-emerald-600 border border-emerald-500 (16, 185, 129,0.35)] text-white font-bold text-sm tracking-wide transition-all duration-300">
                        💾 Simpan Data Kandidat
                    </button>
                    <a href="http://localhost/phpmyadmin/index.php?route=/sql&pos=0&db=db_vote_osis_generate_token&table=tb_kandidat" target="_blank" class="flex items-center justify-center gap-2 py-2.5 rounded-xl bg-slate-100 text-slate-600 text-xs border border-slate-200 transition-all duration-300">
                        <i class="bi bi-database"></i>
                        <span>Buka Database tb_kandidat</span>
                    </a>
                </div>
            </form>
        </div>
    </main>
</body>

</html>




