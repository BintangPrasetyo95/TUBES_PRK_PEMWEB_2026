<?php
// includes/partials/header-publik.php
// Wrapper template header untuk halaman publik (Landing, Login, Register, Guest, 403, 404)

$root = $root ?? '';
require_once __DIR__ . '/head.php';
require_once __DIR__ . '/navbar-publik.php';
?>

<!-- Struktur Layout Halaman Publik -->
<div class="layout-publik">
  <?php
  // Tampilkan notifikasi flash message jika ada
  if (function_exists('flash')) {
      $pesanFlash = flash();
      if ($pesanFlash) {
          $tipe = ($pesanFlash['tipe'] === 'sukses' || $pesanFlash['tipe'] === 'galat' || $pesanFlash['tipe'] === 'info')
              ? $pesanFlash['tipe']
              : 'info';
          echo '<div class="container"><div class="alert alert-' . e($tipe) . '">' . e($pesanFlash['pesan']) . '</div></div>';
      }
  }
  ?>
  <main>
