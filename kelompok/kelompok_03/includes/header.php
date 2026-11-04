
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

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">

        <a class="navbar-brand fw-bold" href="#">
            Sistem Antrian Klinik
        </a>

        <?php if (!empty($_SESSION['user_id'])): ?>
            <span class="navbar-text text-white">
                <?= htmlspecialchars($namaPengguna, ENT_QUOTES, 'UTF-8') ?>
            </span>
        <?php endif; ?>

    </div>
</nav>

<main class="container py-4">
