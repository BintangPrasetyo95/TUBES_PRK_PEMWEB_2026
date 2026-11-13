<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/helpers.php';

require_role('admin');

$pdo = db();

$productId = (int) filter_input(INPUT_GET, 'product', FILTER_VALIDATE_INT);
$showAll = isset($_GET['all']);

$where = [];
$params = [];
if ($productId > 0) {
    $where[] = 'b.product_id = ?';
    $params[] = $productId;
}
if (!$showAll) {
    $where[] = 'b.qty_remaining > 0';
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare(
    "SELECT b.id, b.batch_code, b.received_at, b.expiry_date, b.qty_received, b.qty_remaining,
            b.cost_per_unit, p.sku, p.name, p.unit, s.name AS supplier_name
     FROM stock_batches b
     JOIN products p ON p.id = b.product_id
     LEFT JOIN suppliers s ON s.id = b.supplier_id
     $whereSql
     ORDER BY (b.expiry_date IS NULL), b.expiry_date ASC, b.received_at ASC, b.id ASC"
);
$stmt->execute($params);
$batches = $stmt->fetchAll();

$products = $pdo->query('SELECT id, sku, name FROM products ORDER BY name')->fetchAll();

function batch_status(array $b): array
{
    if ((int) $b['qty_remaining'] === 0) {
        return ['Habis', 'badge-muted'];
    }
    if ($b['expiry_date'] === null) {
        return ['Aman', 'badge-success'];
    }
    $today = new DateTimeImmutable('today');
    $expiry = new DateTimeImmutable((string) $b['expiry_date']);
    $days = (int) $today->diff($expiry)->format('%r%a');
    if ($days < 0) {
        return ['Kedaluwarsa', 'badge-danger'];
    }
    if ($days <= 30) {
        return ['Segera kedaluwarsa (' . $days . ' hari)', 'badge-warning'];
    }
    return ['Aman', 'badge-success'];
}

$pageTitle = 'Batch stok';
$activePage = 'batches';
$navItems = inventory_nav_items();
require __DIR__ . '/../includes/header.php';
echo inventory_css();
?>
<div class="panel-header">
    <div>
        <p class="eyebrow">PERSEDIAAN</p>
        <h1>Batch stok</h1>
    </div>
    <a class="button button-primary" href="stock_in.php">+ Stok masuk</a>
</div>
<p class="muted">Urutan sama dengan aturan FEFO: kedaluwarsa paling awal di atas.</p>

<section class="panel">
    <form method="get" action="batches.php" class="filter-bar">
        <div class="form-stack">
            <label for="product">Produk</label>
            <select id="product" name="product">
                <option value="0">Semua produk</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= $productId === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['sku'] . ' - ' . $p['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div></div>
        <div></div>
        <button class="button button-secondary" type="submit">Terapkan</button>
    </form>
    <p class="muted">
        <?php if ($showAll): ?>
            Menampilkan semua batch. <a href="batches.php<?= $productId > 0 ? '?product=' . $productId : '' ?>">Hanya yang masih ada sisa</a>
        <?php else: ?>
            Hanya batch yang masih ada sisa. <a href="batches.php?all=1<?= $productId > 0 ? '&amp;product=' . $productId : '' ?>">Tampilkan semua</a>
        <?php endif; ?>
    </p>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Produk</th><th>Batch</th><th>Masuk</th><th class="num">Jumlah</th>
                    <th class="num">Sisa</th><th class="num">Harga pokok</th><th>Kedaluwarsa</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$batches): ?>
                <tr><td class="empty-row" colspan="8">Tidak ada batch.</td></tr>
            <?php endif; ?>
            <?php foreach ($batches as $b): ?>
                <?php [$label, $class] = batch_status($b); ?>
                <tr>
                    <td><strong><?= e($b['name']) ?></strong><br><span class="muted"><?= e($b['sku']) ?></span></td>
                    <td><?= ($b['batch_code'] ?? '') !== '' ? e($b['batch_code']) : '#' . (int) $b['id'] ?></td>
                    <td><?= e(substr((string) $b['received_at'], 0, 10)) ?></td>
                    <td class="num"><?= (int) $b['qty_received'] ?> <?= e($b['unit']) ?></td>
                    <td class="num"><?= (int) $b['qty_remaining'] ?></td>
                    <td class="num"><?= e(inventory_rupiah($b['cost_per_unit'])) ?></td>
                    <td><?= $b['expiry_date'] ? e($b['expiry_date']) : '-' ?></td>
                    <td><span class="badge <?= e($class) ?>"><?= e($label) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
