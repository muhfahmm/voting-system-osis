<!-- Modal Form Konfirmasi Pilihan Kandidat -->
<div id="modalVoteForm" class="modal fixed inset-0 bg-slate-950/60 justify-center items-center z-[1000] p-4 sm:p-6">
    <div class="modal-content bg-white border border-slate-200 p-6 sm:p-8 rounded-3xl shadow-2xl w-full max-w-[1100px] text-left relative max-h-[90vh] overflow-y-auto">
        <button type="button" class="absolute top-5 right-6 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 font-sans text-xs sm:text-sm font-semibold transition-all duration-200 flex items-center gap-1.5 cursor-pointer z-20" id="closeVoteForm">
            <i class="bi bi-arrow-left-right"></i>
            <span>Ganti Pilihan</span>
        </button>
        <h2 id="modalVoteTitle" class="font-sans text-xl sm:text-2xl font-bold text-slate-900 mb-2 border-b border-slate-100 pb-3 pr-32">Konfirmasi Pilihan</h2>
        
        <div id="modalKandidatPreview" class="mt-4 mb-4 bg-slate-50 p-4 rounded-2xl border border-slate-200">
            <div class="flex items-center gap-4 sm:gap-5 bg-white p-3.5 sm:p-4 rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <img id="modalPaslonFoto" src="" alt="Paslon" class="w-[90px] sm:w-[110px] aspect-[3/4] object-cover object-top rounded-xl border border-slate-200 shadow-sm shrink-0">
                <div class="overflow-hidden flex-1 min-w-0">
                    <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider block mb-1">Pasangan Calon</span>
                    <h3 class="text-base sm:text-lg font-bold text-slate-900 leading-tight">
                        <span id="modalKetuaNama"></span>
                        <span class="text-slate-400 font-normal mx-1">&</span>
                        <span id="modalWakilNama"></span>
                    </h3>
                    <p class="text-xs text-slate-500 font-medium mt-1">Calon Ketua & Calon Wakil Ketua OSIS</p>
                </div>
            </div>
        </div>
        
        <form action="" method="post" id="formVote" novalidate class="mt-4 flex flex-col gap-5">
            <div class="form-user-group-wrap">
                <div class="form-group flex flex-col gap-2">
                    <label for="pemilih" class="font-sans font-semibold text-xs text-slate-600 tracking-wider uppercase">Token</label>
                    <div class="relative">
                        <input type="text" id="pemilih" name="token_pemilih" 
                               placeholder="Masukkan Token" autocomplete="off" 
                               maxlength="50" pattern="[a-zA-Z0-9\-]+"
                               class="py-3.5 px-5 rounded-xl bg-white border border-slate-200 font-sans text-sm sm:text-base text-slate-700 w-full focus:outline-none focus:border-emerald-500 transition-colors"
                               value="<?= htmlspecialchars($_POST['token_pemilih'] ?? '') ?>"
                               required>
                        <span id="tokenSpinner" class="hidden absolute right-4 top-4 text-slate-400 text-xs">Mengecek...</span>
                    </div>
                    <div id="tokenFeedback" class="hidden text-xs font-semibold mt-1 px-1"></div>
                </div>
            </div>
            
            <input type="hidden" name="kandidat_terpilih" id="kandidat_terpilih" value="<?= $_POST['kandidat_terpilih'] ?? '' ?>">
            
            <div class="mt-4">
                <button type="submit" name="kirim" class="submit-btn bg-emerald-600 text-white border-none w-full h-[54px] rounded-xl font-sans font-bold text-base cursor-pointer tracking-wide hover:bg-emerald-700 transition-colors">Konfirmasi</button>
            </div>
        </form>
    </div>
</div>
