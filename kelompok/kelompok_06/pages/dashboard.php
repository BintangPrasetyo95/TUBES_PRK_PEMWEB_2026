<!-- 1. CONTOH KOMPONEN SUMMARY CARDS (Untuk Anggota 5 - Dashboard) -->
 <?php
// Ambil jumlah produk dari database
$q_produk = mysqli_query($conn, "SELECT id FROM products");
$total_produk = mysqli_num_rows($q_produk);
?>
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-sm">
        <div class="flex items-center justify-between text-slate-400 mb-3">
            <span class="text-xs font-medium text-slate-500">Total Omset Hari Ini</span>
            <i data-lucide="wallet" class="w-4 h-4"></i>
        </div>
        <p class="text-2xl font-bold text-slate-900 tracking-tight">Rp 1.450.000</p>
        <p class="text-[11px] text-emerald-600 font-medium mt-1">+12.5% dari kemarin</p>
    </div>

    <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-sm">
        <div class="flex items-center justify-between text-slate-400 mb-3">
            <span class="text-xs font-medium text-slate-500">Total Transaksi</span>
            <i data-lucide="receipt" class="w-4 h-4"></i>
        </div>
        <p class="text-2xl font-bold text-slate-900 tracking-tight">38 Pesanan</p>
        <p class="text-[11px] text-slate-400 mt-1">8 menggunakan Split Bill</p>
    </div>

    <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-sm">
        <div class="flex items-center justify-between text-slate-400 mb-3">
            <span class="text-xs font-medium text-slate-500">Produk Aktif</span>
            <i data-lucide="box" class="w-4 h-4"></i>
        </div>
        <p class="text-2xl font-bold text-slate-900 tracking-tight"><?= $total_produk ?> Item</p>
        <p class="text-[11px] text-slate-400 mt-1">Terhubung dari MySQL</p>
    </div>

    <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-sm">
        <div class="flex items-center justify-between text-slate-400 mb-3">
            <span class="text-xs font-medium text-slate-500">Rata-rata Transaksi</span>
            <i data-lucide="trending-up" class="w-4 h-4"></i>
        </div>
        <p class="text-2xl font-bold text-slate-900 tracking-tight">Rp 38.150</p>
        <p class="text-[11px] text-slate-400 mt-1">Per pelanggan</p>
    </div>
</div>