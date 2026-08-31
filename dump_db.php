<?php
include 'config/koneksi.php';

$tables = ['admin', 'jadwal_dokter', 'antrian'];

foreach ($tables as $table) {
    echo "TABLE: $table\n";
    $result = mysqli_query($koneksi, "DESCRIBE $table");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "Table does not exist.\n";
    }
    echo "\n";
}

$a = mysqli_query($koneksi, "SELECT * FROM admin");
echo "ADMIN DATA:\n";
while ($r = mysqli_fetch_assoc($a)) {
    print_r($r);
}
