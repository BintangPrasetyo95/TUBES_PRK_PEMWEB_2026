<?php
// app/Views/pekerja.php
// Tugas: CRUD Pekerja (tabel + form modal).
// Diisi oleh: Devi.

$title = "Manajemen Pekerja";
require_once __DIR__ . "/layouts/header.php";
require_once __DIR__ . "/layouts/sidebar.php";
?>
            <div class="container-fluid p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1>Data Pekerja</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPekerja" onclick="resetFormPekerja()">
                        Tambah Pekerja
                    </button>
                </div>

                <div class="card">
                    <div class="card-body">
                        <table class="table table-striped" id="tabelPekerja">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>No. Telepon</th>
                                    <th>Jabatan</th>
                                    <th>Gaji Pokok</th>
                                    <th>Tanggal Bergabung</th>
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

            <!-- Modal Form Pekerja -->
            <div class="modal fade" id="modalPekerja" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalPekerjaTitle">Tambah Pekerja</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="formPekerja">
                            <input type="hidden" name="id" id="pekerjaId">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Nama</label>
                                    <input type="text" class="form-control" name="nama" id="pekerjaNama" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">No. Telepon</label>
                                    <input type="text" class="form-control" name="no_telepon" id="pekerjaNoTelepon" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Jabatan</label>
                                    <select class="form-select" name="jabatan" id="pekerjaJabatan" required>
                                        <option value="">Pilih...</option>
                                        <option value="Kepala Kebun">Kepala Kebun</option>
                                        <option value="Petani">Petani</option>
                                        <option value="Operator Mesin">Operator Mesin</option>
                                        <option value="Admin">Admin</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Gaji Pokok</label>
                                    <input type="number" class="form-control" name="gaji_pokok" id="pekerjaGajiPokok" required min="0" step="0.01">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Bergabung</label>
                                    <input type="date" class="form-control" name="tanggal_bergabung" id="pekerjaTanggalBergabung" required>
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

            <script src="/assets/js/pekerja.js"></script>
<?php
require_once __DIR__ . "/layouts/footer.php";
?>