<?php
// app/Views/panen.php
// Tugas: CRUD Panen (tabel + form modal).
// Diisi oleh: Putri.

$title = "Manajemen Panen";
require_once __DIR__ . "/layouts/header.php";
require_once __DIR__ . "/layouts/sidebar.php";
?>
            <div class="container-fluid p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1>Data Panen</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPanen" onclick="resetFormPanen()">
                        Tambah Hasil Panen
                    </button>
                </div>

                <div class="card">
                    <div class="card-body">
                        <table class="table table-striped" id="tabelPanen">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Siklus</th>
                                    <th>Tanggal Panen</th>
                                    <th>Jumlah (kg)</th>
                                    <th>Kualitas</th>
                                    <th>Catatan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- TODO: Isi data via JS fetch ke API -->
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
                                <div class="mb-3">
                                    <label class="form-label">Siklus</label>
                                    <select class="form-select" name="siklus_id" id="panenSiklusId" required>
                                        <option value="">Pilih Siklus...</option>
                                        <!-- TODO: Isi option via JS fetch siklus -->
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Panen</label>
                                    <input type="date" class="form-control" name="tanggal_panen" id="panenTanggalPanen" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Jumlah (kg)</label>
                                    <input type="number" class="form-control" name="jumlah_kg" id="panenJumlahKg" required min="0" step="0.01">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Kualitas</label>
                                    <select class="form-select" name="kualitas" id="panenKualitas" required>
                                        <option value="A">Grade A (Premium)</option>
                                        <option value="B">Grade B (Standar)</option>
                                        <option value="C">Grade C (Kurang)</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Catatan</label>
                                    <textarea class="form-control" name="catatan" id="panenCatatan" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <script src="/assets/js/panen.js"></script>
<?php
require_once __DIR__ . "/layouts/footer.php";
?>