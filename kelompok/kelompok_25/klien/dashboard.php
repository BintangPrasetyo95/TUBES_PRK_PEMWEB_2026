<?php
// MODUL KLIEN (Ghani). Ganti isi halaman ini dengan dashboard klien.
require_once __DIR__ . '/../config/app.php';
require_role('klien');

$judul = 'Dashboard Klien';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container page-head">
  <h2>Dashboard Klien</h2>
  <p class="text-secondary">Halo, <?= e(current_user()['nama']) ?>.</p>
  <div class="card-rh p-4">
    Halaman ini sementara. <strong>Ghani</strong> akan mengisi: ajukan konsultasi, riwayat dan status, profil.
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
