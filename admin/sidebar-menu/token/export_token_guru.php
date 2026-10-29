<?php
session_start();
require '../../../db/db.php';

if (!isset($_SESSION['login'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$admin = $_SESSION['username'] ?? 'Admin';

$query = mysqli_query($db, "
    SELECT 
        g.*,
        CASE 
            WHEN v.id IS NOT NULL THEN 'Sudah Digunakan'
            ELSE 'Belum Digunakan'
        END AS status_penggunaan
    FROM tb_kode_guru g
    LEFT JOIN tb_voter v ON g.id = v.kode_guru_id
    ORDER BY g.id ASC
");

$tokens = [];
$sudah_pakai = 0;
$belum_pakai = 0;

while ($row = mysqli_fetch_assoc($query)) {
    $tokens[] = $row;
    if ($row['status_penggunaan'] === 'Sudah Digunakan') {
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
    <title>Lembar Token Guru & Karyawan</title>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        input:focus, select:focus, textarea:focus, button:focus { outline: none !important; box-shadow: none !important; }

        /* PRINT STYLES */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            #sidebarMenu, #sidebarOverlay, header, footer, .screen-controls, .print-hidden, #statsOverview {
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
                margin: 0 !important;
                background: transparent !important;
            }
            .token-grid {
                display: grid !important;
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 8px !important;
            }
            .token-slip {
                border: 1.5px dashed #64748b !important;
                padding: 8px 10px !important;
                page-break-inside: avoid;
                break-inside: avoid;
                background: #ffffff !important;
            }
            .slip-token {
                font-size: 16px !important;
                padding: 6px 0 !important;
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
            }
            @page {
                size: A4 portrait;
                margin: 8mm 8mm;
            }
        }
    </style>
</head>

<body class="bg-[#f8fafc] text-slate-800 min-h-screen flex font-sans relative overflow-x-hidden">

    <!-- Include Shared Sidebar -->
    <?php 
    $activeMenu = 'token_guru';
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
                    <h1 class="font-sans text-xl sm:text-2xl font-bold text-slate-900">Daftar Cetak Token Guru & Karyawan</h1>
                    <p class="text-slate-600 text-xs sm:text-sm mt-0.5">Seluruh token pemilih guru/staf siap didistribusikan / dicetak.</p>
                </div>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <a href="../6_token-guru/token-guru.php" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 font-semibold text-sm transition-all duration-300 shadow-sm hover:bg-slate-50">
                    <i class="bi bi-arrow-left"></i>
                    <span>Kembali</span>
                </a>
                <button type="button" onclick="downloadPDF()" id="btnDownloadPDF" class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 border border-emerald-500 text-white font-bold text-sm transition-all duration-300 shadow-sm">
                    <i class="bi bi-file-earmark-pdf-fill"></i>
                    <span>Unduh File PDF</span>
                </button>
                <button type="button" onclick="window.print()" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-sm transition-all duration-300 shadow-sm">
                    <i class="bi bi-printer-fill"></i>
                    <span>Cetak</span>
                </button>
            </div>
        </header>

        <!-- Stats Overview Cards (Fullscreen Wide) -->
        <div id="statsOverview" class="grid grid-cols-1 sm:grid-cols-3 gap-4 print-hidden">
            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm flex items-center gap-3.5">
                <div class="w-11 h-11 bg-emerald-100 border border-emerald-200 rounded-xl flex items-center justify-center text-emerald-600 text-xl shrink-0">
                    <i class="bi bi-key-fill"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs uppercase font-bold text-slate-400">Total Token Dibuat</p>
                    <p class="text-base sm:text-lg font-extrabold text-slate-900"><?= $total_token; ?> <span class="text-xs font-normal text-slate-500">Token</span></p>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm flex items-center gap-3.5">
                <div class="w-11 h-11 bg-blue-100 border border-blue-200 rounded-xl flex items-center justify-center text-blue-600 text-xl shrink-0">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs uppercase font-bold text-slate-400">Token Siap Pakai</p>
                    <p class="text-base sm:text-lg font-extrabold text-emerald-600"><?= $belum_pakai; ?> <span class="text-xs font-normal text-slate-500">Token</span></p>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm flex items-center gap-3.5">
                <div class="w-11 h-11 bg-amber-100 border border-amber-200 rounded-xl flex items-center justify-center text-amber-600 text-xl shrink-0">
                    <i class="bi bi-check2-circle"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs uppercase font-bold text-slate-400">Token Terpakai</p>
                    <p class="text-base sm:text-lg font-extrabold text-amber-600"><?= $sudah_pakai; ?> <span class="text-xs font-normal text-slate-500">Token</span></p>
                </div>
            </div>
        </div>

        <!-- Content Sheet Area: Grid Kupon Token Minimalis Bersih -->
        <div id="printArea" class="print-container bg-white border border-slate-200 rounded-3xl p-5 sm:p-7 shadow-sm">
            <?php if ($total_token > 0): ?>
                <div class="token-grid grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                    <?php foreach ($tokens as $idx => $t): ?>
                        <div class="token-slip border-2 border-dashed border-slate-300 rounded-2xl p-5 bg-slate-50/50 hover:bg-white hover:border-emerald-500 transition-all flex items-center justify-center shadow-xs">
                            <div class="slip-token font-mono font-black text-2xl sm:text-3xl text-slate-900 text-center tracking-widest py-3 px-6 bg-white rounded-xl border border-slate-200 shadow-inner w-full">
                                <?= htmlspecialchars($t['kode']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-16 text-slate-400">
                    <i class="bi bi-key text-4xl block mb-2 text-slate-300"></i>
                    <p class="font-medium">Belum ada token guru yang dibuat.</p>
                    <a href="../6_token-guru/token-guru.php" class="inline-flex items-center gap-1.5 mt-3 text-sm text-emerald-600 font-bold hover:underline">
                        <i class="bi bi-plus-circle"></i> Buat Token Guru Sekarang
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <footer class="mt-8 pt-6 pb-4 text-center text-xs text-slate-500 flex flex-col gap-1 border-t border-slate-200">
            <p>&copy; <?php echo date('Y'); ?> Gradasiweb. All Rights Reserved.</p>
            <p>Dikembangkan oleh: [Fahiim]</p>
        </footer>
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
    </script>

    <script>
        function downloadPDF() {
            const btn = document.getElementById('btnDownloadPDF');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin"></i><span>Membuat PDF...</span>';
            btn.disabled = true;

            const element = document.getElementById('printArea');
            
            const opt = {
                margin:       [8, 8, 8, 8],
                filename:     'token_guru.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true, logging: false },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save().then(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }).catch(err => {
                console.error(err);
                btn.innerHTML = originalText;
                btn.disabled = false;
                window.print();
            });
        }
    </script>
</body>
</html>
