
<?php
// app/Views/siklus.php
// Tugas: CRUD Siklus (tabel + form modal).
// Diisi oleh: anggota yang mengerjakan fitur Siklus.

$title = "Manajemen Siklus Tanam";
require_once __DIR__ . "/layouts/header.php";
require_once __DIR__ . "/layouts/sidebar.php";
?>

            <div class="container-fluid p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1>Data Siklus Tanam</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalSiklus" onclick="resetFormSiklus()">
                        Tambah Siklus
                    </button>
                </div>

                <div id="alertSiklus"></div>

                <div class="card">
                    <div class="card-body">
                        <table class="table table-striped" id="tabelSiklus">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Tanaman</th>
                                    <th>Lahan</th>
                                    <th>Tanggal Tanam</th>
                                    <th>Tanggal Panen Estimasi</th>
                                    <th>Status</th>
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

            <!-- Modal Form Siklus -->
            <div class="modal fade" id="modalSiklus" tabindex="-1" aria-labelledby="modalSiklusTitle" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalSiklusTitle">Tambah Siklus</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>

                        <form id="formSiklus">
                            <input type="hidden" name="id" id="siklusId">

                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label" for="siklusNamaTanaman">Nama Tanaman</label>
                                    <input type="text" class="form-control" name="nama_tanaman" id="siklusNamaTanaman" maxlength="100" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="siklusLahanId">Lahan</label>
                                    <select class="form-select" name="lahan_id" id="siklusLahanId" required>
                                        <option value="">Pilih Lahan...</option>
                                        <!-- TODO: Isi option via JS fetch lahan -->
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="siklusTanggalTanam">Tanggal Tanam</label>
                                    <input type="date" class="form-control" name="tanggal_tanam" id="siklusTanggalTanam" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="siklusTanggalPanenEstimasi">Tanggal Panen Estimasi</label>
                                    <input type="date" class="form-control" name="tanggal_panen_estimasi" id="siklusTanggalPanenEstimasi">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="siklusStatus">Status</label>
                                    <select class="form-select" name="status" id="siklusStatus" required>
                                        <option value="Aktif">Aktif</option>
                                        <option value="Panen">Panen</option>
                                        <option value="Selesai">Selesai</option>
                                        <option value="Gagal">Gagal</option>
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

            <script src="/assets/js/siklus.js"></script>

<?php
require_once __DIR__ . "/layouts/footer.php";
?>
