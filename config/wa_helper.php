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
?>
