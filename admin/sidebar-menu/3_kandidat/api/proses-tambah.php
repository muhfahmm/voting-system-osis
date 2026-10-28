<?php
require '../../../../db/db.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomor = $_POST['nomor_kandidat'];
    $nama_ketua = $_POST['nama_ketua'];
    $kelas_ketua = $_POST['kelas_ketua'];
    $nama_wakil = $_POST['nama_wakil'];
    $kelas_wakil = $_POST['kelas_wakil'];

    
    // Handle single paslon photo
    $uploadedFile = $_FILES['foto_paslon'] ?? ($_FILES['foto_ketua'] ?? null);

    if ($uploadedFile && !empty($uploadedFile['name'])) {
        $foto_name = $uploadedFile['name'];
        $tmp_name = $uploadedFile['tmp_name'];
        $fileName = time() . "_paslon_" . basename($foto_name);
        $target_path = "../../../uploads/" . $fileName;

        if (move_uploaded_file($tmp_name, $target_path)) {
            // Save same filename for foto_ketua and foto_wakil to maintain full DB schema compatibility
            $sql = "INSERT INTO tb_kandidat (nomor_kandidat, nama_ketua, kelas_ketua, foto_ketua, nama_wakil, kelas_wakil, foto_wakil)
                    VALUES ('$nomor', '$nama_ketua', '$kelas_ketua', '$fileName', '$nama_wakil', '$kelas_wakil', '$fileName')";

            if (mysqli_query($db, $sql)) {
                echo "<script>alert('Kandidat berhasil ditambahkan!'); window.location.href='../daftar-kandidat.php';</script>";
            } else {
                echo "Error: " . mysqli_error($db);
            }
        } else {
            echo "Gagal upload foto!";
        }
    } else {
        echo "Silakan pilih foto kandidat!";
    }
}
