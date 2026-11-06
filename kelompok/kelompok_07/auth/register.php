
<?php

require_once __DIR__ . '/../config/app.php';

$error = '';
$nama = '';
$email = '';
$nim_nip = '';
$no_hp = '';

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - LabLoan</title>
</head>
<body>

    <h2>Registrasi Akun LabLoan</h2>

    <?php if ($error !== ''): ?>
        <p style="color: red;">
            <?= e($error) ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="">

        <label>Nama Lengkap</label><br>
        <input type="text" name="nama" required>
        <br><br>

        <label>Email</label><br>
        <input type="email" name="email" required>
        <br><br>

        <label>NIM/NIP</label><br>
        <input type="text" name="nim_nip">
        <br><br>

        <label>Nomor HP</label><br>
        <input type="text" name="no_hp">
        <br><br>

        <label>Password</label><br>
        <input type="password" name="password" required>
        <br><br>

        <label>Konfirmasi Password</label><br>
        <input type="password" name="konfirmasi_password" required>
        <br><br>

        <button type="submit">Daftar</button>

    </form>

    <p>
        Sudah punya akun?
        <a href="<?= e(url('auth/login.php')) ?>">Login</a>
    </p>

</body>
</html>
