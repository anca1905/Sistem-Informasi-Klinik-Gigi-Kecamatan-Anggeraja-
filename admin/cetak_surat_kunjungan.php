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

$query = mysqli_query($koneksi, "SELECT rm.*, p.nama AS nama_pasien_db, p.no_telepon, p.kode_pasien, d.nama_dokter 
                                 FROM rekam_medis rm 
                                 LEFT JOIN pasien p ON rm.id_pasien = p.id 
                                 LEFT JOIN dokter d ON rm.id_dokter = d.id_dokter 
                                 WHERE rm.id = '$id_rm'");
if (mysqli_num_rows($query) == 0) {
    die("Rekam medis tidak ditemukan.");
}

$data = mysqli_fetch_array($query);

// Pastikan ada jadwal kunjungan
if (empty($data['kunjungan_berikutnya'])) {
    die("Tidak ada jadwal kunjungan kembali untuk rekam medis ini.");
}

// Nama Pasien prioritas
$nama_pasien = !empty($data['nama_pasien']) ? $data['nama_pasien'] : (!empty($data['nama_pasien_db']) ? $data['nama_pasien_db'] : '-');

// Format Tanggal Indonesia
$bulanIndo = array("Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember");
$tgl_kembali = date('d', strtotime($data['kunjungan_berikutnya'])) . ' ' . $bulanIndo[(int)date('m', strtotime($data['kunjungan_berikutnya'])) - 1] . ' ' . date('Y', strtotime($data['kunjungan_berikutnya']));
$tgl_periksa = date('d', strtotime($data['tanggal_periksa'])) . ' ' . $bulanIndo[(int)date('m', strtotime($data['tanggal_periksa'])) - 1] . ' ' . date('Y', strtotime($data['tanggal_periksa']));
$hari_ini = date('d') . ' ' . $bulanIndo[(int)date('m') - 1] . ' ' . date('Y');

// Format Nomor Telepon Pasien untuk WhatsApp
$no_telepon = trim($data['no_telepon'] ?? '');
$wa_number = preg_replace('/[^0-9]/', '', $no_telepon);
if (!empty($wa_number)) {
    if (substr($wa_number, 0, 1) === '0') {
        $wa_number = '62' . substr($wa_number, 1);
    } elseif (substr($wa_number, 0, 2) !== '62') {
        $wa_number = '62' . $wa_number;
    }
}

// Format Pesan WhatsApp
$diagnosa_text = trim(preg_replace('/\s+/', ' ', strip_tags($data['diagnosa'] ?? '-')));
$pesan_wa = "*SURAT JADWAL KUNJUNGAN KEMBALI*\n"
    . "*KLINIK GIGI KECAMATAN ANGGERAJA*\n"
    . "Jl. Atlanta No.6, Kecamatan Anggeraja\n"
    . "-------------------------------------------\n\n"
    . "Halo Bapak/Ibu *" . $nama_pasien . "*,\n\n"
    . "Berdasarkan hasil pemeriksaan pada tanggal " . $tgl_periksa . ", diberitahukan agar datang kembali untuk kontrol/perawatan lanjutan pada:\n\n"
    . "🗓️ *Tanggal Kontrol:* *" . $tgl_kembali . "*\n"
    . "📋 *Keluhan / Diagnosa:* " . $diagnosa_text . "\n\n"
    . "Dokter Pemeriksa:\n"
    . "*drg. Syamsuriah*\n\n"
    . "Mohon hadir tepat waktu sesuai jadwal yang telah ditentukan.\n"
    . "Terima kasih, semoga sehat selalu! 🙏";
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Kunjungan - <?php echo htmlspecialchars($nama_pasien); ?></title>
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

        /* Tombol Aksi */
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
        }

        .btn-print {
            background: #1f2937;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: background 0.2s;
        }

        .btn-print:hover {
            background: #111827;
        }

        .btn-wa {
            background: #25D366;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: background 0.2s;
        }

        .btn-wa:hover {
            background: #1eb857;
        }

        /* Hilangkan tombol aksi saat dicetak */
        @media print {
            .action-buttons,
            .btn-print,
            .btn-wa {
                display: none !important;
            }

            body {
                padding: 0;
                margin: 0;
            }
        }
    </style>
</head>

<body>

    <div class="action-buttons">
        <button onclick="window.print()" class="btn-print">🖨️ Cetak Surat</button>
        <button onclick="bagikanWhatsApp()" class="btn-wa">📱 Bagikan ke WhatsApp</button>
    </div>

    <div class="kop-surat">
        <h2>KLINIK GIGI KECAMATAN ANGGERAJA</h2>
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
                <td><strong><?php echo htmlspecialchars($nama_pasien); ?></strong></td>
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
        <p><b>drg. Syamsuriah</b></p>
    </div>

    <script>
        function bagikanWhatsApp() {
            var phone = '<?php echo $wa_number; ?>';
            var text = <?php echo json_encode($pesan_wa); ?>;

            if (!phone) {
                var inputPhone = prompt("Nomor telepon pasien belum tercatat di data sistem.\nSilakan masukkan nomor WhatsApp pasien (contoh: 08123456789):");
                if (!inputPhone) {
                    return;
                }
                phone = inputPhone.replace(/[^0-9]/g, '');
                if (phone.charAt(0) === '0') {
                    phone = '62' + phone.substring(1);
                } else if (!phone.startsWith('62')) {
                    phone = '62' + phone;
                }
            }

            var waUrl = "https://api.whatsapp.com/send?phone=" + encodeURIComponent(phone) + "&text=" + encodeURIComponent(text);
            window.open(waUrl, '_blank');
        }
    </script>
</body>

</html>