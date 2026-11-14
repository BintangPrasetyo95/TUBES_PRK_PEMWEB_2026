<?php

require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../includes/laporan.php';

$periode = laporan_periode($_GET);
$laporan = laporan_data($periode['awal'], $periode['akhir']);

$judul = 'Laporan Peminjaman';
$bodyClass = 'page-laporan';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="section-title">
    <div>
        <h1>Laporan Peminjaman</h1>
        <p class="text-muted mb-0">Lihat, filter, dan cetak laporan transaksi peminjaman alat serta ruang laboratorium.</p>
    </div>
</div>

<?php render_laporan($laporan, $periode, url('admin/laporan.php')); ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
