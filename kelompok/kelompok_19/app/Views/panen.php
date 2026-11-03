<?php
// app/Views/panen.php
// Tugas: CRUD Panen (tabel + form modal).
// Diisi oleh: Putri.

$title = "Manajemen Panen";
require_once __DIR__ . "/layouts/header.php";
require_once __DIR__ . "/layouts/sidebar.php";
?>
            <!-- Aturan cetak: sembunyikan tombol, filter, dan modal saat window.print() -->
            <style media="print">
                .no-print, .modal, nav, aside, .sidebar, #sidebar-wrapper { display: none !important; }
            </style>

            <div class="container-fluid p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1>Data Panen</h1>
                    <div class="no-print">
                        <button type="button" class="btn btn-outline-secondary" id="btnCetak">Cetak PDF</button>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPanen" onclick="resetFormPanen()">
                            Tambah Hasil Panen
                        </button>
                    </div>
                </div>

                <!-- Filter tanggal interaktif (via Fetch) -->
                <div class="card mb-3 no-print">
                    <div class="card-body">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label" for="filterDari">Dari Tanggal</label>
                                <input type="date" class="form-control" id="filterDari">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="filterSampai">Sampai Tanggal</label>
                                <input type="date" class="form-control" id="filterSampai">
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-success" id="btnTerapkan">Terapkan</button>
                                <button type="button" class="btn btn-secondary" id="btnReset">Reset</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rekapitulasi (agregasi SUM/COUNT) -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="card bg-light border-success">
                            <div class="card-body">
                                <h6 class="card-title text-success mb-1">Total Hasil Panen</h6>
                                <h3 class="fw-bold mb-0" id="totalPanenKg">0 kg</h3>
                                <small class="text-muted" id="totalPanenTon">0 ton</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light border-success">
                            <div class="card-body">
                                <h6 class="card-title text-success mb-1">Jumlah Catatan Panen</h6>
                                <h3 class="fw-bold mb-0" id="jumlahCatatan">0</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <h5>Rekap per Tanaman</h5>
                        <table class="table table-sm table-bordered" id="tabelRekap">
                            <thead>
                                <tr>
                                    <th>Tanaman</th>
                                    <th>Jumlah Catatan</th>
                                    <th>Total (kg)</th>
                                </tr>
                            </thead>
                            <tbody id="tabelRekapBody">
                                <tr>
                                    <td colspan="3" class="text-center">Memuat data...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5>Riwayat Panen</h5>
                        <table class="table table-striped" id="tabelPanen">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Siklus</th>
                                    <th>Tanggal Panen</th>
                                    <th>Jumlah (kg)</th>
                                    <th>Kualitas</th>
                                    <th>Catatan</th>
                                    <th class="no-print">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tabelPanenBody">
                                <tr>
                                    <td colspan="7" class="text-center">Memuat data...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Form Panen -->
            <div class="modal fade" id="modalPanen" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalPanenTitle">Tambah Hasil Panen</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="formPanen">
                            <input type="hidden" name="id" id="panenId">
                            <div class="modal-body">
                                <div class="alert alert-danger d-none" id="panenError" role="alert"></div>
                                <div class="mb-3">
                                    <label class="form-label">Siklus</label>
                                    <select class="form-select" name="siklus_id" id="panenSiklusId" required>
                                        <option value="">Pilih Siklus...</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Panen</label>
                                    <input type="date" class="form-control" name="tanggal_panen" id="panenTanggalPanen" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Jumlah (kg)</label>
                                    <input type="number" class="form-control" name="jumlah_kg" id="panenJumlahKg" required min="0.01" step="0.01">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Kualitas</label>
                                    <select class="form-select" name="kualitas" id="panenKualitas" required>
                                        <option value="A">Grade A (Premium)</option>
                                        <option value="B">Grade B (Standar)</option>
                                        <option value="C">Grade C (Kurang)</option>
                                        <option value="Reject">Reject</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Catatan</label>
                                    <textarea class="form-control" name="catatan" id="panenCatatan" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary" id="btnSimpan">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <script src="/assets/js/panen.js"></script>
<?php
require_once __DIR__ . "/layouts/footer.php";
?>