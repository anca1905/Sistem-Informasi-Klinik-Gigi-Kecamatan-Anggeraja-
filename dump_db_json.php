<?php
include 'config/koneksi.php';

$tables = ['admin', 'jadwal_dokter', 'antrian'];
$output = [];

foreach ($tables as $table) {

    $result = mysqli_query($koneksi, "DESCRIBE $table");
    $fields = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $fields[] = $row['Field'] . " (" . $row['Type'] . ")";
        }
    } else {
        $fields[] = "Table does not exist.";
    }
    $output[$table] = $fields;
}

$a = mysqli_query($koneksi, "SELECT * FROM admin");
$admin_data = [];
while ($r = mysqli_fetch_assoc($a)) {
    $admin_data[] = $r;
}
$output['admin_data'] = $admin_data;

file_put_contents('db_schema.json', json_encode($output, JSON_PRETTY_PRINT));
echo "Done";
