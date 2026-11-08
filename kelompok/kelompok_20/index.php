<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Beranda';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h1 class="card-title">Selamat Datang di Aspiraku FISIP</h1>
    <p>Portal Pengaduan dan Aspirasi Publik Fakultas Ilmu Sosial dan Ilmu Politik.</p>
    <p style="margin-top: 1rem;">
        Status sistem: <strong>Fondasi Berhasil Diinisialisasi</strong>. Layout, konfigurasi basis data, dan modul pembantu siap digunakan oleh seluruh anggota tim.
    </p>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
