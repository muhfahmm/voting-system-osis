<?php
require '../../db/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kelas_id'])) {
    $kelas_id = (int) $_POST['kelas_id'];

    $qKelas = mysqli_query($db, "SELECT nama_kelas FROM tb_kelas WHERE id = $kelas_id");
    $kelas = mysqli_fetch_assoc($qKelas)['nama_kelas'] ?? 'Tidak Diketahui';

    $query = mysqli_query($db, "
        SELECT t.token, t.status_token, k.nama_kelas, t.created_at
        FROM tb_buat_token t
        LEFT JOIN tb_kelas k ON t.kelas_id = k.id
        WHERE t.kelas_id = $kelas_id
        ORDER BY t.created_at ASC
    ");

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=Token_{$kelas}.xls");

    echo "<table border='1'>";
    echo "<tr style='background:#2c3e50;color:white;'>
            <th>No</th>
            <th>Token</th>
            <th>Kelas</th>
            <th>Status</th>
            <th>Tanggal Dibuat</th>
          </tr>";

    $no = 1;
    while ($row = mysqli_fetch_assoc($query)) {
        $status = ($row['status_token'] === 'sudah')
            ? 'Sudah Dipakai'
            : 'Belum Dipakai';

        echo "<tr>
                <td>{$no}</td>
                <td>{$row['token']}</td>
                <td>{$row['nama_kelas']}</td>
                <td>{$status}</td>
                <td>{$row['created_at']}</td>
              </tr>";
        $no++;
    }

    echo "</table>";
    exit;
} else {
    echo "Kelas tidak ditemukan.";
    exit;
}
