<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Klinik Desa</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo time(); ?>">
</head>

<body>

    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-tooth" style="font-size: 1.8rem; color: var(--primary-light);"></i>
            <span style="margin-left: 10px;">KLINIK GIGI</span>
        </div>

        <div class="sidebar-menu">
            <div class="menu-label">Menu Utama</div>
            <?php
            $current_page = basename($_SERVER['PHP_SELF']);
            $role_active = isset($_SESSION['role']) ? $_SESSION['role'] : 'Admin';
            ?>

            <a href="dashboard.php" class="menu-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>

            <a href="pendaftaran.php" class="menu-link <?php echo ($current_page == 'pendaftaran.php') ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-list"></i> Pendaftaran
            </a>

            <a href="antrian.php" class="menu-link <?php echo ($current_page == 'antrian.php') ? 'active' : ''; ?>">
                <i class="fas fa-list-ol"></i> Antrian
            </a>

            <a href="data_pasien.php" class="menu-link <?php echo (in_array($current_page, ['data_pasien.php', 'tambah_pasien.php', 'edit_pasien.php'])) ? 'active' : ''; ?>">
                <i class="fas fa-user-injured"></i> Pasien
            </a>

            <?php if ($role_active == 'Admin'): ?>
                <a href="data_dokter.php" class="menu-link <?php echo (in_array($current_page, ['data_dokter.php', 'tambah_dokter.php', 'edit_dokter.php'])) ? 'active' : ''; ?>">
                    <i class="fas fa-user-md"></i> Dokter
                </a>
                <a href="jadwal_praktik.php" class="menu-link <?php echo ($current_page == 'jadwal_praktik.php') ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i> Jadwal Praktik
                </a>
                <a href="transaksi.php" class="menu-link <?php echo (in_array($current_page, ['transaksi.php', 'tambah_transaksi.php', 'konfirmasi_bayar.php', 'cetak_struk_bayar.php'])) ? 'active' : ''; ?>">
                    <i class="fas fa-cash-register"></i> Transaksi
                </a>
            <?php endif; ?>

            <a href="laporan.php" class="menu-link <?php echo ($current_page == 'laporan.php') ? 'active' : ''; ?>">
                <i class="fas fa-file-alt"></i> Laporan
            </a>
        </div>


        <div class="sidebar-footer">
            <a href="../auth/logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt" style="margin-right: 10px;"></i> Logout System
            </a>
        </div>
    </aside>

    <div class="main-content">
        <header class="top-header">
            <div style="display: flex; align-items: center;">
                <button class="toggle-btn" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="header-title">
                    <h1>Sistem Klinik Gigi</h1>
                </div>
            </div>

            <div class="user-profile">
                <div style="text-align: right;" class="hidden-mobile">
                    <div style="font-weight: 600; font-size: 0.9rem;">
                        <?php echo isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : $nama_petugas; ?>
                    </div>
                    <div style="font-size: 0.75rem; color: #6b7280;">
                        <?php echo isset($_SESSION['role']) ? $_SESSION['role'] : 'Admin Petugas'; ?>
                    </div>
                </div>
                <div class="avatar">
                    <?php
                    $avatar_name = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : $nama_petugas;
                    echo substr($avatar_name, 0, 1);
                    ?>
                </div>
            </div>
        </header>
        <div class="content-scroll">