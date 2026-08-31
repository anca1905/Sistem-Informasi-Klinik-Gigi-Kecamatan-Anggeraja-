<?php
include 'config/koneksi.php';

$hari_ini = date('Y-m-d');

// 1. Cek Nomor yang SEDANG DILAYANI
$query_panggil = mysqli_query($koneksi, "SELECT * FROM antrian WHERE DATE(waktu_daftar) = '$hari_ini' AND status = 'Dilayani' ORDER BY id DESC LIMIT 1");
$data_panggil = mysqli_fetch_array($query_panggil);

// Kalau tidak ada yang dilayani, cari nomor terakhir yang Selesai (biar pasien tau sampai mana)
if (!$data_panggil) {
    $query_selesai = mysqli_query($koneksi, "SELECT * FROM antrian WHERE DATE(waktu_daftar) = '$hari_ini' AND status = 'Selesai' ORDER BY id DESC LIMIT 1");
    $data_selesai = mysqli_fetch_array($query_selesai);
}

// 2. Logika Cari Antrian Pasien
$hasil_cari = null;
$pesan_cari = "";

if (isset($_POST['cari'])) {
    $keyword = htmlspecialchars($_POST['keyword']); // Bisa Nomor Antrian

    // Cari data pasien berdasarkan No Antrian HARI INI
    $query_cari = mysqli_query($koneksi, "SELECT * FROM antrian WHERE no_antrian = '$keyword' AND DATE(waktu_daftar) = '$hari_ini'");
    $hasil_cari = mysqli_fetch_array($query_cari);

    if (!$hasil_cari) {
        $pesan_cari = "Nomor antrian tidak ditemukan hari ini.";
    } else {
        // Hitung sisa antrian (Berapa orang lagi di depannya?)
        // Logika: Hitung jumlah orang yang statusnya 'Menunggu' dan nomor antriannya LEBIH KECIL dari pasien ini
        $nomor_saya = $hasil_cari['no_antrian'];
        $query_sisa = mysqli_query($koneksi, "SELECT COUNT(*) as sisa FROM antrian WHERE status='Menunggu' AND no_antrian < '$nomor_saya' AND DATE(waktu_daftar) = '$hari_ini'");
        $data_sisa = mysqli_fetch_array($query_sisa);
        $jumlah_tunggu = $data_sisa['sisa'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Antrian Klinik Gigi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #10b981; /* Hijau Emerald */
            --dark: #064e3b;
            --bg: #f0fdf4;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 480px; /* Ukuran pas buat HP */
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            overflow: hidden;
            position: relative;
        }

        .header {
            background: var(--dark);
            padding: 25px;
            text-align: center;
            color: white;
            border-radius: 0 0 30px 30px;
        }

        .header h2 { margin: 0; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .header p { margin: 5px 0 0; opacity: 0.8; font-size: 0.9rem; }

        /* KOTAK STATUS UTAMA */
        .live-status {
            text-align: center;
            margin-top: -30px;
            padding: 0 20px;
        }

        .status-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-top: 5px solid var(--primary);
        }

        .big-number {
            font-size: 4rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
            margin: 10px 0;
        }

        .status-badge {
            background: #dbeafe; color: #1e40af;
            padding: 5px 15px; border-radius: 50px;
            font-size: 0.8rem; font-weight: bold;
            display: inline-block;
        }

        /* FORM PENCARIAN */
        .search-box {
            padding: 30px 25px;
        }

        .input-group {
            position: relative;
            margin-bottom: 15px;
        }

        .input-group input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            outline: none;
            box-sizing: border-box;
            transition: 0.3s;
        }

        .input-group input:focus { border-color: var(--primary); }
        .input-group i { position: absolute; left: 15px; top: 18px; color: #94a3b8; }

        .btn-cari {
            width: 100%;
            padding: 15px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-cari:hover { background: var(--dark); }

        /* HASIL PENCARIAN */
        .result-box {
            background: #f8fafc;
            margin-top: 20px;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid var(--dark);
        }

        .home-link {
            display: block; text-align: center; margin-top: 20px;
            color: #64748b; text-decoration: none; font-size: 0.9rem;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h2><i class="fas fa-tooth"></i> Klinik Gigi Desa</h2>
            <p>Cek giliranmu dari rumah</p>
        </div>

        <div class="live-status">
            <div class="status-card">
                <small style="color: #64748b; text-transform: uppercase; font-weight: bold;">Sedang Dipanggil</small>
                
                <?php if($data_panggil) { ?>
                    <div class="big-number"><?php echo $data_panggil['no_antrian']; ?></div>
                    <span class="status-badge">Pasien: <?php echo explode(' ', $data_panggil['nama_pendaftar'])[0]; ?>...</span>
                <?php } elseif(isset($data_selesai)) { ?>
                    <div class="big-number" style="font-size: 2.5rem; color: #64748b;"><?php echo $data_selesai['no_antrian']; ?></div>
                     <span class="status-badge" style="background:#e2e8f0; color:#64748b;">Terakhir Selesai</span>
                <?php } else { ?>
                    <div class="big-number" style="font-size: 2rem; color: #94a3b8;">-</div>
                    <span class="status-badge" style="background:#e2e8f0; color:#64748b;">Belum ada antrian</span>
                <?php } ?>
            </div>
        </div>

        <div class="search-box">
            <h3 style="margin-top: 0; color: #334155;">Cek Status Saya</h3>
            
            <form method="POST">
                <div class="input-group">
                    <i class="fas fa-search"></i>
                    <input type="number" name="keyword" placeholder="Masukkan Nomor Antrian..." required>
                </div>
                <button type="submit" name="cari" class="btn-cari">Cek Sekarang</button>
            </form>

            <?php if(isset($_POST['cari'])) { ?>
                <div class="result-box">
                    <?php if($hasil_cari) { ?>
                        <h4 style="margin: 0 0 10px 0; color: var(--dark);">Halo, <?php echo $hasil_cari['nama_pendaftar']; ?> 👋</h4>
                        
                        <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 5px;">
                            <span>Status:</span>
                            <?php if($hasil_cari['status'] == 'Menunggu') { ?>
                                <strong style="color: #d97706;">Menunggu Giliran</strong>
                            <?php } elseif($hasil_cari['status'] == 'Dilayani') { ?>
                                <strong style="color: #2563eb;">Sedang Diperiksa</strong>
                            <?php } else { ?>
                                <strong style="color: var(--primary);">Selesai</strong>
                            <?php } ?>
                        </div>

                        <?php if($hasil_cari['status'] == 'Menunggu') { ?>
                            <div style="display: flex; justify-content: space-between; font-size: 0.9rem; border-top: 1px dashed #cbd5e1; margin-top: 10px; padding-top: 10px;">
                                <span>Antrian di depanmu:</span>
                                <strong><?php echo $jumlah_tunggu; ?> orang lagi</strong>
                            </div>
                            <p style="font-size: 0.8rem; color: #64748b; margin-top: 10px;">
                                *Perkiraan waktu tunggu: <?php echo $jumlah_tunggu * 15; ?> menit.
                            </p>
                        <?php } ?>

                    <?php } else { ?>
                        <p style="color: #ef4444; margin: 0; text-align: center;"><?php echo $pesan_cari; ?></p>
                    <?php } ?>
                </div>
            <?php } ?>

            <a href="index.php" class="home-link">← Kembali ke Halaman Utama</a>
        </div>
    </div>

</body>
</html>