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

$st = $pdo->prepare("SELECT c.id, c.judul, c.status, c.created_at, u.nama AS pemohon, t.nama AS jenis, f.id AS file_id, f.nama_asli FROM cases c JOIN users u ON u.id = c.user_id JOIN document_types t ON t.id = c.document_type_id LEFT JOIN case_files f ON f.case_id = c.id WHERE c.status IN ('siap_ditinjau','sedang_ditinjau') AND (c.reviewer_id IS NULL OR c.reviewer_id = ?) ORDER BY c.created_at ASC, c.id");
$st->execute([$user['id']]);
$cases = [];
foreach ($st->fetchAll() as $row) {
    $id = (int) $row['id'];
    if (!isset($cases[$id])) $cases[$id] = ['id' => $id, 'judul' => $row['judul'], 'status' => $row['status'], 'created_at' => $row['created_at'], 'pemohon' => $row['pemohon'], 'jenis' => $row['jenis'], 'files' => []];
    if ($row['file_id'] !== null) $cases[$id]['files'][] = ['id' => $row['file_id'], 'nama_asli' => $row['nama_asli']];
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
