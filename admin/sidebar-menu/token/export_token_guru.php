<?php
session_start();
require '../../../db/db.php';

// Cek session login
if (!isset($_SESSION['login'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Query data token guru
$query = mysqli_query($db, "
    SELECT 
        g.*,
        CASE 
            WHEN v.id IS NOT NULL THEN 'Sudah Digunakan'
            ELSE 'Belum Digunakan'
        END AS status_penggunaan,
        v.created_at AS waktu_digunakan
    FROM tb_kode_guru g
    LEFT JOIN tb_voter v ON g.id = v.kode_guru_id
    ORDER BY g.created_at ASC
");

// Header untuk file Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=token_guru_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        .table {
            border-collapse: collapse;
            width: 100%;
        }
        .table th, .table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }
        .table th {
            background-color: #2c3e50;
            color: white;
            font-weight: bold;
        }
        .sudah { color: green; font-weight: bold; }
        .belum { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h2 style="text-align:center;">Daftar Token Guru & Karyawan</h2>
    <p><strong>Tanggal Ekspor:</strong> <?= date('d-m-Y H:i:s'); ?></p>
    <p><strong>Total Data:</strong> <?= mysqli_num_rows($query); ?></p>
    
    <table class="table">
        <thead>
            <tr>
                <th>No</th>
                <th>Token</th>
                <th>Status</th>
                <th>Waktu Dibuat</th>
                <th>Waktu Digunakan</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $total = 0;
            $sudah_dipakai = 0;
            $belum_dipakai = 0;
            
            while ($row = mysqli_fetch_assoc($query)):
                $total++;
                if ($row['status_penggunaan'] == 'Sudah Digunakan') {
                    $sudah_dipakai++;
                    $status_class = 'sudah';
                } else {
                    $belum_dipakai++;
                    $status_class = 'belum';
                }
            ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><strong><?= htmlspecialchars($row['kode']); ?></strong></td>
                <td class="<?= $status_class; ?>">
                    <?= $row['status_penggunaan']; ?>
                </td>
                <td><?= date('d-m-Y H:i:s', strtotime($row['created_at'])); ?></td>
                <td>
                    <?= $row['waktu_digunakan'] ? date('d-m-Y H:i:s', strtotime($row['waktu_digunakan'])) : '-'; ?>
                </td>
                <td>
                    <?php if ($row['status_penggunaan'] == 'Sudah Digunakan'): ?>
                        Sudah digunakan untuk voting
                    <?php else: ?>
                        Masih tersedia
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <div style="margin-top: 30px;">
        <h3>Statistik Token Guru</h3>
        <p><strong>Total Token:</strong> <?= $total; ?></p>
        <p><strong>Sudah Digunakan:</strong> <?= $sudah_dipakai; ?></p>
        <p><strong>Belum Digunakan:</strong> <?= $belum_dipakai; ?></p>
    </div>
</body>
</html>
<?php mysqli_close($db); ?>