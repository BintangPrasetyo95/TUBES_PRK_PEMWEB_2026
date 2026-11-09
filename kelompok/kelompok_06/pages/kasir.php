
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// ID kasir sementara, nanti sesuaikan dengan session login.
$user_id = $_SESSION['user_id'] ?? 1;

// Redirect menggunakan JavaScript karena layout sudah mengeluarkan HTML.
function redirectKasir($url) {
    echo '<script>window.location.href = ' . json_encode($url) . ';</script>';
    exit;
}

// Keranjang belanja.
if (!isset($_SESSION['keranjang_kasir'])) {
    $_SESSION['keranjang_kasir'] = [];
}

$error = '';

// Menghitung total keranjang.
function totalKeranjang($keranjang) {
    $total = 0;

    foreach ($keranjang as $item) {
        $total += $item['harga'] * $item['qty'];
    }

    return $total;
}

// ===============================
// TAMBAH PRODUK KE KERANJANG
// ===============================
if (isset($_POST['tambah'])) {
    $id = (int) $_POST['product_id'];
    $qty = max(1, (int) ($_POST['qty'] ?? 1));

    $stmt = mysqli_prepare($conn,
        "SELECT id, nama_produk, harga, stok FROM products WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $produk = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($produk && $produk['stok'] > 0) {
        $jumlah = ($_SESSION['keranjang_kasir'][$id]['qty'] ?? 0) + $qty;

        if ($jumlah <= $produk['stok']) {
            $_SESSION['keranjang_kasir'][$id] = [
                'id' => $id,
                'nama' => $produk['nama_produk'],
                'harga' => (float) $produk['harga'],
                'qty' => $jumlah
            ];
        } else {
            $error = 'Jumlah produk melebihi stok tersedia.';
        }
    } else {
        $error = 'Produk tidak tersedia atau stok habis.';
    }
}

// ===============================
// UBAH JUMLAH PRODUK
// ===============================
if (isset($_POST['update_qty'])) {
    $id = (int) $_POST['product_id'];
    $qty = max(1, (int) $_POST['qty']);

    if (isset($_SESSION['keranjang_kasir'][$id])) {
        $stmt = mysqli_prepare($conn,
            "SELECT stok FROM products WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);

        $produk = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($produk && $qty <= $produk['stok']) {
            $_SESSION['keranjang_kasir'][$id]['qty'] = $qty;
        } else {
            $error = 'Jumlah melebihi stok tersedia.';
        }
    }
}

// ===============================
// HAPUS PRODUK
// ===============================
if (isset($_POST['hapus'])) {
    $id = (int) $_POST['product_id'];
    unset($_SESSION['keranjang_kasir'][$id]);
}

// ===============================
// KOSONGKAN KERANJANG
// ===============================
if (isset($_POST['kosongkan'])) {
    $_SESSION['keranjang_kasir'] = [];
}

// ===============================
// CHECKOUT TRANSAKSI
// ===============================
if (isset($_POST['checkout'])) {
    $keranjang = $_SESSION['keranjang_kasir'];

    if (empty($keranjang)) {
        $error = 'Keranjang masih kosong.';
    } else {
        mysqli_begin_transaction($conn);

        try {
            $total = totalKeranjang($keranjang);
            $invoice = 'INV-' . date('YmdHis');

            $pelanggan = trim($_POST['nama_pelanggan'] ?? 'Umum');
            $pelanggan = $pelanggan ?: 'Umum';

            $meja = trim($_POST['nomor_meja'] ?? '-');
            $meja = $meja ?: '-';

            $stmt = mysqli_prepare($conn,
                "INSERT INTO orders
                (no_invoice, user_id, nama_pelanggan, nomor_meja,
                 total_tagihan, tipe_pembayaran, status_order)
                VALUES (?, ?, ?, ?, ?, 'full', 'pending')"
            );

            mysqli_stmt_bind_param(
                $stmt, "sissd",
                $invoice, $user_id, $pelanggan, $meja, $total
            );

            mysqli_stmt_execute($stmt);

            $order_id = mysqli_insert_id($conn);

            foreach ($keranjang as $item) {
                $product_id = (int) $item['id'];
                $qty = (int) $item['qty'];
                $harga = (float) $item['harga'];
                $subtotal = $harga * $qty;

                // Periksa stok terbaru.
                $cek = mysqli_prepare($conn,
                    "SELECT stok FROM products WHERE id = ? FOR UPDATE"
                );

                mysqli_stmt_bind_param($cek, "i", $product_id);
                mysqli_stmt_execute($cek);

                $stok = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));

                if (!$stok || $stok['stok'] < $qty) {
                    throw new Exception(
                        'Stok produk ' . $item['nama'] . ' tidak mencukupi.'
                    );
                }

                // Simpan detail transaksi.
                $detail = mysqli_prepare($conn,
                    "INSERT INTO order_items
                    (order_id, product_id, qty, harga_satuan, subtotal)
                    VALUES (?, ?, ?, ?, ?)"
                );

                mysqli_stmt_bind_param(
                    $detail, "iiidd",
                    $order_id, $product_id, $qty, $harga, $subtotal
                );

                mysqli_stmt_execute($detail);

                // Kurangi stok produk.
                $update = mysqli_prepare($conn,
                    "UPDATE products SET stok = stok - ? WHERE id = ?"
                );

                mysqli_stmt_bind_param($update, "ii", $qty, $product_id);
                mysqli_stmt_execute($update);
            }

            mysqli_commit($conn);

            $_SESSION['keranjang_kasir'] = [];

            redirectKasir(
                'index.php?page=kasir&order=' . $order_id
            );

        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $error = 'Checkout gagal: ' . $e->getMessage();
        }
    }
}

// ===============================
// PEMBAYARAN SPLIT BILL
// ===============================
if (isset($_POST['bayar_split'])) {
    $order_id = (int) $_POST['order_id'];
    $item_ids = array_map('intval', $_POST['item_ids'] ?? []);

    $metode = $_POST['metode_bayar'] ?? 'tunai';
    $uang = (float) ($_POST['uang_diterima'] ?? 0);

    if (!in_array($metode, ['tunai', 'qris', 'debit'], true)) {
        $metode = 'tunai';
    }

    if (empty($item_ids)) {
        $error = 'Pilih minimal satu item yang ingin dibayar.';
    } else {
        mysqli_begin_transaction($conn);

        try {
            $id_list = implode(',', array_unique($item_ids));

            $result = mysqli_query($conn,
                "SELECT id, subtotal FROM order_items
                 WHERE order_id = $order_id
                 AND status_bayar_item = 'belum'
                 AND id IN ($id_list)
                 FOR UPDATE"
            );

            $nominal = 0;
            $valid_ids = [];

            while ($item = mysqli_fetch_assoc($result)) {
                $nominal += (float) $item['subtotal'];
                $valid_ids[] = (int) $item['id'];
            }

            if (empty($valid_ids)) {
                throw new Exception(
                    'Item tidak tersedia atau sudah lunas.'
                );
            }

            if ($uang < $nominal) {
                throw new Exception(
                    'Uang yang diterima kurang dari total pembayaran.'
                );
            }

            $kembalian = $uang - $nominal;

            // Menentukan urutan pembayaran.
            $cek_split = mysqli_query($conn,
                "SELECT COALESCE(MAX(urutan_split), 0) + 1 AS urutan
                 FROM payments WHERE order_id = $order_id"
            );

            $urutan = (int) mysqli_fetch_assoc($cek_split)['urutan'];

            // Simpan pembayaran.
            $payment = mysqli_prepare($conn,
                "INSERT INTO payments
                (order_id, urutan_split, metode_bayar,
                 nominal_bayar, uang_diterima, kembalian)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $payment, "iisddd",
                $order_id, $urutan, $metode,
                $nominal, $uang, $kembalian
            );

            mysqli_stmt_execute($payment);

            // Tandai item yang sudah dibayar.
            foreach ($valid_ids as $item_id) {
                $update = mysqli_prepare($conn,
                    "UPDATE order_items
                     SET status_bayar_item = 'lunas'
                     WHERE id = ? AND order_id = ?"
                );

                mysqli_stmt_bind_param(
                    $update, "ii", $item_id, $order_id
                );

                mysqli_stmt_execute($update);
            }

            // Periksa apakah masih ada tagihan.
            $cek_sisa = mysqli_query($conn,
                "SELECT COUNT(*) AS belum
                 FROM order_items
                 WHERE order_id = $order_id
                 AND status_bayar_item = 'belum'"
            );

            $sisa = (int) mysqli_fetch_assoc($cek_sisa)['belum'];

            $status = ($sisa === 0) ? 'lunas' : 'sebagian';

            $update_order = mysqli_prepare($conn,
                "UPDATE orders
                 SET status_order = ?, tipe_pembayaran = 'split'
                 WHERE id = ?"
            );

            mysqli_stmt_bind_param(
                $update_order, "si", $status, $order_id
            );

            mysqli_stmt_execute($update_order);

            mysqli_commit($conn);

            redirectKasir(
                'index.php?page=kasir&order=' . $order_id . '&sukses=bayar'
            );

        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    }
}

// ===============================
// AMBIL DATA PRODUK
// ===============================
$produk_result = mysqli_query($conn,
    "SELECT * FROM products WHERE stok > 0 ORDER BY nama_produk ASC"
);

// ===============================
// AMBIL DETAIL TRANSAKSI
// ===============================
$order_id = (int) ($_GET['order'] ?? 0);

$order = null;
$items = [];
$total_lunas = 0;
$total_belum = 0;

if ($order_id > 0) {
    $stmt = mysqli_prepare($conn,
        "SELECT * FROM orders WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);

    $order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($order) {
        $stmt = mysqli_prepare($conn,
            "SELECT oi.*, p.nama_produk
             FROM order_items oi
             JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ?
             ORDER BY oi.id ASC"
        );

        mysqli_stmt_bind_param($stmt, "i", $order_id);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        while ($item = mysqli_fetch_assoc($result)) {
            $items[] = $item;

            if ($item['status_bayar_item'] === 'lunas') {
                $total_lunas += (float) $item['subtotal'];
            } else {
                $total_belum += (float) $item['subtotal'];
            }
        }
    }
}
?>

<div class="p-6 space-y-6">

    <div>
        <h2 class="text-xl font-bold text-slate-800">Terminal Kasir</h2>
        <p class="text-sm text-slate-500">
            Kelola transaksi penjualan dan pembayaran split bill.
        </p>
    </div>

    <?php if ($error): ?>
        <div class="p-3 rounded-lg bg-red-50 text-red-700 text-sm">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['sukses'])): ?>
        <div class="p-3 rounded-lg bg-green-50 text-green-700 text-sm">
            Pembayaran berhasil dicatat.
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- DAFTAR PRODUK -->
        <section class="lg:col-span-2 bg-white border rounded-xl p-5">
            <h3 class="font-semibold mb-4">Daftar Produk</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">

                <?php while ($produk = mysqli_fetch_assoc($produk_result)): ?>

                    <div class="border rounded-lg p-4">
                        <h4 class="font-semibold">
                            <?= htmlspecialchars($produk['nama_produk']) ?>
                        </h4>

                        <p class="text-sm text-slate-500">
                            <?= htmlspecialchars($produk['kode_produk']) ?>
                        </p>

                        <p class="font-bold text-blue-600 mt-2">
                            <?= formatRupiah($produk['harga']) ?>
                        </p>

                        <p class="text-xs text-slate-400 mb-3">
                            Stok: <?= (int) $produk['stok'] ?>
                        </p>

                        <form method="POST" class="flex gap-2">
                            <input type="hidden" name="product_id"
                                   value="<?= $produk['id'] ?>">

                            <input type="number" name="qty" value="1" min="1"
                                   max="<?= (int) $produk['stok'] ?>"
                                   class="w-16 border rounded-lg px-2 py-1">

                            <button type="submit" name="tambah"
                                    class="flex-1 bg-slate-900 text-white rounded-lg px-3 py-2 text-sm">
                                + Keranjang
                            </button>
                        </form>
                    </div>

                <?php endwhile; ?>

            </div>
        </section>

        <!-- KERANJANG -->
        <section class="bg-white border rounded-xl p-5">
            <h3 class="font-semibold mb-4">Keranjang Belanja</h3>

            <?php if (empty($_SESSION['keranjang_kasir'])): ?>

                <p class="text-sm text-slate-400">
                    Belum ada produk di keranjang.
                </p>

            <?php else: ?>

                <div class="space-y-4">

                    <?php foreach ($_SESSION['keranjang_kasir'] as $item): ?>

                        <div class="border-b pb-3">

                            <div class="font-medium text-sm">
                                <?= htmlspecialchars($item['nama']) ?>
                            </div>

                            <div class="text-xs text-slate-500">
                                <?= formatRupiah($item['harga']) ?>
                                × <?= $item['qty'] ?>
                            </div>

                            <div class="flex justify-between items-center mt-2">

                                <strong class="text-sm">
                                    <?= formatRupiah($item['harga'] * $item['qty']) ?>
                                </strong>

                                <div class="flex gap-2">

                                    <form method="POST" class="flex gap-1">
                                        <input type="hidden" name="product_id"
                                               value="<?= $item['id'] ?>">

                                        <input type="number" name="qty"
                                               value="<?= $item['qty'] ?>"
                                               min="1"
                                               class="w-14 border rounded px-1 text-sm">

                                        <button name="update_qty"
                                                class="text-blue-600 text-xs">
                                            Ubah
                                        </button>
                                    </form>

                                    <form method="POST">
                                        <input type="hidden" name="product_id"
                                               value="<?= $item['id'] ?>">

                                        <button name="hapus"
                                                class="text-red-500 text-xs">
                                            Hapus
                                        </button>
                                    </form>

                                </div>
                            </div>
                        </div>

                    <?php endforeach; ?>

                </div>

                <div class="border-t mt-4 pt-4">

                    <div class="flex justify-between font-bold text-lg">
                        <span>Total</span>
                        <span>
                            <?= formatRupiah(totalKeranjang($_SESSION['keranjang_kasir'])) ?>
                        </span>
                    </div>

                    <form method="POST" class="space-y-3 mt-4">

                        <input name="nama_pelanggan"
                               placeholder="Nama pelanggan (opsional)"
                               class="w-full border rounded-lg p-2 text-sm">

                        <input name="nomor_meja"
                               placeholder="Nomor meja (opsional)"
                               class="w-full border rounded-lg p-2 text-sm">

                        <button name="checkout"
                                class="w-full bg-blue-600 text-white rounded-lg p-3 font-semibold">
                            Checkout Transaksi
                        </button>

                    </form>

                    <form method="POST" class="mt-2">
                        <button name="kosongkan"
                                class="w-full border rounded-lg p-2 text-sm text-red-600">
                            Kosongkan Keranjang
                        </button>
                    </form>

                </div>

            <?php endif; ?>

        </section>
    </div>

    <!-- DETAIL TRANSAKSI DAN SPLIT BILL -->
    <?php if ($order): ?>

        <section class="bg-white border rounded-xl p-5">

            <div class="flex flex-wrap justify-between gap-3 border-b pb-4 mb-4">

                <div>
                    <h3 class="font-bold text-lg">Detail Transaksi</h3>

                    <p class="text-sm text-slate-500">
                        <?= htmlspecialchars($order['no_invoice']) ?>
                        | Meja <?= htmlspecialchars($order['nomor_meja']) ?>
                    </p>

                    <p class="text-sm text-slate-500">
                        Pelanggan:
                        <?= htmlspecialchars($order['nama_pelanggan']) ?>
                    </p>
                </div>

                <span class="text-sm font-semibold px-3 py-1 rounded-full bg-blue-50 text-blue-700">
                    <?= strtoupper($order['status_order']) ?>
                </span>

            </div>

            <!-- RINGKASAN PEMBAYARAN -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">

                <div class="bg-slate-50 rounded-lg p-4">
                    <p class="text-sm text-slate-500">Total Tagihan</p>
                    <strong>
                        <?= formatRupiah($order['total_tagihan']) ?>
                    </strong>
                </div>

                <div class="bg-green-50 rounded-lg p-4">
                    <p class="text-sm text-slate-500">Sudah Dibayar</p>
                    <strong class="text-green-700">
                        <?= formatRupiah($total_lunas) ?>
                    </strong>
                </div>

                <div class="bg-orange-50 rounded-lg p-4">
                    <p class="text-sm text-slate-500">Sisa Tagihan</p>
                    <strong class="text-orange-700">
                        <?= formatRupiah($total_belum) ?>
                    </strong>
                </div>

            </div>

            <?php if ($total_belum > 0): ?>

                <form method="POST" class="space-y-4">

                    <input type="hidden" name="order_id"
                           value="<?= $order['id'] ?>">

                    <h4 class="font-semibold">
                        Pilih Item yang Ingin Dibayar
                    </h4>

                    <div class="overflow-x-auto">

                        <table class="w-full text-sm">

                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="p-3 text-left">Pilih</th>
                                    <th class="p-3 text-left">Produk</th>
                                    <th class="p-3 text-center">Qty</th>
                                    <th class="p-3 text-right">Subtotal</th>
                                    <th class="p-3 text-center">Status</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($items as $item): ?>

                                    <tr class="border-b">

                                        <td class="p-3">
                                            <?php if ($item['status_bayar_item'] === 'belum'): ?>
                                                <input type="checkbox"
                                                       name="item_ids[]"
                                                       value="<?= $item['id'] ?>">
                                            <?php else: ?>
                                                <span class="text-green-600">Lunas</span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="p-3">
                                            <?= htmlspecialchars($item['nama_produk']) ?>
                                        </td>

                                        <td class="p-3 text-center">
                                            <?= (int) $item['qty'] ?>
                                        </td>

                                        <td class="p-3 text-right">
                                            <?= formatRupiah($item['subtotal']) ?>
                                        </td>

                                        <td class="p-3 text-center">
                                            <?php if ($item['status_bayar_item'] === 'lunas'): ?>
                                                <span class="text-green-600">Lunas</span>
                                            <?php else: ?>
                                                <span class="text-orange-600">Belum dibayar</span>
                                            <?php endif; ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>
                        </table>

                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">

                        <select name="metode_bayar"
                                class="border rounded-lg p-2">
                            <option value="tunai">Tunai</option>
                            <option value="qris">QRIS</option>
                            <option value="debit">Debit</option>
                        </select>

                        <input type="number" name="uang_diterima"
                               min="0" step="1"
                               placeholder="Uang diterima"
                               required
                               class="border rounded-lg p-2">

                        <button name="bayar_split"
                                class="bg-green-600 text-white rounded-lg p-2 font-semibold">
                            Proses Pembayaran
                        </button>

                    </div>

                    <p class="text-xs text-slate-500">
                        Pilih item yang ingin dibayar. Item yang tidak dipilih
                        akan tetap menjadi sisa tagihan.
                    </p>

                </form>

            <?php else: ?>

                <div class="bg-green-50 text-green-700 p-4 rounded-lg">
                    Transaksi sudah lunas. Terima kasih!
                </div>

            <?php endif; ?>

        </section>

    <?php endif; ?>

</div>
