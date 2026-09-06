<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../auth/login.php");
    exit();
}

$id = intval($_GET['id']);
$q  = mysqli_query($koneksi, "SELECT t.*, a.no_antrian, a.waktu_daftar FROM transaksi t LEFT JOIN antrian a ON t.id_antrian = a.id WHERE t.id_transaksi = '$id'");
$trx = mysqli_fetch_assoc($q);

if (!$trx) {
    echo "<script>alert('Data tidak ditemukan!');window.location='transaksi.php';</script>";
    exit();
}

// Ambil info klinik
$klinik_nama = "Klinik Gigi Kecamatan Anggeraja";
$klinik_alamat = "Jl. Poros Enrekang – Makale, Anggeraja, Enrekang";
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran #<?php echo $trx['id_transaksi']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f3f4f6;
            color: #1f2937;
            padding: 30px 20px;
        }

        .struk-wrapper {
            max-width: 420px;
            margin: 0 auto;
        }

        .struk {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .12);
        }

        .struk-header {
            background: linear-gradient(135deg, #064e3b, #10b981);
            color: white;
            padding: 24px;
            text-align: center;
        }

        .struk-header .icon {
            font-size: 2.2rem;
            margin-bottom: 10px;
        }

        .struk-header h1 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .struk-header p {
            font-size: .78rem;
            opacity: .85;
        }

        .struk-badge {
            text-align: center;
            margin: -14px auto 0;
        }

        .struk-badge span {
            background: #10b981;
            color: white;
            padding: 6px 20px;
            border-radius: 20px;
            font-size: .8rem;
            font-weight: 700;
            display: inline-block;
        }

        .struk-body {
            padding: 24px;
        }

        .struk-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #f3f4f6;
            font-size: .875rem;
        }

        .struk-row:last-child {
            border-bottom: none;
        }

        .struk-row .label {
            color: #6b7280;
        }

        .struk-row .value {
            font-weight: 600;
            color: #1f2937;
            text-align: right;
            max-width: 55%;
        }

        .struk-total {
            background: #f0fdf4;
            border: 2px dashed #10b981;
            border-radius: 10px;
            padding: 16px;
            margin: 20px 0;
            text-align: center;
        }

        .struk-total .total-label {
            font-size: .8rem;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .struk-total .total-nilai {
            font-size: 1.6rem;
            font-weight: 700;
            color: #064e3b;
        }

        .struk-footer {
            background: #f9fafb;
            padding: 16px 24px;
            text-align: center;
            font-size: .78rem;
            color: #9ca3af;
            border-top: 1px solid #f3f4f6;
        }

        .btn-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .btn-print {
            background: #064e3b;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-family: inherit;
            font-weight: 600;
            cursor: pointer;
            flex: 1;
            font-size: .9rem;
        }

        .btn-back {
            background: #f3f4f6;
            color: #374151;
            padding: 12px;
            border-radius: 10px;
            font-family: inherit;
            font-weight: 600;
            text-decoration: none;
            flex: 1;
            font-size: .9rem;
            text-align: center;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .btn-actions {
                display: none;
            }

            .struk {
                box-shadow: none;
                border-radius: 0;
            }
        }
    </style>
</head>

<body>
    <div class="struk-wrapper">
        <div class="struk">
            <div class="struk-header">
                <div class="icon"><i class="fas fa-tooth"></i></div>
                <h1><?php echo $klinik_nama; ?></h1>
                <p><?php echo $klinik_alamat; ?></p>
            </div>

            <div class="struk-badge">
                <span>
                    <?php echo $trx['status_bayar'] == 'Lunas' ? '✓ LUNAS' : 'BELUM BAYAR'; ?>
                </span>
            </div>

            <div class="struk-body">
                <div class="struk-row">
                    <span class="label">No. Struk</span>
                    <span class="value">TRX-<?php echo str_pad($trx['id_transaksi'], 5, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="struk-row">
                    <span class="label">Tanggal</span>
                    <span class="value"><?php echo date('d M Y, H:i', strtotime($trx['tanggal_transaksi'])); ?></span>
                </div>
                <div class="struk-row">
                    <span class="label">No. Antrian</span>
                    <span class="value">#<?php echo $trx['no_antrian'] ?? '-'; ?></span>
                </div>
                <div class="struk-row">
                    <span class="label">Nama Pasien</span>
                    <span class="value"><?php echo htmlspecialchars($trx['nama_pasien']); ?></span>
                </div>
                <div class="struk-row">
                    <span class="label">Tindakan</span>
                    <span class="value" style="font-weight:400;font-size:.8rem;"><?php echo htmlspecialchars($trx['tindakan'] ?? '-'); ?></span>
                </div>
                <div class="struk-row">
                    <span class="label">Metode Bayar</span>
                    <span class="value"><?php echo $trx['metode_bayar']; ?></span>
                </div>

                <div class="struk-total">
                    <div class="total-label">Total Pembayaran</div>
                    <div class="total-nilai">Rp <?php echo number_format($trx['biaya'], 0, ',', '.'); ?></div>
                </div>

                <div style="text-align:center;font-size:.8rem;color:#6b7280;margin-top:8px;">
                    <i class="fas fa-heart" style="color:#10b981;"></i>
                    Terima kasih atas kepercayaan Anda
                </div>
            </div>

            <div class="struk-footer">
                Simpan struk ini sebagai bukti pembayaran resmi.
            </div>
        </div>

        <div class="btn-actions">
            <a href="transaksi.php" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
            <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Cetak Struk</button>
        </div>
    </div>
</body>

</html>