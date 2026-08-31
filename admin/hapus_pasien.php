<?php
include '../config/koneksi.php';

$id = $_GET['id'];
$hapus = mysqli_query($koneksi, "DELETE FROM pasien WHERE id='$id'");

if ($hapus) {
    echo "<script>alert('Data Pasien Terhapus'); window.location='data_pasien.php';</script>";
}
