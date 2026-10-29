<?php
session_start();
require '../../../db/db.php';

if (!isset($_SESSION['login'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$admin = $_SESSION['username'];


$adminsQuery = mysqli_query($db, "SELECT id, username FROM tb_admin ORDER BY id ASC");
$totalAdmins = mysqli_num_rows($adminsQuery);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Admin - Voting OSIS</title>
    <link rel="icon" href="../../assets/img/logo-osis.png">
    
    
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
    
    
    <?php 
    $active = 'daftar_admin';
    $baseMenu = '../';
    require '../sidebar.php'; 
    ?>

    
    <main class="flex-1 p-5 md:p-8 z-10 flex flex-col gap-4 w-full lg:ml-64">
        
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-6">
            <div class="flex items-center gap-3">
                <button type="button" onclick="toggleSidebar()" class="lg:hidden p-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 font-bold text-lg shadow-sm hover:bg-slate-50 flex items-center justify-center">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="font-sans text-2xl font-bold text-slate-900">Daftar Administrator</h1>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="../../auth/register.php" target="_blank" class="flex items-center gap-2 px-5 py-3 rounded-xl bg-emerald-600 border border-emerald-500 text-white font-bold text-sm tracking-wide transition-all duration-300 shadow-sm hover:bg-emerald-700">
                    <i class="bi bi-person-plus"></i>
                    <span>Tambah Admin Baru</span>
                </a>
            </div>
        </header>

        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl p-4 shadow-sm relative overflow-hidden group">
                <div class="flex items-center gap-4">
                    <div class="w-9 h-9 bg-emerald-100 border border-emerald-200 rounded-2xl flex items-center justify-center text-emerald-600 text-xl">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Total Administrator</p>
                        <p class="text-xl font-bold mt-1 text-slate-900"><?= $totalAdmins ?></p>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl shadow-sm p-4 md:p-5 flex flex-col gap-4">
            <h2 class="font-sans text-base font-bold text-slate-800 flex items-center gap-2">
                <i class="bi bi-list-task text-emerald-500"></i>
                <span>Data Administrator Terdaftar</span>
            </h2>

            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs text-slate-500 font-bold uppercase tracking-wider">
                            <th class="py-2.5 px-3">No</th>
                            <th class="py-2.5 px-3">Username</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-sm text-slate-700">
                        <?php 
                        $no = 1;
                        if ($totalAdmins > 0): 
                            mysqli_data_seek($adminsQuery, 0);
                            while ($row = mysqli_fetch_assoc($adminsQuery)):
                        ?>
                            <tr class="transition-colors duration-200 hover:bg-slate-50">
                                <td class="py-2.5 px-3 font-semibold text-slate-500 w-16"><?= $no++ ?></td>
                                <td class="py-2.5 px-3 font-bold flex items-center gap-2 text-slate-900">
                                    <i class="bi bi-person text-slate-400"></i>
                                    <span><?= htmlspecialchars($row['username']) ?></span>
                                    <?php if ($row['username'] === $admin): ?>
                                        <span class="bg-emerald-100 border border-emerald-200 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded-full ml-1">Anda</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php 
                            endwhile;
                        else: 
                        ?>
                            <tr>
                                <td colspan="2" class="py-8 px-4 text-center text-slate-500">Belum ada administrator terdaftar.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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
</body>

</html>