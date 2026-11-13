<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_role(['reviewer']);
$user = current_user();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $caseId = filter_var($_POST['case_id'] ?? null, FILTER_VALIDATE_INT);
    $rawStatus = $_POST['status_baru'] ?? '';
    $rawNote = $_POST['catatan'] ?? '';
    if (!is_string($rawStatus) || !is_string($rawNote)) {
        flash('error', 'Status atau catatan tidak valid.');
        redirect('modules/reviewer/tinjau.php');
    }
    $status = $rawStatus;
    $note = trim($rawNote);
    if (!$caseId || !in_array($status, ['selesai', 'belum_lengkap'], true) || mb_strlen($note) > 5000) {
        flash('error', 'Status, nomor pemeriksaan, atau catatan tidak valid.');
        redirect('modules/reviewer/tinjau.php');
    }
    $pdo->beginTransaction();
    $st = $pdo->prepare("SELECT status FROM cases WHERE id = ? AND status IN ('siap_ditinjau','sedang_ditinjau') AND (reviewer_id IS NULL OR reviewer_id = ?) FOR UPDATE");
    $st->execute([$caseId, $user['id']]);
    $case = $st->fetch();
    if (!$case) {
        $pdo->rollBack();
        flash('error', 'Pemeriksaan tidak tersedia atau sedang ditangani reviewer lain.');
        redirect('modules/reviewer/tinjau.php');
    }
    $st = $pdo->prepare('UPDATE cases SET status = ?, reviewer_id = ? WHERE id = ?');
    $st->execute([$status, $user['id'], $caseId]);
    $st = $pdo->prepare('INSERT INTO review_logs (case_id, reviewer_id, catatan, status_lama, status_baru, dibaca_pengguna) VALUES (?, ?, ?, ?, ?, 0)');
    $st->execute([$caseId, $user['id'], $note === '' ? null : $note, $case['status'], $status]);
    $pdo->commit();
    flash('success', 'Hasil pemeriksaan berhasil disimpan.');
    redirect('modules/reviewer/tinjau.php');
}

$st = $pdo->prepare("SELECT c.id, c.judul, c.status, c.created_at, u.nama AS pemohon, t.nama AS jenis FROM cases c JOIN users u ON u.id = c.user_id JOIN document_types t ON t.id = c.document_type_id WHERE c.status IN ('siap_ditinjau','sedang_ditinjau') AND (c.reviewer_id IS NULL OR c.reviewer_id = ?) ORDER BY c.created_at ASC, c.id");
$st->execute([$user['id']]);
$cases = [];
foreach ($st->fetchAll() as $row) {
    $id = (int) $row['id'];
    $cases[$id] = $row + ['files' => [], 'answers' => []];
}
if ($cases) {
    $ids = array_keys($cases);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare("SELECT case_id, id, nama_asli FROM case_files WHERE case_id IN ($placeholders) ORDER BY id");
    $st->execute($ids);
    foreach ($st->fetchAll() as $file) $cases[(int) $file['case_id']]['files'][] = $file;
    $st = $pdo->prepare("SELECT d.case_id, r.label, d.nilai FROM case_data d JOIN requirements r ON r.id = d.requirement_id WHERE d.case_id IN ($placeholders) ORDER BY r.urutan, r.id");
    $st->execute($ids);
    foreach ($st->fetchAll() as $answer) $cases[(int) $answer['case_id']]['answers'][] = $answer;
}
$page_title = 'Tinjau pemeriksaan';
require __DIR__ . '/../../includes/header.php';
?>
<section class="card">
    <h1>Pemeriksaan untuk ditinjau</h1>
    <?php if (!$cases): ?><p>Tidak ada pemeriksaan yang menunggu peninjauan.</p><?php endif; ?>
    <?php foreach ($cases as $case): ?>
        <article class="card">
            <h2><?= e($case['judul']) ?></h2>
            <p><?= e($case['pemohon']) ?> · <?= e($case['jenis']) ?> · <?= status_badge($case['status']) ?></p>
            <h3>Jawaban pemohon</h3>
            <?php if (!$case['answers']): ?><p>Belum ada isian yang tersimpan.</p><?php else: ?>
                <dl><?php foreach ($case['answers'] as $answer): ?>
                    <dt><strong><?= e($answer['label']) ?></strong></dt>
                    <dd><?= nl2br(e($answer['nilai'] ?? '')) ?></dd>
                <?php endforeach; ?></dl>
            <?php endif; ?>
            <h3>Berkas</h3>
            <?php if (!$case['files']): ?><p>Tidak ada berkas yang diunggah.</p><?php else: ?>
                <ul><?php foreach ($case['files'] as $file): ?><li>
                    <a href="<?= e(base_url('modules/pemeriksaan/berkas.php?action=download&id=' . (int) $file['id'])) ?>"><?= e($file['nama_asli']) ?></a>
                </li><?php endforeach; ?></ul>
            <?php endif; ?>
            <form method="post"><?= csrf_field() ?>
                <input type="hidden" name="case_id" value="<?= e($case['id']) ?>">
                <div class="form-group"><label for="status-<?= e($case['id']) ?>">Hasil peninjauan</label>
                    <select id="status-<?= e($case['id']) ?>" name="status_baru" required>
                        <option value="">Pilih hasil</option><option value="selesai">Selesai</option><option value="belum_lengkap">Perlu dilengkapi</option>
                    </select>
                </div>
                <div class="form-group"><label for="catatan-<?= e($case['id']) ?>">Catatan untuk pemohon</label>
                    <textarea id="catatan-<?= e($case['id']) ?>" name="catatan" maxlength="5000"></textarea>
                </div>
                <button class="btn btn-primary" type="submit">Simpan hasil</button>
            </form>
        </article>
    <?php endforeach; ?>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
