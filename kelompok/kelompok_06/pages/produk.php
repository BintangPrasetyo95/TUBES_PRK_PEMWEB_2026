
<?php
// Mengambil jumlah produk
$q_produk = mysqli_query($conn, "SELECT COUNT(*) AS total FROM products");
$total_produk = mysqli_fetch_assoc($q_produk)['total'];

// Mengambil jumlah kategori
$q_kategori = mysqli_query($conn, "SELECT COUNT(*) AS total FROM categories");
$total_kategori = mysqli_fetch_assoc($q_kategori)['total'];

// Mengambil seluruh produk
$q_data = mysqli_query($conn, "
    SELECT p.*, c.nama_kategori
    FROM products p
    JOIN categories c ON p.category_id = c.id
    ORDER BY p.id DESC
");
?>

<div class="space-y-6">

    <!-- Judul halaman -->
    <div>
        <h2 class="text-xl font-bold text-slate-900">
            Manajemen Produk
        </h2>
        <p class="text-sm text-slate-500 mt-1">
            Kelola produk dan stok menu FlashPOS.
        </p>
    </div>

    <!-- Statistik -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        <div class="bg-white p-5 rounded-xl border border-slate-200">
            <p class="text-sm text-slate-500">Total Produk</p>
            <h3 class="text-2xl font-bold mt-2">
                <?= $total_produk ?>
            </h3>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200">
            <p class="text-sm text-slate-500">Total Kategori</p>
            <h3 class="text-2xl font-bold mt-2">
                <?= $total_kategori ?>
            </h3>
        </div>

    </div>

    <!-- Tabel produk -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">

        <div class="p-5 border-b border-slate-100 flex justify-between items-center">
            <div>
                <h3 class="font-semibold text-slate-900">
                    Daftar Produk
                </h3>
                <p class="text-xs text-slate-400 mt-1">
                    Katalog menu FlashPOS
                </p>
            </div>

            <button type="button"
                class="px-3 py-2 bg-slate-900 text-white rounded-lg text-xs font-medium">
                + Tambah Produk
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">

                <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                    <tr>
                        <th class="px-5 py-3">Kode</th>
                        <th class="px-5 py-3">Nama Produk</th>
                        <th class="px-5 py-3">Kategori</th>
                        <th class="px-5 py-3">Harga</th>
                        <th class="px-5 py-3 text-right">Stok</th>
                        <th class="px-5 py-3 text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">

                    <?php while ($row = mysqli_fetch_assoc($q_data)): ?>

                    <tr class="hover:bg-slate-50">

                        <td class="px-5 py-4">
                            <?= htmlspecialchars($row['kode_produk']) ?>
                        </td>

                        <td class="px-5 py-4 font-medium">
                            <?= htmlspecialchars($row['nama_produk']) ?>
                        </td>

                        <td class="px-5 py-4">
                            <?= htmlspecialchars($row['nama_kategori']) ?>
                        </td>

                        <td class="px-5 py-4">
                            <?= formatRupiah($row['harga']) ?>
                        </td>

                        <td class="px-5 py-4 text-right">
                            <?= (int) $row['stok'] ?>
                        </td>

                        <td class="px-5 py-4 text-center">
                            <span class="text-xs text-slate-400">
                                Edit | Hapus
                            </span>
                        </td>

                    </tr>

                    <?php endwhile; ?>

                </tbody>
            </table>
        </div>
    </div>

</div>
