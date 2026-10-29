<?php
session_start();
require '../../../db/db.php';

if (!isset($_SESSION['login'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$admin = $_SESSION['username'] ?? 'Admin';
$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : (isset($_POST['kelas_id']) ? (int)$_POST['kelas_id'] : 0);

if ($kelas_id <= 0) {
    die("Pilih kelas terlebih dahulu untuk mencetak token.");
}

$qKelas = mysqli_query($db, "SELECT * FROM tb_kelas WHERE id = $kelas_id");
$kelasData = mysqli_fetch_assoc($qKelas);

if (!$kelasData) {
    die("Data kelas tidak ditemukan.");
}

$nama_kelas = $kelasData['nama_kelas'];
$jumlah_siswa = (int)$kelasData['jumlah_siswa'];

$query = mysqli_query($db, "
    SELECT t.*, k.nama_kelas
    FROM tb_buat_token t
    LEFT JOIN tb_kelas k ON t.kelas_id = k.id
    WHERE t.kelas_id = $kelas_id
    ORDER BY t.id ASC
");

$tokens = [];
$sudah_pakai = 0;
$belum_pakai = 0;

while ($row = mysqli_fetch_assoc($query)) {
    $tokens[] = $row;
    if ($row['status_token'] === 'sudah') {
        $sudah_pakai++;
    } else {
        $belum_pakai++;
    }
}
$total_token = count($tokens);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Token Siswa - Kelas <?= htmlspecialchars($nama_kelas); ?></title>
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        input:focus, select:focus, textarea:focus, button:focus { outline: none !important; box-shadow: none !important; }

        /* PRINT STYLES */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            #sidebarMenu, #sidebarOverlay, header, .screen-controls, .print-hidden {
                display: none !important;
            }
            main {
                margin-left: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
            .print-container {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                background: transparent !important;
            }
            .token-grid {
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 8px !important;
            }
            .token-slip {
                border: 1px dashed #64748b !important;
                padding: 8px 10px !important;
                page-break-inside: avoid;
            }
            .slip-token {
                font-size: 16px !important;
                padding: 4px 0 !important;
            }
            @page {
                size: A4 portrait;
                margin: 12mm 10mm;
            }
        }
    </style>
</head>

<body class="bg-[#f8fafc] text-slate-800 min-h-screen flex font-sans relative overflow-x-hidden">

    <!-- Include Shared Sidebar -->
    <?php 
    $activeMenu = 'token_siswa';
    include '../sidebar.php'; 
    ?>

    <!-- Main Content Area (Full-screen Layout) -->
    <main class="flex-1 p-4 md:p-7 z-10 flex flex-col gap-5 w-full lg:ml-64 min-w-0">
        
        <!-- Header Dashboard Style -->
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5">
            <div class="flex items-center gap-3">
                <button type="button" onclick="toggleSidebar()" class="lg:hidden p-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 font-bold text-lg shadow-sm hover:bg-slate-50 flex items-center justify-center">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="font-sans text-xl sm:text-2xl font-bold text-slate-900">Daftar Token Siswa - Kelas <?= htmlspecialchars($nama_kelas); ?></h1>
                    <p class="text-slate-600 text-xs sm:text-sm mt-0.5">Kelola dan pantau seluruh token pemilih kelas <b class="text-emerald-600"><?= htmlspecialchars($nama_kelas); ?></b></p>
                </div>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <a href="../5_token-siswa/token-siswa.php?kelas_id=<?= $kelas_id; ?>" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 font-semibold text-sm transition-all duration-300 shadow-sm hover:bg-slate-50">
                    <i class="bi bi-arrow-left"></i>
                    <span>Kembali ke Kelas</span>
                </a>
                <button type="button" onclick="window.print()" class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 border border-emerald-500 text-white font-bold text-sm transition-all duration-300 shadow-sm">
                    <i class="bi bi-printer-fill"></i>
                    <span>Cetak Lembar Token</span>
                </button>
            </div>
        </header>

        <!-- Stats Overview Cards (Fullscreen Wide) -->
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm flex items-center gap-3.5">
                <div class="w-11 h-11 bg-emerald-100 border border-emerald-200 rounded-xl flex items-center justify-center text-emerald-600 text-xl shrink-0">
                    <i class="bi bi-mortarboard"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs uppercase font-bold text-slate-400">Kelas</p>
                    <p class="text-base sm:text-lg font-extrabold text-slate-900 truncate"><?= htmlspecialchars($nama_kelas); ?></p>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm flex items-center gap-3.5">
                <div class="w-11 h-11 bg-blue-100 border border-blue-200 rounded-xl flex items-center justify-center text-blue-600 text-xl shrink-0">
                    <i class="bi bi-people"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs uppercase font-bold text-slate-400">Jumlah Siswa</p>
                    <p class="text-base sm:text-lg font-extrabold text-slate-900"><?= $jumlah_siswa; ?> <span class="text-xs font-normal text-slate-500">Siswa</span></p>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm flex items-center gap-3.5">
                <div class="w-11 h-11 bg-amber-100 border border-amber-200 rounded-xl flex items-center justify-center text-amber-600 text-xl shrink-0">
                    <i class="bi bi-key"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs uppercase font-bold text-slate-400">Total Token Dibuat</p>
                    <p class="text-base sm:text-lg font-extrabold text-slate-900"><?= $total_token; ?> <span class="text-xs font-normal text-slate-500">Token</span></p>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm flex items-center gap-3.5">
                <div class="w-11 h-11 bg-purple-100 border border-purple-200 rounded-xl flex items-center justify-center text-purple-600 text-xl shrink-0">
                    <i class="bi bi-pie-chart"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs uppercase font-bold text-slate-400">Status Penggunaan</p>
                    <p class="text-xs sm:text-sm font-bold text-slate-900">
                        <span class="text-emerald-600 font-extrabold"><?= $belum_pakai; ?> Aktif</span> / 
                        <span class="text-red-500 font-extrabold"><?= $sudah_pakai; ?> Terpakai</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Mode Selector Bar (Screen Only) -->
        <div class="screen-controls bg-white border border-slate-200 rounded-2xl p-3 shadow-sm flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <button type="button" id="btnModeGrid" onclick="switchView('grid')" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-200 bg-emerald-600 text-white shadow-sm flex items-center gap-2 cursor-pointer">
                    <i class="bi bi-grid-3x3-gap-fill"></i>
                    <span>Format Kupon Gunting</span>
                </button>
                <button type="button" id="btnModeTable" onclick="switchView('table')" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all duration-200 bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center gap-2 cursor-pointer">
                    <i class="bi bi-table"></i>
                    <span>Format Tabel Presensi</span>
                </button>
            </div>
            <div class="text-xs text-slate-500 font-medium flex items-center gap-1.5 px-2">
                <i class="bi bi-info-circle text-emerald-600"></i>
                <span>Menampilkan <?= $total_token; ?> token untuk kelas <?= htmlspecialchars($nama_kelas); ?></span>
            </div>
        </div>

        <!-- Content Sheet Area (Fullscreen card on screen, clean layout for print) -->
        <div class="print-container bg-white border border-slate-200 rounded-3xl p-5 sm:p-7 shadow-sm">
            
            <!-- TAMPILAN 1: GRID KUPON GUNTING -->
            <div id="gridWrap">
                <?php if ($total_token > 0): ?>
                    <div class="token-grid grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                        <?php foreach ($tokens as $idx => $t): ?>
                            <div class="token-slip border-2 border-dashed border-slate-300 rounded-2xl p-4 bg-slate-50/60 hover:bg-white hover:border-emerald-500 transition-all flex flex-col justify-between shadow-xs">
                                <div class="flex items-center justify-between border-b border-slate-200 pb-2 mb-2">
                                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">E-VOTING OSIS</span>
                                    <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200"><?= htmlspecialchars($nama_kelas); ?></span>
                                </div>
                                <div class="slip-token font-mono font-black text-xl sm:text-2xl text-slate-900 text-center tracking-widest py-3 my-1 bg-white rounded-xl border border-slate-200 shadow-inner">
                                    <?= htmlspecialchars($t['token']); ?>
                                </div>
                                <div class="flex items-center justify-between text-[11px] text-slate-400 pt-2 border-t border-slate-100">
                                    <span class="font-bold">No. #<?= $idx + 1; ?></span>
                                    <span class="px-2 py-0.5 rounded font-bold text-[10px] <?= $t['status_token'] === 'sudah' ? 'bg-red-50 text-red-600 border border-red-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'; ?>">
                                        <?= $t['status_token'] === 'sudah' ? 'TERPAKAI' : 'AKTIF'; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-16 text-slate-400">
                        <i class="bi bi-key text-4xl block mb-2 text-slate-300"></i>
                        <p class="font-medium">Belum ada token yang dibuat untuk kelas ini.</p>
                        <a href="../5_token-siswa/token-siswa.php?kelas_id=<?= $kelas_id; ?>" class="inline-flex items-center gap-1.5 mt-3 text-sm text-emerald-600 font-bold hover:underline">
                            <i class="bi bi-plus-circle"></i> Buat Token Sekarang
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TAMPILAN 2: TABEL PRESENSI -->
            <div id="tableWrap" class="hidden overflow-x-auto">
                <?php if ($total_token > 0): ?>
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-y border-slate-200 text-slate-700 text-xs uppercase tracking-wider">
                                <th class="py-3 px-4 text-center w-12 font-bold">No</th>
                                <th class="py-3 px-4 text-center w-40 font-bold">Token Voting</th>
                                <th class="py-3 px-4 text-center w-28 font-bold">Status</th>
                                <th class="py-3 px-4 font-bold">Nama Siswa Penerima Token</th>
                                <th class="py-3 px-4 text-center w-44 font-bold">Paraf / Tanda Tangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($tokens as $idx => $t): ?>
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="py-3 px-4 text-center text-slate-500 font-medium"><?= $idx + 1; ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="inline-block px-3 py-1 font-mono font-bold text-sm rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <?= htmlspecialchars($t['token']); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <?php if ($t['status_token'] === 'sudah'): ?>
                                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-red-50 text-red-600 border border-red-200">Terpakai</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 border-b border-dashed border-slate-200 text-slate-400 text-xs italic">
                                        ...................................................
                                    </td>
                                    <td class="py-3 px-4 text-center text-slate-400 text-xs">
                                        <?= $idx + 1; ?>. ........................
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center text-slate-400 py-12">Belum ada token yang dibuat untuk kelas ini.</p>
                <?php endif; ?>
            </div>

        </div>

    </main>

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

        function switchView(mode) {
            const grid = document.getElementById('gridWrap');
            const table = document.getElementById('tableWrap');
            const btnGrid = document.getElementById('btnModeGrid');
            const btnTable = document.getElementById('btnModeTable');

            if (mode === 'grid') {
                grid.classList.remove('hidden');
                table.classList.add('hidden');
                btnGrid.className = 'px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-200 bg-emerald-600 text-white shadow-sm flex items-center gap-2 cursor-pointer';
                btnTable.className = 'px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all duration-200 bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center gap-2 cursor-pointer';
            } else {
                grid.classList.add('hidden');
                table.classList.remove('hidden');
                btnTable.className = 'px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-200 bg-emerald-600 text-white shadow-sm flex items-center gap-2 cursor-pointer';
                btnGrid.className = 'px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all duration-200 bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center gap-2 cursor-pointer';
            }
        }
    </script>

</body>
</html>
