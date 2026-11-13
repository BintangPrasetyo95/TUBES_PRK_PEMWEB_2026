<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_role(['pengguna']);
$user = current_user();
$st = db()->prepare('SELECT id, judul, status, updated_at FROM cases WHERE user_id = ? ORDER BY updated_at DESC, id DESC');
$st->execute([$user['id']]);
$cases = $st->fetchAll();
$page_title = 'Riwayat Pemeriksaan';
require __DIR__ . '/../../includes/header.php';
?>
<section class="card">
    <h1>Riwayat Pemeriksaan</h1>
    <?php if (!$cases): ?>
        <p>Belum ada pemeriksaan. <a href="<?= e(base_url('modules/pemeriksaan/buat.php')) ?>">Buat pemeriksaan pertama</a>.</p>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Pemeriksaan</th><th>Status</th><th>Diperbarui</th><th>Aksi</th></tr></thead>
            <tbody><?php foreach ($cases as $case): ?>
                <tr>
                    <td><?= e($case['judul']) ?></td>
                    <td><?= status_badge($case['status']) ?></td>
                    <td><?= e($case['updated_at']) ?></td>
                    <td><div class="table-actions">
                        <a class="btn btn-sm" href="<?= e(base_url('modules/hasil/index.php?case=' . (int) $case['id'])) ?>">Hasil</a>
                        <a class="btn btn-sm" href="<?= e(base_url('modules/laporan/index.php?case=' . (int) $case['id'])) ?>">Laporan</a>
                        <?php if (in_array($case['status'], ['draft', 'belum_lengkap'], true)): ?>
                            <a class="btn btn-sm" href="<?= e(base_url('modules/pemeriksaan/form.php?case=' . (int) $case['id'])) ?>">Lanjutkan</a>
                        <?php endif; ?>
                    </div></td>
                </tr>
            <?php endforeach; ?></tbody>
        </table></div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
