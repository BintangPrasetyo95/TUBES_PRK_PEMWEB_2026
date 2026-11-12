<?php
session_start();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Buat akun SaveSmart untuk mulai mencatat keuangan keluarga.">
    <meta name="theme-color" content="#f4f1e9">
    <title>Buat akun — SaveSmart</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="assets/css/app-theme.css">
</head>
<body class="auth-page register-page">
    <main class="login-container">
        <section class="login-illustration">
            <a class="login-brand" href="login.php">Save<span>Smart</span></a>

            <div class="login-illustration-content">
                <h1>Mulai dari catatan kecil.</h1>
                <p>Buat ruang keluarga Anda, lalu catat pemasukan, pengeluaran, anggaran, dan tabungan bersama.</p>
            </div>

            <div class="login-illustration-footer">
                Satu keluarga, satu catatan yang mudah dibuka.
            </div>

            <div class="login-decoration" aria-hidden="true"></div>
        </section>

        <section class="login-form-section">
            <div class="login-form-wrapper">
                <div class="login-heading">
                    <h2>Buat akun keluarga</h2>
                    <p>Isi data berikut untuk memulai.</p>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="login-message login-error" role="alert">
                        <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <form action="backend/auth/register_process.php" method="POST" class="register-form">
                    <div class="auth-fields">
                        <div class="login-field">
                            <label for="nama_keluarga">Nama keluarga</label>
                            <div class="login-input-wrapper">
                                <input type="text" id="nama_keluarga" name="nama_keluarga" autocomplete="organization" required>
                            </div>
                        </div>

                        <div class="login-field">
                            <label for="nama_lengkap">Nama lengkap</label>
                            <div class="login-input-wrapper">
                                <input type="text" id="nama_lengkap" name="nama_lengkap" autocomplete="name" required>
                            </div>
                        </div>

                        <div class="login-field">
                            <label for="email">Alamat email</label>
                            <div class="login-input-wrapper">
                                <input type="email" id="email" name="email" autocomplete="email" required>
                            </div>
                        </div>

                        <div class="login-field">
                            <label for="password">Kata sandi</label>
                            <div class="login-input-wrapper">
                                <input type="password" id="password" name="password" autocomplete="new-password" required>
                            </div>
                        </div>

                        <div class="login-field">
                            <label for="konfirmasi_password">Ulangi kata sandi</label>
                            <div class="login-input-wrapper">
                                <input type="password" id="konfirmasi_password" name="konfirmasi_password" autocomplete="new-password" required>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="login-button">Buat akun</button>
                </form>

                <div class="login-register">
                    Sudah punya akun? <a href="login.php">Masuk</a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
