<?php
/**
 * Shared Sidebar Component for Admin Panel
 * Usage:
 *   $activeMenu = 'token_siswa'; // dashboard | hasil_vote | kandidat | daftar_voter | token_siswa | token_guru | daftar_admin
 *   include __DIR__ . '/sidebar.php'; // or relative path depending on location
 */

$adminUsername = $_SESSION['username'] ?? 'Admin';

// Menghitung path relatif ke folder root admin
// Dilihat dari file yang memanggil
$currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
$inTokenDir = strpos($currentScript, '/token/') !== false;

// Tentukan base path link menu dan assets sesuai lokasi file
if ($inTokenDir) {
    $baseMenu = '../';
    $baseAdmin = '../../';
    $baseRoot = '../../../';
} else {
    $baseMenu = '../';
    $baseAdmin = '../../';
    $baseRoot = '../../../';
}

$active = $activeMenu ?? '';
?>

<!-- Mobile Overlay Backdrop -->
<div id="sidebarOverlay" class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm z-30 hidden lg:hidden transition-opacity duration-300 opacity-0" onclick="toggleSidebar()"></div>

<!-- Aside Sidebar -->
<aside id="sidebarMenu" class="w-64 bg-white/95 lg:bg-white/80 backdrop-blur-xl border-r border-slate-200 shadow-xl lg:shadow-sm flex flex-col fixed top-0 left-0 z-40 h-screen transition-transform duration-300 -translate-x-full lg:translate-x-0 print:hidden">
    
    <div class="flex flex-col gap-4 p-4 flex-1 overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-200 pb-6">
            <div class="flex items-center gap-3">
                <img src="<?= $baseAdmin; ?>assets/img/logo osis.png" alt="Logo OSIS" class="h-9 object-contain">
                <div>
                    <h2 class="font-sans font-extrabold text-lg text-slate-900">Admin Panel</h2>
                </div>
            </div>
            <button type="button" onclick="toggleSidebar()" class="lg:hidden p-2 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors">
                <i class="bi bi-x-lg text-lg"></i>
            </button>
        </div>

        <nav class="flex flex-col gap-1.5">
            <a href="<?= $baseMenu; ?>1_dashboard/dashboard.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg <?= ($active === 'dashboard') ? 'bg-emerald-50 text-emerald-700 border-l-4 border-emerald-500 font-semibold' : 'text-slate-600 font-medium transition-all duration-300 hover:bg-slate-100/80' ?> group">
                <i class="bi bi-speedometer2 text-lg <?= ($active === 'dashboard') ? 'text-emerald-500' : 'group-hover:text-emerald-600 transition-colors' ?>"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?= $baseMenu; ?>2_hasil-vote/result.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg <?= ($active === 'hasil_vote') ? 'bg-emerald-50 text-emerald-700 border-l-4 border-emerald-500 font-semibold' : 'text-slate-600 font-medium transition-all duration-300 hover:bg-slate-100/80' ?> group">
                <i class="bi bi-bar-chart-line text-lg <?= ($active === 'hasil_vote') ? 'text-emerald-500' : 'group-hover:text-emerald-600 transition-colors' ?>"></i>
                <span>Hasil Vote</span>
            </a>
            <a href="<?= $baseMenu; ?>3_kandidat/daftar-kandidat.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg <?= ($active === 'kandidat') ? 'bg-emerald-50 text-emerald-700 border-l-4 border-emerald-500 font-semibold' : 'text-slate-600 font-medium transition-all duration-300 hover:bg-slate-100/80' ?> group">
                <i class="bi bi-people text-lg <?= ($active === 'kandidat') ? 'text-emerald-500' : 'group-hover:text-emerald-600 transition-colors' ?>"></i>
                <span>Daftar Kandidat</span>
            </a>
            <a href="<?= $baseMenu; ?>4_daftar-voter/daftar-voter.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg <?= ($active === 'daftar_voter') ? 'bg-emerald-50 text-emerald-700 border-l-4 border-emerald-500 font-semibold' : 'text-slate-600 font-medium transition-all duration-300 hover:bg-slate-100/80' ?> group">
                <i class="bi bi-card-checklist text-lg <?= ($active === 'daftar_voter') ? 'text-emerald-500' : 'group-hover:text-emerald-600 transition-colors' ?>"></i>
                <span>Daftar Voter</span>
            </a>
            <a href="<?= $baseMenu; ?>5_token-siswa/token-siswa.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg <?= ($active === 'token_siswa') ? 'bg-emerald-50 text-emerald-700 border-l-4 border-emerald-500 font-semibold' : 'text-slate-600 font-medium transition-all duration-300 hover:bg-slate-100/80' ?> group">
                <i class="bi bi-key text-lg <?= ($active === 'token_siswa') ? 'text-emerald-500' : 'group-hover:text-emerald-600 transition-colors' ?>"></i>
                <span>Token Siswa</span>
            </a>
            <a href="<?= $baseMenu; ?>6_token-guru/token-guru.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg <?= ($active === 'token_guru') ? 'bg-emerald-50 text-emerald-700 border-l-4 border-emerald-500 font-semibold' : 'text-slate-600 font-medium transition-all duration-300 hover:bg-slate-100/80' ?> group">
                <i class="bi bi-shield-lock text-lg <?= ($active === 'token_guru') ? 'text-emerald-500' : 'group-hover:text-emerald-600 transition-colors' ?>"></i>
                <span>Token Guru</span>
            </a>
            <a href="<?= $baseMenu; ?>7_daftar-admin/daftar-admin.php" class="flex items-center gap-3.5 px-3 py-2.5 rounded-lg <?= ($active === 'daftar_admin') ? 'bg-emerald-50 text-emerald-700 border-l-4 border-emerald-500 font-semibold' : 'text-slate-600 font-medium transition-all duration-300 hover:bg-slate-100/80' ?> group">
                <i class="bi bi-person-workspace text-lg <?= ($active === 'daftar_admin') ? 'text-emerald-500' : 'group-hover:text-emerald-600 transition-colors' ?>"></i>
                <span>Daftar Admin</span>
            </a>
        </nav>
    </div>

    <div class="p-4 border-t border-slate-200 bg-slate-50 flex flex-col gap-4 shrink-0">
        <a href="<?= $baseAdmin; ?>auth/logout.php" class="flex items-center justify-center gap-2.5 w-full py-2.5 rounded-lg bg-red-50 text-red-600 border border-red-200 font-bold text-sm transition-all duration-300 hover:bg-red-100">
            <i class="bi bi-box-arrow-right"></i>
            <span>Keluar (Logout)</span>
        </a>

    </div>
</aside>

