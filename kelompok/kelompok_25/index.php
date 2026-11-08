<?php
// BERANDA (Nafiza). Ini kerangka sementara; Nafiza menggantinya dengan landing page lengkap.
require_once __DIR__ . '/config/app.php';

$judul = 'Konsultasi Hukum Online';
require_once __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="container">
    <h1 class="display-5 mb-3">Ruang Hukum: Konsultasi Hukum Online</h1>
    <p class="lead mb-4">Tanyakan masalah hukummu kepada pengacara terverifikasi, kapan saja.</p>
    <a class="btn btn-gold btn-lg" href="<?= url('auth/register.php') ?>">Mulai Konsultasi</a>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
