<?php
// File: modals/modal_exceed.php
?>
<div id="exceedModal" class="fixed inset-0 flex items-center justify-center bg-black/60 hidden z-50">
  <div class="bg-slate-900/90 backdrop-blur-md border border-white/10 rounded-xl shadow-xl p-6 w-11/12 max-w-md text-center">
    <h2 class="text-xl font-outfit font-bold text-indigo-300 mb-4">⚠️ Token Melebihi Jumlah Siswa</h2>
    <p class="text-slate-300 mb-6">Jumlah token yang telah dibuat untuk kelas ini melebihi jumlah siswa yang terdaftar. Silakan hapus token yang tidak diperlukan atau tambahkan siswa ke kelas.</p>
    <button id="closeExceedModal" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-medium transition-colors">Tutup</button>
  </div>
</div>
