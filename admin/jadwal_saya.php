<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../auth/login.php");
    exit();
}

if ($_SESSION['role'] != 'Dokter') {
    echo "<script>alert('Akses Ditolak! Ini halaman khusus Dokter.'); window.location='dashboard.php';</script>";
    exit();
}

$nama_petugas = $_SESSION['login_user'];
$nama_lengkap_dokter = $_SESSION['nama_lengkap'];
?>

<?php include "../layout/header.php" ?>

<div class="table-container">
    <div class="table-header">
        <h3><i class="fas fa-calendar-check"></i> Jadwal Praktek Saya</h3>
    </div>

    <div style="padding: 20px; background: #f9fafb; border-bottom: 1px solid #eee;">
        <p style="color: #4b5563; margin: 0;">Berikut adalah jadwal praktek Anda, dr. <strong><?php echo $nama_lengkap_dokter; ?></strong>. Jika ada perubahan jadwal, harap hubungi Staff/Admin klinik.</p>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Hari</th>
                    <th>Nama Dokter</th>
                    <th>Jam Praktek</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Cari jadwal dengan JOIN dokter
                $nama_safe = mysqli_real_escape_string($koneksi, $nama_lengkap_dokter);
                $query = mysqli_query($koneksi, "
                    SELECT j.*, d.nama_dokter 
                    FROM jadwal_dokter j 
                    LEFT JOIN dokter d ON j.id_dokter = d.id_dokter 
                    WHERE d.nama_dokter LIKE '%$nama_safe%'
                    ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), j.jam_mulai
                ");

                // Jika nama dokter generik atau tidak spesifik, tampilkan seluruh jadwal
                if (!$query || mysqli_num_rows($query) == 0) {
                    $query = mysqli_query($koneksi, "
                        SELECT j.*, d.nama_dokter 
                        FROM jadwal_dokter j 
                        LEFT JOIN dokter d ON j.id_dokter = d.id_dokter 
                        ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), j.jam_mulai
                    ");
                }

                if ($query && mysqli_num_rows($query) > 0) {
                    while ($row = mysqli_fetch_array($query)) {
                ?>
                        <tr>
                            <td style="font-weight: bold;"><?php echo $row['hari']; ?></td>
                            <td>dr. <?php echo htmlspecialchars($row['nama_dokter'] ?? '-'); ?></td>
                            <td>
                                <i class="far fa-clock" style="color: #6b7280; margin-right: 5px;"></i>
                                <?php echo date('H:i', strtotime($row['jam_mulai'])) . ' - ' . date('H:i', strtotime($row['jam_selesai'])); ?>
                            </td>
                            <td>
                                <?php if ($row['status'] == 'Praktek') { ?>
                                    <span class="badge badge-done">Praktek</span>
                                <?php } else { ?>
                                    <span class="badge badge-wait" style="background: #fee2e2; color: #b91c1c;">Libur</span>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 20px; color: #6b7280;">
                            Belum ada jadwal praktek yang ditentukan.
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../layout/footer.php" ?>