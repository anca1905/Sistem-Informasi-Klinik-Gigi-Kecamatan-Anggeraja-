<?php
include 'config/koneksi.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$query = mysqli_query($koneksi, "
    SELECT a.*, p.kode_pasien, d.nama_dokter, j.hari, j.jam_mulai, j.jam_selesai
    FROM antrian a
    LEFT JOIN pasien p ON a.id_pasien = p.id
    LEFT JOIN dokter d ON a.id_dokter = d.id_dokter
    LEFT JOIN jadwal_dokter j ON a.id_jadwal = j.id
    WHERE a.id = '$id'
");
$data = mysqli_fetch_array($query);

if (!$data) {
    echo "Data antrian tidak ditemukan!";
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Antrian #<?php echo $data['no_antrian']; ?> - Klinik Gigi Anggeraja</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            background: #e5e5e5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }

        .ticket {
            background: white;
            width: 320px;
            padding: 25px 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-top: 5px solid #064e3b;
            border-bottom: 5px solid #064e3b;
            border-radius: 4px;
        }

        .header h2 {
            margin: 0;
            font-size: 17px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header p {
            margin: 5px 0 15px 0;
            font-size: 11px;
            border-bottom: 1px dashed #333;
            padding-bottom: 10px;
            color: #444;
        }

        .nomor-antrian {
            font-size: 64px;
            font-weight: bold;
            margin: 8px 0;
            line-height: 1;
            color: #064e3b;
        }

        .label {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .info {
            font-size: 12px;
            text-align: left;
            margin-top: 15px;
            border-top: 1px dashed #333;
            padding-top: 10px;
        }

        .info td {
            padding: 3px 0;
            vertical-align: top;
        }

        .footer {
            margin-top: 20px;
            font-size: 10px;
            color: #666;
            border-top: 1px dashed #333;
            padding-top: 10px;
            line-height: 1.4;
        }

        /* Tombol Print (Disembunyikan saat diprint) */
        .btn-box {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 8px;
        }

        .btn-print {
            background: #064e3b;
            color: white;
            border: none;
            padding: 10px 18px;
            cursor: pointer;
            font-weight: bold;
            border-radius: 6px;
            text-decoration: none;
            font-family: inherit;
            font-size: 13px;
        }

        .btn-home {
            background: #64748b;
            color: white;
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
            display: inline-block;
        }

        @media print {
            body {
                background: white;
                height: auto;
                padding: 0;
            }

            .ticket {
                box-shadow: none;
                border: none;
                width: 100%;
                padding: 0;
            }

            .btn-box {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="ticket">
        <div class="header">
            <h2>KLINIK GIGI ANGERAJA</h2>
            <p>Jl. Atlanta No.6, Kecamatan Anggeraja</p>
        </div>

        <div class="label">NOMOR ANTRIAN</div>
        <div class="nomor-antrian">#<?php echo $data['no_antrian']; ?></div>

        <div class="info">
            <table width="100%">
                <tr>
                    <td width="35%">No. RM</td>
                    <td width="5%">:</td>
                    <td><b><?php echo htmlspecialchars($data['kode_pasien'] ?: '-'); ?></b></td>
                </tr>
                <tr>
                    <td>Pasien</td>
                    <td>:</td>
                    <td><b><?php echo htmlspecialchars($data['nama_pendaftar']); ?></b></td>
                </tr>
                <tr>
                    <td>Dokter</td>
                    <td>:</td>
                    <td><?php echo $data['nama_dokter'] ? 'dr. ' . htmlspecialchars($data['nama_dokter']) : '-'; ?></td>
                </tr>
                <tr>
                    <td>Keluhan</td>
                    <td>:</td>
                    <td><?php echo htmlspecialchars($data['keluhan'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td>Tanggal</td>
                    <td>:</td>
                    <td><?php echo date('d-m-Y', strtotime($data['waktu_daftar'])); ?></td>
                </tr>
                <tr>
                    <td>Jam Daftar</td>
                    <td>:</td>
                    <td><?php echo date('H:i', strtotime($data['waktu_daftar'])); ?> WIB</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            *Simpan struk ini sampai nomor Anda dipanggil.<br>
            *Harap bersiap 3 nomor sebelum giliran Anda.<br>
            Terima kasih, semoga lekas sembuh! 🙏
        </div>

        <div class="btn-box">
            <button onclick="window.print()" class="btn-print">🖨️ Cetak / PDF</button>
            <a href="index.php" class="btn-home">Kembali</a>
        </div>
    </div>

</body>

</html>
