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
    $biaya  = isset($_POST['biaya']) ? (float)str_replace(['.', ','], ['', ''], $_POST['biaya']) : (float)$trx['biaya'];
    if ($biaya < 0) $biaya = 0;

    mysqli_query($koneksi, "UPDATE transaksi SET status_bayar='Lunas', metode_bayar='$metode', biaya='$biaya' WHERE id_transaksi='$id'");

    // Sinkronkan ke rekam medis jika terhubung
    if (!empty($trx['id_rekam_medis'])) {
        $id_rm_sync = (int)$trx['id_rekam_medis'];
        mysqli_query($koneksi, "UPDATE rekam_medis SET biaya='$biaya' WHERE id='$id_rm_sync'");
    }

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
                <td style="padding:5px 0;color:#6b7280;">Biaya Pemeriksaan</td>
                <td style="font-size:1.2rem;font-weight:700;color:#059669;" id="label-biaya">
                    Rp <?php echo number_format($trx['biaya'], 0, ',', '.'); ?>
                </td>
            </tr>
        </table>
    </div>

    <form method="POST">
        <div class="form-group" style="margin-bottom:16px;">
            <label class="form-label"><i class="fas fa-money-bill-wave"></i> Total Biaya (Rp)</label>
            <input type="text" name="biaya" id="input_biaya" class="form-control"
                value="<?php echo number_format($trx['biaya'], 0, ',', '.'); ?>"
                oninput="formatBiaya(this); updateLabel(this.value);" required>
            <small style="color:#6b7280;font-size:0.8rem;display:block;margin-top:4px;">*Otomatis terisi dari hasil pemeriksaan dokter, dapat disesuaikan jika perlu.</small>
        </div>

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
            Klik <strong>"Konfirmasi Lunas & Cetak Struk"</strong> untuk menandai transaksi ini sebagai LUNAS dan mencetak bukti pembayaran.
        </div>

        <div style="display:flex;gap:12px;">
            <button type="submit" name="konfirmasi" class="btn btn-primary" style="flex:1;justify-content:center;">
                <i class="fas fa-check-circle"></i> Konfirmasi Lunas & Cetak Struk
            </button>
        </div>
    </form>
</div>

<script>
    function formatBiaya(input) {
        let val = input.value.replace(/\D/g, '');
        input.value = val ? val.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
    }

    function updateLabel(val) {
        document.getElementById('label-biaya').innerText = 'Rp ' + (val || '0');
    }
</script>

<?php include "../layout/footer.php" ?>