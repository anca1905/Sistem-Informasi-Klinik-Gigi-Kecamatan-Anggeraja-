<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../auth/login.php");
    exit();
}

if ($_SESSION['role'] != 'Admin') {
    echo "<script>alert('Akses Ditolak! Hanya Admin yang dapat mengelola jadwal.'); window.location='dashboard.php';</script>";
    exit();
}

// Proses Tambah Jadwal
if (isset($_POST['simpan'])) {
    $nama = $_POST['nama'];
    $hari = $_POST['hari'];
    $mulai = $_POST['mulai'];
    $selesai = $_POST['selesai'];
    $status = $_POST['status'];

    mysqli_query($koneksi, "INSERT INTO jadwal_dokter (nama_dokter, hari, jam_mulai, jam_selesai, status) VALUES ('$nama', '$hari', '$mulai', '$selesai', '$status')");
    echo "<script>alert('Jadwal tersimpan'); window.location='kelola_jadwal.php';</script>";
}

// Proses Hapus
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM jadwal_dokter WHERE id='$id'");
    echo "<script>window.location='kelola_jadwal.php';</script>";
}

$nama_petugas = $_SESSION['login_user'];
?>

<?php include "../layout/header.php" ?>

<div class="table-container">
    <div class="table-header">
        <h3><i class="fas fa-calendar-alt"></i> Kelola Jadwal Dokter</h3>
    </div>

    <div style="padding: 20px; background: #f9fafb; border-bottom: 1px solid #eee;">
        <form method="POST" style="display: flex; gap: 10px; flex-wrap: wrap;">

            <select name="nama" required style="flex: 2; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                <option value="">- Pilih Dokter -</option>
                <?php
                $q_dokter = mysqli_query($koneksi, "SELECT * FROM admin WHERE role='Dokter'");
                while ($d = mysqli_fetch_array($q_dokter)) {
                    echo "<option value='" . $d['nama_lengkap'] . "'>" . $d['nama_lengkap'] . "</option>";
                }
                ?>
            </select>

            <select name="hari" required style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                <option value="">- Hari -</option>
                <option>Senin</option>
                <option>Selasa</option>
                <option>Rabu</option>
                <option>Kamis</option>
                <option>Jumat</option>
                <option>Sabtu</option>
            </select>

            <input type="time" name="mulai" required style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
            <span style="align-self: center;">s/d</span>
            <input type="time" name="selesai" required style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">

            <select name="status" style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                <option value="Praktek">Praktek</option>
                <option value="Libur">Libur/Cuti</option>
            </select>

            <button type="submit" name="simpan" class="btn btn-primary">Simpan</button>
        </form>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Hari</th>
                    <th>Jam Praktek</th>
                    <th>Nama Dokter</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Urutkan berdasarkan FIELD hari biar senin, selasa, dst berurutan (trik SQL simpel)
                $query = mysqli_query($koneksi, "SELECT * FROM jadwal_dokter ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu')");
                while ($row = mysqli_fetch_array($query)) {
                ?>
                    <tr>
                        <td style="font-weight: bold;"><?php echo $row['hari']; ?></td>
                        <td>
                            <i class="far fa-clock" style="color: #6b7280; margin-right: 5px;"></i>
                            <?php echo date('H:i', strtotime($row['jam_mulai'])) . ' - ' . date('H:i', strtotime($row['jam_selesai'])); ?>
                        </td>
                        <td><?php echo $row['nama_dokter']; ?></td>
                        <td>
                            <?php if ($row['status'] == 'Praktek') { ?>
                                <span class="badge badge-done">Praktek</span>
                            <?php } else { ?>
                                <span class="badge badge-wait" style="background: #fee2e2; color: #b91c1c;">Libur</span>
                            <?php } ?>
                        </td>
                        <td>
                            <a href="kelola_jadwal.php?hapus=<?php echo $row['id']; ?>" style="color: #ef4444;" onclick="return confirm('Hapus jadwal ini?')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../layout/footer.php" ?>