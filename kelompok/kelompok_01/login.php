
<?php
session_start();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SaveSmart</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>

    <main class="login-container">
        <section class="login-illustration">
            <div class="login-brand">Save<span>Smart</span></div>

            <div class="login-illustration-content">
                <h1>Kelola Keuangan, Raih Masa Depan.</h1>
                <p>Mulai atur pemasukan, pengeluaran, anggaran, dan tabunganmu dengan lebih mudah bersama SaveSmart.</p>
            </div>

            <div class="login-illustration-footer">
                Smart Financial Management
            </div>

            <div class="login-decoration"></div>
        </section>

        <section class="login-form-section">
            <div class="login-form-wrapper">
                <div class="login-heading">
                    <h2>Selamat Datang!</h2>
                    <p>Silakan masuk ke akun SaveSmart kamu.</p>
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
                        <label for="email">Email</label>
                        <div class="login-input-wrapper">
                            <input type="email" id="email" name="email" placeholder="Masukkan email kamu" required>
                        </div>
                    </div>

                    <div class="login-field">
                        <label for="password">Password</label>
                        <div class="login-input-wrapper">
                            <input type="password" id="password" name="password" placeholder="Masukkan password kamu" required>
                        </div>
                    </div>

                    <button type="submit" class="login-button">Masuk ke Akun</button>
                </form>

                <div class="login-register">
                    Belum punya akun? <a href="register.php">Daftar di sini</a>
                </div>
            </div>
        </section>
    </main>

</body>
</html>