<?php
session_start();
require 'db/db.php';

if (isset($_GET['action']) && $_GET['action'] === 'check_token') {
    header('Content-Type: application/json');
    $token = trim($_GET['token'] ?? '');
    $role = trim($_GET['role'] ?? 'siswa');
    $kelas = trim($_GET['kelas'] ?? '');

    if ($token === '') {
        echo json_encode(['status' => 'empty', 'message' => 'Token belum diisi']);
        exit;
    }

    if ($role === 'siswa') {
        $stmt = mysqli_prepare($db, "
            SELECT t.id, t.status_token, k.nama_kelas 
            FROM tb_buat_token t
            LEFT JOIN tb_kelas k ON t.kelas_id = k.id
            WHERE t.token = ?
        ");
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $tid, $status, $nama_kelas);
        $found = mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if (!$found) {
            echo json_encode(['status' => 'invalid', 'message' => 'Token tidak terdaftar dalam database.']);
        } elseif ($status === 'sudah') {
            echo json_encode(['status' => 'used', 'message' => 'Token ini sudah digunakan untuk memilih sebelumnya.']);
        } elseif ($kelas !== '' && $nama_kelas !== $kelas) {
            echo json_encode(['status' => 'mismatch', 'message' => 'Token ini terdaftar untuk kelas ' . $nama_kelas . ', bukan kelas ' . $kelas . '.']);
        } else {
            echo json_encode(['status' => 'valid', 'message' => 'Token tersedia dan valid (' . $nama_kelas . ').']);
        }
    } else {
        $stmt = mysqli_prepare($db, "
            SELECT id, status_kode 
            FROM tb_kode_guru 
            WHERE kode = ?
        ");
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $gid, $status_kode);
        $found = mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if (!$found) {
            echo json_encode(['status' => 'invalid', 'message' => 'Token Guru tidak terdaftar dalam database.']);
        } elseif ($status_kode === 'sudah') {
            echo json_encode(['status' => 'used', 'message' => 'Token Guru ini sudah digunakan untuk memilih.']);
        } else {
            echo json_encode(['status' => 'valid', 'message' => 'Token Guru tersedia dan valid.']);
        }
    }
    exit;
}

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
    <title>Voting Kandidat OSIS Skalsa</title>
    <link rel="icon" href="admin/assets/img/logo osis.png">
    <link rel="preload" as="image" href="admin/assets/img/logo osis.png">
    <link rel="preload" as="image" href="admin/assets/img/logo sekolah.png">
    <?php 
    mysqli_data_seek($query, 0);
    while ($k = mysqli_fetch_assoc($query)): 
        if (!empty($k['foto_ketua'])): ?>
    <link rel="preload" as="image" href="admin/uploads/<?= htmlspecialchars($k['foto_ketua']) ?>">
        <?php endif; 
        if (!empty($k['foto_wakil'])): ?>
    <link rel="preload" as="image" href="admin/uploads/<?= htmlspecialchars($k['foto_wakil']) ?>">
        <?php endif; 
    endwhile; 
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
        <div class="flex justify-between items-center bg-white/90 backdrop-blur-md border border-slate-200 py-3.5 px-6 rounded-2xl shadow-sm">
            <h1 class="font-sans text-base lg:text-xl font-bold text-slate-900 tracking-tight">Selamat Datang di Forum Pemilihan Osis Skalsa</h1>
            <div>
                <button class="bg-white border border-slate-200 h-10 px-5 rounded-xl font-sans text-xs lg:text-sm font-semibold cursor-pointer text-slate-700 backdrop-blur-sm transition-colors hover:bg-slate-50" name="login" onclick="window.open('admin/auth/logout.php', '_blank', 'noopener,noreferrer')">Dashboard</button>
            </div>
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
                    
                    <h3 class="font-sans text-lg lg:text-xl font-bold text-slate-900 mb-4 text-center relative z-10">Pasangan Nomor <?= $row['nomor_kandidat']; ?></h3>
                    
                    <div class="flex gap-2.5 lg:gap-3 mb-4 relative z-10">
                        <div class="flex-1 min-w-0 bg-slate-50 border border-slate-200 rounded-xl p-2.5 lg:p-3 text-center flex flex-col items-center">
                            <img src="admin/uploads/<?= htmlspecialchars($row['foto_ketua']) ?>" alt="Ketua" class="foto-ketua w-full aspect-[4/5] object-cover object-top rounded-lg mb-2.5 shadow-sm" loading="eager" fetchpriority="high" decoding="async">
                            <h3 class="nama-ketua font-sans my-0.5 font-bold text-xs lg:text-base text-slate-900 truncate w-full text-center"><?= htmlspecialchars($row['nama_ketua']); ?></h3>
                            <small class="text-slate-500 text-[10px] lg:text-xs font-semibold uppercase tracking-wider whitespace-nowrap">Calon Ketua</small>
                        </div>
                        <div class="flex-1 min-w-0 bg-slate-50 border border-slate-200 rounded-xl p-2.5 lg:p-3 text-center flex flex-col items-center">
                            <img src="admin/uploads/<?= htmlspecialchars($row['foto_wakil']) ?>" alt="Wakil" class="foto-wakil w-full aspect-[4/5] object-cover object-top rounded-lg mb-2.5 shadow-sm" loading="eager" fetchpriority="high" decoding="async">
                            <h3 class="nama-wakil font-sans my-0.5 font-bold text-xs lg:text-base text-slate-900 truncate w-full text-center"><?= htmlspecialchars($row['nama_wakil']); ?></h3>
                            <small class="text-slate-500 text-[10px] lg:text-xs font-semibold uppercase tracking-wider whitespace-nowrap">Calon Wakil</small>
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

    <div id="modalVoteForm" class="modal fixed inset-0 bg-slate-950/60 justify-center items-center z-[1000] p-4 sm:p-6">
        <div class="modal-content bg-white border border-slate-200 p-6 sm:p-8 rounded-3xl shadow-2xl w-full max-w-[1100px] text-left relative max-h-[90vh] overflow-y-auto">
            <button type="button" class="absolute top-5 right-6 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 font-sans text-xs sm:text-sm font-semibold transition-all duration-200 flex items-center gap-1.5 cursor-pointer z-20" id="closeVoteForm">
                <i class="bi bi-arrow-left-right"></i>
                <span>Ganti Pilihan</span>
            </button>
            <h2 id="modalVoteTitle" class="font-sans text-xl sm:text-2xl font-bold text-slate-900 mb-2 border-b border-slate-100 pb-3 pr-32">Konfirmasi Pilihan</h2>
            
            <div id="modalKandidatPreview" class="flex flex-col sm:flex-row gap-4 mt-4 mb-4 bg-slate-50 p-4 rounded-2xl border border-slate-200">
                <div class="flex flex-1 items-center gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <img id="modalKetuaFoto" src="" alt="Ketua" class="w-[90px] sm:w-[120px] h-[110px] sm:h-[145px] object-cover object-top rounded-xl border border-slate-200 shadow-sm shrink-0">
                    <div class="overflow-hidden flex-1 min-w-0">
                        <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider block mb-1">Calon Ketua</span>
                        <p id="modalKetuaNama" class="text-base sm:text-lg font-bold text-slate-900 truncate"></p>
                    </div>
                </div>
                <div class="flex flex-1 items-center gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <img id="modalWakilFoto" src="" alt="Wakil" class="w-[90px] sm:w-[120px] h-[110px] sm:h-[145px] object-cover object-top rounded-xl border border-slate-200 shadow-sm shrink-0">
                    <div class="overflow-hidden flex-1 min-w-0">
                        <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider block mb-1">Calon Wakil</span>
                        <p id="modalWakilNama" class="text-base sm:text-lg font-bold text-slate-900 truncate"></p>
                    </div>
                </div>
            </div>
            
            <form action="" method="post" id="formVote" novalidate class="mt-4 flex flex-col gap-5">
                <div class="form-user-group-wrap grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="form-group flex flex-col gap-2 md:col-span-2">
                        <label for="pemilih" class="font-sans font-semibold text-xs text-slate-600 tracking-wider uppercase">Token</label>
                        <div class="relative">
                            <input type="text" id="pemilih" name="token_pemilih" 
                                   placeholder="Masukkan Token" autocomplete="off" 
                                   class="py-3.5 px-5 rounded-xl bg-white border border-slate-200 font-sans text-sm sm:text-base text-slate-700 w-full focus:outline-none focus:border-emerald-500 transition-colors"
                                   value="<?= htmlspecialchars($_POST['token_pemilih'] ?? '') ?>"
                                   required>
                            <span id="tokenSpinner" class="hidden absolute right-4 top-4 text-slate-400 text-xs">Mengecek...</span>
                        </div>
                        <div id="tokenFeedback" class="hidden text-xs font-semibold mt-1 px-1"></div>
                    </div>
                    
                    <div class="form-group flex flex-col gap-2">
                        <label class="font-sans font-semibold text-xs text-slate-600 tracking-wider uppercase">Status</label>
                        <input type="hidden" id="role" name="role" value="<?= htmlspecialchars($_POST['role'] ?? 'siswa') ?>">
                        <div class="flex gap-3 w-full">
                            <button type="button" data-role="siswa" class="role-btn flex-1 py-3.5 px-4 rounded-xl font-sans text-sm font-semibold transition-all duration-200 cursor-pointer text-center">
                                Siswa / Siswi
                            </button>
                            <button type="button" data-role="guru" class="role-btn flex-1 py-3.5 px-4 rounded-xl font-sans text-sm font-semibold transition-all duration-200 cursor-pointer text-center">
                                Guru / Karyawan
                            </button>
                        </div>
                    </div>
                    
                    <div id="kelasWrap" class="form-group flex flex-col gap-2">
                        <label for="kelas" class="font-sans font-semibold text-xs text-slate-600 tracking-wider uppercase">Kelas Pemilih</label>
                        <select id="kelas" name="kelas" class="pilih-kelas py-3.5 px-5 rounded-xl bg-white border border-slate-200 font-sans text-sm sm:text-base text-slate-700 w-full focus:outline-none appearance-none bg-[url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%236b7280%22%3E%3Cpath stroke-linecap=%22round%22 stroke-linejoin=%22round%22 stroke-width=%222%22 d=%22M19 9l-7 7-7-7%22/%3E%3C/svg%3E')] bg-no-repeat bg-[position:right_18px_center] bg-[size:14px] pr-11">
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
                
                <div class="mt-4">
                    <button type="submit" name="kirim" class="submit-btn bg-emerald-600 text-white border-none w-full h-[54px] rounded-xl font-sans font-bold text-base cursor-pointer tracking-wide hover:bg-emerald-700 transition-colors">Konfirmasi</button>
                </div>
            </form>
        </div>
    </div>

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
            <div class="icon-wrap w-24 h-24 rounded-full flex justify-center items-center mx-auto mb-6 text-4xl bg-red-100 text-red-600 border border-red-200 shadow-inner">
                <i class="bi bi-x-lg text-5xl"></i>
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const kandidatList = document.getElementById('kandidatList');
            const voteButtons = document.querySelectorAll('.vote-btn');
            const inputKandidat = document.getElementById('kandidat_terpilih');
            const roleInput = document.getElementById('role');
            const roleButtons = document.querySelectorAll('.role-btn');
            const kelasWrap = document.getElementById('kelasWrap');
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

            const updateKelasVisibility = () => {
                if (roleInput.value === 'siswa') {
                    kelasWrap.style.display = 'flex';
                } else {
                    kelasWrap.style.display = 'none';
                    document.getElementById('kelas').value = '';
                }
            };

            const setRole = (selectedRole) => {
                roleInput.value = selectedRole;
                roleButtons.forEach(btn => {
                    if (btn.getAttribute('data-role') === selectedRole) {
                        btn.className = 'role-btn flex-1 py-3 px-4 rounded-xl bg-emerald-50/30 border-2 border-emerald-600 text-emerald-700 font-sans text-sm font-bold transition-all duration-200 cursor-pointer shadow-sm';
                    } else {
                        btn.className = 'role-btn flex-1 py-3 px-4 rounded-xl bg-white border border-slate-200 text-slate-600 font-sans text-sm font-medium transition-all duration-200 cursor-pointer hover:bg-slate-50';
                    }
                });
                updateKelasVisibility();
            };

            roleButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    setRole(this.getAttribute('data-role'));
                });
            });

            setRole(roleInput.value || 'siswa');

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

                const fotoKetua = card.querySelector('.foto-ketua').src;
                const namaKetua = card.querySelector('.nama-ketua').textContent;
                const fotoWakil = card.querySelector('.foto-wakil').src;
                const namaWakil = card.querySelector('.nama-wakil').textContent;

                document.getElementById('modalVoteTitle').textContent = `Konfirmasi Pilihan: Pasangan Nomor ${cardId}`;
                
                document.getElementById('modalKetuaFoto').src = fotoKetua;
                document.getElementById('modalKetuaNama').textContent = namaKetua;
                document.getElementById('modalWakilFoto').src = fotoWakil;
                document.getElementById('modalWakilNama').textContent = namaWakil;

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
                if (e.target === modalVoteForm) {
                    handleCancelVote();
                }
            };

            <?php if (!empty($successMessage)) : ?>
                showModal(modalSuccess);
            <?php elseif (!empty($errorMessage)) : ?>
                errorText.innerText = "<?= addslashes($errorMessage) ?>";
                showModal(modalError);
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
                const roleVal = roleInput.value;
                const kelasVal = kelasSelect ? kelasSelect.value : '';

                if (!tokenVal) {
                    tokenFeedback.classList.add('hidden');
                    tokenFeedback.textContent = '';
                    tokenInput.classList.remove('border-emerald-500', 'border-red-500', 'border-amber-500');
                    tokenInput.classList.add('border-slate-200');
                    isTokenValid = false;
                    return;
                }

                tokenSpinner.classList.remove('hidden');

                fetch(`index.php?action=check_token&token=${encodeURIComponent(tokenVal)}&role=${encodeURIComponent(roleVal)}&kelas=${encodeURIComponent(kelasVal)}`)
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
                setTimeout(() => {
                    showModal(modalError);
                }, 200);
            };

            document.getElementById('formVote').addEventListener('submit', function(e) {
                if (!inputKandidat.value) {
                    e.preventDefault();
                    showCustomAlert('Peringatan', 'Silakan pilih salah satu pasangan kandidat terlebih dahulu!');
                    return false;
                }
                
                if (!document.getElementById('pemilih').value.trim()) {
                    e.preventDefault();
                    showCustomAlert('Peringatan', 'masukkan token terlebih dahulu!');
                    return false;
                }
                
                if (roleInput.value === 'siswa' && !document.getElementById('kelas').value) {
                    e.preventDefault();
                    showCustomAlert('Peringatan', 'Silakan pilih kelas terlebih dahulu!');
                    return false;
                }
            });
        });
    </script>
</body>

</html>
<?php mysqli_close($db); ?>

