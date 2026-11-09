<?php
require_once __DIR__ . '/config/koneksi.php';

// Data riil dari tempat penelitian (Klinik Gigi Kecamatan Anggeraja)
$data_selasa = [
    ['no' => 1,  'nama' => 'Riska',               'alamat' => 'Cakke',    'umur' => 25, 'kelamin' => 'Perempuan', 'keluhan' => 'Pemasangan kawat gigi'],
    ['no' => 2,  'nama' => 'Aznan',               'alamat' => 'Cakke',    'umur' => 24, 'kelamin' => 'Laki-laki',  'keluhan' => 'Tambal gigi'],
    ['no' => 3,  'nama' => 'Misriani',            'alamat' => 'Sossok',   'umur' => 35, 'kelamin' => 'Perempuan', 'keluhan' => 'Pemasangan kawat gigi'],
    ['no' => 4,  'nama' => 'Siti Nuriya',         'alamat' => 'Bungin',   'umur' => 19, 'kelamin' => 'Perempuan', 'keluhan' => 'Tambal gigi'],
    ['no' => 5,  'nama' => 'Indah',               'alamat' => 'Sossok',   'umur' => 22, 'kelamin' => 'Perempuan', 'keluhan' => 'Mumifikasi'],
    ['no' => 6,  'nama' => 'Wiwi Permatasari',    'alamat' => 'Mampu',    'umur' => 21, 'kelamin' => 'Perempuan', 'keluhan' => 'Scaling'],
    ['no' => 7,  'nama' => 'Selmawati',           'alamat' => 'Kalosi',   'umur' => 22, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 8,  'nama' => 'Nur Layla',           'alamat' => 'Bolang',   'umur' => 20, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 9,  'nama' => 'Rauda',               'alamat' => 'Cakke',    'umur' => 25, 'kelamin' => 'Perempuan', 'keluhan' => 'Pemasangan kawat gigi'],
    ['no' => 10, 'nama' => 'Ainun',               'alamat' => 'Sossok',   'umur' => 20, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 11, 'nama' => 'Mulyanti',            'alamat' => 'Cakke',    'umur' => 30, 'kelamin' => 'Perempuan', 'keluhan' => 'Cabut gigi'],
    ['no' => 12, 'nama' => 'Annisa Salsa',        'alamat' => 'Kalosi',   'umur' => 19, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 13, 'nama' => 'Irmayanti',           'alamat' => 'Belalang', 'umur' => 25, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 14, 'nama' => 'Yuslina',             'alamat' => 'Cakke',    'umur' => 28, 'kelamin' => 'Perempuan', 'keluhan' => 'Tambal gigi'],
    ['no' => 15, 'nama' => 'Sahra',               'alamat' => 'Manggugu', 'umur' => 18, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 16, 'nama' => 'Nur Sam',             'alamat' => 'Mampu',    'umur' => 23, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 17, 'nama' => 'Anindita',            'alamat' => 'Sossok',   'umur' => 21, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 18, 'nama' => 'Iksan',               'alamat' => 'Kalosi',   'umur' => 24, 'kelamin' => 'Laki-laki',  'keluhan' => 'Kontrol behel'],
    ['no' => 19, 'nama' => 'Mirna',               'alamat' => 'Masalle',  'umur' => 22, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 20, 'nama' => 'Dita',                'alamat' => 'Masalle',  'umur' => 21, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 21, 'nama' => 'Masrani',             'alamat' => 'Kotu',     'umur' => 30, 'kelamin' => 'Laki-laki',  'keluhan' => 'Cabut gigi'],
    ['no' => 22, 'nama' => 'Sarmawati',           'alamat' => 'Belalang', 'umur' => 32, 'kelamin' => 'Perempuan', 'keluhan' => 'Tambal gigi'],
    ['no' => 23, 'nama' => 'Alfia',               'alamat' => 'Cakke',    'umur' => 20, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 24, 'nama' => 'Nurul Ain',           'alamat' => 'Buntu',    'umur' => 16, 'kelamin' => 'Perempuan', 'keluhan' => 'Cabut gigi'],
    ['no' => 25, 'nama' => 'Aprilianti',          'alamat' => 'Manggugu', 'umur' => 22, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 26, 'nama' => 'Dinda',               'alamat' => 'Sossok',   'umur' => 23, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
];

$data_rabu = [
    ['no' => 1,  'nama' => 'Warda',               'alamat' => 'Mampu',    'umur' => 30, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 2,  'nama' => 'Zahra',               'alamat' => 'Kasambi',  'umur' => 26, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 3,  'nama' => 'Rahmadani',           'alamat' => 'Kalosi',   'umur' => 21, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 4,  'nama' => 'Mifta',               'alamat' => 'Belalang', 'umur' => 24, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 5,  'nama' => 'Dila',                'alamat' => 'Sossok',   'umur' => 20, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 6,  'nama' => 'Kardopa',             'alamat' => 'Manggugu', 'umur' => 19, 'kelamin' => 'Laki-laki',  'keluhan' => 'Kontrol behel'],
    ['no' => 7,  'nama' => 'Hasnawati',           'alamat' => 'Belalang', 'umur' => 30, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 8,  'nama' => 'Nurul Sagita',        'alamat' => 'Cakke',    'umur' => 32, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 9,  'nama' => 'Filda',               'alamat' => 'Belalang', 'umur' => 34, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 10, 'nama' => 'Widia Astuti',        'alamat' => 'BTP',      'umur' => 26, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol'],
    ['no' => 11, 'nama' => 'Dita Natasya',        'alamat' => 'Mampu',    'umur' => 24, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 12, 'nama' => 'Suti',                'alamat' => 'Mampu',    'umur' => 38, 'kelamin' => 'Perempuan', 'keluhan' => 'Tambal gigi'],
    ['no' => 13, 'nama' => 'Irja',                'alamat' => 'Mampu',    'umur' => 27, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol'],
    ['no' => 14, 'nama' => 'Fitriani Roha',       'alamat' => 'Masalle',  'umur' => 23, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 15, 'nama' => 'Nurul Ulfiana',       'alamat' => 'Masalle',  'umur' => 22, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 16, 'nama' => 'Ika Syafitri',        'alamat' => 'Mampu',    'umur' => 35, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 17, 'nama' => 'Widia Syari Satriani', 'alamat' => 'Mampu',    'umur' => 23, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 18, 'nama' => 'Riska',               'alamat' => 'Cakke',    'umur' => 25, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 19, 'nama' => 'Aslin',               'alamat' => 'Manggugu', 'umur' => 17, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol behel'],
    ['no' => 20, 'nama' => 'Hendra',              'alamat' => 'Kalosi',   'umur' => 24, 'kelamin' => 'Laki-laki',  'keluhan' => 'Kontrol behel'],
    ['no' => 21, 'nama' => 'Sulistia',            'alamat' => 'Kotu',     'umur' => 23, 'kelamin' => 'Perempuan', 'keluhan' => 'Cabut gigi'],
    ['no' => 22, 'nama' => 'Widya',               'alamat' => 'Baroko',   'umur' => 31, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol'],
    ['no' => 23, 'nama' => 'Yani',                'alamat' => 'Baroko',   'umur' => 29, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol'],
    ['no' => 24, 'nama' => 'Sukmawati',           'alamat' => 'Kalimbua', 'umur' => 34, 'kelamin' => 'Perempuan', 'keluhan' => 'Kontrol'],
];

// Helper detail medis berdasarkan keluhan
function getMedicalDetails($keluhan) {
    $k = strtolower($keluhan);
    if (strpos($k, 'kawat') !== false || strpos($k, 'pasang') !== false) {
        return [
            'diagnosa' => 'Maloklusi Gigi Kelas I (Gigi Kurang Rapi)',
            'tindakan' => 'Pemasangan Bracket & Kawat Gigi Orthodonti',
            'resep'    => 'Asam Mefenamat 500mg (3x1), Obat Kumur Chlorhexidine',
            'biaya'    => 3500000,
            'kontrol'  => 21
        ];
    } elseif (strpos($k, 'tambal') !== false) {
        return [
            'diagnosa' => 'Karies Media Gigi Molar (Gigi Berlubang)',
            'tindakan' => 'Preparasi Kavitas & Penambalan Gigi Light Cured Komposit',
            'resep'    => 'Paracetamol 500mg (3x1)',
            'biaya'    => 200000,
            'kontrol'  => 30
        ];
    } elseif (strpos($k, 'mumi') !== false) {
        return [
            'diagnosa' => 'Pulpitis Irreversibel Gigi Premolar',
            'tindakan' => 'Devitalisasi Pulpa & Aplikasi Pasta Mumifikasi',
            'resep'    => 'Amoxicillin 500mg (3x1), Asam Mefenamat 500mg (3x1)',
            'biaya'    => 300000,
            'kontrol'  => 7
        ];
    } elseif (strpos($k, 'scaling') !== false) {
        return [
            'diagnosa' => 'Kalkulus Supragingival & Gingivitis Ringan',
            'tindakan' => 'Scaling & Pembersihan Karang Gigi Ultrasonik',
            'resep'    => 'Obat Kumur Antiseptik Chlorhexidine 0.2%',
            'biaya'    => 250000,
            'kontrol'  => 90
        ];
    } elseif (strpos($k, 'cabut') !== false) {
        return [
            'diagnosa' => 'Gangren Radix Gigi Molar (Sisa Akar Gigi)',
            'tindakan' => 'Ekstraksi / Pencabutan Gigi dengan Anestesi Infiltrasi',
            'resep'    => 'Amoxicillin 500mg (3x1), Asam Mefenamat 500mg (3x1)',
            'biaya'    => 150000,
            'kontrol'  => 7
        ];
    } else { // Kontrol behel / Kontrol umum
        return [
            'diagnosa' => 'Evaluasi Pergerakan Gigi Pasca Pemasangan Orthodonti',
            'tindakan' => 'Penggantian Power O-Ring Karet & Penyesuaian Kawat Gigi',
            'resep'    => 'Analgesik jika diperlukan',
            'biaya'    => 150000,
            'kontrol'  => 21
        ];
    }
}

// Pastikan dokter drg. Syamsuriah Sodding ada di tabel dokter
$q_cek_dok = mysqli_query($koneksi, "SELECT * FROM dokter LIMIT 1");
if (mysqli_num_rows($q_cek_dok) > 0) {
    mysqli_query($koneksi, "UPDATE dokter SET nama_dokter = 'drg. Syamsuriah Sodding' WHERE id_dokter IN (1, 2, 3, 4, 5, 6)");
} else {
    mysqli_query($koneksi, "INSERT INTO dokter (id_dokter, nama_dokter) VALUES (1, 'drg. Syamsuriah Sodding')");
}

// Ambil jadwal Selasa & Rabu
$q_jdwl_selasa = mysqli_query($koneksi, "SELECT id, id_dokter FROM jadwal_dokter WHERE hari = 'Selasa' LIMIT 1");
$d_jdwl_selasa = mysqli_fetch_assoc($q_jdwl_selasa);
if (!$d_jdwl_selasa) {
    mysqli_query($koneksi, "INSERT INTO jadwal_dokter (id_dokter, hari, jam_mulai, jam_selesai, status) VALUES (1, 'Selasa', '08:00:00', '16:00:00', 'Praktek')");
    $id_jdwl_selasa = mysqli_insert_id($koneksi);
    $id_dok_selasa  = 1;
} else {
    $id_jdwl_selasa = (int)$d_jdwl_selasa['id'];
    $id_dok_selasa  = (int)$d_jdwl_selasa['id_dokter'];
}

$q_jdwl_rabu = mysqli_query($koneksi, "SELECT id, id_dokter FROM jadwal_dokter WHERE hari = 'Rabu' LIMIT 1");
$d_jdwl_rabu = mysqli_fetch_assoc($q_jdwl_rabu);
if (!$d_jdwl_rabu) {
    mysqli_query($koneksi, "INSERT INTO jadwal_dokter (id_dokter, hari, jam_mulai, jam_selesai, status) VALUES (1, 'Rabu', '08:00:00', '16:00:00', 'Praktek')");
    $id_jdwl_rabu = mysqli_insert_id($koneksi);
    $id_dok_rabu  = 1;
} else {
    $id_jdwl_rabu = (int)$d_jdwl_rabu['id'];
    $id_dok_rabu  = (int)$d_jdwl_rabu['id_dokter'];
}

// Fungsi import list per tanggal
function importDailyPatients($koneksi, $data_list, $tanggal, $id_jadwal, $id_dokter) {
    $count_pasien = 0;
    $count_antrian = 0;

    foreach ($data_list as $item) {
        $nama    = mysqli_real_escape_string($koneksi, trim($item['nama']));
        $alamat  = mysqli_real_escape_string($koneksi, trim($item['alamat']) . ', Kec. Anggeraja');
        $umur    = (int)$item['umur'];
        $kelamin = $item['kelamin'];
        $keluhan = mysqli_real_escape_string($koneksi, trim($item['keluhan']));
        $no_antr = (int)$item['no'];

        // Hitung estimasi tanggal lahir
        $tahun_lahir = 2026 - $umur;
        $bulan_lahir = str_pad(($no_antr % 12) + 1, 2, '0', STR_PAD_LEFT);
        $hari_lahir  = str_pad(($no_antr % 28) + 1, 2, '0', STR_PAD_LEFT);
        $tgl_lahir   = "$tahun_lahir-$bulan_lahir-$hari_lahir";

        // Generate NIK Enrekang: 7316 (Enrekang) + 04 (Anggeraja) + DDMMYY + 000X
        $dd = $hari_lahir;
        if ($kelamin === 'Perempuan') $dd = str_pad((int)$hari_lahir + 40, 2, '0', STR_PAD_LEFT);
        $nik = "731604" . $dd . $bulan_lahir . substr($tahun_lahir, 2) . str_pad($no_antr, 4, '0', STR_PAD_LEFT);

        // Nomor telepon realistis
        $no_telp = "0821" . str_pad($tahun_lahir, 4, '0', STR_PAD_LEFT) . str_pad($no_antr * 137, 4, '0', STR_PAD_LEFT);

        // Cek apakah pasien sudah ada di database (Nama & Alamat mirip)
        $q_cek = mysqli_query($koneksi, "SELECT id, kode_pasien, nama FROM pasien WHERE nama = '$nama' LIMIT 1");
        if ($q_cek && mysqli_num_rows($q_cek) > 0) {
            $pasien = mysqli_fetch_assoc($q_cek);
            $id_pasien = (int)$pasien['id'];
        } else {
            // Hitung total pasien untuk buat No. RM baru
            $q_tot = mysqli_query($koneksi, "SELECT id FROM pasien");
            $total_p = mysqli_num_rows($q_tot) + 1;
            $kode_rm = 'RM' . str_pad($total_p, 4, '0', STR_PAD_LEFT);

            mysqli_query($koneksi, "
                INSERT INTO pasien (kode_pasien, nama, tanggal_lahir, jenis_kelamin, nik, alamat, no_telepon, password)
                VALUES ('$kode_rm', '$nama', '$tgl_lahir', '$kelamin', '$nik', '$alamat', '$no_telp', '123456')
            ");
            $id_pasien = mysqli_insert_id($koneksi);
            $count_pasien++;
        }

        // Hitung waktu daftar realistis (08:00 sampai 14:30)
        $menit_tambah = ($no_antr - 1) * 14;
        $jam_mulai_detik = strtotime("$tanggal 08:15:00") + ($menit_tambah * 60);
        $waktu_daftar = date('Y-m-d H:i:s', $jam_mulai_detik);

        // Cek apakah antrian hari itu sudah ada
        $q_cek_ant = mysqli_query($koneksi, "
            SELECT id FROM antrian 
            WHERE id_pasien = $id_pasien AND DATE(waktu_daftar) = '$tanggal' LIMIT 1
        ");

        if (mysqli_num_rows($q_cek_ant) == 0) {
            mysqli_query($koneksi, "
                INSERT INTO antrian (id_pasien, id_jadwal, id_dokter, no_antrian, nama_pendaftar, keluhan, status, waktu_daftar)
                VALUES ($id_pasien, $id_jadwal, $id_dokter, $no_antr, '$nama', '$keluhan', 'Selesai', '$waktu_daftar')
            ");
            $id_antrian = mysqli_insert_id($koneksi);
            $count_antrian++;

            // Masukkan data Rekam Medis & Transaksi
            $med = getMedicalDetails($keluhan);
            $diag = mysqli_real_escape_string($koneksi, $med['diagnosa']);
            $tind = mysqli_real_escape_string($koneksi, $med['tindakan']);
            $resp = mysqli_real_escape_string($koneksi, $med['resep']);
            $biay = (float)$med['biaya'];

            // Tanggal kunjungan berikutnya
            if ($nama === 'Riska' && $tanggal === '2026-07-28') {
                $tgl_next = '2026-07-29'; // Kembali kontrol esok harinya!
            } else {
                $tgl_next = date('Y-m-d', strtotime("$tanggal +" . $med['kontrol'] . " days"));
            }

            mysqli_query($koneksi, "
                INSERT INTO rekam_medis (id_pasien, id_dokter, nama_pasien, tanggal_periksa, keluhan, diagnosa, tindakan, resep_obat, biaya, kunjungan_berikutnya)
                VALUES ($id_pasien, $id_dokter, '$nama', '$tanggal', '$keluhan', '$diag', '$tind', '$resp', $biay, '$tgl_next')
            ");
            $id_rm = mysqli_insert_id($koneksi);

            // Masukkan Transaksi (Lunas)
            $metode = ($no_antr % 3 == 0) ? 'Transfer' : (($no_antr % 5 == 0) ? 'BPJS' : 'Tunai');
            mysqli_query($koneksi, "
                INSERT INTO transaksi (id_antrian, id_rekam_medis, nama_pasien, tindakan, biaya, metode_bayar, status_bayar, tanggal_transaksi)
                VALUES ($id_antrian, $id_rm, '$nama', '$tind', $biay, '$metode', 'Lunas', '$waktu_daftar')
            ");
        }
    }

    return ['pasien_baru' => $count_pasien, 'antrian_baru' => $count_antrian];
}

echo "=== MEMULAI IMPORT DATA RIIL PENELITIAN KLINIK GIGI ANGERAJA ===\n";
$res_selasa = importDailyPatients($koneksi, $data_selasa, '2026-07-28', $id_jdwl_selasa, $id_dok_selasa);
echo "Selasa 28-07-2026: {$res_selasa['pasien_baru']} pasien baru, {$res_selasa['antrian_baru']} antrian diinput.\n";

$res_rabu = importDailyPatients($koneksi, $data_rabu, '2026-07-29', $id_jdwl_rabu, $id_dok_rabu);
echo "Rabu 29-07-2026: {$res_rabu['pasien_baru']} pasien baru, {$res_rabu['antrian_baru']} antrian diinput.\n";

$tot_pasien = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM pasien"));
$tot_antrian = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM antrian"));
$tot_rekam = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM rekam_medis"));
$tot_transaksi = mysqli_num_rows(mysqli_query($koneksi, "SELECT id_transaksi FROM transaksi"));

echo "=================================================================\n";
echo "STATUS DATABASE SEKARANG:\n";
echo "- Total Pasien: $tot_pasien\n";
echo "- Total Antrian: $tot_antrian\n";
echo "- Total Rekam Medis: $tot_rekam\n";
echo "- Total Transaksi: $tot_transaksi\n";
echo "SELESAI DENGAN SUKSES!\n";
