
<?php

require_once __DIR__ . '/../config/app.php';

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LabLoan</title>
</head>
<body>

    <h2>Login Akun LabLoan</h2>

    <form method="POST" action="">

        <label>Email</label><br>
        <input type="email" name="email" required>
        <br><br>

        <label>Password</label><br>
        <input type="password" name="password" required>
        <br><br>

        <button type="submit">Login</button>

    </form>

    <p>
        Belum punya akun?
        <a href="<?= e(url('auth/register.php')) ?>">Daftar</a>
    </p>

</body>
</html>
