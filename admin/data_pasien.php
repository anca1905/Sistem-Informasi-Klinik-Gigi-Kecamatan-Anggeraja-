<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../auth/login.php");
    exit();
}

$nama_petugas = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : $_SESSION['login_user'];

// Hapus pasien
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM pasien WHERE id=$id");
    header("location:data_pasien.php");
    exit();
}

// Search
$cari = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, trim($_GET['cari'])) : '';
$where = $cari ? "WHERE nama LIKE '%$cari%' OR kode_pasien LIKE '%$cari%'" : '';

$query = mysqli_query($koneksi, "SELECT * FROM pasien $where ORDER BY id DESC");
?>
<?php include "../layout/header.php" ?>

<div class="page-header">
    <h2><i class="fas fa-user-injured"></i> Data Pasien</h2>
</div>

<!-- Search + Tambah -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <form method="GET" style="display:flex;gap:10px;align-items:center;">
        <div style="position:relative;">
            <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;"></i>
            <input type="text" name="cari" placeholder="Cari nama / No. RM..." value="<?php echo htmlspecialchars($cari); ?>"
                style="padding:10px 12px 10px 36px;border:1px solid #e5e7eb;border-radius:8px;min-width:260px;">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Cari</button>
        <?php if ($cari): ?>
            <a href="data_pasien.php" class="btn" style="background:#f3f4f6;color:#374151;">Reset</a>
        <?php endif; ?>
    </form>
    <a href="tambah_pasien.php" class="btn btn-success"><i class="fas fa-user-plus"></i> Tambah Pasien</a>
</div>

<div class="table-container">
    <div class="table-header">
        <h3 style="font-size:1rem;color:#374151;"><i class="fas fa-users" style="margin-right:8px;"></i>Daftar Pasien</h3>
        <span class="badge badge-done"><?php echo mysqli_num_rows($query); ?> Pasien</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>No. RM</th>
                    <th>Nama Pasien</th>
                    <th>Tanggal Lahir</th>
                    <th>Jenis Kelamin</th>
                    <th>No. Telepon</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($query) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($query)): ?>
                        <tr>
                            <td><span class="queue-number"><?php echo htmlspecialchars($row['kode_pasien'] ?? '-'); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($row['nama']); ?></strong></td>
                            <td style="color:#6b7280;">
                                <?php echo !empty($row['tanggal_lahir']) ? date('d-m-Y', strtotime($row['tanggal_lahir'])) : '-'; ?>
                            </td>
                            <td>
                                <?php if ($row['jenis_kelamin'] == 'Laki-laki'): ?>
                                    <span class="badge" style="background:#dbeafe;color:#1d4ed8;">♂ Laki-laki</span>
                                <?php elseif ($row['jenis_kelamin'] == 'Perempuan'): ?>
                                    <span class="badge" style="background:#fce7f3;color:#9d174d;">♀ Perempuan</span>
                                <?php else: ?>
                                    <span style="color:#9ca3af;">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="color:#6b7280;"><?php echo htmlspecialchars($row['no_telepon'] ?? '-'); ?></td>
                            <td style="display:flex;gap:8px;">
                                <a href="edit_pasien.php?id=<?php echo $row['id']; ?>" class="btn btn-primary" style="font-size:.8rem;padding:6px 12px;">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="data_pasien.php?hapus=<?php echo $row['id']; ?>"
                                    onclick="return confirm('Hapus data pasien ini?')"
                                    class="btn" style="background:#fef2f2;color:#dc2626;font-size:.8rem;padding:6px 12px;">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;padding:40px;color:#9ca3af;">
                            <i class="fas fa-users" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
                            <?php echo $cari ? 'Tidak ada hasil untuk "' . htmlspecialchars($cari) . '"' : 'Belum ada data pasien.'; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../layout/footer.php" ?>