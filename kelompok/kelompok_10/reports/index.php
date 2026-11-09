<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_login();

$user = current_user();
$isAdmin = $user['role'] === 'admin';

$pageTitle = 'Analytics';
$activePage = 'reports';

$navItems = [
    ['key' => 'home', 'label' => 'Beranda', 'href' => 'dashboard.php'],
    ['key' => 'reports', 'label' => 'Analytics', 'href' => 'reports/index.php'],
];

$pdo = db();

// Filter transaksi berdasarkan status dan hak akses.
$where = "s.status = 'completed'";
$params = [];

if (!$isAdmin) {
    $where .= " AND s.cashier_id = :cashier_id";
    $params['cashier_id'] = (int) $user['id'];
}

// Ringkasan omzet dan jumlah transaksi.
$stmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(s.grand_total), 0) AS omzet,
        COUNT(*) AS transaksi
    FROM sales s
    WHERE $where
");
$stmt->execute($params);
$summary = $stmt->fetch();

// Total HPP.
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(si.qty * si.cost_price_snapshot), 0)
    FROM sale_items si
    JOIN sales s ON s.id = si.sale_id
    WHERE $where
");
$stmt->execute($params);
$hpp = (float) $stmt->fetchColumn();

$omzet = (float) $summary['omzet'];
$transaksi = (int) $summary['transaksi'];
$labaKotor = $omzet - $hpp;

// Lima produk terlaris.
$stmt = $pdo->prepare("
    SELECT p.name, SUM(si.qty) AS total_terjual
    FROM sale_items si
    JOIN sales s ON s.id = si.sale_id
    JOIN products p ON p.id = si.product_id
    WHERE $where
    GROUP BY p.id, p.name
    ORDER BY total_terjual DESC
    LIMIT 5
");
$stmt->execute($params);
$bestSellers = $stmt->fetchAll();

// Produk aktif tanpa transaksi selesai selama 30 hari.
$slowMoving = [];

if ($isAdmin) {
    $slowMoving = $pdo->query("
        SELECT p.name
        FROM products p
        WHERE p.is_active = 1
          AND NOT EXISTS (
              SELECT 1
              FROM sale_items si
              JOIN sales s ON s.id = si.sale_id
              WHERE si.product_id = p.id
                AND s.status = 'completed'
                AND s.sold_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          )
        ORDER BY p.name
        LIMIT 10
    ")->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= e(app_url('assets/css/recommendations-reports.css')) ?>">

<section class="reports-container">

    <h1>Dashboard Analytics</h1>

    <p class="reports-description">
        <?= $isAdmin
            ? 'Ringkasan performa penjualan dan produk SisaSense.'
            : 'Ringkasan transaksi penjualan milik Anda.' ?>
    </p>

    <section class="analytics-cards">
        <?php
        $cards = [
            ['Total Omzet', 'Rp ' . number_format($omzet, 0, ',', '.')],
            ['Transaksi Selesai', number_format($transaksi, 0, ',', '.')],
            ['Total HPP', 'Rp ' . number_format($hpp, 0, ',', '.')],
            ['Laba Kotor', 'Rp ' . number_format($labaKotor, 0, ',', '.')],
        ];
        ?>

        <?php foreach ($cards as [$label, $value]): ?>
            <article class="analytics-card">
                <h3><?= e($label) ?></h3>
                <p><?= e($value) ?></p>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="analytics-section">
        <h2>5 Produk Terlaris</h2>

        <?php if (!$bestSellers): ?>
            <p>Belum ada data penjualan.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Nama Produk</th>
                            <th>Jumlah Terjual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bestSellers as $product): ?>
                            <tr>
                                <td><?= e($product['name']) ?></td>
                                <td><?= number_format((int) $product['total_terjual']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($isAdmin): ?>
        <section class="analytics-section">
            <h2>Produk Tidak Terjual Selama 30 Hari</h2>
            <p>Produk aktif tanpa transaksi selesai dalam 30 hari terakhir.</p>

            <?php if (!$slowMoving): ?>
                <p>Tidak ada produk yang memenuhi kriteria.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Nama Produk</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($slowMoving as $i => $product): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= e($product['name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
