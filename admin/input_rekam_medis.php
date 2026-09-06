<?php
session_start();
include '../config/koneksi.php';

// Cek Login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:login.php");
    exit();
}

// Ambil ID Antrian dari URL
$id_antrian = $_GET['id'];

// Ambil Data Pasien berdasarkan ID Antrian
$query = mysqli_query($koneksi, "SELECT * FROM antrian WHERE id = '$id_antrian'");
$data = mysqli_fetch_array($query);

// Jika tombol Simpan ditekan
if (isset($_POST['simpan_rm'])) {
    $nama_pasien = mysqli_real_escape_string($koneksi, $data['nama_pendaftar']);
    $keluhan     = mysqli_real_escape_string($koneksi, $data['keluhan']);
    $diagnosa    = mysqli_real_escape_string($koneksi, $_POST['diagnosa']);
    $resep       = mysqli_real_escape_string($koneksi, $_POST['resep']);
    $tgl         = date('Y-m-d');

    $tgl_kunjungan_kembali = !empty($_POST['tgl_kunjungan_kembali']) ? $_POST['tgl_kunjungan_kembali'] : NULL;

    // 1. Simpan ke tabel rekam_medis
    $id_pasien = $data['id_pasien'];
    if ($tgl_kunjungan_kembali) {
        $insert = mysqli_query($koneksi, "INSERT INTO rekam_medis (id_pasien, nama_pasien, tanggal_periksa, keluhan, diagnosa, resep_obat, kunjungan_berikutnya) VALUES ('$id_pasien', '$nama_pasien', '$tgl', '$keluhan', '$diagnosa', '$resep', '$tgl_kunjungan_kembali')");
    } else {
        $insert = mysqli_query($koneksi, "INSERT INTO rekam_medis (id_pasien, nama_pasien, tanggal_periksa, keluhan, diagnosa, resep_obat) VALUES ('$id_pasien', '$nama_pasien', '$tgl', '$keluhan', '$diagnosa', '$resep')");
    }
    $id_rm_baru = mysqli_insert_id($koneksi);

    // 2. Update status antrian jadi 'Selesai'
    $update = mysqli_query($koneksi, "UPDATE antrian SET status='Selesai' WHERE id='$id_antrian'");

    // 3. Buat transaksi otomatis (status Belum Bayar) lalu arahkan ke form transaksi
    if ($insert && $update) {
        $cek_trx = mysqli_query($koneksi, "SELECT id_transaksi FROM transaksi WHERE id_antrian='$id_antrian' LIMIT 1");
        if (mysqli_num_rows($cek_trx) == 0) {
            mysqli_query($koneksi, "INSERT INTO transaksi (id_antrian, id_rekam_medis, nama_pasien, tindakan, biaya, status_bayar) VALUES ('$id_antrian', '$id_rm_baru', '$nama_pasien', '$diagnosa', 0, 'Belum Bayar')");
        }
        header("location:tambah_transaksi.php?id_antrian=$id_antrian");
        exit();
    } else {
        echo "<script>alert('Gagal menyimpan data');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Rekam Medis</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* Kita pakai style variabel yang sama biar konsisten */
        :root {
            --primary-dark: #064e3b;
            --primary-medium: #065f46;
            --primary-light: #10b981;
            --bg-body: #f3f4f6;
            --text-main: #1f2937;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border-top: 5px solid var(--primary-light);
        }

        h2 {
            color: var(--primary-medium);
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .info-box {
            background: #d1fae5;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #065f46;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        input,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-family: inherit;
            box-sizing: border-box;
        }

        input:focus,
        textarea:focus {
            outline: 2px solid var(--primary-light);
            border-color: transparent;
        }

        .btn-submit {
            background: var(--primary-light);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background: var(--primary-medium);
        }

        .btn-back {
            background: #9ca3af;
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            margin-right: 10px;
        }
    </style>
</head>

<body>

    <div class="container">
        <h2><i class="fas fa-user-md"></i> Pemeriksaan Pasien</h2>

        <div class="info-box">
            <p><strong>Nama Pasien:</strong> <?php echo $data['nama_pendaftar']; ?></p>
            <p><strong>Keluhan Awal:</strong> <?php echo $data['keluhan']; ?></p>
        </div>

        <form method="POST">

            <div style="margin-bottom: 20px; text-align: center; background: #f0f9ff; padding: 15px; border-radius: 8px; border: 1px dashed #bae6fd;">
                <p style="font-weight: bold; color: #0284c7; margin-bottom: 10px;">
                    <i class="fas fa-info-circle"></i> Panduan Nomor Gigi (Odontogram)
                </p>



                <p style="font-size: 0.8rem; color: #64748b; margin-top: 5px;">
                    *Gunakan nomor gigi di atas untuk mengisi diagnosa (Contoh: Gigi 18 Impaksi)
                </p>
            </div>

            <div class="form-group">
                <label>Diagnosa & Tindakan</label>
                <textarea name="diagnosa" rows="4" placeholder="Contoh: - Gigi 46: Karies Profunda (Lubang Dalam)- Tindakan: Tambal Sinar (Composite)" required></textarea>
            </div>

            <div class="form-group">
                <label>Resep Obat (Jika Ada)</label>
                <textarea name="resep" rows="3" placeholder="Contoh: Asam Mefenamat 500mg (3x1), Amoxicillin 500mg (3x1)..."></textarea>
            </div>

            <div class="form-group">
                <label>Tanggal Kunjungan Kembali (Opsional)</label>
                <input type="date" name="tgl_kunjungan_kembali" title="Jika pasien harus kembali, tentukan tanggalnya di sini.">
            </div>

            <div style="margin-top: 30px;">
                <a href="dashboard.php" class="btn-back">Batal</a>
                <button type="submit" name="simpan_rm" class="btn-submit">
                    <i class="fas fa-tooth"></i> Simpan Perawatan
                </button>
            </div>
        </form>
    </div>

</body>

</html>