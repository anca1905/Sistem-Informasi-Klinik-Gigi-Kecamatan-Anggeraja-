<?php
if (!headers_sent()) {
    header('Content-Type: application/json');
}
require_once __DIR__ . '/../config/koneksi.php';

$keyword = isset($_GET['q']) ? mysqli_real_escape_string($koneksi, trim($_GET['q'])) : '';

if (empty($keyword)) {
    echo json_encode(['status' => 'error', 'message' => 'Keyword pencarian kosong']);
    exit();
}

// Cari data pasien berdasarkan kode_pasien (No. RM), id, nik, atau no_telepon
$query = mysqli_query($koneksi, "
    SELECT p.* 
    FROM pasien p 
    WHERE p.kode_pasien = '$keyword' 
       OR p.kode_pasien LIKE '%$keyword%'
       OR p.nik = '$keyword' 
       OR p.no_telepon = '$keyword'
       OR p.id = '$keyword'
       OR p.nama LIKE '%$keyword%'
    ORDER BY 
       CASE 
         WHEN p.kode_pasien = '$keyword' THEN 1 
         WHEN p.kode_pasien LIKE '$keyword%' THEN 2
         WHEN p.nik = '$keyword' THEN 3
         ELSE 4 
       END,
       p.id DESC 
    LIMIT 5
");

if (mysqli_num_rows($query) == 0) {
    echo json_encode(['status' => 'not_found', 'message' => 'Data pasien tidak ditemukan']);
    exit();
}

$results = [];
while ($pasien = mysqli_fetch_assoc($query)) {
    $id_pasien = $pasien['id'];

    // Ambil rekam medis & kontrol terakhir jika ada
    $q_rm = mysqli_query($koneksi, "
        SELECT rm.*, d.nama_dokter 
        FROM rekam_medis rm 
        LEFT JOIN dokter d ON rm.id_dokter = d.id_dokter 
        WHERE rm.id_pasien = '$id_pasien' 
        ORDER BY rm.id DESC 
        LIMIT 1
    ");
    $last_rm = mysqli_fetch_assoc($q_rm);

    // Cek apakah sudah punya antrian aktif hari ini (Pencegahan redundansi)
    $hari_ini = date('Y-m-d');
    $q_antrian = mysqli_query($koneksi, "
        SELECT a.id, a.no_antrian, a.status, d.nama_dokter 
        FROM antrian a 
        LEFT JOIN dokter d ON a.id_dokter = d.id_dokter
        WHERE a.id_pasien = '$id_pasien' 
          AND DATE(a.waktu_daftar) = '$hari_ini' 
          AND a.status IN ('Menunggu', 'Dilayani')
        LIMIT 1
    ");
    $active_queue = mysqli_fetch_assoc($q_antrian);

    $results[] = [
        'id' => (int)$pasien['id'],
        'kode_pasien' => $pasien['kode_pasien'] ?: ('RM' . str_pad($pasien['id'], 4, '0', STR_PAD_LEFT)),
        'nama' => $pasien['nama'],
        'tanggal_lahir' => $pasien['tanggal_lahir'],
        'tanggal_lahir_formatted' => !empty($pasien['tanggal_lahir']) ? date('d-m-Y', strtotime($pasien['tanggal_lahir'])) : '-',
        'jenis_kelamin' => $pasien['jenis_kelamin'] ?: '-',
        'nik' => $pasien['nik'] ?: '-',
        'no_telepon' => $pasien['no_telepon'] ?: '-',
        'alamat' => $pasien['alamat'] ?: '-',
        'last_visit' => $last_rm ? [
            'tanggal' => date('d-m-Y', strtotime($last_rm['tanggal_periksa'])),
            'dokter' => $last_rm['nama_dokter'] ? 'dr. ' . $last_rm['nama_dokter'] : '-',
            'diagnosa' => $last_rm['diagnosa'] ?: '-',
            'tindakan' => $last_rm['tindakan'] ?: '-',
            'kunjungan_berikutnya' => (!empty($last_rm['kunjungan_berikutnya']) && $last_rm['kunjungan_berikutnya'] > '1970-01-01')
                ? date('d-m-Y', strtotime($last_rm['kunjungan_berikutnya']))
                : null
        ] : null,
        'active_queue' => $active_queue ? [
            'id' => (int)$active_queue['id'],
            'no_antrian' => (int)$active_queue['no_antrian'],
            'status' => $active_queue['status'],
            'dokter' => $active_queue['nama_dokter'] ? 'dr. ' . $active_queue['nama_dokter'] : '-'
        ] : null
    ];
}

echo json_encode([
    'status' => 'success',
    'total' => count($results),
    'data' => $results[0], // data teratas / exact match
    'list' => $results
]);
