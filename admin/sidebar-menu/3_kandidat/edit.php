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

    
    <main class="flex-1 p-5 md:p-8 z-10 flex flex-col gap-4 w-full ml-64">
        
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-6">
            <div>
                <h1 class="font-sans text-2xl font-bold text-slate-900 flex items-center gap-3">
                    
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                    </svg>
                    Edit Kandidat
                </h1>
                <p class="text-slate-500 text-sm mt-1">Ubah data pasangan calon Ketua dan Wakil Ketua OSIS</p>
            </div>
            <a href="daftar-kandidat.php" class="flex items-center gap-2 px-5 py-3 rounded-xl bg-white border border-slate-200 text-slate-700 font-bold text-sm transition-all duration-300 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali</span>
            </a>
        </header>

        
        <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl shadow-sm p-8 flex flex-col gap-4 max-w-4xl w-full mx-auto transition-all duration-300 ">
            <div>
                <h2 class="font-sans text-base font-bold text-slate-800 flex items-center gap-2.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Form Edit Kandidat</span>
                </h2>
                <p class="text-xs text-slate-500 mt-1">Perbarui informasi pasangan calon nomor urut <span class="font-bold text-emerald-600"><?= $data['nomor_kandidat']; ?></span></p>
            </div>

            <form action="api/proses-edit.php" method="POST" enctype="multipart/form-data" class="flex flex-col gap-4">
                <input type="hidden" name="id" value="<?= $data['id']; ?>">

                
                <div class="flex flex-col gap-2">
                    <label class="font-sans font-semibold text-xs text-slate-600 tracking-wider uppercase flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                        </svg>
                        Nomor Urut Kandidat
                    </label>
                    <input type="text" id="nomor_kandidat" name="nomor_kandidat" value="<?= $data['nomor_kandidat']; ?>" class="py-3 px-4 rounded-xl bg-white border border-slate-200 font-sans text-sm text-slate-700 w-full transition-all duration-300 placeholder-slate-400 focus:outline-none (16, 185, 129,0.15)]" required autocomplete="off">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-slate-200 pt-6">
                    
                    <div class="flex flex-col gap-4">
                        <span class="font-sans font-bold text-sm text-emerald-600 uppercase tracking-wider flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Calon Ketua OSIS
                        </span>
                        
                        <div class="flex flex-col gap-2">
                            <label class="font-sans font-semibold text-xs text-slate-500 tracking-wider" for="nama_ketua">Nama Ketua</label>
                            <input type="text" id="nama_ketua" name="nama_ketua" value="<?= $data['nama_ketua']; ?>" class="py-3 px-4 rounded-xl bg-white border border-slate-200 font-sans text-sm text-slate-700 w-full transition-all duration-300 placeholder-slate-400 focus:outline-none (16, 185, 129,0.15)]" required autocomplete="off">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="font-sans font-semibold text-xs text-slate-500 tracking-wider flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Foto Ketua
                            </label>
                            <div class="preview mb-2">
                                <img id="preview_ketua" src="../../uploads/<?= $data['foto_ketua']; ?>" alt="Foto Ketua" class="w-full aspect-[3/4] object-cover rounded-2xl border border-slate-200 shadow-sm transition-transform duration-300 ">
                            </div>
                            <input type="file" name="foto_ketua" accept="image/*" onchange="previewImage(this, 'preview_ketua')" class="py-2.5 px-4 rounded-xl bg-white border border-slate-200 font-sans text-xs text-slate-600 w-full focus:outline-none file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-600 :bg-emerald-100 transition-all">
                            <p class="text-[11px] text-slate-400">Biarkan kosong jika tidak ingin mengubah foto.</p>
                        </div>
                    </div>

                    
                    <div class="flex flex-col gap-4 border-t md:border-t-0 md:border-l border-slate-200 pt-6 md:pt-0 md:pl-6">
                        <span class="font-sans font-bold text-sm text-emerald-600 uppercase tracking-wider flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Calon Wakil Ketua OSIS
                        </span>
                        
                        <div class="flex flex-col gap-2">
                            <label class="font-sans font-semibold text-xs text-slate-500 tracking-wider" for="nama_wakil">Nama Wakil</label>
                            <input type="text" id="nama_wakil" name="nama_wakil" value="<?= $data['nama_wakil']; ?>" class="py-3 px-4 rounded-xl bg-white border border-slate-200 font-sans text-sm text-slate-700 w-full transition-all duration-300 placeholder-slate-400 focus:outline-none (16, 185, 129,0.15)]" required autocomplete="off">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="font-sans font-semibold text-xs text-slate-500 tracking-wider flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Foto Wakil
                            </label>
                            <div class="preview mb-2">
                                <img id="preview_wakil" src="../../uploads/<?= $data['foto_wakil']; ?>" alt="Foto Wakil" class="w-full aspect-[3/4] object-cover rounded-2xl border border-slate-200 shadow-sm transition-transform duration-300 ">
                            </div>
                            <input type="file" name="foto_wakil" accept="image/*" onchange="previewImage(this, 'preview_wakil')" class="py-2.5 px-4 rounded-xl bg-white border border-slate-200 font-sans text-xs text-slate-600 w-full focus:outline-none file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-600 :bg-emerald-100 transition-all">
                            <p class="text-[11px] text-slate-400">Biarkan kosong jika tidak ingin mengubah foto.</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 border-t border-slate-200 pt-6">
                    <button type="submit" name="edit" class="flex-1 flex items-center justify-center gap-2 py-3.5 px-6 rounded-xl bg-emerald-600 border border-emerald-500 (16, 185, 129,0.35)] text-white font-bold text-sm tracking-wide transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        Simpan Perubahan
                    </button>
                    <a href="daftar-kandidat.php" class="flex items-center justify-center gap-2 py-3.5 px-6 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 font-bold text-sm transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Batal
                    </a>
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
</body>

</html>








