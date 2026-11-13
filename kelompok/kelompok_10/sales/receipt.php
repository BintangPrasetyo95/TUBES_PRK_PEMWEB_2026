<?php

require_once __DIR__ . '/../config/auth.php';
require_role(['admin', 'cashier']);
require_once __DIR__ . '/helpers.php';

$pdo     = db();
$saleId  = (int) ($_GET['id'] ?? 0);
$isAdmin = sales_is_admin();

$pageTitle  = 'Struk Transaksi';
$activePage = 'history';
$navItems   = sales_nav_items();

function sales_receipt_message(int $code, string $message, string $pageTitle, string $activePage, array $navItems): void
{
    http_response_code($code);
    require __DIR__ . '/../includes/header.php';
    echo sales_css_tag();
    echo '<div class="pos-panel"><p class="pos-warning">' . e($message) . '</p>';
    echo '<p><a class="btn" href="history.php">Kembali ke riwayat</a></p></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

if ($saleId < 1) {
    sales_receipt_message(404, 'Transaksi tidak ditemukan.', $pageTitle, $activePage, $navItems);
}

$stmt = $pdo->prepare(
    'SELECT s.*, u.full_name AS cashier_name
       FROM sales s
       LEFT JOIN users u ON u.id = s.cashier_id
      WHERE s.id = ?'
);
$stmt->execute([$saleId]);
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    sales_receipt_message(404, 'Transaksi tidak ditemukan.', $pageTitle, $activePage, $navItems);
}
if (!$isAdmin && (int) $sale['cashier_id'] !== sales_current_user_id()) {
    sales_receipt_message(403, 'Akses ditolak: Anda hanya dapat melihat transaksi milik Anda sendiri.', $pageTitle, $activePage, $navItems);
}

$itemsStmt = $pdo->prepare(
    'SELECT si.batch_id, si.qty, si.unit_price_snapshot, si.cost_price_snapshot,
            si.discount_amount, si.line_total, p.name AS product_name, p.sku
       FROM sale_items si
       JOIN products p ON p.id = si.product_id
      WHERE si.sale_id = ?
      ORDER BY si.id'
);
$itemsStmt->execute([$saleId]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Struk ' . $sale['invoice_no'];

require __DIR__ . '/../includes/header.php';
echo sales_css_tag();
?>
<div class="pos-panel pos-receipt">
    <header class="pos-receipt-head">
        <h2>Struk Transaksi</h2>
        <p class="pos-invoice"><?= e((string) $sale['invoice_no']) ?></p>
        <span class="badge badge-<?= e((string) $sale['status']) ?>"><?= e(sales_status_label((string) $sale['status'])) ?></span>
    </header>

    <dl class="pos-meta">
        <dt>Waktu</dt><dd><?= e((string) $sale['sold_at']) ?></dd>
        <dt>Kasir</dt><dd><?= e((string) ($sale['cashier_name'] ?? '-')) ?></dd>
        <dt>Metode bayar</dt><dd><?= e(sales_payment_label((string) $sale['payment_method'])) ?></dd>
        <?php if (!empty($sale['notes'])): ?>
            <dt>Catatan</dt><dd><?= e((string) $sale['notes']) ?></dd>
        <?php endif; ?>
    </dl>

    <table class="pos-table">
        <thead>
            <tr>
                <th>Produk</th>
                <th>Batch</th>
                <th class="num">Qty</th>
                <th class="num">Harga</th>
                <th class="num">Diskon</th>
                <th class="num">Jumlah</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $it): ?>
            <tr>
                <td><?= e((string) $it['product_name']) ?><br><span class="pos-muted"><?= e((string) $it['sku']) ?></span></td>
                <td>#<?= (int) $it['batch_id'] ?></td>
                <td class="num"><?= (int) $it['qty'] ?></td>
                <td class="num"><?= e(sales_rupiah((string) $it['unit_price_snapshot'])) ?></td>
                <td class="num"><?= e(sales_rupiah((string) $it['discount_amount'])) ?></td>
                <td class="num"><?= e(sales_rupiah((string) $it['line_total'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <dl class="pos-totals">
        <dt>Subtotal</dt><dd><?= e(sales_rupiah((string) $sale['subtotal'])) ?></dd>
        <dt>Diskon</dt><dd><?= e(sales_rupiah((string) $sale['discount_total'])) ?></dd>
        <dt class="pos-grand">Total</dt><dd class="pos-grand"><?= e(sales_rupiah((string) $sale['grand_total'])) ?></dd>
        <dt>Bayar</dt><dd><?= e(sales_rupiah((string) $sale['amount_paid'])) ?></dd>
        <dt>Kembalian</dt><dd><?= e(sales_rupiah((string) $sale['change_amount'])) ?></dd>
    </dl>

    <div class="pos-actions no-print">
        <a class="btn" href="receipt.php?id=<?= (int) $saleId ?>" data-print="1">Cetak (Ctrl+P)</a>
        <a class="btn" href="index.php">Transaksi baru</a>
        <a class="btn" href="history.php">Riwayat</a>
    </div>

    <?php if ($isAdmin && $sale['status'] === 'completed'): ?>
        <form method="post" action="void.php" class="pos-void-form no-print">
            <h3>Batalkan transaksi</h3>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="sale_id" value="<?= (int) $saleId ?>">
            <label for="reason">Alasan pembatalan (wajib)</label>
            <input type="text" name="reason" id="reason" maxlength="200" required autocomplete="off">
            <button type="submit" class="btn btn-danger" data-confirm="Batalkan transaksi ini? Stok akan dipulihkan.">Batalkan transaksi</button>
        </form>
    <?php endif; ?>
</div>
<?php
echo sales_js_tag();
require __DIR__ . '/../includes/footer.php';