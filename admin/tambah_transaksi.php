<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../auth/login.php");
    exit();
}

// Ambil daftar antrian yang sudah selesai pemeriksaan (status='Selesai')
// dan belum punya transaksi
$q_antrian = mysqli_query($koneksi, "
    SELECT a.id, a.no_antrian, a.nama_pendaftar, a.keluhan, rm.id AS id_rm, rm.tindakan, rm.diagnosa
    FROM antrian a
    LEFT JOIN rekam_medis rm ON rm.id_pasien = a.id_pasien AND DATE(rm.tanggal_periksa) = DATE(a.waktu_daftar)
    LEFT JOIN transaksi t ON t.id_antrian = a.id
    WHERE a.status = 'Selesai'
    AND t.id_transaksi IS NULL
    ORDER BY a.waktu_daftar DESC
");

// Pre-fill jika dipanggil dari rekam medis
$prefill_id_antrian = isset($_GET['id_antrian']) ? intval($_GET['id_antrian']) : 0;
$prefill_data = null;
if ($prefill_id_antrian > 0) {
    $q_pre = mysqli_query($koneksi, "
        SELECT a.id, a.no_antrian, a.nama_pendaftar, rm.id AS id_rm, rm.tindakan, rm.diagnosa
        FROM antrian a
        LEFT JOIN rekam_medis rm ON rm.id_pasien = a.id_pasien
        WHERE a.id = '$prefill_id_antrian'
        ORDER BY rm.id DESC LIMIT 1
    ");
    $prefill_data = mysqli_fetch_assoc($q_pre);
}

// Simpan transaksi
if (isset($_POST['simpan_transaksi'])) {
    $id_antrian    = intval($_POST['id_antrian']);
    $id_rekam_medis = !empty($_POST['id_rekam_medis']) ? intval($_POST['id_rekam_medis']) : 'NULL';
    $nama_pasien   = mysqli_real_escape_string($koneksi, $_POST['nama_pasien']);
    $tindakan      = mysqli_real_escape_string($koneksi, $_POST['tindakan']);
    $biaya         = intval(str_replace(['.', ','], ['', ''], $_POST['biaya']));
    $metode_bayar  = mysqli_real_escape_string($koneksi, $_POST['metode_bayar']);
    $status_bayar  = mysqli_real_escape_string($koneksi, $_POST['status_bayar']);

    $id_rm_val = ($id_rekam_medis === 'NULL') ? 'NULL' : "'$id_rekam_medis'";

    $insert = mysqli_query($koneksi, "
        INSERT INTO transaksi (id_antrian, id_rekam_medis, nama_pasien, tindakan, biaya, metode_bayar, status_bayar)
        VALUES ('$id_antrian', $id_rm_val, '$nama_pasien', '$tindakan', '$biaya', '$metode_bayar', '$status_bayar')
    ");

    if ($insert) {
        $id_baru = mysqli_insert_id($koneksi);
        if ($status_bayar == 'Lunas') {
            header("location:cetak_struk_bayar.php?id=$id_baru");
        } else {
            header("location:transaksi.php?sukses=1");
        }
        exit();
    } else {
        $error_msg = "Gagal menyimpan transaksi: " . mysqli_error($koneksi);
    }
}
?>
<?php include "../layout/header.php" ?>

<div class="page-header">
    <h2><i class="fas fa-plus-circle"></i> Tambah Transaksi</h2>
    <a href="transaksi.php" class="btn" style="background:#f3f4f6;color:#374151;">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

<?php if (isset($error_msg)): ?>
    <div style="background:#fee2e2;color:#991b1b;padding:15px;border-radius:10px;margin-bottom:20px;">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
    </div>
<?php endif; ?>

<div class="form-card">
    <form method="POST" id="form-transaksi">
        <!-- Pilih Antrian -->
        <div class="form-group">
            <label class="form-label"><i class="fas fa-list-ol"></i> Pilih Antrian (Pasien Selesai Diperiksa)</label>
            <select name="id_antrian" id="select_antrian" class="form-control" onchange="isiOtomatis(this)" required>
                <option value="">-- Pilih Antrian --</option>
                <?php if ($prefill_data): ?>
                    <option value="<?php echo $prefill_data['id']; ?>" data-nama="<?php echo htmlspecialchars($prefill_data['nama_pendaftar']); ?>"
                        data-tindakan="<?php echo htmlspecialchars($prefill_data['tindakan'] ?? $prefill_data['diagnosa'] ?? ''); ?>"
                        data-idpm="<?php echo $prefill_data['id_rm'] ?? ''; ?>" selected>
                        #<?php echo $prefill_data['no_antrian']; ?> — <?php echo htmlspecialchars($prefill_data['nama_pendaftar']); ?>
                    </option>
                <?php endif; ?>
                <?php while ($a = mysqli_fetch_assoc($q_antrian)): ?>
                    <option value="<?php echo $a['id']; ?>"
                        data-nama="<?php echo htmlspecialchars($a['nama_pendaftar']); ?>"
                        data-tindakan="<?php echo htmlspecialchars($a['tindakan'] ?? $a['diagnosa'] ?? ''); ?>"
                        data-idpm="<?php echo $a['id_rm'] ?? ''; ?>">
                        #<?php echo $a['no_antrian']; ?> — <?php echo htmlspecialchars($a['nama_pendaftar']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <input type="hidden" name="id_rekam_medis" id="id_rekam_medis"
            value="<?php echo $prefill_data['id_rm'] ?? ''; ?>">

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Nama Pasien</label>
                <input type="text" name="nama_pasien" id="nama_pasien" class="form-control"
                    value="<?php echo htmlspecialchars($prefill_data['nama_pendaftar'] ?? ''); ?>"
                    placeholder="Otomatis terisi" readonly required>
            </div>
            <div class="form-group">
                <label class="form-label">Metode Pembayaran</label>
                <select name="metode_bayar" class="form-control" required>
                    <option value="Tunai">Tunai</option>
                    <option value="Transfer">Transfer Bank</option>
                    <option value="BPJS">BPJS</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Tindakan / Keterangan</label>
            <textarea name="tindakan" id="tindakan" class="form-control" rows="3"
                placeholder="Otomatis dari rekam medis"><?php echo htmlspecialchars($prefill_data['tindakan'] ?? $prefill_data['diagnosa'] ?? ''); ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><i class="fas fa-money-bill-wave"></i> Biaya (Rp)</label>
                <input type="text" name="biaya" id="biaya" class="form-control"
                    placeholder="Contoh: 150000" required oninput="formatBiaya(this)">
            </div>
            <div class="form-group">
                <label class="form-label">Status Pembayaran</label>
                <select name="status_bayar" class="form-control" required>
                    <option value="Belum Bayar">Belum Bayar</option>
                    <option value="Lunas">Lunas (Langsung Cetak Struk)</option>
                </select>
            </div>
        </div>

        <div style="margin-top:24px;display:flex;gap:12px;">
            <button type="submit" name="simpan_transaksi" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan Transaksi
            </button>
            <a href="transaksi.php" class="btn" style="background:#f3f4f6;color:#374151;">Batal</a>
        </div>
    </form>
</div>

<script>
    function isiOtomatis(sel) {
        const opt = sel.options[sel.selectedIndex];
        document.getElementById('nama_pasien').value = opt.dataset.nama || '';
        document.getElementById('tindakan').value = opt.dataset.tindakan || '';
        document.getElementById('id_rekam_medis').value = opt.dataset.idpm || '';
    }

    function formatBiaya(input) {
        let val = input.value.replace(/\D/g, '');
        input.value = val.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    // Auto-fill jika ada prefill dari URL
    window.addEventListener('DOMContentLoaded', function() {
        const sel = document.getElementById('select_antrian');
        if (sel && sel.value) isiOtomatis(sel);
    });
</script>

<?php include "../layout/footer.php" ?>