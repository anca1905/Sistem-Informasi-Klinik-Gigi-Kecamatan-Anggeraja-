<?php
session_start();
include 'config/koneksi.php';

// Validasi Sesi Pasien
if (!isset($_SESSION['status_pasien']) || $_SESSION['status_pasien'] != "login") {
    header("location:auth/login.php");
    exit();
}

$id_pasien = (int)$_SESSION['pasien_id'];

// Ambil Data Profil Pasien
$q_pasien = mysqli_query($koneksi, "SELECT * FROM pasien WHERE id = $id_pasien LIMIT 1");
$pasien = mysqli_fetch_assoc($q_pasien);

if (!$pasien) {
    session_destroy();
    header("location:auth/login.php");
    exit();
}

$hari_ini = date('Y-m-d');
$pesan_aksi = '';
$pesan_type = '';

// PROSES AMBIL ANTRIAN CEPAT DARI PORTAL PASIEN (1-KLIK)
if (isset($_POST['ambil_antrian_portal'])) {
    $id_jadwal = (int)$_POST['id_jadwal'];
    $keluhan   = mysqli_real_escape_string($koneksi, trim($_POST['keluhan']));

    if (empty($id_jadwal) || empty($keluhan)) {
        $pesan_aksi = 'Pilih dokter dan tulis keluhan Anda.';
        $pesan_type = 'error';
    } else {
        // Cek redundansi
        $cek_antrian = mysqli_query($koneksi, "
            SELECT * FROM antrian 
            WHERE id_pasien = $id_pasien 
              AND DATE(waktu_daftar) = '$hari_ini' 
              AND status IN ('Menunggu', 'Dilayani')
            LIMIT 1
        ");

        if (mysqli_num_rows($cek_antrian) > 0) {
            $pesan_aksi = 'Anda sudah memiliki antrian aktif untuk hari ini!';
            $pesan_type = 'warning';
        } else {
            // Ambil id_dokter
            $q_jdwl = mysqli_query($koneksi, "SELECT id_dokter FROM jadwal_dokter WHERE id = $id_jadwal");
            $r_jdwl = mysqli_fetch_assoc($q_jdwl);
            $id_dokter = $r_jdwl ? (int)$r_jdwl['id_dokter'] : 'NULL';

            mysqli_query($koneksi, "START TRANSACTION");
            $q_max = mysqli_query($koneksi, "SELECT COALESCE(MAX(no_antrian), 0) + 1 AS next_no FROM antrian WHERE DATE(waktu_daftar) = '$hari_ini' FOR UPDATE");
            $d_max = mysqli_fetch_assoc($q_max);
            $no_antrian = (int)$d_max['next_no'];

            $nama_safe = mysqli_real_escape_string($koneksi, $pasien['nama']);
            $simpan = mysqli_query($koneksi, "
                INSERT INTO antrian (id_pasien, id_jadwal, id_dokter, no_antrian, nama_pendaftar, keluhan, status, waktu_daftar)
                VALUES ($id_pasien, $id_jadwal, $id_dokter, $no_antrian, '$nama_safe', '$keluhan', 'Menunggu', NOW())
            ");

            if ($simpan) {
                $id_antrian_baru = mysqli_insert_id($koneksi);
                mysqli_query($koneksi, "COMMIT");

                $pesan_aksi = "Nomor antrian #$no_antrian berhasil diambil!";
                $pesan_type = 'success';

                // WhatsApp notif
                if (!empty($pasien['no_telepon'])) {
                    $q_dok = mysqli_query($koneksi, "SELECT nama_dokter FROM dokter WHERE id_dokter='$id_dokter' LIMIT 1");
                    $d_dok = mysqli_fetch_assoc($q_dok);
                    $nama_dokter = $d_dok ? $d_dok['nama_dokter'] : 'Dokter';

                    $msg_antrian = "Halo *" . $pasien['nama'] . "*,\n\n" .
                                   "Pendaftaran antrian kontrol Anda di *Klinik Gigi Anggeraja* berhasil!\n\n" .
                                   "📌 *Nomor Antrian:* #$no_antrian\n" .
                                   "📅 *Tanggal:* " . date('d-m-Y') . "\n" .
                                   "👨‍⚕️ *Dokter:* $nama_dokter\n\n" .
                                   "Silakan datang sebelum nomor Anda dipanggil. Terima kasih! 🙏";
                    @sendWA($pasien['no_telepon'], $msg_antrian);
                }
            } else {
                mysqli_query($koneksi, "ROLLBACK");
                $pesan_aksi = 'Gagal mengambil antrian: ' . mysqli_error($koneksi);
                $pesan_type = 'error';
            }
        }
    }
}

// PROSES UBAH PASSWORD
if (isset($_POST['ubah_password'])) {
    $pass_baru = mysqli_real_escape_string($koneksi, trim($_POST['password_baru']));
    $pass_konf = mysqli_real_escape_string($koneksi, trim($_POST['konfirmasi_password']));

    if (empty($pass_baru) || strlen($pass_baru) < 4) {
        $pesan_aksi = 'Password baru minimal 4 karakter!';
        $pesan_type = 'error';
    } elseif ($pass_baru !== $pass_konf) {
        $pesan_aksi = 'Konfirmasi password tidak cocok!';
        $pesan_type = 'error';
    } else {
        $up = mysqli_query($koneksi, "UPDATE pasien SET password = '$pass_baru' WHERE id = $id_pasien");
        if ($up) {
            $pesan_aksi = 'Password berhasil diperbarui!';
            $pesan_type = 'success';
        } else {
            $pesan_aksi = 'Gagal memperbarui password.';
            $pesan_type = 'error';
        }
    }
}

// Cek Antrian Pasien Hari Ini
$q_antrian_today = mysqli_query($koneksi, "
    SELECT a.*, d.nama_dokter, j.hari, j.jam_mulai, j.jam_selesai
    FROM antrian a
    LEFT JOIN dokter d ON a.id_dokter = d.id_dokter
    LEFT JOIN jadwal_dokter j ON a.id_jadwal = j.id
    WHERE a.id_pasien = $id_pasien AND DATE(a.waktu_daftar) = '$hari_ini'
    ORDER BY a.id DESC LIMIT 1
");
$antrian_today = mysqli_fetch_assoc($q_antrian_today);
$sisa_tunggu = 0;
if ($antrian_today && $antrian_today['status'] == 'Menunggu') {
    $no_s = (int)$antrian_today['no_antrian'];
    $q_sisa = mysqli_query($koneksi, "SELECT COUNT(*) as sisa FROM antrian WHERE status='Menunggu' AND no_antrian < $no_s AND DATE(waktu_daftar) = '$hari_ini'");
    $d_sisa = mysqli_fetch_assoc($q_sisa);
    $sisa_tunggu = (int)$d_sisa['sisa'];
}

// Ambil Jadwal Dokter Aktif untuk Form Ambil Antrian
$q_dokter_list = mysqli_query($koneksi, "
    SELECT d.nama_dokter, j.id AS id_jadwal, j.hari, j.jam_mulai, j.jam_selesai
    FROM dokter d JOIN jadwal_dokter j ON j.id_dokter = d.id_dokter
    WHERE j.status = 'Praktek'
    ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), d.nama_dokter
");

// Ambil Riwayat Rekam Medis & Jadwal Kontrol
$q_rekam = mysqli_query($koneksi, "
    SELECT rm.*, d.nama_dokter
    FROM rekam_medis rm
    LEFT JOIN dokter d ON rm.id_dokter = d.id_dokter
    WHERE rm.id_pasien = $id_pasien
    ORDER BY rm.id DESC
");

// Jadwal Kontrol Mendatang
$q_kontrol_upcoming = mysqli_query($koneksi, "
    SELECT rm.*, d.nama_dokter
    FROM rekam_medis rm
    LEFT JOIN dokter d ON rm.id_dokter = d.id_dokter
    WHERE rm.id_pasien = $id_pasien 
      AND rm.kunjungan_berikutnya IS NOT NULL 
      AND rm.kunjungan_berikutnya >= '$hari_ini'
    ORDER BY rm.kunjungan_berikutnya ASC
    LIMIT 1
");
$upcoming_kontrol = mysqli_fetch_assoc($q_kontrol_upcoming);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Pasien - <?php echo htmlspecialchars($pasien['nama']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #059669;
            --primary-dark: #064e3b;
            --primary-light: #d1fae5;
            --bg: #f3f4f6;
            --text-dark: #1e293b;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            color: var(--text-dark);
            margin: 0;
            padding: 0;
        }

        /* NAVBAR */
        .patient-navbar {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .brand {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .user-nav {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn-logout {
            background: #fee2e2;
            color: #dc2626;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-logout:hover {
            background: #fecaca;
        }

        .main-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* PROFILE CARD & DIGITAL PASIEN CARD */
        .id-card {
            background: linear-gradient(135deg, #064e3b 0%, #059669 100%);
            color: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(5, 150, 105, 0.25);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }

        .id-card::after {
            content: "\f0f8";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            position: absolute;
            right: -20px;
            bottom: -20px;
            font-size: 10rem;
            opacity: 0.08;
            pointer-events: none;
        }

        .id-info h2 {
            margin: 0 0 5px 0;
            font-size: 1.4rem;
        }

        .id-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            display: inline-block;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }

        .id-details {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            font-size: 0.88rem;
            opacity: 0.95;
        }

        .id-details span {
            display: block;
        }

        .btn-portal-action {
            background: white;
            color: var(--primary-dark);
            border: none;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: 0.25s;
        }

        .btn-portal-action:hover {
            transform: translateY(-2px);
            background: #f0fdf4;
        }

        /* GRID CONTENT */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        .card-box {
            background: white;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            border: 1px solid #e2e8f0;
        }

        .card-box-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .card-box-title {
            margin: 0;
            font-size: 1.1rem;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ANTRIAN CARD */
        .antrian-display {
            text-align: center;
            padding: 15px;
            background: #f0fdf4;
            border-radius: 14px;
            border: 2px dashed var(--primary);
        }

        .antrian-num {
            font-size: 3.5rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
            margin: 5px 0;
        }

        /* RIWAYAT TABLE */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
        }

        th, td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
        }

        th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
        }

        .badge-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .badge-wait { background: #fef3c7; color: #92400e; }
        .badge-process { background: #dbeafe; color: #1e40af; }
        .badge-done { background: #d1fae5; color: #065f46; }

        .alert-box {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.92rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    </style>
</head>

<body>

    <nav class="patient-navbar">
        <a href="index.php" class="brand">
            <i class="fas fa-tooth"></i> Klinik Gigi Anggeraja
        </a>
        <div class="user-nav">
            <span style="font-size:0.9rem; color:#475569; display:none; @media(min-width:600px){display:inline;}">
                Halo, <b><?php echo htmlspecialchars($pasien['nama']); ?></b>
            </span>
            <a href="auth/logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> Keluar
            </a>
        </div>
    </nav>

    <div class="main-container">

        <?php if ($pesan_aksi): ?>
            <div class="alert-box alert-<?php echo $pesan_type; ?>">
                <i class="fas fa-<?php echo $pesan_type == 'success' ? 'check-circle' : ($pesan_type == 'warning' ? 'exclamation-triangle' : 'times-circle'); ?>"></i>
                <?php echo $pesan_aksi; ?>
            </div>
        <?php endif; ?>

        <!-- KARTU PASIEN DIGITAL -->
        <div class="id-card">
            <div class="id-info">
                <span class="id-badge">KARTU PASIEN DIGITAL</span>
                <h2><?php echo htmlspecialchars($pasien['nama']); ?></h2>
                <div class="id-details">
                    <div>
                        <span style="opacity:0.75; font-size:0.75rem;">NO. REKAM MEDIS</span>
                        <strong><?php echo htmlspecialchars($pasien['kode_pasien']); ?></strong>
                    </div>
                    <div>
                        <span style="opacity:0.75; font-size:0.75rem;">TANGGAL LAHIR</span>
                        <strong><?php echo !empty($pasien['tanggal_lahir']) ? date('d-m-Y', strtotime($pasien['tanggal_lahir'])) : '-'; ?></strong>
                    </div>
                    <div>
                        <span style="opacity:0.75; font-size:0.75rem;">JENIS KELAMIN</span>
                        <strong><?php echo htmlspecialchars($pasien['jenis_kelamin'] ?: '-'); ?></strong>
                    </div>
                    <div>
                        <span style="opacity:0.75; font-size:0.75rem;">NO. TELEPON</span>
                        <strong><?php echo htmlspecialchars($pasien['no_telepon'] ?: '-'); ?></strong>
                    </div>
                </div>
            </div>
            <div>
                <a href="#sectionAmbilAntrian" class="btn-portal-action">
                    <i class="fas fa-ticket-alt"></i> Ambil Antrian Kontrol
                </a>
            </div>
        </div>

        <div class="content-grid">
            <!-- KARTU 1: STATUS ANTRIAN HARI INI -->
            <div class="card-box">
                <div class="card-box-header">
                    <h3 class="card-box-title">
                        <i class="fas fa-calendar-day" style="color:var(--primary);"></i> Antrian Anda Hari Ini
                    </h3>
                    <span style="font-size:0.8rem; color:#64748b;"><?php echo date('d M Y'); ?></span>
                </div>

                <?php if ($antrian_today): ?>
                    <div class="antrian-display">
                        <small style="color:#64748b; font-weight:600; text-transform:uppercase;">Nomor Antrian</small>
                        <div class="antrian-num">#<?php echo $antrian_today['no_antrian']; ?></div>
                        
                        <div style="margin: 10px 0;">
                            <?php if ($antrian_today['status'] == 'Menunggu'): ?>
                                <span class="badge-status badge-wait"><i class="fas fa-clock"></i> Menunggu Giliran</span>
                            <?php elseif ($antrian_today['status'] == 'Dilayani'): ?>
                                <span class="badge-status badge-process"><i class="fas fa-stethoscope"></i> Sedang Diperiksa</span>
                            <?php else: ?>
                                <span class="badge-status badge-done"><i class="fas fa-check-circle"></i> Selesai Diperiksa</span>
                            <?php endif; ?>
                        </div>

                        <p style="margin: 8px 0 0 0; font-size:0.9rem; color:#334151;">
                            Dokter: <b><?php echo $antrian_today['nama_dokter'] ? 'dr. ' . htmlspecialchars($antrian_today['nama_dokter']) : '-'; ?></b><br>
                            Jadwal: <?php echo $antrian_today['hari']; ?> (<?php echo substr($antrian_today['jam_mulai'], 0, 5); ?>–<?php echo substr($antrian_today['jam_selesai'], 0, 5); ?>)
                        </p>

                        <?php if ($antrian_today['status'] == 'Menunggu'): ?>
                            <div style="margin-top:12px; padding-top:10px; border-top:1px dashed #a7f3d0; font-size:0.85rem; color:#065f46;">
                                <i class="fas fa-users"></i> Antrian di depan Anda: <b><?php echo $sisa_tunggu; ?> orang lagi</b>
                            </div>
                        <?php endif; ?>

                        <div style="margin-top: 15px;">
                            <a href="cetak_struk.php?id=<?php echo $antrian_today['id']; ?>" target="_blank" style="display:inline-flex; align-items:center; gap:6px; background:#064e3b; color:white; padding:8px 16px; border-radius:8px; text-decoration:none; font-size:0.85rem; font-weight:600;">
                                <i class="fas fa-print"></i> Cetak Struk Antrian
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="text-align:center; padding: 25px 15px; color:#64748b;">
                        <i class="fas fa-ticket-alt" style="font-size:2.5rem; color:#cbd5e1; margin-bottom:10px; display:block;"></i>
                        <p style="margin:0 0 12px 0; font-size:0.92rem;">Anda belum mengambil nomor antrian untuk hari ini.</p>
                        <a href="#sectionAmbilAntrian" style="color:var(--primary); font-weight:700; text-decoration:none; font-size:0.9rem;">
                            Daftar antrian kontrol di bawah ↓
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- KARTU 2: JADWAL KONTROL BERIKUTNYA -->
            <div class="card-box">
                <div class="card-box-header">
                    <h3 class="card-box-title">
                        <i class="fas fa-calendar-check" style="color:#2563eb;"></i> Jadwal Kontrol Dokter
                    </h3>
                </div>

                <?php if ($upcoming_kontrol): ?>
                    <div style="background:#eff6ff; border:1.5px solid #bfdbfe; border-radius:14px; padding:18px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                            <span style="background:#2563eb; color:white; font-size:0.75rem; font-weight:700; padding:3px 10px; border-radius:20px;">
                                JADWAL KONTROL
                            </span>
                            <span style="font-weight:700; color:#1e40af; font-size:1.05rem;">
                                <?php echo date('d M Y', strtotime($upcoming_kontrol['kunjungan_berikutnya'])); ?>
                            </span>
                        </div>
                        <p style="margin:0 0 8px 0; font-size:0.9rem; color:#1e3a8a;">
                            Dokter: <b><?php echo $upcoming_kontrol['nama_dokter'] ? 'dr. ' . htmlspecialchars($upcoming_kontrol['nama_dokter']) : '-'; ?></b><br>
                            Diagnosa: <em><?php echo htmlspecialchars($upcoming_kontrol['diagnosa'] ?: '-'); ?></em><br>
                            Tindakan Terakhir: <em><?php echo htmlspecialchars($upcoming_kontrol['tindakan'] ?: '-'); ?></em>
                        </p>
                        <small style="color:#3b82f6; display:block;">
                            <i class="fas fa-info-circle"></i> Datanglah sesuai jadwal di atas untuk kontrol lanjutan perawatan gigi Anda.
                        </small>
                    </div>
                <?php else: ?>
                    <div style="text-align:center; padding: 25px 15px; color:#64748b;">
                        <i class="fas fa-notes-medical" style="font-size:2.5rem; color:#cbd5e1; margin-bottom:10px; display:block;"></i>
                        <p style="margin:0; font-size:0.9rem;">Belum ada jadwal kontrol kunjungan berikutnya dari dokter.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- FORM CEPAT AMBIL ANTRIAN KONTROL (TANPA KETIK DATA DIRI) -->
        <div class="card-box" id="sectionAmbilAntrian" style="margin-bottom: 25px; border-top: 4px solid var(--primary);">
            <div class="card-box-header">
                <h3 class="card-box-title">
                    <i class="fas fa-plus-circle" style="color:var(--primary);"></i> Ambil Antrian Kontrol Hari Ini
                </h3>
                <span style="font-size:0.85rem; color:#059669; font-weight:600;">
                    <i class="fas fa-check-circle"></i> Otomatis menggunakan No. RM: <?php echo htmlspecialchars($pasien['kode_pasien']); ?>
                </span>
            </div>

            <form method="POST">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                    <div>
                        <label style="display:block; font-weight:600; font-size:0.88rem; margin-bottom:6px;">Pilih Dokter & Jadwal Praktik <span style="color:red;">*</span></label>
                        <select name="id_jadwal" required style="width:100%; padding:11px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-family:inherit; font-size:0.9rem;">
                            <option value="">-- Pilih Dokter & Jadwal --</option>
                            <?php 
                            mysqli_data_seek($q_dokter_list, 0);
                            while ($j = mysqli_fetch_assoc($q_dokter_list)): 
                            ?>
                                <option value="<?php echo $j['id_jadwal']; ?>">
                                    dr. <?php echo htmlspecialchars($j['nama_dokter']); ?> — <?php echo $j['hari']; ?> (<?php echo substr($j['jam_mulai'], 0, 5); ?>–<?php echo substr($j['jam_selesai'], 0, 5); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; font-size:0.88rem; margin-bottom:6px;">Keluhan / Tujuan Kunjungan <span style="color:red;">*</span></label>
                        <input type="text" name="keluhan" value="Kontrol rutin pasca perawatan gigi" required style="width:100%; padding:11px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-family:inherit; font-size:0.9rem;">
                    </div>
                </div>

                <div style="margin-top:16px; text-align:right;">
                    <button type="submit" name="ambil_antrian_portal" style="background:var(--primary); color:white; border:none; padding:12px 24px; border-radius:10px; font-weight:700; font-size:0.95rem; cursor:pointer; display:inline-flex; align-items:center; gap:8px;">
                        <i class="fas fa-ticket-alt"></i> Ambil Nomor Antrian Sekarang
                    </button>
                </div>
            </form>
        </div>

        <!-- RIWAYAT REKAM MEDIS PASIEN -->
        <div class="card-box" style="margin-bottom: 25px;">
            <div class="card-box-header">
                <h3 class="card-box-title">
                    <i class="fas fa-history" style="color:var(--primary);"></i> Riwayat Pemeriksaan Gigi Saya
                </h3>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal Periksa</th>
                            <th>Dokter</th>
                            <th>Keluhan</th>
                            <th>Diagnosa</th>
                            <th>Tindakan</th>
                            <th>Resep Obat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($q_rekam) > 0): ?>
                            <?php while ($r = mysqli_fetch_assoc($q_rekam)): ?>
                                <tr>
                                    <td><strong><?php echo date('d-m-Y', strtotime($r['tanggal_periksa'])); ?></strong></td>
                                    <td><?php echo $r['nama_dokter'] ? 'dr. ' . htmlspecialchars($r['nama_dokter']) : '-'; ?></td>
                                    <td><?php echo htmlspecialchars($r['keluhan'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($r['diagnosa'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($r['tindakan'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($r['resep_obat'] ?: '-'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding:30px; color:#94a3b8;">
                                    Belum ada catatan riwayat rekam medis sebelumnya.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- KOTAK GANTI PASSWORD AKUN -->
        <div class="card-box" style="margin-bottom: 30px;">
            <div class="card-box-header">
                <h3 class="card-box-title">
                    <i class="fas fa-key" style="color:#64748b;"></i> Keamanan Akun Pasien
                </h3>
            </div>
            <form method="POST" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
                <div style="flex:1; min-width:200px;">
                    <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:5px;">Password Baru</label>
                    <input type="password" name="password_baru" placeholder="Masukkan password baru" required style="width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px; font-family:inherit;">
                </div>
                <div style="flex:1; min-width:200px;">
                    <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:5px;">Konfirmasi Password Baru</label>
                    <input type="password" name="konfirmasi_password" placeholder="Ulangi password baru" required style="width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px; font-family:inherit;">
                </div>
                <button type="submit" name="ubah_password" style="background:#064e3b; color:white; border:none; padding:10px 18px; border-radius:8px; font-weight:600; font-size:0.9rem; cursor:pointer;">
                    Simpan Password
                </button>
            </form>
        </div>

    </div>

</body>
</html>
