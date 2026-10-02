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
$id_terbaru = 0;

// Ambil daftar dokter untuk dropdown
$query_dokter = mysqli_query($koneksi, "SELECT d.id_dokter, d.nama_dokter, j.id AS id_jadwal, j.hari, j.jam_mulai, j.jam_selesai
    FROM dokter d
    JOIN jadwal_dokter j ON j.id_dokter = d.id_dokter
    WHERE j.status = 'Praktek'
    ORDER BY j.hari, d.nama_dokter");

if (isset($_POST['simpan'])) {
    $nama  = mysqli_real_escape_string($koneksi, trim($_POST['nama']));
    $tgl_l = mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir']);
    $jenis = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin']);
    $telp  = mysqli_real_escape_string($koneksi, $_POST['no_telepon']);
    $keluhan  = mysqli_real_escape_string($koneksi, trim($_POST['keluhan']));
    $id_jadwal = (int)$_POST['id_jadwal'];

    if (empty($nama) || empty($tgl_l) || empty($jenis) || empty($keluhan) || empty($id_jadwal)) {
        $notif_gagal = true;
    } else {
        // Ambil id_dokter dari jadwal yang dipilih
        $q_jdwl = mysqli_query($koneksi, "SELECT j.id_dokter FROM jadwal_dokter j WHERE j.id = $id_jadwal");
        $row_jdwl = mysqli_fetch_assoc($q_jdwl);
        $id_dokter = $row_jdwl ? (int)$row_jdwl['id_dokter'] : 'NULL';

        // Cek / insert data pasien (berdasarkan nama + tgl lahir)
        $cek = mysqli_query($koneksi, "SELECT id FROM pasien WHERE nama='$nama' AND tanggal_lahir='$tgl_l' LIMIT 1");
        if (mysqli_num_rows($cek) > 0) {
            $pasien = mysqli_fetch_assoc($cek);
            $id_pasien = $pasien['id'];
        } else {
            $total_pasien = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM pasien")) + 1;
            $kode_rm = 'RM' . str_pad($total_pasien, 4, '0', STR_PAD_LEFT);
            mysqli_query($koneksi, "INSERT INTO pasien (kode_pasien, nama, tanggal_lahir, jenis_kelamin, no_telepon, nik, alamat) 
                VALUES ('$kode_rm', '$nama', '$tgl_l', '$jenis', '$telp', '', '')");
            $id_pasien = mysqli_insert_id($koneksi);
        }

        $simpan = mysqli_query($koneksi, "INSERT INTO antrian (id_pasien, id_jadwal, id_dokter, no_antrian, nama_pendaftar, keluhan, status, waktu_daftar) 
            VALUES ($id_pasien, $id_jadwal, $id_dokter, '$nomor_antrian', '$nama', '$keluhan', 'Menunggu', NOW())");

        if ($simpan) {
            $id_terbaru = mysqli_insert_id($koneksi);
            $notif_sukses = true;

            // Kirim notifikasi WhatsApp via Fonnte jika nomor terisi
            if (!empty($telp)) {
                $q_dok = mysqli_query($koneksi, "SELECT nama_dokter FROM dokter WHERE id_dokter='$id_dokter' LIMIT 1");
                $d_dok = mysqli_fetch_assoc($q_dok);
                $nama_dokter = $d_dok ? $d_dok['nama_dokter'] : 'Dokter';

                $msg_antrian = "Halo *$nama*,\n\nPendaftaran antrian Anda di *Klinik Gigi Anggeraja* berhasil!\n\n" .
                               "📌 *Nomor Antrian:* #$nomor_antrian\n" .
                               "📅 *Tanggal:* " . date('d-m-Y') . "\n" .
                               "👨‍⚕️ *Dokter:* $nama_dokter\n\n" .
                               "Silakan datang ke klinik sebelum nomor antrian Anda dipanggil. Terima kasih! 🙏";
                @sendWA($telp, $msg_antrian);
            }
        } else {
            $notif_gagal = true;
        }
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
        textarea,
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 16px;
            box-sizing: border-box;
            /* Biar padding gak ngerusak lebar */
            transition: 0.3s;
            font-family: 'Segoe UI', sans-serif;
            background: white;
        }

        input:focus,
        textarea:focus,
        select:focus {
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
                <label>Nama Lengkap Pasien <span style="color:red;">*</span></label>
                <input type="text" name="nama" placeholder="Contoh: Budi Santoso" required autocomplete="off">
            </div>

            <div class="form-group" style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <label>Tanggal Lahir <span style="color:red;">*</span></label>
                    <input type="date" name="tanggal_lahir" required>
                </div>
                <div style="flex: 1;">
                    <label>Jenis Kelamin <span style="color:red;">*</span></label>
                    <select name="jenis_kelamin" required>
                        <option value="">-- Pilih --</option>
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>No. Telepon / WhatsApp</label>
                <input type="text" name="no_telepon" placeholder="08xxxxxxxxxx" autocomplete="off">
            </div>

            <div class="form-group">
                <label>Pilih Dokter & Jadwal <span style="color:red;">*</span></label>
                <select name="id_jadwal" required>
                    <option value="">-- Pilih Jadwal Dokter --</option>
                    <?php while ($jdwl = mysqli_fetch_assoc($query_dokter)): ?>
                        <option value="<?php echo $jdwl['id_jadwal']; ?>">
                            dr. <?php echo htmlspecialchars($jdwl['nama_dokter']); ?> —
                            <?php echo $jdwl['hari']; ?>
                            (<?php echo substr($jdwl['jam_mulai'], 0, 5); ?>–<?php echo substr($jdwl['jam_selesai'], 0, 5); ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Keluhan / Sakit Apa? <span style="color:red;">*</span></label>
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