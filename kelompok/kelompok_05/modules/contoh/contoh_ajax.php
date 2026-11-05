<?php
// ============================================================================
// HALAMAN CONTOH AJAX: pasangan dari modules/contoh/api/cases.php dan assets/contoh/contoh_ajax.js.
// SEMENTARA: folder modules/contoh/ dihapus sebelum PR final ke LabKom.
//
// Pola: PHP hanya menyajikan kerangka HTML (form, tabel kosong, modal). Data dimuat oleh JavaScript
// lewat fetch() ke endpoint JSON, lalu tabel dirender lewat DOM tanpa muat ulang halaman.
// ============================================================================
require_once __DIR__ . '/../../includes/bootstrap.php';
require_role(['pengguna']);

// Opsi dropdown disajikan langsung oleh PHP (data kecil dan jarang berubah).
$types = db()->query('SELECT id, nama FROM document_types WHERE aktif = 1 ORDER BY nama')->fetchAll();

$page_title = 'Contoh AJAX';
$extra_js = ['assets/contoh/contoh_ajax.js'];
require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <h1>Contoh: AJAX dan REST API</h1>
    <p class="muted">Tabel di bawah dimuat lewat fetch() dari <code>modules/contoh/api/cases.php</code>, tanpa muat ulang halaman.</p>

    <form id="form-tambah-case" method="post" action="#" novalidate>
        <div class="form-group">
            <label for="judul">Judul pemeriksaan</label>
            <input type="text" id="judul" name="judul" maxlength="200" required>
        </div>
        <div class="form-group">
            <label for="document_type_id">Jenis dokumen</label>
            <select id="document_type_id" name="document_type_id" required>
                <option value="">Pilih...</option>
                <?php foreach ($types as $t): ?>
                    <option value="<?= e($t['id']) ?>"><?= e($t['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Tambah</button>
    </form>
</div>

<div class="card">
    <div class="toolbar">
        <input type="text" id="input-search" placeholder="Cari judul..." aria-label="Cari judul">
        <span>Total: <strong id="total-cases">0</strong></span>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Judul</th><th>Jenis</th><th>Status</th><th>Dibuat</th><th>Aksi</th></tr></thead>
            <tbody id="tabel-body-cases"></tbody>
        </table>
    </div>
    <div id="status-cases"></div>
</div>

<div class="modal" id="modal-edit" aria-hidden="true">
    <div class="modal-dialog card" role="dialog" aria-labelledby="judul-modal-edit">
        <h2 id="judul-modal-edit">Ubah pemeriksaan</h2>
        <form id="form-edit-case" novalidate>
            <input type="hidden" id="edit-id" name="id">
            <div class="form-group">
                <label for="edit-judul">Judul pemeriksaan</label>
                <input type="text" id="edit-judul" name="judul" maxlength="200" required>
            </div>
            <div class="form-group">
                <label for="edit-jenis">Jenis dokumen</label>
                <select id="edit-jenis" name="document_type_id" required>
                    <option value="">Pilih...</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= e($t['id']) ?>"><?= e($t['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Simpan perubahan</button>
            <button type="button" class="btn" data-modal-close>Batal</button>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
