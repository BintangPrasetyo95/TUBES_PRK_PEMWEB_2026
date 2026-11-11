
<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

require_role(['admin']);

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    redirect('modules/admin/requirements.php');
}

// Ambil data persyaratan.
$stmt = db()->prepare(
    'SELECT * FROM requirements WHERE id = ?'
);
$stmt->execute([$id]);
$persyaratan = $stmt->fetch();

if (!$persyaratan) {
    redirect('modules/admin/requirements.php');
}

// Ambil jenis dokumen.
$stmt = db()->prepare(
    'SELECT id, nama FROM document_types ORDER BY nama ASC'
);
$stmt->execute();
$jenis_dokumen = $stmt->fetchAll();

$error = '';

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
    } elseif (
        !in_array($tipe_input, $tipe_valid, true) ||
        !in_array($aturan_format, $aturan_valid, true) ||
        !in_array($status_verifikasi, $status_valid, true)
    ) {
        $error = 'Pilihan data tidak valid.';
    } else {
        $cek = db()->prepare(
            'SELECT id FROM document_types WHERE id = ?'
        );
        $cek->execute([$document_type_id]);

        if (!$cek->fetch()) {
            $error = 'Jenis dokumen tidak ditemukan.';
        } else {
            try {
                $stmt = db()->prepare(
                    'UPDATE requirements SET
                        document_type_id = ?,
                        kode = ?,
                        label = ?,
                        tipe_input = ?,
                        wajib = ?,
                        aturan_format = ?,
                        penjelasan = ?,
                        sumber = ?,
                        tanggal_pembaruan = ?,
                        status_verifikasi = ?,
                        urutan = ?
                     WHERE id = ?'
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
                    $urutan,
                    $id
                ]);

                redirect('modules/admin/requirements.php');
            } catch (PDOException $e) {
                $error = 'Gagal memperbarui. Pastikan kode persyaratan tidak duplikat.';
            }
        }
    }
}

$page_title = 'Edit Persyaratan';
require __DIR__ . '/../../includes/header.php';

function old_value($key, $data)
{
    return e($_POST[$key] ?? $data[$key] ?? '');
}
?>

<div class="card">
    <h1>Edit Persyaratan</h1>

    <?php if ($error !== ''): ?>
        <p><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="document_type_id">Jenis Dokumen</label>
            <select id="document_type_id" name="document_type_id" required>
                <?php foreach ($jenis_dokumen as $jenis): ?>
                    <option
                        value="<?= (int) $jenis['id'] ?>"
                        <?= (int) old_value('document_type_id', $persyaratan) === (int) $jenis['id'] ? 'selected' : '' ?>
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
                value="<?= old_value('kode', $persyaratan) ?>"
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
                value="<?= old_value('label', $persyaratan) ?>"
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
                        <?= old_value('tipe_input', $persyaratan) === $value ? 'selected' : '' ?>
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
                    <?= (int) ($_POST['wajib'] ?? $persyaratan['wajib']) === 1 ? 'checked' : '' ?>
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
                        <?= old_value('aturan_format', $persyaratan) === $aturan ? 'selected' : '' ?>
                    >
                        <?= e(ucfirst($aturan)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="penjelasan">Penjelasan</label>
            <textarea id="penjelasan" name="penjelasan" rows="3"><?= old_value('penjelasan', $persyaratan) ?></textarea>
        </div>

        <div class="form-group">
            <label for="sumber">Sumber</label>
            <input
                type="text"
                id="sumber"
                name="sumber"
                maxlength="255"
                value="<?= old_value('sumber', $persyaratan) ?>"
            >
        </div>

        <div class="form-group">
            <label for="tanggal_pembaruan">Tanggal Pembaruan</label>
            <input
                type="date"
                id="tanggal_pembaruan"
                name="tanggal_pembaruan"
                value="<?= old_value('tanggal_pembaruan', $persyaratan) ?>"
            >
        </div>

        <div class="form-group">
            <label for="status_verifikasi">Status Verifikasi</label>
            <select id="status_verifikasi" name="status_verifikasi">
                <option
                    value="belum_diverifikasi"
                    <?= old_value('status_verifikasi', $persyaratan) === 'belum_diverifikasi' ? 'selected' : '' ?>
                >
                    Belum Diverifikasi
                </option>
                <option
                    value="terverifikasi"
                    <?= old_value('status_verifikasi', $persyaratan) === 'terverifikasi' ? 'selected' : '' ?>
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
                value="<?= old_value('urutan', $persyaratan) ?>"
            >
        </div>

        <button type="submit" class="btn">Simpan Perubahan</button>
        <a href="<?= e(base_url('modules/admin/requirements.php')) ?>">Batal</a>
    </form>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
