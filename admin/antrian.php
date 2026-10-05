<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../auth/login.php");
    exit();
}

$nama_petugas = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : $_SESSION['login_user'];
$role_active  = isset($_SESSION['role']) ? $_SESSION['role'] : 'Admin';

// Update status antrian
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id   = (int)$_GET['id'];
    $aksi = $_GET['aksi'];
    if ($aksi == 'panggil')  mysqli_query($koneksi, "UPDATE antrian SET status='Dilayani' WHERE id=$id");
    if ($aksi == 'selesai')  mysqli_query($koneksi, "UPDATE antrian SET status='Selesai'  WHERE id=$id");
    header("location:antrian.php");
    exit();
}

// Filter
$filter_tgl    = isset($_GET['tanggal']) && $_GET['tanggal'] != '' ? mysqli_real_escape_string($koneksi, $_GET['tanggal']) : date('Y-m-d');
$filter_status = isset($_GET['status'])  && $_GET['status']  != '' ? mysqli_real_escape_string($koneksi, $_GET['status'])  : '';

$where = "WHERE DATE(a.waktu_daftar) = '$filter_tgl'";
if ($filter_status != '') $where .= " AND a.status = '$filter_status'";

$query = mysqli_query($koneksi, "
    SELECT a.*, p.kode_pasien, d.nama_dokter
    FROM antrian a
    LEFT JOIN pasien  p ON a.id_pasien  = p.id
    LEFT JOIN dokter  d ON a.id_dokter  = d.id_dokter
    $where
    ORDER BY a.no_antrian ASC
");

// Hitung statistik hari ini
$semua    = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM antrian WHERE DATE(waktu_daftar)='" . date('Y-m-d') . "'"));
$menunggu = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM antrian WHERE DATE(waktu_daftar)='" . date('Y-m-d') . "' AND status='Menunggu'"));
$dipanggil = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM antrian WHERE DATE(waktu_daftar)='" . date('Y-m-d') . "' AND status='Dilayani'"));
$selesai  = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM antrian WHERE DATE(waktu_daftar)='" . date('Y-m-d') . "' AND status='Selesai'"));
?>
<?php include "../layout/header.php" ?>

<div class="page-header">
    <h2><i class="fas fa-list-ol"></i> Data Antrian</h2>
</div>

<!-- Statistik mini -->
<div class="stats-grid" style="grid-template-columns: repeat(4,1fr); margin-bottom:20px;">
    <div class="stat-card card-green">
        <div class="stat-text">
            <p>Semua</p>
            <h3><?php echo $semua; ?></h3>
        </div>
        <div class="icon-box icon-green"><i class="fas fa-users"></i></div>
    </div>
    <div class="stat-card card-yellow">
        <div class="stat-text">
            <p>Menunggu</p>
            <h3><?php echo $menunggu; ?></h3>
        </div>
        <div class="icon-box icon-yellow"><i class="fas fa-clock"></i></div>
    </div>
    <div class="stat-card card-blue">
        <div class="stat-text">
            <p>Dipanggil</p>
            <h3><?php echo $dipanggil; ?></h3>
        </div>
        <div class="icon-box icon-blue"><i class="fas fa-bullhorn"></i></div>
    </div>
    <div class="stat-card" style="border-left-color:#8b5cf6;">
        <div class="stat-text">
            <p>Selesai</p>
            <h3><?php echo $selesai; ?></h3>
        </div>
        <div class="icon-box" style="background:#ede9fe;color:#7c3aed;width:50px;height:50px;border-radius:50%;display:flex;align-items:center;justify-content:center;">
            <i class="fas fa-check-circle"></i>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="table-container" style="margin-bottom:20px;">
    <div style="padding:20px 25px;">
        <form method="GET" style="display:flex;gap:15px;align-items:flex-end;flex-wrap:wrap;">
            <div>
                <label style="display:block;font-weight:600;margin-bottom:6px;font-size:.875rem;">Tanggal</label>
                <input type="date" name="tanggal" value="<?php echo $filter_tgl; ?>" style="padding:9px 12px;border:1px solid #e5e7eb;border-radius:8px;">
            </div>
            <div>
                <label style="display:block;font-weight:600;margin-bottom:6px;font-size:.875rem;">Status</label>
                <select name="status" style="padding:9px 12px;border:1px solid #e5e7eb;border-radius:8px;">
                    <option value="">Semua Status</option>
                    <option value="Menunggu" <?php echo $filter_status == 'Menunggu'  ? 'selected' : ''; ?>>Menunggu</option>
                    <option value="Dilayani" <?php echo $filter_status == 'Dilayani'  ? 'selected' : ''; ?>>Dilayani</option>
                    <option value="Selesai" <?php echo $filter_status == 'Selesai'   ? 'selected' : ''; ?>>Selesai</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tampilkan</button>
            <?php if ($role_active == 'Admin'): ?>
                <a href="pendaftaran.php" class="btn btn-success"><i class="fas fa-plus"></i> Daftar Baru</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if (isset($_GET['status']) && $_GET['status'] == 'selesai_periksa'): ?>
    <div style="background:#d1fae5; border-left:4px solid #10b981; padding:12px 18px; margin-bottom:20px; border-radius:6px; color:#065f46; font-weight:500;">
        <i class="fas fa-check-circle" style="margin-right:8px;"></i> Pemeriksaan berhasil disimpan & jadwal kontrol telah dicatat! Data tagihan diteruskan ke Kasir/Admin.
    </div>
<?php endif; ?>

<!-- Tabel -->
<div class="table-container">
    <div class="table-header">
        <h3 style="font-size:1rem;color:#374151;"><i class="fas fa-list" style="margin-right:8px;"></i>Daftar Antrian — <?php echo date('d-m-Y', strtotime($filter_tgl)); ?></h3>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>No. Antrian</th>
                    <th>Nama Pasien</th>
                    <th>No. RM</th>
                    <th>Dokter</th>
                    <th>Status</th>
                    <th>Waktu Daftar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($query) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($query)): ?>
                        <tr>
                            <td><span class="queue-number">#<?php echo $row['no_antrian']; ?></span></td>
                            <td><strong><?php echo htmlspecialchars($row['nama_pendaftar']); ?></strong></td>
                            <td style="color:#6b7280;"><?php echo $row['kode_pasien'] ?? '-'; ?></td>
                            <td><?php echo $row['nama_dokter'] ? 'dr. ' . htmlspecialchars($row['nama_dokter']) : '-'; ?></td>
                            <td>
                                <?php if ($row['status'] == 'Menunggu'): ?>
                                    <span class="badge badge-wait"><span class="dot" style="background:#d97706;"></span> Menunggu</span>
                                <?php elseif ($row['status'] == 'Dilayani'): ?>
                                    <span class="badge badge-process"><span class="dot" style="background:#2563eb;"></span> Dilayani</span>
                                <?php else: ?>
                                    <span class="badge badge-done"><span class="dot" style="background:#059669;"></span> Selesai</span>
                                <?php endif; ?>
                            </td>
                            <td style="color:#6b7280;font-size:.875rem;"><?php echo date('H:i', strtotime($row['waktu_daftar'])); ?></td>
                            <td>
                                <?php if ($role_active == 'Admin'): ?>
                                    <?php if ($row['status'] == 'Menunggu'): ?>
                                        <a href="antrian.php?aksi=panggil&id=<?php echo $row['id']; ?>" class="btn btn-primary" style="font-size:.8rem;padding:6px 12px;">
                                            <i class="fas fa-bullhorn"></i> Panggil
                                        </a>
                                    <?php elseif ($row['status'] == 'Dilayani'): ?>
                                        <a href="input_rekam_medis.php?id=<?php echo $row['id']; ?>" class="btn btn-success" style="font-size:.8rem;padding:6px 12px;">
                                            <i class="fas fa-stethoscope"></i> Periksa
                                        </a>
                                    <?php else: ?>
                                        <span style="color:#059669;font-weight:600;font-size:.85rem;"><i class="fas fa-check"></i> Tuntas</span>
                                    <?php endif; ?>
                                <?php elseif ($role_active == 'Dokter'): ?>
                                    <?php if ($row['status'] == 'Menunggu'): ?>
                                        <span style="color:#d97706;font-weight:600;font-size:.85rem;"><i class="fas fa-clock"></i> Belum Dipanggil</span>
                                    <?php elseif ($row['status'] == 'Dilayani'): ?>
                                        <a href="input_rekam_medis.php?id=<?php echo $row['id']; ?>" class="btn btn-success" style="font-size:.8rem;padding:6px 12px;">
                                            <i class="fas fa-stethoscope"></i> Periksa Pasien
                                        </a>
                                    <?php else: ?>
                                        <span style="color:#059669;font-weight:600;font-size:.85rem;"><i class="fas fa-check"></i> Selesai Diperiksa</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color:#6b7280;font-size:.85rem;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center;padding:40px;color:#9ca3af;">
                            <i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
                            Tidak ada data antrian untuk tanggal ini.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../layout/footer.php" ?>