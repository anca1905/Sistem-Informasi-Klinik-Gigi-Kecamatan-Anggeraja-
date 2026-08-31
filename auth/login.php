<?php
session_start();
include '../config/koneksi.php';

$login_gagal = false;

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $cek = mysqli_query($koneksi, "SELECT * FROM admin WHERE username = '$username' AND password = '$password'");

    if (mysqli_num_rows($cek) > 0) {
        $user_data = mysqli_fetch_assoc($cek);

        $_SESSION['login_user'] = $username;
        $_SESSION['nama_lengkap'] = $user_data['nama_lengkap'];
        $_SESSION['role'] = $user_data['role'];
        $_SESSION['status'] = "login";

        $login_sukses = true;
    } else {
        $login_gagal = true;
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Petugas Klinik</title>
    <style>
        :root {
            --hijau-tua: #059669;
            --hijau-muda: #34d399;
            --background: #f3f4f6;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--background);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-card {
            background: white;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .login-header {
            margin-bottom: 30px;
        }

        .login-header h2 {
            color: var(--hijau-tua);
            margin: 0;
            font-size: 24px;
        }

        .login-header p {
            color: #6b7280;
            font-size: 14px;
            margin-top: 5px;
        }

        .input-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
        }

        .input-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 16px;
            transition: 0.3s;
        }

        .input-group input:focus {
            border-color: var(--hijau-tua);
            outline: none;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }

        .btn-login {
            background: var(--hijau-tua);
            color: white;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-login:hover {
            background: #047857;
        }

        .link-home {
            display: block;
            margin-top: 20px;
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
        }

        .link-home:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="login-card">
        <div class="login-header">
            <span style="font-size: 40px;">👨‍⚕️</span>
            <h2>Login Petugas</h2>
            <p>Silakan masuk untuk mengelola antrian</p>
        </div>

        <form method="POST">
            <div class="input-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Masukkan username" required autocomplete="off">
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Masukkan password" required>
            </div>

            <button type="submit" name="login" class="btn-login">Masuk Sistem</button>
        </form>

        <a href="../index.php" class="link-home">← Kembali ke Halaman Depan</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php if (isset($login_sukses)) { ?>
        <script>
            Swal.fire({
                title: 'Login Berhasil!',
                text: 'Selamat datang kembali, Petugas.',
                icon: 'success',
                timer: 2000, // Otomatis pindah dalam 2 detik
                showConfirmButton: false
            }).then(() => {
                window.location = '../admin/dashboard.php'; // Kita akan buat ini nanti
            });
        </script>
    <?php } ?>

    <?php if ($login_gagal) { ?>
        <script>
            Swal.fire({
                title: 'Login Gagal!',
                text: 'Username atau Password salah.',
                icon: 'error',
                confirmButtonColor: '#d33'
            });
        </script>
    <?php } ?>

</body>

</html>