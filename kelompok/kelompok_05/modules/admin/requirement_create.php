
<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

require_role(['admin']);

$error = '';

// Ambil jenis dokumen untuk pilihan dropdown.
$stmt = db()->prepare(
    'SELECT id, nama
     FROM document_types
     ORDER BY nama ASC'
);
$stmt->execute();
$jenis_dokumen = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $document_type_id = (int) ($_POST['document_type_id'] ?? 0);
    $kode = trim($_POST['kode'] ?? '');
    $label = trim($_POST['label'] ?? '');
    $tipe_input = $_POST['tipe_input'] ?? 'text';
    $wajib = isset($_POST['wajib']) ? 1 : 0;
    $aturan_format = $_POST['aturan_format'] ?? '';
    $penjelasan = trim($_POST['penjelasan'] ?? '');
    $sumber = trim($_POST['sumber'] ?? '');
    $tanggal_pembaruan = $_POST['tanggal_pembaruan'] ?? '';
    $status_verifikasi = $_POST['status_verifikasi'] ?? 'belum_diverifikasi';
    $urutan = (int) ($_POST['urutan'] ?? 0);

    $tipe_valid = ['text', 'textarea', 'date', 'number', 'file', 'checkbox'];
    $aturan_valid = ['', 'tanggal', 'angka', 'nomor', 'email'];
    $status_valid = ['belum_diverifikasi', 'terverifikasi'];

    if ($document_type_id <= 0 || $kode === '' || $label === '') {
        $error = 'Jenis dokumen, kode, dan nama persyaratan wajib diisi.';
    } elseif (!in_array($tipe_input, $tipe_valid, true)) {
        $error = 'Tipe input tidak valid.';
    } elseif (!in_array($aturan_format, $aturan_valid, true)) {
        $error = 'Aturan format tidak valid.';
    } elseif (!in_array($status_verifikasi, $status_valid, true)) {
        $error = 'Status verifikasi tidak valid.';
    } else {
        // Pastikan jenis dokumen benar-benar tersedia.
        $cek = db()->prepare(
            'SELECT id FROM document_types WHERE id = ?'
        );
        $cek->execute([$document_type_id]);

        if (!$cek->fetch()) {
            $error = 'Jenis dokumen tidak ditemukan.';
        } else {
            try {
                $stmt = db()->prepare(
                    'INSERT INTO requirements (
                        document_type_id,
                        kode,
                        label,
                        tipe_input,
                        wajib,
                        aturan_format,
                        penjelasan,
                        sumber,
                        tanggal_pembaruan,
                        status_verifikasi,
                        urutan
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );

                $stmt->execute([
                    $document_type_id,
                    $kode,
                    $label,
                    $tipe_input,
                    $wajib,
                    $aturan_format !== '' ? $aturan_format : null,
                    $penjelasan !== '' ? $penjelasan : null,
                    $sumber !== '' ? $sumber : null,
                    $tanggal_pembaruan !== '' ? $tanggal_pembaruan : null,
                    $status_verifikasi,
                    $urutan
                ]);

                redirect('modules/admin/requirements.php');
            } catch (PDOException $e) {
                $error = 'Gagal menyimpan. Pastikan kode persyaratan belum digunakan pada jenis dokumen yang sama.';
            }
        }
    }
}

$page_title = 'Tambah Persyaratan';
require __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h1>Tambah Persyaratan</h1>

    <?php if ($error !== ''): ?>
        <p><?= e($error) ?></p>
    <?php endif; ?>

    <?php if (empty($jenis_dokumen)): ?>
        <p>Belum ada jenis dokumen. Tambahkan jenis dokumen terlebih dahulu.</p>
        <a href="<?= e(base_url('modules/admin/index.php')) ?>">
            Kembali
        </a>
    <?php else: ?>
        <form method="post">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="document_type_id">Jenis Dokumen</label>
                <select id="document_type_id" name="document_type_id" required>
                    <option value="">-- Pilih Jenis Dokumen --</option>
                    <?php foreach ($jenis_dokumen as $jenis): ?>
                        <option
                            value="<?= (int) $jenis['id'] ?>"
                            <?= (int) ($_POST['document_type_id'] ?? 0) === (int) $jenis['id'] ? 'selected' : '' ?>
                        >
                            <?= e($jenis['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="kode">Kode Persyaratan</label>
                <input
                    type="text"
                    id="kode"
                    name="kode"
                    maxlength="50"
                    value="<?= e($_POST['kode'] ?? '') ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="label">Nama Persyaratan</label>
                <input
                    type="text"
                    id="label"
                    name="label"
                    maxlength="200"
                    value="<?= e($_POST['label'] ?? '') ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="tipe_input">Tipe Input</label>
                <select id="tipe_input" name="tipe_input">
                    <?php
                    $tipe_options = [
                        'text' => 'Teks',
                        'textarea' => 'Teks Panjang',
                        'date' => 'Tanggal',
                        'number' => 'Angka',
                        'file' => 'File',
                        'checkbox' => 'Checkbox'
                    ];
                    ?>
                    <?php foreach ($tipe_options as $value => $nama): ?>
                        <option
                            value="<?= e($value) ?>"
                            <?= ($_POST['tipe_input'] ?? 'text') === $value ? 'selected' : '' ?>
                        >
                            <?= e($nama) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>
                    <input
                        type="checkbox"
                        name="wajib"
                        value="1"
                        <?= !isset($_POST['wajib']) || $_POST['wajib'] ? 'checked' : '' ?>
                    >
                    Persyaratan wajib
                </label>
            </div>

            <div class="form-group">
                <label for="aturan_format">Aturan Format</label>
                <select id="aturan_format" name="aturan_format">
                    <option value="">-- Tidak ada --</option>
                    <?php foreach (['tanggal', 'angka', 'nomor', 'email'] as $aturan): ?>
                        <option
                            value="<?= e($aturan) ?>"
                            <?= ($_POST['aturan_format'] ?? '') === $aturan ? 'selected' : '' ?>
                        >
                            <?= e(ucfirst($aturan)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="penjelasan">Penjelasan</label>
                <textarea
                    id="penjelasan"
                    name="penjelasan"
                    rows="3"
                ><?= e($_POST['penjelasan'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="sumber">Sumber</label>
                <input
                    type="text"
                    id="sumber"
                    name="sumber"
                    maxlength="255"
                    value="<?= e($_POST['sumber'] ?? '') ?>"
                >
            </div>

            <div class="form-group">
                <label for="tanggal_pembaruan">Tanggal Pembaruan</label>
                <input
                    type="date"
                    id="tanggal_pembaruan"
                    name="tanggal_pembaruan"
                    value="<?= e($_POST['tanggal_pembaruan'] ?? '') ?>"
                >
            </div>

            <div class="form-group">
                <label for="status_verifikasi">Status Verifikasi</label>
                <select id="status_verifikasi" name="status_verifikasi">
                    <option value="belum_diverifikasi">Belum Diverifikasi</option>
                    <option
                        value="terverifikasi"
                        <?= ($_POST['status_verifikasi'] ?? '') === 'terverifikasi' ? 'selected' : '' ?>
                    >
                        Terverifikasi
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label for="urutan">Urutan Tampilan</label>
                <input
                    type="number"
                    id="urutan"
                    name="urutan"
                    value="<?= e($_POST['urutan'] ?? '0') ?>"
                >
            </div>

            <button type="submit" class="btn">Simpan Persyaratan</button>
            <a href="<?= e(base_url('modules/admin/requirements.php')) ?>">
                Batal
            </a>
        </form>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
