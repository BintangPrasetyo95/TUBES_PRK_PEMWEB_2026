<?php

require_once __DIR__ . '/../config/auth.php';
require_role(['admin', 'cashier']);
require_once __DIR__ . '/helpers.php';

const SALES_HISTORY_PER_PAGE = 20;

$pdo     = db();
$isAdmin = sales_is_admin();
$meId    = sales_current_user_id();

$from      = trim((string) ($_GET['from'] ?? ''));
$to        = trim((string) ($_GET['to'] ?? ''));
$invoice   = trim((string) ($_GET['invoice'] ?? ''));
$status    = trim((string) ($_GET['status'] ?? ''));
$cashierId = $isAdmin ? (int) ($_GET['cashier_id'] ?? 0) : $meId;
$page      = max(1, (int) ($_GET['page'] ?? 1));

$errors = [];

$validDate = static function (string $d): bool {
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt !== false && $dt->format('Y-m-d') === $d;
};
if ($from !== '' && !$validDate($from)) {
    $errors[] = 'Format tanggal awal tidak valid.';
}
if ($to !== '' && !$validDate($to)) {
    $errors[] = 'Format tanggal akhir tidak valid.';
}
if (!$errors && $from !== '' && $to !== '' && $from > $to) {
    $errors[] = 'Tanggal awal tidak boleh setelah tanggal akhir.';
}
if ($status !== '' && !isset(SALES_STATUS_LABELS[$status])) {
    $status = '';
}

$rows  = [];
$total = 0;

if (!$errors) {
    $where  = [];
    $params = [];

    if ($from !== '') {
        $where[]  = 's.sold_at >= ?';
        $params[] = $from . ' 00:00:00';
    }
    if ($to !== '') {
        $where[]  = 's.sold_at < DATE_ADD(?, INTERVAL 1 DAY)';
        $params[] = $to;
    }
    if ($invoice !== '') {
        $where[]  = 's.invoice_no LIKE ?';
        $params[] = '%' . addcslashes($invoice, '%_\\') . '%';
    }
    if ($status !== '') {
        $where[]  = 's.status = ?';
        $params[] = $status;
    }
    if ($cashierId > 0) {
        $where[]  = 's.cashier_id = ?';
        $params[] = $cashierId;
    }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM sales s $whereSql");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $pages  = max(1, (int) ceil($total / SALES_HISTORY_PER_PAGE));
    $page   = min($page, $pages);
    $limit  = (int) SALES_HISTORY_PER_PAGE;
    $offset = (int) (($page - 1) * SALES_HISTORY_PER_PAGE);

    $listStmt = $pdo->prepare(
        "SELECT s.id, s.invoice_no, s.sold_at, s.payment_method, s.grand_total, s.status,
                u.full_name AS cashier_name
           FROM sales s
           LEFT JOIN users u ON u.id = s.cashier_id
           $whereSql
          ORDER BY s.sold_at DESC, s.id DESC
          LIMIT $limit OFFSET $offset"
    );
    $listStmt->execute($params);
    $rows = $listStmt->fetchAll(PDO::FETCH_ASSOC);
}
$pages = max(1, (int) ceil($total / SALES_HISTORY_PER_PAGE));

$cashiers = [];
if ($isAdmin) {
    $cashiers = $pdo->query("SELECT id, full_name AS name FROM users WHERE is_active = 1 AND role IN ('admin','cashier') ORDER BY full_name")
                    ->fetchAll(PDO::FETCH_ASSOC);
}

$pageUrl = static function (int $p) use ($from, $to, $invoice, $status, $cashierId, $isAdmin): string {
    $qs = array_filter([
        'from'       => $from,
        'to'         => $to,
        'invoice'    => $invoice,
        'status'     => $status,
        'cashier_id' => $isAdmin && $cashierId > 0 ? $cashierId : '',
        'page'       => $p,
    ], static fn($v) => $v !== '' && $v !== null);
    return 'history.php?' . http_build_query($qs);
};

foreach ($errors as $msg) {
    flash('error', $msg);
}

$pageTitle  = 'Riwayat Transaksi';
$activePage = 'history';
$navItems   = sales_nav_items();

require __DIR__ . '/../includes/header.php';
echo sales_css_tag();
?>
<div class="pos-panel">
    <h2>Riwayat Transaksi</h2>

    <form method="get" action="history.php" class="pos-filter">
        <label>Dari
            <input type="date" name="from" value="<?= e($from) ?>">
        </label>
        <label>Sampai
            <input type="date" name="to" value="<?= e($to) ?>">
        </label>
        <label>Invoice
            <input type="text" name="invoice" value="<?= e($invoice) ?>" placeholder="INV-..." autocomplete="off">
        </label>
        <label>Status
            <select name="status">
                <option value="">Semua</option>
                <?php foreach (SALES_STATUS_LABELS as $value => $label): ?>
                    <option value="<?= e($value) ?>"<?= $status === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php if ($isAdmin): ?>
            <label>Kasir
                <select name="cashier_id">
                    <option value="0">Semua</option>
                    <?php foreach ($cashiers as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"<?= $cashierId === (int) $c['id'] ? ' selected' : '' ?>><?= e((string) $c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">Terapkan</button>
        <a class="btn" href="history.php">Reset</a>
    </form>

    <table class="pos-table">
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Waktu</th>
                <th>Kasir</th>
                <th>Metode</th>
                <th class="num">Total</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="7" class="pos-empty">Tidak ada transaksi.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= e((string) $r['invoice_no']) ?></td>
                <td><?= e((string) $r['sold_at']) ?></td>
                <td><?= e((string) ($r['cashier_name'] ?? '-')) ?></td>
                <td><?= e(sales_payment_label((string) $r['payment_method'])) ?></td>
                <td class="num"><?= e(sales_rupiah((string) $r['grand_total'])) ?></td>
                <td><span class="badge badge-<?= e((string) $r['status']) ?>"><?= e(sales_status_label((string) $r['status'])) ?></span></td>
                <td><a class="btn btn-small" href="receipt.php?id=<?= (int) $r['id'] ?>">Detail</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($pages > 1): ?>
        <nav class="pos-pagination" aria-label="Halaman">
            <?php if ($page > 1): ?>
                <a class="btn btn-small" href="<?= e($pageUrl($page - 1)) ?>">&laquo; Sebelumnya</a>
            <?php endif; ?>
            <span>Halaman <?= (int) $page ?> dari <?= (int) $pages ?> (<?= (int) $total ?> transaksi)</span>
            <?php if ($page < $pages): ?>
                <a class="btn btn-small" href="<?= e($pageUrl($page + 1)) ?>">Berikutnya &raquo;</a>
            <?php endif; ?>
        </nav>
    <?php else: ?>
        <p class="pos-muted"><?= (int) $total ?> transaksi</p>
    <?php endif; ?>
</div>
<?php
echo sales_js_tag();
require __DIR__ . '/../includes/footer.php';