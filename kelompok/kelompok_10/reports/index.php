<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_login();

$user = current_user();
$isAdmin = $user['role'] === 'admin';
$pdo = db();

$pageTitle = 'Analytics';
$activePage = 'reports';

$navItems = [
    ['key' => 'home', 'label' => 'Beranda', 'href' => 'dashboard.php'],
];

if ($isAdmin) {
    $navItems[] = ['key' => 'restock', 'label' => 'Rekomendasi Restock', 'href' => 'recommendations/restock.php'];
    $navItems[] = ['key' => 'discount', 'label' => 'Simulasi Diskon', 'href' => 'recommendations/discount.php'];
}

$navItems[] = ['key' => 'reports', 'label' => 'Analytics', 'href' => 'reports/index.php'];
$navItems[] = ['key' => 'sales', 'label' => 'Laporan Penjualan', 'href' => 'reports/sales.php'];

if ($isAdmin) {
    $navItems[] = ['key' => 'inventory', 'label' => 'Laporan Inventaris', 'href' => 'reports/inventory.php'];
}

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

$startDate = is_string($startDate) ? trim($startDate) : '';
$endDate = is_string($endDate) ? trim($endDate) : '';

$validDate = static function (string $date): bool {
    $parsed = DateTime::createFromFormat('!Y-m-d', $date);
    return $parsed !== false && $parsed->format('Y-m-d') === $date;
};

$errors = [];

if (($startDate !== '' && !$validDate($startDate)) ||
    ($endDate !== '' && !$validDate($endDate))) {
    $errors[] = 'Format tanggal tidak valid.';
}

if (!$errors && $startDate !== '' && $endDate !== '' && $startDate > $endDate) {
    $errors[] = 'Tanggal awal tidak boleh melebihi tanggal akhir.';
}

$filterValid = empty($errors);

$where = ["s.status = 'completed'"];
$params = [];

if (!$isAdmin) {
    $where[] = 's.cashier_id = :cashier_id';
    $params['cashier_id'] = (int) $user['id'];
}

if ($filterValid) {
    if ($startDate !== '') {
        $where[] = 's.sold_at >= :start_date';
        $params['start_date'] = $startDate . ' 00:00:00';
    }

    if ($endDate !== '') {
        $where[] = 's.sold_at < :end_date';
        $params['end_date'] = date(
            'Y-m-d',
            strtotime($endDate . ' +1 day')
        ) . ' 00:00:00';
    }
}

$whereSql = implode(' AND ', $where);

$periodLabel = 'Semua periode';

if ($filterValid) {
    if ($startDate !== '' && $endDate !== '') {
        $periodLabel = date('d-m-Y', strtotime($startDate))
            . ' sampai '
            . date('d-m-Y', strtotime($endDate));
    } elseif ($startDate !== '') {
        $periodLabel = 'Mulai ' . date('d-m-Y', strtotime($startDate));
    } elseif ($endDate !== '') {
        $periodLabel = 'Sampai ' . date('d-m-Y', strtotime($endDate));
    }
} else {
    $periodLabel = 'Filter tidak valid';
}

$omzet = 0.0;
$transaksi = 0;
$hpp = 0.0;
$labaKotor = 0.0;
$bestSellers = [];
$trend = [];

$period = $_GET['period'] ?? 'daily';
$period = is_string($period) ? $period : 'daily';

$periods = [
    'daily' => [
        'label' => 'Harian',
        'sql' => 'DATE(s.sold_at)',
    ],
    'weekly' => [
        'label' => 'Mingguan',
        'sql' => "DATE_FORMAT(s.sold_at, '%x-W%v')",
    ],
    'monthly' => [
        'label' => 'Bulanan',
        'sql' => "DATE_FORMAT(s.sold_at, '%Y-%m')",
    ],
];

if (!isset($periods[$period])) {
    $period = 'daily';
}

if ($filterValid) {
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(s.grand_total), 0) AS omzet,
            COUNT(*) AS transaksi
        FROM sales s
        WHERE $whereSql
    ");
    $stmt->execute($params);
    $summary = $stmt->fetch();

    $omzet = (float) $summary['omzet'];
    $transaksi = (int) $summary['transaksi'];

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(si.qty * si.cost_price_snapshot), 0)
        FROM sale_items si
        JOIN sales s ON s.id = si.sale_id
        WHERE $whereSql
    ");
    $stmt->execute($params);

    $hpp = (float) $stmt->fetchColumn();
    $labaKotor = $omzet - $hpp;

    $stmt = $pdo->prepare("
        SELECT p.name, SUM(si.qty) AS total_terjual
        FROM sale_items si
        JOIN sales s ON s.id = si.sale_id
        JOIN products p ON p.id = si.product_id
        WHERE $whereSql
        GROUP BY p.id, p.name
        ORDER BY total_terjual DESC
        LIMIT 5
    ");
    $stmt->execute($params);
    $bestSellers = $stmt->fetchAll();

    $groupSql = $periods[$period]['sql'];

    $stmt = $pdo->prepare("
        SELECT
            $groupSql AS periode,
            SUM(s.grand_total) AS omzet,
            COUNT(*) AS transaksi
        FROM sales s
        WHERE $whereSql
        GROUP BY $groupSql
        ORDER BY MIN(s.sold_at) ASC
    ");
    $stmt->execute($params);
    $trend = $stmt->fetchAll();
}

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
                AND s.sold_at <= NOW()
          )
        ORDER BY p.name
        LIMIT 10
    ")->fetchAll();
}

$rupiah = static fn(float $value): string =>
    'Rp ' . number_format($value, 0, ',', '.');

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= e(app_url('assets/css/recommendations-reports.css')) ?>">

<section class="reports-container">
    <h1>Dashboard Analytics</h1>

    <p class="reports-description">
        Ringkasan performa penjualan dan produk SisaSense.
    </p>

    <section class="analytics-section">
        <h2>Filter Periode</h2>

        <?php foreach ($errors as $error): ?>
            <p class="report-error"><?= e($error) ?></p>
        <?php endforeach; ?>

        <form method="GET" action="<?= e(app_url('reports/index.php')) ?>">
            <div class="report-filters">
                <div>
                    <label for="start_date">Tanggal Awal</label>
                    <input type="date" id="start_date" name="start_date"
                        value="<?= e($startDate) ?>">
                </div>

                <div>
                    <label for="end_date">Tanggal Akhir</label>
                    <input type="date" id="end_date" name="end_date"
                        value="<?= e($endDate) ?>">
                </div>

                <div>
                    <label for="period">Tren Penjualan</label>
                    <select id="period" name="period">
                        <?php foreach ($periods as $key => $item): ?>
                            <option value="<?= e($key) ?>" <?= $period === $key ? 'selected' : '' ?>>
                                <?= e($item['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit">Terapkan</button>
                <a href="<?= e(app_url('reports/index.php')) ?>">Reset</a>
            </div>
        </form>

        <p class="reports-description">
            Periode ringkasan: <strong><?= e($periodLabel) ?></strong>.
        </p>
    </section>

    <section class="analytics-section">
        <h2>Menu Laporan</h2>

        <div class="report-menu">
            <a class="report-menu-item" href="<?= e(app_url('reports/sales.php')) ?>">
                <strong>Laporan Penjualan</strong>
                <span>Daftar transaksi berdasarkan tanggal, kasir, dan invoice.</span>
            </a>

            <?php if ($isAdmin): ?>
                <a class="report-menu-item" href="<?= e(app_url('reports/inventory.php')) ?>">
                    <strong>Laporan Inventaris</strong>
                    <span>Stok, batch, dan kedaluwarsa produk.</span>
                </a>

                <a class="report-menu-item" href="<?= e(app_url('recommendations/restock.php')) ?>">
                    <strong>Rekomendasi Restock</strong>
                    <span>Perhitungan ADS, ROP, dan jumlah pembelian.</span>
                </a>

                <a class="report-menu-item" href="<?= e(app_url('recommendations/discount.php')) ?>">
                    <strong>Simulasi Diskon</strong>
                    <span>Simulasi laba dan risiko kerugian diskon.</span>
                </a>
            <?php endif; ?>
        </div>
    </section>

    <section class="analytics-cards">
        <?php
        $cards = [
            'Total Omzet' => $rupiah($omzet),
            'Transaksi Selesai' => number_format($transaksi, 0, ',', '.'),
            'Total HPP' => $rupiah($hpp),
            'Laba Kotor' => $rupiah($labaKotor),
        ];
        ?>

        <?php foreach ($cards as $label => $value): ?>
            <article class="analytics-card">
                <h3><?= e($label) ?></h3>
                <p><?= e($value) ?></p>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="analytics-section">
        <h2>Tren Penjualan <?= e($periods[$period]['label']) ?></h2>

        <?php if (!$filterValid || empty($trend)): ?>
            <p>Belum ada data tren penjualan.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Periode</th>
                            <th>Omzet</th>
                            <th>Transaksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trend as $item): ?>
                            <tr>
                                <td><?= e($item['periode']) ?></td>
                                <td><?= e($rupiah((float) $item['omzet'])) ?></td>
                                <td><?= number_format((int) $item['transaksi']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="analytics-section">
        <h2>5 Produk Terlaris</h2>

        <?php if (empty($bestSellers) || !$filterValid): ?>
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

            <?php if (empty($slowMoving)): ?>
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