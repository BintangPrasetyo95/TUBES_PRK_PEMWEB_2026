<?php
// ============================================================================
// FITUR CONTOH: POLA STANDAR HALAMAN CEKHAK. Tiru pola ini untuk fitur kamu.
// SEMENTARA: folder modules/contoh/ dihapus sebelum PR final ke LabKom.
//
// Alur satu fitur: form -> validasi -> simpan ke database -> redirect -> tampil daftar.
// ============================================================================

// 1) SELALU di baris awal: muat bootstrap (koneksi DB, sesi, helper).
require_once __DIR__ . '/../../includes/bootstrap.php';

// 2) Batasi akses di SERVER. Tanpa baris ini, siapa pun yang tahu URL-nya bisa masuk.
require_role(['pengguna']);
$user = current_user();

// 3) Siapkan variabel untuk tampilan.
$errors = [];
$input = ['judul' => '', 'document_type_id' => ''];

// Data untuk dropdown (selalu pakai prepared statement bila ada input dari pengguna).
$types = db()->query('SELECT id, nama FROM document_types WHERE aktif = 1 ORDER BY nama')->fetchAll();

// 4) Tangani form (POST).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); // tolak POST tanpa token keamanan yang benar

    $input['judul'] = trim((string) ($_POST['judul'] ?? ''));
    $input['document_type_id'] = (string) ($_POST['document_type_id'] ?? '');

    // Validasi di server (jangan hanya mengandalkan atribut required di HTML).
    if ($input['judul'] === '') {
        $errors['judul'] = 'Judul wajib diisi.';
    } elseif (mb_strlen($input['judul']) > 200) {
        $errors['judul'] = 'Judul maksimal 200 karakter.';
    }

    $id_valid = array_map('intval', array_column($types, 'id'));
    if (!in_array((int) $input['document_type_id'], $id_valid, true)) {
        $errors['document_type_id'] = 'Pilih jenis dokumen.';
    }

    // Simpan hanya bila tidak ada error.
    if (!$errors) {
        $st = db()->prepare('INSERT INTO cases (user_id, document_type_id, judul, status) VALUES (?, ?, ?, ?)');
        $st->execute([$user['id'], (int) $input['document_type_id'], $input['judul'], 'draft']);
        $case_id = (int) db()->lastInsertId();

        audit_log('buat_pemeriksaan', 'cases', $case_id, 'Contoh pola: ' . $input['judul']);
        flash('success', 'Pemeriksaan contoh berhasil dibuat.');

        // Pola Post-Redirect-Get: redirect setelah simpan agar refresh tidak menyimpan dua kali.
        redirect('modules/contoh/contoh_pemeriksaan.php');
    }
}

// 5) Ambil data untuk ditampilkan (hanya milik pengguna ini).
$st = db()->prepare(
    'SELECT c.id, c.judul, c.status, c.created_at, t.nama AS jenis
     FROM cases c
     JOIN document_types t ON t.id = c.document_type_id
     WHERE c.user_id = ?
     ORDER BY c.id DESC'
);
$st->execute([$user['id']]);
$cases = $st->fetchAll();

// 6) Tampilan: header, isi, footer. SEMUA data pengguna dibungkus e().
$page_title = 'Contoh Pola Fitur';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <h1>Contoh: buat pemeriksaan</h1>
    <p class="muted">Halaman acuan pola. Fitur sebenarnya dibuat di modules/pemeriksaan/ oleh pemiliknya.</p>

    <form method="post">
        <?= csrf_field() ?>

        <div class="form-group <?= isset($errors['judul']) ? 'has-error' : '' ?>">
            <label for="judul">Judul pemeriksaan</label>
            <input type="text" id="judul" name="judul" value="<?= e($input['judul']) ?>">
            <?php if (isset($errors['judul'])): ?><div class="field-error"><?= e($errors['judul']) ?></div><?php endif; ?>
        </div>

        <div class="form-group <?= isset($errors['document_type_id']) ? 'has-error' : '' ?>">
            <label for="document_type_id">Jenis dokumen</label>
            <select id="document_type_id" name="document_type_id">
                <option value="">Pilih...</option>
                <?php foreach ($types as $t): ?>
                    <option value="<?= e($t['id']) ?>" <?= (string) $t['id'] === $input['document_type_id'] ? 'selected' : '' ?>>
                        <?= e($t['nama']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['document_type_id'])): ?><div class="field-error"><?= e($errors['document_type_id']) ?></div><?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary">Simpan</button>
    </form>
</div>

<div class="card">
    <h2>Pemeriksaan saya</h2>
    <?php if (!$cases): ?>
        <p class="muted">Belum ada pemeriksaan.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Judul</th><th>Jenis</th><th>Status</th><th>Dibuat</th></tr></thead>
                <tbody>
                <?php foreach ($cases as $c): ?>
                    <tr>
                        <td><?= e($c['judul']) ?></td>
                        <td><?= e($c['jenis']) ?></td>
                        <td><?= status_badge($c['status']) ?></td>
                        <td><?= e($c['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
