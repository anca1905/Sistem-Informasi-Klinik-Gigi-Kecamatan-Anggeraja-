<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../auth/login.php");
    exit();
}

$nama_petugas = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : $_SESSION['login_user'];
$pesan = '';
$pesan_type = '';

// Ambil daftar dokter untuk dropdown
$query_dokter = mysqli_query($koneksi, "SELECT d.id_dokter, d.nama_dokter, j.id AS id_jadwal, j.hari, j.jam_mulai, j.jam_selesai
    FROM dokter d
    JOIN jadwal_dokter j ON j.id_dokter = d.id_dokter
    WHERE j.status = 'Praktek'
    ORDER BY j.hari, d.nama_dokter");

// Proses Form Pendaftaran
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama  = mysqli_real_escape_string($koneksi, trim($_POST['nama_pasien']));
    $tgl_l = mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir']);
    $jenis = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin']);
    $telp  = mysqli_real_escape_string($koneksi, $_POST['no_telepon']);
    $keluhan  = mysqli_real_escape_string($koneksi, trim($_POST['keluhan']));
    $id_jadwal = (int)$_POST['id_jadwal'];

    if (empty($nama) || empty($tgl_l) || empty($jenis) || empty($keluhan) || empty($id_jadwal)) {
        $pesan = 'Semua field wajib diisi!';
        $pesan_type = 'error';
    } else {
        // Ambil id_dokter dari jadwal yang dipilih
        $q_jdwl = mysqli_query($koneksi, "SELECT j.id_dokter FROM jadwal_dokter j WHERE j.id = $id_jadwal");
        $row_jdwl = mysqli_fetch_assoc($q_jdwl);
        $id_dokter = $row_jdwl ? (int)$row_jdwl['id_dokter'] : null;

        // Cek / insert data pasien (berdasarkan nama + tgl lahir)
        $cek = mysqli_query($koneksi, "SELECT id, kode_pasien FROM pasien WHERE nama='$nama' AND tanggal_lahir='$tgl_l' LIMIT 1");
        if (mysqli_num_rows($cek) > 0) {
            $pasien = mysqli_fetch_assoc($cek);
            $id_pasien = $pasien['id'];
        } else {
            // Buat kode RM baru
            $total_pasien = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM pasien")) + 1;
            $kode_rm = 'RM' . str_pad($total_pasien, 4, '0', STR_PAD_LEFT);
            mysqli_query($koneksi, "INSERT INTO pasien (kode_pasien, nama, tanggal_lahir, jenis_kelamin, no_telepon, nik, alamat) 
                VALUES ('$kode_rm', '$nama', '$tgl_l', '$jenis', '$telp', '', '')");
            $id_pasien = mysqli_insert_id($koneksi);
        }

        // Hitung nomor antrian hari ini
        $hari_ini = date('Y-m-d');
        $no_antrian = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM antrian WHERE DATE(waktu_daftar)='$hari_ini'")) + 1;

        // Simpan antrian
        $id_dokter_val = $id_dokter ? $id_dokter : 'NULL';
        $insert = mysqli_query($koneksi, "INSERT INTO antrian (id_pasien, id_jadwal, id_dokter, no_antrian, nama_pendaftar, keluhan, status, waktu_daftar)
            VALUES ($id_pasien, $id_jadwal, $id_dokter_val, $no_antrian, '$nama', '$keluhan', 'Menunggu', NOW())");

        if ($insert) {
            $pesan = "Pendaftaran berhasil! No. Antrian: <strong>#$no_antrian</strong>";
            $pesan_type = 'success';
        } else {
            $pesan = 'Terjadi kesalahan, coba lagi.';
            $pesan_type = 'error';
        }
    }
    // Query ulang dokter setelah POST
    $query_dokter = mysqli_query($koneksi, "SELECT d.id_dokter, d.nama_dokter, j.id AS id_jadwal, j.hari, j.jam_mulai, j.jam_selesai
        FROM dokter d JOIN jadwal_dokter j ON j.id_dokter = d.id_dokter
        WHERE j.status = 'Praktek' ORDER BY j.hari, d.nama_dokter");
}
?>
<?php include "../layout/header.php" ?>

<div class="page-header">
    <h2><i class="fas fa-clipboard-list"></i> Pendaftaran Pasien</h2>
    <p>Daftarkan pasien baru untuk mendapat nomor antrian.</p>
</div>

<?php if ($pesan): ?>
    <div class="alert alert-<?php echo $pesan_type; ?>">
        <i class="fas fa-<?php echo $pesan_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo $pesan; ?>
    </div>
<?php endif; ?>

<div class="form-card">
    <form method="POST" action="">
        <div class="form-grid">
            <div class="form-group">
                <label>Nama Pasien <span class="required">*</span></label>
                <input type="text" name="nama_pasien" placeholder="Masukkan nama lengkap" required>
            </div>
            <div class="form-group">
                <label>Tanggal Lahir <span class="required">*</span></label>
                <input type="date" name="tanggal_lahir" required>
            </div>
            <div class="form-group">
                <label>Jenis Kelamin <span class="required">*</span></label>
                <select name="jenis_kelamin" required>
                    <option value="">-- Pilih --</option>
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                </select>
            </div>
            <div class="form-group">
                <label>No. Telepon</label>
                <input type="text" name="no_telepon" placeholder="08xxxxxxxxxx">
            </div>
            <div class="form-group form-group-full">
                <label>Pilih Dokter & Jadwal <span class="required">*</span></label>
                <select name="id_jadwal" required>
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
                <label>Keluhan <span class="required">*</span></label>
                <textarea name="keluhan" rows="4" placeholder="Deskripsikan keluhan pasien..." required></textarea>
            </div>
        </div>

        <div style="margin-top: 20px;">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-ticket-alt"></i> Ambil Nomor Antrian
            </button>
            <a href="dashboard.php" class="btn" style="background:#f3f4f6; color:#374151; margin-left:10px;">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </form>
</div>

<?php include "../layout/footer.php" ?>