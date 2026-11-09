<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../inventory/helpers.php';

require_role('admin');

$pdo = db();
$perPage = 10;

$q = trim((string) ($_GET['q'] ?? ''));
$categoryId = (int) filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
$statusInput = (string) ($_GET['status'] ?? '');
$status = in_array($statusInput, ['active', 'inactive'], true) ? $statusInput : 'all';
$page = max(1, (int) filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT));

$where = [];
$params = [];
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(p.name LIKE ? OR p.sku LIKE ?)';
    array_push($params, $like, $like);
}
if ($categoryId > 0) {
    $where[] = 'p.category_id = ?';
    $params[] = $categoryId;
}
if ($status === 'active') {
    $where[] = 'p.is_active = 1';
} elseif ($status === 'inactive') {
    $where[] = 'p.is_active = 0';
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// $perPage dan $offset sudah int hasil perhitungan, aman ditulis langsung.
$sql = "SELECT p.id, p.sku, p.name, p.unit, p.selling_price, p.min_stock, p.is_active,
               c.name AS category_name,
               COALESCE(SUM(CASE WHEN b.expiry_date IS NULL OR b.expiry_date >= CURDATE()
                                 THEN b.qty_remaining ELSE 0 END), 0) AS stock
        FROM products p
        JOIN categories c ON c.id = p.category_id
        LEFT JOIN stock_batches b ON b.product_id = p.id
        $whereSql
        GROUP BY p.id, p.sku, p.name, p.unit, p.selling_price, p.min_stock, p.is_active, c.name
        ORDER BY p.name
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

function products_page_url(int $page): string
{
    $query = $_GET;
    $query['page'] = $page;
    return 'index.php?' . http_build_query($query);
}

$pageTitle = 'Produk';
$activePage = 'products';
$navItems = inventory_nav_items();
require __DIR__ . '/../includes/header.php';
echo inventory_css();
?>
<div class="panel-header">
    <div>
        <p class="eyebrow">PRODUK</p>
        <h1>Daftar produk</h1>
    </div>
    <a class="button button-primary" href="form.php">+ Tambah produk</a>
</div>

<section class="panel">
    <form method="get" action="index.php" class="filter-bar">
        <div class="form-stack">
            <label for="q">Cari nama atau SKU</label>
            <input id="q" name="q" type="text" maxlength="100" value="<?= e($q) ?>">
        </div>
        <div class="form-stack">
            <label for="category">Kategori</label>
            <select id="category" name="category">
                <option value="0">Semua</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $categoryId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-stack">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Semua</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Aktif</option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
            </select>
        </div>
        <button class="button button-secondary" type="submit">Terapkan</button>
    </form>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Nama</th>
                    <th>Kategori</th>
                    <th class="num">Harga jual</th>
                    <th class="num">Stok</th>
                    <th class="num">Min.</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$products): ?>
                <tr><td class="empty-row" colspan="8">Tidak ada produk yang cocok.</td></tr>
            <?php endif; ?>
            <?php foreach ($products as $p): ?>
                <?php
                $stock = (int) $p['stock'];
                $active = (int) $p['is_active'] === 1;
                ?>
                <tr>
                    <td><?= e($p['sku']) ?></td>
                    <td><strong><?= e($p['name']) ?></strong></td>
                    <td class="muted"><?= e($p['category_name']) ?></td>
                    <td class="num"><?= e(inventory_rupiah($p['selling_price'])) ?></td>
                    <td class="num">
                        <?= $stock ?> <?= e($p['unit']) ?>
                        <?php if ($stock === 0): ?>
                            <span class="badge badge-danger">Habis</span>
                        <?php elseif ($stock <= (int) $p['min_stock']): ?>
                            <span class="badge badge-warning">Menipis</span>
                        <?php endif; ?>
                    </td>
                    <td class="num"><?= (int) $p['min_stock'] ?></td>
                    <td>
                        <span class="badge <?= $active ? 'badge-success' : 'badge-muted' ?>"><?= $active ? 'Aktif' : 'Nonaktif' ?></span>
                    </td>
                    <td class="actions">
                        <a class="button button-secondary button-small" href="form.php?id=<?= (int) $p['id'] ?>">Ubah</a>
                        <form class="inline-form" method="post" action="save.php">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                            <button class="button button-secondary button-small" type="submit"><?= $active ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <?php if ($page > 1): ?>
            <a class="button button-secondary button-small" href="<?= e(products_page_url($page - 1)) ?>">Sebelumnya</a>
        <?php endif; ?>
        <span class="muted">Halaman <?= $page ?> dari <?= $totalPages ?> (<?= $total ?> produk)</span>
        <?php if ($page < $totalPages): ?>
            <a class="button button-secondary button-small" href="<?= e(products_page_url($page + 1)) ?>">Berikutnya</a>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
