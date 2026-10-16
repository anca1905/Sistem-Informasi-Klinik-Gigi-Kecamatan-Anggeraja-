<?php
// config/wa_helper.php
// WhatsApp Gateway Helper menggunakan Fonnte API

/**
 * Mengambil koneksi database aktif ($koneksi atau $conn)
 */
function getDbConnection() {
    global $koneksi, $conn;
    if (isset($koneksi) && $koneksi instanceof mysqli) {
        return $koneksi;
    }
    if (isset($conn) && $conn instanceof mysqli) {
        return $conn;
    }
    return null;
}

/**
 * Mengambil Fonnte API Token dari tabel settings atau fallback konstanta FONNTE_TOKEN
 */
function getFonnteToken() {
    $db = getDbConnection();
    if ($db) {
        $q = @mysqli_query($db, "SELECT fonnte_token FROM settings LIMIT 1");
        if ($q && $row = mysqli_fetch_assoc($q)) {
            if (!empty($row['fonnte_token'])) {
                return trim($row['fonnte_token']);
            }
        }
    }
    if (defined('FONNTE_TOKEN') && !empty(FONNTE_TOKEN)) {
        return trim(FONNTE_TOKEN);
    }
    return '';
}

/**
 * Menyimpan Fonnte API Token ke tabel settings
 */
function saveFonnteToken($token) {
    $db = getDbConnection();
    if (!$db) return false;

    // Pastikan tabel settings ada
    @mysqli_query($db, "CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fonnte_token VARCHAR(255) NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $safe_token = mysqli_real_escape_string($db, trim($token));
    $cek = mysqli_query($db, "SELECT id FROM settings LIMIT 1");
    if ($cek && mysqli_num_rows($cek) > 0) {
        return mysqli_query($db, "UPDATE settings SET fonnte_token = '$safe_token'");
    } else {
        return mysqli_query($db, "INSERT INTO settings (fonnte_token) VALUES ('$safe_token')");
    }
}

/**
 * Helper untuk melakukan HTTP POST ke Fonnte API
 */
function callFonnteApi($endpoint, $postFields = [], $token = null) {
    if ($token === null) {
        $token = getFonnteToken();
    }
    $token = trim($token);

    if (empty($token)) {
        return json_encode([
            'status' => false,
            'reason' => 'Token Fonnte belum dikonfigurasi'
        ]);
    }

    $url = 'https://api.fonnte.com/' . ltrim($endpoint, '/');

    $curl = curl_init();
    $curlOptions = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_HTTPHEADER => array(
            'Authorization: ' . $token
        ),
    );

    if (!empty($postFields)) {
        $curlOptions[CURLOPT_POSTFIELDS] = $postFields;
    }

    curl_setopt_array($curl, $curlOptions);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        return json_encode([
            'status' => false,
            'reason' => 'cURL Error: ' . $err
        ]);
    }

    return $response;
}

/**
 * Helper utama pengiriman pesan WhatsApp via Fonnte API
 * Mendukung pengiriman teks dan lampiran file (PDF, gambar, dll)
 */
function sendWA($nomor, $pesan, $filePath = null, $filename = null) {
    if (empty($nomor) || (empty($pesan) && empty($filePath))) return false;

    // Bersihkan nomor (hanya digit)
    $clean_nomor = preg_replace('/[^0-9]/', '', $nomor);
    if (substr($clean_nomor, 0, 1) === '0') {
        $clean_nomor = '62' . substr($clean_nomor, 1);
    }

    $postFields = [
        'target' => $clean_nomor,
        'message' => $pesan,
        'countryCode' => '62'
    ];

    if ($filePath && file_exists($filePath)) {
        $postFields['file'] = new CURLFile($filePath);
        if ($filename) {
            $postFields['filename'] = $filename;
        }
    }

    return callFonnteApi('send', $postFields);
}

/**
 * Cek status perangkat Fonnte
 */
function checkFonnteStatus($token = null) {
    return callFonnteApi('device', [], $token);
}

/**
 * Ambil QR Code Fonnte
 */
function getFonnteQR($token = null) {
    return callFonnteApi('qr', [], $token);
}

/**
 * Putuskan koneksi perangkat Fonnte
 */
function disconnectFonnte($token = null) {
    return callFonnteApi('disconnect', [], $token);
}

/**
 * Generate Gambar Surat Kontrol / Rekam Medis (PNG) menggunakan GD Library
 */
function createSuratKontrolImage($nama_pasien, $tgl_periksa, $tgl_kontrol, $diagnosa = '-', $nama_dokter = 'drg. Syamsuriah', $outputPath = null) {
    $w = 800;
    $h = 580;
    $im = imagecreatetruecolor($w, $h);

    // Warna
    $white     = imagecolorallocate($im, 255, 255, 255);
    $bg_header = imagecolorallocate($im, 6, 78, 59);       // emerald dark
    $green     = imagecolorallocate($im, 16, 185, 129);     // emerald light
    $text_dark = imagecolorallocate($im, 31, 41, 55);      // gray-800
    $text_gray = imagecolorallocate($im, 107, 114, 128);   // gray-500
    $border    = imagecolorallocate($im, 209, 213, 219);   // gray-300
    $gold_bg   = imagecolorallocate($im, 254, 243, 199);   // amber-100
    $gold_bd   = imagecolorallocate($im, 245, 158, 11);    // amber-500
    $gold_text = imagecolorallocate($im, 180, 83, 9);      // amber-700

    // Background Putih
    imagefill($im, 0, 0, $white);

    // Frame border
    imagerectangle($im, 15, 15, $w - 15, $h - 15, $border);
    imagerectangle($im, 16, 16, $w - 16, $h - 16, $green);

    // Header Banner
    imagefilledrectangle($im, 17, 17, $w - 17, 85, $bg_header);

    // Teks Header
    $font = 5;
    $txt1 = "KLINIK GIGI KECAMATAN ANGGERAJA";
    $txt2 = "Jl. Atlanta No.6, Kecamatan Anggeraja | Telp: (021) 555-8888";
    
    // Centering teks header
    $x1 = ($w - (strlen($txt1) * imagefontwidth(5))) / 2;
    imagestring($im, 5, (int)$x1, 30, $txt1, $white);
    $x2 = ($w - (strlen($txt2) * imagefontwidth(3))) / 2;
    imagestring($im, 3, (int)$x2, 55, $txt2, $white);

    // Judul Surat
    $judul = "SURAT JADWAL KUNJUNGAN KEMBALI";
    $xj = ($w - (strlen($judul) * imagefontwidth(5))) / 2;
    imagestring($im, 5, (int)$xj, 115, $judul, $bg_header);
    imageline($im, 240, 137, $w - 240, 137, $bg_header);

    // Isi Surat
    $intro = "Berdasarkan hasil pemeriksaan klinis, diberitahukan kepada pasien:";
    imagestring($im, 4, 50, 165, $intro, $text_dark);

    // Detail Pasien
    imagestring($im, 4, 60, 200, "Nama Pasien", $text_gray);
    imagestring($im, 4, 210, 200, ": " . $nama_pasien, $text_dark);

    imagestring($im, 4, 60, 230, "Tanggal Periksa", $text_gray);
    imagestring($im, 4, 210, 230, ": " . $tgl_periksa, $text_dark);

    $diag_clean = trim(preg_replace('/\s+/', ' ', strip_tags($diagnosa)));
    if (strlen($diag_clean) > 55) {
        $diag_clean = substr($diag_clean, 0, 52) . '...';
    }
    imagestring($im, 4, 60, 260, "Diagnosa", $text_gray);
    imagestring($im, 4, 210, 260, ": " . $diag_clean, $text_dark);

    // Kotak Tanggal Kontrol (Highlighted)
    imagefilledrectangle($im, 50, 305, $w - 50, 375, $gold_bg);
    imagerectangle($im, 50, 305, $w - 50, 375, $gold_bd);

    $lbl_ktrl = "JADWAL KONTROL BERIKUTNYA:";
    $xk1 = ($w - (strlen($lbl_ktrl) * imagefontwidth(4))) / 2;
    imagestring($im, 4, (int)$xk1, 318, $lbl_ktrl, $gold_text);

    $tgl_box = $tgl_kontrol ? $tgl_kontrol : "Sesuai petunjuk dokter";
    $xk2 = ($w - (strlen($tgl_box) * imagefontwidth(5))) / 2;
    imagestring($im, 5, (int)$xk2, 342, $tgl_box, $gold_text);

    // Keterangan bawah
    $ket = "Harap membawa surat ini saat kunjungan ulang. Semoga lekas sembuh!";
    imagestring($im, 3, 50, 400, $ket, $text_gray);

    // Area Tanda Tangan (kanan bawah)
    $hari_ini = date('d-m-Y');
    imagestring($im, 3, $w - 260, 440, "Anggeraja, " . $hari_ini, $text_dark);
    imagestring($im, 3, $w - 260, 458, "Dokter Pemeriksa,", $text_dark);
    imagestring($im, 4, $w - 260, 525, $nama_dokter, $text_dark);
    imageline($im, $w - 260, 545, $w - 60, 545, $border);

    if ($outputPath) {
        imagepng($im, $outputPath);
        imagedestroy($im);
        return $outputPath;
    } else {
        header('Content-Type: image/png');
        imagepng($im);
        imagedestroy($im);
        exit();
    }
}
?>
