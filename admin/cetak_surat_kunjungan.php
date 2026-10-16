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

// Cek apakah ada jadwal kunjungan berikutnya
$has_kontrol = !empty($data['kunjungan_berikutnya']) && $data['kunjungan_berikutnya'] > '1970-01-01';

// Nama Pasien prioritas
$nama_pasien = !empty($data['nama_pasien']) ? $data['nama_pasien'] : (!empty($data['nama_pasien_db']) ? $data['nama_pasien_db'] : '-');

// Format Tanggal Indonesia
$bulanIndo = array("Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember");
$tgl_periksa = !empty($data['tanggal_periksa']) ? date('d', strtotime($data['tanggal_periksa'])) . ' ' . $bulanIndo[(int)date('m', strtotime($data['tanggal_periksa'])) - 1] . ' ' . date('Y', strtotime($data['tanggal_periksa'])) : '-';
$tgl_kembali = $has_kontrol ? date('d', strtotime($data['kunjungan_berikutnya'])) . ' ' . $bulanIndo[(int)date('m', strtotime($data['kunjungan_berikutnya'])) - 1] . ' ' . date('Y', strtotime($data['kunjungan_berikutnya'])) : '';
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
$keluhan_text = trim(preg_replace('/\s+/', ' ', strip_tags($data['keluhan'] ?? '-')));
$resep_text = trim(preg_replace('/\s+/', ' ', strip_tags($data['resep_obat'] ?? '-')));
$tindakan_text = trim(preg_replace('/\s+/', ' ', strip_tags($data['tindakan'] ?? '-')));

if ($has_kontrol) {
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
} else {
    $pesan_wa = "*RESUME HASIL PEMERIKSAAN MEDIS*\n"
        . "*KLINIK GIGI KECAMATAN ANGGERAJA*\n"
        . "Jl. Atlanta No.6, Kecamatan Anggeraja\n"
        . "-------------------------------------------\n\n"
        . "Halo Bapak/Ibu *" . $nama_pasien . "*,\n\n"
        . "Berikut resume pemeriksaan medis Anda pada tanggal " . $tgl_periksa . ":\n\n"
        . "📋 *Keluhan:* " . $keluhan_text . "\n"
        . "🩺 *Diagnosa:* " . $diagnosa_text . "\n"
        . "💊 *Resep Obat:* " . $resep_text . "\n"
        . "💉 *Tindakan:* " . $tindakan_text . "\n\n"
        . "Dokter Pemeriksa:\n"
        . "*drg. Syamsuriah*\n\n"
        . "Terima kasih atas kunjungan Anda, semoga lekas sembuh! 🙏";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title><?php echo $has_kontrol ? 'Surat Kontrol' : 'Resume Medis'; ?> - <?php echo htmlspecialchars($nama_pasien); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', serif;
            background: #f3f4f6;
            padding: 30px 15px;
            margin: 0;
            color: #1f2937;
        }

        .container {
            max-width: 820px;
            margin: 0 auto;
        }

        /* Tombol Aksi */
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 6px;
            font-family: system-ui, -apple-system, sans-serif;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .btn-back {
            background: #e5e7eb;
            color: #374151;
        }

        .btn-back:hover {
            background: #d1d5db;
        }

        .btn-print {
            background: #1f2937;
            color: white;
        }

        .btn-print:hover {
            background: #111827;
        }

        .btn-img {
            background: #2563eb;
            color: white;
        }

        .btn-img:hover {
            background: #1d4ed8;
        }

        .btn-wa {
            background: #10b981;
            color: white;
        }

        .btn-wa:hover {
            background: #059669;
        }

        /* Surat Card */
        #surat-dokumen {
            background: #ffffff;
            padding: 45px 50px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08);
            margin: 0 auto;
        }

        /* Kop Surat Sederhana */
        .kop-surat {
            text-align: center;
            border-bottom: 3px double black;
            padding-bottom: 12px;
            margin-bottom: 25px;
        }

        .kop-surat h2 {
            margin: 0;
            font-size: 23px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .kop-surat p {
            margin: 4px 0 0 0;
            font-size: 13.5px;
            color: #4b5563;
        }

        .judul-surat {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 25px;
            text-transform: uppercase;
        }

        .isi-surat {
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 35px;
        }

        table.info-pasien {
            width: 100%;
            margin: 15px 0 20px 0;
            border-collapse: collapse;
        }

        table.info-pasien td {
            padding: 6px 4px;
            vertical-align: top;
            font-size: 15px;
        }

        .kotak-jadwal {
            background-color: #fffbeb;
            border: 2px dashed #f59e0b;
            padding: 16px;
            text-align: center;
            margin: 25px 0;
            border-radius: 8px;
        }

        /* Area Tanda Tangan */
        .ttd-area {
            float: right;
            text-align: center;
            width: 220px;
            margin-top: 25px;
            font-size: 14.5px;
        }

        /* Hilangkan tombol aksi saat dicetak */
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }

            .action-buttons {
                display: none !important;
            }

            #surat-dokumen {
                border: none;
                box-shadow: none;
                padding: 20px 0;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="action-buttons">
            <a href="riwayat.php" class="btn-action btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
            <button onclick="window.print()" class="btn-action btn-print"><i class="fas fa-print"></i> Cetak Dokumen</button>
            <button onclick="unduhGambar()" class="btn-action btn-img"><i class="fas fa-image"></i> Unduh Gambar (PNG)</button>
            <button onclick="bagikanWhatsAppGambar()" class="btn-action btn-wa"><i class="fab fa-whatsapp"></i> Bagikan Gambar ke WhatsApp</button>
        </div>

        <div id="surat-dokumen">
            <div class="kop-surat">
                <h2>KLINIK GIGI KECAMATAN ANGGERAJA</h2>
                <p>Jl. Atlanta No.6, Kecamatan Anggeraja</p>
                <p>Telp: (021) 555-8888 | Email: admin@klinikdesa.com</p>
            </div>

            <div class="judul-surat">
                <?php echo $has_kontrol ? 'SURAT JADWAL KUNJUNGAN KEMBALI' : 'RESUME HASIL PEMERIKSAAN MEDIS'; ?>
            </div>

            <div class="isi-surat">
                <p>Berdasarkan hasil pemeriksaan klinis pada tanggal <strong><?php echo $tgl_periksa; ?></strong>, dengan ini menerangkan data pasien:</p>

                <table class="info-pasien">
                    <tr>
                        <td width="180"><strong>No. Rekam Medis</strong></td>
                        <td width="10">:</td>
                        <td><strong><?php echo htmlspecialchars($data['kode_pasien'] ?? '-'); ?></strong></td>
                    </tr>
                    <tr>
                        <td><strong>Nama Pasien</strong></td>
                        <td>:</td>
                        <td><strong><?php echo htmlspecialchars($nama_pasien); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Keluhan</td>
                        <td>:</td>
                        <td><?php echo nl2br(htmlspecialchars($data['keluhan'] ?? '-')); ?></td>
                    </tr>
                    <tr>
                        <td>Diagnosa Dokter</td>
                        <td>:</td>
                        <td><?php echo nl2br(htmlspecialchars($data['diagnosa'] ?? '-')); ?></td>
                    </tr>
                    <?php if (!empty($data['tindakan'])): ?>
                        <tr>
                            <td>Tindakan Medis</td>
                            <td>:</td>
                            <td><?php echo nl2br(htmlspecialchars($data['tindakan'])); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if (!empty($data['resep_obat'])): ?>
                        <tr>
                            <td>Resep Obat</td>
                            <td>:</td>
                            <td><?php echo nl2br(htmlspecialchars($data['resep_obat'])); ?></td>
                        </tr>
                    <?php endif; ?>
                </table>

                <?php if ($has_kontrol): ?>
                    <p>Dianjurkan agar datang kembali untuk melanjutkan perawatan / kontrol rutin pada:</p>

                    <div class="kotak-jadwal">
                        <span style="font-size: 13px; color: #b45309; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">JADWAL KONTROL BERIKUTNYA:</span>
                        <h2 style="margin: 6px 0 0; color: #92400e; font-size: 22px; font-weight: 800;"><?php echo $tgl_kembali; ?></h2>
                    </div>

                    <p>Demikian surat pengingat jadwal kontrol ini dibuat agar dapat dipergunakan sebagaimana mestinya.</p>
                <?php else: ?>
                    <p style="margin-top: 25px;">Demikian ringkasan resume hasil pemeriksaan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</p>
                <?php endif; ?>
            </div>

            <div class="ttd-area">
                <p>Anggeraja, <?php echo $hari_ini; ?><br>Dokter Pemeriksa,</p>
                <br><br><br>
                <p><b><?php echo !empty($data['nama_dokter']) ? 'dr. ' . htmlspecialchars($data['nama_dokter']) : 'drg. Syamsuriah'; ?></b></p>
            </div>
            <div style="clear: both;"></div>
        </div>
    </div>

    <!-- Script HTML2Canvas untuk mengubah dokumen menjadi gambar -->
    <script src="../assets/js/html2canvas.min.js"></script>
    <script>
        if (typeof html2canvas === 'undefined') {
            document.write('<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"><\/script>');
        }
    </script>

    <script>
        async function captureSuratCanvas() {
            const el = document.getElementById('surat-dokumen');
            return await html2canvas(el, {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff'
            });
        }

        async function unduhGambar() {
            const btn = document.querySelector('.btn-img');
            const oldHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyiapkan...';
            btn.disabled = true;

            try {
                const canvas = await captureSuratCanvas();
                const dataUrl = canvas.toDataURL('image/png');
                const a = document.createElement('a');
                a.href = dataUrl;
                a.download = 'Surat_RekamMedis_<?php echo preg_replace('/[^a-zA-Z0-9_-]/', '_', $nama_pasien); ?>.png';
                a.click();
            } catch (err) {
                alert('Gagal mengunduh gambar: ' + err.message);
            } finally {
                btn.innerHTML = oldHtml;
                btn.disabled = false;
            }
        }

        async function bagikanWhatsAppGambar() {
            var phone = '<?php echo $wa_number; ?>';
            if (!phone) {
                var inputPhone = prompt("Nomor WhatsApp pasien belum tercatat di data sistem.\nSilakan masukkan nomor WhatsApp pasien (contoh: 08123456789):");
                if (!inputPhone) return;
                phone = inputPhone.replace(/[^0-9]/g, '');
                if (phone.charAt(0) === '0') {
                    phone = '62' + phone.substring(1);
                } else if (!phone.startsWith('62')) {
                    phone = '62' + phone;
                }
            }

            const btn = document.querySelector('.btn-wa');
            const oldHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses Gambar...';
            btn.disabled = true;

            try {
                const canvas = await captureSuratCanvas();
                const dataUrl = canvas.toDataURL('image/png');

                // 1. Salin Gambar ke Clipboard (Bisa langsung di-Paste / Ctrl+V di WhatsApp Web)
                if (navigator.clipboard && window.ClipboardItem) {
                    try {
                        canvas.toBlob(async function(blob) {
                            if (blob) {
                                await navigator.clipboard.write([
                                    new ClipboardItem({ 'image/png': blob })
                                ]);
                            }
                        }, 'image/png');
                    } catch (clipErr) {
                        console.log('Clipboard write note: ', clipErr);
                    }
                }

                // 2. Unduh otomatis file gambar agar pengguna memiliki file PNG-nya
                const dlLink = document.createElement('a');
                dlLink.href = dataUrl;
                dlLink.download = 'Surat_RekamMedis_<?php echo preg_replace('/[^a-zA-Z0-9_-]/', '_', $nama_pasien); ?>.png';
                dlLink.click();

                // 3. Kirim via backend jika Fonnte Gateway aktif
                let sentViaFonnte = false;
                try {
                    const formData = new FormData();
                    formData.append('image_base64', dataUrl);
                    formData.append('nomor_wa', phone);
                    formData.append('nama_pasien', <?php echo json_encode($nama_pasien); ?>);
                    formData.append('pesan', <?php echo json_encode($pesan_wa); ?>);

                    const resp = await fetch('kirim_wa_gambar.php', {
                        method: 'POST',
                        body: formData
                    });
                    const resJson = await resp.json();
                    if (resJson && resJson.sent_via_fonnte) {
                        sentViaFonnte = true;
                    }
                } catch (fetchErr) {
                    console.log('Fonnte send note:', fetchErr);
                }

                btn.innerHTML = oldHtml;
                btn.disabled = false;

                if (sentViaFonnte) {
                    alert('✅ Gambar Surat Rekam Medis berhasil dikirim langsung ke WhatsApp pasien via Fonnte Gateway!');
                } else {
                    const textMsg = <?php echo json_encode($pesan_wa); ?>;
                    const waUrl = "https://api.whatsapp.com/send?phone=" + encodeURIComponent(phone) + "&text=" + encodeURIComponent(textMsg);
                    window.open(waUrl, '_blank');

                    alert('📸 GAMBAR REKAM MEDIS BERHASIL DIBUAT!\n\n1. Gambar telah otomatis diunduh & disalin ke clipboard.\n2. Di chat WhatsApp Web yang terbuka, tekan Ctrl + V (Tempel) untuk mengirim gambar ke pasien.');
                }
            } catch (err) {
                btn.innerHTML = oldHtml;
                btn.disabled = false;
                alert('Terjadi kesalahan saat memproses gambar: ' + err.message);
            }
        }
    </script>
</body>

</html>