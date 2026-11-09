<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_login();

$user = current_user();
$isAdmin = $user['role'] === 'admin';

$pageTitle = 'Laporan Penjualan';
$activePage = 'reports';

$navItems = [
    ['key' => 'home', 'label' => 'Beranda', 'href' => 'dashboard.php'],
    ['key' => 'reports', 'label' => 'Analytics', 'href' => 'reports/index.php'],
];

$pdo = db();

// Mengambil filter.
$startDate = is_string($_GET['start_date'] ?? null) ? trim($_GET['start_date']) : '';
$endDate = is_string($_GET['end_date'] ?? null) ? trim($_GET['end_date']) : '';
$invoice = is_string($_GET['invoice'] ?? null) ? trim($_GET['invoice']) : '';

$where = ["s.status = 'completed'"];
$params = [];
$errors = [];

// Validasi tanggal.
foreach (['start_date' => $startDate, 'end_date' => $endDate] as $field => $value) {
    if ($value === '') {
        continue;
    }

    $date = DateTime::createFromFormat('!Y-m-d', $value);

    if (!$date || $date->format('Y-m-d') !== $value) {
        $errors[] = $field === 'start_date'
            ? 'Tanggal awal tidak valid.'
            : 'Tanggal akhir tidak valid.';
    } else {
        $where[] = $field === 'start_date'
            ? 's.sold_at >= :start_date'
            : 's.sold_at < :end_date';

        $params[$field] = $field === 'start_date'
            ? $value . ' 00:00:00'
            : date('Y-m-d', strtotime($value . ' +1 day')) . ' 00:00:00';
    }
}

// Validasi rentang tanggal.
if (
    $startDate !== '' &&
    $endDate !== '' &&
    empty($errors) &&
    $startDate > $endDate
) {
    $errors[] = 'Tanggal awal tidak boleh melebihi tanggal akhir.';
}

// Pencarian invoice.
if ($invoice !== '') {
    $where[] = 's.invoice_no LIKE :invoice';
    $params['invoice'] = '%' . $invoice . '%';
}

// Kasir hanya melihat transaksi sendiri.
if (!$isAdmin) {
    $where[] = 's.cashier_id = :cashier_id';
    $params['cashier_id'] = (int) $user['id'];
}

// Mengambil data penjualan.
$sales = [];

if (!$errors) {
    $stmt = $pdo->prepare("
        SELECT
            s.id,
            s.invoice_no,
            s.sold_at,
            s.grand_total,
            s.payment_method,
            u.full_name AS cashier_name
        FROM sales s
        JOIN users u ON u.id = s.cashier_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY s.sold_at DESC
    ");

    $stmt->execute($params);
    $sales = $stmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= e(app_url('assets/css/recommendations-reports.css')) ?>">

<section class="reports-container">

    <h1>Laporan Penjualan</h1>
    <p class="reports-description">
        Daftar transaksi penjualan yang telah selesai.
    </p>

    <section class="analytics-section">
        <h2>Filter Penjualan</h2>

        <form method="GET" action="<?= e(app_url('reports/sales.php')) ?>">
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
                    <label for="invoice">Nomor Invoice</label>
                    <input type="text" id="invoice" name="invoice"
                        placeholder="Cari nomor invoice"
                        value="<?= e($invoice) ?>">
                </div>

                <button type="submit">Terapkan Filter</button>

                <a href="<?= e(app_url('reports/sales.php')) ?>">Reset</a>

                <a href="<?= e(app_url('reports/export-sales.php')) ?>?<?= e(http_build_query($_GET)) ?>">
                    Export CSV
                </a>

            </div>
        </form>
    </section>

    <section class="analytics-section">
        <h2>Data Penjualan</h2>

        <?php if ($errors): ?>

            <?php foreach ($errors as $error): ?>
                <p class="report-error"><?= e($error) ?></p>
            <?php endforeach; ?>

        <?php elseif (!$sales): ?>

            <p>Belum ada transaksi penjualan yang sesuai.</p>

        <?php else: ?>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Invoice</th>
                            <th>Tanggal</th>
                            <th>Kasir</th>
                            <th>Metode Pembayaran</th>
                            <th>Total</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($sales as $index => $sale): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= e($sale['invoice_no']) ?></td>
                                <td><?= e(date('d-m-Y H:i', strtotime($sale['sold_at']))) ?></td>
                                <td><?= e($sale['cashier_name']) ?></td>
                                <td><?= e($sale['payment_method']) ?></td>
                                <td>Rp <?= number_format((float) $sale['grand_total'], 0, ',', '.') ?></td>
                                <td>
                                    <a href="<?= e(app_url('reports/sales-detail.php?id=' . (int) $sale['id'])) ?>">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </section>

</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
