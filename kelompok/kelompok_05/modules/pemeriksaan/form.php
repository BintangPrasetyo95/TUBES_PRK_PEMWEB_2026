<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/_lib.php';
require_role(['pengguna']);
$user = current_user();
$pdo = db();
$errors = [];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: (filter_input(INPUT_GET, 'case', FILTER_VALIDATE_INT) ?: 0);
$case = null;
$sedangKirim = false;

$types = $pdo->query('SELECT id, nama, deskripsi FROM document_types WHERE aktif = 1 ORDER BY nama')->fetchAll();
$jumlah = [];
foreach ($pdo->query('SELECT document_type_id, COUNT(*) AS total, SUM(wajib) AS wajib FROM requirements GROUP BY document_type_id')->fetchAll() as $j) {
    $jumlah[(int) $j['document_type_id']] = $j;
}

if ($id) {
    $st = $pdo->prepare('SELECT c.*, t.nama AS jenis_nama FROM cases c JOIN document_types t ON t.id = c.document_type_id WHERE c.id = ? AND c.user_id = ?');
    $st->execute([$id, $user['id']]);
    $case = $st->fetch();
    if (!$case) {
        http_response_code(404);
        $page_title = 'Tidak ditemukan';
        require __DIR__ . '/../../includes/header.php';
        echo '<div class="card"><h1>Pemeriksaan tidak ditemukan</h1><p>Pemeriksaan ini tidak ada atau bukan milik kamu.</p><a class="btn" href="' . e(base_url('modules/pemeriksaan/buat.php')) . '">Buat pemeriksaan baru</a></div>';
        require __DIR__ . '/../../includes/footer.php';
        exit;
    }
}
$editable = $case && in_array($case['status'], ['draft', 'belum_lengkap'], true);
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = (string) ($_POST['aksi'] ?? '');

    if (!$case) {
        $judul = trim((string) ($_POST['judul'] ?? ''));
        $typeId = filter_var($_POST['document_type_id'] ?? null, FILTER_VALIDATE_INT);
        if ($judul === '' || mb_strlen($judul) > 200) {
            $errors['judul'] = 'Judul wajib diisi (maksimal 200 karakter).';
        }
        if (!$typeId || !in_array($typeId, array_map('intval', array_column($types, 'id')), true)) {
            $errors['jenis'] = 'Pilih jenis dokumen yang tersedia.';
        }
        if (!$errors) {
            $pdo->prepare("INSERT INTO cases (user_id, document_type_id, judul, status) VALUES (?, ?, ?, 'draft')")->execute([$user['id'], $typeId, $judul]);
            $baru = (int) $pdo->lastInsertId();
            audit_log('buat_pemeriksaan', 'cases', $baru, mb_substr($judul, 0, 100));
            flash('success', 'Draft dibuat. Lengkapi checklist di bawah ini.');
            redirect('modules/pemeriksaan/form.php?id=' . $baru);
        }
    } elseif ($aksi === 'hapus_draft') {
        if (!$editable) {
            flash('error', 'Hanya draft yang belum dikirim yang dapat dihapus.');
            redirect('modules/pemeriksaan/form.php?id=' . $id);
        }
        $fs = $pdo->prepare('SELECT nama_tersimpan FROM case_files WHERE case_id = ?');
        $fs->execute([$id]);
        $namaBerkas = $fs->fetchAll(PDO::FETCH_COLUMN);
        $pdo->prepare('DELETE FROM cases WHERE id = ? AND user_id = ?')->execute([$id, $user['id']]);
        foreach ($namaBerkas as $nb) {
            berkas_hapus_fisik((string) $nb);
        }
        audit_log('hapus_pemeriksaan', 'cases', $id, mb_substr((string) $case['judul'], 0, 100));
        flash('success', 'Draft pemeriksaan dihapus.');
        redirect('modules/pemeriksaan/buat.php');
    } elseif ($aksi === 'tarik') {
        $st = $pdo->prepare("UPDATE cases SET status = 'draft' WHERE id = ? AND user_id = ? AND status = 'siap_ditinjau'");
        $st->execute([$id, $user['id']]);
        if ($st->rowCount() === 1) {
            audit_log('tarik_pemeriksaan', 'cases', $id, 'Kembali ke draft');
            flash('success', 'Pemeriksaan ditarik kembali menjadi draft. Kamu bisa mengubahnya lagi.');
        } else {
            flash('error', 'Pemeriksaan tidak dapat ditarik karena sudah mulai ditinjau.');
        }
        redirect('modules/pemeriksaan/form.php?id=' . $id);
    } elseif ($editable && in_array($aksi, ['draft', 'submit'], true)) {
        $requirements = ambil_persyaratan($pdo, (int) $case['document_type_id']);
        $fields = is_array($_POST['field'] ?? null) ? $_POST['field'] : [];
        $hasil = periksa_isian($requirements, $fields);
        $errors = $hasil['errors'];
        if (!$errors) {
            simpan_isian($pdo, $id, $hasil['valid']);
            $values = $hasil['valid'];
            $cl = hitung_checklist($requirements, $values, ambil_berkas_per_persyaratan($pdo, $id));
            $kurang = $cl['wajib_total'] - $cl['wajib_terpenuhi'];
            $submit = $aksi === 'submit';
            $status = $submit ? ($kurang > 0 ? 'belum_lengkap' : 'siap_ditinjau') : 'draft';
            $pdo->prepare('UPDATE cases SET status = ? WHERE id = ? AND user_id = ?')->execute([$status, $id, $user['id']]);
            audit_log($submit ? 'kirim_pemeriksaan' : 'simpan_draft', 'cases', $id, 'Status: ' . $status . ' (' . $cl['wajib_terpenuhi'] . '/' . $cl['wajib_total'] . ' wajib)');
            if ($submit && $kurang > 0) {
                flash('error', 'Pemeriksaan disimpan, tetapi ' . $kurang . ' persyaratan wajib masih belum lengkap.');
            } else {
                flash('success', $submit ? 'Pemeriksaan siap ditinjau.' : 'Draft berhasil disimpan.');
            }
            redirect('modules/pemeriksaan/form.php?id=' . $id);
        }
        $old = $fields;
        $sedangKirim = true;
    }
}

if (!$case) {
    $page_title = 'Buat pemeriksaan';
    $extra_css = ['assets/pemeriksaan/pemeriksaan.css'];
    $extra_js = ['assets/pemeriksaan/pemeriksaan.js'];
    require __DIR__ . '/../../includes/header.php';
    ?>
    <section class="card">
        <h1>Buat pemeriksaan</h1>
        <p class="muted">Pilih jenis dokumen yang ingin diperiksa kelengkapan administratifnya. Kamu bisa menyimpan sebagai draft dan melanjutkannya nanti.</p>
        <form method="post"><?= csrf_field() ?>
            <div class="form-group <?= isset($errors['judul']) ? 'has-error' : '' ?>">
                <label for="judul">Judul pemeriksaan</label>
                <input id="judul" name="judul" maxlength="200" required value="<?= e($_POST['judul'] ?? '') ?>" placeholder="Contoh: Surat kuasa pengurusan sertifikat">
                <?php if (isset($errors['judul'])): ?><div class="field-error"><?= e($errors['judul']) ?></div><?php endif; ?>
            </div>
            <div class="form-group <?= isset($errors['jenis']) ? 'has-error' : '' ?>">
                <label for="jenis">Jenis dokumen</label>
                <select id="jenis" name="document_type_id" required data-jenis-select>
                    <option value="">Pilih jenis</option>
                    <?php foreach ($types as $type): $j = $jumlah[(int) $type['id']] ?? null; ?>
                        <option value="<?= e($type['id']) ?>"
                                data-deskripsi="<?= e($type['deskripsi'] ?? '') ?>"
                                data-total="<?= e($j['total'] ?? 0) ?>" data-wajib="<?= e($j['wajib'] ?? 0) ?>"
                                <?= (string) $type['id'] === (string) ($_POST['document_type_id'] ?? '') ? 'selected' : '' ?>><?= e($type['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['jenis'])): ?><div class="field-error"><?= e($errors['jenis']) ?></div><?php endif; ?>
                <div class="jenis-info" data-jenis-info hidden></div>
            </div>
            <button class="btn btn-primary" type="submit">Buat draft</button>
        </form>
    </section>
    <?php require __DIR__ . '/../../includes/footer.php'; exit;
}

$requirements = ambil_persyaratan($pdo, (int) $case['document_type_id']);
$st = $pdo->prepare('SELECT requirement_id, nilai FROM case_data WHERE case_id = ?');
$st->execute([$id]);
$values = array_column($st->fetchAll(), 'nilai', 'requirement_id');
if ($sedangKirim) {
    foreach ($requirements as $req) {
        $rid = (int) $req['id'];
        if ($req['tipe_input'] === 'checkbox') {
            $values[$rid] = isset($old[$rid]) ? '1' : '0';
        } elseif ($req['tipe_input'] !== 'file' && isset($old[$rid]) && is_scalar($old[$rid])) {
            $values[$rid] = (string) $old[$rid];
        }
    }
}
$berkas = ambil_berkas_per_persyaratan($pdo, $id);
$cl = hitung_checklist($requirements, $values, $berkas);
$belum = [];
foreach ($requirements as $req) {
    if (($cl['item'][(int) $req['id']] ?? '') === 'belum') {
        $belum[] = $req;
    }
}

$st = $pdo->prepare('SELECT r.id, r.catatan, r.status_baru, r.dibaca_pengguna, r.created_at, u.nama AS reviewer_nama
                     FROM review_logs r JOIN users u ON u.id = r.reviewer_id WHERE r.case_id = ? ORDER BY r.id DESC LIMIT 10');
$st->execute([$id]);
$catatanReviewer = $st->fetchAll();

$aturanHint = ['tanggal' => 'Format: YYYY-MM-DD', 'angka' => 'Hanya angka', 'nomor' => 'Nomor 8 sampai 15 digit', 'email' => 'Contoh: nama@contoh.id'];
$page_title = 'Checklist pemeriksaan';
$extra_css = ['assets/pemeriksaan/pemeriksaan.css'];
$extra_js = ['assets/pemeriksaan/pemeriksaan.js'];
require __DIR__ . '/../../includes/header.php';
?>
<section class="card">
    <h1><?= e($case['judul']) ?></h1>
    <p>Jenis: <strong><?= e($case['jenis_nama']) ?></strong> · Status: <?= status_badge($case['status']) ?></p>
    <?php if ($case['status'] === 'siap_ditinjau'): ?>
        <p class="alert alert-info">Pemeriksaan sudah dikirim dan menunggu reviewer. Kamu masih bisa menariknya kembali menjadi draft selama belum ditinjau.</p>
    <?php elseif (!$editable): ?>
        <p class="alert alert-info">Pemeriksaan sedang atau sudah ditinjau dan tidak dapat diubah.</p>
    <?php endif; ?>

    <div class="checklist-ringkas" data-checklist-ringkas data-total="<?= e($cl['wajib_total']) ?>">
        <div class="checklist-judul">
            <strong>Kelengkapan persyaratan wajib</strong>
            <span data-progress-teks><?= e($cl['wajib_terpenuhi']) ?> dari <?= e($cl['wajib_total']) ?> terpenuhi (<?= e($cl['persen']) ?>%)</span>
        </div>
        <div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= e($cl['persen']) ?>"><span data-progress-bar style="width: <?= e($cl['persen']) ?>%"></span></div>
        <?php if ($editable): ?>
            <div data-belum-wadah <?= $belum ? '' : 'hidden' ?>>
                <small class="muted">Masih perlu dilengkapi:</small>
                <ul class="daftar-belum" data-belum-daftar>
                    <?php foreach ($belum as $b): ?><li><a href="#persyaratan-<?= e($b['id']) ?>"><?= e($b['label']) ?></a></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($catatanReviewer): ?>
<section class="card notif-reviewer" id="catatan-reviewer">
    <h2>Catatan dari reviewer</h2>
    <?php foreach ($catatanReviewer as $c): ?>
        <div class="notif-item <?= (int) $c['dibaca_pengguna'] ? '' : 'notif-baru' ?>">
            <div class="notif-meta">
                <?php if (!(int) $c['dibaca_pengguna']): ?><span class="badge badge-warn">Baru</span><?php endif; ?>
                <strong><?= e($c['reviewer_nama']) ?></strong> · <?= e($c['created_at']) ?>
                <?php if ($c['status_baru']): ?> · menjadi <?= status_badge((string) $c['status_baru']) ?><?php endif; ?>
            </div>
            <?php if (trim((string) $c['catatan']) !== ''): ?><p><?= nl2br(e($c['catatan'])) ?></p><?php endif; ?>
        </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<section class="card">
    <?php if ($editable): foreach ($requirements as $req): ?>
        <?php if ($req['tipe_input'] === 'file'): ?>
        <form id="upload-<?= e($req['id']) ?>" method="post" enctype="multipart/form-data" action="berkas.php?case_id=<?= e($id) ?>" data-upload-form>
            <?= csrf_field() ?><input type="hidden" name="case_id" value="<?= e($id) ?>">
            <input type="hidden" name="requirement_id" value="<?= e($req['id']) ?>">
        </form>
        <?php foreach ($berkas[(int) $req['id']] ?? [] as $b): ?>
        <form id="hapus-<?= e($b['id']) ?>" method="post" action="berkas.php?case_id=<?= e($id) ?>" data-konfirmasi="Hapus berkas &quot;<?= e($b['nama_asli']) ?>&quot;?">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="file_id" value="<?= e($b['id']) ?>">
            <input type="hidden" name="case_id" value="<?= e($id) ?>">
        </form>
        <?php endforeach; endif; ?>
    <?php endforeach; endif; ?>

    <form id="case-form" method="post" novalidate data-case-form>
        <?= csrf_field() ?>
        <?php foreach ($requirements as $req):
            $rid = (int) $req['id'];
            $value = $values[$rid] ?? '';
            $statusItem = $cl['item'][$rid] ?? 'opsional';
            $aturan = (string) ($req['aturan_format'] ?? '');
            ?>
            <div class="form-group persyaratan <?= isset($errors[$rid]) ? 'has-error' : '' ?>" id="persyaratan-<?= e($rid) ?>"
                 data-persyaratan data-tipe="<?= e($req['tipe_input']) ?>" data-wajib="<?= $req['wajib'] ? '1' : '0' ?>"
                 data-aturan="<?= e($aturan) ?>" data-terisi="<?= $statusItem === 'terpenuhi' ? '1' : '0' ?>" data-label="<?= e($req['label']) ?>">
                <label for="field-<?= e($rid) ?>">
                    <span class="penanda penanda-<?= e($statusItem) ?>" data-penanda aria-hidden="true"><?= $statusItem === 'terpenuhi' ? '✓' : '○' ?></span>
                    <?= e($req['label']) ?><?= $req['wajib'] ? ' <span class="wajib" title="Wajib">*</span>' : ' <span class="muted">(opsional)</span>' ?>
                </label>
                <?php if ($req['penjelasan']): ?><small class="muted"><?= e($req['penjelasan']) ?></small><?php endif; ?>
                <?php if (isset($aturanHint[$aturan]) && $req['tipe_input'] !== 'date'): ?><small class="hint"><?= e($aturanHint[$aturan]) ?></small><?php endif; ?>

                <?php if ($req['tipe_input'] === 'file'): ?>
                    <?php foreach ($berkas[$rid] ?? [] as $b): ?>
                        <div class="berkas-item">
                            <span class="berkas-nama"><?= e($b['nama_asli']) ?> <small class="muted">(<?= e(ukuran_label((int) $b['ukuran'])) ?>)</small></span>
                            <a class="btn btn-small" href="berkas.php?id=<?= e($b['id']) ?>&amp;lihat=1" target="_blank" rel="noopener">Lihat</a>
                            <a class="btn btn-small" href="berkas.php?id=<?= e($b['id']) ?>">Unduh</a>
                            <?php if ($editable): ?><button class="btn btn-small btn-danger" type="submit" form="hapus-<?= e($b['id']) ?>">Hapus</button><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($editable): ?>
                        <div class="berkas-unggah">
                            <input id="field-<?= e($rid) ?>" type="file" name="berkas" required form="upload-<?= e($rid) ?>" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" data-berkas-input>
                            <button class="btn btn-small" type="submit" form="upload-<?= e($rid) ?>"><?= !empty($berkas[$rid]) ? 'Ganti berkas' : 'Unggah' ?></button>
                        </div>
                        <small class="muted">PDF, JPG, PNG, WebP, atau Word. Maksimal 5 MB. Unggahan baru menggantikan berkas lama.</small>
                    <?php elseif (empty($berkas[$rid])): ?><small class="muted">Tidak ada berkas.</small><?php endif; ?>
                <?php elseif ($req['tipe_input'] === 'textarea'): ?>
                    <textarea id="field-<?= e($rid) ?>" name="field[<?= e($rid) ?>]" maxlength="5000" <?= !$editable ? 'disabled' : '' ?>><?= e($value) ?></textarea>
                <?php elseif ($req['tipe_input'] === 'checkbox'): ?>
                    <label class="cek-baris" for="field-<?= e($rid) ?>"><input id="field-<?= e($rid) ?>" type="checkbox" name="field[<?= e($rid) ?>]" value="1" <?= $value === '1' ? 'checked' : '' ?> <?= !$editable ? 'disabled' : '' ?>> Ya, sudah terpenuhi</label>
                <?php else: ?>
                    <input id="field-<?= e($rid) ?>" type="<?= e($req['tipe_input'] === 'number' ? 'text' : $req['tipe_input']) ?>" name="field[<?= e($rid) ?>]" value="<?= e($value) ?>"
                           <?= $req['tipe_input'] === 'number' ? 'inputmode="decimal"' : '' ?> maxlength="5000" <?= !$editable ? 'disabled' : '' ?>>
                <?php endif; ?>
                <?php if (isset($errors[$rid])): ?><div class="field-error"><?= e($errors[$rid]) ?></div><?php endif; ?>

                <?php if ($req['sumber'] || $req['tanggal_pembaruan']): ?>
                    <details class="sumber">
                        <summary>Dasar persyaratan <?= status_badge((string) $req['status_verifikasi']) ?></summary>
                        <?php if ($req['sumber']): ?><div>Sumber: <?= e($req['sumber']) ?></div><?php endif; ?>
                        <?php if ($req['tanggal_pembaruan']): ?><div>Terakhir diperbarui: <?= e($req['tanggal_pembaruan']) ?></div><?php endif; ?>
                    </details>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if ($editable): ?>
            <div class="aksi-form">
                <button class="btn" name="aksi" value="draft" type="submit">Simpan draft</button>
                <button class="btn btn-primary" name="aksi" value="submit" type="submit">Kirim untuk ditinjau</button>
            </div>
        <?php endif; ?>
    </form>

    <?php if ($case['status'] === 'siap_ditinjau'): ?>
        <form method="post" class="aksi-form" data-konfirmasi="Tarik pemeriksaan kembali menjadi draft?"><?= csrf_field() ?>
            <button class="btn" name="aksi" value="tarik" type="submit">Tarik kembali ke draft</button>
        </form>
    <?php elseif ($editable): ?>
        <form method="post" class="aksi-bahaya" data-konfirmasi="Hapus draft ini beserta semua berkas yang sudah diunggah? Tindakan ini tidak dapat dibatalkan."><?= csrf_field() ?>
            <button class="btn btn-danger btn-small" name="aksi" value="hapus_draft" type="submit">Hapus draft</button>
        </form>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
