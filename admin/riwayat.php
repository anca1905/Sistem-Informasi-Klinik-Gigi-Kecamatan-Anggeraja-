<?php
session_start();
// Pastikan path koneksinya benar (sesuaikan naik turun foldernya)
include '../config/koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../login.php"); // Sesuaikan path login
    exit();
}

$nama_petugas = $_SESSION['login_user'];
?>

<?php include "../layout/header.php" ?>

<div class="table-container">
    <div class="table-header">
        <h3 style="font-size: 1rem; color: #374151;">
            <i class="fas fa-history" style="margin-right: 10px;"></i>
            Riwayat Rekam Medis
        </h3>
        <span style="background: #dbeafe; color: #1e40af; padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: bold;">
            Data Lengkap
        </span>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nama Pasien</th>
                    <th>Keluhan</th>
                    <th>Diagnosa Dokter</th>
                    <th>Resep Obat</th>
                    <th>Tgl Kembali</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = mysqli_query($koneksi, "SELECT * FROM rekam_medis ORDER BY id DESC");
                while ($row = mysqli_fetch_array($query)) {
                ?>
                    <tr>
                        <td>
                            <span style="font-weight: 500;"><?php echo date('d-m-Y', strtotime($row['tanggal_periksa'])); ?></span>
                        </td>
                        <td><strong><?php echo htmlspecialchars($row['nama_pasien'] ?? '-'); ?></strong></td>
                        <td style="color: #6b7280;"><?php echo $row['keluhan']; ?></td>

                        <td style="color: #2563eb; font-weight: 500;">
                            <?php echo $row['diagnosa']; ?>
                        </td>

                        <td style="color: #059669; font-weight: 500;">
                            <?php echo $row['resep_obat']; ?>
                        </td>

                        <td>
                            <?php if (!empty($row['kunjungan_berikutnya'])) { ?>
                                <span style="background: #fef3c7; color: #b45309; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.85rem;">
                                    <i class="fas fa-calendar-alt"></i> <?php echo date('d-m-Y', strtotime($row['kunjungan_berikutnya'])); ?>
                                </span>
                            <?php } else { ?>
                                <span style="color: #9ca3af;">-</span>
                            <?php } ?>
                        </td>

                        <td>
                            <?php if (!empty($row['kunjungan_berikutnya']) && !empty($row['nama_pasien'])) { ?>
                                <a href="cetak_surat_kunjungan.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-primary" style="background:#2563eb; color:white; padding:6px 12px; border-radius:4px; text-decoration:none; display:inline-flex; align-items:center; gap:5px; font-size: 0.85rem;">
                                    <i class="fas fa-print"></i> Cetak Surat
                                </a>
                            <?php } else { ?>
                                <span style="color: #9ca3af; font-size: 0.85rem;">-</span>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../layout/footer.php" ?>