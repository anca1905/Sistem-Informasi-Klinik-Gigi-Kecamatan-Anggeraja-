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

// Hapus jadwal
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM jadwal_dokter WHERE id=$id");
    header("location:jadwal_praktik.php?pesan=hapus");
    exit();
}

// Toggle status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $cur = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT status FROM jadwal_dokter WHERE id=$id"));
    $new_status = ($cur['status'] == 'Praktek') ? 'Libur' : 'Praktek';
    mysqli_query($koneksi, "UPDATE jadwal_dokter SET status='$new_status' WHERE id=$id");
    header("location:jadwal_praktik.php");
    exit();
}

// Tambah jadwal
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_dokter  = (int)$_POST['id_dokter'];
    $hari       = mysqli_real_escape_string($koneksi, $_POST['hari']);
    $jam_mulai  = mysqli_real_escape_string($koneksi, $_POST['jam_mulai']);
    $jam_selesai = mysqli_real_escape_string($koneksi, $_POST['jam_selesai']);
    $status     = 'Praktek';

    if ($id_dokter && $hari && $jam_mulai && $jam_selesai) {
        mysqli_query($koneksi, "INSERT INTO jadwal_dokter (id_dokter, hari, jam_mulai, jam_selesai, status)
            VALUES ($id_dokter, '$hari', '$jam_mulai', '$jam_selesai', '$status')");
        $pesan = 'Jadwal berhasil ditambahkan.';
        $pesan_type = 'success';
    } else {
        $pesan = 'Semua field wajib diisi.';
        $pesan_type = 'error';
    }
}

if (isset($_GET['pesan']) && $_GET['pesan'] == 'hapus') {
    $pesan = 'Jadwal berhasil dihapus.';
    $pesan_type = 'success';
}

$dokter_list = mysqli_query($koneksi, "SELECT * FROM dokter ORDER BY nama_dokter");
$jadwal_list = mysqli_query($koneksi, "
    SELECT j.*, d.nama_dokter
    FROM jadwal_dokter j
    LEFT JOIN dokter d ON j.id_dokter = d.id_dokter
    ORDER BY FIELD(j.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), j.jam_mulai
");
?>
<?php include "../layout/header.php" ?>

<div class="page-header">
    <h2><i class="fas fa-calendar-alt"></i> Jadwal Praktik</h2>
</div>

<?php if ($pesan): ?>
    <div class="alert alert-<?php echo $pesan_type; ?>">
        <i class="fas fa-<?php echo $pesan_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo $pesan; ?>
    </div>
<?php endif; ?>

<!-- Form Tambah Jadwal Modal -->
<div id="jadwalModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle" style="margin-right:8px;color:var(--primary-light);"></i>Tambah Jadwal Baru</h3>
            <button type="button" class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Dokter <span class="required">*</span></label>
                        <select name="id_dokter" required>
                            <option value="">-- Pilih Dokter --</option>
                            <?php
                            mysqli_data_seek($dokter_list, 0); // reset pointer so it can be re-iterated if needed, though it's the first time it's used here anyway.
                            while ($dok = mysqli_fetch_assoc($dokter_list)): ?>
                                <option value="<?php echo $dok['id_dokter']; ?>">dr. <?php echo htmlspecialchars($dok['nama_dokter']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Hari <span class="required">*</span></label>
                        <select name="hari" required>
                            <option value="">-- Pilih Hari --</option>
                            <?php foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $h): ?>
                                <option value="<?php echo $h; ?>"><?php echo $h; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Jam Mulai <span class="required">*</span></label>
                        <input type="time" name="jam_mulai" required>
                    </div>
                    <div class="form-group">
                        <label>Jam Selesai <span class="required">*</span></label>
                        <input type="time" name="jam_selesai" required>
                    </div>
                </div>
                <div style="margin-top:20px; text-align: right;">
                    <button type="button" class="btn" style="background:#f3f4f6;color:#6b7280;margin-right:10px;" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan Jadwal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tabel Jadwal -->
<div class="table-container">
    <div class="table-header">
        <h3 style="font-size:1rem;color:#374151;"><i class="fas fa-calendar-check" style="margin-right:8px;"></i>Daftar Jadwal Praktik</h3>
        <div>
            <span class="badge badge-done" style="margin-right:10px;"><?php echo mysqli_num_rows($jadwal_list); ?> Jadwal</span>
            <button class="btn btn-success" onclick="openModal()"><i class="fas fa-plus"></i> Tambah Jadwal</button>
        </div>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Dokter</th>
                    <th>Hari</th>
                    <th>Jam Mulai</th>
                    <th>Jam Selesai</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1;
                while ($row = mysqli_fetch_assoc($jadwal_list)): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><strong>dr. <?php echo htmlspecialchars($row['nama_dokter'] ?? ''); ?></strong></td>
                        <td><?php echo $row['hari']; ?></td>
                        <td><?php echo substr($row['jam_mulai'], 0, 5); ?></td>
                        <td><?php echo substr($row['jam_selesai'], 0, 5); ?></td>
                        <td>
                            <a href="jadwal_praktik.php?toggle=<?php echo $row['id']; ?>" style="text-decoration:none;">
                                <?php if ($row['status'] == 'Praktek'): ?>
                                    <span class="badge badge-done"><span class="dot" style="background:#059669;"></span> Praktek</span>
                                <?php else: ?>
                                    <span class="badge" style="background:#f3f4f6;color:#6b7280;"><span class="dot" style="background:#9ca3af;"></span> Libur</span>
                                <?php endif; ?>
                            </a>
                        </td>
                        <td>
                            <a href="jadwal_praktik.php?hapus=<?php echo $row['id']; ?>"
                                onclick="return confirm('Hapus jadwal ini?')"
                                class="btn" style="background:#fef2f2;color:#dc2626;font-size:.8rem;padding:6px 12px;">
                                <i class="fas fa-trash"></i> Hapus
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($jadwal_list) == 0): ?>
                    <tr>
                        <td colspan="7" style="text-align:center;padding:40px;color:#9ca3af;">
                            <i class="fas fa-calendar-times" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
                            Belum ada jadwal praktik.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById('jadwalModal').classList.add('active');
    }

    function closeModal() {
        document.getElementById('jadwalModal').classList.remove('active');
    }

    // Tutup saat klik di luar modal (di overlay area)
    window.onclick = function(event) {
        let modal = document.getElementById('jadwalModal');
        if (event.target == modal) {
            closeModal();
        }
    }
</script>

<?php include "../layout/footer.php" ?>