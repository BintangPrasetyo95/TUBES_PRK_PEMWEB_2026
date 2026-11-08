<?php
// MODUL ADMIN (Linda). Ganti isi halaman ini dengan dashboard admin.
require_once __DIR__ . '/../config/app.php';
require_role('admin');

$judul = 'Dashboard Admin';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container page-head">
  <h2>Dashboard Admin</h2>
  <p class="text-secondary">Halo, <?= e(current_user()['nama']) ?>.</p>
  <div class="card-rh p-4">
    Halaman ini sementara. <strong>Linda</strong> akan mengisi: kelola pengguna, verifikasi pengacara, kelola artikel.
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
