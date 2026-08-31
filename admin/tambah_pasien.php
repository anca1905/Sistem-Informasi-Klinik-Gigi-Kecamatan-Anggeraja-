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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama    = mysqli_real_escape_string($koneksi, trim($_POST['nama']));
    $tgl_l   = mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir']);
    $jenis   = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin']);
    $nik     = mysqli_real_escape_string($koneksi, trim($_POST['nik']));
    $alamat  = mysqli_real_escape_string($koneksi, trim($_POST['alamat']));
    $telp    = mysqli_real_escape_string($koneksi, trim($_POST['no_telepon']));

    if (empty($nama)) {
        $pesan = 'Nama pasien tidak boleh kosong.';
        $pesan_type = 'error';
    } else {
        // Generate No. RM
        $total = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM pasien")) + 1;
        $kode_rm = 'RM' . str_pad($total, 4, '0', STR_PAD_LEFT);

        mysqli_query($koneksi, "INSERT INTO pasien (kode_pasien, nama, tanggal_lahir, jenis_kelamin, nik, alamat, no_telepon)
            VALUES ('$kode_rm', '$nama', '$tgl_l', '$jenis', '$nik', '$alamat', '$telp')");

        header("location:data_pasien.php");
        exit();
    }
}
?>
<?php include "../layout/header.php" ?>

<div class="page-header">
    <h2><i class="fas fa-user-plus"></i> Tambah Pasien Baru</h2>
</div>

<?php if ($pesan): ?>
    <div class="alert alert-<?php echo $pesan_type; ?>">
        <i class="fas fa-exclamation-circle"></i> <?php echo $pesan; ?>
    </div>
<?php endif; ?>

<div class="form-card">
    <form method="POST">
        <div class="form-grid">
            <div class="form-group form-group-full">
                <label>Nama Lengkap <span class="required">*</span></label>
                <input type="text" name="nama" placeholder="Nama lengkap pasien" required>
            </div>
            <div class="form-group">
                <label>Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir">
            </div>
            <div class="form-group">
                <label>Jenis Kelamin</label>
                <select name="jenis_kelamin">
                    <option value="">-- Pilih --</option>
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                </select>
            </div>
            <div class="form-group">
                <label>NIK</label>
                <input type="text" name="nik" placeholder="Nomor Induk Kependudukan">
            </div>
            <div class="form-group">
                <label>No. Telepon</label>
                <input type="text" name="no_telepon" placeholder="08xxxxxxxxxx">
            </div>
            <div class="form-group form-group-full">
                <label>Alamat</label>
                <textarea name="alamat" placeholder="Alamat lengkap..." rows="3"></textarea>
            </div>
        </div>
        <div style="margin-top:20px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan</button>
            <a href="data_pasien.php" class="btn" style="background:#f3f4f6;color:#374151;"><i class="fas fa-arrow-left"></i> Batal</a>
        </div>
    </form>
</div>

<?php include "../layout/footer.php" ?>