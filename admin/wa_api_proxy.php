<?php
require_once '../config/koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || (isset($_SESSION['role']) && $_SESSION['role'] !== 'Admin')) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['status' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? ($_POST['action'] ?? 'status');

if ($action === 'status') {
    $token = getFonnteToken();
    if (empty($token)) {
        echo json_encode([
            'status' => false,
            'configured' => false,
            'reason' => 'Token Fonnte belum diatur. Silakan masukkan Token Fonnte Anda di bawah.',
            'token' => ''
        ]);
        exit;
    }

    $raw = checkFonnteStatus($token);
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        echo json_encode([
            'status' => false,
            'configured' => true,
            'reason' => 'Gagal menghubungi server Fonnte (Respons tidak valid).',
            'raw' => $raw,
            'token' => $token
        ]);
        exit;
    }

    $data['configured'] = true;
    $data['token'] = $token;
    echo json_encode($data);
    exit;
}

if ($action === 'save_token') {
    $token = trim($_POST['token'] ?? '');
    if (empty($token)) {
        echo json_encode(['status' => false, 'message' => 'Token tidak boleh kosong!']);
        exit;
    }

    $saved = saveFonnteToken($token);
    if (!$saved) {
        echo json_encode(['status' => false, 'message' => 'Gagal menyimpan token ke database!']);
        exit;
    }

    // Verifikasi token langsung ke Fonnte API
    $test_res = checkFonnteStatus($token);
    $test_data = json_decode($test_res, true);

    echo json_encode([
        'status' => true,
        'message' => 'Token Fonnte berhasil disimpan!',
        'fonnte_response' => $test_data
    ]);
    exit;
}

if ($action === 'qr') {
    $raw = getFonnteQR();
    echo $raw ?: json_encode(['status' => false, 'message' => 'Gagal mengambil QR dari Fonnte']);
    exit;
}

if ($action === 'disconnect') {
    $raw = disconnectFonnte();
    echo $raw ?: json_encode(['status' => false, 'message' => 'Gagal memutus koneksi di Fonnte']);
    exit;
}

if ($action === 'test_send') {
    $target = trim($_POST['target'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($target) || empty($message)) {
        echo json_encode(['status' => false, 'message' => 'Nomor tujuan dan pesan wajib diisi!']);
        exit;
    }

    $raw = sendWA($target, $message);
    $data = json_decode($raw, true);

    if (is_array($data) && isset($data['status']) && $data['status'] === true) {
        echo json_encode(['status' => true, 'message' => 'Pesan uji coba berhasil dikirim via Fonnte!', 'response' => $data]);
    } else {
        $reason = is_array($data) ? ($data['reason'] ?? ($data['message'] ?? 'Gagal mengirim pesan')) : 'Gagal menghubungi gateway Fonnte';
        echo json_encode(['status' => false, 'message' => 'Gagal mengirim: ' . $reason, 'response' => $data]);
    }
    exit;
}

if ($action === 'run_reminder') {
    $hari_ini = date('Y-m-d');
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

            $diff_days = (int)floor((strtotime($tgl_kontrol) - strtotime($hari_ini)) / (60 * 60 * 24));

            if ($diff_days === 3 && (int)$row['reminder_3_days'] === 0) {
                $pesan = "Halo *$nama*,\n\nIni pesan pengingat dari *Klinik Gigi Anggeraja*.\nJadwal kontrol gigi Anda adalah *3 hari lagi* pada tanggal *$tgl_format*.\n\nMohon persiapkan diri dan datang tepat waktu. Terima kasih! 🙏";
                sendWA($no_telp, $pesan);
                mysqli_query($koneksi, "UPDATE rekam_medis SET reminder_3_days = 1 WHERE id = $id_rm");
                $sent_count++;
                $logs[] = "H-3: $nama ($no_telp)";
            }

            if ($diff_days === 1 && (int)$row['reminder_1_day'] === 0) {
                $pesan = "Halo *$nama*,\n\nIni pengingat dari *Klinik Gigi Anggeraja*.\nJadwal kontrol gigi Anda adalah *BESOK*, tanggal *$tgl_format*.\n\nJangan sampai lupa ya! Kami siap melayani Anda. Terima kasih! 🙏";
                sendWA($no_telp, $pesan);
                mysqli_query($koneksi, "UPDATE rekam_medis SET reminder_1_day = 1 WHERE id = $id_rm");
                $sent_count++;
                $logs[] = "H-1: $nama ($no_telp)";
            }

            if ($diff_days === 0 && (int)$row['reminder_today'] === 0) {
                $pesan = "Halo *$nama*,\n\n*HARI INI* adalah jadwal kontrol gigi Anda di *Klinik Gigi Anggeraja* (Tanggal: *$tgl_format*).\n\nMohon datang tepat waktu sesuai jam operasional klinik. Semoga lekas sembuh dan sehat selalu! 🙏";
                sendWA($no_telp, $pesan);
                mysqli_query($koneksi, "UPDATE rekam_medis SET reminder_today = 1 WHERE id = $id_rm");
                $sent_count++;
                $logs[] = "Hari H: $nama ($no_telp)";
            }
        }
    }

    echo json_encode([
        'status' => true,
        'message' => "Proses pengingat selesai! Berhasil mengirim $sent_count pengingat jadwal kontrol.",
        'sent_count' => $sent_count,
        'logs' => $logs
    ]);
    exit;
}

echo json_encode(['status' => false, 'message' => 'Action tidak dikenali']);
exit;
?>
