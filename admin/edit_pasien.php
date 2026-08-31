<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../auth/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("location:data_pasien.php");
    exit();
}
$id = (int)$_GET['id'];

$ambil = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM pasien WHERE id=$id"));
if (!$ambil) {
    header("location:data_pasien.php");
    exit();
}

$nama_petugas = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : $_SESSION['login_user'];

if (isset($_POST['update'])) {
    $nama    = mysqli_real_escape_string($koneksi, trim($_POST['nama']));
    $tgl_l   = mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir']);
    $jenis   = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin']);
    $nik     = mysqli_real_escape_string($koneksi, trim($_POST['nik']));
    $alamat  = mysqli_real_escape_string($koneksi, trim($_POST['alamat']));
    $telp    = mysqli_real_escape_string($koneksi, trim($_POST['no_telepon']));

    mysqli_query($koneksi, "UPDATE pasien SET 
        nama='$nama', tanggal_lahir='$tgl_l', jenis_kelamin='$jenis',
        nik='$nik', alamat='$alamat', no_telepon='$telp'
        WHERE id=$id");

    header("location:data_pasien.php");
    exit();
}
?>
<?php include "../layout/header.php" ?>

<div class="page-header">
    <h2><i class="fas fa-user-edit"></i> Edit Data Pasien</h2>
</div>

<div class="form-card">
    <form method="POST">
        <div class="form-grid">
            <div class="form-group form-group-full">
                <label>No. Rekam Medis</label>
                <input type="text" value="<?php echo htmlspecialchars($ambil['kode_pasien']); ?>" disabled
                    style="background:#f9fafb;color:#6b7280;">
            </div>
            <div class="form-group form-group-full">
                <label>Nama Lengkap <span class="required">*</span></label>
                <input type="text" name="nama" value="<?php echo htmlspecialchars($ambil['nama']); ?>" required>
            </div>
            <div class="form-group">
                <label>Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir" value="<?php echo $ambil['tanggal_lahir']; ?>">
            </div>
            <div class="form-group">
                <label>Jenis Kelamin</label>
                <select name="jenis_kelamin">
                    <option value="">-- Pilih --</option>
                    <option value="Laki-laki" <?php echo $ambil['jenis_kelamin'] == 'Laki-laki'  ? 'selected' : ''; ?>>Laki-laki</option>
                    <option value="Perempuan" <?php echo $ambil['jenis_kelamin'] == 'Perempuan'  ? 'selected' : ''; ?>>Perempuan</option>
                </select>
            </div>
            <div class="form-group">
                <label>NIK</label>
                <input type="text" name="nik" value="<?php echo htmlspecialchars($ambil['nik'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>No. Telepon</label>
                <input type="text" name="no_telepon" value="<?php echo htmlspecialchars($ambil['no_telepon'] ?? ''); ?>">
            </div>
            <div class="form-group form-group-full">
                <label>Alamat</label>
                <textarea name="alamat" rows="3"><?php echo htmlspecialchars($ambil['alamat'] ?? ''); ?></textarea>
            </div>
        </div>
        <div style="margin-top:20px;display:flex;gap:10px;">
            <button type="submit" name="update" class="btn btn-success"><i class="fas fa-save"></i> Update Data</button>
            <a href="data_pasien.php" class="btn" style="background:#f3f4f6;color:#374151;"><i class="fas fa-arrow-left"></i> Batal</a>
        </div>
    </form>
</div>

<?php include "../layout/footer.php" ?>