<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../config/database.php';

$errors = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/** Read a trimmed string value from the POST body. */
function mata_pelajaran_post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/** Send a flash message and return to the subject list (Post/Redirect/Get). */
function mata_pelajaran_redirect_with_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header('Location: mata-pelajaran.php');
    exit;
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
if ($editId < 0) {
    $editId = 0;
}

// ---------------------------------------------------------------
// Handle POST actions (save and toggle). CSRF is mandatory.
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['_csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Permintaan tidak valid.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'toggle') {
        $mapelId = (int) ($_POST['mata_pelajaran_id'] ?? 0);

        try {
            $statement = $pdo->prepare('SELECT is_active FROM mata_pelajaran WHERE id = :id');
            $statement->execute(['id' => $mapelId]);
            $target = $statement->fetch();

            if (!is_array($target)) {
                mata_pelajaran_redirect_with_flash('error', 'Data mata pelajaran tidak ditemukan.');
            }

            $newStatus = !(bool) $target['is_active'];
            $statement = $pdo->prepare('UPDATE mata_pelajaran SET is_active = :active WHERE id = :id');
            $statement->execute(['active' => $newStatus ? 1 : 0, 'id' => $mapelId]);

            mata_pelajaran_redirect_with_flash(
                'success',
                $newStatus ? 'Mata pelajaran berhasil diaktifkan.' : 'Mata pelajaran berhasil dinonaktifkan.'
            );
        } catch (PDOException $exception) {
            error_log('ATTENDARA toggle mata pelajaran failed: ' . $exception->getMessage());
            mata_pelajaran_redirect_with_flash('error', 'Status mata pelajaran gagal diubah.');
        }
    }

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $kode = mata_pelajaran_post_string('kode');
        $nama = mata_pelajaran_post_string('nama');

        // Server-side validation.
        if ($kode === '') {
            $errors[] = 'Kode mata pelajaran wajib diisi.';
        } elseif (mb_strlen($kode) > 30) {
            $errors[] = 'Kode mata pelajaran maksimal 30 karakter.';
        }

        if ($nama === '') {
            $errors[] = 'Nama mata pelajaran wajib diisi.';
        } elseif (mb_strlen($nama) > 100) {
            $errors[] = 'Nama mata pelajaran maksimal 100 karakter.';
        }

        // Uniqueness check with a friendly message.
        if ($errors === []) {
            $sql = 'SELECT COUNT(*) FROM mata_pelajaran WHERE kode = :kode';
            $params = ['kode' => $kode];
            if ($id > 0) {
                $sql .= ' AND id <> :id';
                $params['id'] = $id;
            }
            $statement = $pdo->prepare($sql);
            $statement->execute($params);
            if ((int) $statement->fetchColumn() > 0) {
                $errors[] = 'Kode mata pelajaran sudah digunakan.';
            }
        }

        if ($errors === []) {
            $sql = 'SELECT COUNT(*) FROM mata_pelajaran WHERE nama = :nama';
            $params = ['nama' => $nama];
            if ($id > 0) {
                $sql .= ' AND id <> :id';
                $params['id'] = $id;
            }
            $statement = $pdo->prepare($sql);
            $statement->execute($params);
            if ((int) $statement->fetchColumn() > 0) {
                $errors[] = 'Nama mata pelajaran sudah digunakan.';
            }
        }

        if ($errors === []) {
            try {
                if ($id === 0) {
                    $statement = $pdo->prepare(
                        'INSERT INTO mata_pelajaran (kode, nama, is_active)
                         VALUES (:kode, :nama, TRUE)'
                    );
                    $statement->execute([
                        'kode' => $kode,
                        'nama' => $nama,
                    ]);
                    mata_pelajaran_redirect_with_flash('success', 'Mata pelajaran berhasil ditambahkan.');
                } else {
                    $statement = $pdo->prepare(
                        'UPDATE mata_pelajaran
                         SET kode = :kode, nama = :nama
                         WHERE id = :id'
                    );
                    $statement->execute([
                        'kode' => $kode,
                        'nama' => $nama,
                        'id' => $id,
                    ]);
                    mata_pelajaran_redirect_with_flash('success', 'Data mata pelajaran berhasil diperbarui.');
                }
            } catch (PDOException $exception) {
                error_log('ATTENDARA save mata pelajaran failed: ' . $exception->getMessage());
                $errors[] = $exception->getCode() === '23000'
                    ? 'Kode atau nama mata pelajaran sudah digunakan.'
                    : 'Mata pelajaran gagal disimpan.';
            }
        }
    }
}

// ---------------------------------------------------------------
// Load data for the edit form and the table.
// ---------------------------------------------------------------
$editMapel = null;
if ($editId > 0) {
    $statement = $pdo->prepare('SELECT id, kode, nama, is_active FROM mata_pelajaran WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $editId]);
    $editMapel = $statement->fetch() ?: null;
}

$statement = $pdo->query(
    'SELECT id, kode, nama, is_active
     FROM mata_pelajaran
     ORDER BY nama'
);
$mataPelajaranList = $statement->fetchAll();

// Repopulate the form from the last POST when validation failed.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save' && $errors !== []) {
    $formValues = [
        'id' => (int) ($_POST['id'] ?? 0),
        'kode' => mata_pelajaran_post_string('kode'),
        'nama' => mata_pelajaran_post_string('nama'),
    ];
} elseif ($editMapel !== null) {
    $formValues = [
        'id' => (int) $editMapel['id'],
        'kode' => (string) $editMapel['kode'],
        'nama' => (string) $editMapel['nama'],
    ];
} else {
    $formValues = [
        'id' => 0,
        'kode' => '',
        'nama' => '',
    ];
}

$isEditing = $formValues['id'] > 0;

admin_page_start($isEditing ? 'Edit Mata Pelajaran' : 'Data Mata Pelajaran', 'mata-pelajaran.php');
?>
<section class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-[#20392a]">Daftar Mata Pelajaran</h2>
            <p class="mt-1 text-sm text-slate-600">Daftar mata pelajaran yang tersedia untuk penugasan guru.</p>
        </div>
        <?php attendara_ui_button('Tambah Mata Pelajaran', 'mata-pelajaran.php'); ?>
    </div>

    <?php if ($flash !== null): ?>
        <?php $flashType = ($flash['type'] ?? 'success') === 'error' ? 'error' : 'success'; ?>
        <p role="status" class="rounded-xl px-4 py-3 text-sm <?= $flashType === 'error' ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700' ?>">
            <?= h((string) ($flash['message'] ?? '')) ?>
        </p>
    <?php endif; ?>

    <div class="overflow-x-auto rounded-2xl border border-[#e2e9e2] bg-white">
        <table data-admin-table class="min-w-full divide-y divide-[#edf1ec] text-left text-sm">
            <thead class="bg-[#f3f6f1] text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">No</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Kode</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Nama</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Status</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#edf1ec] text-slate-700">
                <?php if ($mataPelajaranList === []): ?>
                    <tr><td colspan="5" class="px-4 py-9 text-center text-slate-500">Belum ada data mata pelajaran.</td></tr>
                <?php else: ?>
                    <?php foreach ($mataPelajaranList as $index => $mapel): ?>
                        <tr class="hover:bg-[#f8faf7]">
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) ($index + 1)) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) $mapel['kode']) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) $mapel['nama']) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5">
                                <?php if ((bool) $mapel['is_active']): ?>
                                    <span class="inline-flex rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">Aktif</span>
                                <?php else: ?>
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5">
                                <div class="flex items-center gap-2">
                                    <a href="mata-pelajaran.php?edit=<?= h((string) $mapel['id']) ?>" class="rounded-md px-2 py-1 text-xs font-semibold text-[#28583b] hover:bg-[#edf4ec]">Edit</a>
                                    <form method="post" action="mata-pelajaran.php" class="inline">
                                        <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="mata_pelajaran_id" value="<?= h((string) $mapel['id']) ?>">
                                        <button type="submit" class="rounded-md px-2 py-1 text-xs font-semibold <?= (bool) $mapel['is_active'] ? 'text-red-700 hover:bg-red-50' : 'text-[#28583b] hover:bg-[#edf4ec]' ?>">
                                            <?= (bool) $mapel['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section id="form-mata-pelajaran" class="rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:p-6">
    <h2 class="text-base font-semibold text-[#20392a]"><?= $isEditing ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran' ?></h2>
    <p class="mt-1 text-sm text-slate-600">
        <?= $isEditing ? 'Perbarui data mata pelajaran.' : 'Tambahkan mata pelajaran baru. Kode harus unik, contoh: MAT, BIN, IPA.' ?>
    </p>

    <?php if ($errors !== []): ?>
        <ul class="mt-4 space-y-1 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            <?php foreach ($errors as $error): ?>
                <li>• <?= h($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="mata-pelajaran.php" class="mt-4 grid gap-4 sm:grid-cols-2">
        <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= h((string) $formValues['id']) ?>">

        <?php
        attendara_ui_input('Kode', 'kode', $formValues['kode'], 'text', 'Contoh: MAT');
        attendara_ui_input('Nama', 'nama', $formValues['nama'], 'text', 'Contoh: Matematika');
        ?>

        <div class="flex items-center gap-3 sm:col-span-2">
            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1e5638] px-4 py-2 text-sm font-semibold text-white hover:bg-[#17452d] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#376c4d] focus-visible:ring-offset-2">
                <?= $isEditing ? 'Simpan Perubahan' : 'Simpan Mata Pelajaran' ?>
            </button>
            <?php if ($isEditing): ?>
                <a href="mata-pelajaran.php" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#284b35] hover:bg-[#f2f6f2]">Batal</a>
            <?php endif; ?>
        </div>
    </form>
</section>
<?php admin_page_end(); ?>
