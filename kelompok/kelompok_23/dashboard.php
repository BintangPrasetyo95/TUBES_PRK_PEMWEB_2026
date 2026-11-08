<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$pageTitle = 'Dashboard';

require_once __DIR__ . '/config/database.php';

$totalProduk = null;
$errorTotalProduk = '';

try {
    $hasilProduk = $conn->query(
        "SELECT COUNT(*) AS total_produk FROM products"
    );

    if ($hasilProduk === false) {
        throw new RuntimeException($conn->error);
    }

    $dataProduk = $hasilProduk->fetch_assoc();
    $totalProduk = (int) $dataProduk['total_produk'];

    $hasilProduk->free();
} catch (Exception $e) {
    error_log('Query total produk gagal: ' . $e->getMessage());
    $errorTotalProduk = 'Jumlah produk belum dapat dimuat.';
}

$totalTransaksiHariIni = null;
$errorTransaksiHariIni = '';

try {
    $hasilTransaksi = $conn->query(
        "SELECT COUNT(*) AS total_transaksi
         FROM transactions
         WHERE transaction_date >= CURDATE()
           AND transaction_date < CURDATE() + INTERVAL 1 DAY"
    );

    if ($hasilTransaksi === false) {
        throw new RuntimeException($conn->error);
    }

    $dataTransaksi = $hasilTransaksi->fetch_assoc();
    $totalTransaksiHariIni = (int) $dataTransaksi['total_transaksi'];

    $hasilTransaksi->free();
} catch (Exception $e) {
    error_log('Query transaksi hari ini gagal: ' . $e->getMessage());
    $errorTransaksiHariIni = 'Jumlah transaksi belum dapat dimuat.';
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/helper.php';
?>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="app-layout d-flex flex-grow-1">

    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content flex-grow-1 p-4">

        <div class="mb-4">
            <h3 class="fw-bold">Dashboard</h3>
            <p class="text-muted">
                Selamat datang di sistem manajemen FEBMart.
            </p>
        </div>

        <?php showFlash(); ?>

        <section class="mb-4" aria-labelledby="judul-ringkasan">
            <h4 id="judul-ringkasan" class="fw-bold mb-3">
                Ringkasan Toko
            </h4>

            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="text-muted fs-6">Total Produk</h5>

                            <p class="fs-3 fw-bold mb-1">
                                <?= $totalProduk !== null
                                    ? number_format($totalProduk, 0, ',', '.')
                                    : '&mdash;'; ?>
                            </p>

                            <small class="text-muted">Jumlah jenis produk</small>

                            <?php if ($errorTotalProduk !== ''): ?>
                                <p class="text-danger small mt-2 mb-0" role="alert">
                                    <?= htmlspecialchars(
                                        $errorTotalProduk,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="text-muted fs-6">Transaksi Hari Ini</h5>

                            <p class="fs-3 fw-bold mb-1">
                                <?= $totalTransaksiHariIni !== null
                                    ? number_format($totalTransaksiHariIni, 0, ',', '.')
                                    : '&mdash;'; ?>
                            </p>

                            <small class="text-muted">Jumlah transaksi penjualan</small>

                            <?php if ($errorTransaksiHariIni !== ''): ?>
                                <p class="text-danger small mt-2 mb-0" role="alert">
                                    <?= htmlspecialchars(
                                        $errorTransaksiHariIni,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="text-muted fs-6">Omzet Hari Ini</h5>
                            <p class="fs-3 fw-bold mb-1">&mdash;</p>
                            <small class="text-muted">Total nilai penjualan</small>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mb-4" aria-labelledby="judul-stok">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h4 id="judul-stok" class="fw-bold mb-2">
                        Monitoring Stok
                    </h4>
                    <p class="text-muted mb-0">
                        Daftar produk dengan stok mencapai atau di bawah
                        batas minimum akan ditampilkan di bagian ini.
                    </p>
                </div>
            </div>
        </section>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">

                <h4 class="fw-bold mb-3">
                    Selamat Datang,
                    <?= htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?>!
                </h4>

                <p class="text-muted">
                    Anda berhasil login ke sistem manajemen FEBMart.
                </p>

                <hr>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <small class="text-muted">Username</small>
                        <p class="fw-semibold">
                            <?= htmlspecialchars($_SESSION['username'] ?? '-'); ?>
                        </p>
                    </div>

                    <div class="col-md-6 mb-3">
                        <small class="text-muted">Role</small>
                        <p class="fw-semibold">
                            <?= htmlspecialchars($_SESSION['role'] ?? '-'); ?>
                        </p>
                    </div>
                </div>

            </div>
        </div>

    </main>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>