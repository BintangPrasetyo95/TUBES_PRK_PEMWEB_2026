<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$pesan_sukses = $_SESSION['flash_success'] ?? '';
$pesan_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);




if (!empty($_SESSION['cart'])) {
    $stmt_refresh = $conn->prepare(
        "SELECT id, sku, name, price, stock FROM products WHERE id = ?"
    );

    foreach ($_SESSION['cart'] as $cart_id => &$cart_item) {
        $product_id = (int)$cart_id;
        $stmt_refresh->bind_param("i", $product_id);
        $stmt_refresh->execute();
        $fresh_product = $stmt_refresh->get_result()->fetch_assoc();

        if (!$fresh_product || (int)$fresh_product['stock'] <= 0) {
            unset($_SESSION['cart'][$cart_id]);
            $pesan_error = 'Sebagian produk di keranjang sudah tidak tersedia.';
            continue;
        }

        $cart_item['id'] = (int)$fresh_product['id'];
        $cart_item['sku'] = $fresh_product['sku'];
        $cart_item['name'] = $fresh_product['name'];
        $cart_item['price'] = (float)$fresh_product['price'];
        $cart_item['stock'] = (int)$fresh_product['stock'];
        $cart_item['qty'] = max(1, (int)$cart_item['qty']);

        if ($cart_item['qty'] > $cart_item['stock']) {
            $cart_item['qty'] = $cart_item['stock'];
            $pesan_error = 'Jumlah keranjang disesuaikan dengan stok terbaru.';
        }
    }
    unset($cart_item);
    $stmt_refresh->close();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_token = $_POST['csrf_token'] ?? '';

    if (!is_string($submitted_token) || !hash_equals($csrf_token, $submitted_token)) {
        $_SESSION['flash_error'] = 'Sesi formulir tidak valid. Silakan coba lagi.';
        header("Location: index.php");
        exit;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah') {
    $product_id = (int)($_POST['product_id'] ?? 0);

    if ($product_id > 0) {
        $stmt = $conn->prepare(
            "SELECT id, sku, name, price, stock FROM products WHERE id = ?"
        );
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();

        if ($product) {
            $current_qty = (int)($_SESSION['cart'][$product_id]['qty'] ?? 0);

            if ((int)$product['stock'] > $current_qty) {
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id]['qty']++;
                } else {
                    $_SESSION['cart'][$product_id] = [
                        'id' => (int)$product['id'],
                        'sku' => $product['sku'],
                        'name' => $product['name'],
                        'price' => (float)$product['price'],
                        'stock' => (int)$product['stock'],
                        'qty' => 1
                    ];
                }
                $_SESSION['flash_success'] = 'Produk berhasil ditambahkan ke keranjang.';
            } else {
                $_SESSION['flash_error'] = 'Stok produk tidak mencukupi.';
            }
        } else {
            $_SESSION['flash_error'] = 'Produk tidak ditemukan.';
        }
    }

    header("Location: index.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 1);

    if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
        $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $max_stock = $product ? (int)$product['stock'] : 0;

        if ($qty < 1) {
            $_SESSION['flash_error'] = 'Jumlah minimal 1.';
        } elseif ($qty > $max_stock) {
            $_SESSION['flash_error'] = "Jumlah melebihi stok yang tersedia (stok: $max_stock).";
        } else {
            $_SESSION['cart'][$product_id]['qty'] = $qty;
            $_SESSION['flash_success'] = 'Jumlah produk berhasil diperbarui.';
        }
    }

    header("Location: index.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'hapus') {
    $product_id = (int)($_POST['product_id'] ?? 0);

    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        $_SESSION['flash_success'] = 'Produk berhasil dihapus dari keranjang.';
    }

    header("Location: index.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'kosongkan') {
    $_SESSION['cart'] = [];
    $_SESSION['flash_success'] = 'Keranjang berhasil dikosongkan.';
    header("Location: index.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'checkout') {
    if (empty($_SESSION['cart'])) {
        $_SESSION['flash_error'] = 'Keranjang belanja masih kosong.';
        header("Location: index.php");
        exit;
    }

    
    $user_id = (int)$_SESSION['user_id'];

    if ($user_id <= 0) {
        $_SESSION['flash_error'] = 'ID pengguna tidak valid. Silakan login kembali.';
        header("Location: ../auth/login.php");
        exit;
    }

    
    $stmt_user = $conn->prepare("SELECT id FROM users WHERE id = ? AND is_active = 1");
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $logged_user = $stmt_user->get_result()->fetch_assoc();

    if (!$logged_user) {
        session_destroy();
        header("Location: ../auth/login.php");
        exit;
    }

    $paid_input = trim((string)($_POST['nominal_bayar'] ?? ''));

    if ($paid_input === '' || !ctype_digit($paid_input)) {
        $_SESSION['flash_error'] = 'Nominal pembayaran harus berupa angka bulat yang valid.';
        header("Location: index.php");
        exit;
    }

    $amount_paid = (float)$paid_input;
    $subtotal_transaksi = 0;

    foreach ($_SESSION['cart'] as $item) {
        $subtotal_transaksi += (float)$item['price'] * (int)$item['qty'];
    }

    $total = $subtotal_transaksi;

    if ($amount_paid < $total) {
        $kurang = $total - $amount_paid;
        $_SESSION['flash_error'] = 'Pembayaran kurang Rp ' . number_format($kurang, 0, ',', '.') . '.';
        header("Location: index.php");
        exit;
    }

    $change_amount = $amount_paid - $total;
    $payment_method = 'cash';
    $invoice_number = 'INV-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

    $conn->begin_transaction();

    try {
        
        $stmt_check_stock = $conn->prepare(
            "SELECT id, name, stock FROM products WHERE id = ? FOR UPDATE"
        );
        $stmt_update_stock = $conn->prepare(
            "UPDATE products SET stock = stock - ? WHERE id = ?"
        );

        foreach ($_SESSION['cart'] as $item) {
            $product_id = (int)$item['id'];
            $qty = (int)$item['qty'];

            $stmt_check_stock->bind_param("i", $product_id);
            $stmt_check_stock->execute();
            $db_product = $stmt_check_stock->get_result()->fetch_assoc();

            if (!$db_product) {
                throw new Exception("Produk '{$item['name']}' tidak ditemukan.");
            }

            if ((int)$db_product['stock'] < $qty) {
                throw new Exception(
                    "Stok '{$db_product['name']}' tidak mencukupi. Stok tersedia: {$db_product['stock']}."
                );
            }

            $stmt_update_stock->bind_param("ii", $qty, $product_id);
            if (!$stmt_update_stock->execute()) {
                throw new Exception("Gagal memperbarui stok produk.");
            }
        }

        




        $stmt_trans = $conn->prepare("
            INSERT INTO transactions
            (invoice_number, user_id, transaction_date, subtotal, total,
             amount_paid, change_amount, payment_method)
            VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)
        ");

        $stmt_trans->bind_param(
            "sidddds",
            $invoice_number,
            $user_id,
            $subtotal_transaksi,
            $total,
            $amount_paid,
            $change_amount,
            $payment_method
        );

        if (!$stmt_trans->execute()) {
            throw new Exception("Gagal menyimpan transaksi: " . $stmt_trans->error);
        }

        $transaction_id = $conn->insert_id;

        




        $stmt_detail = $conn->prepare("
            INSERT INTO transaction_details
            (transaction_id, product_id, product_name, quantity, unit_price, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($_SESSION['cart'] as $item) {
            $product_id = (int)$item['id'];
            $product_name = (string)$item['name'];
            $quantity = (int)$item['qty'];
            $unit_price = (float)$item['price'];
            $detail_subtotal = $unit_price * $quantity;

            $stmt_detail->bind_param(
                "iisidd",
                $transaction_id,
                $product_id,
                $product_name,
                $quantity,
                $unit_price,
                $detail_subtotal
            );

            if (!$stmt_detail->execute()) {
                throw new Exception("Gagal menyimpan detail transaksi: " . $stmt_detail->error);
            }
        }

        $conn->commit();
        $_SESSION['cart'] = [];
        $_SESSION['flash_success'] =
            "Transaksi $invoice_number berhasil diproses. Stok berhasil dipotong. Kembalian: Rp " .
            number_format($change_amount, 0, ',', '.');
    } catch (Throwable $e) {
        $conn->rollback();
        error_log("Checkout FEBMart: " . $e->getMessage());
        $_SESSION['flash_error'] = "Transaksi dibatalkan: " . $e->getMessage();
    }

    header("Location: index.php");
    exit;
}


$search = trim($_GET['cari'] ?? '');

if ($search !== '') {
    $stmt_produk = $conn->prepare("
        SELECT products.*, categories.name AS nama_kategori
        FROM products
        LEFT JOIN categories ON products.category_id = categories.id
        WHERE products.name LIKE ? OR products.sku LIKE ?
        ORDER BY products.name ASC
    ");
    $keyword = "%$search%";
    $stmt_produk->bind_param("ss", $keyword, $keyword);
    $stmt_produk->execute();
    $produk = $stmt_produk->get_result();
} else {
    $produk = $conn->query("
        SELECT products.*, categories.name AS nama_kategori
        FROM products
        LEFT JOIN categories ON products.category_id = categories.id
        ORDER BY products.name ASC
    ");
}

$total_subtotal = 0;
$total_qty = 0;

foreach ($_SESSION['cart'] as $cart_item) {
    $total_subtotal += (float)$cart_item['price'] * (int)$cart_item['qty'];
    $total_qty += (int)$cart_item['qty'];
}

$total_bayar = $total_subtotal;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Kasir | FEBMart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { min-height:100vh; margin:0; padding:35px 20px; background:#f3f4f6; font-family:Arial,sans-serif; }
        .main-container { width:100%; max-width:1200px; margin:0 auto; overflow:hidden; background:#fff; border-radius:14px; box-shadow:0 5px 25px rgba(0,0,0,.08); }
        .header-section { padding:30px 35px; color:#fff; background:linear-gradient(145deg,#29245c,#5142a5); }
        .brand { margin-bottom:25px; font-size:23px; font-weight:bold; }
        .brand i { margin-right:7px; color:#ffad42; }
        .header-section h2 { margin-bottom:8px; font-size:25px; font-weight:bold; }
        .header-section p { margin:0; color:#d5d9e2; font-size:14px; }
        .content-section { padding:35px; }
        .section-card { height:100%; padding:24px; border:1px solid #e8e7f0; border-radius:12px; background:#fff; }
        .section-title { margin-bottom:5px; color:#29245c; font-size:19px; font-weight:bold; }
        .section-description { margin-bottom:22px; color:#888; font-size:13px; }
        .empty-state { min-height:180px; padding:50px 20px; border:1px dashed #d8d5e8; border-radius:10px; color:#888; text-align:center; }
        .empty-state i { display:block; margin-bottom:12px; color:#5142a5; font-size:42px; }
        .summary-box { margin-bottom:20px; padding:18px; border-radius:10px; background:#f5f4fb; }
        .summary-label { color:#888; font-size:13px; }
        .summary-value { margin-top:5px; color:#29245c; font-size:22px; font-weight:bold; }
        .btn-back { display:inline-flex; align-items:center; padding:10px 18px; border:1px solid #ddd; border-radius:7px; color:#5142a5; background:#f5f4fb; font-size:14px; font-weight:bold; text-decoration:none; }
        .catalog-container { max-height:520px; overflow-y:auto; padding-right:5px; }
        .product-card { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:10px; padding:12px; border:1px solid #e8e7f0; border-radius:10px; }
        .product-card:hover { border-color:#5142a5; background:#fbfbfe; }
        .product-img { width:50px; height:50px; object-fit:cover; border-radius:8px; background:#f0f0f4; }
        .product-info { flex:1; min-width:0; }
        .product-name { overflow:hidden; margin-bottom:2px; color:#29245c; font-size:14px; font-weight:bold; white-space:nowrap; text-overflow:ellipsis; }
        .product-meta { color:#777; font-size:12px; }
        .product-price { color:#5142a5; font-size:14px; font-weight:bold; }
        .btn-add-cart { padding:7px 14px; border:0; border-radius:6px; color:#fff; background:#29245c; font-size:12px; font-weight:600; }
        .btn-add-cart:disabled { opacity:.5; }
        .copyright { padding:20px; border-top:1px solid #eee; color:#999; font-size:12px; text-align:center; }
        @media(max-width:768px) { .content-section {padding:18px;} .header-section {padding:24px;} body {padding:12px;} }
    </style>
</head>
<body>
<main class="main-container">
    <header class="header-section">
        <div class="brand"><i class="bi bi-cart3"></i>FEBMart</div>
        <h2><i class="bi bi-cash-register me-2"></i>Transaksi Kasir</h2>
        <p>Kelola transaksi penjualan FEBMart melalui halaman kasir.</p>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <a href="../riwayat/index.php" class="btn btn-sm btn-light"><i class="bi bi-clock-history me-1"></i>Riwayat Transaksi</a>
            <a href="../laporan/index.php" class="btn btn-sm btn-outline-light"><i class="bi bi-bar-chart-line me-1"></i>Laporan Penjualan</a>
        </div>
    </header>

    <section class="content-section">
        <?php if ($pesan_sukses): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($pesan_sukses); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($pesan_error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($pesan_error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="section-card">
                    <h3 class="section-title">Katalog Produk</h3>
                    <p class="section-description">Pilih produk yang ingin ditambahkan ke transaksi.</p>

                    <form method="GET" class="mb-3">
                        <div class="input-group">
                            <input type="text" name="cari" class="form-control" placeholder="Cari nama produk atau SKU..." value="<?= htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
                            <?php if ($search !== ''): ?>
                                <a href="index.php" class="btn btn-outline-danger"><i class="bi bi-x-lg"></i></a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <div class="catalog-container">
                        <?php if ($produk && $produk->num_rows > 0): ?>
                            <?php while ($item = $produk->fetch_assoc()): ?>
                                <div class="product-card">
                                    <?php if (!empty($item['image']) && file_exists(__DIR__ . '/../assets/img/' . $item['image'])): ?>
                                        <img src="../assets/img/<?= htmlspecialchars($item['image']); ?>" class="product-img" alt="<?= htmlspecialchars($item['name']); ?>">
                                    <?php else: ?>
                                        <div class="product-img d-flex align-items-center justify-content-center text-muted"><i class="bi bi-image"></i></div>
                                    <?php endif; ?>

                                    <div class="product-info">
                                        <div class="product-name"><?= htmlspecialchars($item['name']); ?></div>
                                        <div class="product-meta">
                                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($item['sku']); ?></span>
                                            <span class="ms-1">Stok: <strong><?= (int)$item['stock']; ?></strong></span>
                                        </div>
                                    </div>

                                    <div class="text-end">
                                        <div class="product-price mb-1">Rp <?= number_format((float)$item['price'], 0, ',', '.'); ?></div>
                                        <form method="POST" action="index.php" class="m-0">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="action" value="tambah">
                                            <input type="hidden" name="product_id" value="<?= (int)$item['id']; ?>">
                                            <button type="submit" class="btn-add-cart" <?= (int)$item['stock'] <= 0 ? 'disabled' : ''; ?>>
                                                <i class="bi bi-cart-plus me-1"></i><?= (int)$item['stock'] <= 0 ? 'Habis' : 'Pilih'; ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-box-seam"></i>
                                <div>Produk tidak ditemukan.</div>
                                <small>Tidak ada produk yang sesuai dengan pencarian.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h3 class="section-title mb-0">Keranjang Belanja</h3>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-secondary"><?= count($_SESSION['cart']); ?> Item</span>
                            <?php if (!empty($_SESSION['cart'])): ?>
                                <form method="POST" action="index.php" class="m-0" onsubmit="return confirm('Kosongkan semua item di keranjang?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="kosongkan">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Kosongkan</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="section-description">Daftar barang yang dipilih untuk transaksi.</p>

                    <?php if (empty($_SESSION['cart'])): ?>
                        <div class="empty-state mb-4">
                            <i class="bi bi-cart"></i>
                            <div>Keranjang masih kosong.</div>
                            <small>Tambahkan produk dari katalog.</small>
                        </div>
                    <?php else: ?>
                        <div class="mb-3" style="max-height:320px;overflow-y:auto;">
                            <?php foreach ($_SESSION['cart'] as $id => $cart_item): ?>
                                <div class="d-flex justify-content-between align-items-center p-2 mb-2 border rounded bg-light">
                                    <div style="min-width:0;flex:1;">
                                        <div class="fw-bold text-truncate" style="font-size:13px;color:#29245c;">
                                            <?= htmlspecialchars($cart_item['name']); ?>
                                        </div>
                                        <div class="text-muted" style="font-size:11px;">
                                            <?= htmlspecialchars($cart_item['sku']); ?> |
                                            Rp <?= number_format((float)$cart_item['price'], 0, ',', '.'); ?> × <?= (int)$cart_item['qty']; ?>
                                            = <strong>Rp <?= number_format((float)$cart_item['price'] * (int)$cart_item['qty'], 0, ',', '.'); ?></strong>
                                        </div>
                                    </div>

                                    <div class="ms-2 d-flex align-items-center gap-1">
                                        <form method="POST" action="index.php" class="d-flex align-items-center gap-1 m-0">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="product_id" value="<?= (int)$id; ?>">
                                            <input type="number" name="qty" value="<?= (int)$cart_item['qty']; ?>" min="1" max="<?= (int)$cart_item['stock']; ?>" class="form-control form-control-sm text-center" style="width:60px;">
                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Perbarui jumlah"><i class="bi bi-arrow-repeat"></i></button>
                                        </form>
                                        <form method="POST" action="index.php" class="m-0">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="action" value="hapus">
                                            <input type="hidden" name="product_id" value="<?= (int)$id; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus item"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="summary-box">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="summary-label">Total Item / Kuantitas</span>
                            <strong style="font-size:13px;color:#29245c;"><?= count($_SESSION['cart']); ?> jenis (<?= $total_qty; ?> pcs)</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                            <span class="summary-label">Subtotal</span>
                            <strong style="font-size:14px;color:#29245c;">Rp <?= number_format($total_subtotal, 0, ',', '.'); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="summary-label fw-bold" style="color:#29245c;">Total Bayar</div>
                            <div class="summary-value" id="display-total">Rp <?= number_format($total_bayar, 0, ',', '.'); ?></div>
                        </div>
                    </div>

                    <form method="POST" action="index.php" id="form-transaksi">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="action" value="checkout">

                        <div class="mb-3">
                            <label class="form-label fw-bold" style="font-size:13px;color:#29245c;">Nominal Pembayaran (Cash) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold">Rp</span>
                                <input type="number" name="nominal_bayar" id="nominal-bayar" class="form-control"
                                    placeholder="0" min="<?= (int)$total_bayar; ?>" step="1" required
                                    <?= empty($_SESSION['cart']) ? 'disabled' : ''; ?>>
                            </div>
                            <?php if (!empty($_SESSION['cart'])): ?>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary quick-payment" data-amount="<?= (int)$total_bayar; ?>">Uang Pas</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary quick-payment" data-amount="<?= (int)($total_bayar + 10000); ?>">+10.000</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary quick-payment" data-amount="<?= (int)($total_bayar + 20000); ?>">+20.000</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary quick-payment" data-amount="<?= (int)($total_bayar + 50000); ?>">+50.000</button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold" style="font-size:13px;color:#29245c;">Kembalian</label>
                            <div class="p-2 border rounded bg-light d-flex justify-content-between align-items-center">
                                <span class="text-muted" style="font-size:13px;">Uang Kembali:</span>
                                <span class="fw-bold text-success" id="display-kembalian">Rp 0</span>
                            </div>
                        </div>

                        <button type="submit" id="btn-submit-transaksi" class="btn w-100 py-2 fw-bold"
                            style="background:#29245c;color:white;border-radius:8px;"
                            <?= empty($_SESSION['cart']) ? 'disabled' : ''; ?>>
                            <i class="bi bi-check-circle me-1"></i>Selesaikan Transaksi
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <a href="../dashboard.php" class="btn-back mt-4">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Dashboard
        </a>
    </section>

    <footer class="copyright">© 2026 FEBMart. All Rights Reserved.</footer>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const inputBayar = document.getElementById('nominal-bayar');
const displayKembalian = document.getElementById('display-kembalian');
const totalBayar = <?= (int)$total_bayar; ?>;
const btnSubmit = document.getElementById('btn-submit-transaksi');

function hitungKembalian() {
    if (!inputBayar || !displayKembalian) return;

    const bayar = Number(inputBayar.value) || 0;
    const kembalian = bayar - totalBayar;

    if (totalBayar === 0) {
        displayKembalian.textContent = 'Rp 0';
        if (btnSubmit) btnSubmit.disabled = true;
        return;
    }

    if (kembalian >= 0) {
        displayKembalian.textContent = 'Rp ' + Math.floor(kembalian).toLocaleString('id-ID');
        displayKembalian.className = 'fw-bold text-success';
        if (btnSubmit) btnSubmit.disabled = false;
    } else {
        displayKembalian.textContent = 'Kurang Rp ' + Math.floor(Math.abs(kembalian)).toLocaleString('id-ID');
        displayKembalian.className = 'fw-bold text-danger';
        if (btnSubmit) btnSubmit.disabled = true;
    }
}

if (inputBayar) {
    inputBayar.addEventListener('input', hitungKembalian);
    hitungKembalian();
}

document.querySelectorAll('.quick-payment').forEach((button) => {
    button.addEventListener('click', () => {
        inputBayar.value = button.dataset.amount;
        hitungKembalian();
    });
});
</script>
</body>
</html>