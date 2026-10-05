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

// Filter
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_tgl    = isset($_GET['tanggal']) ? $_GET['tanggal'] : '';

$where_parts = [];
if ($filter_status != '') {
    $s = mysqli_real_escape_string($koneksi, $filter_status);
    $where_parts[] = "t.status_bayar = '$s'";
}
if ($filter_tgl != '') {
    $tgl_s = mysqli_real_escape_string($koneksi, $filter_tgl);
    $where_parts[] = "DATE(t.tanggal_transaksi) = '$tgl_s'";
}
$where = count($where_parts) > 0 ? 'WHERE ' . implode(' AND ', $where_parts) : '';

$query = mysqli_query($koneksi, "
    SELECT t.*, a.no_antrian
    FROM transaksi t
    LEFT JOIN antrian a ON t.id_antrian = a.id
    $where
    ORDER BY t.tanggal_transaksi DESC
");

// Hitung total pendapatan (Lunas)
$q_total = mysqli_query($koneksi, "SELECT SUM(biaya) as total FROM transaksi WHERE status_bayar='Lunas'");
$total_pendapatan = mysqli_fetch_assoc($q_total)['total'] ?? 0;

$q_belum = mysqli_query($koneksi, "SELECT COUNT(*) as jml FROM transaksi WHERE status_bayar='Belum Bayar'");
$jml_belum = mysqli_fetch_assoc($q_belum)['jml'] ?? 0;
?>
<?php include "../layout/header.php" ?>

<div class="page-header">
    <h2><i class="fas fa-cash-register"></i> Transaksi Pembayaran</h2>
    <a href="tambah_transaksi.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Tambah Transaksi
    </a>
</div>

<!-- Stat Cards -->
<div class="stats-grid" style="margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#10b981,#059669);">
            <i class="fas fa-coins"></i>
        </div>
        <div class="stat-info">
            <div class="stat-number">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></div>
            <div class="stat-label">Total Pendapatan (Lunas)</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-info">
            <div class="stat-number"><?php echo $jml_belum; ?></div>
            <div class="stat-label">Belum Dibayar</div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="form-card" style="margin-bottom:20px;">
    <form method="GET" style="display:flex;gap:15px;align-items:flex-end;flex-wrap:wrap;">
        <div>
            <label style="display:block;font-weight:600;margin-bottom:6px;font-size:.875rem;">Tanggal</label>
            <input type="date" name="tanggal" value="<?php echo htmlspecialchars($filter_tgl); ?>"
                style="padding:10px 14px;border:1px solid #e5e7eb;border-radius:8px;">
        </div>
        <div>
            <label style="display:block;font-weight:600;margin-bottom:6px;font-size:.875rem;">Status</label>
            <select name="status" style="padding:10px 14px;border:1px solid #e5e7eb;border-radius:8px;background:white;">
                <option value="">Semua</option>
                <option value="Belum Bayar" <?php echo $filter_status == 'Belum Bayar' ? 'selected' : ''; ?>>Belum Bayar</option>
                <option value="Lunas" <?php echo $filter_status == 'Lunas' ? 'selected' : ''; ?>>Lunas</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
        <a href="transaksi.php" class="btn" style="background:#f3f4f6;color:#374151;">Reset</a>
    </form>
</div>

<!-- Tabel -->
<div class="table-container">
    <div class="table-header">
        <h3 style="font-size:1rem;color:#374151;">
            <i class="fas fa-list" style="margin-right:8px;"></i>Daftar Transaksi
        </h3>
        <span class="badge badge-done"><?php echo mysqli_num_rows($query); ?> Data</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tanggal</th>
                    <th>No. Antrian</th>
                    <th>Nama Pasien</th>
                    <th>Tindakan</th>
                    <th>Biaya</th>
                    <th>Metode</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($query) > 0): ?>
                    <?php $no = 1;
                    while ($row = mysqli_fetch_assoc($query)): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo date('d-m-Y H:i', strtotime($row['tanggal_transaksi'])); ?></td>
                            <td><span class="queue-number"><?php echo $row['no_antrian'] ?? '-'; ?></span></td>
                            <td><strong><?php echo htmlspecialchars($row['nama_pasien']); ?></strong></td>
                            <td style="max-width:200px;font-size:.85rem;color:#374151;"><?php echo htmlspecialchars($row['tindakan'] ?? '-'); ?></td>
                            <td style="font-weight:600;color:#065f46;">Rp <?php echo number_format($row['biaya'], 0, ',', '.'); ?></td>
                            <td>
                                <span style="background:#dbeafe;color:#1d4ed8;padding:3px 10px;border-radius:20px;font-size:.8rem;font-weight:600;">
                                    <?php echo $row['metode_bayar']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['status_bayar'] == 'Lunas'): ?>
                                    <span class="badge badge-done">Lunas</span>
                                <?php else: ?>
                                    <span class="badge badge-waiting">Belum Bayar</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['status_bayar'] == 'Belum Bayar'): ?>
                                    <a href="konfirmasi_bayar.php?id=<?php echo $row['id_transaksi']; ?>"
                                        class="btn btn-primary" style="padding:6px 12px;font-size:.8rem;">
                                        <i class="fas fa-check-circle"></i> Bayar
                                    </a>
                                <?php else: ?>
                                    <a href="cetak_struk_bayar.php?id=<?php echo $row['id_transaksi']; ?>"
                                        class="btn" style="padding:6px 12px;font-size:.8rem;background:#064e3b;color:white;" target="_blank">
                                        <i class="fas fa-print"></i> Struk
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align:center;padding:40px;color:#9ca3af;">
                            <i class="fas fa-cash-register" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
                            Tidak ada data transaksi.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../layout/footer.php" ?>