<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_login();

$user = current_user();
$isAdmin = $user['role'] === 'admin';

$pageTitle = 'Detail Penjualan';
$activePage = 'reports';

$navItems = [
    ['key' => 'home', 'label' => 'Beranda', 'href' => 'dashboard.php'],
    ['key' => 'reports', 'label' => 'Analytics', 'href' => 'reports/index.php'],
];

$pdo = db();

// Validasi ID transaksi.
$saleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$saleId || $saleId <= 0) {
    http_response_code(400);
    exit('ID transaksi tidak valid.');
}

// Mengambil transaksi.
$sql = "
    SELECT
        s.id, s.invoice_no, s.sold_at, s.subtotal,
        s.discount_total, s.grand_total, s.amount_paid,
        s.change_amount, s.payment_method,
        u.full_name AS cashier_name
    FROM sales s
    JOIN users u ON u.id = s.cashier_id
    WHERE s.id = :sale_id AND s.status = 'completed'
";

$params = ['sale_id' => $saleId];

if (!$isAdmin) {
    $sql .= ' AND s.cashier_id = :cashier_id';
    $params['cashier_id'] = (int) $user['id'];
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sale = $stmt->fetch();

if (!$sale) {
    http_response_code(404);
    exit('Transaksi tidak ditemukan atau Anda tidak memiliki akses.');
}

// Mengambil rincian produk.
$stmt = $pdo->prepare("
    SELECT
        p.name AS product_name,
        si.qty,
        si.unit_price_snapshot,
        si.cost_price_snapshot,
        si.discount_amount,
        si.line_total
    FROM sale_items si
    JOIN products p ON p.id = si.product_id
    WHERE si.sale_id = :sale_id
    ORDER BY si.id ASC
");

$stmt->execute(['sale_id' => $saleId]);
$items = $stmt->fetchAll();

// Menghitung HPP dan laba kotor.
$totalHpp = 0;
$totalLabaKotor = 0;

foreach ($items as $item) {
    $hpp = (float) $item['qty'] * (float) $item['cost_price_snapshot'];
    $laba = (float) $item['line_total'] - $hpp;

    $totalHpp += $hpp;
    $totalLabaKotor += $laba;
}

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= e(app_url('assets/css/recommendations-reports.css')) ?>">

<section class="reports-container">

    <h1>Detail Penjualan</h1>
    <p class="reports-description">
        Informasi lengkap transaksi penjualan SisaSense.
    </p>

    <section class="analytics-section">
        <h2>Informasi Transaksi</h2>

        <div class="report-detail-info">
            <p><strong>Nomor Invoice:</strong> <?= e($sale['invoice_no']) ?></p>
            <p><strong>Tanggal:</strong> <?= e(date('d-m-Y H:i', strtotime($sale['sold_at']))) ?></p>
            <p><strong>Kasir:</strong> <?= e($sale['cashier_name']) ?></p>
            <p><strong>Metode Pembayaran:</strong> <?= e($sale['payment_method']) ?></p>
        </div>
    </section>

    <section class="analytics-section">
        <h2>Rincian Produk</h2>

        <?php if (!$items): ?>
            <p>Tidak ada detail produk dalam transaksi ini.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nama Produk</th>
                            <th>Qty</th>
                            <th>Harga Jual</th>
                            <th>HPP</th>
                            <th>Diskon</th>
                            <th>Pendapatan</th>
                            <th>Laba Kotor</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($items as $index => $item): ?>
                            <?php
                            $hpp = (float) $item['qty'] * (float) $item['cost_price_snapshot'];
                            $revenue = (float) $item['line_total'];
                            $profit = $revenue - $hpp;
                            ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= e($item['product_name']) ?></td>
                                <td><?= number_format((int) $item['qty']) ?></td>
                                <td>Rp <?= number_format((float) $item['unit_price_snapshot'], 0, ',', '.') ?></td>
                                <td>Rp <?= number_format($hpp, 0, ',', '.') ?></td>
                                <td>Rp <?= number_format((float) $item['discount_amount'], 0, ',', '.') ?></td>
                                <td>Rp <?= number_format($revenue, 0, ',', '.') ?></td>
                                <td>Rp <?= number_format($profit, 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="analytics-section">
        <h2>Ringkasan Pembayaran</h2>

        <div class="report-detail-info">
            <p><strong>Subtotal:</strong> Rp <?= number_format((float) $sale['subtotal'], 0, ',', '.') ?></p>
            <p><strong>Total Diskon:</strong> Rp <?= number_format((float) $sale['discount_total'], 0, ',', '.') ?></p>
            <p><strong>Total HPP:</strong> Rp <?= number_format($totalHpp, 0, ',', '.') ?></p>
            <p><strong>Total Omzet:</strong> Rp <?= number_format((float) $sale['grand_total'], 0, ',', '.') ?></p>
            <p><strong>Laba Kotor:</strong> Rp <?= number_format($totalLabaKotor, 0, ',', '.') ?></p>
            <p><strong>Jumlah Dibayar:</strong> Rp <?= number_format((float) $sale['amount_paid'], 0, ',', '.') ?></p>
            <p><strong>Kembalian:</strong> Rp <?= number_format((float) $sale['change_amount'], 0, ',', '.') ?></p>
        </div>
    </section>

    <a href="<?= e(app_url('reports/sales.php')) ?>" class="report-back-link">
        Kembali ke Laporan Penjualan
    </a>

</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
