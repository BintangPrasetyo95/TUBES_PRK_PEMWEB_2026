<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/katalog.php';

$ketersediaanBawaan = 'tersedia';
$filter = katalog_filter_dari_request($_GET, $ketersediaanBawaan);
$daftarKategori = katalog_kategori();
$alats = katalog_cari($filter);
$actionKatalog = url('peminjaman/index.php');

$judul = 'Ajukan Peminjaman';
require __DIR__ . '/../includes/header.php';
?>
<?php page_head('Katalog peminjaman', 'Pilih alat yang mau', 'dipinjam', 'Cari berdasarkan nama atau kode, saring per kategori, jenis, dan ketersediaan. Klik kartu untuk mengisi formulir pengajuan.'); ?>

<?php render_katalog_filter($filter, $daftarKategori, $actionKatalog); ?>
<?php render_katalog_hasil($alats, $filter, $actionKatalog, $ketersediaanBawaan); ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
