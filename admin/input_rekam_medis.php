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

// Jika tombol Simpan ditekan
if (isset($_POST['simpan_rm'])) {
    $nama_pasien = mysqli_real_escape_string($koneksi, $data['nama_pendaftar']);
    $keluhan     = mysqli_real_escape_string($koneksi, $data['keluhan']);
    $diagnosa    = mysqli_real_escape_string($koneksi, $_POST['diagnosa']);
    $tindakan    = !empty($_POST['tindakan']) ? mysqli_real_escape_string($koneksi, $_POST['tindakan']) : $diagnosa;
    $resep       = mysqli_real_escape_string($koneksi, $_POST['resep']);
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

    // 2. Update status antrian jadi 'Selesai'
    $update = mysqli_query($koneksi, "UPDATE antrian SET status='Selesai' WHERE id='$id_antrian'");

    // 3. Buat Surat Kontrol PDF & Kirim WhatsApp
    if ($tgl_kunjungan_kembali) {
        // Ambil data pasien untuk nomor WA
        $q_pasien = mysqli_query($koneksi, "SELECT no_telepon FROM pasien WHERE id='$id_pasien'");
        $d_pasien = mysqli_fetch_array($q_pasien);
        $no_wa = $d_pasien['no_telepon'];

        if ($no_wa) {
            // Generate PDF menggunakan FPDF
            require_once('../assets/fpdf/fpdf.php');
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(190, 10, 'SURAT KONTROL KLINIK GIGI ANGGERAJA', 0, 1, 'C');
            $pdf->Ln(10);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(50, 10, 'Nama Pasien', 0, 0);
            $pdf->Cell(5, 10, ':', 0, 0);
            $pdf->Cell(100, 10, $nama_pasien, 0, 1);
            $pdf->Cell(50, 10, 'Tanggal Periksa', 0, 0);
            $pdf->Cell(5, 10, ':', 0, 0);
            $pdf->Cell(100, 10, date('d-m-Y', strtotime($tgl)), 0, 1);
            $pdf->Cell(50, 10, 'Jadwal Kontrol', 0, 0);
            $pdf->Cell(5, 10, ':', 0, 0);
            $pdf->Cell(100, 10, date('d-m-Y', strtotime($tgl_kunjungan_kembali)), 0, 1);
            $pdf->Ln(10);
            $pdf->MultiCell(190, 10, "Harap datang kembali pada tanggal jadwal kontrol yang tertera. Terima kasih atas kepercayaan Anda kepada kami.");
            
            // Buat folder jika belum ada
            if (!is_dir('../assets/surat_kontrol')) {
                mkdir('../assets/surat_kontrol', 0777, true);
            }
            $pdf_filename = "Surat_Kontrol_" . str_replace(" ", "_", $nama_pasien) . "_" . time() . ".pdf";
            $pdf_path = __DIR__ . "/../assets/surat_kontrol/" . $pdf_filename;
            $pdf->Output('F', $pdf_path);

            // Kirim ke WhatsApp via Fonnte API
            $message = "Halo *$nama_pasien*,\n\nBerikut adalah Surat Kontrol Anda dari *Klinik Gigi Anggeraja*.\nJadwal kontrol berikutnya: *" . date('d-m-Y', strtotime($tgl_kunjungan_kembali)) . "*.\n\nHarap datang kembali pada tanggal jadwal kontrol yang tertera. Terima kasih atas kepercayaan Anda kepada kami! 🙏";
            sendWA($no_wa, $message, $pdf_path, $pdf_filename);
        }
    }

    // 4. Buat transaksi otomatis (status Belum Bayar) lalu arahkan sesuai role
    if ($insert && $update) {
        $cek_trx = mysqli_query($koneksi, "SELECT id_transaksi FROM transaksi WHERE id_antrian='$id_antrian' LIMIT 1");
        if (mysqli_num_rows($cek_trx) == 0) {
            mysqli_query($koneksi, "INSERT INTO transaksi (id_antrian, id_rekam_medis, nama_pasien, tindakan, biaya, status_bayar) VALUES ('$id_antrian', '$id_rm_baru', '$nama_pasien', '$tindakan', 0, 'Belum Bayar')");
        }
        if (isset($_SESSION['role']) && $_SESSION['role'] == 'Dokter') {
            header("location:antrian.php?status=selesai_periksa");
            exit();
        } else {
            header("location:tambah_transaksi.php?id_antrian=$id_antrian");
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
                <label>Resep Obat (Jika Ada)</label>
                <textarea name="resep" rows="3" placeholder="Contoh: Asam Mefenamat 500mg (3x1), Amoxicillin 500mg (3x1)..."></textarea>
            </div>

            <div class="form-group">
                <label>Tanggal Kunjungan Kembali / Jadwal Kontrol (Opsional)</label>
                <input type="date" name="tgl_kunjungan_kembali" title="Jika pasien harus kembali, tentukan tanggalnya di sini.">
            </div>

            <div style="margin-top: 30px;">
                <a href="antrian.php" class="btn-back">Batal</a>
                <button type="submit" name="simpan_rm" class="btn-submit">
                    <i class="fas fa-tooth"></i> Simpan Perawatan
                </button>
            </div>
                    <i class="fas fa-tooth"></i> Simpan Perawatan
                </button>
            </div>
        </form>
    </div>

</body>

</html>