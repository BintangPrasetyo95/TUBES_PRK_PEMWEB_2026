<?php
declare(strict_types=1);

/** Menu sidebar untuk halaman modul inventory/produk (admin). */
function inventory_nav_items(): array
{
    return [
        ['key' => 'dashboard',  'label' => 'Dashboard',    'href' => 'dashboard.php'],
        ['key' => 'categories', 'label' => 'Kategori',     'href' => 'products/categories.php'],
        ['key' => 'products',   'label' => 'Produk',       'href' => 'products/index.php'],
        ['key' => 'stock_in',   'label' => 'Stok masuk',   'href' => 'inventory/stock_in.php'],
        ['key' => 'stock_out',  'label' => 'Stok keluar',  'href' => 'inventory/stock_out.php'],
        ['key' => 'batches',    'label' => 'Batch',        'href' => 'inventory/batches.php'],
        ['key' => 'movements',  'label' => 'Riwayat stok', 'href' => 'inventory/movements.php'],
        ['key' => 'alerts',     'label' => 'Peringatan',   'href' => 'inventory/alerts.php'],
    ];
}

/** Tag CSS modul. Path relatif dari folder products/ atau inventory/. */
function inventory_css(): string
{
    return '<link rel="stylesheet" href="../assets/css/inventory.css">';
}

/** Redirect relatif terhadap halaman saat ini (tidak tergantung app_url). */
function inventory_redirect(string $relativeUrl): never
{
    header('Location: ' . $relativeUrl);
    exit;
}

/** Format rupiah tanpa desimal, mis. Rp3.500 */
function inventory_rupiah(string|int|float|null $value): string
{
    return 'Rp' . number_format((float) $value, 0, ',', '.');
}
