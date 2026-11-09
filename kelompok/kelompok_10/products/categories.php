<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../inventory/helpers.php';

require_role('admin');

$pdo = db();

$editing = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
if ($editId) {
    $stmt = $pdo->prepare('SELECT id, name, description FROM categories WHERE id = ?');
    $stmt->execute([$editId]);
    $editing = $stmt->fetch() ?: null;
}

$categories = $pdo->query(
    'SELECT c.id, c.name, c.description, c.is_active, COUNT(p.id) AS product_count
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id, c.name, c.description, c.is_active
     ORDER BY c.name'
)->fetchAll();

$pageTitle = 'Kategori produk';
$activePage = 'categories';
$navItems = inventory_nav_items();
require __DIR__ . '/../includes/header.php';
echo inventory_css();
?>
<p class="eyebrow">PRODUK</p>
<h1>Kategori</h1>
<p class="muted">Kelompokkan produk agar mudah dicari dan difilter.</p>

<div class="inv-grid">
    <section class="panel">
        <h2><?= $editing ? 'Ubah kategori' : 'Tambah kategori' ?></h2>
        <form method="post" action="category_save.php" class="form-stack">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save">
            <?php if ($editing): ?>
                <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
            <?php endif; ?>

            <label for="name">Nama kategori</label>
            <input id="name" name="name" type="text" maxlength="100" required
                   value="<?= e($editing['name'] ?? '') ?>">

            <label for="description">Deskripsi (opsional)</label>
            <input id="description" name="description" type="text" maxlength="255"
                   value="<?= e($editing['description'] ?? '') ?>">

            <button class="button button-primary button-wide" type="submit">
                <?= $editing ? 'Simpan perubahan' : 'Tambah kategori' ?>
            </button>
            <?php if ($editing): ?>
                <a class="button button-secondary button-wide" href="categories.php">Batal</a>
            <?php endif; ?>
        </form>
    </section>

    <section class="panel">
        <div class="panel-header">
            <h2>Daftar kategori</h2>
            <span class="muted"><?= count($categories) ?> kategori</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Deskripsi</th>
                        <th class="num">Produk</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$categories): ?>
                    <tr><td class="empty-row" colspan="5">Belum ada kategori. Tambahkan yang pertama di sebelah kiri.</td></tr>
                <?php endif; ?>
                <?php foreach ($categories as $c): ?>
                    <?php $active = (int) $c['is_active'] === 1; ?>
                    <tr>
                        <td><strong><?= e($c['name']) ?></strong></td>
                        <td class="muted"><?= ($c['description'] ?? '') !== '' ? e($c['description']) : '-' ?></td>
                        <td class="num"><?= (int) $c['product_count'] ?></td>
                        <td>
                            <span class="badge <?= $active ? 'badge-success' : 'badge-muted' ?>">
                                <?= $active ? 'Aktif' : 'Nonaktif' ?>
                            </span>
                        </td>
                        <td class="actions">
                            <a class="button button-secondary button-small" href="categories.php?edit=<?= (int) $c['id'] ?>">Ubah</a>
                            <form class="inline-form" method="post" action="category_save.php">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <button class="button button-secondary button-small" type="submit">
                                    <?= $active ? 'Nonaktifkan' : 'Aktifkan' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
