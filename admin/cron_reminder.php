<?php
// admin/cron_reminder.php
// Script otomatis untuk mengirim pengingat jadwal kontrol gigi via Fonnte API

require_once __DIR__ . '/../config/koneksi.php';

// Cek apakah dipanggil via browser (perlu cek login admin) atau via CLI / cron
$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Jika via web, hanya admin yang boleh akses langsung (kecuali jika ada secret key jika diperlukan)
    $auth_key = $_GET['key'] ?? '';
    $valid_key = defined('CRON_KEY') ? CRON_KEY : 'arifa_cron_reminder_2026';
    
    if ($auth_key !== $valid_key) {
        if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || (isset($_SESSION['role']) && $_SESSION['role'] !== 'Admin')) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['status' => false, 'message' => 'Unauthorized']);
            exit;
        }
    }
}

$token = getFonnteToken();
if (empty($token)) {
    $res = ['status' => false, 'message' => 'Token Fonnte belum diatur. Pengingat tidak dapat dikirim.'];
    if (!$is_cli) {
        header('Content-Type: application/json');
        echo json_encode($res);
    } else {
        echo $res['message'] . PHP_EOL;
    }
    exit;
}

$hari_ini = date('Y-m-d');
$jam_sekarang = (int)date('H');

// Query rekam medis yang memiliki tanggal kunjungan berikutnya >= hari ini
$query = "
    SELECT rm.id, rm.nama_pasien, rm.kunjungan_berikutnya, rm.reminder_3_days, rm.reminder_1_day, rm.reminder_today, p.no_telepon 
    FROM rekam_medis rm 
    JOIN pasien p ON rm.id_pasien = p.id 
    WHERE rm.kunjungan_berikutnya IS NOT NULL 
    AND p.no_telepon IS NOT NULL 
    AND p.no_telepon != ''
    AND rm.kunjungan_berikutnya >= '$hari_ini'
";

$result = mysqli_query($koneksi, $query);
$sent_count = 0;
$logs = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $id_rm = $row['id'];
        $nama = $row['nama_pasien'];
        $no_telp = $row['no_telepon'];
        $tgl_kontrol = $row['kunjungan_berikutnya'];
        $tgl_format = date('d-m-Y', strtotime($tgl_kontrol));

        // Hitung selisih hari
        $diff_days = (int)floor((strtotime($tgl_kontrol) - strtotime($hari_ini)) / (60 * 60 * 24));

        // 1. Pengingat H-3 (3 hari sebelumnya)
        if ($diff_days === 3 && (int)$row['reminder_3_days'] === 0) {
            $pesan = "Halo *$nama*,\n\nIni pesan pengingat dari *Klinik Gigi Anggeraja*.\nJadwal kontrol gigi Anda adalah *3 hari lagi* pada tanggal *$tgl_format*.\n\nMohon persiapkan diri dan datang tepat waktu. Terima kasih! 🙏";
            $send_res = sendWA($no_telp, $pesan);
            mysqli_query($koneksi, "UPDATE rekam_medis SET reminder_3_days = 1 WHERE id = $id_rm");
            $sent_count++;
            $logs[] = "H-3 dikirim ke $nama ($no_telp)";
        }

        // 2. Pengingat H-1 (Besok)
        if ($diff_days === 1 && (int)$row['reminder_1_day'] === 0) {
            $pesan = "Halo *$nama*,\n\nIni pengingat dari *Klinik Gigi Anggeraja*.\nJadwal kontrol gigi Anda adalah *BESOK*, tanggal *$tgl_format*.\n\nJangan sampai lupa ya! Kami siap melayani Anda. Terima kasih! 🙏";
            $send_res = sendWA($no_telp, $pesan);
            mysqli_query($koneksi, "UPDATE rekam_medis SET reminder_1_day = 1 WHERE id = $id_rm");
            $sent_count++;
            $logs[] = "H-1 dikirim ke $nama ($no_telp)";
        }

        // 3. Pengingat Hari H
        if ($diff_days === 0 && (int)$row['reminder_today'] === 0) {
            $pesan = "Halo *$nama*,\n\n*HARI INI* adalah jadwal kontrol gigi Anda di *Klinik Gigi Anggeraja* (Tanggal: *$tgl_format*).\n\nMohon datang tepat waktu sesuai jam operasional klinik. Semoga lekas sembuh dan sehat selalu! 🙏";
            $send_res = sendWA($no_telp, $pesan);
            mysqli_query($koneksi, "UPDATE rekam_medis SET reminder_today = 1 WHERE id = $id_rm");
            $sent_count++;
            $logs[] = "Hari-H dikirim ke $nama ($no_telp)";
        }
    }
}

$response = [
    'status' => true,
    'message' => "Pengecekan pengingat selesai. Total terkirim: $sent_count pesan.",
    'sent_count' => $sent_count,
    'logs' => $logs
];

if (!$is_cli) {
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    echo $response['message'] . PHP_EOL;
    foreach ($logs as $log) {
        echo "- " . $log . PHP_EOL;
    }
}
?>
