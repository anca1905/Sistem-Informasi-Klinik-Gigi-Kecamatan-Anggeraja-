<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../auth/login.php");
    exit();
}

if ($_SESSION['role'] != 'Admin') {
    header("location:dashboard.php");
    exit();
}

$nama_petugas = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : $_SESSION['login_user'];
$pesan = '';
$pesan_type = '';
$id_antrian_baru = 0;

// Ambil daftar dokter untuk dropdown
$query_dokter = mysqli_query($koneksi, "
    SELECT d.id_dokter, d.nama_dokter, j.id AS id_jadwal, j.hari, j.jam_mulai, j.jam_selesai
    FROM dokter d
    JOIN jadwal_dokter j ON j.id_dokter = d.id_dokter
    WHERE j.status = 'Praktek'
    ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), d.nama_dokter
");

// Ambil daftar 50 pasien terakhir untuk datalist / dropdown cepat
$query_pasien_list = mysqli_query($koneksi, "SELECT id, kode_pasien, nama, no_telepon FROM pasien ORDER BY id DESC LIMIT 100");

// Proses Form Pendaftaran
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tipe_pasien = isset($_POST['tipe_pasien']) ? $_POST['tipe_pasien'] : 'lama';
    $id_jadwal   = (int)$_POST['id_jadwal'];
    $keluhan     = mysqli_real_escape_string($koneksi, trim($_POST['keluhan']));

    if (empty($id_jadwal) || empty($keluhan)) {
        $pesan = 'Jadwal dokter dan keluhan wajib diisi!';
        $pesan_type = 'error';
    } else {
        // Ambil id_dokter dari jadwal yang dipilih
        $q_jdwl = mysqli_query($koneksi, "SELECT j.id_dokter FROM jadwal_dokter j WHERE j.id = $id_jadwal");
        $row_jdwl = mysqli_fetch_assoc($q_jdwl);
        $id_dokter = $row_jdwl ? (int)$row_jdwl['id_dokter'] : null;

        $id_pasien = 0;
        $nama_pasien = '';
        $telp_pasien = '';

        if ($tipe_pasien === 'lama') {
            // === PASIEN LAMA / KONTROL ===
            $no_rm = mysqli_real_escape_string($koneksi, trim($_POST['no_rm']));
            $id_pasien_hidden = (int)($_POST['id_pasien_hidden'] ?? 0);

            if ($id_pasien_hidden > 0) {
                $q_pasien = mysqli_query($koneksi, "SELECT * FROM pasien WHERE id = $id_pasien_hidden LIMIT 1");
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
                $pesan = "Nomor Rekam Medis ($no_rm) tidak ditemukan! Silakan gunakan form Pasien Baru jika belum terdaftar.";
                $pesan_type = 'error';
            }
        } else {
            // === PASIEN BARU ===
            $nama_pasien = mysqli_real_escape_string($koneksi, trim($_POST['nama_pasien']));
            $tgl_l       = mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir']);
            $jenis       = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin']);
            $telp_pasien = mysqli_real_escape_string($koneksi, trim($_POST['no_telepon']));
            $nik         = mysqli_real_escape_string($koneksi, trim($_POST['nik'] ?? ''));
            $alamat      = mysqli_real_escape_string($koneksi, trim($_POST['alamat'] ?? ''));

            if (empty($nama_pasien) || empty($tgl_l) || empty($jenis)) {
                $pesan = 'Nama, Tanggal Lahir, dan Jenis Kelamin wajib diisi!';
                $pesan_type = 'error';
            } else {
                // Cek apakah data pasien sudah ada
                $cek = mysqli_query($koneksi, "SELECT id, kode_pasien FROM pasien WHERE nama='$nama_pasien' AND tanggal_lahir='$tgl_l' LIMIT 1");
                if (mysqli_num_rows($cek) > 0) {
                    $pasien_lama = mysqli_fetch_assoc($cek);
                    $id_pasien = (int)$pasien_lama['id'];
                } else {
                    $total_pasien = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM pasien")) + 1;
                    $kode_rm = 'RM' . str_pad($total_pasien, 4, '0', STR_PAD_LEFT);
                    mysqli_query($koneksi, "
                        INSERT INTO pasien (kode_pasien, nama, tanggal_lahir, jenis_kelamin, no_telepon, nik, alamat, password) 
                        VALUES ('$kode_rm', '$nama_pasien', '$tgl_l', '$jenis', '$telp_pasien', '$nik', '$alamat', '123456')
                    ");
                    $id_pasien = mysqli_insert_id($koneksi);
                }
            }
        }

        // Lanjutkan jika pasien valid
        if ($id_pasien > 0 && $pesan_type != 'error') {
            $hari_ini = date('Y-m-d');

            // 1. CEK REDUNDANSI NOMOR ANTRIAN
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
                $dup = mysqli_fetch_assoc($cek_antrian_aktif);
                $id_antrian_baru = $dup['id'];
                $pesan = "Pasien <strong>" . htmlspecialchars($nama_pasien) . "</strong> sudah memiliki antrian aktif hari ini dengan No. Antrian <strong>#" . $dup['no_antrian'] . "</strong> (Status: " . $dup['status'] . "). Tidak membuat duplikat antrian!";
                $pesan_type = 'warning';
            } else {
                // 2. HITUNG NOMOR ANTRIAN SECARA ATOMIK DENGAN TRANSACTION
                mysqli_query($koneksi, "START TRANSACTION");

                $q_max = mysqli_query($koneksi, "SELECT COALESCE(MAX(no_antrian), 0) + 1 AS next_antrian FROM antrian WHERE DATE(waktu_daftar) = '$hari_ini' FOR UPDATE");
                $d_max = mysqli_fetch_assoc($q_max);
                $no_antrian = (int)$d_max['next_antrian'];

                $id_dokter_val = $id_dokter ? $id_dokter : 'NULL';
                $safe_nama = mysqli_real_escape_string($koneksi, $nama_pasien);

                $insert = mysqli_query($koneksi, "
                    INSERT INTO antrian (id_pasien, id_jadwal, id_dokter, no_antrian, nama_pendaftar, keluhan, status, waktu_daftar)
                    VALUES ($id_pasien, $id_jadwal, $id_dokter_val, $no_antrian, '$safe_nama', '$keluhan', 'Menunggu', NOW())
                ");

                if ($insert) {
                    $id_antrian_baru = mysqli_insert_id($koneksi);
                    mysqli_query($koneksi, "COMMIT");

                    $pesan = "Pendaftaran berhasil! No. Antrian: <strong>#$no_antrian</strong> untuk pasien <strong>" . htmlspecialchars($nama_pasien) . "</strong>";
                    $pesan_type = 'success';

                    // Kirim notifikasi WhatsApp via Fonnte jika nomor terisi
                    if (!empty($telp_pasien)) {
                        $q_dok = mysqli_query($koneksi, "SELECT nama_dokter FROM dokter WHERE id_dokter='$id_dokter' LIMIT 1");
                        $d_dok = mysqli_fetch_assoc($q_dok);
                        $nama_dokter = $d_dok ? $d_dok['nama_dokter'] : 'Dokter';

                        $msg_antrian = "Halo *$nama_pasien*,\n\n" .
                                       "Pendaftaran antrian Anda di *Klinik Gigi Anggeraja* berhasil!\n\n" .
                                       "📌 *Nomor Antrian:* #$no_antrian\n" .
                                       "📅 *Tanggal:* " . date('d-m-Y') . "\n" .
                                       "👨‍⚕️ *Dokter:* $nama_dokter\n\n" .
                                       "Silakan menunggu di ruang tunggu klinik hingga nomor Anda dipanggil. Terima kasih! 🙏";
                        @sendWA($telp_pasien, $msg_antrian);
                    }
                } else {
                    mysqli_query($koneksi, "ROLLBACK");
                    $pesan = 'Terjadi kesalahan sistem saat menyimpan antrian: ' . mysqli_error($koneksi);
                    $pesan_type = 'error';
                }
            }
        }
    }

    // Refresh dokter dropdown
    $query_dokter = mysqli_query($koneksi, "
        SELECT d.id_dokter, d.nama_dokter, j.id AS id_jadwal, j.hari, j.jam_mulai, j.jam_selesai
        FROM dokter d JOIN jadwal_dokter j ON j.id_dokter = d.id_dokter
        WHERE j.status = 'Praktek' ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), d.nama_dokter
    ");
}
?>
<?php include "../layout/header.php" ?>

<div class="page-header">
    <h2><i class="fas fa-clipboard-list"></i> Pendaftaran Antrian Pasien</h2>
    <p>Daftarkan pasien kontrol (lama) atau pasien baru untuk mendapatkan nomor antrian.</p>
</div>

<?php if ($pesan): ?>
    <div class="alert alert-<?php echo $pesan_type; ?>" style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <i class="fas fa-<?php echo $pesan_type == 'success' ? 'check-circle' : ($pesan_type == 'warning' ? 'exclamation-triangle' : 'times-circle'); ?>"></i>
            <?php echo $pesan; ?>
        </div>
        <?php if ($id_antrian_baru > 0): ?>
            <a href="../cetak_struk.php?id=<?php echo $id_antrian_baru; ?>" target="_blank" class="btn btn-primary" style="font-size:0.85rem; padding:6px 14px;">
                <i class="fas fa-print"></i> Cetak Struk
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- TAB SWITCHER: KONTROL vs PASIEN BARU -->
<div style="display:flex; gap:10px; margin-bottom:20px;">
    <button type="button" class="btn <?php echo (!isset($_POST['tipe_pasien']) || $_POST['tipe_pasien'] == 'lama') ? 'btn-primary' : ''; ?>" id="tabAdminLama" onclick="switchAdminTab('lama')" style="border-radius:10px; font-weight:600; padding:10px 20px;">
        <i class="fas fa-id-card"></i> Pasien Lama / Kontrol (Input No. RM)
    </button>
    <button type="button" class="btn <?php echo (isset($_POST['tipe_pasien']) && $_POST['tipe_pasien'] == 'baru') ? 'btn-primary' : ''; ?>" id="tabAdminBaru" onclick="switchAdminTab('baru')" style="border-radius:10px; font-weight:600; padding:10px 20px; background:#f3f4f6; color:#374151;">
        <i class="fas fa-user-plus"></i> Pasien Baru
    </button>
</div>

<div class="form-card">
    <form method="POST" action="" id="formAdminPendaftaran">
        <input type="hidden" name="tipe_pasien" id="tipePasienAdmin" value="lama">
        <input type="hidden" name="id_pasien_hidden" id="adminIdPasienHidden" value="">

        <!-- SEKSI PASIEN LAMA / KONTROL -->
        <div id="adminSectionLama">
            <div style="background:#f0fdf4; border:1px solid #bbf7d0; padding:16px 20px; border-radius:12px; margin-bottom:20px;">
                <label style="display:block; font-weight:700; color:#065f46; margin-bottom:8px;">
                    <i class="fas fa-search"></i> Cari Pasien Kontrol (Ketik No. RM / Nama / No. HP):
                </label>
                <div style="display:flex; gap:10px;">
                    <input type="text" name="no_rm" id="adminInputRm" list="pasienList" placeholder="Ketik No. RM (contoh: RM0008) atau ketik nama pasien..." style="flex:1; padding:11px 14px; border:2px solid #86efac; border-radius:8px; font-size:15px;" autocomplete="off">
                    <datalist id="pasienList">
                        <?php while ($p_opt = mysqli_fetch_assoc($query_pasien_list)): ?>
                            <option value="<?php echo htmlspecialchars($p_opt['kode_pasien']); ?>"><?php echo htmlspecialchars($p_opt['nama']); ?> (<?php echo htmlspecialchars($p_opt['no_telepon']); ?>)</option>
                        <?php endwhile; ?>
                    </datalist>
                    <button type="button" class="btn btn-primary" onclick="lookupAdminPasien()" style="padding:0 20px;">
                        <i class="fas fa-search"></i> Cek Data
                    </button>
                </div>
                <small style="color:#047857; margin-top:6px; display:block;">
                    <i class="fas fa-check-circle"></i> Pasien kontrol tidak perlu didaftarkan ulang. Cukup masukkan No. RM untuk otomatis memuat data pasien.
                </small>
            </div>

            <!-- KOTAK INFO PASIEN TERPILIH -->
            <div id="adminPatientPreview" style="display:none; background:white; border:2px solid #10b981; border-radius:12px; padding:16px 20px; margin-bottom:20px;">
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e5e7eb; padding-bottom:8px; margin-bottom:12px;">
                    <h4 style="margin:0; color:#064e3b; font-size:1.05rem;">
                        <i class="fas fa-user-check" style="color:#10b981;"></i> Data Pasien Terpilih
                    </h4>
                    <span id="adminPrevRm" style="background:#059669; color:white; font-weight:bold; font-size:0.85rem; padding:3px 10px; border-radius:20px;">-</span>
                </div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px; font-size:0.9rem;">
                    <div><span style="color:#6b7280; font-size:0.8rem; display:block;">NAMA PASIEN</span><strong id="adminPrevNama">-</strong></div>
                    <div><span style="color:#6b7280; font-size:0.8rem; display:block;">TANGGAL LAHIR</span><strong id="adminPrevTgl">-</strong></div>
                    <div><span style="color:#6b7280; font-size:0.8rem; display:block;">JENIS KELAMIN</span><strong id="adminPrevGender">-</strong></div>
                    <div><span style="color:#6b7280; font-size:0.8rem; display:block;">NO. TELEPON</span><strong id="adminPrevTelp">-</strong></div>
                </div>
                <div id="adminPrevKontrolAlert" style="display:none; margin-top:12px; padding:10px 14px; background:#eff6ff; border-left:4px solid #3b82f6; border-radius:6px; font-size:0.88rem; color:#1e40af;"></div>
            </div>
        </div>

        <!-- SEKSI PASIEN BARU -->
        <div id="adminSectionBaru" style="display:none;">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nama Pasien <span class="required">*</span></label>
                    <input type="text" name="nama_pasien" id="adminInputNamaBaru" placeholder="Masukkan nama lengkap">
                </div>
                <div class="form-group">
                    <label>Tanggal Lahir <span class="required">*</span></label>
                    <input type="date" name="tanggal_lahir" id="adminInputTglBaru">
                </div>
                <div class="form-group">
                    <label>Jenis Kelamin <span class="required">*</span></label>
                    <select name="jenis_kelamin" id="adminSelectGenderBaru">
                        <option value="">-- Pilih --</option>
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>No. Telepon / WhatsApp</label>
                    <input type="text" name="no_telepon" id="adminInputTelpBaru" placeholder="08xxxxxxxxxx">
                </div>
                <div class="form-group">
                    <label>NIK</label>
                    <input type="text" name="nik" placeholder="16 digit NIK">
                </div>
                <div class="form-group">
                    <label>Alamat</label>
                    <input type="text" name="alamat" placeholder="Alamat lengkap pasien">
                </div>
            </div>
        </div>

        <!-- DOKTER, JADWAL & KELUHAN -->
        <div class="form-grid" style="margin-top:10px;">
            <div class="form-group form-group-full">
                <label>Pilih Dokter & Jadwal Praktik <span class="required">*</span></label>
                <select name="id_jadwal" id="adminSelectJadwal" required>
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
            <div class="form-group form-group-full">
                <label>Keluhan / Tujuan Kunjungan <span class="required">*</span></label>
                <textarea name="keluhan" id="adminInputKeluhan" rows="3" placeholder="Contoh: Kontrol rutin pasca tindakan / pencabutan gigi..." required>Kontrol rutin pasca perawatan gigi</textarea>
            </div>
        </div>

        <div style="margin-top: 25px; display:flex; gap:12px; align-items:center;">
            <button type="submit" class="btn btn-success" style="font-size:1rem; padding:12px 24px;">
                <i class="fas fa-ticket-alt"></i> Ambil Nomor Antrian
            </button>
            <a href="antrian.php" class="btn" style="background:#f3f4f6; color:#374151;">
                <i class="fas fa-arrow-left"></i> Kembali ke Antrian
            </a>
        </div>
    </form>
</div>

<script>
function switchAdminTab(type) {
    const tabLama = document.getElementById('tabAdminLama');
    const tabBaru = document.getElementById('tabAdminBaru');
    const secLama = document.getElementById('adminSectionLama');
    const secBaru = document.getElementById('adminSectionBaru');
    const tipeInput = document.getElementById('tipePasienAdmin');
    const inputRm = document.getElementById('adminInputRm');
    const inputNama = document.getElementById('adminInputNamaBaru');
    const inputTgl = document.getElementById('adminInputTglBaru');
    const selectGen = document.getElementById('adminSelectGenderBaru');
    const keluhan = document.getElementById('adminInputKeluhan');

    if (type === 'lama') {
        tabLama.classList.add('btn-primary');
        tabLama.style.background = '';
        tabLama.style.color = '';
        tabBaru.classList.remove('btn-primary');
        tabBaru.style.background = '#f3f4f6';
        tabBaru.style.color = '#374151';

        secLama.style.display = 'block';
        secBaru.style.display = 'none';
        tipeInput.value = 'lama';

        inputRm.setAttribute('required', 'required');
        inputNama.removeAttribute('required');
        inputTgl.removeAttribute('required');
        selectGen.removeAttribute('required');

        if (!keluhan.value) keluhan.value = 'Kontrol rutin pasca perawatan gigi';
    } else {
        tabBaru.classList.add('btn-primary');
        tabBaru.style.background = '';
        tabBaru.style.color = '';
        tabLama.classList.remove('btn-primary');
        tabLama.style.background = '#f3f4f6';
        tabLama.style.color = '#374151';

        secBaru.style.display = 'block';
        secLama.style.display = 'none';
        tipeInput.value = 'baru';

        inputRm.removeAttribute('required');
        inputNama.setAttribute('required', 'required');
        inputTgl.setAttribute('required', 'required');
        selectGen.setAttribute('required', 'required');

        if (keluhan.value === 'Kontrol rutin pasca perawatan gigi') keluhan.value = '';
    }
}

// Inisialisasi tab
switchAdminTab('lama');

let debounceTimer = null;
const adminInputRm = document.getElementById('adminInputRm');

adminInputRm.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    const val = this.value.trim();
    if (val.length >= 2) {
        debounceTimer = setTimeout(lookupAdminPasien, 400);
    }
});

adminInputRm.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        lookupAdminPasien();
    }
});

function lookupAdminPasien() {
    const q = document.getElementById('adminInputRm').value.trim();
    if (!q) return;

    fetch('../api/cari_pasien.php?q=' + encodeURIComponent(q))
        .then(res => res.json())
        .then(data => {
            const preview = document.getElementById('adminPatientPreview');
            const alertKontrol = document.getElementById('adminPrevKontrolAlert');
            const hiddenId = document.getElementById('adminIdPasienHidden');

            if (data.status === 'success' && data.data) {
                const p = data.data;
                hiddenId.value = p.id;
                document.getElementById('adminPrevNama').textContent = p.nama;
                document.getElementById('adminPrevRm').textContent = p.kode_pasien;
                document.getElementById('adminPrevTgl').textContent = p.tanggal_lahir_formatted;
                document.getElementById('adminPrevGender').textContent = p.jenis_kelamin;
                document.getElementById('adminPrevTelp').textContent = p.no_telepon;

                if (p.active_queue) {
                    alert('PERINGATAN: Pasien ' + p.nama + ' sudah memiliki antrian aktif hari ini (# ' + p.active_queue.no_antrian + ', Status: ' + p.active_queue.status + ')!');
                }

                if (p.last_visit && p.last_visit.kunjungan_berikutnya) {
                    alertKontrol.style.display = 'block';
                    alertKontrol.innerHTML = `<strong><i class="fas fa-calendar-check"></i> Jadwal Kontrol Pasien:</strong> ${p.last_visit.kunjungan_berikutnya} (dr. ${p.last_visit.dokter})<br><em>Diagnosa: ${p.last_visit.diagnosa}</em>`;
                    document.getElementById('adminInputKeluhan').value = 'Kontrol lanjutan (Diagnosa: ' + p.last_visit.diagnosa + ')';
                } else {
                    alertKontrol.style.display = 'none';
                }

                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
                hiddenId.value = '';
                alert('Data pasien dengan kata kunci "' + q + '" tidak ditemukan!');
            }
        })
        .catch(err => console.error(err));
}
</script>

<?php include "../layout/footer.php" ?>
