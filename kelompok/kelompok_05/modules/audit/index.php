<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_role(['admin']);
$st = db()->prepare('SELECT a.aksi, a.entitas, a.entitas_id, a.detail, a.ip, a.created_at, u.nama AS nama_pengguna FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC, a.id DESC LIMIT 200');
$st->execute();
$logs = $st->fetchAll();
$page_title = 'Log aktivitas';
require __DIR__ . '/../../includes/header.php';
?>
<section class="card">
    <h1>Log aktivitas</h1>
    <?php if (!$logs): ?><p>Belum ada aktivitas tercatat.</p><?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Entitas</th><th>ID</th><th>Detail</th><th>IP</th></tr></thead>
            <tbody><?php foreach ($logs as $log): ?><tr>
                <td><?= e($log['created_at']) ?></td><td><?= e($log['nama_pengguna'] ?? 'Pengguna dihapus') ?></td>
                <td><?= e($log['aksi']) ?></td><td><?= e($log['entitas']) ?></td><td><?= e($log['entitas_id'] ?? '-') ?></td>
                <td><?= e($log['detail'] ?? '') ?></td><td><?= e($log['ip']) ?></td>
            </tr><?php endforeach; ?></tbody>
        </table></div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
