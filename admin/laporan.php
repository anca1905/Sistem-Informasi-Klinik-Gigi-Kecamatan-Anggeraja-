<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../auth/login.php");
    exit();
}

$nama_petugas = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : $_SESSION['login_user'];

// Tab aktif
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'pemeriksaan';

// ---- Filter ----
$filter_tgl = isset($_GET['tanggal']) && $_GET['tanggal'] != '' ? $_GET['tanggal'] : '';

// ---- Query Pemeriksaan ----
$where_rm = '';
if ($filter_tgl != '') {
    $tgl_safe = mysqli_real_escape_string($koneksi, $filter_tgl);
    $where_rm = "WHERE DATE(rm.tanggal_periksa) = '$tgl_safe'";
}
$query_rm = mysqli_query($koneksi, "
    SELECT rm.*, p.kode_pasien, p.nama AS nama_pasien2, d.nama_dokter
    FROM rekam_medis rm
    LEFT JOIN pasien p ON rm.id_pasien = p.id
    LEFT JOIN dokter d ON rm.id_dokter = d.id_dokter
    $where_rm
    ORDER BY rm.tanggal_periksa DESC, rm.id DESC
");

// ---- Query Transaksi ----
$where_trx = '';
if ($filter_tgl != '') {
    $tgl_safe2 = mysqli_real_escape_string($koneksi, $filter_tgl);
    $where_trx = "WHERE DATE(t.tanggal_transaksi) = '$tgl_safe2'";
}
$query_trx = mysqli_query($koneksi, "
    SELECT t.*, a.no_antrian
    FROM transaksi t
    LEFT JOIN antrian a ON t.id_antrian = a.id
    $where_trx
    ORDER BY t.tanggal_transaksi DESC
");

// Total pendapatan hari itu
$q_sum = mysqli_query($koneksi, "SELECT SUM(biaya) as total FROM transaksi WHERE status_bayar='Lunas'" . ($filter_tgl ? " AND DATE(tanggal_transaksi)='$filter_tgl'" : ""));
$total_lunas = mysqli_fetch_assoc($q_sum)['total'] ?? 0;
?>
<?php include "../layout/header.php" ?>

<div class="page-header">
    <h2><i class="fas fa-file-alt"></i> Laporan</h2>
</div>

<!-- Tab Switcher -->
<div style="display:flex;gap:8px;margin-bottom:20px;border-bottom:2px solid #e5e7eb;padding-bottom:0;">
    <a href="laporan.php?tab=pemeriksaan&tanggal=<?php echo urlencode($filter_tgl); ?>"
        style="padding:10px 20px;border-radius:8px 8px 0 0;text-decoration:none;font-weight:600;font-size:.875rem;
        <?php echo $tab == 'pemeriksaan' ? 'background:#064e3b;color:white;' : 'background:#f3f4f6;color:#374151;'; ?>">
        <i class="fas fa-stethoscope"></i> Hasil Pemeriksaan
    </a>
    <a href="laporan.php?tab=pembayaran&tanggal=<?php echo urlencode($filter_tgl); ?>"
        style="padding:10px 20px;border-radius:8px 8px 0 0;text-decoration:none;font-weight:600;font-size:.875rem;
        <?php echo $tab == 'pembayaran' ? 'background:#064e3b;color:white;' : 'background:#f3f4f6;color:#374151;'; ?>">
        <i class="fas fa-cash-register"></i> Laporan Pembayaran
    </a>
</div>

<!-- Filter -->
<div class="form-card" style="margin-bottom:20px;">
    <form method="GET" style="display:flex;gap:15px;align-items:flex-end;flex-wrap:wrap;">
        <input type="hidden" name="tab" value="<?php echo $tab; ?>">
        <div>
            <label style="display:block;font-weight:600;margin-bottom:6px;font-size:.875rem;">Tanggal</label>
            <input type="date" name="tanggal" value="<?php echo htmlspecialchars($filter_tgl); ?>"
                style="padding:10px 14px;border:1px solid #e5e7eb;border-radius:8px;">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tampilkan</button>
        <?php if ($filter_tgl): ?>
            <a href="laporan.php?tab=<?php echo $tab; ?>" class="btn" style="background:#f3f4f6;color:#374151;">Semua Data</a>
        <?php endif; ?>
        <?php if ($tab == 'pemeriksaan' && mysqli_num_rows($query_rm) > 0): ?>
            <a href="cetak_laporan.php?tanggal=<?php echo urlencode($filter_tgl); ?>" target="_blank"
                class="btn" style="background:#064e3b;color:white;margin-left:auto;">
                <i class="fas fa-print"></i> Cetak
            </a>
        <?php endif; ?>
    </form>
</div>

<?php if ($tab == 'pemeriksaan'): ?>
    <!-- ===== TAB PEMERIKSAAN ===== -->
    <div class="table-container">
        <div class="table-header">
            <h3 style="font-size:1rem;color:#374151;">
                <i class="fas fa-file-alt" style="margin-right:8px;"></i>
                Laporan Pemeriksaan <?php echo $filter_tgl ? '— ' . date('d-m-Y', strtotime($filter_tgl)) : '(Semua)'; ?>
            </h3>
            <span class="badge badge-done"><?php echo mysqli_num_rows($query_rm); ?> Data</span>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>No. RM</th>
                        <th>Pasien</th>
                        <th>Dokter</th>
                        <th>Diagnosa</th>
                        <th>Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($query_rm) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($query_rm)): ?>
                            <tr>
                                <td style="font-weight:500;"><?php echo date('d-m-Y', strtotime($row['tanggal_periksa'])); ?></td>
                                <td><span class="queue-number"><?php echo htmlspecialchars($row['kode_pasien'] ?? '-'); ?></span></td>
                                <td><strong><?php echo htmlspecialchars($row['nama_pasien'] ?? $row['nama_pasien2'] ?? '-'); ?></strong></td>
                                <td><?php echo $row['nama_dokter'] ? 'dr. ' . htmlspecialchars($row['nama_dokter']) : '<span style="color:#9ca3af;">-</span>'; ?></td>
                                <td style="color:#2563eb;font-weight:500;"><?php echo htmlspecialchars($row['diagnosa'] ?? '-'); ?></td>
                                <td style="color:#059669;font-weight:500;"><?php echo htmlspecialchars($row['tindakan'] ?? '-'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center;padding:40px;color:#9ca3af;">
                                <i class="fas fa-file-medical-alt" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
                                <?php echo $filter_tgl ? 'Tidak ada data untuk tanggal ini.' : 'Belum ada data rekam medis.'; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php else: ?>
    <!-- ===== TAB PEMBAYARAN ===== -->

    <!-- Ringkasan -->
    <div style="background:linear-gradient(135deg,#064e3b,#10b981);border-radius:12px;padding:20px;margin-bottom:20px;color:white;display:flex;gap:20px;flex-wrap:wrap;align-items:center;">
        <div>
            <div style="font-size:.8rem;opacity:.8;">Total Pendapatan<?php echo $filter_tgl ? ' — ' . date('d M Y', strtotime($filter_tgl)) : ''; ?></div>
            <div style="font-size:1.8rem;font-weight:700;">Rp <?php echo number_format($total_lunas, 0, ',', '.'); ?></div>
        </div>
        <div style="margin-left:auto;">
            <span style="background:rgba(255,255,255,.2);padding:8px 16px;border-radius:8px;font-size:.875rem;">
                <?php echo mysqli_num_rows($query_trx); ?> transaksi
            </span>
        </div>
    </div>

    <div class="table-container">
        <div class="table-header">
            <h3 style="font-size:1rem;color:#374151;">
                <i class="fas fa-cash-register" style="margin-right:8px;"></i>
                Laporan Pembayaran <?php echo $filter_tgl ? '— ' . date('d-m-Y', strtotime($filter_tgl)) : '(Semua)'; ?>
            </h3>
            <span class="badge badge-done"><?php echo mysqli_num_rows($query_trx); ?> Data</span>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tanggal</th>
                        <th>No. Antrian</th>
                        <th>Nama Pasien</th>
                        <th>Biaya</th>
                        <th>Metode</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($query_trx) > 0): $no = 1;
                        while ($row = mysqli_fetch_assoc($query_trx)): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo date('d-m-Y H:i', strtotime($row['tanggal_transaksi'])); ?></td>
                                <td><span class="queue-number"><?php echo $row['no_antrian'] ?? '-'; ?></span></td>
                                <td><strong><?php echo htmlspecialchars($row['nama_pasien']); ?></strong></td>
                                <td style="font-weight:600;color:#065f46;">Rp <?php echo number_format($row['biaya'], 0, ',', '.'); ?></td>
                                <td><span style="background:#dbeafe;color:#1d4ed8;padding:3px 10px;border-radius:20px;font-size:.8rem;font-weight:600;"><?php echo $row['metode_bayar']; ?></span></td>
                                <td>
                                    <?php if ($row['status_bayar'] == 'Lunas'): ?>
                                        <span class="badge badge-done">Lunas</span>
                                    <?php else: ?>
                                        <span class="badge badge-waiting">Belum Bayar</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile;
                    else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center;padding:40px;color:#9ca3af;">
                                <i class="fas fa-cash-register" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
                                Belum ada data transaksi.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php include "../layout/footer.php" ?>