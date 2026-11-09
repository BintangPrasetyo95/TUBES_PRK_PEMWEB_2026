<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/helpers.php';

require_role('admin');

$pdo = db();
$perPage = 20;
$types = inventory_movement_types();

$productId = (int) filter_input(INPUT_GET, 'product', FILTER_VALIDATE_INT);
$typeInput = (string) ($_GET['type'] ?? '');
$type = isset($types[$typeInput]) ? $typeInput : '';
$fromInput = trim((string) ($_GET['from'] ?? ''));
$toInput = trim((string) ($_GET['to'] ?? ''));
$page = max(1, (int) filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT));

function movements_parse_date(string $value): ?DateTimeImmutable
{
    if ($value === '') {
        return null;
    }
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    return ($d && $d->format('Y-m-d') === $value) ? $d->setTime(0, 0) : null;
}

$from = movements_parse_date($fromInput);
$to = movements_parse_date($toInput);
$filterError = '';
if (($fromInput !== '' && $from === null) || ($toInput !== '' && $to === null)) {
    $filterError = 'Format tanggal tidak valid.';
    $from = $to = null;
} elseif ($from !== null && $to !== null && $from > $to) {
    $filterError = 'Tanggal awal tidak boleh setelah tanggal akhir. Filter tanggal diabaikan.';
    $from = $to = null;
}

$where = [];
$params = [];
if ($productId > 0) {
    $where[] = 'm.product_id = ?';
    $params[] = $productId;
}
if ($type !== '') {
    $where[] = 'm.movement_type = ?';
    $params[] = $type;
}
if ($from !== null) {
    $where[] = 'm.occurred_at >= ?';
    $params[] = $from->format('Y-m-d H:i:s');
}
if ($to !== null) {
    $where[] = 'm.occurred_at < ?';
    $params[] = $to->modify('+1 day')->format('Y-m-d H:i:s');
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_movements m $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT m.id, m.movement_type, m.quantity, m.unit_cost, m.reason, m.occurred_at,
            p.sku, p.name AS product_name, p.unit,
            b.id AS batch_id, b.batch_code, u.full_name
     FROM inventory_movements m
     JOIN products p ON p.id = m.product_id
     LEFT JOIN stock_batches b ON b.id = m.batch_id
     JOIN users u ON u.id = m.user_id
     $whereSql
     ORDER BY m.occurred_at DESC, m.id DESC
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$products = $pdo->query('SELECT id, sku, name FROM products ORDER BY name')->fetchAll();

function movements_page_url(int $page): string
{
    $query = $_GET;
    $query['page'] = $page;
    return 'movements.php?' . http_build_query($query);
}

$pageTitle = 'Riwayat stok';
$activePage = 'movements';
$navItems = inventory_nav_items();
require __DIR__ . '/../includes/header.php';
echo inventory_css();
?>
<p class="eyebrow">PERSEDIAAN</p>
<h1>Riwayat pergerakan stok</h1>
<p class="muted">Catatan audit semua stok masuk, keluar, dan penjualan. Tidak bisa diubah atau dihapus.</p>

<section class="panel">
    <?php if ($filterError !== ''): ?>
        <div class="notice notice-error" role="alert"><?= e($filterError) ?></div>
    <?php endif; ?>

    <form method="get" action="movements.php" class="filter-bar">
        <div class="form-stack">
            <label for="product">Produk</label>
            <select id="product" name="product">
                <option value="0">Semua produk</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= $productId === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['sku'] . ' - ' . $p['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-stack">
            <label for="type">Tipe</label>
            <select id="type" name="type">
                <option value="">Semua tipe</option>
                <?php foreach ($types as $key => [$label]): ?>
                    <option value="<?= e($key) ?>" <?= $type === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-stack">
            <label for="from">Dari tanggal</label>
            <input id="from" name="from" type="date" value="<?= e($fromInput) ?>">
        </div>
        <div class="form-stack">
            <label for="to">Sampai tanggal</label>
            <input id="to" name="to" type="date" value="<?= e($toInput) ?>">
        </div>
        <button class="button button-secondary" type="submit">Terapkan</button>
    </form>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Waktu</th><th>Produk</th><th>Tipe</th><th>Batch</th>
                    <th class="num">Jumlah</th><th class="num">Harga pokok</th><th>Keterangan</th><th>Oleh</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td class="empty-row" colspan="8">Tidak ada pergerakan yang cocok.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
                <?php [$label, $class, $sign] = $types[$r['movement_type']] ?? [$r['movement_type'], 'badge-muted', '']; ?>
                <tr>
                    <td><?= e(substr((string) $r['occurred_at'], 0, 16)) ?></td>
                    <td><strong><?= e($r['product_name']) ?></strong><br><span class="muted"><?= e($r['sku']) ?></span></td>
                    <td><span class="badge <?= e($class) ?>"><?= e($label) ?></span></td>
                    <td><?= ($r['batch_code'] ?? '') !== '' ? e($r['batch_code']) : ($r['batch_id'] ? '#' . (int) $r['batch_id'] : '-') ?></td>
                    <td class="num"><?= e($sign) ?><?= (int) $r['quantity'] ?> <?= e($r['unit']) ?></td>
                    <td class="num"><?= $r['unit_cost'] !== null ? e(inventory_rupiah($r['unit_cost'])) : '-' ?></td>
                    <td class="muted"><?= ($r['reason'] ?? '') !== '' ? e($r['reason']) : '-' ?></td>
                    <td><?= e($r['full_name']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <?php if ($page > 1): ?>
            <a class="button button-secondary button-small" href="<?= e(movements_page_url($page - 1)) ?>">Sebelumnya</a>
        <?php endif; ?>
        <span class="muted">Halaman <?= $page ?> dari <?= $totalPages ?> (<?= $total ?> catatan)</span>
        <?php if ($page < $totalPages): ?>
            <a class="button button-secondary button-small" href="<?= e(movements_page_url($page + 1)) ?>">Berikutnya</a>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
