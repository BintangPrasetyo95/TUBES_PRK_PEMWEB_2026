<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_role(['pengguna']);
$user = current_user();
$pdo = db();

$st = $pdo->prepare('UPDATE review_logs l JOIN cases c ON c.id = l.case_id SET l.dibaca_pengguna = 1 WHERE c.user_id = ? AND l.dibaca_pengguna = 0');
$st->execute([$user['id']]);
$st = $pdo->prepare("SELECT c.id, c.judul, c.status, c.updated_at, t.nama AS jenis FROM cases c JOIN document_types t ON t.id = c.document_type_id WHERE c.user_id = ? AND c.status <> 'selesai' ORDER BY c.updated_at DESC LIMIT 20");
$st->execute([$user['id']]);
$cases = $st->fetchAll();
$st = $pdo->prepare('SELECT c.judul, l.catatan, l.status_baru, l.created_at, u.nama AS reviewer FROM review_logs l JOIN cases c ON c.id = l.case_id JOIN users u ON u.id = l.reviewer_id WHERE c.user_id = ? AND l.catatan IS NOT NULL AND l.catatan <> ? ORDER BY l.created_at DESC LIMIT 20');
$st->execute([$user['id'], '']);
$notes = $st->fetchAll();
$page_title = 'Dashboard';
require __DIR__ . '/../../includes/header.php';
?>
<section class="card">
    <h1>Dashboard</h1>
    <p>Halo, <?= e($user['nama']) ?>. Berikut status pemeriksaan aktif kamu.</p>
    <?php if (!$cases): ?><p>Belum ada pemeriksaan aktif.</p><?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Pemeriksaan</th><th>Jenis dokumen</th><th>Status</th><th>Diperbarui</th></tr></thead>
            <tbody><?php foreach ($cases as $case): ?><tr>
                <td><a href="<?= e(base_url('modules/pemeriksaan/form.php?id=' . (int) $case['id'])) ?>"><?= e($case['judul']) ?></a></td>
                <td><?= e($case['jenis']) ?></td><td><?= status_badge($case['status']) ?></td><td><?= e($case['updated_at']) ?></td>
            </tr><?php endforeach; ?></tbody>
        </table></div>
    <?php endif; ?>
</section>
<section class="card">
    <h2>Catatan reviewer</h2>
    <?php if (!$notes): ?><p>Belum ada catatan reviewer.</p><?php else: ?>
        <?php foreach ($notes as $note): ?><article>
            <h3><?= e($note['judul']) ?></h3>
            <p><?= nl2br(e($note['catatan'])) ?></p>
            <small><?= e($note['reviewer']) ?> · <?= e($note['created_at']) ?> · <?= e(status_label($note['status_baru'] ?? '')) ?></small>
        </article><?php endforeach; ?>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
