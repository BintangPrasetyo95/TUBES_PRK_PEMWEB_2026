<!-- 3. CONTOH PANEL TRANSAKSI & SPLIT BILL (Untuk Anggota 4 - Kasir) -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm p-5 flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Preview Bukti Transaksi</h3>
                    <p class="text-xs text-slate-400">INV-20261102-001 • Meja 04</p>
                </div>
                <span class="px-2 py-1 rounded text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                    Mode Split Bill
                </span>
            </div>

            <!-- Daftar Item Keranjang -->
            <div class="py-4 space-y-3 border-b border-slate-100 text-sm">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="font-medium text-slate-800">2x Kopi Susu Gula Aren</p>
                        <p class="text-xs text-emerald-600 font-medium">✓ Dibayar (Tagihan 1)</p>
                    </div>
                    <span class="font-medium text-slate-400 line-through">Rp 44.000</span>
                </div>
                <div class="flex justify-between items-center">
                    <div>
                        <p class="font-medium text-slate-800">1x Nasi Goreng Spesial</p>
                        <p class="text-xs text-slate-400">Belum dibayar (Tagihan 2)</p>
                    </div>
                    <span class="font-semibold text-slate-900">Rp 28.000</span>
                </div>
            </div>

            <!-- Ringkasan Kalkulasi -->
            <div class="py-4 space-y-2 text-sm">
                <div class="flex justify-between text-slate-500 text-xs">
                    <span>Total Pesanan Awal</span>
                    <span>Rp 72.000</span>
                </div>
                <div class="flex justify-between text-slate-500 text-xs">
                    <span>Sudah Dibayar (Split 1)</span>
                    <span>- Rp 44.000</span>
                </div>
                <div class="flex justify-between text-slate-900 font-bold text-base pt-2 border-t border-slate-100">
                    <span>Sisa Tagihan</span>
                    <span>Rp 28.000</span>
                </div>
            </div>
        </div>

        <!-- Tombol Aksi Kasir -->
        <div class="space-y-2 pt-2 no-print">
            <button class="w-full py-2.5 bg-slate-900 text-white rounded-lg text-xs font-semibold hover:bg-slate-800 transition">
                Proses Bayar Sisa (Rp 28.000)
            </button>
            <button class="w-full py-2 bg-white text-slate-700 border border-slate-200 rounded-lg text-xs font-medium hover:bg-slate-50 transition">
                Pisah Tagihan Baru (Split Bill)
            </button>
        </div>
    </div>

</div>