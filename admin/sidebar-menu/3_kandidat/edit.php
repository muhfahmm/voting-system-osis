<?php
session_start();
require '../../../db/db.php';

if (!isset($_SESSION['login'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$admin = $_SESSION['username'];

$id = $_GET['id'];
$query = mysqli_query($db, "SELECT * FROM tb_kandidat WHERE id='$id'");
$data = mysqli_fetch_assoc($query);
if (!$data) {
    echo "Data kandidat tidak ditemukan.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kandidat - Voting OSIS</title>
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

    <!-- Aside Sidebar -->
    <aside id="sidebarMenu" class="w-64 bg-white/95 lg:bg-white/80 backdrop-blur-xl border-r border-slate-200 shadow-xl lg:shadow-sm flex flex-col fixed top-0 left-0 z-40 h-screen transition-transform duration-300 -translate-x-full lg:translate-x-0">
        
        <div class="flex flex-col gap-4 p-4 flex-1 overflow-y-auto">
            
            <div class="flex items-center justify-between border-b border-slate-200 pb-6">
                <div class="flex items-center gap-3">
                    <img src="../../assets/img/logo osis.png" alt="Logo OSIS" class="h-9 object-contain">
                    <div>
                        <h2 class="font-sans font-extrabold text-lg text-slate-900">Admin Panel</h2>
                        <p class="text-xs text-slate-500 font-semibold tracking-wide">E-VOTING SKALSA</p>
                    </div>
                </div>
                <button type="button" onclick="toggleSidebar()" class="lg:hidden p-2 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors">
                    <i class="bi bi-x-lg text-lg"></i>
                </button>
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
                <a href="daftar-kandidat.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg bg-emerald-50 text-emerald-700 border-l-4 border-emerald-500 font-semibold group">
                    <i class="bi bi-people text-lg text-emerald-500"></i>
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

    
    <main class="flex-1 p-5 md:p-8 z-10 flex flex-col gap-4 w-full lg:ml-64">
        
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-6">
            <div class="flex items-center gap-3">
                <button type="button" onclick="toggleSidebar()" class="lg:hidden p-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 font-bold text-lg shadow-sm hover:bg-slate-50 flex items-center justify-center">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="font-sans text-2xl font-bold text-slate-900 flex items-center gap-3">
                        <i class="bi bi-pencil-square text-emerald-500 text-3xl"></i>
                        <span>Edit Kandidat</span>
                    </h1>
                    <p class="text-slate-500 text-sm mt-1">Ubah data pasangan calon Ketua dan Wakil Ketua OSIS</p>
                </div>
            </div>
        </header>

        
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 md:p-8 flex flex-col gap-6 max-w-4xl w-full mx-auto transition-all duration-300">
            <div>
                <h2 class="font-sans text-base font-bold text-slate-800 flex items-center gap-2.5">
                    <i class="bi bi-pencil-square text-emerald-500 text-xl"></i>
                    <span>Form Edit Kandidat</span>
                </h2>
                <p class="text-xs text-slate-500 mt-1">Perbarui informasi pasangan calon nomor urut <span class="font-bold text-emerald-600"><?= htmlspecialchars($data['nomor_kandidat']); ?></span></p>
            </div>

            <form action="api/proses-edit.php" method="POST" enctype="multipart/form-data" class="flex flex-col gap-6">
                <input type="hidden" name="id" value="<?= $data['id']; ?>">

                <div class="flex flex-col gap-1.5 max-w-xs">
                    <label for="nomor_kandidat" class="text-xs font-semibold text-slate-600">Nomor Urut Kandidat</label>
                    <input type="number" id="nomor_kandidat" name="nomor_kandidat" value="<?= htmlspecialchars($data['nomor_kandidat']); ?>" class="w-full py-2.5 px-3.5 rounded-lg bg-white border border-slate-200 text-sm font-medium text-slate-800 focus:border-emerald-500 focus:outline-none" required autocomplete="off">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-slate-100 pt-6">
                    <div class="flex flex-col gap-4">
                        <h3 class="text-sm font-bold text-slate-800 pb-2 border-b border-slate-100 flex items-center gap-2">
                            <i class="bi bi-person-fill text-emerald-500"></i>
                            <span>Calon Ketua OSIS</span>
                        </h3>
                        <div class="flex flex-col gap-1.5">
                            <label for="nama_ketua" class="text-xs font-semibold text-slate-600">Nama Lengkap Ketua</label>
                            <input type="text" id="nama_ketua" name="nama_ketua" value="<?= htmlspecialchars($data['nama_ketua']); ?>" class="w-full py-2.5 px-3.5 rounded-lg bg-white border border-slate-200 text-sm font-medium text-slate-800 focus:border-emerald-500 focus:outline-none" required autocomplete="off">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-xs font-semibold text-slate-600">Foto Ketua Saat Ini & Pratinjau</label>
                            <div class="w-32 aspect-[3/4] overflow-hidden rounded-xl border border-slate-200 shadow-sm bg-slate-50">
                                <img id="preview_ketua" src="../../uploads/<?= htmlspecialchars($data['foto_ketua']); ?>" alt="Foto Ketua" class="w-full h-full object-cover">
                            </div>
                            <input type="file" name="foto_ketua" accept="image/*" onchange="previewImage(this, 'preview_ketua')" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 transition-all">
                            <p class="text-[11px] text-slate-400">Biarkan kosong jika tidak ingin mengunggah foto baru.</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-4 border-t md:border-t-0 md:border-l border-slate-100 pt-6 md:pt-0 md:pl-6">
                        <h3 class="text-sm font-bold text-slate-800 pb-2 border-b border-slate-100 flex items-center gap-2">
                            <i class="bi bi-person-fill text-emerald-500"></i>
                            <span>Calon Wakil Ketua OSIS</span>
                        </h3>
                        <div class="flex flex-col gap-1.5">
                            <label for="nama_wakil" class="text-xs font-semibold text-slate-600">Nama Lengkap Wakil</label>
                            <input type="text" id="nama_wakil" name="nama_wakil" value="<?= htmlspecialchars($data['nama_wakil']); ?>" class="w-full py-2.5 px-3.5 rounded-lg bg-white border border-slate-200 text-sm font-medium text-slate-800 focus:border-emerald-500 focus:outline-none" required autocomplete="off">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-xs font-semibold text-slate-600">Foto Wakil Saat Ini & Pratinjau</label>
                            <div class="w-32 aspect-[3/4] overflow-hidden rounded-xl border border-slate-200 shadow-sm bg-slate-50">
                                <img id="preview_wakil" src="../../uploads/<?= htmlspecialchars($data['foto_wakil']); ?>" alt="Foto Wakil" class="w-full h-full object-cover">
                            </div>
                            <input type="file" name="foto_wakil" accept="image/*" onchange="previewImage(this, 'preview_wakil')" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 transition-all">
                            <p class="text-[11px] text-slate-400">Biarkan kosong jika tidak ingin mengunggah foto baru.</p>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end gap-3">
                    <a href="daftar-kandidat.php" class="py-2.5 px-5 rounded-xl bg-slate-100 text-slate-700 font-bold text-sm hover:bg-slate-200 transition-all flex items-center gap-2">
                        <i class="bi bi-x-lg"></i>
                        <span>Batal</span>
                    </a>
                    <button type="submit" name="edit" class="py-2.5 px-6 rounded-xl bg-emerald-600 text-white font-bold text-sm hover:bg-emerald-700 transition-all shadow-sm flex items-center gap-2">
                        <i class="bi bi-check-lg"></i>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        function previewImage(input, previewId) {
            const file = input.files[0];
            const preview = document.getElementById(previewId);

            if (file) {
                const reader = new FileReader();
                reader.onload = e => {
                    preview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        }
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