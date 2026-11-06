<?php
// app/Views/lahan.php
// Tugas: CRUD Lahan (tabel + form modal).
// Diisi oleh: Faqih.

$title = "Manajemen Lahan";
require_once __DIR__ . "/layouts/header.php";
require_once __DIR__ . "/layouts/sidebar.php";
?>
            <div class="container-fluid p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1>Data Lahan</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalLahan" onclick="resetFormLahan()">
                        Tambah Lahan
                    </button>
                </div>

                <div class="card">
                    <div class="card-body">
                        <table class="table table-striped" id="tabelLahan">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Lahan</th>
                                    <th>Lokasi</th>
                                    <th>Luas (m²)</th>
                                    <th>Jenis Tanah</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- TODO: Isi data via JS fetch ke API -->
                                <tr id="placeholderLahan"><td colspan="6" class="text-center">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Form Lahan -->
            <div class="modal fade" id="modalLahan" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalLahanTitle">Tambah Lahan</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="formLahan">
                            <input type="hidden" name="id" id="lahanId">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Nama Lahan</label>
                                    <input type="text" class="form-control" name="nama" id="lahanNama" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Lokasi</label>
                                    <input type="text" class="form-control" name="lokasi" id="lahanLokasi" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Luas (m²)</label>
                                    <input type="number" class="form-control" name="luas" id="lahanLuas" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Jenis Tanah</label>
                                    <select class="form-select" name="jenis_tanah" id="lahanJenisTanah" required>
                                        <option value="">Pilih...</option>
                                        <option value="Liat">Liat</option>
                                        <option value="Pasir">Pasir</option>
                                        <option value="Humus">Humus</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
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

            <script src="/assets/js/lahan.js"></script>
<?php
require_once __DIR__ . "/layouts/footer.php";
?>