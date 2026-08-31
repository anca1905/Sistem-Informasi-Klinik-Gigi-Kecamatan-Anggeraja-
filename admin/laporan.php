<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../auth/login.php");
    exit();
}

$nama_petugas = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : $_SESSION['login_user'];

// Filter tanggal
$filter_tgl = isset($_GET['tanggal']) && $_GET['tanggal'] != '' ? $_GET['tanggal'] : '';
$where = '';
if ($filter_tgl != '') {
    $tgl_safe = mysqli_real_escape_string($koneksi, $filter_tgl);
    $where = "WHERE DATE(rm.tanggal_periksa) = '$tgl_safe'";
}

$query = mysqli_query($koneksi, "
    SELECT rm.*, p.kode_pasien, p.nama AS nama_pasien2, d.nama_dokter
    FROM rekam_medis rm
    LEFT JOIN pasien p ON rm.id_pasien = p.id
    LEFT JOIN dokter d ON rm.id_dokter = d.id_dokter
    $where
    ORDER BY rm.tanggal_periksa DESC, rm.id DESC
");
?>
<?php include "../layout/header.php" ?>

<div class="page-header">
    <h2><i class="fas fa-file-medical-alt"></i> Laporan Hasil Pemeriksaan</h2>
</div>

<!-- Filter -->
<div class="form-card" style="margin-bottom:20px;">
    <form method="GET" style="display:flex;gap:15px;align-items:flex-end;flex-wrap:wrap;">
        <div>
            <label style="display:block;font-weight:600;margin-bottom:6px;font-size:.875rem;">Tanggal</label>
            <input type="date" name="tanggal" value="<?php echo htmlspecialchars($filter_tgl); ?>"
                style="padding:10px 14px;border:1px solid #e5e7eb;border-radius:8px;">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tampilkan</button>
        <?php if ($filter_tgl): ?>
            <a href="laporan.php" class="btn" style="background:#f3f4f6;color:#374151;">Semua Data</a>
        <?php endif; ?>
        <?php if (mysqli_num_rows($query) > 0): ?>
            <a href="cetak_laporan.php?tanggal=<?php echo urlencode($filter_tgl); ?>" target="_blank" class="btn" style="background:#064e3b;color:white;margin-left:auto;">
                <i class="fas fa-print"></i> Cetak
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Tabel Laporan -->
<div class="table-container">
    <div class="table-header">
        <h3 style="font-size:1rem;color:#374151;">
            <i class="fas fa-file-alt" style="margin-right:8px;"></i>
            Laporan Pemeriksaan <?php echo $filter_tgl ? '— ' . date('d-m-Y', strtotime($filter_tgl)) : '(Semua)'; ?>
        </h3>
        <span class="badge badge-done"><?php echo mysqli_num_rows($query); ?> Data</span>
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
                <?php if (mysqli_num_rows($query) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($query)): ?>
                        <tr>
                            <td style="font-weight:500;"><?php echo date('d-m-Y', strtotime($row['tanggal_periksa'])); ?></td>
                            <td><span class="queue-number"><?php echo htmlspecialchars($row['kode_pasien'] ?? '-'); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($row['nama_pasien'] ?? $row['nama_pasien2'] ?? '-'); ?></strong></td>
                            <td style="color:#374151;">
                                <?php echo $row['nama_dokter'] ? 'dr. ' . htmlspecialchars($row['nama_dokter']) : '<span style="color:#9ca3af;">-</span>'; ?>
                            </td>
                            <td style="color:#2563eb;font-weight:500;">
                                <?php echo htmlspecialchars($row['diagnosa'] ?? '-'); ?>
                            </td>
                            <td style="color:#059669;font-weight:500;">
                                <?php echo htmlspecialchars($row['tindakan'] ?? '-'); ?>
                            </td>
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

<?php include "../layout/footer.php" ?>