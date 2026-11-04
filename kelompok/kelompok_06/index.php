<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

// 1. Tangkap parameter page dari URL, default ke 'dashboard'
$page = $_GET['page'] ?? 'dashboard';

// 2. Tentukan judul halaman dinamis untuk Topbar
$titles = [
    'dashboard' => 'Dashboard Analitik',
    'kasir'     => 'Terminal Kasir (POS) & Split Bill',
    'produk'    => 'Data Master Produk',
    'laporan'   => 'Laporan & Bukti Transaksi'
];
$page_heading = $titles[$page] ?? 'FlashPOS System';
$page_title   = $page_heading . ' — FlashPOS';

// 3. Panggil Layout Atas (Header & Sidebar)
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<!-- KONTEN HALAMAN DINAMIS -->
<?php
// 4. Routing pemanggilan file berdasarkan parameter $page
switch ($page) {
    case 'dashboard':
        if (file_exists('pages/dashboard.php')) include 'pages/dashboard.php';
        break;
    case 'kasir':
        if (file_exists('pages/kasir.php')) include 'pages/kasir.php';
        break;
    case 'produk':
        if (file_exists('pages/produk.php')) include 'pages/produk.php';
        break;
    case 'laporan':
        if (file_exists('pages/laporan.php')) include 'pages/laporan.php';
        break;
    default:
        echo "<div class='p-4 bg-red-50 text-red-600 rounded-lg'>Halaman tidak ditemukan!</div>";
        break;
}
?>

<?php
// 5. Panggil Layout Bawah (Footer)
require_once 'includes/footer.php';
?>