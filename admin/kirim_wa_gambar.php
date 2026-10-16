<?php
session_start();
include '../config/koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    echo json_encode(['status' => false, 'message' => 'Sesi login tidak valid.']);
    exit();
}

$image_data = isset($_POST['image_base64']) ? $_POST['image_base64'] : '';
$nomor_wa = isset($_POST['nomor_wa']) ? trim($_POST['nomor_wa']) : '';
$pesan = isset($_POST['pesan']) ? trim($_POST['pesan']) : '';
$nama_pasien = isset($_POST['nama_pasien']) ? trim($_POST['nama_pasien']) : 'pasien';

if (empty($image_data)) {
    echo json_encode(['status' => false, 'message' => 'Data gambar tidak ditemukan.']);
    exit();
}

// Bersihkan data base64
$image_data = preg_replace('/^data:image\/\w+;base64,/', '', $image_data);
$image_data = str_replace(' ', '+', $image_data);
$decoded_image = base64_decode($image_data);

if (!$decoded_image) {
    echo json_encode(['status' => false, 'message' => 'Format gambar tidak valid.']);
    exit();
}

if (!is_dir('../assets/surat_kontrol')) {
    @mkdir('../assets/surat_kontrol', 0777, true);
}

$safe_nama = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nama_pasien);
$filename = "Surat_RekamMedis_" . $safe_nama . "_" . time() . ".png";
$filepath = __DIR__ . "/../assets/surat_kontrol/" . $filename;

$saved = file_put_contents($filepath, $decoded_image);
if (!$saved) {
    echo json_encode(['status' => false, 'message' => 'Gagal menyimpan file gambar di server.']);
    exit();
}

// Cek apakah token Fonnte ada dan nomor telepon valid
$token = getFonnteToken();
$sent_via_fonnte = false;
$fonnte_response = null;

if (!empty($token) && !empty($nomor_wa)) {
    $res = sendWA($nomor_wa, $pesan, $filepath, $filename);
    $sent_via_fonnte = true;
    $fonnte_response = is_string($res) ? json_decode($res, true) : $res;
}

echo json_encode([
    'status' => true,
    'message' => $sent_via_fonnte ? 'Gambar berhasil dikirim ke WhatsApp via Fonnte Gateway!' : 'Gambar berhasil disimpan.',
    'sent_via_fonnte' => $sent_via_fonnte,
    'fonnte_response' => $fonnte_response,
    'image_url' => '../assets/surat_kontrol/' . $filename,
    'filename' => $filename
]);
