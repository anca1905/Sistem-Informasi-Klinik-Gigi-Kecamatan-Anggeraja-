<?php
session_start();
include '../config/koneksi.php';

// Validasi Login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    die("Akses ditolak. Silakan login terlebih dahulu.");
}

if (!isset($_GET['id'])) {
    die("Data tidak ditemukan.");
}

$id_rm = mysqli_real_escape_string($koneksi, $_GET['id']);

$query = mysqli_query($koneksi, "SELECT * FROM rekam_medis WHERE id = '$id_rm'");
if (mysqli_num_rows($query) == 0) {
    die("Rekam medis tidak ditemukan.");
}

$data = mysqli_fetch_array($query);

// Pastikan ada jadwal kunjungan
if (empty($data['kunjungan_berikutnya'])) {
    die("Tidak ada jadwal kunjungan kembali untuk rekam medis ini.");
}

// Format Tanggal Indonesia
$bulanIndo = array("Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember");
$tgl_kembali = date('d', strtotime($data['kunjungan_berikutnya'])) . ' ' . $bulanIndo[(int)date('m', strtotime($data['kunjungan_berikutnya'])) - 1] . ' ' . date('Y', strtotime($data['kunjungan_berikutnya']));
$tgl_periksa = date('d', strtotime($data['tanggal_periksa'])) . ' ' . $bulanIndo[(int)date('m', strtotime($data['tanggal_periksa'])) - 1] . ' ' . date('Y', strtotime($data['tanggal_periksa']));
$hari_ini = date('d') . ' ' . $bulanIndo[(int)date('m') - 1] . ' ' . date('Y');
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Kunjungan - <?php echo htmlspecialchars($data['nama_pasien'] ?? '-'); ?></title>
    <style>
        body {
            font-family: 'Times New Roman', serif;
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
        }

        /* Kop Surat Sederhana */
        .kop-surat {
            text-align: center;
            border-bottom: 3px double black;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }

        .kop-surat h2 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }

        .kop-surat p {
            margin: 5px 0 0 0;
            font-size: 14px;
        }

        .judul-surat {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 30px;
        }

        .isi-surat {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 40px;
        }

        table.info-pasien {
            width: 100%;
            margin-bottom: 20px;
        }

        table.info-pasien td {
            padding: 5px;
            vertical-align: top;
        }

        /* Area Tanda Tangan */
        .ttd-area {
            float: right;
            text-align: center;
            width: 250px;
            margin-top: 50px;
        }

        /* Hilangkan tombol print saat dicetak */
        @media print {
            .btn-print {
                display: none;
            }

            body {
                padding: 0;
                margin: 0;
            }
        }

        .btn-print {
            background: #333;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            margin-bottom: 20px;
            border-radius: 5px;
            display: block;
        }
    </style>
</head>

<body>

    <button onclick="window.print()" class="btn-print">🖨️ Cetak Surat</button>

    <div class="kop-surat">
        <h2>KLINIK GIGI DESA SEHAT</h2>
        <p>Jl. Atlanta No.6, Kecamatan Anggeraja</p>
        <p>Telp: (021) 555-8888 | Email: admin@klinikdesa.com</p>
    </div>

    <div class="judul-surat">
        SURAT JADWAL KUNJUNGAN KEMBALI
    </div>

    <div class="isi-surat">
        <p>Berdasarkan hasil pemeriksaan pada tanggal <?php echo $tgl_periksa; ?>, dengan ini memberitahukan kepada pasien:</p>

        <table class="info-pasien">
            <tr>
                <td width="200"><strong>Nama Pasien</strong></td>
                <td width="10">:</td>
                <td><strong><?php echo htmlspecialchars($data['nama_pasien'] ?? '-'); ?></strong></td>
            </tr>
            <tr>
                <td>Keluhan / Diagnosa</td>
                <td>:</td>
                <td><?php echo nl2br(htmlspecialchars($data['diagnosa'])); ?></td>
            </tr>
        </table>

        <p>Agar datang kembali untuk melanjutkan perawatan / kontrol rutin pada:</p>

        <div style="background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; text-align: center; margin: 20px 0; border-radius: 5px;">
            <h3 style="margin: 0; color: #b45309;">Tanggal: <?php echo $tgl_kembali; ?></h3>
        </div>

        <p>Demikian surat pengingat jadwal ini dibuat agar dapat dipergunakan sebagaimana mestinya.</p>
    </div>

    <div class="ttd-area">
        <p>Anggeraja, <?php echo $hari_ini; ?><br>Dokter Pemeriksa,</p>
        <br><br><br>
        <p><b><?php echo htmlspecialchars($_SESSION['login_user']); ?></b><br>(..........................................................)</p>
    </div>

    <script>
        // Otomatis print saat halaman dibuka
        window.onload = function() {
            window.print();
        }
    </script>
</body>

</html>