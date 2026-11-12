
<?php
session_start();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Masuk ke catatan keuangan keluarga Anda di SaveSmart.">
    <meta name="theme-color" content="#f4f1e9">
    <title>Masuk — SaveSmart</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="assets/css/app-theme.css">
</head>
<body class="auth-page login-page">

    <main class="login-container">
        <section class="login-illustration">
            <a class="login-brand" href="login.php">Save<span>Smart</span></a>

            <div class="login-illustration-content">
                <h1>Uang keluarga, satu catatan.</h1>
                <p>Catat yang masuk, yang keluar, dan rencana berikutnya tanpa menyebar catatan ke banyak tempat.</p>
            </div>

            <div class="login-illustration-footer">
                Ruang bersama untuk mengelola keuangan keluarga.
            </div>

            <div class="login-decoration"></div>
        </section>

        <section class="login-form-section">
            <div class="login-form-wrapper">
                <div class="login-heading">
                    <h2>Masuk ke SaveSmart</h2>
                    <p>Lanjutkan catatan keluarga Anda.</p>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="login-message login-error">
                        <?= htmlspecialchars($_SESSION['error']) ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="login-message login-success">
                        <?= htmlspecialchars($_SESSION['success']) ?>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <form action="backend/auth/login_process.php" method="POST">
                    <div class="login-field">
                        <label for="email">Alamat email</label>
                        <div class="login-input-wrapper">
                            <input type="email" id="email" name="email" placeholder="nama@email.com" autocomplete="email" required>
                        </div>
                    </div>

                    <div class="login-field">
                        <label for="password">Kata sandi</label>
                        <div class="login-input-wrapper">
                            <input type="password" id="password" name="password" placeholder="Masukkan kata sandi" autocomplete="current-password" required>
                        </div>
                    </div>

                    <button type="submit" class="login-button">Masuk</button>
                </form>

                <div class="login-register">
                    Belum punya akun keluarga? <a href="register.php">Buat akun</a>
                </div>
            </div>
        </section>
    </main>

</body>
</html>