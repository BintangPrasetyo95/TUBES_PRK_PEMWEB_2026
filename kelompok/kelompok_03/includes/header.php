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

    <link rel="stylesheet" href="../assets/style.css">
</head>

<body class="badan-admin">

<nav class="nav-admin">
    <a class="merek" href="admin.php">Sistem Antrian Klinik</a>

    <?php if (!empty($_SESSION['user_id'])): ?>
        <div class="kanan-nav">
            <span class="nama">
                <?= htmlspecialchars($namaPengguna, ENT_QUOTES, 'UTF-8') ?>
            </span>
            <a href="../auth/logout.php" class="tombol tombol-nav" onclick="return confirm('Apakah Anda yakin ingin keluar?');">
                Logout
            </a>
        </div>
    <?php endif; ?>
</nav>

<main class="wadah">
