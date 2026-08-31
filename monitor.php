<?php
include 'config/koneksi.php';

// Ambil Tanggal Hari Ini
$hari_ini = date('Y-m-d');

// 1. Ambil Antrian yang SEDANG DILAYANI (Status = Dilayani)
$query_panggil = mysqli_query($koneksi, "SELECT * FROM antrian WHERE DATE(waktu_daftar) = '$hari_ini' AND status = 'Dilayani' ORDER BY id DESC LIMIT 1");
$data_panggil = mysqli_fetch_array($query_panggil);

// 2. Ambil 5 Antrian BERIKUTNYA (Status = Menunggu)
$query_tunggu = mysqli_query($koneksi, "SELECT * FROM antrian WHERE DATE(waktu_daftar) = '$hari_ini' AND status = 'Menunggu' ORDER BY no_antrian ASC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor Antrian Klinik</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        :root {
            --bg-dark: #0f172a;
            /* Latar Belakang Gelap */
            --primary: #10b981;
            /* Hijau Emerald */
            --text-white: #f8fafc;
            --card-bg: #1e293b;
            /* Latar Kotak */
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-white);
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            /* Hilangkan scrollbar */
        }

        /* HEADER */
        .header {
            background: #064e3b;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            z-index: 10;
        }

        .brand {
            font-size: 1.5rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary);
        }

        .clock {
            font-size: 1.2rem;
            font-weight: 600;
            background: rgba(0, 0, 0, 0.3);
            padding: 5px 15px;
            border-radius: 50px;
        }

        /* CONTAINER UTAMA (Split Screen) */
        .main-display {
            flex: 1;
            display: flex;
            padding: 20px;
            gap: 20px;
        }

        /* KIRI: NOMOR BESAR (YANG DIPANGGIL) */
        .current-queue {
            flex: 1.5;
            /* Lebih lebar */
            background: var(--card-bg);
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            border: 2px solid var(--primary);
            box-shadow: 0 0 30px rgba(16, 185, 129, 0.1);
            position: relative;
        }

        .current-label {
            font-size: 1.5rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .big-number {
            font-size: 10rem;
            /* Super Besar */
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
            margin: 20px 0;
            text-shadow: 0 0 20px rgba(16, 185, 129, 0.3);
        }

        .patient-name {
            font-size: 2rem;
            font-weight: 600;
            background: #0f172a;
            padding: 10px 40px;
            border-radius: 50px;
            margin-bottom: 10px;
        }

        .poli-name {
            font-size: 1.2rem;
            color: var(--primary);
            font-weight: bold;
        }

        /* KANAN: LIST ANTRIAN */
        .waiting-list {
            flex: 1;
            background: var(--card-bg);
            border-radius: 20px;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .list-header {
            font-size: 1.2rem;
            font-weight: bold;
            border-bottom: 2px solid #334155;
            padding-bottom: 15px;
            margin-bottom: 15px;
            color: #94a3b8;
        }

        .queue-item {
            background: #334155;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .queue-item .q-num {
            color: white;
        }

        .queue-item .q-name {
            color: #cbd5e1;
            font-size: 1rem;
            font-weight: normal;
        }

        /* FOOTER RUNNING TEXT */
        .footer-marquee {
            background: var(--primary);
            color: #064e3b;
            padding: 10px 0;
            font-weight: bold;
            font-size: 1.1rem;
        }
    </style>
</head>

<body>

    <div class="header">
        <div class="brand">
            <i class="fas fa-clinic-medical"></i> KLINIK DESA SEHAT
        </div>
        <div class="clock" id="jam">00:00:00</div>
    </div>

    <div class="main-display">
        <div class="current-queue">
            <?php if ($data_panggil) { ?>
                <div class="current-label">Sedang Dipanggil</div>
                <div class="big-number"><?php echo $data_panggil['no_antrian']; ?></div>
                <div class="patient-name"><?php echo $data_panggil['nama_pendaftar']; ?></div>
                <div class="poli-name">Silakan Masuk ke Ruang Periksa</div>

                <style>
                    .big-number {
                        animation: pulse 2s infinite;
                    }

                    @keyframes pulse {
                        0% {
                            transform: scale(1);
                        }

                        50% {
                            transform: scale(1.05);
                        }

                        100% {
                            transform: scale(1);
                        }
                    }
                </style>
            <?php } else { ?>
                <div class="current-label">Status Antrian</div>
                <div style="font-size: 2rem; color: #64748b; margin-top: 20px;">
                    <i class="fas fa-coffee" style="font-size: 4rem; margin-bottom: 20px; display: block;"></i>
                    Belum ada panggilan
                </div>
            <?php } ?>
        </div>

        <div class="waiting-list">
            <div class="list-header">
                <i class="fas fa-list-ol"></i> Antrian Berikutnya
            </div>

            <?php
            if (mysqli_num_rows($query_tunggu) > 0) {
                while ($row = mysqli_fetch_array($query_tunggu)) {
            ?>
                    <div class="queue-item">
                        <span class="q-num">#<?php echo $row['no_antrian']; ?></span>
                        <span class="q-name"><?php echo substr($row['nama_pendaftar'], 0, 15); ?>...</span>
                    </div>
            <?php
                }
            } else {
                echo "<p style='text-align:center; color:#64748b; margin-top:50px;'>Tidak ada antrian menunggu.</p>";
            }
            ?>
        </div>
    </div>

    <div class="footer-marquee">
        <marquee>
            Selamat Datang di Klinik Desa Sehat. Budayakan antri untuk kenyamanan bersama. Jagalah kebersihan lingkungan klinik. Terima Kasih.
        </marquee>
    </div>

    <script>
        // 1. Script Jam Digital
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID');
            document.getElementById('jam').textContent = timeString;
        }
        setInterval(updateClock, 1000);
        updateClock(); // Jalankan langsung

        // 2. Script Auto Refresh Halaman (Setiap 5 Detik)
        // Ini trik paling gampang biar data selalu update tanpa coding ribet
        setTimeout(function() {
            window.location.reload(1);
        }, 5000); // 5000 milidetik = 5 detik
    </script>

</body>

</html>