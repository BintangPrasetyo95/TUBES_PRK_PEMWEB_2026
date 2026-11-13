<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_role(['admin', 'cashier']);
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/sales_service.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metode tidak diizinkan.');
}

require_valid_csrf();

$cart = cart_items();
if ($cart === []) {
    flash('error', 'Keranjang kosong.');
    sales_redirect('index.php');
}

$method = (string) ($_POST['payment_method'] ?? '');
$amountPaid = isset($_POST['amount_paid']) ? trim((string) $_POST['amount_paid']) : null;
$notes = trim((string) ($_POST['notes'] ?? ''));
if (mb_strlen($notes) > 255) {
    $notes = mb_substr($notes, 0, 255);
}
$notes = $notes === '' ? null : $notes;

$user = current_user();

try {
    $result = sales_checkout(db(), (int) $user['id'], $cart, $method, $amountPaid, $notes);

    cart_clear();
    flash('success', 'Transaksi ' . $result['invoice_no'] . ' berhasil.');
    sales_redirect('receipt.php?id=' . $result['sale_id']);
} catch (PDOException $e) {

    error_log('sales_checkout (DB): ' . $e->getMessage());
    flash('error', 'Transaksi gagal disimpan. Stok tidak berubah. Coba lagi atau hubungi admin.');
} catch (InvalidArgumentException | RuntimeException $e) {

    flash('error', $e->getMessage());
} catch (Throwable $e) {
    error_log('sales_checkout: ' . $e->getMessage());
    flash('error', 'Transaksi gagal diproses. Stok tidak berubah. Coba lagi atau hubungi admin.');
}

sales_redirect('index.php');
