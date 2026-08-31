<?php
session_start();
// Sesuaikan path ini jika perlu
include '../config/koneksi.php';

// --- 1. LOGIC PHP (Sama seperti sebelumnya) ---

// Cek Login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:login.php");
    exit();
}

// Logic Update Status Antrian
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $aksi = $_GET['aksi'];

    if ($aksi == 'panggil') {
        $status_baru = 'Dilayani';
    } elseif ($aksi == 'selesai') {
        $status_baru = 'Selesai';
    }

    mysqli_query($koneksi, "UPDATE antrian SET status='$status_baru' WHERE id='$id'");
    header("location:dashboard.php");
}

// Ambil Data Statistik
$hari_ini = date('Y-m-d');
$total_antrian = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM antrian WHERE DATE(waktu_daftar) = '$hari_ini'"));
$sisa_antrian = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM antrian WHERE DATE(waktu_daftar) = '$hari_ini' AND status='Menunggu'"));
$sedang_dilayani = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM antrian WHERE DATE(waktu_daftar) = '$hari_ini' AND status='Dilayani'"));

// Data User Login
$nama_petugas = $_SESSION['login_user'];
?>


<?php include "../layout/header.php" ?>

<div class="stats-grid">
    <div class="stat-card card-green">
        <div class="stat-text">
            <p>Total Pasien Hari Ini</p>
            <h3><?php echo $total_antrian; ?></h3>
        </div>
        <div class="icon-box icon-green">
            <i class="fas fa-users"></i>
        </div>
    </div>
    <div class="stat-card card-yellow">
        <div class="stat-text">
            <p>Antrian Menunggu</p>
            <h3><?php echo $sisa_antrian; ?></h3>
        </div>
        <div class="icon-box icon-yellow">
            <i class="fas fa-clock"></i>
        </div>
    </div>
    <div class="stat-card card-blue">
        <div class="stat-text">
            <p>Sedang Diperiksa</p>
            <h3><?php echo $sedang_dilayani; ?></h3>
        </div>
        <div class="icon-box icon-blue">
            <i class="fas fa-user-md"></i>
        </div>
    </div>
</div>

<div class="table-container">
    <div class="table-header">
        <h3 style="font-size: 1rem; color: #374151;">
            <i class="fas fa-list-ol" style="margin-right: 10px;"></i>
            Daftar Antrian (<?php echo date('d-m-Y'); ?>)
        </h3>
        <span class="badge badge-done">Realtime</span>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>No. Antrian</th>
                    <th>Nama Pasien</th>
                    <th>Keluhan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $where_query = "DATE(waktu_daftar) = '$hari_ini'";
                if ($role_active == 'Dokter') {
                    $where_query .= " AND status != 'Menunggu'";
                }
                $query = mysqli_query($koneksi, "SELECT * FROM antrian WHERE $where_query ORDER BY no_antrian ASC");

                if (mysqli_num_rows($query) > 0) {
                    while ($row = mysqli_fetch_array($query)) {
                ?>
                        <tr>
                            <td>
                                <span class="queue-number">#<?php echo $row['no_antrian']; ?></span>
                            </td>
                            <td><b><?php echo $row['nama_pendaftar']; ?></b></td>
                            <td style="color: #6b7280; font-size: 0.9rem;">
                                <?php echo substr($row['keluhan'], 0, 50) . '...'; ?>
                            </td>
                            <td>
                                <?php if ($row['status'] == 'Menunggu') { ?>
                                    <span class="badge badge-wait">
                                        <span class="dot" style="background: #d97706;"></span> Menunggu
                                    </span>
                                <?php } elseif ($row['status'] == 'Dilayani') { ?>
                                    <span class="badge badge-process">
                                        <span class="dot" style="background: #2563eb;"></span> Diperiksa
                                    </span>
                                <?php } else { ?>
                                    <span class="badge badge-done">
                                        <span class="dot" style="background: #059669;"></span> Selesai
                                    </span>
                                <?php } ?>
                            </td>
                            <td>
                                <?php if ($row['status'] == 'Menunggu') { ?>
                                    <?php if ($role_active == 'Admin') { ?>
                                        <a href="dashboard.php?aksi=panggil&id=<?php echo $row['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-bullhorn"></i> Panggil
                                        </a>
                                    <?php } else { ?>
                                        <span style="color: #6b7280; font-weight: bold; font-size: 0.85rem;">
                                            <i class="fas fa-clock"></i> Belum Dipanggil
                                        </span>
                                    <?php } ?>
                                <?php } elseif ($row['status'] == 'Dilayani') { ?>
                                    <?php if ($role_active == 'Dokter') { ?>
                                        <a href="input_rekam_medis.php?id=<?php echo $row['id']; ?>" class="btn btn-success">
                                            <i class="fas fa-stethoscope"></i> Periksa & Obat
                                        </a>
                                    <?php } else { ?>
                                        <span style="color: #2563eb; font-weight: bold; font-size: 0.85rem;">
                                            <i class="fas fa-bullhorn"></i> Dipanggil (Diperiksa)
                                        </span>
                                    <?php } ?>
                                <?php } else { ?>
                                    <span style="color: var(--primary-dark); font-weight: bold; font-size: 0.85rem;">
                                        <i class="fas fa-check-circle"></i> Tuntas
                                    </span>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: #9ca3af;">
                            <i class="fas fa-coffee" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                            Belum ada pasien hari ini.
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../layout/footer.php" ?>