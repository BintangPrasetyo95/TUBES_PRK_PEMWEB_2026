<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/nav.php';

require_login();

$isAdmin = current_user()['role'] === 'admin';

$pageTitle = 'Laporan';
$activePage = 'reports';
$navItems = reports_nav_items($isAdmin);

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= e(app_url('assets/css/recommendations-reports.css')) ?>">

<section class="reports-container">
    <h1>Laporan</h1>

    <p class="reports-description">
        Halaman ini digunakan untuk mengakses dan mengelola informasi laporan
        sesuai dengan hak akses pengguna.
    </p>

    <section class="analytics-section">
        <h2>Informasi Laporan</h2>

        <p>
            Gunakan menu navigasi di samping untuk membuka laporan penjualan,
            laporan inventaris, serta fitur rekomendasi yang tersedia.
        </p>

        <?php if ($isAdmin): ?>
            <p>
                Sebagai admin, Anda dapat mengakses seluruh laporan penjualan,
                laporan inventaris, rekomendasi restock, dan simulasi diskon.
            </p>
        <?php else: ?>
            <p>
                Sebagai kasir, Anda hanya dapat mengakses laporan penjualan
                sesuai dengan transaksi milik Anda sendiri.
            </p>
        <?php endif; ?>
    </section>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
