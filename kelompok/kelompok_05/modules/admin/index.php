
<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

// Khusus admin.
require_role(['admin']);

// Ambil seluruh jenis dokumen.
$stmt = db()->prepare(
    'SELECT id, nama, deskripsi, aktif
     FROM document_types
     ORDER BY id DESC'
);
$stmt->execute();
$jenis_dokumen = $stmt->fetchAll();

$page_title = 'Kelola Jenis Dokumen';
require __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h1>Kelola Jenis Dokumen</h1>
    <p>Halaman untuk mengelola jenis dokumen CEKHAK.</p>

    <a href="<?= e(base_url('modules/admin/create.php')) ?>" class="btn">
        + Tambah Jenis Dokumen
    </a>
</div>

<div class="card">
    <h2>Daftar Jenis Dokumen</h2>

    <?php if (empty($jenis_dokumen)): ?>
        <p>Belum ada jenis dokumen.</p>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama</th>
                        <th>Deskripsi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jenis_dokumen as $i => $jenis): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($jenis['nama']) ?></td>
                            <td><?= e($jenis['deskripsi'] ?? '-') ?></td>
                            <td>
                                <?= $jenis['aktif'] ? 'Aktif' : 'Nonaktif' ?>
                            </td>
                            <td>
                                <a href="<?= e(base_url('modules/admin/edit.php?id=' . (int) $jenis['id'])) ?>">
                                    Edit
                                </a>

                                <form
                                    method="post"
                                    action="<?= e(base_url('modules/admin/delete.php')) ?>"
                                    onsubmit="return confirm('Yakin ingin menghapus jenis dokumen ini?')"
                                >
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $jenis['id'] ?>">
                                    <button type="submit" class="btn">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
