<?php
include 'config/koneksi.php';

// Cek nomor antrian (sama kayak tadi)
$hari_ini = date('Y-m-d');
$query = mysqli_query($koneksi, "SELECT MAX(no_antrian) as kodeTerbesar FROM antrian WHERE DATE(waktu_daftar) = '$hari_ini'");
$data = mysqli_fetch_array($query);
$urutan = $data['kodeTerbesar'];
$urutan++;
$nomor_antrian = $urutan;

// Variabel notifikasi
$notif_sukses = false;
$notif_gagal = false;

if (isset($_POST['simpan'])) {
    $nama = htmlspecialchars($_POST['nama']);
    $keluhan = htmlspecialchars($_POST['keluhan']);

    $simpan = mysqli_query($koneksi, "INSERT INTO antrian (no_antrian, nama_pendaftar, keluhan, status) VALUES ('$nomor_antrian', '$nama', '$keluhan', 'Menunggu')");

    if ($simpan) {
        // AMBIL ID YANG BARUSAN DIBUAT
        $id_terbaru = mysqli_insert_id($koneksi);

        $notif_sukses = true; // Trigger SweetAlert Sukses
    } else {
        $notif_gagal = true; // Trigger SweetAlert Gagal
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ambil Antrian - Klinik Desa</title>
    <style>
        /* Menggunakan style yang sama dengan index biar konsisten */
        :root {
            --hijau-tua: #059669;
            --hijau-muda: #34d399;
            --putih-tulang: #f9fafb;
            --teks-gelap: #1f2937;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--putih-tulang);
            color: var(--teks-gelap);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .container {
            background: white;
            width: 100%;
            max-width: 500px;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-top: 10px solid var(--hijau-tua);
        }

        h2 {
            color: var(--hijau-tua);
            margin-bottom: 5px;
        }

        p.subtitle {
            color: #6b7280;
            margin-bottom: 30px;
        }

        /* Tampilan Nomor Antrian Besar */
        .nomor-box {
            background: #d1fae5;
            color: var(--hijau-tua);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            border: 2px dashed var(--hijau-tua);
        }

        .nomor-box span {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .nomor-box h1 {
            font-size: 60px;
            margin: 0;
            line-height: 1;
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
        }

        input,
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 16px;
            box-sizing: border-box;
            /* Biar padding gak ngerusak lebar */
            transition: 0.3s;
        }

        input:focus,
        textarea:focus {
            border-color: var(--hijau-tua);
            outline: none;
            background: #f0fdf4;
        }

        .btn-submit {
            background: var(--hijau-tua);
            color: white;
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 50px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: #047857;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(5, 150, 105, 0.3);
        }

        .link-kembali {
            display: block;
            margin-top: 20px;
            text-decoration: none;
            color: #6b7280;
            font-size: 14px;
        }

        .link-kembali:hover {
            color: var(--hijau-tua);
        }
    </style>
</head>

<body>

    <div class="container">
        <h2>Ambil Antrian</h2>
        <p class="subtitle">Isi data diri untuk mendapatkan nomor antrian.</p>

        <div class="nomor-box">
            <span>Nomor Antrian Kamu Nanti:</span>
            <h1><?php echo $nomor_antrian; ?></h1>
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Nama Lengkap Pasien</label>
                <input type="text" name="nama" placeholder="Contoh: Budi Santoso" required autocomplete="off">
            </div>

            <div class="form-group">
                <label>Keluhan / Sakit Apa?</label>
                <textarea name="keluhan" rows="3" placeholder="Contoh: Demam tinggi sejak semalam, pusing..." required></textarea>
            </div>

            <button type="submit" name="simpan" class="btn-submit">🚀 Ambil Antrian</button>
        </form>

        <a href="index.php" class="link-kembali">← Kembali ke Halaman Utama</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Cek variabel PHP, kalau sukses munculkan SweetAlert
        <?php if ($notif_sukses) { ?>
            Swal.fire({
                title: 'Berhasil!',
                text: 'Nomor Antrian Kamu: <?php echo $nomor_antrian; ?>. Silakan simpan nomor antrian.',
                icon: 'success',
                confirmButtonText: 'Cetak Kartu Antrian', // Ubah teks tombol
                confirmButtonColor: '#059669'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirect ke halaman cetak bawa ID
                    window.location = 'cetak_struk.php?id=<?php echo $id_terbaru; ?>';
                }
            });
        <?php } ?>

        <?php if ($notif_gagal) { ?>
            Swal.fire({
                title: 'Gagal!',
                text: 'Terjadi kesalahan sistem, coba lagi nanti.',
                icon: 'error',
                confirmButtonText: 'Tutup'
            });
        <?php } ?>
    </script>

</body>

</html>