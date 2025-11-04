<?php
session_start();
require '../../../db/db.php';

if (!isset($_SESSION['login'])) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET['kelas_id'])) {
    echo "<script>alert('Kelas tidak ditemukan!'); window.location.href='../kode-guru.php';</script>";
    exit;
}

$kelas_id = (int)$_GET['kelas_id'];

$qKelas = mysqli_query($db, "SELECT nama_kelas FROM tb_kelas WHERE id = $kelas_id");
if (mysqli_num_rows($qKelas) === 0) {
    echo "<script>alert('Kelas tidak valid!'); window.location.href='../kode-guru.php';</script>";
    exit;
}
$kelas = mysqli_fetch_assoc($qKelas)['nama_kelas'];

$qToken = mysqli_query($db, "SELECT * FROM tb_buat_token WHERE kelas_id = $kelas_id ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Token - <?= htmlspecialchars($kelas) ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
                * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background: #f4f6f9;
        }

        .sidebar {
            width: 220px;
            background: #2c3e50;
            color: #fff;
            padding: 20px;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .sidebar ul {
            list-style: none;
        }

        .sidebar ul li {
            margin: 15px 0;
        }

        .sidebar ul li a {
            color: #fff;
            text-decoration: none;
            display: block;
            padding: 8px 10px;
            border-radius: 5px;
        }

        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background: #34495e;
        }

        .main-content {
            flex: 1;
            padding: 30px;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #2b2b2b;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            background: #007bff;
            color: white;
            padding: 8px 14px;
            border-radius: 5px;
            transition: 0.3s;
        }

        .back-btn:hover {
            background: #0056b3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table th,
        table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }

        table th {
            background: #007bff;
            color: white;
        }

        tr:hover {
            background: #f1f1f1;
        }

        .status-used {
            color: #28a745;
            font-weight: bold;
        }

        .status-unused {
            color: #dc3545;
            font-weight: bold;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #777;
        }

        .btn-border-red {
            background: #fff;
            color: #e74c3c;
            border: 2px solid #e74c3c;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.2s;
        }

        .btn-border-red:hover {
            background: #e74c3c;
            color: #fff;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul>
            <li><a href="../../index.php">Dashboard</a></li>
            <li><a href="../../hasil-vote/result.php">Hasil</a></li>
            <li><a href="../../kandidat/daftar.php">Daftar Kandidat</a></li>
            <li><a href="../voter.php" class="active">Daftar Voter</a></li>
            <li><a href="../token-siswa.php">Kelas & Token Siswa</a></li>
            <li><a href="../kode-guru.php">Token Guru</a></li>
            <li><a href="../auth/logout.php">Logout</a></li>
        </ul>
    </div>
    <div class="main-content">
        <a href="../voter.php" class="back-btn">Kembali</a>
        <h1>Daftar Token Kelas <?= htmlspecialchars($kelas) ?></h1>
        <?php if (mysqli_num_rows($qToken) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Token</th>
                        <th>Status</th>
                        <th>Tanggal Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1;
                    while ($row = mysqli_fetch_assoc($qToken)): ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= htmlspecialchars($row['token']); ?></td>
                            <td>
                                <?php
                                $status = strtolower(trim($row['status_token'] ?? ''));
                                if (in_array($status, ['used', 'sudah', 'ya', 'true', '1', 'sudah digunakan'])) {
                                    echo '<span class="status-used">Sudah Dipakai</span>';
                                } else {
                                    echo '<span class="status-unused">Belum Dipakai</span>';
                                }
                                ?>
                            </td>
                            <td><?= htmlspecialchars($row['created_at'] ?? '-'); ?></td>
                            <td>
                                <a href="?hapus_token=<?= $row['id']; ?>&kelas_id=<?= $kelas_id; ?>"
                                    class="btn-border-red"
                                    onclick="return confirm('Hapus token ini?')">Hapus Token</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">Belum ada token yang dibuat untuk kelas ini.</div>
        <?php endif; ?>
    </div>
</body>

</html>