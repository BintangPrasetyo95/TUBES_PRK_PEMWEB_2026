<!-- GRID 2 KOLOM: TABEL DATA MASTER & SIMULASI KASIR SPLIT BILL -->
 <?php
// Ambil data detail produk beserta kategorinya
$q_produk = mysqli_query($conn, "SELECT p.*, c.nama_kategori FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id ASC");
?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- 2. CONTOH TABEL MINIMALIS (Untuk Anggota 3 - Data Master) -->
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-slate-900">Daftar Menu / Produk (Live Database)</h3>
                <p class="text-xs text-slate-400 mt-0.5">Template tabel standar untuk modul CRUD Anggota 3</p>
            </div>
            <button class="px-3 py-1.5 bg-slate-900 text-white rounded-lg text-xs font-medium hover:bg-slate-800 transition no-print">
                + Tambah Produk
            </button>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/50 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                        <th class="py-3 px-5">Kode</th>
                        <th class="py-3 px-5">Nama Produk</th>
                        <th class="py-3 px-5">Kategori</th>
                        <th class="py-3 px-5">Harga</th>
                        <th class="py-3 px-5 text-right">Stok</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php while($row = mysqli_fetch_assoc($q_produk)): ?>
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3.5 px-5 font-mono text-xs text-slate-500"><?= $row['kode_produk'] ?></td>
                        <td class="py-3.5 px-5 font-medium text-slate-900"><?= $row['nama_produk'] ?></td>
                        <td class="py-3.5 px-5">
                            <span class="px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-600 text-xs font-medium">
                                <?= $row['nama_kategori'] ?>
                            </span>
                        </td>
                        <td class="py-3.5 px-5 text-slate-700 font-medium"><?= formatRupiah($row['harga']) ?></td>
                        <td class="py-3.5 px-5 text-right font-medium text-slate-900"><?= $row['stok'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>