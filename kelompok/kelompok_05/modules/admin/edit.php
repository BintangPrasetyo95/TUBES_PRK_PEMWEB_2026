
<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

require_role(['admin']);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(404);
    exit('Jenis dokumen tidak ditemukan.');
}

$stmt = db()->prepare(
    'SELECT id, nama, deskripsi, aktif
     FROM document_types
     WHERE id = ?'
);
$stmt->execute([$id]);
$jenis = $stmt->fetch();

if (!$jenis) {
    http_response_code(404);
    exit('Jenis dokumen tidak ditemukan.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $nama = trim($_POST['nama'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $aktif = isset($_POST['aktif']) ? 1 : 0;

    if ($nama === '') {
        $error = 'Nama jenis dokumen wajib diisi.';
    } else {
        $stmt = db()->prepare(
            'UPDATE document_types
             SET nama = ?, deskripsi = ?, aktif = ?
             WHERE id = ?'
        );
        $stmt->execute([$nama, $deskripsi, $aktif, $id]);

        redirect('modules/admin/index.php');
    }
}

$page_title = 'Edit Jenis Dokumen';
require __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h1>Edit Jenis Dokumen</h1>

    <?php if ($error !== ''): ?>
        <p><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="nama">Nama Jenis Dokumen</label>
            <input
                type="text"
                id="nama"
                name="nama"
                maxlength="150"
                value="<?= e($_POST['nama'] ?? $jenis['nama']) ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="deskripsi">Deskripsi</label>
            <textarea
                id="deskripsi"
                name="deskripsi"
                rows="4"
            ><?= e($_POST['deskripsi'] ?? $jenis['deskripsi']) ?></textarea>
        </div>

        <div class="form-group">
            <label>
                <input
                    type="checkbox"
                    name="aktif"
                    value="1"
                    <?= (isset($_POST['aktif']) ? true : (bool) $jenis['aktif']) ? 'checked' : '' ?>
                >
                Jenis dokumen aktif
            </label>
        </div>

        <button type="submit" class="btn">Simpan Perubahan</button>
        <a href="<?= e(base_url('modules/admin/index.php')) ?>">Batal</a>
    </form>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
