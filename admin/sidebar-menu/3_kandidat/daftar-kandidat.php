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
                <a href="daftar-kandidat.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl bg-indigo-600/10 text-indigo-300 border-l-4 border-indigo-500 font-semibold group">
                    <i class="bi bi-people text-lg text-indigo-400"></i>
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
                <h1 class="font-outfit text-3xl font-extrabold bg-gradient-to-r from-white to-indigo-200 bg-clip-text text-transparent">Daftar Kandidat OSIS</h1>
                <p class="text-slate-400 text-sm mt-1">Mengelola data pasangan calon Ketua dan Wakil Ketua OSIS Skalsa</p>
            </div>
            <div class="flex gap-3">
                <a href="#tambahKandidatSec" class="flex items-center gap-2 px-5 py-3 rounded-xl bg-indigo-600 border border-indigo-500 hover:bg-indigo-500 hover:border-indigo-400 hover:shadow-[0_8px_20px_rgba(99,102,241,0.25)] active:translate-y-0.5 text-white font-bold text-sm tracking-wide transition-all duration-300">
                    <i class="bi bi-plus-lg"></i>
                    <span>Tambah Kandidat</span>
                </a>
            </div>
        </header>

        <!-- Candidate Cards Container -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (mysqli_num_rows($query) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($query)): ?>
                    <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-[28px] p-6 shadow-2xl flex flex-col gap-5 transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_12px_24px_-8px_rgba(0,0,0,0.6)]">
                        <!-- Foto Paslon -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="flex flex-col items-center gap-1.5">
                                <img src="../../uploads/<?= $row['foto_ketua']; ?>" alt="Foto Ketua" class="w-full aspect-[3/4] object-cover rounded-2xl border border-white/10 shadow-lg">
                                <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase">Ketua</span>
                            </div>
                            <div class="flex flex-col items-center gap-1.5">
                                <img src="../../uploads/<?= $row['foto_wakil']; ?>" alt="Foto Wakil" class="w-full aspect-[3/4] object-cover rounded-2xl border border-white/10 shadow-lg">
                                <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase">Wakil</span>
                            </div>
                        </div>

                        <!-- Info Paslon -->
                        <div class="text-center flex flex-col gap-2">
                            <span class="inline-block mx-auto px-3.5 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 font-outfit font-extrabold text-xs">
                                Pasangan Nomor <?= $row['nomor_kandidat']; ?>
                            </span>
                            <h3 class="font-outfit text-base font-extrabold text-white mt-1 leading-snug truncate">
                                <?= htmlspecialchars($row['nama_ketua']); ?> & <?= htmlspecialchars($row['nama_wakil']); ?>
                            </h3>
                        </div>

                        <!-- Actions -->
                        <div class="grid grid-cols-2 gap-3 border-t border-white/5 pt-4">
                            <a href="edit.php?id=<?= $row['id']; ?>" class="flex items-center justify-center gap-1.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-750 text-slate-200 font-bold text-xs transition-colors duration-200">
                                <i class="bi bi-pencil-square"></i>
                                <span>Edit</span>
                            </a>
                            <a href="hapus.php?id=<?= $row['id']; ?>" class="flex items-center justify-center gap-1.5 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 border border-red-500/10 hover:border-red-500/30 text-red-200 font-bold text-xs transition-colors duration-200" onclick="return confirm('Yakin ingin menghapus kandidat ini?')">
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
        <div id="tambahKandidatSec" class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-[28px] shadow-2xl p-8 flex flex-col gap-6 max-w-2xl mt-4">
            <div>
                <h2 class="font-outfit text-2xl font-extrabold text-slate-200 flex items-center gap-2.5">
                    <i class="bi bi-person-plus text-indigo-400"></i>
                    <span>Tambah Kandidat Baru</span>
                </h2>
                <p class="text-xs text-slate-400 mt-1">Lengkapi formulir di bawah untuk menambahkan pasangan calon kandidat</p>
            </div>

            <form action="api/proses-tambah.php" method="post" enctype="multipart/form-data" class="flex flex-col gap-5">
                <div class="flex flex-col gap-2">
                    <label class="font-outfit font-semibold text-xs text-slate-300 tracking-wider uppercase" for="nomor_kandidat">Nomor Urut Kandidat</label>
                    <input type="number" id="nomor_kandidat" name="nomor_kandidat" class="py-3 px-4 rounded-xl bg-slate-950/65 border border-white/10 font-sans text-sm text-white w-full transition-all duration-300 placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:bg-slate-950/85 focus:shadow-[0_0_0_4px_rgba(99,102,241,0.15)]" placeholder="Masukkan nomor urut..." required autocomplete="off">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 border-t border-white/5 pt-4">
                    <!-- Ketua Form -->
                    <div class="flex flex-col gap-4">
                        <span class="font-outfit font-bold text-sm text-indigo-300 uppercase tracking-wider">Calon Ketua OSIS</span>
                        
                        <div class="flex flex-col gap-2">
                            <label class="font-outfit font-semibold text-xs text-slate-400 tracking-wider" for="nama_ketua">Nama Ketua</label>
                            <input type="text" id="nama_ketua" name="nama_ketua" class="py-3 px-4 rounded-xl bg-slate-950/65 border border-white/10 font-sans text-sm text-white w-full transition-all duration-300 placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:bg-slate-950/85 focus:shadow-[0_0_0_4px_rgba(99,102,241,0.15)]" placeholder="Nama lengkap ketua..." required autocomplete="off">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="font-outfit font-semibold text-xs text-slate-400 tracking-wider" for="kelas_ketua">Kelas Ketua</label>
                            <input type="text" id="kelas_ketua" name="kelas_ketua" class="py-3 px-4 rounded-xl bg-slate-950/65 border border-white/10 font-sans text-sm text-white w-full transition-all duration-300 placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:bg-slate-950/85 focus:shadow-[0_0_0_4px_rgba(99,102,241,0.15)]" placeholder="Kelas ketua (misal: XII RPL 1)..." required autocomplete="off">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="font-outfit font-semibold text-xs text-slate-400 tracking-wider" for="foto_ketua">Foto Ketua</label>
                            <input type="file" id="foto_ketua" name="foto_ketua" class="py-2.5 px-4 rounded-xl bg-slate-950/65 border border-white/10 font-sans text-xs text-slate-300 w-full focus:outline-none focus:border-indigo-500 focus:bg-slate-950/85" required autocomplete="off">
                        </div>
                    </div>

                    <!-- Wakil Form -->
                    <div class="flex flex-col gap-4 border-t md:border-t-0 md:border-l border-white/5 pt-4 md:pt-0 md:pl-5">
                        <span class="font-outfit font-bold text-sm text-indigo-300 uppercase tracking-wider">Calon Wakil Ketua OSIS</span>
                        
                        <div class="flex flex-col gap-2">
                            <label class="font-outfit font-semibold text-xs text-slate-400 tracking-wider" for="nama_wakil">Nama Wakil</label>
                            <input type="text" id="nama_wakil" name="nama_wakil" class="py-3 px-4 rounded-xl bg-slate-950/65 border border-white/10 font-sans text-sm text-white w-full transition-all duration-300 placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:bg-slate-950/85 focus:shadow-[0_0_0_4px_rgba(99,102,241,0.15)]" placeholder="Nama lengkap wakil..." required autocomplete="off">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="font-outfit font-semibold text-xs text-slate-400 tracking-wider" for="kelas_wakil">Kelas Wakil</label>
                            <input type="text" id="kelas_wakil" name="kelas_wakil" class="py-3 px-4 rounded-xl bg-slate-950/65 border border-white/10 font-sans text-sm text-white w-full transition-all duration-300 placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:bg-slate-950/85 focus:shadow-[0_0_0_4px_rgba(99,102,241,0.15)]" placeholder="Kelas wakil..." required autocomplete="off">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="font-outfit font-semibold text-xs text-slate-400 tracking-wider" for="foto_wakil">Foto Wakil</label>
                            <input type="file" id="foto_wakil" name="foto_wakil" class="py-2.5 px-4 rounded-xl bg-slate-950/65 border border-white/10 font-sans text-xs text-slate-300 w-full focus:outline-none focus:border-indigo-500 focus:bg-slate-950/85" required autocomplete="off">
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-4 border-t border-white/5 pt-5">
                    <button type="submit" class="w-full py-3.5 px-6 rounded-xl bg-indigo-600 border border-indigo-500 hover:bg-indigo-500 hover:border-indigo-400 hover:shadow-[0_8px_20px_rgba(99,102,241,0.35)] active:translate-y-0.5 text-white font-bold text-sm tracking-wide transition-all duration-300">
                        💾 Simpan Data Kandidat
                    </button>
                    <a href="http://localhost/phpmyadmin/index.php?route=/sql&pos=0&db=db_vote_osis_generate_token&table=tb_kandidat" target="_blank" class="flex items-center justify-center gap-2 py-2.5 rounded-xl bg-slate-850 hover:bg-slate-800 text-slate-300 text-xs border border-white/5 transition-all duration-300">
                        <i class="bi bi-database"></i>
                        <span>Buka Database tb_kandidat</span>
                    </a>
                </div>
            </form>
        </div>
    </main>
</body>

</html>
