<?php
include '../config/koneksi.php';

// Ambil data dari Form Filter
$jenis = $_GET['jenis'];
$tgl_awal = $_GET['tgl_awal'];
$tgl_akhir = $_GET['tgl_akhir'];

// Validasi Query berdasarkan jenis laporan
if ($jenis == 'antrian') {
    $judul = "LAPORAN KUNJUNGAN PASIEN";
    $query = mysqli_query($koneksi, "SELECT * FROM antrian WHERE DATE(waktu_daftar) BETWEEN '$tgl_awal' AND '$tgl_akhir' ORDER BY waktu_daftar ASC");
} elseif ($jenis == 'kunjungan_berikutnya') {
    $judul = "LAPORAN JADWAL KUNJUNGAN BERIKUTNYA";
    $query = mysqli_query($koneksi, "SELECT * FROM rekam_medis WHERE kunjungan_berikutnya BETWEEN '$tgl_awal' AND '$tgl_akhir' ORDER BY kunjungan_berikutnya ASC");
} else {
    $judul = "LAPORAN REKAM MEDIS";
    // Disini kita ambil data dari rekam medis (nanti bisa di JOIN dengan pasien kalo ada)
    $query = mysqli_query($koneksi, "SELECT * FROM rekam_medis WHERE tanggal_periksa BETWEEN '$tgl_awal' AND '$tgl_akhir' ORDER BY tanggal_periksa ASC");
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan</title>
    <style>
        body {
            font-family: 'Times New Roman', serif;
            padding: 40px;
        }

        /* Kop Surat Sederhana */
        .kop-surat {
            text-align: center;
            border-bottom: 3px double black;
            padding-bottom: 10px;
            margin-bottom: 20px;
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

        .info-laporan {
            margin-bottom: 20px;
        }

        /* Tabel Laporan Formal */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th,
        td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
            font-size: 14px;
        }

        th {
            background-color: #f0f0f0;
            text-align: center;
        }

        /* Area Tanda Tangan */
        .ttd-area {
            float: right;
            text-align: center;
            width: 200px;
            margin-top: 50px;
        }

        /* Hilangkan tombol print saat dicetak */
        @media print {
            .btn-print {
                display: none;
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
        }
    </style>
</head>

<body>

    <button onclick="window.print()" class="btn-print">🖨️ Cetak Dokumen</button>

    <div class="kop-surat">
        <h2>KLINIK GIGI DESA SEHAT</h2>
        <p>Jl. Atlanta No.6, Kecamatan Anggeraja</p>
        <p>Telp: (021) 555-8888 | Email: admin@klinikdesa.com</p>
    </div>

    <div class="info-laporan">
        <strong>Perihal:</strong> <?php echo $judul; ?><br>
        <strong>Periode:</strong> <?php echo date('d-m-Y', strtotime($tgl_awal)); ?> s/d <?php echo date('d-m-Y', strtotime($tgl_akhir)); ?>
    </div>

    <table>
        <thead>
            <?php if ($jenis == 'antrian') { ?>
                <tr>
                    <th width="5%">No</th>
                    <th>Tanggal & Jam</th>
                    <th>Nomor Antrian</th>
                    <th>Nama Pasien</th>
                    <th>Keluhan</th>
                    <th>Status Akhir</th>
                </tr>
            <?php } elseif ($jenis == 'kunjungan_berikutnya') { ?>
                <tr>
                    <th width="5%">No</th>
                    <th>Tanggal Kunjungan Awal</th>
                    <th>Nama Pasien</th>
                    <th>Jadwal Kunjungan Berikutnya</th>
                    <th>Keluhan / Diagnosa</th>
                </tr>
            <?php } else { ?>
                <tr>
                    <th width="5%">No</th>
                    <th>Tanggal Periksa</th>
                    <th>Keluhan</th>
                    <th>Diagnosa</th>
                    <th>Resep Obat</th>
                </tr>
            <?php } ?>
        </thead>
        <tbody>
            <?php
            $no = 1;
            if (mysqli_num_rows($query) > 0) {
                while ($row = mysqli_fetch_array($query)) {
            ?>
                    <tr>
                        <td style="text-align: center;"><?php echo $no++; ?></td>

                        <?php if ($jenis == 'antrian') { ?>
                            <td><?php echo date('d-m-Y H:i', strtotime($row['waktu_daftar'])); ?></td>
                            <td style="text-align: center;"><?php echo $row['no_antrian']; ?></td>
                            <td><?php echo $row['nama_pendaftar']; ?></td>
                            <td><?php echo $row['keluhan']; ?></td>
                            <td><?php echo $row['status']; ?></td>

                        <?php } elseif ($jenis == 'kunjungan_berikutnya') { ?>
                            <td style="text-align: center;"><?php echo date('d-m-Y', strtotime($row['tanggal_periksa'])); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_pasien'] ?? '-'); ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php echo date('d-m-Y', strtotime($row['kunjungan_berikutnya'])); ?></td>
                            <td><?php echo "<b>Keluhan:</b> " . $row['keluhan'] . "<br><b>Diagnosa:</b> " . $row['diagnosa']; ?></td>

                        <?php } else { ?>
                            <td style="text-align: center;"><?php echo date('d-m-Y', strtotime($row['tanggal_periksa'])); ?></td>
                            <td><?php echo $row['keluhan']; ?></td>
                            <td><?php echo $row['diagnosa']; ?></td>
                            <td><?php echo $row['resep_obat']; ?></td>
                        <?php } ?>

                    </tr>
            <?php
                }
            } else {
                echo "<tr><td colspan='6' style='text-align:center; padding: 20px;'>Tidak ada data pada periode ini.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <div class="ttd-area">
        <p>Anggeraja, <?php echo date('d-m-Y'); ?><br>
            <?php
            // In a real application, we might want to change this dynamically based on login.
            // For now, let's keep it generic or based on a quick check.
            session_start();
            $role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Kepala Klinik';
            $nama = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'drg. Budi Santoso';

            if ($role == 'Dokter') {
                echo "Dokter Pemeriksa,</p>";
            } else {
                echo "Kepala Klinik,</p>";
            }
            ?>
            <br><br><br>
        <p><b><?php echo htmlspecialchars($nama); ?></b></p>
    </div>

</body>

</html>