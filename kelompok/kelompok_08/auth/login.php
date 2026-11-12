<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../assets/style.css">
<?php
session_start();

require_once __DIR__ . '/../config/database.php';

$pesan = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {
        $pesan = "Email dan password wajib diisi!";
    } else {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, nama, email, password, role
             FROM users
             WHERE email = ?"
        );

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user["password"])) {

            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["nama"] = $user["nama"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];

            if ($user["role"] === "petani") {
                header("Location: ../petani/profil.php");
                exit;
            } 

        } else {
            $pesan = "Email atau password salah!";
        }

        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login BUYPANEN</title>
</head>
<body>
  <p style="text-align:center; margin-top:20px; color:#14532d; font-size:24px; font-weight:700;">
    Login BUYPANEN
</p>


    <?php if (!empty($pesan)): ?>
        <p>
            <?= htmlspecialchars($pesan) ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <label>Email</label><br>
        <input type="email" name="email" required>
        <br><br>

        <label>Password</label><br>
        <input type="password" name="password" required>
        <br><br>

        <button type="submit">Login</button>

    </form>

     <p style="text-align:center; margin-top:20px;"> 
        Belum punya akun?
        <a href="../auth/register.php">Daftar Sekarang</a>
    </p>

</body>
</html>
