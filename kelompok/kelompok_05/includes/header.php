<?php
// Pembuka halaman. Dipakai setelah bootstrap.php:
//   $page_title = 'Judul Halaman';        // opsional
//   $extra_css  = ['assets/pemeriksaan/pemeriksaan.css'];  // opsional, CSS tambahan milik fitur
//   require __DIR__ . '/../../includes/header.php';
$judul_halaman = isset($page_title)
    ? $page_title . ' | ' . config('app_name', 'CEKHAK')
    : config('app_name', 'CEKHAK');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="base-url" content="<?= e(base_url()) ?>">
    <title><?= e($judul_halaman) ?></title>
    <link rel="stylesheet" href="<?= e(base_url('assets/css/theme.css')) ?>">
<?php foreach ((array) ($extra_css ?? []) as $css_tambahan): ?>
    <link rel="stylesheet" href="<?= e(base_url($css_tambahan)) ?>">
<?php endforeach; ?>
</head>
<body>
<?php require __DIR__ . '/navbar.php'; ?>
<main class="container">
<?php foreach (flash_all() as $tipe_flash => $pesan_flash): ?>
    <div class="alert alert-<?= e($tipe_flash) ?>"><?= e($pesan_flash) ?></div>
<?php endforeach; ?>
