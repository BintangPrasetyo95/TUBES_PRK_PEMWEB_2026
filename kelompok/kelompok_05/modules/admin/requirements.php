
<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

require_role(['admin']);

// Ambil seluruh persyaratan beserta nama jenis dokumennya.
$stmt = db()->prepare(
    'SELECT
        r.id,
        r.kode,
        r.label,
        r.tipe_input,
        r.wajib,
        r.status_verifikasi,
        r.urutan,
        d.nama AS nama_dokumen
     FROM requirements r
     JOIN document_types d ON d.id = r.document_type_id
     ORDER BY d.nama ASC, r.urutan ASC, r.id ASC'
);
$stmt->execute();
$persyaratan = $stmt->fetchAll();

$page_title = 'Kelola Persyaratan';
require __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h1>Kelola Persyaratan Dokumen</h1>
    <p>Halaman untuk mengelola daftar persyaratan setiap jenis dokumen.</p>

    <a href="<?= e(base_url('modules/admin/requirement_create.php')) ?>" class="btn">
        + Tambah Persyaratan
    </a>
</div>

<div class="card">
    <h2>Daftar Persyaratan</h2>

    <?php if (empty($persyaratan)): ?>
        <p>Belum ada persyaratan.</p>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Jenis Dokumen</th>
                        <th>Kode</th>
                        <th>Persyaratan</th>
                        <th>Tipe Input</th>
                        <th>Wajib</th>
                        <th>Verifikasi</th>
                        <th>Urutan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($persyaratan as $i => $item): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($item['nama_dokumen']) ?></td>
                            <td><?= e($item['kode']) ?></td>
                            <td><?= e($item['label']) ?></td>
                            <td><?= e($item['tipe_input']) ?></td>
                            <td><?= $item['wajib'] ? 'Ya' : 'Tidak' ?></td>
                            <td>
                                <?= $item['status_verifikasi'] === 'terverifikasi'
                                    ? 'Terverifikasi'
                                    : 'Belum diverifikasi' ?>
                            </td>
                            <td><?= (int) $item['urutan'] ?></td>
                            <td>
                                <a href="<?= e(base_url('modules/admin/requirement_edit.php?id=' . (int) $item['id'])) ?>">
                                    Edit
                                </a>

                                <form
                                    method="post"
                                    action="<?= e(base_url('modules/admin/requirement_delete.php')) ?>"
                                    onsubmit="return confirm('Yakin ingin menghapus persyaratan ini?')"
                                >
                                    <?= csrf_field() ?>
                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int) $item['id'] ?>"
                                    >
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
