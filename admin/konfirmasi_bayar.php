<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../auth/login.php");
    exit();
}

$id = intval($_GET['id']);
$q  = mysqli_query($koneksi, "SELECT t.*, a.no_antrian FROM transaksi t LEFT JOIN antrian a ON t.id_antrian = a.id WHERE t.id_transaksi = '$id'");
$trx = mysqli_fetch_assoc($q);

if (!$trx) {
    echo "<script>alert('Data tidak ditemukan!');window.location='transaksi.php';</script>";
    exit();
}

if (isset($_POST['konfirmasi'])) {
    $metode = mysqli_real_escape_string($koneksi, $_POST['metode_bayar']);
    mysqli_query($koneksi, "UPDATE transaksi SET status_bayar='Lunas', metode_bayar='$metode' WHERE id_transaksi='$id'");
    header("location:cetak_struk_bayar.php?id=$id");
    exit();
}
?>
<?php include "../layout/header.php" ?>

<div class="page-header">
    <h2><i class="fas fa-check-circle"></i> Konfirmasi Pembayaran</h2>
    <a href="transaksi.php" class="btn" style="background:#f3f4f6;color:#374151;">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

<div class="form-card" style="max-width:600px;margin:0 auto;">
    <!-- Info Transaksi -->
    <div style="background:#d1fae5;padding:20px;border-radius:10px;margin-bottom:24px;border-left:5px solid #10b981;">
        <h3 style="margin:0 0 12px;color:#065f46;"><i class="fas fa-receipt"></i> Detail Transaksi</h3>
        <table style="width:100%;font-size:.9rem;">
            <tr>
                <td style="padding:5px 0;color:#6b7280;width:140px;">No. Antrian</td>
                <td style="font-weight:600;">#<?php echo $trx['no_antrian'] ?? '-'; ?></td>
            </tr>
            <tr>
                <td style="padding:5px 0;color:#6b7280;">Nama Pasien</td>
                <td style="font-weight:600;"><?php echo htmlspecialchars($trx['nama_pasien']); ?></td>
            </tr>
            <tr>
                <td style="padding:5px 0;color:#6b7280;">Tindakan</td>
                <td><?php echo htmlspecialchars($trx['tindakan'] ?? '-'); ?></td>
            </tr>
            <tr>
                <td style="padding:5px 0;color:#6b7280;">Total Biaya</td>
                <td style="font-size:1.3rem;font-weight:700;color:#059669;">
                    Rp <?php echo number_format($trx['biaya'], 0, ',', '.'); ?>
                </td>
            </tr>
        </table>
    </div>

    <form method="POST">
        <div class="form-group">
            <label class="form-label">Metode Pembayaran</label>
            <select name="metode_bayar" class="form-control" required>
                <option value="Tunai" <?php echo $trx['metode_bayar'] == 'Tunai' ? 'selected' : ''; ?>>Tunai / Cash</option>
                <option value="Transfer" <?php echo $trx['metode_bayar'] == 'Transfer' ? 'selected' : ''; ?>>Transfer Bank</option>
                <option value="BPJS" <?php echo $trx['metode_bayar'] == 'BPJS' ? 'selected' : ''; ?>>BPJS</option>
            </select>
        </div>

        <div style="background:#fef3c7;padding:15px;border-radius:8px;margin-bottom:20px;font-size:.875rem;color:#92400e;">
            <i class="fas fa-info-circle"></i>
            Klik <strong>"Konfirmasi Lunas"</strong> untuk menandai pembayaran ini sebagai LUNAS dan mencetak struk.
        </div>

        <div style="display:flex;gap:12px;">
            <button type="submit" name="konfirmasi" class="btn btn-primary" style="flex:1;justify-content:center;">
                <i class="fas fa-check-circle"></i> Konfirmasi Lunas & Cetak Struk
            </button>
        </div>
    </form>
</div>

<?php include "../layout/footer.php" ?>