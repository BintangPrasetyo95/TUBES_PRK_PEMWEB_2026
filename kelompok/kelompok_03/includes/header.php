<?php
require_once __DIR__ . '/init.php';

$pageTitle = $pageTitle ?? 'Sistem Antrian Klinik';
$namaPengguna = $_SESSION['nama'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700;800&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body class="badan-admin">

<nav class="nav-admin">
    <a class="merek" href="admin.php">Klinik FK Unila</a>

    <?php if (!empty($_SESSION['user_id'])): ?>
        <div class="kanan-nav">
            <span class="nama"><?= htmlspecialchars($namaPengguna, ENT_QUOTES, 'UTF-8') ?></span>
            <a href="../auth/logout.php" class="tombol tombol-nav" onclick="return confirm('Apakah Anda yakin ingin keluar?');">Keluar</a>
        </div>
    <?php endif; ?>
</nav>

<main class="wadah">
