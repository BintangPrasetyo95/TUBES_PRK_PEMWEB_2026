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

// ===== AKSI KELOLA USER (tab Users) — hanya admin =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['tambah_user', 'edit_user', 'hapus_user'], true)) {
    if (($_SESSION['role'] ?? '') !== 'admin') {
        $_SESSION['flash_error'] = 'Anda tidak memiliki izin untuk mengelola pengguna.';
        header("Location: index.php?tab=users");
        exit;
    }

    if (($_POST['action'] ?? '') === 'tambah_user') {
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $role = $_POST['role'] ?? '';

        if ($name === '' || $username === '' || $password === '' || !in_array($role, ['admin', 'kasir'], true)) {
            $_SESSION['flash_error'] = 'Semua data wajib diisi dengan benar!';
            header("Location: index.php?tab=users");
            exit;
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Format email tidak valid!';
            header("Location: index.php?tab=users");
            exit;
        }

        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $check->close();
            $_SESSION['flash_error'] = 'Username sudah digunakan! Silakan gunakan username lain.';
            header("Location: index.php?tab=users");
            exit;
        }
        $check->close();

        if ($email !== '') {
            $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $check->close();
                $_SESSION['flash_error'] = 'Email sudah digunakan! Silakan gunakan email lain.';
                header("Location: index.php?tab=users");
                exit;
            }
            $check->close();
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $username, $email, $hashed_password, $role);
        if ($stmt->execute()) {
            $_SESSION['flash_success'] = 'Pengguna baru berhasil ditambahkan!';
        } else {
            $_SESSION['flash_error'] = 'Gagal menambahkan pengguna: ' . $stmt->error;
        }
        $stmt->close();
        header("Location: index.php?tab=users");
        exit;
    }

    if (($_POST['action'] ?? '') === 'edit_user') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';

        if ($id <= 0 || $name === '' || $username === '' || !in_array($role, ['admin', 'kasir'], true)) {
            $_SESSION['flash_error'] = 'Data pengguna tidak valid!';
            header("Location: index.php?tab=users");
            exit;
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Format email tidak valid!';
            header("Location: index.php?tab=users");
            exit;
        }

        $check = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check->bind_param("si", $username, $id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $check->close();
            $_SESSION['flash_error'] = 'Username sudah digunakan oleh pengguna lain!';
            header("Location: index.php?tab=users");
            exit;
        }
        $check->close();

        if ($email !== '') {
            $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check->bind_param("si", $email, $id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $check->close();
                $_SESSION['flash_error'] = 'Email sudah digunakan oleh pengguna lain!';
                header("Location: index.php?tab=users");
                exit;
            }
            $check->close();
        }

        $stmt = $conn->prepare("UPDATE users SET name = ?, username = ?, email = ?, role = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $username, $email, $role, $id);
        if ($stmt->execute()) {
            $_SESSION['flash_success'] = 'Data pengguna berhasil diperbarui!';
        } else {
            $_SESSION['flash_error'] = 'Gagal memperbarui pengguna: ' . $stmt->error;
        }
        $stmt->close();
        header("Location: index.php?tab=users");
        exit;
    }

    if (($_POST['action'] ?? '') === 'hapus_user') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Pengguna gagal dihapus.';
            header("Location: index.php?tab=users");
            exit;
        }
        if ($id === (int)$_SESSION['user_id']) {
            $_SESSION['flash_error'] = 'Anda tidak dapat menghapus akun sendiri!';
            header("Location: index.php?tab=users");
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $_SESSION['flash_success'] = 'Pengguna berhasil dihapus!';
        } else {
            $_SESSION['flash_error'] = 'Pengguna gagal dihapus.';
        }
        $stmt->close();
        header("Location: index.php?tab=users");
        exit;
    }
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

// ===== HELPERS (untuk tab Riwayat & Laporan) =====
if (!function_exists('h')) {
    function h(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('rupiah')) {
    function rupiah(float|string $amount): string
    {
        return 'Rp ' . number_format((float)$amount, 0, ',', '.');
    }
}
if (!function_exists('parse_date')) {
    function parse_date(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();
        if (!$date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $value) {
            return null;
        }
        return $date;
    }
}

// Tab aktif pada halaman kasir (Kasir | Riwayat | Laporan | Kelola User)
$aktif_tab = $_GET['tab'] ?? 'kasir';
$aktif_tab = in_array($aktif_tab, ['kasir', 'riwayat', 'laporan', 'users'], true) ? $aktif_tab : 'kasir';

// ===== DATA TAB RIWAYAT TRANSAKSI =====
$per_page = 20;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = $page !== false && $page !== null ? max(1, $page) : 1;

$count_result = $conn->query("SELECT COUNT(*) AS total FROM transactions");
$total_transactions = (int)$count_result->fetch_assoc()['total'];
$revenue_result = $conn->query("SELECT COALESCE(SUM(total), 0) AS revenue FROM transactions");
$total_revenue = (float)$revenue_result->fetch_assoc()['revenue'];
$total_pages = max(1, (int)ceil($total_transactions / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

$stmt = $conn->prepare("
    SELECT t.id, t.invoice_number, t.transaction_date, t.subtotal, t.total,
           t.amount_paid, t.change_amount, t.payment_method,
           u.name AS cashier_name,
           (SELECT COUNT(*) FROM transaction_details td WHERE td.transaction_id = t.id) AS item_count
    FROM transactions t
    INNER JOIN users u ON u.id = t.user_id
    ORDER BY t.transaction_date DESC, t.id DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $per_page, $offset);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$details_by_transaction = [];
if ($transactions) {
    $transaction_ids = array_map(static fn(array $transaction): int => (int)$transaction['id'], $transactions);
    $id_list = implode(',', $transaction_ids);
    $details_result = $conn->query("
        SELECT transaction_id, product_name, quantity, unit_price, subtotal
        FROM transaction_details
        WHERE transaction_id IN ($id_list)
        ORDER BY transaction_id, id
    ");
    while ($detail = $details_result->fetch_assoc()) {
        $details_by_transaction[(int)$detail['transaction_id']][] = $detail;
    }
}

// ===== DATA TAB LAPORAN PENJUALAN =====
$today = new DateTimeImmutable('today');
$start_input = is_string($_GET['tanggal_mulai'] ?? null) ? trim($_GET['tanggal_mulai']) : $today->modify('first day of this month')->format('Y-m-d');
$end_input = is_string($_GET['tanggal_akhir'] ?? null) ? trim($_GET['tanggal_akhir']) : $today->format('Y-m-d');
$start_date = parse_date($start_input);
$end_date = parse_date($end_input);
$error_laporan = '';
if (!$start_date || !$end_date) {
    $error_laporan = 'Format tanggal tidak valid. Gunakan tanggal mulai dan akhir yang benar.';
} elseif ($start_date > $end_date) {
    $error_laporan = 'Tanggal mulai tidak boleh melewati tanggal akhir.';
}

$laporan_transactions = [];
$laporan_summary = ['transaction_count' => 0, 'revenue' => 0, 'items_sold' => 0];
if ($error_laporan === '') {
    $start_bound = $start_date->format('Y-m-d 00:00:00');
    $end_bound = $end_date->modify('+1 day')->format('Y-m-d 00:00:00');

    $summary_stmt = $conn->prepare("
        SELECT COUNT(*) AS transaction_count, COALESCE(SUM(total), 0) AS revenue
        FROM transactions
        WHERE transaction_date >= ? AND transaction_date < ?
    ");
    $summary_stmt->bind_param("ss", $start_bound, $end_bound);
    $summary_stmt->execute();
    $laporan_summary = array_merge($laporan_summary, $summary_stmt->get_result()->fetch_assoc());
    $summary_stmt->close();
    $laporan_summary['average_transaction'] = (int)$laporan_summary['transaction_count'] > 0
        ? (float)$laporan_summary['revenue'] / (int)$laporan_summary['transaction_count']
        : 0;

    $items_stmt = $conn->prepare("
        SELECT COALESCE(SUM(td.quantity), 0) AS items_sold
        FROM transaction_details td
        INNER JOIN transactions t ON t.id = td.transaction_id
        WHERE t.transaction_date >= ? AND t.transaction_date < ?
    ");
    $items_stmt->bind_param("ss", $start_bound, $end_bound);
    $items_stmt->execute();
    $laporan_summary['items_sold'] = (int)$items_stmt->get_result()->fetch_assoc()['items_sold'];
    $items_stmt->close();

    $transactions_stmt = $conn->prepare("
        SELECT t.id, t.invoice_number, t.transaction_date, t.total, t.payment_method,
               u.name AS cashier_name,
               (SELECT COALESCE(SUM(td.quantity), 0)
                FROM transaction_details td
                WHERE td.transaction_id = t.id) AS items_sold
        FROM transactions t
        INNER JOIN users u ON u.id = t.user_id
        WHERE t.transaction_date >= ? AND t.transaction_date < ?
        ORDER BY t.transaction_date DESC, t.id DESC
    ");
    $transactions_stmt->bind_param("ss", $start_bound, $end_bound);
    $transactions_stmt->execute();
    $laporan_transactions = $transactions_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $transactions_stmt->close();
}

// ===== DATA TAB KELOLA USER (khusus admin) =====
$is_admin = ($_SESSION['role'] ?? '') === 'admin';
$users_list = [];
if ($is_admin) {
    $users_result = $conn->query(
        "SELECT id, name, username, email, role, is_active, created_at
         FROM users
         ORDER BY id DESC"
    );
    if ($users_result) {
        $users_list = $users_result->fetch_all(MYSQLI_ASSOC);
        $users_result->free();
    }
}
?>

<?php
$pageTitle = 'Transaksi Kasir';
require_once __DIR__ . '/../includes/header.php';
?>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="app-layout d-flex flex-grow-1">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content flex-grow-1 p-4">

        <div class="mb-4">
            <h3 class="fw-bold">
                <i class="bi bi-cash-register me-2"></i>Transaksi Kasir
            </h3>
            <p class="text-muted">
                Kelola transaksi penjualan FEBMart.
            </p>
            <ul class="nav nav-pills gap-2 mt-3">
                <li class="nav-item">
                    <a class="nav-link <?= $aktif_tab === 'kasir' ? 'active' : '' ?>" href="#tab-kasir" data-bs-toggle="tab">
                        <i class="bi bi-cash-register me-1"></i>Kasir
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $aktif_tab === 'riwayat' ? 'active' : '' ?>" href="#tab-riwayat" data-bs-toggle="tab">
                        <i class="bi bi-clock-history me-1"></i>Riwayat Transaksi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $aktif_tab === 'laporan' ? 'active' : '' ?>" href="#tab-laporan" data-bs-toggle="tab">
                        <i class="bi bi-bar-chart-line me-1"></i>Laporan Penjualan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $aktif_tab === 'users' ? 'active' : '' ?>" href="#tab-users" data-bs-toggle="tab">
                        <i class="bi bi-people me-1"></i>Kelola User
                    </a>
                </li>
            </ul>
        </div>

        <?php if ($pesan_sukses): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-1"></i>
                <?= htmlspecialchars($pesan_sukses); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($pesan_error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <?= htmlspecialchars($pesan_error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="tab-content">
        <div class="tab-pane fade show <?= $aktif_tab === 'kasir' ? 'active' : '' ?>" id="tab-kasir">

        <div class="row g-4">

            <!-- KATALOG PRODUK -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">

                        <h5 class="fw-bold mb-1">Katalog Produk</h5>
                        <p class="text-muted small">
                            Pilih produk untuk ditambahkan ke transaksi.
                        </p>

                        <form method="GET" class="mb-3">
                            <div class="input-group">
                                <input type="text" name="cari"
                                    class="form-control"
                                    placeholder="Cari nama produk atau SKU..."
                                    value="<?= htmlspecialchars($search); ?>">

                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="bi bi-search"></i>
                                </button>

                                <?php if ($search !== ''): ?>
                                    <a href="index.php" class="btn btn-outline-danger">
                                        <i class="bi bi-x-lg"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>

                        <div style="max-height:520px;overflow-y:auto;">

                            <?php if ($produk && $produk->num_rows > 0): ?>

                                <?php while ($item = $produk->fetch_assoc()): ?>

                                    <div class="d-flex align-items-center gap-3 border rounded p-3 mb-2">

                                        <?php if (!empty($item['image']) && file_exists(__DIR__ . '/../assets/img/' . $item['image'])): ?>

                                            <img src="../assets/img/<?= htmlspecialchars($item['image']); ?>"
                                                width="55" height="55"
                                                style="object-fit:cover;border-radius:8px;"
                                                alt="<?= htmlspecialchars($item['name']); ?>">

                                        <?php else: ?>

                                            <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                                style="width:55px;height:55px;">
                                                <i class="bi bi-image fs-4 text-muted"></i>
                                            </div>

                                        <?php endif; ?>

                                        <div class="flex-grow-1 overflow-hidden">
                                            <div class="fw-bold text-truncate">
                                                <?= htmlspecialchars($item['name']); ?>
                                            </div>

                                            <small class="text-muted">
                                                <?= htmlspecialchars($item['sku']); ?>
                                                | Stok: <?= (int)$item['stock']; ?>
                                            </small>

                                            <div class="fw-bold text-primary">
                                                Rp <?= number_format((float)$item['price'], 0, ',', '.'); ?>
                                            </div>
                                        </div>

                                        <form method="POST" action="index.php" class="m-0">
                                            <input type="hidden" name="csrf_token"
                                                value="<?= htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="action" value="tambah">
                                            <input type="hidden" name="product_id"
                                                value="<?= (int)$item['id']; ?>">

                                            <button type="submit"
                                                class="btn btn-sm btn-primary"
                                                <?= (int)$item['stock'] <= 0 ? 'disabled' : ''; ?>>

                                                <i class="bi bi-cart-plus"></i>
                                                <?= (int)$item['stock'] <= 0 ? 'Habis' : 'Pilih'; ?>

                                            </button>
                                        </form>

                                    </div>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <div class="text-center text-muted py-5">
                                    <i class="bi bi-box-seam fs-1 d-block mb-2"></i>
                                    Produk tidak ditemukan.
                                </div>

                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>

            <!-- KERANJANG -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0">Keranjang Belanja</h5>

                            <span class="badge bg-primary">
                                <?= count($_SESSION['cart']); ?> Item
                            </span>
                        </div>

                        <?php if (!empty($_SESSION['cart'])): ?>

                            <form method="POST" class="mb-3"
                                onsubmit="return confirm('Kosongkan semua keranjang?');">

                                <input type="hidden" name="csrf_token"
                                    value="<?= htmlspecialchars($csrf_token); ?>">
                                <input type="hidden" name="action" value="kosongkan">

                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i> Kosongkan Keranjang
                                </button>
                            </form>

                        <?php endif; ?>

                        <?php if (empty($_SESSION['cart'])): ?>

                            <div class="text-center text-muted py-5 border rounded mb-3">
                                <i class="bi bi-cart fs-1 d-block mb-2"></i>
                                Keranjang masih kosong.
                            </div>

                        <?php else: ?>

                            <div style="max-height:320px;overflow-y:auto;">

                                <?php foreach ($_SESSION['cart'] as $id => $cart_item): ?>

                                    <div class="border rounded p-3 mb-2">

                                        <div class="fw-bold">
                                            <?= htmlspecialchars($cart_item['name']); ?>
                                        </div>

                                        <small class="text-muted">
                                            <?= htmlspecialchars($cart_item['sku']); ?>
                                            <br>
                                            Rp <?= number_format((float)$cart_item['price'], 0, ',', '.'); ?>
                                            Ã— <?= (int)$cart_item['qty']; ?>
                                        </small>

                                        <div class="d-flex justify-content-between align-items-center mt-2">

                                            <strong class="text-primary">
                                                Rp <?= number_format(
                                                    (float)$cart_item['price'] * (int)$cart_item['qty'],
                                                    0, ',', '.'
                                                ); ?>
                                            </strong>

                                            <div class="d-flex gap-1">

                                                <form method="POST" class="d-flex gap-1">
                                                    <input type="hidden" name="csrf_token"
                                                        value="<?= htmlspecialchars($csrf_token); ?>">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="product_id"
                                                        value="<?= (int)$id; ?>">

                                                    <input type="number" name="qty"
                                                        value="<?= (int)$cart_item['qty']; ?>"
                                                        min="1"
                                                        max="<?= (int)$cart_item['stock']; ?>"
                                                        class="form-control form-control-sm"
                                                        style="width:65px;">

                                                    <button class="btn btn-sm btn-outline-primary"
                                                        type="submit">
                                                        <i class="bi bi-arrow-repeat"></i>
                                                    </button>
                                                </form>

                                                <form method="POST">
                                                    <input type="hidden" name="csrf_token"
                                                        value="<?= htmlspecialchars($csrf_token); ?>">
                                                    <input type="hidden" name="action" value="hapus">
                                                    <input type="hidden" name="product_id"
                                                        value="<?= (int)$id; ?>">

                                                    <button class="btn btn-sm btn-outline-danger"
                                                        type="submit">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>

                                            </div>
                                        </div>
                                    </div>

                                <?php endforeach; ?>

                            </div>

                        <?php endif; ?>

                        <!-- RINGKASAN -->
                        <div class="bg-light rounded p-3 my-3">

                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Total Item</span>
                                <strong>
                                    <?= count($_SESSION['cart']); ?>
                                    jenis (<?= $total_qty; ?> pcs)
                                </strong>
                            </div>

                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Subtotal</span>
                                <strong>
                                    Rp <?= number_format($total_subtotal, 0, ',', '.'); ?>
                                </strong>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between align-items-center">
                                <strong>Total Bayar</strong>
                                <h5 class="fw-bold text-primary mb-0">
                                    Rp <?= number_format($total_bayar, 0, ',', '.'); ?>
                                </h5>
                            </div>

                        </div>

                        <!-- PEMBAYARAN -->
                        <form method="POST" action="index.php" id="form-transaksi">

                            <input type="hidden" name="csrf_token"
                                value="<?= htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="action" value="checkout">

                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    Nominal Pembayaran (Cash)
                                </label>

                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="nominal_bayar"
                                        id="nominal-bayar"
                                        class="form-control"
                                        placeholder="Masukkan nominal"
                                        min="<?= (int)$total_bayar; ?>"
                                        step="1" required
                                        <?= empty($_SESSION['cart']) ? 'disabled' : ''; ?>>
                                </div>

                                <?php if (!empty($_SESSION['cart'])): ?>

                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                        <button type="button"
                                            class="btn btn-sm btn-outline-secondary quick-payment"
                                            data-amount="<?= (int)$total_bayar; ?>">
                                            Uang Pas
                                        </button>

                                        <button type="button"
                                            class="btn btn-sm btn-outline-secondary quick-payment"
                                            data-amount="<?= (int)($total_bayar + 10000); ?>">
                                            +10.000
                                        </button>

                                        <button type="button"
                                            class="btn btn-sm btn-outline-secondary quick-payment"
                                            data-amount="<?= (int)($total_bayar + 20000); ?>">
                                            +20.000
                                        </button>

                                        <button type="button"
                                            class="btn btn-sm btn-outline-secondary quick-payment"
                                            data-amount="<?= (int)($total_bayar + 50000); ?>">
                                            +50.000
                                        </button>
                                    </div>

                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Kembalian</label>

                                <div class="bg-light rounded p-3 d-flex justify-content-between">
                                    <span class="text-muted">Uang Kembali</span>
                                    <strong id="display-kembalian" class="text-success">
                                        Rp 0
                                    </strong>
                                </div>
                            </div>

                            <button type="submit"
                                id="btn-submit-transaksi"
                                class="btn btn-primary w-100 py-2 fw-bold"
                                <?= empty($_SESSION['cart']) ? 'disabled' : ''; ?>>

                                <i class="bi bi-check-circle me-1"></i>
                                Selesaikan Transaksi

                            </button>

                        </form>

                    </div>
                </div>
            </div>

        </div>

        </div><!-- /.tab-pane tab-kasir -->

        <div class="tab-pane fade <?= $aktif_tab === 'riwayat' ? 'show active' : '' ?>" id="tab-riwayat">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h5 class="fw-bold mb-1">Riwayat Transaksi</h5>
                    <p class="text-muted small mb-0">
                        Total <?= number_format($total_transactions); ?> transaksi tercatat
                        dengan omzet <?= rupiah($total_revenue); ?>.
                    </p>
                </div>
                <a class="btn btn-sm btn-outline-secondary" href="../riwayat/index.php" target="_blank" rel="noopener">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Buka Halaman Penuh
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead><tr><th>No</th><th>No. Invoice</th><th>Tanggal</th><th>Kasir</th><th>Item</th><th>Pembayaran</th><th>Total</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php if (!$transactions): ?>
                        <tr><td colspan="8" class="py-5 text-center text-muted"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Belum ada transaksi.</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $index => $transaction): ?>
                            <?php $transaction_id = (int)$transaction['id']; ?>
                            <tr>
                                <td><?= $offset + $index + 1; ?></td>
                                <td class="fw-semibold"><?= h($transaction['invoice_number']); ?></td>
                                <td><?= h(date('d/m/Y H:i', strtotime($transaction['transaction_date']))); ?></td>
                                <td><?= h($transaction['cashier_name']); ?></td>
                                <td><?= (int)$transaction['item_count']; ?></td>
                                <td><span class="badge text-bg-light border"><?= h(strtoupper($transaction['payment_method'])); ?></span></td>
                                <td class="text-nowrap"><?= rupiah($transaction['total']); ?></td>
                                <td class="text-nowrap">
                                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#detail-<?= $transaction_id; ?>">
                                        <i class="bi bi-eye me-1"></i>Detail
                                    </button>
                                    <a class="btn btn-sm btn-outline-secondary" href="../riwayat/struk.php?id=<?= $transaction_id; ?>" target="_blank" rel="noopener">
                                        <i class="bi bi-receipt me-1"></i>Struk
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php foreach ($transactions as $transaction): ?>
                <?php $transaction_id = (int)$transaction['id']; ?>
                <div class="modal fade" id="detail-<?= $transaction_id; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <div><h5 class="modal-title mb-1">Detail Transaksi</h5><small class="text-muted"><?= h($transaction['invoice_number']); ?></small></div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-2 small mb-3">
                                    <div class="col-sm-6"><span class="text-muted">Tanggal:</span> <?= h(date('d/m/Y H:i', strtotime($transaction['transaction_date']))); ?></div>
                                    <div class="col-sm-6"><span class="text-muted">Kasir:</span> <?= h($transaction['cashier_name']); ?></div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-3">
                                        <thead><tr><th>Nama Produk</th><th>Harga</th><th>Qty</th><th>Subtotal</th></tr></thead>
                                        <tbody>
                                        <?php foreach ($details_by_transaction[$transaction_id] ?? [] as $detail): ?>
                                            <tr>
                                                <td><?= h($detail['product_name']); ?></td>
                                                <td><?= rupiah($detail['unit_price']); ?></td>
                                                <td><?= (int)$detail['quantity']; ?></td>
                                                <td><?= rupiah($detail['subtotal']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="ms-auto" style="max-width:260px">
                                    <div class="d-flex justify-content-between"><span>Subtotal</span><span><?= rupiah($transaction['subtotal']); ?></span></div>
                                    <div class="d-flex justify-content-between fw-bold"><span>Total</span><span><?= rupiah($transaction['total']); ?></span></div>
                                    <div class="d-flex justify-content-between"><span>Dibayar</span><span><?= rupiah($transaction['amount_paid']); ?></span></div>
                                    <div class="d-flex justify-content-between"><span>Kembalian</span><span><?= rupiah($transaction['change_amount']); ?></span></div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <a class="btn btn-primary" href="../riwayat/struk.php?id=<?= $transaction_id; ?>" target="_blank" rel="noopener"><i class="bi bi-receipt me-1"></i>Buka Struk</a>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ($total_pages > 1): ?>
                <nav aria-label="Navigasi riwayat transaksi">
                    <ul class="pagination justify-content-end">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="?tab=riwayat&page=<?= max(1, $page - 1); ?>">Sebelumnya</a></li>
                        <li class="page-item disabled"><span class="page-link">Halaman <?= $page; ?> dari <?= $total_pages; ?></span></li>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : ''; ?>"><a class="page-link" href="?tab=riwayat&page=<?= min($total_pages, $page + 1); ?>">Berikutnya</a></li>
                    </ul>
                </nav>
            <?php endif; ?>

        </div><!-- /.tab-pane tab-riwayat -->

        <div class="tab-pane fade <?= $aktif_tab === 'laporan' ? 'show active' : '' ?>" id="tab-laporan">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h5 class="fw-bold mb-0">Laporan Penjualan</h5>
                <a class="btn btn-sm btn-outline-secondary" href="../laporan/index.php" target="_blank" rel="noopener">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Buka Halaman Penuh
                </a>
            </div>

            <form method="GET" action="index.php" class="row g-3 align-items-end mb-4">
                <input type="hidden" name="tab" value="laporan">
                <div class="col-sm-5 col-lg-3">
                    <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                    <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" value="<?= h($start_input); ?>" required>
                </div>
                <div class="col-sm-5 col-lg-3">
                    <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                    <input type="date" class="form-control" id="tanggal_akhir" name="tanggal_akhir" value="<?= h($end_input); ?>" required>
                </div>
                <div class="col-sm-2">
                    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button>
                </div>
                <div class="col-sm-2 col-lg-auto">
                    <a class="btn btn-outline-secondary w-100" href="index.php?tab=laporan"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</a>
                </div>
                <div class="col-lg-auto ms-lg-auto">
                    <?php if ($error_laporan === ''): ?>
                        <a class="btn btn-success" href="../laporan/export.php?tanggal_mulai=<?= rawurlencode($start_input); ?>&amp;tanggal_akhir=<?= rawurlencode($end_input); ?>">
                            <i class="bi bi-file-earmark-excel me-1"></i>Ekspor Excel
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ($error_laporan !== ''): ?>
                <div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle me-2"></i><?= h($error_laporan); ?></div>
            <?php else: ?>
                <p class="text-muted mb-3">Periode <?= h($start_date->format('d/m/Y')); ?> – <?= h($end_date->format('d/m/Y')); ?></p>
                <div class="row g-3 mb-4">
                    <div class="col-md-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small"><i class="bi bi-cash-stack me-1"></i>Total Omzet</div><div class="fs-5 fw-bold text-primary mt-1"><?= rupiah($laporan_summary['revenue']); ?></div></div></div></div>
                    <div class="col-md-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small"><i class="bi bi-receipt me-1"></i>Jumlah Transaksi</div><div class="fs-5 fw-bold text-primary mt-1"><?= number_format((int)$laporan_summary['transaction_count']); ?></div></div></div></div>
                    <div class="col-md-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small"><i class="bi bi-box-seam me-1"></i>Barang Terjual</div><div class="fs-5 fw-bold text-primary mt-1"><?= number_format((int)$laporan_summary['items_sold']); ?></div></div></div></div>
                    <div class="col-md-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small"><i class="bi bi-calculator me-1"></i>Rata-rata Transaksi</div><div class="fs-5 fw-bold text-primary mt-1"><?= rupiah($laporan_summary['average_transaction']); ?></div></div></div></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead><tr><th>No</th><th>No. Invoice</th><th>Tanggal</th><th>Kasir</th><th>Barang</th><th>Pembayaran</th><th>Total Penjualan</th></tr></thead>
                        <tbody>
                        <?php if (!$laporan_transactions): ?>
                            <tr><td colspan="7" class="py-5 text-center text-muted"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Tidak ada transaksi pada periode ini.</td></tr>
                        <?php else: ?>
                            <?php foreach ($laporan_transactions as $index => $transaction): ?>
                                <tr>
                                    <td><?= $index + 1; ?></td>
                                    <td><?= h($transaction['invoice_number']); ?></td>
                                    <td><?= h(date('d/m/Y H:i', strtotime($transaction['transaction_date']))); ?></td>
                                    <td><?= h($transaction['cashier_name']); ?></td>
                                    <td><?= (int)$transaction['items_sold']; ?></td>
                                    <td><?= h(strtoupper($transaction['payment_method'])); ?></td>
                                    <td class="text-nowrap"><?= rupiah($transaction['total']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div><!-- /.tab-pane tab-laporan -->

        <div class="tab-pane fade <?= $aktif_tab === 'users' ? 'show active' : '' ?>" id="tab-users">

            <?php if (!$is_admin): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-shield-lock me-2"></i>Halaman Kelola User hanya dapat diakses oleh Administrator.
                </div>
            <?php else: ?>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h5 class="fw-bold mb-1">Kelola User</h5>
                        <p class="text-muted small mb-0">Informasi akun pengguna FEBMart.</p>
                    </div>
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <span class="badge text-bg-light border py-2 px-3">
                            <i class="bi bi-person-check me-1"></i>Total: <?= count($users_list); ?> Pengguna
                        </span>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-tambah-user">
                            <i class="bi bi-person-plus me-1"></i>Tambah Pengguna
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead><tr><th>No</th><th>Nama</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Tanggal Dibuat</th><th>Aksi</th></tr></thead>
                        <tbody>
                        <?php if (!$users_list): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Belum ada data pengguna.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users_list as $i => $user): ?>
                                <tr>
                                    <td><?= $i + 1; ?></td>
                                    <td><?= h($user['name']); ?></td>
                                    <td><?= h($user['username']); ?></td>
                                    <td><?= h($user['email'] ?? '-'); ?></td>
                                    <td><span class="badge text-bg-light border text-primary"><?= h($user['role']); ?></span></td>
                                    <td>
                                        <?php if ((int)$user['is_active']): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= h($user['created_at']); ?></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-warning btn-sm"
                                                data-bs-toggle="modal" data-bs-target="#modal-edit-user"
                                                data-user-id="<?= (int)$user['id']; ?>"
                                                data-user-name="<?= h($user['name']); ?>"
                                                data-user-username="<?= h($user['username']); ?>"
                                                data-user-email="<?= h($user['email'] ?? ''); ?>"
                                                data-user-role="<?= h($user['role']); ?>">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </button>
                                            <form method="POST" action="index.php" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus pengguna ini?');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">
                                                <input type="hidden" name="action" value="hapus_user">
                                                <input type="hidden" name="id" value="<?= (int)$user['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i> Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Modal Tambah User -->
                <div class="modal fade" id="modal-tambah-user" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form method="POST" action="index.php">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="bi bi-person-plus me-1"></i>Tambah Pengguna</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="tambah_user">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" placeholder="Nama pengguna" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Username <span class="text-danger">*</span></label>
                                        <input type="text" name="username" class="form-control" placeholder="Username unik" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" placeholder="email@contoh.com">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password <span class="text-danger">*</span></label>
                                        <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Role <span class="text-danger">*</span></label>
                                        <select name="role" class="form-select" required>
                                            <option value="kasir">Kasir</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal Edit User -->
                <div class="modal fade" id="modal-edit-user" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form method="POST" action="index.php">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="bi bi-pencil-square me-1"></i>Edit Pengguna</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="edit_user">
                                    <input type="hidden" name="id" id="edit-user-id">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                        <input type="text" name="name" id="edit-user-name" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Username <span class="text-danger">*</span></label>
                                        <input type="text" name="username" id="edit-user-username" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" id="edit-user-email" class="form-control">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Role <span class="text-danger">*</span></label>
                                        <select name="role" id="edit-user-role" class="form-select" required>
                                            <option value="kasir">Kasir</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Simpan Perubahan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

        </div><!-- /.tab-pane tab-users -->

        </div><!-- /.tab-content -->

    </main>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

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
        displayKembalian.textContent =
            'Rp ' + Math.floor(kembalian).toLocaleString('id-ID');

        displayKembalian.className = 'fw-bold text-success';

        if (btnSubmit) btnSubmit.disabled = false;
    } else {
        displayKembalian.textContent =
            'Kurang Rp ' + Math.floor(Math.abs(kembalian)).toLocaleString('id-ID');

        displayKembalian.className = 'fw-bold text-danger';

        if (btnSubmit) btnSubmit.disabled = true;
    }
}

if (inputBayar) {
    inputBayar.addEventListener('input', hitungKembalian);
    hitungKembalian();
}

document.querySelectorAll('.quick-payment').forEach(button => {
    button.addEventListener('click', () => {
        inputBayar.value = button.dataset.amount;
        hitungKembalian();
    });
});

const modalEditUser = document.getElementById('modal-edit-user');
if (modalEditUser) {
    modalEditUser.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        if (!btn) return;
        document.getElementById('edit-user-id').value = btn.dataset.userId;
        document.getElementById('edit-user-name').value = btn.dataset.userName;
        document.getElementById('edit-user-username').value = btn.dataset.userUsername;
        document.getElementById('edit-user-email').value = btn.dataset.userEmail || '';
        document.getElementById('edit-user-role').value = btn.dataset.userRole;
    });
}
</script>

</body>
</html>
