<?php
session_start();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - SaveSmart</title>
    <link rel="stylesheet" href="assets/css/register.css">
</head>
<body>

    <div class="register-container">
        <div class="register-card">

            <div class="brand">
                <div class="brand-icon">S</div>
                <span>Save<span>Smart</span></span>
            </div>

            <div class="register-heading">
                <h1>Buat Akun Baru</h1>
                <p>Mulai kelola keuangan keluargamu bersama SaveSmart.</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert-error">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form action="backend/auth/register_process.php" method="POST">

                <div class="form-group">
                    <label for="nama_keluarga">Nama Keluarga</label>
                    <input
                        type="text"
                        id="nama_keluarga"
                        name="nama_keluarga"
                        placeholder="Masukkan nama keluarga"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="nama_lengkap">Nama Lengkap</label>
                    <input
                        type="text"
                        id="nama_lengkap"
                        name="nama_lengkap"
                        placeholder="Masukkan nama lengkap"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="contoh@email.com"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Masukkan password"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="konfirmasi_password">Konfirmasi Password</label>
                    <input
                        type="password"
                        id="konfirmasi_password"
                        name="konfirmasi_password"
                        placeholder="Masukkan ulang password"
                        required
                    >
                </div>

                <button type="submit" class="btn-register">
                    Daftar Sekarang
                </button>

            </form>

            <div class="login-link">
                Sudah punya akun?
                <a href="login.php">Login di sini</a>
            </div>

        </div>

        <p class="footer-text">© 2026 SaveSmart. Kelola keuangan lebih bijak.</p>
    </div>

</body>
</html>