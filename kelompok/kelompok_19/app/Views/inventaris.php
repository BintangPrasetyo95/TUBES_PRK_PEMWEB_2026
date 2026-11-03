<?php
// app/Views/inventaris.php
// Tugas: CRUD Inventaris (tabel + form modal) & Log Transaksi.
// Diisi oleh: Valerie

$title = "Manajemen Inventaris";
require_once __DIR__ . "/layouts/header.php";
require_once __DIR__ . "/layouts/sidebar.php";
?>
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Data Inventaris</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalInventaris" onclick="resetFormInventaris()">
            <i class="bi bi-plus-circle"></i> Tambah Item
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="tabelInventaris">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>Nama Item</th>
                            <th>Kategori</th>
                            <th>Stok</th>
                            <th>Satuan</th>
                            <th>Harga Satuan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data akan disuntikkan via Fetch API (inventaris.js) -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form Master Inventaris -->
<div class="modal fade" id="modalInventaris" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalInventarisTitle">Tambah Item Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formInventaris">
                <input type="hidden" name="id" id="inventarisId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Item</label>
                        <input type="text" class="form-control" name="nama" id="inventarisNama" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="kategori" id="inventarisKategori" required>
                            <option value="">Pilih...</option>
                            <option value="Bibit">Bibit</option>
                            <option value="Pupuk">Pupuk</option>
                            <option value="Pestisida">Pestisida</option>
                            <option value="Alat">Alat</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok Awal</label>
                        <input type="number" class="form-control" name="stok" id="inventarisStok" required min="0" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Satuan</label>
                        <input type="text" class="form-control" name="satuan" id="inventarisSatuan" required placeholder="Contoh: kg, liter, pcs">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga Satuan (Rp)</label>
                        <input type="number" class="form-control" name="harga_satuan" id="inventarisHargaSatuan" required min="0" step="0.01">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Form Transaksi (Masuk/Keluar) -->
<div class="modal fade" id="modalTransaksi" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Catat Transaksi Barang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formTransaksi">
                <input type="hidden" name="inventaris_id" id="transaksiInventarisId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Barang</label>
                        <input type="text" class="form-control" id="transaksiNamaItem" readonly disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jenis Transaksi</label>
                        <select class="form-select" name="jenis" id="transaksiJenis" required>
                            <option value="">Pilih...</option>
                            <option value="Masuk">Barang Masuk (Kulakan / Restock)</option>
                            <option value="Keluar">Barang Keluar (Pemakaian ke Lahan)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Transaksi</label>
                        <input type="date" class="form-control" name="tanggal" id="transaksiTanggal" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jumlah</label>
                        <input type="number" class="form-control" name="jumlah" id="transaksiJumlah" required min="0.01" step="0.01">
                    </div>
                    <!-- Dropdown Siklus: Disembunyikan secara default, hanya muncul jika jenis = Keluar -->
                    <div class="mb-3" id="grupSiklusTanam" style="display: none;">
                        <label class="form-label">Gunakan untuk Siklus Tanam:</label>
                        <select class="form-select" name="siklus_id" id="transaksiSiklusId">
                            <option value="">-- Memuat data siklus... --</option>
                        </select>
                        <small class="text-muted">Wajib diisi untuk mencatat beban biaya pemakaian lahan.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan Transaksi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/assets/js/inventaris.js"></script>
<?php
require_once __DIR__ . "/layouts/footer.php";
?>