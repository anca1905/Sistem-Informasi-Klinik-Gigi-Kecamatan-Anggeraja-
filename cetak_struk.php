<?php
include 'config/koneksi.php';

$id = $_GET['id'];

$query = mysqli_query($koneksi, "SELECT * FROM antrian WHERE id = '$id'");
$data = mysqli_fetch_array($query);

if (!$data) {
    echo "Data tidak ditemukan!";
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Antrian #<?php echo $data['no_antrian']; ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            /* Font ala struk */
            background: #e5e5e5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .ticket {
            background: white;
            width: 300px;
            /* Lebar ala kertas struk */
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-top: 5px solid #064e3b;
            /* Hijau tema kita */
            border-bottom: 5px solid #064e3b;
        }

        .header h2 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
        }

        .header p {
            margin: 5px 0 20px 0;
            font-size: 12px;
            border-bottom: 1px dashed #333;
            padding-bottom: 10px;
        }

        .nomor-antrian {
            font-size: 60px;
            font-weight: bold;
            margin: 10px 0;
            line-height: 1;
        }

        .label {
            font-size: 14px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .info {
            font-size: 12px;
            text-align: left;
            margin-top: 20px;
            border-top: 1px dashed #333;
            padding-top: 10px;
        }

        .footer {
            margin-top: 20px;
            font-size: 10px;
            color: #666;
        }

        /* Tombol Print (Disembunyikan saat diprint) */
        .btn-print {
            margin-top: 20px;
            background: #064e3b;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            font-weight: bold;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-home {
            background: #555;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            margin-left: 5px;
        }

        /* CSS KHUSUS PRINT */
        @media print {
            body {
                background: white;
                height: auto;
            }

            .ticket {
                box-shadow: none;
                border: none;
                width: 100%;
            }

            .btn-print,
            .btn-home {
                display: none;
            }

            /* Tombol hilang pas diprint */
        }
    </style>
</head>

<body>

    <div class="ticket">
        <div class="header">
            <h2>Klinik Desa</h2>
            <p>Jl. Atlanta No.6, Kecamatan Anggeraja</p>
        </div>

        <div class="label">Nomor Antrian Anda</div>
        <div class="nomor-antrian"><?php echo $data['no_antrian']; ?></div>

        <div class="info">
            <table width="100%">
                <tr>
                    <td>Nama</td>
                    <td>: <b><?php echo $data['nama_pendaftar']; ?></b></td>
                </tr>
                <tr>
                    <td>Tanggal</td>
                    <td>: <?php echo date('d-m-Y', strtotime($data['waktu_daftar'])); ?></td>
                </tr>
                <tr>
                    <td>Jam</td>
                    <td>: <?php echo date('H:i', strtotime($data['waktu_daftar'])); ?> WIB</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            *Simpan struk ini sampai giliran Anda dipanggil.<br>
            Terima kasih semoga lekas sembuh.
        </div>

        <button onclick="window.print()" class="btn-print">🖨️ Cetak / Simpan PDF</button>
        <a href="index.php" class="btn-home">Kembali</a>
    </div>

</body>

</html>