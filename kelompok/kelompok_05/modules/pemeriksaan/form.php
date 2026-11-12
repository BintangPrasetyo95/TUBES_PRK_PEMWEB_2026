<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_role(['pengguna']);
$user = current_user();
$pdo = db();
$errors = [];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$case = null;
$types = $pdo->query('SELECT id, nama FROM document_types WHERE aktif = 1 ORDER BY nama')->fetchAll();
if ($id) {
    $st = $pdo->prepare('SELECT * FROM cases WHERE id = ? AND user_id = ?');
    $st->execute([$id, $user['id']]);
    $case = $st->fetch();
    if (!$case) {
        http_response_code(404);
        exit('Pemeriksaan tidak ditemukan.');
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!$case) {
        $judul = trim((string) ($_POST['judul'] ?? ''));
        $typeId = filter_var($_POST['document_type_id'] ?? null, FILTER_VALIDATE_INT);
        $validType = array_column($types, 'id');
        if ($judul === '' || mb_strlen($judul) > 200) $errors['judul'] = 'Judul wajib diisi (maksimal 200 karakter).';
        if (!$typeId || !in_array($typeId, array_map('intval', $validType), true)) $errors['jenis'] = 'Pilih jenis dokumen yang tersedia.';
        if (!$errors) {
            $st = $pdo->prepare("INSERT INTO cases (user_id, document_type_id, judul, status) VALUES (?, ?, ?, 'draft')");
            $st->execute([$user['id'], $typeId, $judul]);
            redirect('modules/pemeriksaan/form.php?id=' . $pdo->lastInsertId());
        }
    } elseif (in_array($case['status'], ['draft', 'belum_lengkap'], true)) {
        $st = $pdo->prepare('SELECT * FROM requirements WHERE document_type_id = ? ORDER BY urutan, id');
        $st->execute([$case['document_type_id']]);
        $requirements = $st->fetchAll();
        $save = $pdo->prepare('INSERT INTO case_data (case_id, requirement_id, nilai) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)');
        $submitted = [];
        $fields = is_array($_POST['field'] ?? null) ? $_POST['field'] : [];
        foreach ($requirements as $req) {
            if ($req['tipe_input'] === 'file') continue;
            if ($req['tipe_input'] !== 'checkbox' && isset($fields[$req['id']]) && !is_scalar($fields[$req['id']])) {
                $errors[$req['id']] = 'Isian tidak valid.';
                continue;
            }
            $value = $req['tipe_input'] === 'checkbox' ? (isset($fields[$req['id']]) ? '1' : '0') : trim((string) ($fields[$req['id']] ?? ''));
            if (mb_strlen($value) > 5000) $errors[$req['id']] = 'Isian terlalu panjang.';
            elseif ($req['tipe_input'] === 'number' && $value !== '' && !is_numeric($value)) $errors[$req['id']] = 'Masukkan angka yang valid.';
            elseif ($req['tipe_input'] === 'date' && $value !== '' && (($date = DateTime::createFromFormat('!Y-m-d', $value)) === false || $date->format('Y-m-d') !== $value)) $errors[$req['id']] = 'Masukkan tanggal yang valid.';
            else $submitted[$req['id']] = $value;
        }
        if (!$errors) {
            foreach ($submitted as $requirementId => $value) $save->execute([$id, $requirementId, $value]);
            $missing = false;
            foreach ($requirements as $req) {
                if (!$req['wajib']) continue;
                if ($req['tipe_input'] === 'file') {
                    $check = $pdo->prepare('SELECT COUNT(*) FROM case_files WHERE case_id = ? AND requirement_id = ?');
                    $check->execute([$id, $req['id']]);
                    $missing = $missing || !(int) $check->fetchColumn();
                } elseif ($req['tipe_input'] === 'checkbox') {
                    $missing = $missing || (($fields[$req['id']] ?? '') !== '1');
                } else {
                    $missing = $missing || trim((string) ($fields[$req['id']] ?? '')) === '';
                }
            }
            $submit = ($_POST['aksi'] ?? '') === 'submit';
            $status = $submit ? ($missing ? 'belum_lengkap' : 'siap_ditinjau') : 'draft';
            $pdo->prepare('UPDATE cases SET status = ? WHERE id = ? AND user_id = ?')->execute([$status, $id, $user['id']]);
            flash($missing && $submit ? 'error' : 'success', $missing && $submit ? 'Pemeriksaan disimpan, tetapi persyaratan wajib masih belum lengkap.' : ($submit ? 'Pemeriksaan siap ditinjau.' : 'Draft berhasil disimpan.'));
            redirect('modules/pemeriksaan/form.php?id=' . $id);
        }
    }
}

if (!$case) {
    $page_title = 'Buat pemeriksaan';
    require __DIR__ . '/../../includes/header.php';
    ?>
    <section class="card">
        <h1>Buat pemeriksaan</h1>
        <form method="post"><?= csrf_field() ?>
            <div class="form-group">
                <label for="judul">Judul pemeriksaan</label>
                <input id="judul" name="judul" maxlength="200" required value="<?= e($_POST['judul'] ?? '') ?>">
                <?php if (isset($errors['judul'])): ?><div class="field-error"><?= e($errors['judul']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="jenis">Jenis dokumen</label>
                <select id="jenis" name="document_type_id" required><option value="">Pilih jenis</option>
                    <?php foreach ($types as $type): ?><option value="<?= e($type['id']) ?>" <?= (string) $type['id'] === (string) ($_POST['document_type_id'] ?? '') ? 'selected' : '' ?>><?= e($type['nama']) ?></option><?php endforeach; ?>
                </select>
                <?php if (isset($errors['jenis'])): ?><div class="field-error"><?= e($errors['jenis']) ?></div><?php endif; ?>
            </div>
            <button class="btn btn-primary" type="submit">Buat draft</button>
        </form>
    </section>
    <?php require __DIR__ . '/../../includes/footer.php'; exit;
}

$st = $pdo->prepare('SELECT * FROM requirements WHERE document_type_id = ? ORDER BY urutan, id');
$st->execute([$case['document_type_id']]);
$requirements = $st->fetchAll();
$st = $pdo->prepare('SELECT requirement_id, nilai FROM case_data WHERE case_id = ?');
$st->execute([$id]);
$values = array_column($st->fetchAll(), 'nilai', 'requirement_id');
if (isset($submitted)) $values = array_replace($values, $submitted);
$st = $pdo->prepare('SELECT requirement_id, id, nama_asli FROM case_files WHERE case_id = ? ORDER BY id DESC');
$st->execute([$id]);
$files = $st->fetchAll();
$editable = in_array($case['status'], ['draft', 'belum_lengkap'], true);
$page_title = 'Checklist pemeriksaan';
require __DIR__ . '/../../includes/header.php';
?>
<section class="card">
    <h1><?= e($case['judul']) ?></h1>
    <p>Status: <?= status_badge($case['status']) ?> · Isi checklist dan unggah berkas yang diminta.</p>
    <?php if (!$editable): ?><p class="alert alert-info">Pemeriksaan sudah dikirim dan tidak dapat diubah.</p><?php endif; ?>
    <?php if ($editable): foreach ($requirements as $req): if ($req['tipe_input'] === 'file'): ?>
        <form id="upload-<?= e($req['id']) ?>" method="post" enctype="multipart/form-data" action="berkas.php">
            <?= csrf_field() ?><input type="hidden" name="case_id" value="<?= e($id) ?>">
            <input type="hidden" name="requirement_id" value="<?= e($req['id']) ?>">
        </form>
    <?php endif; endforeach; endif; ?>
    <form id="case-form" method="post"><?= csrf_field() ?>
        <?php foreach ($requirements as $req): $value = $values[$req['id']] ?? ''; ?>
            <div class="form-group">
                <label for="field-<?= e($req['id']) ?>"><?= e($req['label']) ?><?= $req['wajib'] ? ' *' : '' ?></label>
                <?php if ($req['penjelasan']): ?><small><?= e($req['penjelasan']) ?></small><?php endif; ?>
                <?php if ($req['tipe_input'] === 'file'): ?>
                    <?php foreach ($files as $file): if ((int) $file['requirement_id'] === (int) $req['id']): ?>
                        <p><a href="berkas.php?id=<?= e($file['id']) ?>"><?= e($file['nama_asli']) ?></a></p>
                    <?php endif; endforeach; ?>
                    <?php if ($editable): ?><input id="field-<?= e($req['id']) ?>" type="file" name="berkas" required form="upload-<?= e($req['id']) ?>">
                        <button class="btn btn-small" type="submit" form="upload-<?= e($req['id']) ?>">Unggah</button><?php endif; ?>
                <?php elseif ($req['tipe_input'] === 'textarea'): ?>
                    <textarea id="field-<?= e($req['id']) ?>" name="field[<?= e($req['id']) ?>]" <?= !$editable ? 'disabled' : '' ?>><?= e($value) ?></textarea>
                <?php elseif ($req['tipe_input'] === 'checkbox'): ?>
                    <input id="field-<?= e($req['id']) ?>" type="checkbox" name="field[<?= e($req['id']) ?>]" value="1" <?= $value === '1' ? 'checked' : '' ?> <?= !$editable ? 'disabled' : '' ?>>
                <?php else: ?>
                    <input id="field-<?= e($req['id']) ?>" type="<?= e($req['tipe_input']) ?>" name="field[<?= e($req['id']) ?>]" value="<?= e($value) ?>" <?= $req['tipe_input'] === 'number' ? 'step="any"' : '' ?> <?= !$editable ? 'disabled' : '' ?>>
                <?php endif; ?>
                <?php if (isset($errors[$req['id']])): ?><div class="field-error"><?= e($errors[$req['id']]) ?></div><?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if ($editable): ?><button class="btn" name="aksi" value="draft">Simpan draft</button>
            <button class="btn btn-primary" name="aksi" value="submit">Kirim untuk ditinjau</button><?php endif; ?>
    </form>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
