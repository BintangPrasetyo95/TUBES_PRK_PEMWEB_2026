<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/_lib.php';
require_role(['pengguna']);
$user = current_user();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pdo->prepare('UPDATE review_logs r JOIN cases c ON c.id = r.case_id SET r.dibaca_pengguna = 1 WHERE c.user_id = ? AND r.dibaca_pengguna = 0')->execute([$user['id']]);
    flash('success', 'Semua notifikasi ditandai sudah dibaca.');
    redirect('modules/pemeriksaan/notifikasi.php');
}

$st = $pdo->prepare('SELECT r.id, r.case_id, r.catatan, r.status_baru, r.dibaca_pengguna, r.created_at, c.judul, u.nama AS reviewer_nama
                     FROM review_logs r
                     JOIN cases c ON c.id = r.case_id
                     JOIN users u ON u.id = r.reviewer_id
                     WHERE c.user_id = ? ORDER BY r.dibaca_pengguna ASC, r.id DESC LIMIT 50');
$st->execute([$user['id']]);
$daftar = $st->fetchAll();
$belumDibaca = jumlah_notifikasi_belum_dibaca($pdo, (int) $user['id']);

$page_title = 'Notifikasi';
$extra_css = ['assets/pemeriksaan/pemeriksaan.css'];
require __DIR__ . '/../../includes/header.php';
?>
<section class="card">
    <h1>Notifikasi <?php if ($belumDibaca): ?><span class="badge badge-warn"><?= e($belumDibaca) ?> baru</span><?php endif; ?></h1>
    <p class="muted">Catatan dan perubahan status dari reviewer pada pemeriksaan kamu.</p>
    <?php if ($belumDibaca): ?>
        <form method="post"><?= csrf_field() ?><button class="btn btn-small" type="submit">Tandai semua sudah dibaca</button></form>
    <?php endif; ?>
</section>
<?php if (!$daftar): ?>
    <section class="card"><div class="state">Belum ada notifikasi.</div></section>
<?php endif; ?>
<?php foreach ($daftar as $n): ?>
    <section class="card notif-item <?= (int) $n['dibaca_pengguna'] ? '' : 'notif-baru' ?>">
        <div class="notif-meta">
            <?php if (!(int) $n['dibaca_pengguna']): ?><span class="badge badge-warn">Baru</span><?php endif; ?>
            <a href="form.php?id=<?= e($n['case_id']) ?>#catatan-reviewer"><strong><?= e($n['judul']) ?></strong></a>
            · <?= e($n['reviewer_nama']) ?> · <?= e($n['created_at']) ?>
            <?php if ($n['status_baru']): ?> · status menjadi <?= status_badge((string) $n['status_baru']) ?><?php endif; ?>
        </div>
        <?php if (trim((string) $n['catatan']) !== ''): ?><p><?= nl2br(e($n['catatan'])) ?></p><?php endif; ?>
    </section>
<?php endforeach; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
