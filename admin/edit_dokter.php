<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../auth/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("location:data_dokter.php");
    exit();
}
$id = (int)$_GET['id'];

$dokter = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM dokter WHERE id_dokter=$id"));
if (!$dokter) {
    header("location:data_dokter.php");
    exit();
}

$nama_petugas = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : $_SESSION['login_user'];
$pesan = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = mysqli_real_escape_string($koneksi, trim($_POST['nama_dokter']));
    if (!empty($nama)) {
        mysqli_query($koneksi, "UPDATE dokter SET nama_dokter='$nama' WHERE id_dokter=$id");
        header("location:data_dokter.php");
        exit();
    } else {
        $pesan = 'Nama dokter tidak boleh kosong.';
    }
}
?>
<?php include "../layout/header.php" ?>

<div class="page-header">
    <h2><i class="fas fa-user-edit"></i> Edit Dokter</h2>
</div>

<?php if ($pesan): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $pesan; ?></div>
<?php endif; ?>

<div class="form-card">
    <form method="POST">
        <div class="form-group">
            <label>Nama Dokter <span class="required">*</span></label>
            <input type="text" name="nama_dokter" value="<?php echo htmlspecialchars($dokter['nama_dokter']); ?>" required>
        </div>
        <div style="margin-top:20px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan Perubahan</button>
            <a href="data_dokter.php" class="btn" style="background:#f3f4f6;color:#374151;"><i class="fas fa-arrow-left"></i> Batal</a>
        </div>
    </form>
</div>

<?php include "../layout/footer.php" ?>