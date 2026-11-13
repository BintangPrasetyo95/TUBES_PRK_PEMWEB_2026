<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/katalog.php';

$ketersediaanBawaan = 'semua';
$filter = katalog_filter_dari_request($_GET, $ketersediaanBawaan);
$daftarKategori = katalog_kategori();
$alats = katalog_cari($filter);
$actionKatalog = url('alat/index.php');

$judul = 'Daftar Alat';
require __DIR__ . '/../includes/header.php';
?>
<?php page_head('Inventaris laboratorium', 'Daftar alat &', 'ruang lab', 'Lihat seluruh inventaris beserta stok dan lokasinya.' . (is_logged_in() ? '' : ' Masuk terlebih dahulu untuk mengajukan peminjaman.')); ?>

<?php render_katalog_filter($filter, $daftarKategori, $actionKatalog); ?>
<?php render_katalog_hasil($alats, $filter, $actionKatalog, $ketersediaanBawaan); ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
