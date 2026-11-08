<?php
session_start();
include '../config/koneksi.php';

$login_gagal = false;
$pesan_error = 'Username atau Password salah.';
$login_type_success = '';

if (isset($_POST['login_petugas'])) {
    $username = mysqli_real_escape_string($koneksi, trim($_POST['username']));
    $password = mysqli_real_escape_string($koneksi, trim($_POST['password']));

    $cek = mysqli_query($koneksi, "SELECT * FROM admin WHERE username = '$username' AND password = '$password'");

    if (mysqli_num_rows($cek) > 0) {
        $user_data = mysqli_fetch_assoc($cek);

        $_SESSION['login_user']   = $username;
        $_SESSION['nama_lengkap'] = $user_data['nama_lengkap'];
        $_SESSION['role']         = $user_data['role'];
        $_SESSION['status']       = "login";

        $login_type_success = 'petugas';
    } else {
        $login_gagal = true;
        $pesan_error = 'Username atau Password petugas salah!';
    }
}

if (isset($_POST['login_pasien'])) {
    $identifier = mysqli_real_escape_string($koneksi, trim($_POST['identifier'])); // No. RM, No. HP, atau NIK
    $password   = mysqli_real_escape_string($koneksi, trim($_POST['password']));

    $cek = mysqli_query($koneksi, "
        SELECT * FROM pasien 
        WHERE (kode_pasien = '$identifier' OR no_telepon = '$identifier' OR nik = '$identifier') 
          AND (password = '$password' OR (password IS NULL AND '$password' = '123456') OR password = '')
        LIMIT 1
    ");

    if (mysqli_num_rows($cek) > 0) {
        $pasien_data = mysqli_fetch_assoc($cek);

        $_SESSION['pasien_id']      = $pasien_data['id'];
        $_SESSION['pasien_rm']      = $pasien_data['kode_pasien'];
        $_SESSION['pasien_nama']    = $pasien_data['nama'];
        $_SESSION['status_pasien']  = "login";

        $login_type_success = 'pasien';
    } else {
        $login_gagal = true;
        $pesan_error = 'No. Rekam Medis / No. HP atau Password salah! (Default password akun baru: 123456)';
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Klinik Gigi Anggeraja</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --hijau-tua: #059669;
            --hijau-gelap: #064e3b;
            --hijau-muda: #34d399;
            --background: #f3f4f6;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--background);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px 15px;
        }

        .login-card {
            background: white;
            padding: 35px 30px;
            width: 100%;
            max-width: 440px;
            border-radius: 20px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
            text-align: center;
            border-top: 6px solid var(--hijau-tua);
        }

        .login-header {
            margin-bottom: 25px;
        }

        .login-header h2 {
            color: var(--hijau-gelap);
            margin: 10px 0 0 0;
            font-size: 22px;
        }

        .login-header p {
            color: #6b7280;
            font-size: 13.5px;
            margin-top: 4px;
        }

        /* TAB LOGIN SWITCHER */
        .tab-switcher {
            display: flex;
            background: #f3f4f6;
            padding: 5px;
            border-radius: 12px;
            margin-bottom: 25px;
            gap: 6px;
        }

        .tab-btn {
            flex: 1;
            padding: 10px 8px;
            border: none;
            background: transparent;
            font-family: inherit;
            font-size: 13.5px;
            font-weight: 600;
            color: #6b7280;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .tab-btn.active {
            background: white;
            color: var(--hijau-tua);
            box-shadow: 0 3px 10px rgba(5, 150, 105, 0.15);
        }

        .input-group {
            margin-bottom: 18px;
            text-align: left;
        }

        .input-group label {
            display: block;
            margin-bottom: 7px;
            font-size: 13.5px;
            font-weight: 600;
            color: #374151;
        }

        .input-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #d1d5db;
            border-radius: 10px;
            font-size: 15px;
            font-family: inherit;
            transition: 0.25s;
        }

        .input-group input:focus {
            border-color: var(--hijau-tua);
            outline: none;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12);
            background: #f0fdf4;
        }

        .btn-login {
            background: var(--hijau-tua);
            color: white;
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 10px;
            font-size: 15.5px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            font-family: inherit;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-login:hover {
            background: #047857;
            transform: translateY(-1px);
        }

        .link-home {
            display: inline-block;
            margin-top: 22px;
            color: #6b7280;
            text-decoration: none;
            font-size: 13.5px;
            transition: 0.2s;
        }

        .link-home:hover {
            color: var(--hijau-tua);
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="login-card">
        <div class="login-header">
            <span style="font-size: 40px; display:inline-block;"><i class="fas fa-tooth" style="color:var(--hijau-tua);"></i></span>
            <h2>Klinik Gigi Anggeraja</h2>
            <p>Silakan masuk ke sistem klinik</p>
        </div>

        <!-- TAB SWITCHER: PASIEN VS PETUGAS -->
        <div class="tab-switcher">
            <button type="button" class="tab-btn active" id="tabPasienBtn" onclick="toggleLoginTab('pasien')">
                <i class="fas fa-user"></i> Akun Pasien
            </button>
            <button type="button" class="tab-btn" id="tabPetugasBtn" onclick="toggleLoginTab('petugas')">
                <i class="fas fa-user-md"></i> Petugas / Dokter
            </button>
        </div>

        <!-- FORM LOGIN AKUN PASIEN -->
        <div id="formPasienBox">
            <form method="POST">
                <div class="input-group">
                    <label>No. Rekam Medis (No. RM) / No. HP</label>
                    <input type="text" name="identifier" placeholder="Contoh: RM0008 atau 08123xxxx" required autocomplete="off">
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Masukkan password (default: 123456)" required>
                </div>

                <button type="submit" name="login_pasien" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Masuk Akun Pasien
                </button>
            </form>
            <small style="display:block; margin-top:10px; color:#6b7280; font-size:12px;">
                <i class="fas fa-info-circle"></i> Pasien terdaftar dapat langsung masuk dengan No. RM dan password default: <b>123456</b>.
            </small>
        </div>

        <!-- FORM LOGIN PETUGAS / DOKTER -->
        <div id="formPetugasBox" style="display: none;">
            <form method="POST">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Masukkan username" required autocomplete="off">
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Masukkan password" required>
                </div>

                <button type="submit" name="login_petugas" class="btn-login">
                    <i class="fas fa-lock"></i> Masuk Sebagai Petugas
                </button>
            </form>
        </div>

        <a href="../index.php" class="link-home">← Kembali ke Halaman Utama</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function toggleLoginTab(type) {
            const tabPasien = document.getElementById('tabPasienBtn');
            const tabPetugas = document.getElementById('tabPetugasBtn');
            const boxPasien = document.getElementById('formPasienBox');
            const boxPetugas = document.getElementById('formPetugasBox');

            if (type === 'pasien') {
                tabPasien.classList.add('active');
                tabPetugas.classList.remove('active');
                boxPasien.style.display = 'block';
                boxPetugas.style.display = 'none';
            } else {
                tabPetugas.classList.add('active');
                tabPasien.classList.remove('active');
                boxPetugas.style.display = 'block';
                boxPasien.style.display = 'none';
            }
        }

        <?php if ($login_type_success === 'petugas') { ?>
            Swal.fire({
                title: 'Login Berhasil!',
                text: 'Selamat datang, <?php echo isset($_SESSION["nama_lengkap"]) ? addslashes($_SESSION["nama_lengkap"]) : "Petugas"; ?>.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location = '../admin/dashboard.php';
            });
        <?php } elseif ($login_type_success === 'pasien') { ?>
            Swal.fire({
                title: 'Selamat Datang!',
                text: 'Halo, <?php echo isset($_SESSION["pasien_nama"]) ? addslashes($_SESSION["pasien_nama"]) : "Pasien"; ?>. Mengalihkan ke Portal Pasien...',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location = '../portal_pasien.php';
            });
        <?php } ?>

        <?php if ($login_gagal) { ?>
            Swal.fire({
                title: 'Login Gagal!',
                text: '<?php echo addslashes($pesan_error); ?>',
                icon: 'error',
                confirmButtonColor: '#059669'
            });
        <?php } ?>
    </script>

</body>

</html>
