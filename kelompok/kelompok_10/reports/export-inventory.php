<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';

require_login();
require_role('admin');

$pdo = db();

// Mengambil data inventaris.
$inventoryQuery = $pdo->query("
    SELECT
        p.sku,
        p.name,
        p.min_stock,

        COALESCE(
            (
                SELECT SUM(sb2.qty_remaining)
                FROM stock_batches sb2
                WHERE sb2.product_id = p.id
            ),
            0
        ) AS available_stock,

        sb.batch_code,
        sb.qty_remaining AS batch_stock,
        sb.expiry_date

    FROM products p

    LEFT JOIN stock_batches sb
        ON sb.product_id = p.id
        AND sb.qty_remaining > 0

    WHERE p.is_active = 1

    ORDER BY
        p.name ASC,
        sb.expiry_date ASC
");

$inventory = $inventoryQuery->fetchAll();

// Menyiapkan nama file.
$filename = 'laporan-inventaris-' . date('Y-m-d-His') . '.csv';

// Mengirim header unduhan.
header('Content-Type: text/csv; charset=UTF-8');
header(
    'Content-Disposition: attachment; filename="' . $filename . '"'
);
header('Cache-Control: no-store, no-cache, must-revalidate');

// Membuka output CSV.
$output = fopen('php://output', 'w');

if ($output === false) {
    http_response_code(500);
    exit('Gagal membuat file CSV.');
}

// BOM UTF-8 untuk kompatibilitas Excel.
fwrite($output, "\xEF\xBB\xBF");

// Mencegah formula spreadsheet dari data teks.
$safeCsvValue = static function (mixed $value): string {
    $value = (string) $value;

    if (preg_match('/^[\s]*[=+\-@]/u', $value)) {
        return "'" . $value;
    }

    return $value;
};

// Header kolom CSV.
fputcsv($output, [
    'No.',
    'SKU',
    'Nama Produk',
    'Stok Tersedia',
    'Minimum Stok',
    'Kode Batch',
    'Stok Batch',
    'Tanggal Kedaluwarsa',
    'Status Stok',
]);

// Mengisi data CSV.
foreach ($inventory as $index => $product) {
    $availableStock = (int) $product['available_stock'];
    $minimumStock = (int) $product['min_stock'];

    $status = $availableStock <= $minimumStock
        ? 'Stok Menipis'
        : 'Stok Aman';

    $expiryDate = $product['expiry_date']
        ? date('d-m-Y', strtotime($product['expiry_date']))
        : '-';

    fputcsv($output, [
        $index + 1,
        $safeCsvValue($product['sku']),
        $safeCsvValue($product['name']),
        $availableStock,
        $minimumStock,
        $safeCsvValue($product['batch_code'] ?? '-'),
        $product['batch_stock'] !== null
            ? (int) $product['batch_stock']
            : '-',
        $expiryDate,
        $status,
    ]);
}

fclose($output);
exit;
