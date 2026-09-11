<?php
session_start();
require '../../../db/db.php';

if (!isset($_SESSION['login'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$admin = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kandidat - Voting OSIS</title>
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
    <style>input:focus, select:focus, textarea:focus, button:focus { outline: none !important; box-shadow: none !important; }</style>
</head>

<body class="bg-[#f8fafc] text-slate-800 min-h-screen flex font-sans relative overflow-x-hidden">
    
    <!-- Sidebar Navigation (FIXED) -->
    <aside class="w-64 bg-white/80 backdrop-blur-xl border-r border-slate-200 shadow-sm flex flex-col fixed top-0 left-0 z-20 h-screen">
        <!-- Bagian Atas: Brand & Navigasi (Bisa di-scroll dalam sidebar) -->
        <div class="flex flex-col gap-4 p-4 flex-1 overflow-y-auto">
            <!-- Brand / Header -->
            <div class="flex items-center gap-3 border-b border-slate-200 pb-6">
                <img src="../../assets/img/logo osis.png" alt="Logo OSIS" class="h-9 object-contain">
                <div>
                    <h2 class="font-sans font-extrabold text-lg text-slate-900">Admin Panel</h2>
                    <p class="text-xs text-slate-500 font-semibold tracking-wide">E-VOTING SKALSA</p>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex flex-col gap-1.5">
                <a href="../1_dashboard/dashboard.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-600 font-medium transition-all duration-300 group hover:bg-slate-100/80">
                    <i class="bi bi-speedometer2 text-lg group-hover:text-emerald-600 transition-colors"></i>
                    <span>Dashboard</span>
                </a>
                <a href="../2_hasil-vote/result.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-600 font-medium transition-all duration-300 group hover:bg-slate-100/80">
                    <i class="bi bi-bar-chart-line text-lg group-hover:text-emerald-600 transition-colors"></i>
                    <span>Hasil Vote</span>
                </a>
                <a href="daftar-kandidat.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg bg-emerald-50 text-emerald-700 border-l-4 border-emerald-500 font-semibold group">
                    <i class="bi bi-people text-lg text-emerald-500"></i>
                    <span>Daftar Kandidat</span>
                </a>
                <a href="../4_daftar-voter/daftar-voter.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-600 font-medium transition-all duration-300 group hover:bg-slate-100/80">
                    <i class="bi bi-card-checklist text-lg group-hover:text-emerald-600 transition-colors"></i>
                    <span>Daftar Voter</span>
                </a>
                <a href="../5_token-siswa/token-siswa.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-600 font-medium transition-all duration-300 group hover:bg-slate-100/80">
                    <i class="bi bi-key text-lg group-hover:text-emerald-600 transition-colors"></i>
                    <span>Token Siswa</span>
                </a>
                <a href="../6_token-guru/token-guru.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-600 font-medium transition-all duration-300 group hover:bg-slate-100/80">
                    <i class="bi bi-shield-lock text-lg group-hover:text-emerald-600 transition-colors"></i>
                    <span>Token Guru</span>
                </a>
                <a href="../7_daftar-admin/daftar-admin.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg text-slate-600 font-medium transition-all duration-300 group hover:bg-slate-100/80">
                    <i class="bi bi-person-workspace text-lg group-hover:text-emerald-600 transition-colors"></i>
                    <span>Daftar Admin</span>
                </a>
            </nav>
        </div>

        <!-- User / Logout -->
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
            <a href="../../auth/logout.php" class="flex items-center justify-center gap-2.5 w-full py-2.5 rounded-lg bg-red-50 text-red-600 border border-red-200 font-bold text-sm transition-all duration-300 hover:bg-red-100">
                <i class="bi bi-box-arrow-right"></i>
                <span>Keluar (Logout)</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 p-5 md:p-8 z-10 flex flex-col gap-6 w-full ml-64 max-w-4xl">
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-6">
            <div>
                <h1 class="font-sans text-2xl font-bold text-slate-900">Tambah Pasangan Kandidat</h1>
                <p class="text-slate-500 text-sm mt-1">Masukkan data lengkap Calon Ketua & Wakil Ketua OSIS</p>
            </div>
            <a href="daftar-kandidat.php" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 font-semibold text-sm transition-all duration-300 hover:bg-slate-200 self-start sm:self-auto">
                <i class="bi bi-arrow-left"></i>
                <span>Kembali</span>
            </a>
        </header>

        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            <form action="api/proses-tambah.php" method="post" enctype="multipart/form-data" class="flex flex-col gap-6">
                <div class="flex flex-col gap-2">
                    <label for="nomor_kandidat" class="text-xs font-bold text-slate-700 uppercase tracking-wider">Nomor Pasangan Kandidat</label>
                    <input type="number" id="nomor_kandidat" name="nomor_kandidat" placeholder="Contoh: 1" required
                           class="w-full py-3 px-4 rounded-xl bg-slate-50 border border-slate-200 text-sm font-medium text-slate-800 focus:bg-white focus:border-emerald-500 transition-all">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-100">
                    <!-- Data Calon Ketua -->
                    <div class="flex flex-col gap-4 bg-slate-50/70 p-5 rounded-xl border border-slate-200">
                        <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2 text-emerald-600">
                            <i class="bi bi-person-badge"></i> Data Calon Ketua
                        </h3>
                        <div class="flex flex-col gap-1.5">
                            <label for="nama_ketua" class="text-xs font-semibold text-slate-600">Nama Lengkap Ketua</label>
                            <input type="text" id="nama_ketua" name="nama_ketua" placeholder="Masukkan nama ketua..." required
                                   class="w-full py-2.5 px-3.5 rounded-lg bg-white border border-slate-200 text-sm font-medium text-slate-800 focus:border-emerald-500">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="kelas_ketua" class="text-xs font-semibold text-slate-600">Kelas Ketua</label>
                            <input type="text" id="kelas_ketua" name="kelas_ketua" placeholder="Contoh: XI PPLG 1" required
                                   class="w-full py-2.5 px-3.5 rounded-lg bg-white border border-slate-200 text-sm font-medium text-slate-800 focus:border-emerald-500">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="foto_ketua" class="text-xs font-semibold text-slate-600">Foto Ketua</label>
                            <input type="file" id="foto_ketua" name="foto_ketua" required
                                   class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                        </div>
                    </div>

                    <!-- Data Calon Wakil -->
                    <div class="flex flex-col gap-4 bg-slate-50/70 p-5 rounded-xl border border-slate-200">
                        <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2 text-emerald-600">
                            <i class="bi bi-person-badge"></i> Data Calon Wakil
                        </h3>
                        <div class="flex flex-col gap-1.5">
                            <label for="nama_wakil" class="text-xs font-semibold text-slate-600">Nama Lengkap Wakil</label>
                            <input type="text" id="nama_wakil" name="nama_wakil" placeholder="Masukkan nama wakil..." required
                                   class="w-full py-2.5 px-3.5 rounded-lg bg-white border border-slate-200 text-sm font-medium text-slate-800 focus:border-emerald-500">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="kelas_wakil" class="text-xs font-semibold text-slate-600">Kelas Wakil</label>
                            <input type="text" id="kelas_wakil" name="kelas_wakil" placeholder="Contoh: X PPLG 2" required
                                   class="w-full py-2.5 px-3.5 rounded-lg bg-white border border-slate-200 text-sm font-medium text-slate-800 focus:border-emerald-500">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="foto_wakil" class="text-xs font-semibold text-slate-600">Foto Wakil</label>
                            <input type="file" id="foto_wakil" name="foto_wakil" required
                                   class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end gap-3">
                    <a href="daftar-kandidat.php" class="py-3 px-5 rounded-xl bg-slate-100 text-slate-700 font-bold text-sm hover:bg-slate-200 transition-all">Batal</a>
                    <button type="submit" class="py-3 px-6 rounded-xl bg-emerald-600 text-white font-bold text-sm hover:bg-emerald-700 transition-all shadow-sm flex items-center gap-2">
                        <i class="bi bi-check-lg"></i>
                        <span>Simpan Kandidat</span>
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>

</html>
