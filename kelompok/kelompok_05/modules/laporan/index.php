<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Metode permintaan tidak diizinkan.');
}
$user = current_user();
$caseId = filter_input(INPUT_GET, 'case', FILTER_VALIDATE_INT);
if (!$caseId) {
    http_response_code(400);
    exit('ID pemeriksaan tidak valid.');
}
$pdo = db();
$st = $pdo->prepare('SELECT c.id, c.user_id, c.judul, c.status, c.document_type_id, t.nama AS jenis FROM cases c JOIN document_types t ON t.id = c.document_type_id WHERE c.id = ?');
$st->execute([$caseId]);
$case = $st->fetch();
if (!$case) {
    http_response_code(404);
    exit('Pemeriksaan tidak ditemukan.');
}
if ((int) $case['user_id'] !== (int) $user['id'] && !in_array($user['role'], ['reviewer', 'admin'], true)) {
    http_response_code(403);
    exit('Kamu tidak diizinkan melihat laporan ini.');
}
$st = $pdo->prepare('SELECT persentase, jumlah_terpenuhi, jumlah_berlaku, hasil_json, created_at FROM analysis_results WHERE case_id = ? ORDER BY id DESC LIMIT 1');
$st->execute([$caseId]);
$analysis = $st->fetch();
$hasil = $analysis && $analysis['hasil_json'] ? json_decode($analysis['hasil_json'], true, 512, JSON_THROW_ON_ERROR) : [];
if (!is_array($hasil)) throw new UnexpectedValueException('Hasil analisis tidak berbentuk daftar.');
$byId = [];
foreach ($hasil as $item) if (isset($item['requirement_id'])) $byId[(int) $item['requirement_id']] = $item;
$st = $pdo->prepare('SELECT id, label, wajib, status_verifikasi, sumber FROM requirements WHERE document_type_id = ? ORDER BY urutan, id');
$st->execute([$case['document_type_id']]);
$requirements = $st->fetchAll();
$missing = [];
$unverified = [];
foreach ($requirements as $req) {
    $item = $byId[(int) $req['id']] ?? [];
    if (in_array($item['status'] ?? '', ['belum_lengkap', 'belum_diverifikasi'], true)) $missing[] = $req;
    if ($req['status_verifikasi'] !== 'terverifikasi') $unverified[] = $req;
}
$persentase = $analysis ? max(0, min(100, (float) $analysis['persentase'])) : 0;
$page_title = 'Laporan pemeriksaan';
require __DIR__ . '/../../includes/header.php';
?>
<style>@media print { .no-print { display: none !important; } .report-section { break-inside: avoid; } }</style>
<section class="card report-section">
    <h1>Laporan pemeriksaan</h1>
    <p><strong><?= e($case['judul']) ?></strong> · <?= e($case['jenis']) ?></p>
    <p>Status pemeriksaan: <?= status_badge($case['status']) ?></p>
    <?php if (!$analysis): ?><p>Belum ada hasil analisis. Laporan akan tersedia setelah pemeriksaan dianalisis.</p><?php else: ?>
        <p>Kelengkapan: <strong><?= e(number_format($persentase, 2, ',', '.')) ?>%</strong>
            (<?= e($analysis['jumlah_terpenuhi']) ?>/<?= e($analysis['jumlah_berlaku']) ?> persyaratan berlaku)</p>
        <div class="progress" role="progressbar" aria-valuenow="<?= e($persentase) ?>" aria-valuemin="0" aria-valuemax="100">
            <span style="width: <?= e($persentase) ?>%"></span>
        </div>
        <p>Analisis terakhir: <?= e($analysis['created_at']) ?></p>
    <?php endif; ?>
</section>
<section class="card report-section">
    <h2>Persyaratan belum lengkap</h2>
    <?php if (!$missing): ?><p><?= $analysis ? 'Tidak ada persyaratan yang tercatat belum lengkap.' : 'Jalankan analisis untuk mengetahui kelengkapan.' ?></p>
    <?php else: ?><ul><?php foreach ($missing as $req): ?><li><?= e($req['label']) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <h2>Aturan belum diverifikasi</h2>
    <?php if (!$unverified): ?><p>Semua aturan persyaratan telah diverifikasi.</p>
    <?php else: ?><ul><?php foreach ($unverified as $req): ?>
        <li><?= e($req['label']) ?><?= $req['sumber'] ? ' — sumber: ' . e($req['sumber']) : '' ?></li>
    <?php endforeach; ?></ul><?php endif; ?>
</section>
<section class="card report-section">
    <h2>Rincian persyaratan</h2>
    <div class="table-wrap"><table><thead><tr><th>Persyaratan</th><th>Hasil</th><th>Verifikasi aturan</th><th>Catatan</th></tr></thead><tbody>
        <?php foreach ($requirements as $req): $item = $byId[(int) $req['id']] ?? []; ?>
            <tr><td><?= e($req['label']) ?><?= $req['wajib'] ? ' *' : '' ?></td>
                <td><?= e(status_label((string) ($item['status'] ?? 'belum_dianalisis'))) ?></td>
                <td><?= e(status_label($req['status_verifikasi'])) ?></td><td><?= e($item['pesan'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody></table></div>
    <p><strong>Disclaimer:</strong> CEKHAK membantu memeriksa kelengkapan administratif. Laporan ini bukan pendapat hukum final dan tidak menjamin dokumen sah atau diterima instansi tertentu. Persyaratan yang belum diverifikasi perlu diperiksa terhadap sumber resmi.</p>
    <button class="btn btn-primary no-print" id="print-report" type="button">Cetak laporan</button>
</section>
<script>document.getElementById('print-report').addEventListener('click', function () { window.print(); });</script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
