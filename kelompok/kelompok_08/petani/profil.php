<?php

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/database.php';

$user_id = $_SESSION["user_id"];

$query = mysqli_prepare(
    $conn,
    "SELECT 
        users.nama,
        users.email,
        petani.no_whatsapp,
        petani.lokasi_kebun
     FROM users
     INNER JOIN petani ON users.id = petani.user_id
     WHERE users.id = ?"
);

mysqli_stmt_bind_param($query, "i", $user_id);
mysqli_stmt_execute($query);

$result = mysqli_stmt_get_result($query);
$petani = mysqli_fetch_assoc($result);

if (!$petani) {
    exit("Data profil petani tidak ditemukan.");
}

?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Petani - BUYPANEN</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<div class="profile-container">

    <div class="profile-header">
        <h1>🌱 BUYPANEN</h1>
        <p>Platform pemasaran hasil panen petani Indonesia.</p>
        <br>
        <h2 style="color:white;">Halo, <?= htmlspecialchars($petani["nama"]) ?>!</h2>
        <p>Selamat datang di dashboard petani.</p>
    </div>

    <div class="profile-card">

        <h2>Profil Petani</h2>
        <p><strong>Nama Lengkap</strong><br>
            <?= htmlspecialchars($petani["nama"]) ?>
        </p>

        <p><strong>Email</strong><br>
            <?= htmlspecialchars($petani["email"]) ?>
        </p>

        <p><strong>Nomor WhatsApp</strong><br>
            <?= htmlspecialchars($petani["no_whatsapp"]) ?>
        </p>

        <p><strong>Lokasi Kebun</strong><br>
            <?= nl2br(htmlspecialchars($petani["lokasi_kebun"])) ?>
        </p>

        <div class="profile-actions">
            <a href="update_profil.php">
                <button type="button">✏️ Edit Profil</button>
            </a>

            <a href="../auth/logout.php"
               onclick="return confirm('Yakin ingin logout?')">
                <button type="button" class="btn-secondary">
                    Logout
                </button>
            </a>
        </div>

    </div>

    <p style="text-align:center; color:#718078; margin-top:25px; font-size:12px;">
        © 2026 BUYPANEN - Mendukung Petani Indonesia
    </p>

</div>

</body>
</html>

