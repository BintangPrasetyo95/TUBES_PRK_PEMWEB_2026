
<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

require_role(['admin']);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $nama = trim($_POST['nama'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if ($nama === '') {
        $error = 'Nama jenis dokumen wajib diisi.';
    } else {
        $stmt = db()->prepare(
            'INSERT INTO document_types (nama, deskripsi, aktif)
             VALUES (?, ?, 1)'
        );
        $stmt->execute([$nama, $deskripsi]);

        redirect('modules/admin/index.php');
    }
}

$page_title = 'Tambah Jenis Dokumen';
require __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h1>Tambah Jenis Dokumen</h1>

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
                value="<?= e($_POST['nama'] ?? '') ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="deskripsi">Deskripsi</label>
            <textarea
                id="deskripsi"
                name="deskripsi"
                rows="4"
            ><?= e($_POST['deskripsi'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn">Simpan</button>
        <a href="<?= e(base_url('modules/admin/index.php')) ?>">Batal</a>
    </form>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
