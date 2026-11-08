<?php
include 'config/koneksi.php';

$hari_ini = date('Y-m-d');

// 1. Cek Nomor yang SEDANG DILAYANI
$query_panggil = mysqli_query($koneksi, "
    SELECT a.*, d.nama_dokter 
    FROM antrian a 
    LEFT JOIN dokter d ON a.id_dokter = d.id_dokter
    WHERE DATE(a.waktu_daftar) = '$hari_ini' AND a.status = 'Dilayani' 
    ORDER BY a.id DESC LIMIT 1
");
$data_panggil = mysqli_fetch_array($query_panggil);

// Kalau tidak ada yang dilayani, cari nomor terakhir yang Selesai
if (!$data_panggil) {
    $query_selesai = mysqli_query($koneksi, "
        SELECT a.*, d.nama_dokter 
        FROM antrian a 
        LEFT JOIN dokter d ON a.id_dokter = d.id_dokter
        WHERE DATE(a.waktu_daftar) = '$hari_ini' AND a.status = 'Selesai' 
        ORDER BY a.id DESC LIMIT 1
    ");
    $data_selesai = mysqli_fetch_array($query_selesai);
}

// 2. Logika Cari Antrian Pasien (Bisa dengan No. Antrian ATAU ID Pasien / No. RM / No. HP)
$hasil_cari = null;
$pesan_cari = "";
$pasien_ada_tapi_belum_antri = null;

if (isset($_POST['cari'])) {
    $keyword = mysqli_real_escape_string($koneksi, trim($_POST['keyword']));

    if (!empty($keyword)) {
        // Cari data antrian berdasarkan:
        // - No Antrian
        // - ID Pasien / Kode Pasien (No. RM)
        // - NIK atau No. Telepon
        // - Nama
        $query_cari = mysqli_query($koneksi, "
            SELECT a.*, p.kode_pasien, p.nik, p.no_telepon, d.nama_dokter, j.hari, j.jam_mulai, j.jam_selesai
            FROM antrian a
            LEFT JOIN pasien p ON a.id_pasien = p.id
            LEFT JOIN dokter d ON a.id_dokter = d.id_dokter
            LEFT JOIN jadwal_dokter j ON a.id_jadwal = j.id
            WHERE (
                a.no_antrian = '$keyword' 
                OR p.kode_pasien = '$keyword' 
                OR p.id = '$keyword'
                OR a.id_pasien = '$keyword'
                OR p.nik = '$keyword' 
                OR p.no_telepon = '$keyword'
                OR a.nama_pendaftar LIKE '%$keyword%'
            ) AND DATE(a.waktu_daftar) = '$hari_ini'
            ORDER BY 
                CASE 
                    WHEN a.no_antrian = '$keyword' THEN 1
                    WHEN p.kode_pasien = '$keyword' THEN 2
                    ELSE 3
                END,
                a.id DESC 
            LIMIT 1
        ");
        $hasil_cari = mysqli_fetch_array($query_cari);

        if (!$hasil_cari) {
            // Cek apakah sebenarnya pasien terdaftar di sistem tapi belum ambil nomor antrian hari ini
            $q_cek_pasien = mysqli_query($koneksi, "
                SELECT * FROM pasien 
                WHERE kode_pasien = '$keyword' 
                   OR id = '$keyword' 
                   OR nik = '$keyword' 
                   OR no_telepon = '$keyword'
                LIMIT 1
            ");
            if ($q_cek_pasien && mysqli_num_rows($q_cek_pasien) > 0) {
                $pasien_ada_tapi_belum_antri = mysqli_fetch_assoc($q_cek_pasien);
                $pesan_cari = "Pasien <strong>" . htmlspecialchars($pasien_ada_tapi_belum_antri['nama']) . "</strong> (No. RM: " . htmlspecialchars($pasien_ada_tapi_belum_antri['kode_pasien']) . ") terdaftar, tetapi belum mengambil antrian untuk hari ini.";
            } else {
                $pesan_cari = "Data antrian dengan Nomor Antrian / ID Pasien / No. RM '<strong>" . htmlspecialchars($keyword) . "</strong>' tidak ditemukan untuk hari ini.";
            }
        } else {
            // Hitung sisa antrian (Berapa orang lagi di depannya?)
            $nomor_saya = (int)$hasil_cari['no_antrian'];
            $query_sisa = mysqli_query($koneksi, "
                SELECT COUNT(*) as sisa 
                FROM antrian 
                WHERE status='Menunggu' 
                  AND no_antrian < $nomor_saya 
                  AND DATE(waktu_daftar) = '$hari_ini'
            ");
            $data_sisa = mysqli_fetch_array($query_sisa);
            $jumlah_tunggu = (int)$data_sisa['sisa'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Antrian - Klinik Gigi Anggeraja</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #10b981; /* Hijau Emerald */
            --dark: #064e3b;
            --bg: #f0fdf4;
            --border: #e2e8f0;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 25px 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 24px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.06);
            overflow: hidden;
            position: relative;
            border-top: 8px solid var(--primary);
        }

        .header {
            background: var(--dark);
            padding: 28px 20px;
            text-align: center;
            color: white;
            border-radius: 0 0 25px 25px;
        }

        .header h2 { 
            margin: 0; 
            font-size: 1.3rem; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 10px; 
        }

        .header p { 
            margin: 6px 0 0; 
            opacity: 0.85; 
            font-size: 0.88rem; 
        }

        /* KOTAK STATUS UTAMA */
        .live-status {
            text-align: center;
            margin-top: -25px;
            padding: 0 20px;
        }

        .status-card {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            border-top: 5px solid var(--primary);
        }

        .big-number {
            font-size: 4rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
            margin: 8px 0;
        }

        .status-badge {
            background: #dbeafe; color: #1e40af;
            padding: 5px 15px; border-radius: 50px;
            font-size: 0.82rem; font-weight: 600;
            display: inline-block;
        }

        /* FORM PENCARIAN */
        .search-box {
            padding: 25px 25px;
        }

        .search-title {
            margin-top: 0;
            margin-bottom: 6px;
            color: #1e293b;
            font-size: 1.15rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-desc {
            color: #64748b;
            font-size: 0.85rem;
            margin-bottom: 18px;
        }

        .input-group {
            position: relative;
            margin-bottom: 15px;
        }

        .input-group input {
            width: 100%;
            padding: 14px 15px 14px 44px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 0.95rem;
            outline: none;
            transition: 0.25s;
            font-family: inherit;
        }

        .input-group input:focus { 
            border-color: var(--primary);
            background: #f0fdf4;
        }

        .input-group i { 
            position: absolute; 
            left: 15px; 
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8; 
            font-size: 1.05rem;
        }

        .btn-cari {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-cari:hover { 
            background: var(--dark); 
        }

        /* HASIL PENCARIAN */
        .result-box {
            background: #f8fafc;
            margin-top: 22px;
            padding: 20px;
            border-radius: 14px;
            border-left: 5px solid var(--primary);
            border: 1px solid #e2e8f0;
            border-left-width: 5px;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .result-queue-badge {
            background: var(--primary);
            color: white;
            font-size: 1.3rem;
            font-weight: 800;
            padding: 4px 14px;
            border-radius: 10px;
            display: inline-block;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        .home-link {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            text-decoration: none;
            font-size: 0.88rem;
        }

        .home-link a {
            color: #64748b;
            text-decoration: none;
            transition: 0.2s;
        }

        .home-link a:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h2><i class="fas fa-tooth"></i> Klinik Gigi Anggeraja</h2>
            <p>Pantau antrian & giliran pemeriksaan Anda secara real-time</p>
        </div>

        <div class="live-status">
            <div class="status-card">
                <small style="color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Sedang Dilayani Dokter</small>
                
                <?php if($data_panggil) { ?>
                    <div class="big-number">#<?php echo $data_panggil['no_antrian']; ?></div>
                    <span class="status-badge" style="background:#dbeafe; color:#1e40af;">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($data_panggil['nama_pendaftar']); ?> — <?php echo htmlspecialchars($data_panggil['nama_dokter'] ?? 'Dokter'); ?>
                    </span>
                <?php } elseif(isset($data_selesai)) { ?>
                    <div class="big-number" style="font-size: 2.8rem; color: #64748b;">#<?php echo $data_selesai['no_antrian']; ?></div>
                    <span class="status-badge" style="background:#e2e8f0; color:#64748b;">Terakhir Selesai: <?php echo htmlspecialchars($data_selesai['nama_pendaftar']); ?></span>
                <?php } else { ?>
                    <div class="big-number" style="font-size: 2.2rem; color: #94a3b8;">-</div>
                    <span class="status-badge" style="background:#e2e8f0; color:#64748b;">Belum ada antrian aktif hari ini</span>
                <?php } ?>
            </div>
        </div>

        <div class="search-box">
            <h3 class="search-title"><i class="fas fa-search"></i> Cek Status Antrian Saya</h3>
            <p class="search-desc">Masukkan <b>Nomor Antrian</b> ATAU <b>ID Pasien / No. RM</b> (contoh: <code>RM0008</code>), NIK, atau No. HP.</p>
            
            <form method="POST">
                <div class="input-group">
                    <i class="fas fa-id-card"></i>
                    <input type="text" name="keyword" value="<?php echo isset($_POST['keyword']) ? htmlspecialchars($_POST['keyword']) : ''; ?>" placeholder="Ketik No. Antrian atau No. RM / ID Pasien..." required autocomplete="off">
                </div>
                <button type="submit" name="cari" class="btn-cari">
                    <i class="fas fa-search"></i> Cek Sekarang
                </button>
            </form>

            <?php if(isset($_POST['cari'])) { ?>
                <div class="result-box">
                    <?php if($hasil_cari) { ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">
                            <div>
                                <h4 style="margin:0; color:var(--dark); font-size:1.05rem;">
                                    <?php echo htmlspecialchars($hasil_cari['nama_pendaftar']); ?>
                                </h4>
                                <small style="color:#64748b;">
                                    No. RM: <b><?php echo htmlspecialchars($hasil_cari['kode_pasien'] ?: '-'); ?></b>
                                </small>
                            </div>
                            <span class="result-queue-badge">
                                #<?php echo $hasil_cari['no_antrian']; ?>
                            </span>
                        </div>
                        
                        <div class="info-row">
                            <span style="color:#64748b;">Status Saat Ini:</span>
                            <?php if($hasil_cari['status'] == 'Menunggu') { ?>
                                <strong style="color: #d97706;"><i class="fas fa-clock"></i> Menunggu Giliran</strong>
                            <?php } elseif($hasil_cari['status'] == 'Dilayani') { ?>
                                <strong style="color: #2563eb;"><i class="fas fa-stethoscope"></i> Sedang Diperiksa</strong>
                            <?php } else { ?>
                                <strong style="color: var(--primary);"><i class="fas fa-check-circle"></i> Selesai Diperiksa</strong>
                            <?php } ?>
                        </div>

                        <div class="info-row">
                            <span style="color:#64748b;">Dokter yang Dituju:</span>
                            <strong><?php echo $hasil_cari['nama_dokter'] ? 'dr. ' . htmlspecialchars($hasil_cari['nama_dokter']) : '-'; ?></strong>
                        </div>

                        <div class="info-row">
                            <span style="color:#64748b;">Keluhan:</span>
                            <span style="text-align:right; max-width:60%;"><?php echo htmlspecialchars($hasil_cari['keluhan'] ?? '-'); ?></span>
                        </div>

                        <?php if($hasil_cari['status'] == 'Menunggu') { ?>
                            <div style="border-top:1px dashed #cbd5e1; margin-top:12px; padding-top:10px;">
                                <div class="info-row" style="margin-bottom:4px;">
                                    <span style="font-weight:600;">Antrian di depan Anda:</span>
                                    <strong style="color:#047857; font-size:1.05rem;"><?php echo $jumlah_tunggu; ?> orang lagi</strong>
                                </div>
                                <p style="font-size:0.8rem; color:#64748b; margin:6px 0 0 0;">
                                    <i class="fas fa-info-circle"></i> Perkiraan waktu tunggu: <b><?php echo $jumlah_tunggu * 15; ?> menit</b>. Harap bersiap di ruang tunggu.
                                </p>
                            </div>
                        <?php } ?>

                        <div style="margin-top:15px; text-align:center;">
                            <a href="cetak_struk.php?id=<?php echo $hasil_cari['id']; ?>" target="_blank" style="display:inline-flex; align-items:center; gap:6px; background:#064e3b; color:white; padding:8px 16px; border-radius:8px; text-decoration:none; font-size:0.88rem; font-weight:600;">
                                <i class="fas fa-print"></i> Cetak / Simpan Struk Antrian
                            </a>
                        </div>

                    <?php } else { ?>
                        <div style="text-align:center; padding:10px 0;">
                            <i class="fas fa-exclamation-circle" style="font-size:2rem; color:#ef4444; margin-bottom:8px; display:block;"></i>
                            <p style="color:#374151; margin:0 0 10px 0; font-size:0.92rem;"><?php echo $pesan_cari; ?></p>
                            
                            <?php if ($pasien_ada_tapi_belum_antri): ?>
                                <a href="ambil_antrian.php" style="display:inline-flex; align-items:center; gap:6px; background:var(--primary); color:white; padding:10px 18px; border-radius:8px; text-decoration:none; font-weight:600; font-size:0.9rem;">
                                    <i class="fas fa-ticket-alt"></i> Ambil Antrian Kontrol Sekarang
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>

            <div class="home-link">
                <a href="index.php"><i class="fas fa-arrow-left"></i> Beranda</a>
                <a href="ambil_antrian.php"><i class="fas fa-ticket-alt"></i> Ambil Antrian</a>
                <a href="auth/login.php"><i class="fas fa-lock"></i> Login</a>
            </div>
        </div>
    </div>

</body>
</html>
