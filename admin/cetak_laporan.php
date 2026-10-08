<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../auth/login.php");
    exit();
}

// Ambil data dari Form Filter
$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : '';
$tab = isset($_GET['tab']) ? $_GET['tab'] : '';

$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : '';
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : '';
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : '';

// Validasi Query berdasarkan jenis/tab laporan
if ($jenis == 'antrian') {
    $judul = "LAPORAN KUNJUNGAN PASIEN";
    $query = mysqli_query($koneksi, "SELECT * FROM antrian WHERE DATE(waktu_daftar) BETWEEN '$tgl_awal' AND '$tgl_akhir' ORDER BY waktu_daftar ASC");
} elseif ($jenis == 'kunjungan_berikutnya' || $tab == 'kontrol') {
    $judul = "LAPORAN JADWAL KUNJUNGAN BERIKUTNYA (KONTROL)" . ($tanggal ? " TANGGAL " . date('d-m-Y', strtotime($tanggal)) : "");
    $where = $tanggal ? "WHERE DATE(rm.kunjungan_berikutnya) = '$tanggal'" : "WHERE rm.kunjungan_berikutnya IS NOT NULL AND rm.kunjungan_berikutnya > '1970-01-01'";
    $query = mysqli_query($koneksi, "
        SELECT rm.*, p.kode_pasien, p.nama AS nama_pasien, p.no_telepon, d.nama_dokter
        FROM rekam_medis rm 
        LEFT JOIN pasien p ON rm.id_pasien = p.id 
        LEFT JOIN dokter d ON rm.id_dokter = d.id_dokter 
        $where 
        ORDER BY rm.kunjungan_berikutnya ASC
    ");
} elseif ($tab == 'pemeriksaan') {
    $judul = "LAPORAN PEMERIKSAAN" . ($tanggal ? " TANGGAL " . date('d-m-Y', strtotime($tanggal)) : "");
    $where = $tanggal ? "WHERE DATE(rm.tanggal_periksa) = '$tanggal'" : "";
    $query = mysqli_query($koneksi, "SELECT rm.*, p.nama AS nama_pasien FROM rekam_medis rm LEFT JOIN pasien p ON rm.id_pasien = p.id $where ORDER BY rm.tanggal_periksa ASC");
} elseif ($tab == 'pembayaran') {
    $judul = "LAPORAN PEMBAYARAN" . ($tanggal ? " TANGGAL " . date('d-m-Y', strtotime($tanggal)) : "");
    $where = $tanggal ? "WHERE DATE(t.tanggal_transaksi) = '$tanggal'" : "";
    $query = mysqli_query($koneksi, "SELECT t.*, a.no_antrian FROM transaksi t LEFT JOIN antrian a ON t.id_antrian = a.id $where ORDER BY t.tanggal_transaksi ASC");
} elseif ($tab == 'pasien') {
    $judul = "LAPORAN DAFTAR PASIEN";
    $query = mysqli_query($koneksi, "SELECT * FROM pasien ORDER BY id ASC");
} else {
    $judul = "LAPORAN REKAM MEDIS";
    // Disini kita ambil data dari rekam medis (nanti bisa di JOIN dengan pasien kalo ada)
    $query = mysqli_query($koneksi, "SELECT * FROM rekam_medis WHERE tanggal_periksa BETWEEN '$tgl_awal' AND '$tgl_akhir' ORDER BY tanggal_periksa ASC");
}

$periode_text = "";
if ($tgl_awal && $tgl_akhir) {
    $periode_text = date('d-m-Y', strtotime($tgl_awal)) . " s/d " . date('d-m-Y', strtotime($tgl_akhir));
} elseif ($tanggal) {
    $periode_text = date('d-m-Y', strtotime($tanggal));
} else {
    $periode_text = "Semua Data";
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
        <strong>Periode:</strong> <?php echo $periode_text; ?>
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
            <?php } elseif ($jenis == 'kunjungan_berikutnya' || $tab == 'kontrol') { ?>
                <tr>
                    <th width="5%">No</th>
                    <th>Jadwal Kontrol</th>
                    <th>No. RM</th>
                    <th>Nama Pasien</th>
                    <th>No. Telepon</th>
                    <th>Dokter</th>
                    <th>Diagnosa</th>
                </tr>
            <?php } elseif ($tab == 'pemeriksaan' || $jenis == '') { ?>
                <tr>
                    <th width="5%">No</th>
                    <th>Tanggal Periksa</th>
                    <th>Pasien</th>
                    <th>Keluhan</th>
                    <th>Diagnosa</th>
                    <th>Resep Obat</th>
                </tr>
            <?php } elseif ($tab == 'pembayaran') { ?>
                <tr>
                    <th width="5%">No</th>
                    <th>Tanggal</th>
                    <th>No. Antrian</th>
                    <th>Nama Pasien</th>
                    <th>Biaya</th>
                    <th>Metode</th>
                    <th>Status</th>
                </tr>
            <?php } elseif ($tab == 'pasien') { ?>
                <tr>
                    <th width="5%">No</th>
                    <th>Kode</th>
                    <th>NIK</th>
                    <th>Nama Pasien</th>
                    <th>L/P</th>
                    <th>Tanggal Lahir</th>
                    <th>No. Telepon</th>
                </tr>
            <?php } ?>
        </thead>
        <tbody>
            <?php
            $no = 1;
            if ($query && mysqli_num_rows($query) > 0) {
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

                        <?php } elseif ($jenis == 'kunjungan_berikutnya' || $tab == 'kontrol') { ?>
                            <td style="text-align: center; font-weight: bold;"><?php echo date('d-m-Y', strtotime($row['kunjungan_berikutnya'])); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($row['kode_pasien'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_pasien'] ?? '-'); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($row['no_telepon'] ?? '-'); ?></td>
                            <td><?php echo $row['nama_dokter'] ? 'dr. ' . htmlspecialchars($row['nama_dokter']) : '-'; ?></td>
                            <td><?php echo htmlspecialchars($row['diagnosa'] ?? '-'); ?></td>

                        <?php } elseif ($tab == 'pemeriksaan' || $jenis == '') { ?>
                            <td style="text-align: center;"><?php echo date('d-m-Y', strtotime($row['tanggal_periksa'])); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_pasien'] ?? '-'); ?></td>
                            <td><?php echo $row['keluhan']; ?></td>
                            <td><?php echo $row['diagnosa']; ?></td>
                            <td><?php echo $row['resep_obat']; ?></td>
                        
                        <?php } elseif ($tab == 'pembayaran') { ?>
                            <td style="text-align: center;"><?php echo date('d-m-Y H:i', strtotime($row['tanggal_transaksi'])); ?></td>
                            <td style="text-align: center;"><?php echo $row['no_antrian']; ?></td>
                            <td><?php echo htmlspecialchars($row['nama_pasien']); ?></td>
                            <td>Rp <?php echo number_format($row['biaya'], 0, ',', '.'); ?></td>
                            <td><?php echo $row['metode_bayar']; ?></td>
                            <td><?php echo $row['status_bayar']; ?></td>
                        
                        <?php } elseif ($tab == 'pasien') { ?>
                            <td style="text-align: center;"><?php echo htmlspecialchars($row['kode_pasien']); ?></td>
                            <td><?php echo htmlspecialchars($row['nik']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama']); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($row['jenis_kelamin'] == 'Laki-laki' ? 'L' : ($row['jenis_kelamin'] == 'Perempuan' ? 'P' : '-')); ?></td>
                            <td style="text-align: center;"><?php echo date('d-m-Y', strtotime($row['tanggal_lahir'])); ?></td>
                            <td><?php echo htmlspecialchars($row['no_telepon']); ?></td>
                        <?php } ?>

                    </tr>
            <?php
                }
            } else {
                echo "<tr><td colspan='10' style='text-align:center; padding: 20px;'>Tidak ada data pada periode ini.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <div class="ttd-area">
        <p>Anggeraja, <?php echo date('d-m-Y'); ?><br>
            <?php
            $role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Manajer Klinik';
            $nama = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Manajer Klinik';

            if ($role == 'Manajer Klinik') {
                echo "Manajer Klinik,</p>";
            } elseif ($role == 'Dokter') {
                echo "Dokter Pemeriksa,</p>";
            } else {
                echo "Petugas / Admin Klinik,</p>";
            }
            ?>
            <br><br><br>
        <p><b><?php echo htmlspecialchars($nama); ?></b></p>
    </div>

</body>

</html>