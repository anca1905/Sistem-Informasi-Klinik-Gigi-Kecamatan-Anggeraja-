<?php
session_start();
include '../config/koneksi.php';

// Cek Login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../auth/login.php");
    exit();
}

if ($_SESSION['role'] != 'Dokter' && $_SESSION['role'] != 'Admin') {
    header("location:dashboard.php");
    exit();
}

// Ambil ID Antrian dari URL
$id_antrian = (int)$_GET['id'];

// Ambil Data Pasien berdasarkan ID Antrian
$query = mysqli_query($koneksi, "SELECT * FROM antrian WHERE id = '$id_antrian'");
$data = mysqli_fetch_array($query);

if (!$data) {
    header("location:antrian.php");
    exit();
}

// Cek apakah transaksi sudah ada sebelumnya (untuk nilai awal biaya)
$q_trx_old = mysqli_query($koneksi, "SELECT * FROM transaksi WHERE id_antrian = '$id_antrian' LIMIT 1");
$data_trx_old = mysqli_fetch_assoc($q_trx_old);
$biaya_val = ($data_trx_old && $data_trx_old['biaya'] > 0) ? number_format($data_trx_old['biaya'], 0, ',', '.') : '';

// Jika tombol Simpan ditekan
if (isset($_POST['simpan_rm'])) {
    $nama_pasien = mysqli_real_escape_string($koneksi, $data['nama_pendaftar']);
    $keluhan     = mysqli_real_escape_string($koneksi, $data['keluhan']);
    $diagnosa    = mysqli_real_escape_string($koneksi, $_POST['diagnosa']);
    $tindakan    = !empty($_POST['tindakan']) ? mysqli_real_escape_string($koneksi, $_POST['tindakan']) : $diagnosa;
    $resep       = mysqli_real_escape_string($koneksi, $_POST['resep']);
    $biaya_raw   = isset($_POST['biaya']) ? $_POST['biaya'] : '0';
    $biaya       = (float)str_replace(['.', ','], ['', ''], $biaya_raw);
    if ($biaya < 0) $biaya = 0;
    $tgl         = date('Y-m-d');
    $id_dokter   = !empty($data['id_dokter']) ? (int)$data['id_dokter'] : 'NULL';

    $tgl_kunjungan_kembali = !empty($_POST['tgl_kunjungan_kembali']) ? $_POST['tgl_kunjungan_kembali'] : NULL;

    // 1. Cek atau Buat Pasien Jika Belum Ada (Untuk Antrian Lama)
    $id_pasien = $data['id_pasien'];
    if (empty($id_pasien)) {
        $nama_pendaftar_safe = mysqli_real_escape_string($koneksi, $data['nama_pendaftar']);
        $total_pasien = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM pasien")) + 1;
        $kode_rm = 'RM' . str_pad($total_pasien, 4, '0', STR_PAD_LEFT);
        mysqli_query($koneksi, "INSERT INTO pasien (kode_pasien, nama, tanggal_lahir, jenis_kelamin, no_telepon, nik, alamat) 
            VALUES ('$kode_rm', '$nama_pendaftar_safe', '2000-01-01', 'Laki-laki', '', '', '')");
        $id_pasien = mysqli_insert_id($koneksi);
        
        // Update antrian agar kedepannya id_pasien tidak kosong
        mysqli_query($koneksi, "UPDATE antrian SET id_pasien = '$id_pasien' WHERE id = '$id_antrian'");
    }

    // 2. Simpan ke tabel rekam_medis
    if ($tgl_kunjungan_kembali) {
        $insert = mysqli_query($koneksi, "INSERT INTO rekam_medis (id_pasien, id_dokter, nama_pasien, tanggal_periksa, keluhan, diagnosa, tindakan, resep_obat, kunjungan_berikutnya) VALUES ('$id_pasien', $id_dokter, '$nama_pasien', '$tgl', '$keluhan', '$diagnosa', '$tindakan', '$resep', '$tgl_kunjungan_kembali')");
    } else {
        $insert = mysqli_query($koneksi, "INSERT INTO rekam_medis (id_pasien, id_dokter, nama_pasien, tanggal_periksa, keluhan, diagnosa, tindakan, resep_obat) VALUES ('$id_pasien', $id_dokter, '$nama_pasien', '$tgl', '$keluhan', '$diagnosa', '$tindakan', '$resep')");
    }
    $id_rm_baru = mysqli_insert_id($koneksi);

    // 3. Update status antrian jadi 'Selesai'
    $update = mysqli_query($koneksi, "UPDATE antrian SET status='Selesai' WHERE id='$id_antrian'");

    // 4. Buat Surat Kontrol PDF & Kirim WhatsApp
    if ($tgl_kunjungan_kembali) {
        // Ambil data pasien untuk nomor WA
        $q_pasien = mysqli_query($koneksi, "SELECT no_telepon FROM pasien WHERE id='$id_pasien'");
        $d_pasien = mysqli_fetch_array($q_pasien);
        $no_wa = $d_pasien['no_telepon'];

        if ($no_wa) {
            // Buat folder jika belum ada
            if (!is_dir('../assets/surat_kontrol')) {
                @mkdir('../assets/surat_kontrol', 0777, true);
            }

            // Generate Gambar Surat Kontrol (PNG)
            $safe_nama_pasien = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nama_pasien);
            $img_filename = "Surat_Kontrol_" . $safe_nama_pasien . "_" . time() . ".png";
            $img_path = __DIR__ . "/../assets/surat_kontrol/" . $img_filename;
            
            try {
                createSuratKontrolImage(
                    $nama_pasien, 
                    date('d-m-Y', strtotime($tgl)), 
                    date('d-m-Y', strtotime($tgl_kunjungan_kembali)), 
                    $diagnosa, 
                    'drg. Syamsuriah', 
                    $img_path
                );
            } catch (Exception $e) {
                $img_path = null;
                $img_filename = null;
            }

            // Kirim ke WhatsApp via Fonnte API sebagai GAMBAR
            $message = "Halo *$nama_pasien*,\n\nBerikut adalah *Surat Jadwal Kontrol* Anda dari *Klinik Gigi Anggeraja*.\nJadwal kontrol berikutnya: *" . date('d-m-Y', strtotime($tgl_kunjungan_kembali)) . "*.\n\nHarap datang kembali pada tanggal jadwal kontrol yang tertera. Terima kasih atas kepercayaan Anda kepada kami! 🙏";
            @sendWA($no_wa, $message, $img_path, $img_filename);
        }
    }

    // 5. Buat atau update transaksi otomatis (status Belum Bayar) dengan total biaya dari dokter
    if ($insert && $update) {
        $cek_trx = mysqli_query($koneksi, "SELECT id_transaksi FROM transaksi WHERE id_antrian='$id_antrian' LIMIT 1");
        if (mysqli_num_rows($cek_trx) == 0) {
            mysqli_query($koneksi, "INSERT INTO transaksi (id_antrian, id_rekam_medis, nama_pasien, tindakan, biaya, status_bayar) VALUES ('$id_antrian', '$id_rm_baru', '$nama_pasien', '$tindakan', '$biaya', 'Belum Bayar')");
        } else {
            $trx_exist = mysqli_fetch_assoc($cek_trx);
            $id_trx_exist = $trx_exist['id_transaksi'];
            mysqli_query($koneksi, "UPDATE transaksi SET id_rekam_medis='$id_rm_baru', nama_pasien='$nama_pasien', tindakan='$tindakan', biaya='$biaya' WHERE id_transaksi='$id_trx_exist'");
        }
        if (isset($_SESSION['role']) && $_SESSION['role'] == 'Dokter') {
            header("location:antrian.php?status=selesai_periksa");
            exit();
        } else {
            header("location:transaksi.php?status=Belum+Bayar");
            exit();
        }
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
                <label>Diagnosa Medis</label>
                <textarea name="diagnosa" rows="3" placeholder="Contoh: Gigi 46: Karies Profunda (Lubang Gigi Dalam)" required></textarea>
            </div>

            <div class="form-group">
                <label>Tindakan Medis</label>
                <textarea name="tindakan" rows="3" placeholder="Contoh: Penambalan Komposit Sinar / Pembersihan Karang Gigi" required></textarea>
            </div>

            <div class="form-group">
                <label><i class="fas fa-pills"></i> Resep Obat (Jika Ada)</label>
                <textarea name="resep" rows="3" placeholder="Contoh: Asam Mefenamat 500mg (3x1), Amoxicillin 500mg (3x1)..."></textarea>
            </div>

            <div class="form-group">
                <label><i class="fas fa-money-bill-wave" style="color: #10b981;"></i> Total Biaya Pemeriksaan & Tindakan (Rp)</label>
                <input type="text" name="biaya" id="biaya" placeholder="Contoh: 150.000" value="<?php echo htmlspecialchars($biaya_val); ?>" oninput="formatBiaya(this)" required>
                <small style="color: #64748b; font-size: 0.8rem; display: block; margin-top: 4px;">*Masukkan total biaya perawatan/obat agar langsung tercatat dan tidak nol di transaksi kasir.</small>
            </div>

            <div class="form-group">
                <label><i class="fas fa-calendar-alt"></i> Tanggal Kunjungan Kembali / Jadwal Kontrol (Opsional)</label>
                <input type="date" name="tgl_kunjungan_kembali" title="Jika pasien harus kembali, tentukan tanggalnya di sini.">
            </div>

            <div style="margin-top: 30px; display: flex; gap: 10px; align-items: center;">
                <a href="antrian.php" class="btn-back">Batal</a>
                <button type="submit" name="simpan_rm" class="btn-submit">
                    <i class="fas fa-tooth"></i> Simpan Perawatan
                </button>
            </div>
        </form>
    </div>

    <script>
        function formatBiaya(input) {
            let val = input.value.replace(/\D/g, '');
            input.value = val ? val.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
        }
    </script>
</body>

</html>