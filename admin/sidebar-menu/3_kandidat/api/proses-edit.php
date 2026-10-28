<?php
session_start();
require '../../../../db/db.php';

if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $nomor_kandidat = (int)$_POST['nomor_kandidat'];
    $nama_ketua = $_POST['nama_ketua'];
    $nama_wakil = $_POST['nama_wakil'];

    $check_duplicate = mysqli_prepare($db, "SELECT id FROM tb_kandidat WHERE nomor_kandidat = ? AND id != ?");
    mysqli_stmt_bind_param($check_duplicate, "ii", $nomor_kandidat, $id);
    mysqli_stmt_execute($check_duplicate);
    mysqli_stmt_store_result($check_duplicate);

    if (mysqli_stmt_num_rows($check_duplicate) > 0) {
        mysqli_stmt_close($check_duplicate);

        $message = "Gagal update kandidat: Nomor Kandidat " . $nomor_kandidat . " sudah digunakan oleh kandidat lain.";

        echo "<script>
            alert('" . $message . "');
            window.location.href = '../daftar-kandidat.php'; 
        </script>";

        exit;
    }

    mysqli_stmt_close($check_duplicate);
    $queryOld = mysqli_query($db, "SELECT * FROM tb_kandidat WHERE id='$id'");
    $old = mysqli_fetch_assoc($queryOld);

    $foto_ketua = $old['foto_ketua'];
    $foto_wakil = $old['foto_wakil'];

    // Handle single paslon photo upload
    $uploadedFile = $_FILES['foto_paslon'] ?? ($_FILES['foto_ketua'] ?? null);
    if ($uploadedFile && !empty($uploadedFile['name'])) {
        $fileName = time() . "_paslon_" . basename($uploadedFile['name']);
        $target = "../../../uploads/" . $fileName;
        if (move_uploaded_file($uploadedFile['tmp_name'], $target)) {
            if (!empty($foto_ketua) && file_exists("../../../uploads/" . $foto_ketua)) {
                unlink("../../../uploads/" . $foto_ketua);
            }
            if (!empty($foto_wakil) && $foto_wakil !== $foto_ketua && file_exists("../../../uploads/" . $foto_wakil)) {
                unlink("../../../uploads/" . $foto_wakil);
            }
            $foto_ketua = $fileName;
            $foto_wakil = $fileName;
        }
    }

    
    $update = mysqli_query($db, "UPDATE tb_kandidat SET
        nomor_kandidat='$nomor_kandidat',
        nama_ketua='$nama_ketua',
        nama_wakil='$nama_wakil',
        foto_ketua='$foto_ketua',
        foto_wakil='$foto_wakil'
        WHERE id='$id'");

    if ($update) {
        header("Location: ../daftar-kandidat.php");
        exit;
    } else {
        echo "Gagal update kandidat: " . mysqli_error($db);
    }
}
