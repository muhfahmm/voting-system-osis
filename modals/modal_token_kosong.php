<!-- Modal Peringatan: Token Belum Diisi -->
<div id="modalTokenKosong" class="modal fixed inset-0 bg-slate-950/60 justify-center items-center z-[1050] p-4">
    <div class="modal-content bg-white border border-slate-200 p-8 sm:p-10 rounded-3xl shadow-2xl w-full max-w-[540px] text-center relative flex flex-col items-center">
        <span class="close-modal-validation absolute top-5 right-6 cursor-pointer text-2xl text-slate-400 hover:text-slate-600">&times;</span>
        <div class="icon-wrap w-24 h-24 rounded-full flex justify-center items-center mx-auto mb-6 bg-red-100 text-red-600 border border-red-200 shadow-inner">
            <svg class="w-12 h-12 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
        </div>
        <h2 class="font-sans text-2xl sm:text-3xl font-bold text-slate-900 mb-3">Peringatan</h2>
        <p class="text-slate-600 text-base mb-8 leading-relaxed max-w-[440px]">masukkan token terlebih dahulu!</p>
        <button type="button" class="btn-ok-validation bg-red-600 w-full h-[52px] border-none rounded-xl font-sans text-base font-bold text-white cursor-pointer hover:bg-red-700 transition-colors">OK</button>
    </div>
</div>
