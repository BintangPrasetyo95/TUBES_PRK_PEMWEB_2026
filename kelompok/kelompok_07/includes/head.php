<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/dekorasi.php';

$pageTitle = isset($judul) && $judul !== '' ? $judul . ' | ' . APP_NAME : APP_NAME;
$bodyClass = $bodyClass ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2D3FE0">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,100..900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/style.css') . '?v=' . filemtime(__DIR__ . '/../assets/css/style.css')) ?>">
    <script src="<?= e(asset('js/app.js') . '?v=' . filemtime(__DIR__ . '/../assets/js/app.js')) ?>" defer></script>
</head>
<body id="atas" class="<?= e($bodyClass) ?>">
<a class="skip-link" href="#konten">Lewati ke konten utama</a>
