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

// Hapus dokter
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM dokter WHERE id_dokter=$id");
    header("location:data_dokter.php?pesan=hapus");
    exit();
}

// Tambah dokter
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aksi']) && $_POST['aksi'] == 'tambah') {
    $nama = mysqli_real_escape_string($koneksi, trim($_POST['nama_dokter']));
    if (!empty($nama)) {
        mysqli_query($koneksi, "INSERT INTO dokter (nama_dokter) VALUES ('$nama')");
        $pesan = 'Dokter berhasil ditambahkan.';
        $pesan_type = 'success';
    } else {
        $pesan = 'Nama dokter tidak boleh kosong.';
        $pesan_type = 'error';
    }
}

// Edit dokter
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aksi']) && $_POST['aksi'] == 'edit') {
    $id   = (int)$_POST['id_dokter'];
    $nama = mysqli_real_escape_string($koneksi, trim($_POST['nama_dokter']));
    if (!empty($nama)) {
        mysqli_query($koneksi, "UPDATE dokter SET nama_dokter='$nama' WHERE id_dokter=$id");
        $pesan = 'Data dokter berhasil diupdate.';
        $pesan_type = 'success';
    }
}

if (isset($_GET['pesan']) && $_GET['pesan'] == 'hapus') {
    $pesan = 'Dokter berhasil dihapus.';
    $pesan_type = 'success';
}

$query = mysqli_query($koneksi, "SELECT * FROM dokter ORDER BY id_dokter ASC");
?>
<?php include "../layout/header.php" ?>

<div class="page-header">
    <h2><i class="fas fa-user-md"></i> Data Dokter</h2>
</div>

<?php if ($pesan): ?>
    <div class="alert alert-<?php echo $pesan_type; ?>">
        <i class="fas fa-<?php echo $pesan_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo $pesan; ?>
    </div>
<?php endif; ?>

<!-- Form Tambah -->
<div class="form-card" style="margin-bottom:20px;">
    <h3 style="margin-bottom:15px;font-size:.95rem;color:#374151;"><i class="fas fa-plus-circle" style="margin-right:8px;color:var(--primary-light);"></i>Tambah Dokter Baru</h3>
    <form method="POST" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <input type="hidden" name="aksi" value="tambah">
        <div style="flex:1;min-width:250px;">
            <label style="display:block;font-weight:600;margin-bottom:6px;font-size:.875rem;">Nama Dokter</label>
            <input type="text" name="nama_dokter" placeholder="Contoh: Budi Santoso" style="width:100%;padding:10px 14px;border:1px solid #e5e7eb;border-radius:8px;">
        </div>
        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan</button>
    </form>
</div>

<!-- Tabel Dokter -->
<div class="table-container">
    <div class="table-header">
        <h3 style="font-size:1rem;color:#374151;"><i class="fas fa-list" style="margin-right:8px;"></i>Daftar Dokter</h3>
        <span class="badge badge-done"><?php echo mysqli_num_rows($query); ?> Dokter</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Dokter</th>
                    <th>Jadwal Praktik</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1;
                while ($row = mysqli_fetch_assoc($query)): ?>
                    <?php
                    $q_jdwl = mysqli_query($koneksi, "SELECT hari, jam_mulai, jam_selesai FROM jadwal_dokter WHERE id_dokter=" . $row['id_dokter'] . " AND status='Praktek'");
                    $jadwal_list = [];
                    while ($jdwl = mysqli_fetch_assoc($q_jdwl)) {
                        $jadwal_list[] = $jdwl['hari'] . ' ' . substr($jdwl['jam_mulai'], 0, 5) . '-' . substr($jdwl['jam_selesai'], 0, 5);
                    }
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><strong>dr. <?php echo htmlspecialchars($row['nama_dokter']); ?></strong></td>
                        <td style="color:#6b7280;font-size:.875rem;">
                            <?php echo !empty($jadwal_list) ? implode(', ', $jadwal_list) : '<span style="color:#d1d5db;">Belum ada jadwal</span>'; ?>
                        </td>
                        <td style="display:flex;gap:8px;">
                            <a href="edit_dokter.php?id=<?php echo $row['id_dokter']; ?>" class="btn btn-primary" style="font-size:.8rem;padding:6px 12px;">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="data_dokter.php?hapus=<?php echo $row['id_dokter']; ?>"
                                onclick="return confirm('Hapus dokter ini?')"
                                class="btn" style="background:#fef2f2;color:#dc2626;font-size:.8rem;padding:6px 12px;">
                                <i class="fas fa-trash"></i> Hapus
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($query) == 0): ?>
                    <tr>
                        <td colspan="4" style="text-align:center;padding:40px;color:#9ca3af;">
                            <i class="fas fa-user-md" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
                            Belum ada data dokter.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../layout/footer.php" ?>