<?php
// Konfigurasi Database
$host     = "localhost";
$user     = "root";
$pass     = ""; // Kosongkan jika pakai XAMPP default
$db_name  = "if0_41035429_arifa";

// Melakukan koneksi
$koneksi = mysqli_connect($host, $user, $pass, $db_name);

// Cek koneksi (Biar tau kalau error)
if (!$koneksi) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}

// Set timezone ke WIB (Waktu Indonesia Barat) biar jam antrian pas
date_default_timezone_set('Asia/Jakarta');
