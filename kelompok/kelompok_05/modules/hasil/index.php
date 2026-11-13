<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();
$user = current_user();
$caseId = filter_input(INPUT_GET, 'case', FILTER_VALIDATE_INT);
if (!$caseId) {
    http_response_code(400);
    exit('ID pemeriksaan tidak valid.');
}
$st = db()->prepare('SELECT c.id, c.user_id, c.judul, c.status, t.nama AS jenis FROM cases c JOIN document_types t ON t.id = c.document_type_id WHERE c.id = ?');
$st->execute([$caseId]);
$case = $st->fetch();
if (!$case) {
    http_response_code(404);
    exit('Pemeriksaan tidak ditemukan.');
}
if ((int) $case['user_id'] !== (int) $user['id'] && !in_array($user['role'], ['reviewer', 'admin'], true)) {
    http_response_code(403);
    exit('Kamu tidak diizinkan melihat hasil ini.');
}
$st = db()->prepare('SELECT persentase, jumlah_terpenuhi, jumlah_berlaku, hasil_json, created_at FROM analysis_results WHERE case_id = ? ORDER BY id DESC LIMIT 1');
$st->execute([$caseId]);
$analysis = $st->fetch();
$hasil = $analysis && $analysis['hasil_json'] ? json_decode($analysis['hasil_json'], true, 512, JSON_THROW_ON_ERROR) : [];
if (!is_array($hasil)) throw new UnexpectedValueException('Hasil analisis tidak berbentuk daftar.');
$bolehAnalisis = (int) $case['user_id'] === (int) $user['id'] && $user['role'] === 'pengguna'
    && $case['status'] !== 'sedang_ditinjau';
$page_title = 'Hasil analisis';
require __DIR__ . '/../../includes/header.php';
?>
<section class="card">
    <h1>Hasil pemeriksaan</h1>
    <h2><?= e($case['judul']) ?></h2>
    <p><?= e($case['jenis']) ?> · <?= status_badge($case['status']) ?></p>
    <?php if (!$analysis): ?><p>Belum ada hasil analisis. Jalankan analisis untuk membuat hasil.</p><?php else: ?>
        <p>Kelengkapan: <strong><?= e(number_format((float) $analysis['persentase'], 2, ',', '.')) ?>%</strong>
            (<?= e($analysis['jumlah_terpenuhi']) ?> dari <?= e($analysis['jumlah_berlaku']) ?> persyaratan berlaku)</p>
        <p>Analisis terakhir: <?= e($analysis['created_at']) ?></p>
    <?php endif; ?>
    <div class="btn-group">
        <a class="btn" href="<?= e(base_url('modules/laporan/index.php?case=' . (int) $caseId)) ?>">Lihat laporan</a>
        <?php if ($bolehAnalisis): ?><form method="post" action="<?= e(base_url('modules/analisis/engine.php')) ?>" class="no-print">
            <?= csrf_field() ?><input type="hidden" name="case_id" value="<?= e($caseId) ?>">
            <button class="btn btn-primary" type="submit"><?= $analysis ? 'Periksa Ulang' : 'Jalankan Analisis' ?></button>
        </form><?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
