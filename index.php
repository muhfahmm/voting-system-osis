<?php
session_start();
require 'db/db.php';

if (isset($_GET['action']) && $_GET['action'] === 'check_token') {
    header('Content-Type: application/json');
    $token = trim($_GET['token'] ?? '');

    if ($token === '') {
        echo json_encode(['status' => 'empty', 'message' => 'Token belum diisi']);
        exit;
    }

    // 1. Cek di tabel token siswa (tb_buat_token)
    $stmt = mysqli_prepare($db, "
        SELECT t.id, t.status_token 
        FROM tb_buat_token t
        WHERE t.token = ?
    ");
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $tid, $status_token);
    $found_siswa = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($found_siswa) {
        if ($status_token === 'sudah') {
            echo json_encode(['status' => 'used', 'message' => 'Token ini sudah digunakan untuk memilih sebelumnya.']);
        } else {
            echo json_encode(['status' => 'valid', 'message' => 'Token Siswa tersedia dan valid.', 'role' => 'siswa']);
        }
        exit;
    }

    // 2. Cek di tabel token guru (tb_kode_guru)
    $stmt = mysqli_prepare($db, "
        SELECT id, status_kode 
        FROM tb_kode_guru 
        WHERE kode = ?
    ");
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $gid, $status_kode);
    $found_guru = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($found_guru) {
        if ($status_kode === 'sudah') {
            echo json_encode(['status' => 'used', 'message' => 'Token Guru ini sudah digunakan untuk memilih.']);
        } else {
            echo json_encode(['status' => 'valid', 'message' => 'Token Guru tersedia dan valid.', 'role' => 'guru']);
        }
        exit;
    }

    echo json_encode(['status' => 'invalid', 'message' => 'Token tidak terdaftar dalam database.']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['kirim'])) {
    $token_pemilih      = trim($_POST['token_pemilih'] ?? '');
    $kandidat_terpilih  = (int) ($_POST['kandidat_terpilih'] ?? 0);

    $errorMessage = "";
    $successMessage = "";
    $tokenUsedMessage = "";

    $voter_token_id = null;
    $voter_kode_guru_id = null;

    if ($token_pemilih === '' || $kandidat_terpilih <= 0) {
        $errorMessage = "Token dan kandidat wajib diisi!";
    } else {
        $token_db_id = null;
        $role = '';
        $token_table_name = '';
        $status_check = null;
        $kelas_voter = '';

        // Cek apakah token milik Siswa (tb_buat_token)
        $token_check = mysqli_prepare($db, "
            SELECT id, status_token 
            FROM tb_buat_token 
            WHERE token = ?
        ");
        mysqli_stmt_bind_param($token_check, "s", $token_pemilih);
        mysqli_stmt_execute($token_check);
        mysqli_stmt_bind_result($token_check, $token_db_id_siswa, $status_token);
        $found_siswa = mysqli_stmt_fetch($token_check);
        mysqli_stmt_close($token_check);

        if ($found_siswa) {
            $token_db_id = $token_db_id_siswa;
            $voter_token_id = $token_db_id_siswa;
            $voter_kode_guru_id = null;
            $role = 'siswa';
            $token_table_name = 'tb_buat_token';
            $status_check = $status_token;
            $kelas_voter = 'Siswa';
        } else {
            // Cek apakah token milik Guru (tb_kode_guru)
            $kode_guru_check = mysqli_prepare($db, "
                SELECT id, status_kode 
                FROM tb_kode_guru 
                WHERE kode = ?
            ");
            mysqli_stmt_bind_param($kode_guru_check, "s", $token_pemilih);
            mysqli_stmt_execute($kode_guru_check);
            mysqli_stmt_bind_result($kode_guru_check, $token_db_id_guru, $status_kode);
            $found_guru = mysqli_stmt_fetch($kode_guru_check);
            mysqli_stmt_close($kode_guru_check);

            if ($found_guru) {
                $token_db_id = $token_db_id_guru;
                $voter_kode_guru_id = $token_db_id_guru;
                $voter_token_id = null;
                $role = 'guru';
                $token_table_name = 'tb_kode_guru';
                $status_check = $status_kode;
                $kelas_voter = 'Guru/Staf';
            } else {
                $errorMessage = "Token tidak terdaftar.";
            }
        }

        if (empty($errorMessage) && $token_db_id) {
            $is_already_used = false;
            if ($status_check === 'sudah') {
                $is_already_used = true;
            }
            if ($is_already_used) {
                $tokenUsedMessage = "Token sudah digunakan.";
            } else {
                mysqli_begin_transaction($db);
                try {
                    $kelas_voter = ($role === 'siswa') ? $nama_token : 'Guru/Staf';
                    
                    if ($role === 'siswa') {
                        $sql_voter_siswa = "
                            INSERT INTO tb_voter 
                            (nama_voter, kelas, role, token_id) 
                            VALUES (?, ?, ?, ?)
                        ";
                        $voter_siswa = mysqli_prepare($db, $sql_voter_siswa);
                        mysqli_stmt_bind_param($voter_siswa, "sssi", $token_pemilih, $kelas_voter, $role, $voter_token_id);
                        mysqli_stmt_execute($voter_siswa);
                        $voter_id = mysqli_insert_id($db);
                        mysqli_stmt_close($voter_siswa);
                    } elseif ($role === 'guru') {
                        $sql_voter_guru = "
                            INSERT INTO tb_voter 
                            (nama_voter, kelas, role, kode_guru_id) 
                            VALUES (?, ?, ?, ?)
                        ";
                        $voter_guru = mysqli_prepare($db, $sql_voter_guru);
                        mysqli_stmt_bind_param($voter_guru, "sssi", $token_pemilih, $kelas_voter, $role, $voter_kode_guru_id);
                        mysqli_stmt_execute($voter_guru);
                        $voter_id = mysqli_insert_id($db);
                        mysqli_stmt_close($voter_guru);
                    } else {
                        throw new Exception("Role tidak terdefinisi.");
                    }

                    $vote_log = mysqli_prepare($db, "INSERT INTO tb_vote_log (voter_id, nomor_kandidat) VALUES (?, ?)");
                    mysqli_stmt_bind_param($vote_log, "ii", $voter_id, $kandidat_terpilih);
                    mysqli_stmt_execute($vote_log);
                    mysqli_stmt_close($vote_log);

                    if ($token_table_name === 'tb_buat_token') {
                        mysqli_query($db, "UPDATE tb_buat_token SET status_token = 'sudah' WHERE id = $token_db_id");
                    } elseif ($token_table_name === 'tb_kode_guru') {
                        mysqli_query($db, "UPDATE tb_kode_guru SET status_kode = 'sudah' WHERE id = $token_db_id");
                    }

                    mysqli_commit($db);
                    $successMessage = "Vote berhasil! Terima kasih sudah memilih.";
                } catch (Exception $e) {
                    mysqli_rollback($db);
                    $errorMessage = "Terjadi kesalahan pada database: " . $e->getMessage();
                }
            }
        }
    }
}

$query = mysqli_query($db, "SELECT * FROM tb_kandidat ORDER BY nomor_kandidat ASC");
$query_kelas = mysqli_query($db, "SELECT * FROM tb_kelas ORDER BY nama_kelas ASC");
$kelas_list = [];
while ($k = mysqli_fetch_assoc($query_kelas)) {
    $kelas_list[] = $k;
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemilos 2026</title>
    <link rel="icon" href="admin/assets/img/logo osis.png">
    <link rel="preload" as="image" href="admin/assets/img/logo osis.png">
    <link rel="preload" as="image" href="admin/assets/img/logo sekolah.png">
    <?php 
    mysqli_data_seek($query, 0);
    while ($k = mysqli_fetch_assoc($query)): 
        if (!empty($k['foto_ketua'])): ?>
    <link rel="preload" as="image" href="admin/uploads/<?= htmlspecialchars($k['foto_ketua']) ?>">
        <?php endif; 
    endwhile; 
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
    <style type="text/tailwindcss">
        @layer base {
            body {
                background: #f8fafc, transparent 700px),
                            #f8fafc, transparent 700px),
                            #f8fafc;
            }
        }

        .kandidat-list.has-selection .kandidat-card:not(.active) {
            filter: blur(5px) grayscale(30%);
            opacity: 0.35;
            pointer-events: none;
            transform: scale(0.97);
        }

        .kandidat-card.active {
            border-color: rgba(16, 185, 129, 0.4);
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.12);
            transform: translateY(-4px) scale(1.01);
        }

        . {
            background: #f8fafc 0%, rgba(5, 150, 105, 0.05) 50%, transparent 100%);
            filter: blur(80px);
        }

        .modal {
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal.show {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            transform: scale(0.92);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .modal.show .modal-content {
            transform: scale(1);
        }
    </style>
<style>input:focus, select:focus, textarea:focus, button:focus { outline: none !important; box-shadow: none !important; }</style>
</head>

<body class="text-slate-800 min-h-screen p-4 sm:p-6 leading-relaxed overflow-x-hidden relative flex flex-col items-center justify-start lg:py-8">
    <div class="container max-w-[1350px] mx-auto relative z-10 w-full flex flex-col gap-6">
        <div class="flex justify-center items-center gap-3 sm:gap-4 bg-white/90 backdrop-blur-md border border-slate-200 py-3.5 px-4 sm:px-6 rounded-2xl shadow-sm">
    <h1 class="w-full text-center font-sans text-sm sm:text-base lg:text-xl font-bold text-slate-900 tracking-tight">
        PEMILIHAN KETUA DAN WAKIL OSIS SMK AL ISLAM SURAKARTA
    </h1>
</div>
        
        <div class="flex justify-center">
            <div class="flex justify-center items-center gap-6 bg-white/90 backdrop-blur-md py-4 px-8 rounded-2xl border border-slate-200 shadow-sm">
                <img src="admin/assets/img/logo osis.png" alt="Logo OSIS" class="h-[75px] lg:h-[95px] object-contain" loading="eager" fetchpriority="high" decoding="async">
                <img src="admin/assets/img/logo sekolah.png" alt="Logo Sekolah" class="h-[75px] lg:h-[95px] object-contain" loading="eager" fetchpriority="high" decoding="async">
            </div>
        </div>
        
        <div class="kandidat-list grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8 mb-4 lg:mb-0 w-full" id="kandidatList">
            <?php 
            mysqli_data_seek($query, 0);
            while ($row = mysqli_fetch_assoc($query)) : 
            ?>
                <div class="kandidat-card bg-white/90 backdrop-blur-md border border-slate-200 rounded-2xl p-4 lg:p-5 shadow-sm relative overflow-hidden select-none h-full flex flex-col justify-between" data-id="<?= $row['nomor_kandidat']; ?>">
                    <div class="absolute -top-3 -right-1 text-[90px] lg:text-[110px] font-sans font-black text-slate-900/10 pointer-events-none select-none z-0 leading-none">0<?= $row['nomor_kandidat']; ?></div>
                    
                    <h3 class="font-sans text-lg lg:text-xl font-bold text-slate-900 mb-3 text-center relative z-10">Pasangan Nomor <?= $row['nomor_kandidat']; ?></h3>
                    
                    <div class="flex flex-col items-center mb-4 relative z-10">
                        <div class="w-full flex justify-center mb-3">
                            <img src="admin/uploads/<?= htmlspecialchars($row['foto_ketua']) ?>" alt="Paslon <?= $row['nomor_kandidat']; ?>" class="foto-paslon w-full max-w-[280px] aspect-[3/4] object-cover object-top rounded-2xl shadow-sm border border-slate-100" loading="eager" fetchpriority="high" decoding="async">
                        </div>
                        <div class="w-full text-center px-1">
                            <h4 class="nama-paslon font-sans font-bold text-sm lg:text-base text-slate-900 leading-snug">
                                <span class="nama-ketua"><?= htmlspecialchars($row['nama_ketua']); ?></span>
                                <span class="text-slate-400 font-normal mx-1">&</span>
                                <span class="nama-wakil"><?= htmlspecialchars($row['nama_wakil']); ?></span>
                            </h4>
                            <p class="text-slate-500 text-[11px] lg:text-xs font-semibold uppercase tracking-wider mt-1">Calon Ketua & Calon Wakil</p>
                        </div>
                    </div>
                    
                    <div class="btn-vote relative z-10 text-center mt-auto pt-2">
                        <button type="button" class="vote-btn bg-emerald-600 text-white border border-emerald-600 py-3 px-5 rounded-xl cursor-pointer w-full font-sans text-xs lg:text-base font-bold tracking-wide transition-all hover:bg-emerald-700" data-id="<?= $row['nomor_kandidat']; ?>">
                            Pilih Kandidat <?= $row['nomor_kandidat']; ?>
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Include Modal Form Konfirmasi Pilihan -->
    <?php include 'modals/modal_vote_form.php'; ?>

    <div id="modalSuccess" class="modal fixed inset-0 bg-slate-950/60 justify-center items-center z-[1050] p-4">
        <div class="modal-content bg-white border border-slate-200 p-8 sm:p-10 rounded-3xl shadow-2xl w-full max-w-[580px] text-center relative flex flex-col items-center">
            <span class="close absolute top-5 right-6 cursor-pointer text-2xl text-slate-400 hover:text-slate-600">&times;</span>
            <div class="icon-wrap w-24 h-24 rounded-full flex justify-center items-center mx-auto mb-6 text-4xl bg-emerald-100 text-emerald-600 border border-emerald-200 shadow-inner">
                <i class="bi bi-check-lg text-5xl"></i>
            </div>
            <h2 class="font-sans text-2xl sm:text-3xl font-bold text-slate-900 mb-3">Vote Berhasil!</h2>
            <p class="text-slate-600 text-base mb-8 leading-relaxed max-w-[460px]">Terima kasih sudah memilih. Semoga pilihanmu membawa kebaikan bagi sekolah.</p>
            <button id="okBtn" class="button-ok bg-emerald-600 w-full h-[52px] border-none rounded-xl font-sans text-base font-bold text-white cursor-pointer hover:bg-emerald-700 transition-colors">OK</button>
        </div>
    </div>

    <div id="modalError" class="modal fixed inset-0 bg-slate-950/60 justify-center items-center z-[1050] p-4">
        <div class="modal-content bg-white border border-slate-200 p-8 sm:p-10 rounded-3xl shadow-2xl w-full max-w-[580px] text-center relative flex flex-col items-center">
            <span class="close absolute top-5 right-6 cursor-pointer text-2xl text-slate-400 hover:text-slate-600">&times;</span>
            <div id="iconErrorWrap" class="icon-wrap w-24 h-24 rounded-full flex justify-center items-center mx-auto mb-6 bg-red-100 text-red-600 border border-red-200 shadow-inner">
                <svg id="svgErrorIcon" class="w-12 h-12 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
            <h2 id="modalErrorTitle" class="font-sans text-2xl sm:text-3xl font-bold text-slate-900 mb-3">Terjadi Kesalahan</h2>
            <p id="errorText" class="text-slate-600 text-base mb-8 leading-relaxed max-w-[460px]"></p>
            <button id="errorBtn" class="button-ok bg-red-600 w-full h-[52px] border-none rounded-xl font-sans text-base font-bold text-white cursor-pointer hover:bg-red-700 transition-colors">OK</button>
        </div>
    </div>

    <div id="modalTokenUsed" class="modal fixed inset-0 bg-slate-950/60 justify-center items-center z-[1050] p-4">
        <div class="modal-content bg-white border border-slate-200 p-8 sm:p-10 rounded-3xl shadow-2xl w-full max-w-[580px] text-center relative flex flex-col items-center">
            <span class="close absolute top-5 right-6 cursor-pointer text-2xl text-slate-400 hover:text-slate-600">&times;</span>
            <div class="icon-wrap w-24 h-24 rounded-full flex justify-center items-center mx-auto mb-6 text-4xl bg-amber-100 text-amber-600 border border-amber-200 shadow-inner">
                <i class="bi bi-exclamation-triangle-fill text-5xl"></i>
            </div>
            <h2 class="font-sans text-2xl sm:text-3xl font-bold text-slate-900 mb-3">Token Sudah Digunakan</h2>
            <p class="text-slate-600 text-base mb-8 leading-relaxed max-w-[460px]">Token ini sudah dipakai untuk memilih sebelumnya. Satu token hanya berlaku untuk satu kali pemungutan suara.</p>
            <button id="tokenUsedBtn" class="button-ok bg-amber-600 w-full h-[52px] border-none rounded-xl font-sans text-base font-bold text-white cursor-pointer hover:bg-amber-700 transition-colors">OK</button>
        </div>
    </div>

    <!-- Include Modals Terpisah -->
    <?php include 'modals/modal_kandidat_kosong.php'; ?>
    <?php include 'modals/modal_token_kosong.php'; ?>
    <?php include 'modals/modal_kelas_kosong.php'; ?>
    <?php include 'modals/modal_token_tidak_terdaftar.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const kandidatList = document.getElementById('kandidatList');
            const voteButtons = document.querySelectorAll('.vote-btn');
            const inputKandidat = document.getElementById('kandidat_terpilih');
            let selectedCard = null;
            
            const modalSuccess = document.getElementById('modalSuccess');
            const modalError = document.getElementById('modalError');
            const modalTokenUsed = document.getElementById('modalTokenUsed');
            const modalVoteForm = document.getElementById('modalVoteForm');
            const errorText = document.getElementById('errorText');
            
            const closeVoteForm = document.getElementById('closeVoteForm');

            const showModal = (modal) => {
                modal.style.display = 'flex';
                modal.offsetHeight; 
                modal.classList.add('show');
            };

            const hideModal = (modal) => {
                modal.classList.remove('show');
                setTimeout(() => {
                    modal.style.display = 'none';
                }, 300);
            };

            const selectedKandidat = inputKandidat.value;
            if (selectedKandidat) {
                const card = document.querySelector(`.kandidat-card[data-id="${selectedKandidat}"]`);
                if (card) {
                    selectCard(card);
                }
            }



            voteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const card = this.closest('.kandidat-card');
                    
                    if (selectedCard === card) {
                        handleCancelVote();
                    } else {
                        if (selectedCard) {
                            deselectCard(selectedCard);
                        }
                        selectCard(card);
                    }
                });
            });

            function selectCard(card) {
                const cardId = card.getAttribute('data-id');
                const button = card.querySelector('.vote-btn');
                
                card.classList.add('active');
                button.textContent = "Pilihan Terpilih";
                
                inputKandidat.value = cardId;
                kandidatList.classList.add('has-selection');
                selectedCard = card;

                const fotoPaslon = card.querySelector('.foto-paslon').src;
                const namaKetua = card.querySelector('.nama-ketua').textContent;
                const namaWakil = card.querySelector('.nama-wakil').textContent;

                document.getElementById('modalVoteTitle').textContent = `Konfirmasi Pilihan: Pasangan Nomor ${cardId}`;
                
                const modalFoto = document.getElementById('modalPaslonFoto');
                if (modalFoto) modalFoto.src = fotoPaslon;
                const modalNamaKetua = document.getElementById('modalKetuaNama');
                if (modalNamaKetua) modalNamaKetua.textContent = namaKetua;
                const modalNamaWakil = document.getElementById('modalWakilNama');
                if (modalNamaWakil) modalNamaWakil.textContent = namaWakil;

                showModal(modalVoteForm);
            }

            function deselectCard(card) {
                const button = card.querySelector('.vote-btn');
                const originalText = `Pilih Kandidat ${card.getAttribute('data-id')}`;
                
                card.classList.remove('active');
                button.textContent = originalText;
                
                inputKandidat.value = '';
                
                if (!document.querySelector('.kandidat-card.active')) {
                    kandidatList.classList.remove('has-selection');
                    selectedCard = null;
                }
            }

            const handleCancelVote = () => {
                if (selectedCard) {
                    deselectCard(selectedCard);
                }
                hideModal(modalVoteForm);
            };

            closeVoteForm.addEventListener('click', handleCancelVote);

            let isFormValidationAlert = false;

            const closeFeedbackBtns = document.querySelectorAll('#modalSuccess .close, #okBtn, #modalError .close, #errorBtn, #modalTokenUsed .close, #tokenUsedBtn');
            closeFeedbackBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    hideModal(modalSuccess);
                    hideModal(modalError);
                    hideModal(modalTokenUsed);

                    if (isFormValidationAlert) {
                        isFormValidationAlert = false;
                        setTimeout(() => {
                            showModal(modalVoteForm);
                        }, 350);
                    } else {
                        setTimeout(() => {
                            window.location.href = 'index.php';
                        }, 350);
                    }
                });
            });

            window.onclick = (e) => {
                if (e.target === modalSuccess) {
                    hideModal(modalSuccess);
                    setTimeout(() => { window.location.href = 'index.php'; }, 350);
                }
                if (e.target === modalError) {
                    hideModal(modalError);
                    if (isFormValidationAlert) {
                        isFormValidationAlert = false;
                        setTimeout(() => { showModal(modalVoteForm); }, 350);
                    } else {
                        setTimeout(() => { window.location.href = 'index.php'; }, 350);
                    }
                }
                if (e.target === modalTokenUsed) {
                    hideModal(modalTokenUsed);
                    setTimeout(() => { window.location.href = 'index.php'; }, 350);
                }
                if (e.target === modalPilihKandidat || e.target === modalTokenKosong || e.target === modalKelasKosong || e.target === modalTokenInvalid) {
                    hideModal(e.target);
                    setTimeout(() => { showModal(modalVoteForm); }, 250);
                }
                if (e.target === modalVoteForm) {
                    handleCancelVote();
                }
            };

            <?php if (!empty($successMessage)) : ?>
                showModal(modalSuccess);
            <?php elseif (!empty($errorMessage)) : ?>
                const modalTokenInvalid = document.getElementById('modalTokenInvalid');
                const invalidText = document.getElementById('modalTokenInvalidText');
                if (invalidText) invalidText.innerText = "<?= addslashes($errorMessage) ?>";
                showModal(modalTokenInvalid);
            <?php elseif (!empty($tokenUsedMessage)) : ?>
                showModal(modalTokenUsed);
            <?php endif; ?>

            const tokenInput = document.getElementById('pemilih');
            const tokenSpinner = document.getElementById('tokenSpinner');
            const tokenFeedback = document.getElementById('tokenFeedback');
            const kelasSelect = document.getElementById('kelas');
            let checkTimer = null;
            let isTokenValid = false;

            const checkTokenAvailability = () => {
                const tokenVal = tokenInput.value.trim();
                
                if (!tokenVal) {
                    tokenFeedback.classList.add('hidden');
                    tokenFeedback.textContent = '';
                    tokenInput.classList.remove('border-emerald-500', 'border-red-500', 'border-amber-500');
                    tokenInput.classList.add('border-slate-200');
                    isTokenValid = false;
                    return;
                }

                tokenSpinner.classList.remove('hidden');

                fetch(`index.php?action=check_token&token=${encodeURIComponent(tokenVal)}`)
                    .then(res => res.json())
                    .then(data => {
                        tokenSpinner.classList.add('hidden');
                        tokenFeedback.classList.remove('hidden', 'text-emerald-600', 'text-red-600', 'text-amber-600');
                        tokenInput.classList.remove('border-slate-200', 'border-emerald-500', 'border-red-500', 'border-amber-500');

                        if (data.status === 'valid') {
                            tokenFeedback.classList.add('text-emerald-600');
                            tokenFeedback.textContent = '✓ ' + data.message;
                            tokenInput.classList.add('border-emerald-500');
                            isTokenValid = true;
                        } else if (data.status === 'used') {
                            tokenFeedback.classList.add('text-amber-600');
                            tokenFeedback.textContent = '⚠️ ' + data.message;
                            tokenInput.classList.add('border-amber-500');
                            isTokenValid = false;
                        } else {
                            tokenFeedback.classList.add('hidden');
                            tokenFeedback.textContent = '';
                            tokenInput.classList.add('border-slate-200');
                            isTokenValid = false;
                        }
                    })
                    .catch(() => {
                        tokenSpinner.classList.add('hidden');
                    });
            };

            tokenInput.addEventListener('input', () => {
                clearTimeout(checkTimer);
                checkTimer = setTimeout(checkTokenAvailability, 300);
            });

            if (kelasSelect) {
                kelasSelect.addEventListener('change', () => {
                    if (tokenInput.value.trim()) {
                        checkTokenAvailability();
                    }
                });
            }

            const showCustomAlert = (title, message) => {
                isFormValidationAlert = true;
                hideModal(modalVoteForm);
                const modalErrorTitle = document.getElementById('modalErrorTitle');
                if (modalErrorTitle) modalErrorTitle.textContent = title;
                errorText.textContent = message;
                const iconEl = document.getElementById('iconErrorEl');
                if (iconEl) {
                    iconEl.className = 'bi bi-exclamation-triangle-fill text-5xl text-red-600';
                }
                setTimeout(() => {
                    showModal(modalError);
                }, 200);
            };

            const modalPilihKandidat = document.getElementById('modalPilihKandidat');
            const modalTokenKosong = document.getElementById('modalTokenKosong');
            const modalKelasKosong = document.getElementById('modalKelasKosong');

            const openValidationModal = (targetModal) => {
                hideModal(modalVoteForm);
                setTimeout(() => {
                    showModal(targetModal);
                }, 200);
            };

            const closeValidationBtns = document.querySelectorAll('.close-modal-validation, .btn-ok-validation, .close-modal-invalid, .btn-ok-invalid');
            closeValidationBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const parentModal = this.closest('.modal');
                    if (parentModal) {
                        hideModal(parentModal);
                        setTimeout(() => {
                            showModal(modalVoteForm);
                        }, 250);
                    }
                });
            });

            document.getElementById('formVote').addEventListener('submit', function(e) {
                if (!inputKandidat.value) {
                    e.preventDefault();
                    openValidationModal(modalPilihKandidat);
                    return false;
                }
                
                if (!document.getElementById('pemilih').value.trim()) {
                    e.preventDefault();
                    openValidationModal(modalTokenKosong);
                    return false;
                }
            });
        });
    </script>
</body>

</html>
<?php mysqli_close($db); ?>