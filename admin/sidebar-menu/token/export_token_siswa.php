<?php
session_start();
require '../../../db/db.php';

if (!isset($_SESSION['login'])) {
    header("Location: ../../auth/login.php");
    exit;
}

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
    <title>Cetak Token Siswa - Kelas <?= htmlspecialchars($nama_kelas); ?></title>
    <link rel="icon" href="../../assets/img/logo osis.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
            padding: 24px;
        }
        .container {
            max-width: 960px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.08);
            padding: 32px;
        }

        /* Action bar for screen */
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 28px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        .btn-group {
            display: flex;
            gap: 10px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: all 0.2s ease;
        }
        .btn-print {
            background-color: #059669;
            color: #ffffff;
        }
        .btn-print:hover {
            background-color: #047857;
        }
        .btn-back {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        .btn-back:hover {
            background-color: #e2e8f0;
        }

        /* Header Document */
        .kop-surat {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .kop-logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
        }
        .kop-text {
            text-align: center;
            flex: 1;
            padding: 0 16px;
        }
        .kop-text h2 {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .kop-text h3 {
            font-size: 15px;
            font-weight: 700;
            color: #059669;
            margin-top: 2px;
        }
        .kop-text p {
            font-size: 11px;
            color: #64748b;
            margin-top: 4px;
        }

        /* Detail Info */
        .meta-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 24px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
        }
        .meta-item .label {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748b;
            letter-spacing: 0.5px;
        }
        .meta-item .value {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 2px;
        }

        /* Mode Selector */
        .view-mode-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
        }
        .tab-btn {
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
            cursor: pointer;
        }
        .tab-btn.active {
            background: #059669;
            color: #ffffff;
            border-color: #059669;
        }

        /* Token Card Slips Grid (Format Kupon Gunting Siap Cetak untuk Siswa) */
        .token-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }
        .token-slip {
            border: 1.5px dashed #94a3b8;
            border-radius: 10px;
            padding: 12px 14px;
            background: #ffffff;
            position: relative;
            page-break-inside: avoid;
        }
        .slip-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .slip-title {
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .slip-class {
            font-size: 11px;
            font-weight: 800;
            color: #059669;
        }
        .slip-token {
            font-family: 'JetBrains Mono', monospace;
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            text-align: center;
            letter-spacing: 2px;
            padding: 6px 0;
            background: #f8fafc;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            margin-bottom: 6px;
        }
        .slip-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 9px;
            color: #94a3b8;
        }
        .slip-status {
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
        }
        .slip-status.belum {
            background: #ecfdf5;
            color: #059669;
        }
        .slip-status.sudah {
            background: #fef2f2;
            color: #dc2626;
        }

        /* Token Table Format */
        .token-table-wrap {
            display: none;
            overflow-x: auto;
            margin-bottom: 24px;
        }
        .custom-table {
            width: 100%;
            border-collapse: collapse;
        }
        .custom-table th, .custom-table td {
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            font-size: 12px;
            text-align: left;
        }
        .custom-table th {
            background: #f8fafc;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            font-size: 11px;
        }
        .custom-table td.center, .custom-table th.center {
            text-align: center;
        }
        .badge-token {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            background: #ecfdf5;
            color: #059669;
            padding: 2px 8px;
            border-radius: 4px;
            border: 1px solid #a7f3d0;
            display: inline-block;
        }

        /* Signature block */
        .signature-block {
            margin-top: 36px;
            display: flex;
            justify-content: flex-end;
            page-break-inside: avoid;
        }
        .signature-box {
            text-align: center;
            width: 220px;
        }
        .signature-box .date {
            font-size: 12px;
            color: #475569;
            margin-bottom: 6px;
        }
        .signature-box .title {
            font-size: 12px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 60px;
        }
        .signature-box .name {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            border-bottom: 1px solid #0f172a;
            padding-bottom: 2px;
        }
        .signature-box .role {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }

        /* PRINT STYLES */
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .container {
                max-width: 100%;
                margin: 0;
                padding: 0;
                border-radius: 0;
                box-shadow: none;
            }
            .action-bar, .view-mode-tabs {
                display: none !important;
            }
            .token-grid {
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 8px !important;
            }
            .token-slip {
                border: 1px dashed #64748b !important;
                padding: 8px 10px !important;
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
<body>

<div class="container">
    <!-- Action Bar for Screen -->
    <div class="action-bar">
        <div>
            <h1 style="font-size: 18px; font-weight: 800; color: #0f172a;">Lembar Token Pemilihan - Kelas <?= htmlspecialchars($nama_kelas); ?></h1>
            <p style="font-size: 12px; color: #64748b; margin-top: 2px;">Siap dicetak langsung atau disimpan sebagai PDF (Ctrl+P / Simpan sebagai PDF)</p>
        </div>
        <div class="btn-group">
            <button type="button" onclick="window.print()" class="btn btn-print">
                <i class="bi bi-printer-fill"></i>
                <span>Cetak / Simpan PDF (1 Kelas)</span>
            </button>
            <a href="../5_token-siswa/token-siswa.php?kelas_id=<?= $kelas_id; ?>" class="btn btn-back">
                <i class="bi bi-arrow-left"></i>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    <!-- Pilihan Tampilan Cetak -->
    <div class="view-mode-tabs">
        <button type="button" class="tab-btn active" onclick="switchView('grid', this)">
            <i class="bi bi-grid-3x3-gap-fill"></i> Format Kupon Gunting (Rekomendasi Cetak)
        </button>
        <button type="button" class="tab-btn" onclick="switchView('table', this)">
            <i class="bi bi-table"></i> Format Tabel Presensi
        </button>
    </div>

    <!-- KOP Surat / Header Dokumen Resmi -->
    <div class="kop-surat">
        <img src="../../assets/img/logo osis.png" alt="Logo" class="kop-logo" onerror="this.style.display='none'">
        <div class="kop-text">
            <h2>Panitia Pemilihan Ketua & Wakil Ketua OSIS</h2>
            <h3>LEMBAR DISTRIBUSI TOKEN PEMILIH SISWA</h3>
            <p>Sistem E-Voting Pemilihan OSIS | Token bersifat rahasia dan hanya dapat digunakan 1 (satu) kali vote.</p>
        </div>
        <div style="width: 72px;"></div>
    </div>

    <!-- Informasi Detail Kelas -->
    <div class="meta-card">
        <div class="meta-item">
            <div class="label">Kelas</div>
            <div class="value" style="color: #059669;"><?= htmlspecialchars($nama_kelas); ?></div>
        </div>
        <div class="meta-item">
            <div class="label">Jumlah Siswa</div>
            <div class="value"><?= $jumlah_siswa; ?> Siswa</div>
        </div>
        <div class="meta-item">
            <div class="label">Total Token Dibuat</div>
            <div class="value"><?= $total_token; ?> Token</div>
        </div>
        <div class="meta-item">
            <div class="label">Status Token</div>
            <div class="value" style="font-size: 13px;">
                <span style="color: #059669;"><?= $belum_pakai; ?> Siap Pakai</span> / 
                <span style="color: #dc2626;"><?= $sudah_pakai; ?> Terpakai</span>
            </div>
        </div>
        <div class="meta-item">
            <div class="label">Tanggal Cetak</div>
            <div class="value" style="font-size: 13px; font-weight: 600;"><?= date('d F Y, H:i'); ?> WIB</div>
        </div>
    </div>

    <!-- TAMPILAN 1: GRID KUPON GUNTING (Siap digunting dan dibagikan per siswa) -->
    <div id="gridWrap">
        <?php if ($total_token > 0): ?>
            <div class="token-grid">
                <?php foreach ($tokens as $idx => $t): ?>
                    <div class="token-slip">
                        <div class="slip-header">
                            <span class="slip-title">E-VOTING OSIS</span>
                            <span class="slip-class"><?= htmlspecialchars($nama_kelas); ?></span>
                        </div>
                        <div class="slip-token"><?= htmlspecialchars($t['token']); ?></div>
                        <div class="slip-footer">
                            <span>No. #<?= $idx + 1; ?></span>
                            <span class="slip-status <?= $t['status_token'] === 'sudah' ? 'sudah' : 'belum'; ?>">
                                <?= $t['status_token'] === 'sudah' ? 'TERPAKAI' : 'AKTIF'; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: #64748b; padding: 40px 0;">Belum ada token yang dibuat untuk kelas ini.</p>
        <?php endif; ?>
    </div>

    <!-- TAMPILAN 2: TABEL PRESENSI (Dengan kolom tanda tangan/paraf) -->
    <div id="tableWrap" class="token-table-wrap">
        <?php if ($total_token > 0): ?>
            <table class="custom-table">
                <thead>
                    <tr>
                        <th class="center" style="width: 40px;">No</th>
                        <th class="center" style="width: 140px;">Token Voting</th>
                        <th class="center" style="width: 110px;">Status</th>
                        <th>Nama Siswa Penerima Token</th>
                        <th class="center" style="width: 130px;">Paraf / Tanda Tangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tokens as $idx => $t): ?>
                        <tr>
                            <td class="center"><?= $idx + 1; ?></td>
                            <td class="center"><span class="badge-token"><?= htmlspecialchars($t['token']); ?></span></td>
                            <td class="center">
                                <?= $t['status_token'] === 'sudah' ? '<span style="color:#dc2626;font-weight:700;">Terpakai</span>' : '<span style="color:#059669;font-weight:700;">Belum</span>'; ?>
                            </td>
                            <td></td>
                            <td class="center" style="color: #94a3b8; font-size: 10px;"><?= $idx + 1; ?>. ............</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #64748b; padding: 40px 0;">Belum ada token yang dibuat untuk kelas ini.</p>
        <?php endif; ?>
    </div>

    <!-- Kolom Tanda Tangan Pengesahan Panitia & Wali Kelas -->
    <div class="signature-block">
        <div class="signature-box">
            <div class="date"><?= date('d F Y'); ?></div>
            <div class="title">Panitia / Saksi Kelas,</div>
            <div class="name">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</div>
            <div class="role">Wali Kelas / Saksi TPS</div>
        </div>
    </div>
</div>

<script>
function switchView(mode, btn) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const grid = document.getElementById('gridWrap');
    const table = document.getElementById('tableWrap');

    if (mode === 'grid') {
        grid.style.display = 'block';
        table.style.display = 'none';
    } else {
        grid.style.display = 'none';
        table.style.display = 'block';
    }
}
</script>

</body>
</html>