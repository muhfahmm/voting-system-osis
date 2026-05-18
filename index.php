<?php
session_start();
require 'db/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['kirim'])) {
    $token_pemilih      = trim($_POST['token_pemilih'] ?? '');
    $role               = trim($_POST['role'] ?? 'siswa');
    $kelas_pemilih      = trim($_POST['kelas'] ?? '');
    $kandidat_terpilih  = (int) ($_POST['kandidat_terpilih'] ?? 0);

    $errorMessage = "";
    $successMessage = "";
    $tokenUsedMessage = "";

    $voter_token_id = null;
    $voter_kode_guru_id = null;

    if ($token_pemilih === '' || $kandidat_terpilih <= 0) {
        $errorMessage = "Token dan kandidat wajib diisi!";
    } elseif (!in_array($role, ['siswa', 'guru'])) {
        $errorMessage = "Role tidak valid.";
    } elseif ($role === 'siswa' && $kelas_pemilih === '') {
        $errorMessage = "Untuk siswa, kelas wajib diisi.";
    } else {
        $token_db_id = null;
        $nama_token = '';
        $token_table_name = '';
        $status_check = null;
        $kelas_token = '';

        if ($role === 'siswa') {
            $token_check = mysqli_prepare($db, "
                SELECT t.id, t.kelas_id, t.status_token, k.nama_kelas 
                FROM tb_buat_token t
                LEFT JOIN tb_kelas k ON t.kelas_id = k.id
                WHERE t.token = ?
            ");
            mysqli_stmt_bind_param($token_check, "s", $token_pemilih);
            mysqli_stmt_execute($token_check);
            mysqli_stmt_bind_result($token_check, $token_db_id, $kelas_id_token, $status_token, $nama_kelas_token);
            mysqli_stmt_fetch($token_check);
            mysqli_stmt_close($token_check);

            if ($token_db_id) {
                if ($nama_kelas_token !== $kelas_pemilih) {
                    $errorMessage = "Token ini bukan untuk kelas $kelas_pemilih. Token ini untuk kelas $nama_kelas_token.";
                } else {
                    $voter_token_id = $token_db_id;
                    $voter_kode_guru_id = null;
                    $nama_token = $nama_kelas_token;
                    $token_table_name = 'tb_buat_token';
                    $status_check = $status_token;
                    $kelas_token = $nama_kelas_token;
                }
            } else {
                $errorMessage = "Token tidak terdaftar.";
            }
        } elseif ($role === 'guru') {
            $kode_guru_check = mysqli_prepare($db, "
                SELECT id, status_kode 
                FROM tb_kode_guru 
                WHERE kode = ?
            ");
            mysqli_stmt_bind_param($kode_guru_check, "s", $token_pemilih);
            mysqli_stmt_execute($kode_guru_check);
            mysqli_stmt_bind_result($kode_guru_check, $token_db_id_guru, $status_kode);
            mysqli_stmt_fetch($kode_guru_check);
            mysqli_stmt_close($kode_guru_check);

            if ($token_db_id_guru) {
                $token_db_id = $token_db_id_guru;
                $voter_kode_guru_id = $token_db_id_guru;
                $voter_token_id = null;
                $nama_token = 'Guru/Staf';
                $token_table_name = 'tb_kode_guru';
                $status_check = $status_kode;
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
                    $kelas_voter = ($role === 'siswa') ? $kelas_pemilih : $nama_token;
                    
                    if ($role === 'siswa') {
                        $sql_voter_siswa = "
                            INSERT INTO tb_voter 
                            (nama_voter, kelas, role, token_id, created_at) 
                            VALUES (?, ?, ?, ?, NOW())
                        ";
                        $voter_siswa = mysqli_prepare($db, $sql_voter_siswa);
                        mysqli_stmt_bind_param($voter_siswa, "sssi", $token_pemilih, $kelas_voter, $role, $voter_token_id);
                        mysqli_stmt_execute($voter_siswa);
                        $voter_id = mysqli_insert_id($db);
                        mysqli_stmt_close($voter_siswa);
                    } elseif ($role === 'guru') {
                        $sql_voter_guru = "
                            INSERT INTO tb_voter 
                            (nama_voter, kelas, role, kode_guru_id, created_at) 
                            VALUES (?, ?, ?, ?, NOW())
                        ";
                        $voter_guru = mysqli_prepare($db, $sql_voter_guru);
                        mysqli_stmt_bind_param($voter_guru, "sssi", $token_pemilih, $kelas_voter, $role, $voter_kode_guru_id);
                        mysqli_stmt_execute($voter_guru);
                        $voter_id = mysqli_insert_id($db);
                        mysqli_stmt_close($voter_guru);
                    } else {
                        throw new Exception("Role tidak terdefinisi.");
                    }

                    $vote_log = mysqli_prepare($db, "INSERT INTO tb_vote_log (voter_id, nomor_kandidat, created_at) VALUES (?, ?, NOW())");
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
    <title>Voting Kandidat OSIS Skalsa</title>
    <link rel="icon" href="admin/assets/img/logo osis.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        darkbg: '#0b0f19',
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        outfit: ['Outfit', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer base {
            body {
                background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.15), transparent 700px),
                            radial-gradient(circle at bottom left, rgba(16, 185, 129, 0.12), transparent 700px),
                            #0b0f19;
            }
        }

        /* Interactive candidate card selection blur & active effects */
        .kandidat-list.has-selection .kandidat-card:not(.active) {
            filter: blur(5px) grayscale(30%);
            opacity: 0.35;
            pointer-events: none;
            transform: scale(0.97);
        }

        .kandidat-card.active {
            border-color: rgba(16, 185, 129, 0.7);
            background: rgba(30, 41, 59, 0.55);
            box-shadow: 0 25px 50px -12px rgba(16, 185, 129, 0.25),
                        0 0 30px rgba(16, 185, 129, 0.15);
            transform: translateY(-4px) scale(1.01);
        }

        /* Ambient glowing circles */
        .ambient-glow {
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.05) 50%, transparent 100%);
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
</head>

<body class="text-[#f1f5f9] min-h-screen p-5 leading-relaxed overflow-x-hidden relative flex flex-col items-center justify-start lg:py-8">
    <div class="container max-w-[1200px] mx-auto relative z-10 w-full flex flex-col gap-6">
        <!-- Header -->
        <div class="flex justify-between items-center bg-slate-800/45 backdrop-blur-md border border-white/5 py-4 px-7 rounded-[20px] shadow-[0_20px_40px_-15px_rgba(0,0,0,0.5)]">
            <h1 class="font-outfit text-xl lg:text-2xl font-extrabold bg-gradient-to-r from-white to-indigo-200 bg-clip-text text-transparent tracking-tight">Selamat Datang di Forum Pemilihan Osis Skalsa</h1>
            <div>
                <button class="bg-white/5 border border-white/10 h-10 px-5 rounded-xl font-sans text-xs lg:text-sm font-semibold cursor-pointer text-[#f1f5f9] transition-all duration-300 hover:bg-indigo-500/20 hover:border-indigo-500/40 hover:text-indigo-200 hover:shadow-[0_0_20px_rgba(99,102,241,0.25)] hover:-translate-y-0.5 active:translate-y-0 backdrop-blur-sm" name="login" onclick="window.location.href='admin/auth/logout.php'">Dashboard</button>
            </div>
        </div>
        
        <!-- Logo Section -->
        <div class="flex justify-center">
            <div class="flex justify-center items-center gap-10 bg-slate-900/40 backdrop-blur-md py-5 px-10 rounded-[28px] border border-white/5 shadow-2xl">
                <img src="admin/assets/img/logo osis.png" alt="Logo OSIS" class="h-[110px] lg:h-[135px] object-contain drop-shadow-[0_6px_12px_rgba(0,0,0,0.4)] transition-transform duration-300 hover:scale-110 hover:rotate-2">
                <img src="admin/assets/img/logo sekolah.png" alt="Logo Sekolah" class="h-[110px] lg:h-[135px] object-contain drop-shadow-[0_6px_12px_rgba(0,0,0,0.4)] transition-transform duration-300 hover:scale-110 hover:rotate-2">
            </div>
        </div>
        
        <!-- Candidate List -->
        <div class="kandidat-list grid grid-cols-1 gap-6 mb-5 perspective-[1000px] lg:grid-cols-3 lg:gap-6 lg:mb-0" id="kandidatList">
            <?php 
            mysqli_data_seek($query, 0);
            while ($row = mysqli_fetch_assoc($query)) : 
            ?>
                <div class="kandidat-card bg-slate-800/35 backdrop-blur-[20px] border border-white/5 rounded-[24px] p-6 shadow-[0_20px_40px_-15px_rgba(0,0,0,0.5)] transition-all duration-500 relative overflow-hidden group select-none lg:p-5 lg:h-fit self-center" data-id="<?= $row['nomor_kandidat']; ?>">
                    <!-- Large Number Background -->
                    <div class="absolute -top-5 -right-2 text-[130px] font-outfit font-black text-white/5 pointer-events-none select-none z-0 leading-none">0<?= $row['nomor_kandidat']; ?></div>
                    
                    <h3 class="font-outfit text-lg font-bold text-[#f1f5f9] mb-[18px] text-center relative z-10 tracking-wide">Pasangan Nomor <?= $row['nomor_kandidat']; ?></h3>
                    
                    <div class="flex gap-4 mb-[18px] relative z-10">
                        <!-- Ketua -->
                        <div class="flex-1 bg-slate-950/35 border border-white/5 rounded-2xl p-3 text-center transition-all duration-300 group-hover:border-white/10 group-hover:bg-slate-950/50">
                            <img src="admin/uploads/<?= htmlspecialchars($row['foto_ketua']) ?>" alt="Ketua" class="foto-ketua w-full h-[190px] object-cover object-top rounded-xl mb-[10px] shadow-[0_8px_16px_rgba(0,0,0,0.4)] transition-transform duration-500 group-hover:scale-105">
                            <h3 class="nama-ketua font-outfit my-1 font-semibold text-sm lg:text-base text-white truncate"><?= htmlspecialchars($row['nama_ketua']); ?></h3>
                            <small class="text-slate-400 text-[10px] lg:text-[11px] font-semibold uppercase tracking-wider">Calon Ketua OSIS</small>
                        </div>
                        <!-- Wakil -->
                        <div class="flex-1 bg-slate-950/35 border border-white/5 rounded-2xl p-3 text-center transition-all duration-300 group-hover:border-white/10 group-hover:bg-slate-950/50">
                            <img src="admin/uploads/<?= htmlspecialchars($row['foto_wakil']) ?>" alt="Wakil" class="foto-wakil w-full h-[190px] object-cover object-top rounded-xl mb-[10px] shadow-[0_8px_16px_rgba(0,0,0,0.4)] transition-transform duration-500 group-hover:scale-105">
                            <h3 class="nama-wakil font-outfit my-1 font-semibold text-sm lg:text-base text-white truncate"><?= htmlspecialchars($row['nama_wakil']); ?></h3>
                            <small class="text-slate-400 text-[10px] lg:text-[11px] font-semibold uppercase tracking-wider">Calon Wakil OSIS</small>
                        </div>
                    </div>
                    
                    <div class="btn-vote mt-[10px] relative z-10 text-center">
                        <button type="button" class="vote-btn bg-indigo-500/10 text-indigo-300 border border-indigo-500/25 py-3 px-5 rounded-2xl cursor-pointer w-full font-sans text-sm font-bold transition-all duration-300 tracking-wide hover:bg-indigo-500 hover:text-white hover:border-indigo-500 hover:shadow-[0_8px_20px_rgba(99,102,241,0.35)]" data-id="<?= $row['nomor_kandidat']; ?>">
                            Pilih Kandidat <?= $row['nomor_kandidat']; ?>
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Modal Voting Form -->
    <div id="modalVoteForm" class="modal fixed inset-0 bg-slate-950/80 backdrop-blur-md justify-center items-center z-[1000] p-5">
        <div class="modal-content bg-slate-800/85 backdrop-blur-[32px] border border-white/10 p-9 rounded-[28px] shadow-[0_35px_70px_-15px_rgba(0,0,0,0.7)] w-full max-w-[680px] text-left relative">
            <span class="close absolute top-5 right-6 cursor-pointer text-2xl text-slate-400 hover:text-red-500 transition-colors duration-200" id="closeVoteForm">&times;</span>
            <h2 id="modalVoteTitle" class="font-outfit text-xl lg:text-2xl font-bold text-white mb-2">Konfirmasi Pilihan</h2>
            <p id="modalVoteSubtitle" class="text-slate-400 text-sm">Silakan masukkan data Anda untuk melanjutkan pemilihan.</p>
            
            <!-- Selected Candidate Preview -->
            <div id="modalKandidatPreview" class="flex gap-5 mt-5 mb-2 bg-slate-950/40 p-4 rounded-[22px] border border-white/5 shadow-inner">
                <!-- Ketua Preview -->
                <div class="flex flex-1 items-center gap-4 bg-slate-900/30 p-3.5 rounded-2xl border border-white/5 overflow-hidden">
                    <img id="modalKetuaFoto" src="" alt="Ketua" class="w-[84px] h-[100px] object-cover object-top rounded-xl border border-white/10 shadow-lg">
                    <div class="overflow-hidden flex-1">
                        <span class="text-xs font-semibold text-indigo-300 uppercase tracking-wider block">Calon Ketua</span>
                        <p id="modalKetuaNama" class="text-base font-extrabold text-white truncate mt-1"></p>
                    </div>
                </div>
                <!-- Wakil Preview -->
                <div class="flex flex-1 items-center gap-4 bg-slate-900/30 p-3.5 rounded-2xl border border-white/5 overflow-hidden">
                    <img id="modalWakilFoto" src="" alt="Wakil" class="w-[84px] h-[100px] object-cover object-top rounded-xl border border-white/10 shadow-lg">
                    <div class="overflow-hidden flex-1">
                        <span class="text-xs font-semibold text-indigo-300 uppercase tracking-wider block">Calon Wakil</span>
                        <p id="modalWakilNama" class="text-base font-extrabold text-white truncate mt-1"></p>
                    </div>
                </div>
            </div>
            
            <form action="" method="post" id="formVote" novalidate class="mt-6 flex flex-col gap-5">
                <div class="form-user-group-wrap flex flex-col gap-5">
                    <div class="form-group flex flex-col gap-2">
                        <label for="pemilih" class="font-outfit font-semibold text-xs text-slate-300 tracking-wider uppercase">Token Pemilih</label>
                        <input type="text" id="pemilih" name="token_pemilih" 
                               placeholder="Masukkan Token Anda" autocomplete="off" 
                               class="py-3 px-[18px] rounded-xl bg-slate-950/65 border border-white/10 font-sans text-sm text-white w-full transition-all duration-300 placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:bg-slate-950/85 focus:shadow-[0_0_0_4px_rgba(99,102,241,0.15)]"
                               value="<?= htmlspecialchars($_POST['token_pemilih'] ?? '') ?>"
                               required>
                    </div>
                    
                    <div class="form-group flex flex-col gap-2">
                        <label for="role" class="font-outfit font-semibold text-xs text-slate-300 tracking-wider uppercase">Role / Status</label>
                        <select id="role" name="role" class="py-3 px-[18px] rounded-xl bg-slate-950/65 border border-white/10 font-sans text-sm text-white w-full transition-all duration-300 focus:outline-none focus:border-indigo-500 focus:bg-slate-950/85 focus:shadow-[0_0_0_4px_rgba(99,102,241,0.15)] appearance-none bg-[url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22%3E%3Cpath stroke-linecap=%22round%22 stroke-linejoin=%22round%22 stroke-width=%222%22 d=%22M19 9l-7 7-7-7%22/%3E%3C/svg%3E')] bg-no-repeat bg-[position:right_18px_center] bg-[size:14px] pr-11">
                            <option value="siswa" <?= (!isset($_POST['role']) || $_POST['role'] === 'siswa') ? 'selected' : '' ?>>Siswa</option>
                            <option value="guru" <?= (isset($_POST['role']) && $_POST['role'] === 'guru') ? 'selected' : '' ?>>Guru</option>
                        </select>
                    </div>
                    
                    <div id="kelasWrap" class="form-group flex flex-col gap-2">
                        <label for="kelas" class="font-outfit font-semibold text-xs text-slate-300 tracking-wider uppercase">Kelas Pemilih</label>
                        <select id="kelas" name="kelas" class="pilih-kelas py-3 px-[18px] rounded-xl bg-slate-950/65 border border-white/10 font-sans text-sm text-white w-full transition-all duration-300 focus:outline-none focus:border-indigo-500 focus:bg-slate-950/85 focus:shadow-[0_0_0_4px_rgba(99,102,241,0.15)] appearance-none bg-[url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22%3E%3Cpath stroke-linecap=%22round%22 stroke-linejoin=%22round%22 stroke-width=%222%22 d=%22M19 9l-7 7-7-7%22/%3E%3C/svg%3E')] bg-no-repeat bg-[position:right_18px_center] bg-[size:14px] pr-11">
                            <option value="">Pilih Kelas</option>
                            <?php foreach ($kelas_list as $kelas): ?>
                                <option value="<?= htmlspecialchars($kelas['nama_kelas']) ?>"
                                    <?= (isset($_POST['kelas']) && $_POST['kelas'] === $kelas['nama_kelas']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kelas['nama_kelas']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <input type="hidden" name="kandidat_terpilih" id="kandidat_terpilih" value="<?= $_POST['kandidat_terpilih'] ?? '' ?>">
                
                <div class="flex gap-4 mt-6">
                    <button type="button" id="btnBatalVote" class="button-ok bg-white/5 border border-white/10 text-slate-300 w-full h-[52px] rounded-xl font-outfit font-bold cursor-pointer transition-all duration-300 hover:bg-red-500/15 hover:border-red-500/40 hover:text-red-300 hover:shadow-[0_8px_20px_rgba(239,68,68,0.2)] flex-1">Batal</button>
                    <button type="submit" name="kirim" class="submit-btn bg-gradient-to-r from-emerald-500 to-emerald-600 text-white border-none w-full h-[52px] rounded-xl font-outfit font-bold cursor-pointer transition-all duration-300 tracking-wide hover:shadow-[0_12px_25px_-5px_rgba(16,185,129,0.55)] active:translate-y-[1px] flex-[2]">Kirim Vote Sekarang</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Success -->
    <div id="modalSuccess" class="modal fixed inset-0 bg-slate-950/80 backdrop-blur-md justify-center items-center z-[1000] p-5">
        <div class="modal-content bg-slate-800/85 backdrop-blur-2xl border border-white/10 p-9 rounded-[28px] shadow-[0_30px_60px_-15px_rgba(0,0,0,0.6)] w-full max-w-[460px] text-center relative">
            <span class="close absolute top-5 right-6 cursor-pointer text-2xl text-slate-400 hover:text-red-500 transition-colors duration-200">&times;</span>
            <div class="icon-wrap w-20 h-20 rounded-full flex justify-center items-center mx-auto mb-5 text-4xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="44" height="44">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h2 class="font-outfit text-2xl font-bold text-white mb-3">Vote Berhasil!</h2>
            <p class="text-slate-300 text-sm mb-6 leading-relaxed">Terima kasih sudah memilih. Semoga pilihanmu membawa kebaikan bagi sekolah.</p>
            <button id="okBtn" class="button-ok bg-emerald-500 w-full h-[52px] border-none rounded-xl font-outfit text-base font-bold text-white cursor-pointer transition-all duration-300 hover:bg-emerald-600 hover:shadow-[0_8px_20px_rgba(16,185,129,0.35)] hover:-translate-y-[1px] active:translate-y-0">OK</button>
        </div>
    </div>

    <!-- Modal Error -->
    <div id="modalError" class="modal fixed inset-0 bg-slate-950/80 backdrop-blur-md justify-center items-center z-[1000] p-5">
        <div class="modal-content bg-slate-800/85 backdrop-blur-2xl border border-white/10 p-9 rounded-[28px] shadow-[0_30px_60px_-15px_rgba(0,0,0,0.6)] w-full max-w-[460px] text-center relative">
            <span class="close absolute top-5 right-6 cursor-pointer text-2xl text-slate-400 hover:text-red-500 transition-colors duration-200">&times;</span>
            <div class="icon-wrap w-20 h-20 rounded-full flex justify-center items-center mx-auto mb-5 text-4xl bg-red-500/10 text-red-400 border border-red-500/20">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="44" height="44">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
            <h2 class="font-outfit text-2xl font-bold text-white mb-3">Terjadi Kesalahan</h2>
            <p id="errorText" class="text-slate-300 text-sm mb-6 leading-relaxed"></p>
            <button id="errorBtn" class="button-ok bg-red-500 w-full h-[52px] border-none rounded-xl font-outfit text-base font-bold text-white cursor-pointer transition-all duration-300 hover:bg-red-600 hover:shadow-[0_8px_20px_rgba(239,68,68,0.35)] hover:-translate-y-[1px] active:translate-y-0">OK</button>
        </div>
    </div>

    <!-- Modal Token Used -->
    <div id="modalTokenUsed" class="modal fixed inset-0 bg-slate-950/80 backdrop-blur-md justify-center items-center z-[1000] p-5">
        <div class="modal-content bg-slate-800/85 backdrop-blur-2xl border border-white/10 p-9 rounded-[28px] shadow-[0_30px_60px_-15px_rgba(0,0,0,0.6)] w-full max-w-[460px] text-center relative">
            <span class="close absolute top-5 right-6 cursor-pointer text-2xl text-slate-400 hover:text-red-500 transition-colors duration-200">&times;</span>
            <div class="icon-wrap w-20 h-20 rounded-full flex justify-center items-center mx-auto mb-5 text-4xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="44" height="44">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h2 class="font-outfit text-2xl font-bold text-white mb-3">Token Sudah Digunakan</h2>
            <p class="text-slate-300 text-sm mb-6 leading-relaxed">Token ini sudah dipakai untuk memilih sebelumnya. Satu token hanya berlaku untuk satu kali pemungutan suara.</p>
            <button id="tokenUsedBtn" class="button-ok bg-amber-500 w-full h-[52px] border-none rounded-xl font-outfit text-base font-bold text-white cursor-pointer transition-all duration-300 hover:bg-amber-600 hover:shadow-[0_8px_20px_rgba(245,158,11,0.35)] hover:-translate-y-[1px] active:translate-y-0">OK</button>
        </div>
    </div>

    <!-- Internal JS -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const kandidatList = document.getElementById('kandidatList');
            const voteButtons = document.querySelectorAll('.vote-btn');
            const inputKandidat = document.getElementById('kandidat_terpilih');
            const roleSelect = document.getElementById('role');
            const kelasWrap = document.getElementById('kelasWrap');
            let selectedCard = null;
            
            // Modal Elements
            const modalSuccess = document.getElementById('modalSuccess');
            const modalError = document.getElementById('modalError');
            const modalTokenUsed = document.getElementById('modalTokenUsed');
            const modalVoteForm = document.getElementById('modalVoteForm');
            const errorText = document.getElementById('errorText');
            
            const closeVoteForm = document.getElementById('closeVoteForm');
            const btnBatalVote = document.getElementById('btnBatalVote');

            const showModal = (modal) => {
                modal.style.display = 'flex';
                // Trigger reflow untuk animasi
                modal.offsetHeight; 
                modal.classList.add('show');
            };

            const hideModal = (modal) => {
                modal.classList.remove('show');
                setTimeout(() => {
                    modal.style.display = 'none';
                }, 300);
            };

            // Periksa apakah kandidat sudah terpilih dari sesi sebelumnya (misalnya setelah POST gagal)
            const selectedKandidat = inputKandidat.value;
            if (selectedKandidat) {
                const card = document.querySelector(`.kandidat-card[data-id="${selectedKandidat}"]`);
                if (card) {
                    selectCard(card);
                }
            }

            // Tampilkan atau sembunyikan dropdown kelas berdasarkan role
            const updateKelasVisibility = () => {
                if (roleSelect.value === 'siswa') {
                    kelasWrap.style.display = 'flex';
                } else {
                    kelasWrap.style.display = 'none';
                    document.getElementById('kelas').value = '';
                }
            };
            
            roleSelect.addEventListener('change', updateKelasVisibility);
            updateKelasVisibility();

            // Klik pada tombol vote kandidat
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
                button.classList.add('bg-rose-500', 'text-white', 'border-rose-500', 'hover:bg-rose-600', 'hover:border-rose-600', 'hover:shadow-[0_8px_20px_rgba(244,63,94,0.35)]');
                button.classList.remove('bg-indigo-500/10', 'text-indigo-300', 'border-indigo-500/25', 'hover:bg-indigo-500', 'hover:text-white', 'hover:border-indigo-500', 'hover:shadow-[0_8px_20px_rgba(99,102,241,0.35)]');
                
                inputKandidat.value = cardId;
                kandidatList.classList.add('has-selection');
                selectedCard = card;

                // Extract Ketua & Wakil data
                const fotoKetua = card.querySelector('.foto-ketua').src;
                const namaKetua = card.querySelector('.nama-ketua').textContent;
                const fotoWakil = card.querySelector('.foto-wakil').src;
                const namaWakil = card.querySelector('.nama-wakil').textContent;

                // Update info di dalam modal vote form
                document.getElementById('modalVoteTitle').textContent = `Konfirmasi Pilihan: Pasangan Nomor ${cardId}`;
                document.getElementById('modalVoteSubtitle').textContent = `Anda memilih Pasangan Nomor ${cardId}. Silakan masukkan data Anda untuk melanjutkan pemilihan.`;
                
                // Populate preview elements
                document.getElementById('modalKetuaFoto').src = fotoKetua;
                document.getElementById('modalKetuaNama').textContent = namaKetua;
                document.getElementById('modalWakilFoto').src = fotoWakil;
                document.getElementById('modalWakilNama').textContent = namaWakil;

                // Buka modal secara otomatis
                showModal(modalVoteForm);
            }

            function deselectCard(card) {
                const button = card.querySelector('.vote-btn');
                const originalText = `Pilih Kandidat ${card.getAttribute('data-id')}`;
                
                card.classList.remove('active');
                button.textContent = originalText;
                button.classList.remove('bg-rose-500', 'text-white', 'border-rose-500', 'hover:bg-rose-600', 'hover:border-rose-600', 'hover:shadow-[0_8px_20px_rgba(244,63,94,0.35)]');
                button.classList.add('bg-indigo-500/10', 'text-indigo-300', 'border-indigo-500/25', 'hover:bg-indigo-500', 'hover:text-white', 'hover:border-indigo-500', 'hover:shadow-[0_8px_20px_rgba(99,102,241,0.35)]');
                
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
            btnBatalVote.addEventListener('click', handleCancelVote);

            const closeFeedbackBtns = document.querySelectorAll('#modalSuccess .close, #okBtn, #modalError .close, #errorBtn, #modalTokenUsed .close, #tokenUsedBtn');
            closeFeedbackBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    hideModal(modalSuccess);
                    hideModal(modalError);
                    hideModal(modalTokenUsed);
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 350);
                });
            });

            // Klik di luar area modal untuk menutup
            window.onclick = (e) => {
                if (e.target === modalSuccess) {
                    hideModal(modalSuccess);
                    setTimeout(() => { window.location.href = 'index.php'; }, 350);
                }
                if (e.target === modalError) {
                    hideModal(modalError);
                    setTimeout(() => { window.location.href = 'index.php'; }, 350);
                }
                if (e.target === modalTokenUsed) {
                    hideModal(modalTokenUsed);
                    setTimeout(() => { window.location.href = 'index.php'; }, 350);
                }
                if (e.target === modalVoteForm) {
                    handleCancelVote();
                }
            };

            // Trigger modal feedback dari PHP
            <?php if (!empty($successMessage)) : ?>
                showModal(modalSuccess);
            <?php elseif (!empty($errorMessage)) : ?>
                errorText.innerText = "<?= addslashes($errorMessage) ?>";
                showModal(modalError);
            <?php elseif (!empty($tokenUsedMessage)) : ?>
                showModal(modalTokenUsed);
            <?php endif; ?>

            // Validasi form sebelum submit
            document.getElementById('formVote').addEventListener('submit', function(e) {
                if (!inputKandidat.value) {
                    e.preventDefault();
                    alert('Silakan pilih salah satu pasangan kandidat terlebih dahulu!');
                    return false;
                }
                
                if (!document.getElementById('pemilih').value.trim()) {
                    e.preventDefault();
                    alert('Silakan masukkan token pemilih Anda!');
                    return false;
                }
                
                if (roleSelect.value === 'siswa' && !document.getElementById('kelas').value) {
                    e.preventDefault();
                    alert('Silakan pilih kelas Anda!');
                    return false;
                }
            });
        });
    </script>
</body>

</html>
<?php mysqli_close($db); ?>