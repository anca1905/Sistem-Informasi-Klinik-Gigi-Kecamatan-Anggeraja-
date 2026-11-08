<?php
include 'config/koneksi.php';

$hari_ini = date('Y-m-d');

// Hitung estimasi nomor antrian berikutnya hari ini
$q_estimasi = mysqli_query($koneksi, "SELECT COALESCE(MAX(no_antrian), 0) as max_no FROM antrian WHERE DATE(waktu_daftar) = '$hari_ini'");
$d_estimasi = mysqli_fetch_assoc($q_estimasi);
$estimasi_antrian = ((int)$d_estimasi['max_no']) + 1;

// Variabel notifikasi
$notif_sukses = false;
$notif_gagal = false;
$notif_duplikat = false;
$pesan_error = '';
$nomor_antrian_final = 0;
$id_terbaru = 0;
$duplikat_data = null;

// Ambil daftar dokter untuk dropdown
$query_dokter = mysqli_query($koneksi, "
    SELECT d.id_dokter, d.nama_dokter, j.id AS id_jadwal, j.hari, j.jam_mulai, j.jam_selesai
    FROM dokter d
    JOIN jadwal_dokter j ON j.id_dokter = d.id_dokter
    WHERE j.status = 'Praktek'
    ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), d.nama_dokter
");

// PROSES SUBMIT FORM
if (isset($_POST['simpan'])) {
    $tipe_pasien = isset($_POST['tipe_pasien']) ? $_POST['tipe_pasien'] : 'lama'; // 'lama' (kontrol) atau 'baru'
    $id_jadwal   = (int)$_POST['id_jadwal'];
    $keluhan     = mysqli_real_escape_string($koneksi, trim($_POST['keluhan']));

    if (empty($id_jadwal) || empty($keluhan)) {
        $notif_gagal = true;
        $pesan_error = 'Jadwal dokter dan keluhan wajib diisi!';
    } else {
        // Ambil id_dokter dari jadwal yang dipilih
        $q_jdwl = mysqli_query($koneksi, "SELECT j.id_dokter FROM jadwal_dokter j WHERE j.id = $id_jadwal");
        $row_jdwl = mysqli_fetch_assoc($q_jdwl);
        $id_dokter = $row_jdwl ? (int)$row_jdwl['id_dokter'] : 'NULL';

        $id_pasien = 0;
        $nama_pasien = '';
        $telp_pasien = '';

        if ($tipe_pasien === 'lama') {
            // === PASIEN LAMA / KONTROL ===
            // Sisa nomor rekam medisnya dimasukkan terus langsung dapat nomor antrian!
            $no_rm = mysqli_real_escape_string($koneksi, trim($_POST['no_rm']));
            $id_pasien_input = (int)($_POST['id_pasien_hidden'] ?? 0);

            if ($id_pasien_input > 0) {
                $q_pasien = mysqli_query($koneksi, "SELECT * FROM pasien WHERE id = $id_pasien_input LIMIT 1");
            } else {
                $q_pasien = mysqli_query($koneksi, "
                    SELECT * FROM pasien 
                    WHERE kode_pasien = '$no_rm' 
                       OR id = '$no_rm' 
                       OR nik = '$no_rm' 
                       OR no_telepon = '$no_rm'
                    LIMIT 1
                ");
            }

            if ($q_pasien && mysqli_num_rows($q_pasien) > 0) {
                $d_pasien = mysqli_fetch_assoc($q_pasien);
                $id_pasien   = (int)$d_pasien['id'];
                $nama_pasien = $d_pasien['nama'];
                $telp_pasien = $d_pasien['no_telepon'];
            } else {
                $notif_gagal = true;
                $pesan_error = "Nomor Rekam Medis ($no_rm) tidak ditemukan! Pastikan nomor RM benar atau pilih 'Pasien Baru'.";
            }
        } else {
            // === PASIEN BARU ===
            $nama_pasien = mysqli_real_escape_string($koneksi, trim($_POST['nama']));
            $tgl_l       = mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir']);
            $jenis       = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin']);
            $telp_pasien = mysqli_real_escape_string($koneksi, trim($_POST['no_telepon']));
            $nik         = mysqli_real_escape_string($koneksi, trim($_POST['nik'] ?? ''));
            $alamat      = mysqli_real_escape_string($koneksi, trim($_POST['alamat'] ?? ''));

            if (empty($nama_pasien) || empty($tgl_l) || empty($jenis)) {
                $notif_gagal = true;
                $pesan_error = 'Nama, Tanggal Lahir, dan Jenis Kelamin wajib diisi untuk pasien baru!';
            } else {
                // Cek apakah sebenarnya sudah terdaftar
                $cek = mysqli_query($koneksi, "SELECT id, nama, no_telepon FROM pasien WHERE nama='$nama_pasien' AND tanggal_lahir='$tgl_l' LIMIT 1");
                if (mysqli_num_rows($cek) > 0) {
                    $pasien_lama = mysqli_fetch_assoc($cek);
                    $id_pasien = (int)$pasien_lama['id'];
                } else {
                    $total_pasien = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM pasien")) + 1;
                    $kode_rm = 'RM' . str_pad($total_pasien, 4, '0', STR_PAD_LEFT);
                    mysqli_query($koneksi, "INSERT INTO pasien (kode_pasien, nama, tanggal_lahir, jenis_kelamin, no_telepon, nik, alamat, password) 
                        VALUES ('$kode_rm', '$nama_pasien', '$tgl_l', '$jenis', '$telp_pasien', '$nik', '$alamat', '123456')");
                    $id_pasien = mysqli_insert_id($koneksi);
                }
            }
        }

        // Jika data pasien valid
        if ($id_pasien > 0 && !$notif_gagal) {
            // 1. CEK REDUNDANSI NOMOR ANTRIAN
            // Jika pasien sudah mengambil antrian hari ini yang masih 'Menunggu' atau 'Dilayani', jangan buat dobel!
            $cek_antrian_aktif = mysqli_query($koneksi, "
                SELECT a.*, d.nama_dokter 
                FROM antrian a 
                LEFT JOIN dokter d ON a.id_dokter = d.id_dokter
                WHERE a.id_pasien = $id_pasien 
                  AND DATE(a.waktu_daftar) = '$hari_ini' 
                  AND a.status IN ('Menunggu', 'Dilayani')
                LIMIT 1
            ");

            if (mysqli_num_rows($cek_antrian_aktif) > 0) {
                // Sudah ada antrian aktif hari ini -> cegah redundansi!
                $duplikat_data = mysqli_fetch_assoc($cek_antrian_aktif);
                $notif_duplikat = true;
            } else {
                // 2. HITUNG NOMOR ANTRIAN HARI INI SECARA ATOMIK
                mysqli_query($koneksi, "START TRANSACTION");

                $q_max = mysqli_query($koneksi, "SELECT COALESCE(MAX(no_antrian), 0) + 1 AS next_antrian FROM antrian WHERE DATE(waktu_daftar) = '$hari_ini' FOR UPDATE");
                $d_max = mysqli_fetch_assoc($q_max);
                $nomor_antrian_final = (int)$d_max['next_antrian'];

                $safe_nama = mysqli_real_escape_string($koneksi, $nama_pasien);
                $simpan = mysqli_query($koneksi, "
                    INSERT INTO antrian (id_pasien, id_jadwal, id_dokter, no_antrian, nama_pendaftar, keluhan, status, waktu_daftar) 
                    VALUES ($id_pasien, $id_jadwal, $id_dokter, $nomor_antrian_final, '$safe_nama', '$keluhan', 'Menunggu', NOW())
                ");

                if ($simpan) {
                    $id_terbaru = mysqli_insert_id($koneksi);
                    mysqli_query($koneksi, "COMMIT");
                    $notif_sukses = true;

                    // Kirim notifikasi WhatsApp via Fonnte jika nomor terisi
                    if (!empty($telp_pasien)) {
                        $q_dok = mysqli_query($koneksi, "SELECT nama_dokter FROM dokter WHERE id_dokter='$id_dokter' LIMIT 1");
                        $d_dok = mysqli_fetch_assoc($q_dok);
                        $nama_dokter = $d_dok ? $d_dok['nama_dokter'] : 'Dokter';

                        $msg_antrian = "Halo *$nama_pasien*,\n\n" .
                                       "Pendaftaran antrian Anda di *Klinik Gigi Anggeraja* berhasil!\n\n" .
                                       "📌 *Nomor Antrian:* #$nomor_antrian_final\n" .
                                       "📅 *Tanggal:* " . date('d-m-Y') . "\n" .
                                       "👨‍⚕️ *Dokter:* $nama_dokter\n" .
                                       "📝 *Keluhan/Tujuan:* $keluhan\n\n" .
                                       "Silakan datang ke klinik sebelum nomor antrian Anda dipanggil. Terima kasih! 🙏";
                        @sendWA($telp_pasien, $msg_antrian);
                    }
                } else {
                    mysqli_query($koneksi, "ROLLBACK");
                    $notif_gagal = true;
                    $pesan_error = 'Terjadi kesalahan sistem saat menyimpan antrian: ' . mysqli_error($koneksi);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ambil Antrian - Klinik Gigi Anggeraja</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --hijau-tua: #059669;
            --hijau-gelap: #064e3b;
            --hijau-muda: #34d399;
            --hijau-bg: #f0fdf4;
            --putih-tulang: #f9fafb;
            --teks-gelap: #1f2937;
            --border-color: #e5e7eb;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f3f4f6;
            color: var(--teks-gelap);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 25px 15px;
        }

        .container {
            background: white;
            width: 100%;
            max-width: 580px;
            padding: 35px 30px;
            border-radius: 24px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
            border-top: 8px solid var(--hijau-tua);
            position: relative;
        }

        .header-box {
            text-align: center;
            margin-bottom: 25px;
        }

        .header-box h2 {
            color: var(--hijau-gelap);
            margin: 0 0 5px 0;
            font-size: 1.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        p.subtitle {
            color: #6b7280;
            margin: 0;
            font-size: 0.95rem;
        }

        /* Estimasi Antrian Box */
        .nomor-box {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: var(--hijau-gelap);
            padding: 16px;
            border-radius: 16px;
            margin-bottom: 25px;
            border: 2px dashed var(--hijau-tua);
            text-align: center;
        }

        .nomor-box span {
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .nomor-box h1 {
            font-size: 48px;
            margin: 5px 0 0 0;
            line-height: 1;
            font-weight: 800;
        }

        /* TAB PILIHAN PASIEN (KONTROL vs BARU) */
        .tab-switcher {
            display: flex;
            background: #f3f4f6;
            padding: 5px;
            border-radius: 14px;
            margin-bottom: 25px;
            gap: 6px;
        }

        .tab-btn {
            flex: 1;
            padding: 12px 10px;
            border: none;
            background: transparent;
            font-family: inherit;
            font-size: 0.92rem;
            font-weight: 600;
            color: #6b7280;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .tab-btn.active {
            background: white;
            color: var(--hijau-tua);
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.15);
        }

        .tab-btn:hover:not(.active) {
            color: #374151;
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 18px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
            font-size: 14px;
            color: #374151;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 15px;
            transition: 0.2s;
            font-family: inherit;
            background: white;
            color: #1f2937;
        }

        input:focus,
        textarea:focus,
        select:focus {
            border-color: var(--hijau-tua);
            outline: none;
            background: #f0fdf4;
            box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1);
        }

        /* Search Box Pasien Lama */
        .rm-search-group {
            display: flex;
            gap: 8px;
        }

        .btn-cari-rm {
            background: var(--hijau-tua);
            color: white;
            border: none;
            padding: 0 18px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: 0.2s;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-cari-rm:hover {
            background: #047857;
        }

        /* Kartu Info Pasien Terpilih */
        .patient-card {
            background: #ecfdf5;
            border: 1.5px solid #a7f3d0;
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 20px;
            display: none;
            text-align: left;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .patient-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px dashed #a7f3d0;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .patient-card-title {
            font-weight: 700;
            color: var(--hijau-gelap);
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .patient-card-rm {
            background: var(--hijau-tua);
            color: white;
            font-size: 12px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .patient-grid-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            font-size: 13px;
        }

        .patient-grid-item span {
            color: #6b7280;
            display: block;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .patient-grid-item strong {
            color: #111827;
        }

        .kontrol-badge-alert {
            margin-top: 10px;
            padding: 10px 12px;
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            border-radius: 6px;
            font-size: 12.5px;
            color: #1e40af;
        }

        .btn-submit {
            background: var(--hijau-tua);
            color: white;
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 50px;
            font-size: 17px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(5, 150, 105, 0.25);
        }

        .btn-submit:hover {
            background: #047857;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(5, 150, 105, 0.35);
        }

        .links-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #f3f4f6;
            font-size: 13.5px;
        }

        .links-bottom a {
            text-decoration: none;
            color: #6b7280;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .links-bottom a:hover {
            color: var(--hijau-tua);
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="header-box">
            <h2><i class="fas fa-tooth"></i> Ambil Antrian</h2>
            <p class="subtitle">Klinik Gigi Kecamatan Anggeraja</p>
        </div>

        <div class="nomor-box">
            <span>Nomor Antrian Berikutnya Hari Ini:</span>
            <h1>#<?php echo $estimasi_antrian; ?></h1>
        </div>

        <!-- TAB SWITCHER: KONTROL (PASIEN LAMA) VS PASIEN BARU -->
        <div class="tab-switcher">
            <button type="button" class="tab-btn active" id="tabLamaBtn" onclick="switchTab('lama')">
                <i class="fas fa-id-card"></i> Pasien Lama / Kontrol
            </button>
            <button type="button" class="tab-btn" id="tabBaruBtn" onclick="switchTab('baru')">
                <i class="fas fa-user-plus"></i> Pasien Baru
            </button>
        </div>

        <form method="POST" id="formAntrian">
            <input type="hidden" name="tipe_pasien" id="tipePasienInput" value="lama">
            <input type="hidden" name="id_pasien_hidden" id="idPasienHidden" value="">

            <!-- BAGIAN PASIEN LAMA / KONTROL -->
            <div id="sectionPasienLama">
                <div class="form-group">
                    <label>Nomor Rekam Medis (No. RM) Pasien <span style="color:red;">*</span></label>
                    <div class="rm-search-group">
                        <input type="text" name="no_rm" id="inputNoRm" placeholder="Contoh: RM0008 atau No. HP / NIK" autocomplete="off">
                        <button type="button" class="btn-cari-rm" onclick="cariPasienByRm()">
                            <i class="fas fa-search"></i> Cek Data
                        </button>
                    </div>
                    <small style="color:#6b7280; font-size:12px; margin-top:4px; display:block;">
                        <i class="fas fa-info-circle"></i> Pasien kontrol tidak perlu mengisi formulir ulang. Cukup masukkan No. RM Anda.
                    </small>
                </div>

                <!-- KARTU DATA PASIEN YANG DITEMUKAN -->
                <div class="patient-card" id="patientFoundCard">
                    <div class="patient-card-header">
                        <div class="patient-card-title">
                            <i class="fas fa-user-check"></i> <span id="cardNamaPasien">-</span>
                        </div>
                        <span class="patient-card-rm" id="cardNoRm">-</span>
                    </div>
                    <div class="patient-grid-info">
                        <div class="patient-grid-item">
                            <span>Tanggal Lahir:</span>
                            <strong id="cardTglLahir">-</strong>
                        </div>
                        <div class="patient-grid-item">
                            <span>Jenis Kelamin:</span>
                            <strong id="cardJenisKelamin">-</strong>
                        </div>
                        <div class="patient-grid-item">
                            <span>No. Telepon / WA:</span>
                            <strong id="cardNoTelepon">-</strong>
                        </div>
                        <div class="patient-grid-item">
                            <span>NIK:</span>
                            <strong id="cardNik">-</strong>
                        </div>
                    </div>
                    <!-- Info Kunjungan & Kontrol Terakhir jika ada -->
                    <div id="cardKontrolAlert" class="kontrol-badge-alert" style="display:none;"></div>
                </div>
            </div>

            <!-- BAGIAN PASIEN BARU (Disembunyikan jika mode kontrol) -->
            <div id="sectionPasienBaru" style="display: none;">
                <div class="form-group">
                    <label>Nama Lengkap Pasien <span style="color:red;">*</span></label>
                    <input type="text" name="nama" id="inputNamaBaru" placeholder="Contoh: Siti Fatimah" autocomplete="off">
                </div>

                <div class="form-group" style="display: flex; gap: 12px;">
                    <div style="flex: 1;">
                        <label>Tanggal Lahir <span style="color:red;">*</span></label>
                        <input type="date" name="tanggal_lahir" id="inputTglLahirBaru">
                    </div>
                    <div style="flex: 1;">
                        <label>Jenis Kelamin <span style="color:red;">*</span></label>
                        <select name="jenis_kelamin" id="selectGenderBaru">
                            <option value="">-- Pilih --</option>
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="display: flex; gap: 12px;">
                    <div style="flex: 1;">
                        <label>No. HP / WhatsApp</label>
                        <input type="text" name="no_telepon" id="inputTelpBaru" placeholder="08xxxxxxxxxx" autocomplete="off">
                    </div>
                    <div style="flex: 1;">
                        <label>NIK (Opsional)</label>
                        <input type="text" name="nik" placeholder="16 digit NIK" autocomplete="off">
                    </div>
                </div>

                <div class="form-group">
                    <label>Alamat Lengkap (Opsional)</label>
                    <textarea name="alamat" rows="2" placeholder="Contoh: Jl. Anggeraja No. 12..."></textarea>
                </div>
            </div>

            <!-- BAGIAN UMUM: PILIH DOKTER & KELUHAN -->
            <div class="form-group">
                <label>Pilih Dokter Gigi & Jadwal Praktik <span style="color:red;">*</span></label>
                <select name="id_jadwal" id="selectJadwal" required>
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
                <label>Keluhan / Tujuan Kunjungan <span style="color:red;">*</span></label>
                <textarea name="keluhan" id="inputKeluhan" rows="3" placeholder="Contoh: Kontrol rutin behel / cabut gigi / penambalan gigi..." required></textarea>
            </div>

            <button type="submit" name="simpan" class="btn-submit" id="btnSubmitAntrian">
                <i class="fas fa-ticket-alt"></i> Ambil Nomor Antrian
            </button>
        </form>

        <div class="links-bottom">
            <a href="index.php"><i class="fas fa-arrow-left"></i> Beranda</a>
            <a href="cek_antrian.php"><i class="fas fa-search"></i> Cek Giliran Antrian</a>
            <a href="auth/login.php"><i class="fas fa-user-lock"></i> Portal</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function switchTab(type) {
            const tabLamaBtn = document.getElementById('tabLamaBtn');
            const tabBaruBtn = document.getElementById('tabBaruBtn');
            const sectionLama = document.getElementById('sectionPasienLama');
            const sectionBaru = document.getElementById('sectionPasienBaru');
            const tipePasienInput = document.getElementById('tipePasienInput');
            const inputNoRm = document.getElementById('inputNoRm');
            const inputNamaBaru = document.getElementById('inputNamaBaru');
            const inputTglLahirBaru = document.getElementById('inputTglLahirBaru');
            const selectGenderBaru = document.getElementById('selectGenderBaru');
            const inputKeluhan = document.getElementById('inputKeluhan');

            if (type === 'lama') {
                tabLamaBtn.classList.add('active');
                tabBaruBtn.classList.remove('active');
                sectionLama.style.display = 'block';
                sectionBaru.style.display = 'none';
                tipePasienInput.value = 'lama';

                inputNoRm.setAttribute('required', 'required');
                inputNamaBaru.removeAttribute('required');
                inputTglLahirBaru.removeAttribute('required');
                selectGenderBaru.removeAttribute('required');

                if (!inputKeluhan.value || inputKeluhan.value.includes('Pemeriksaan baru')) {
                    inputKeluhan.value = 'Kontrol rutin pasca perawatan gigi';
                }
            } else {
                tabBaruBtn.classList.add('active');
                tabLamaBtn.classList.remove('active');
                sectionLama.style.display = 'none';
                sectionBaru.style.display = 'block';
                tipePasienInput.value = 'baru';

                inputNoRm.removeAttribute('required');
                inputNamaBaru.setAttribute('required', 'required');
                inputTglLahirBaru.setAttribute('required', 'required');
                selectGenderBaru.setAttribute('required', 'required');

                if (inputKeluhan.value === 'Kontrol rutin pasca perawatan gigi') {
                    inputKeluhan.value = '';
                }
            }
        }

        // Jalankan default saat dimuat
        switchTab('lama');

        // Pencarian Pasien via AJAX
        let timeoutRm = null;
        const inputNoRmElem = document.getElementById('inputNoRm');

        inputNoRmElem.addEventListener('input', function() {
            clearTimeout(timeoutRm);
            const val = this.value.trim();
            if (val.length >= 2) {
                timeoutRm = setTimeout(cariPasienByRm, 400);
            }
        });

        inputNoRmElem.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                cariPasienByRm();
            }
        });

        function cariPasienByRm() {
            const query = document.getElementById('inputNoRm').value.trim();
            if (!query) {
                Swal.fire({
                    title: 'Perhatian',
                    text: 'Silakan ketikkan Nomor Rekam Medis (No. RM), NIK, atau No. HP Anda terlebih dahulu.',
                    icon: 'warning',
                    confirmButtonColor: '#059669'
                });
                return;
            }

            fetch('api/cari_pasien.php?q=' + encodeURIComponent(query))
                .then(res => res.json())
                .then(data => {
                    const card = document.getElementById('patientFoundCard');
                    const cardKontrolAlert = document.getElementById('cardKontrolAlert');
                    const idHidden = document.getElementById('idPasienHidden');

                    if (data.status === 'success' && data.data) {
                        const p = data.data;
                        idHidden.value = p.id;
                        document.getElementById('cardNamaPasien').textContent = p.nama;
                        document.getElementById('cardNoRm').textContent = p.kode_pasien;
                        document.getElementById('cardTglLahir').textContent = p.tanggal_lahir_formatted;
                        document.getElementById('cardJenisKelamin').textContent = p.jenis_kelamin;
                        document.getElementById('cardNoTelepon').textContent = p.no_telepon || '-';
                        document.getElementById('cardNik').textContent = p.nik || '-';

                        // Cek apakah punya antrian aktif hari ini
                        if (p.active_queue) {
                            Swal.fire({
                                title: 'Antrian Sudah Aktif!',
                                html: `Pasien <b>${p.nama}</b> sudah memiliki nomor antrian aktif untuk hari ini:<br><br>` +
                                      `<span style="font-size:2rem;font-weight:bold;color:#059669;">#${p.active_queue.no_antrian}</span><br>` +
                                      `Status: <b>${p.active_queue.status}</b> | Dokter: <b>${p.active_queue.dokter}</b><br><br>` +
                                      `Tidak perlu mengambil antrian ganda. Anda dapat langsung mencetak struk atau memantau panggilan.`,
                                icon: 'info',
                                showCancelButton: true,
                                confirmButtonText: 'Cetak Struk Antrian',
                                cancelButtonText: 'Tutup',
                                confirmButtonColor: '#059669'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location = 'cetak_struk.php?id=' + p.active_queue.id;
                                }
                            });
                        }

                        // Cek jika ada jadwal kontrol berikutnya
                        if (p.last_visit && p.last_visit.kunjungan_berikutnya) {
                            cardKontrolAlert.style.display = 'block';
                            cardKontrolAlert.innerHTML = `<strong><i class="fas fa-calendar-check"></i> Jadwal Kontrol Ditemukan:</strong><br>` +
                                `Tanggal: <b>${p.last_visit.kunjungan_berikutnya}</b> | Dokter: <b>${p.last_visit.dokter}</b><br>` +
                                `Diagnosa Terakhir: <em>${p.last_visit.diagnosa}</em>`;
                            
                            document.getElementById('inputKeluhan').value = `Kontrol lanjutan sesuai anjuran drg (Diagnosa: ${p.last_visit.diagnosa})`;
                        } else {
                            cardKontrolAlert.style.display = 'none';
                        }

                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                        idHidden.value = '';
                        Swal.fire({
                            title: 'Pasien Tidak Ditemukan',
                            text: `Tidak ada data pasien dengan No. RM / No. HP "${query}". Jika Anda belum pernah berobat sebelumnya, silakan pilih tab 'Pasien Baru'.`,
                            icon: 'error',
                            confirmButtonColor: '#059669',
                            confirmButtonText: 'Pilih Pasien Baru',
                            showCancelButton: true,
                            cancelButtonText: 'Periksa Lagi'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                switchTab('baru');
                            }
                        });
                    }
                })
                .catch(err => {
                    console.error('Error lookup pasien:', err);
                });
        }

        // VALIDASI SUBMIT
        document.getElementById('formAntrian').addEventListener('submit', function(e) {
            const tipe = document.getElementById('tipePasienInput').value;
            if (tipe === 'lama') {
                const noRm = document.getElementById('inputNoRm').value.trim();
                if (!noRm) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Nomor RM Wajib Diisi',
                        text: 'Silakan masukkan Nomor Rekam Medis (No. RM) Anda untuk mengambil antrian kontrol.',
                        icon: 'warning',
                        confirmButtonColor: '#059669'
                    });
                }
            }
        });

        // SWEETALERT NOTIFIKASI
        <?php if ($notif_sukses) { ?>
            Swal.fire({
                title: 'Antrian Berhasil Diambil!',
                html: 'Nomor Antrian Kamu Hari Ini:<br><span style="font-size:3rem; font-weight:800; color:#059669;">#<?php echo $nomor_antrian_final; ?></span><br>Silakan simpan atau cetak nomor antrian Anda.',
                icon: 'success',
                confirmButtonText: '<i class="fas fa-print"></i> Cetak Kartu Antrian',
                confirmButtonColor: '#059669',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location = 'cetak_struk.php?id=<?php echo $id_terbaru; ?>';
                }
            });
        <?php } ?>

        <?php if ($notif_duplikat && $duplikat_data) { ?>
            Swal.fire({
                title: 'Nomor Antrian Sudah Aktif!',
                html: 'Anda sudah terdaftar dalam antrian hari ini dengan nomor:<br>' +
                      '<span style="font-size:3rem; font-weight:800; color:#059669;">#<?php echo $duplikat_data['no_antrian']; ?></span><br>' +
                      'Status: <b><?php echo $duplikat_data['status']; ?></b><br>' +
                      'Untuk menghindari redundansi nomor antrian, satu pasien hanya diperkenankan memiliki satu antrian aktif.',
                icon: 'info',
                confirmButtonText: '<i class="fas fa-print"></i> Cetak Ulang Struk',
                confirmButtonColor: '#059669',
                showCancelButton: true,
                cancelButtonText: 'Tutup'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location = 'cetak_struk.php?id=<?php echo $duplikat_data['id']; ?>';
                }
            });
        <?php } ?>

        <?php if ($notif_gagal) { ?>
            Swal.fire({
                title: 'Pendaftaran Gagal',
                text: '<?php echo addslashes($pesan_error ?: 'Terjadi kesalahan sistem, silakan coba lagi.'); ?>',
                icon: 'error',
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#ef4444'
            });
        <?php } ?>
    </script>
</body>

</html>
