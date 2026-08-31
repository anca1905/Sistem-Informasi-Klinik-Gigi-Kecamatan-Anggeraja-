<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Klinik Gigi Sehat - Booking Online</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* --- 1. VARIABEL & RESET --- */
        :root {
            --primary: #059669;
            /* Emerald 600 */
            --primary-dark: #047857;
            /* Emerald 700 */
            --accent: #34d399;
            /* Emerald 400 */
            --bg-light: #f0fdf4;
            /* Emerald 50 */
            --text-dark: #1e293b;
            --text-gray: #64748b;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            overflow-x: hidden;
        }

        /* --- 2. NAVBAR (GLASS EFFECT) --- */
        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 15px 0;
            transition: 0.3s;
        }

        nav {
            width: 90%;
            max-width: 1200px;
            margin: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-menu {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-link {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 0.95rem;
            transition: 0.3s;
        }

        .nav-link:hover {
            color: var(--primary);
        }

        .btn-login {
            padding: 8px 20px;
            border: 2px solid var(--primary);
            border-radius: 50px;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
        }

        .btn-login:hover {
            background: var(--primary);
            color: var(--white);
        }

        .hamburger {
            display: none;
            cursor: pointer;
            flex-direction: column;
            gap: 5px;
        }

        .bar {
            width: 25px;
            height: 3px;
            background-color: var(--text-dark);
            transition: 0.3s;
            border-radius: 5px;
        }

        /* --- 3. HERO SECTION --- */
        .hero {
            padding: 120px 5% 50px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1200px;
            margin: auto;
            min-height: 90vh;
            position: relative;
        }

        .hero-text {
            flex: 1;
            padding-right: 50px;
            z-index: 2;
        }

        .hero-image {
            flex: 1;
            position: relative;
            z-index: 2;
        }

        .hero-image img {
            width: 100%;
            max-width: 500px;
            border-radius: 30px 5px 30px 5px;
            box-shadow: 20px 20px 0px var(--accent);
        }

        .badge-hero {
            display: inline-block;
            background: #d1fae5;
            color: var(--primary-dark);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        h1 {
            font-size: 3rem;
            line-height: 1.2;
            color: var(--text-dark);
            margin-bottom: 20px;
        }

        h1 span {
            color: var(--primary);
        }

        p.subtitle {
            font-size: 1.1rem;
            color: var(--text-gray);
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .cta-buttons {
            display: flex;
            gap: 15px;
        }

        .btn-fill {
            background: var(--primary);
            color: white;
            padding: 15px 30px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 10px 20px rgba(5, 150, 105, 0.25);
            transition: 0.3s;
        }

        .btn-outline {
            background: transparent;
            color: var(--text-dark);
            padding: 15px 30px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            border: 1px solid #cbd5e1;
            transition: 0.3s;
        }

        .btn-fill:hover {
            transform: translateY(-3px);
            background: var(--primary-dark);
        }

        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .blob {
            position: absolute;
            z-index: 1;
            filter: blur(50px);
            opacity: 0.6;
        }

        .blob-1 {
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: #a7f3d0;
            border-radius: 50%;
        }

        .blob-2 {
            bottom: 0;
            left: 0;
            width: 400px;
            height: 400px;
            background: #d1fae5;
            border-radius: 50%;
        }

        /* --- 4. LAYANAN SECTION --- */
        .services {
            padding: 80px 5%;
            background: white;
            position: relative;
            z-index: 3;
        }

        .section-title {
            text-align: center;
            max-width: 600px;
            margin: 0 auto 50px;
        }

        .section-title h2 {
            font-size: 2.2rem;
            margin-bottom: 10px;
        }

        .section-title p {
            color: var(--text-gray);
        }

        .service-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: auto;
        }

        .service-card {
            background: var(--bg-light);
            padding: 30px;
            border-radius: 20px;
            transition: 0.3s;
            border: 1px solid transparent;
        }

        .service-card:hover {
            background: white;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.05);
            border-color: var(--accent);
            transform: translateY(-5px);
        }

        .icon-box {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        /* --- 5. JADWAL SECTION (DIPINDAH KESINI BIAR JALAN DI LAPTOP) --- */
        .schedule-section {
            padding: 80px 5%;
            background: #f8fafc;
        }

        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            max-width: 1000px;
            margin: auto;
        }

        .schedule-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border-left: 5px solid var(--primary);
            /* Garis hijau */
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: 0.3s;
        }

        .schedule-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .day-badge {
            background: var(--primary);
            color: white;
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
            display: inline-block;
        }

        .doc-name {
            font-weight: bold;
            font-size: 1.1rem;
            margin: 5px 0;
        }

        .doc-time {
            color: #64748b;
            font-size: 0.9rem;
        }


        /* --- 6. RESPONSIVE MEDIA QUERIES (KHUSUS HP) --- */
        @media (max-width: 968px) {
            .hero {
                flex-direction: column-reverse;
                text-align: center;
                padding-top: 100px;
                gap: 50px;
            }

            .hero-text {
                padding-right: 0;
            }

            .hero-image img {
                max-width: 80%;
                margin: auto;
                display: block;
            }

            .cta-buttons {
                justify-content: center;
            }

            /* Mobile Menu */
            .hamburger {
                display: flex;
            }

            .nav-menu {
                position: fixed;
                top: 70px;
                left: -100%;
                flex-direction: column;
                background: white;
                width: 100%;
                text-align: center;
                padding: 40px 0;
                box-shadow: 0 10px 10px rgba(0, 0, 0, 0.05);
                transition: 0.3s;
            }

            .nav-menu.active {
                left: 0;
            }

            /* Animasi Burger */
            .hamburger.active .bar:nth-child(2) {
                opacity: 0;
            }

            .hamburger.active .bar:nth-child(1) {
                transform: translateY(8px) rotate(45deg);
            }

            .hamburger.active .bar:nth-child(3) {
                transform: translateY(-8px) rotate(-45deg);
            }
        }
    </style>
</head>

<body>

    <header>
        <nav>
            <div class="logo">
                <span style="font-size: 1.8rem;">🦷</span> KLINIK GIGI
            </div>

            <div class="nav-menu" id="navMenu">
                <a href="#" class="nav-link">Beranda</a>
                <a href="#layanan" class="nav-link">Layanan</a>
                <a href="#jadwal" class="nav-link">Jadwal Dokter</a>
                <a href="auth/login.php" class="btn-login">Login Staff</a>
            </div>

            <div class="hamburger" id="hamburger">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </nav>
    </header>

    <section class="hero">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>

        <div class="hero-text">
            <span class="badge-hero">✨ Solusi Sakit Gigi Tanpa Ribet</span>
            <h1>Rawat Gigimu,<br><span>Senyum</span> Percaya Diri.</h1>
            <p class="subtitle">Tidak perlu antri berjam-jam di klinik. Ambil nomor antrian dari rumah, pantau giliran lewat HP, dan datang tepat waktu.</p>

            <div class="cta-buttons">
                <a href="ambil_antrian.php" class="btn-fill">Ambil Antrian Sekarang</a>
                <a href="cek_antrian.php" class="btn-outline">Cek Giliran Saya</a>
            </div>
        </div>

        <div class="hero-image">
            <img src="https://images.unsplash.com/photo-1606811841689-23dfddce3e95?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" alt="Dokter Gigi Ramah">
        </div>
    </section>

    <section id="layanan" class="services">
        <div class="section-title">
            <h2>Layanan Unggulan</h2>
            <p>Kami menyediakan perawatan gigi terbaik dengan peralatan modern dan dokter spesialis yang ramah.</p>
        </div>

        <div class="service-grid">
            <div class="service-card">
                <div class="icon-box">🦷</div>
                <h3>Cabut & Tambal</h3>
                <p>Penanganan gigi berlubang dengan bahan berkualitas dan metode minim rasa sakit.</p>
            </div>
            <div class="service-card">
                <div class="icon-box">✨</div>
                <h3>Scaling (Karang Gigi)</h3>
                <p>Bersihkan karang gigi secara menyeluruh untuk mencegah bau mulut dan radang gusi.</p>
            </div>
            <div class="service-card">
                <div class="icon-box">😁</div>
                <h3>Kawat Gigi (Behel)</h3>
                <p>Konsultasi ortodonti untuk merapikan struktur gigi agar senyum lebih estetik.</p>
            </div>
        </div>
    </section>

    <section id="jadwal" class="schedule-section">
        <div class="section-title">
            <h2>Jadwal Dokter Gigi</h2>
            <p>Cek ketersediaan dokter favoritmu minggu ini.</p>
        </div>

        <div class="schedule-grid">
            <?php
            include 'config/koneksi.php';
            $query_jadwal = mysqli_query($koneksi, "
                SELECT j.*, d.nama_dokter 
                FROM jadwal_dokter j 
                LEFT JOIN dokter d ON j.id_dokter = d.id_dokter 
                ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu')
            ");

            while ($j = mysqli_fetch_array($query_jadwal)) {
            ?>
                <div class="schedule-card">
                    <div>
                        <span class="day-badge"><?php echo $j['hari']; ?></span>
                        <div class="doc-name"><?php echo $j['nama_dokter']; ?></div>
                        <div class="doc-time">
                            <i class="far fa-clock"></i>
                            <?php echo date('H:i', strtotime($j['jam_mulai'])) . ' - ' . date('H:i', strtotime($j['jam_selesai'])); ?> WIB
                        </div>
                    </div>

                    <div>
                        <?php if ($j['status'] == 'Aktif' || $j['status'] == 'Praktek') { ?>
                            <span style="color: var(--primary); font-size: 2rem;"><i class="fas fa-user-md"></i></span>
                        <?php } else { ?>
                            <span style="color: #ef4444; font-weight:bold; border:1px solid #ef4444; padding:5px 10px; border-radius:5px; font-size:0.8rem;">LIBUR</span>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </section>

    <footer>
        <div style="background: var(--text-dark); color: white; padding: 40px 5%; text-align: center;">
            <p style="margin-bottom: 10px;">📍 Jl. Atlanta No.6, Kecamatan Anggeraja | 📞 WhatsApp: 0812-3456-7890</p>
            <p style="font-size: 0.8rem; opacity: 0.6;">&copy; 2024 Sistem Informasi Klinik Gigi. Dibuat dengan ❤️</p>
        </div>
    </footer>

    <script>
        const hamburger = document.getElementById('hamburger');
        const navMenu = document.getElementById('navMenu');

        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
        });

        document.querySelectorAll('.nav-link').forEach(n => n.addEventListener('click', () => {
            hamburger.classList.remove('active');
            navMenu.classList.remove('active');
        }));
    </script>
</body>

</html>