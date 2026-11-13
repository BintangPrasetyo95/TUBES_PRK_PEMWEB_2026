<?php

require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../config/koneksi.php';

$judul = 'Manajemen Kategori';

$pesanError = '';
$kategoriEdit = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    try {
        if ($aksi === 'tambah') {
            $nama = trim($_POST['nama'] ?? '');
            $deskripsi = trim($_POST['deskripsi'] ?? '');

            if ($nama === '') {
                throw new RuntimeException('Nama kategori wajib diisi.');
            }

            if (mb_strlen($nama) > 100) {
                throw new RuntimeException('Nama kategori maksimal 100 karakter.');
            }

            $query = db()->prepare(
                'INSERT INTO kategori (nama, deskripsi)
                 VALUES (:nama, :deskripsi)'
            );

            $query->execute([
                'nama' => $nama,
                'deskripsi' => $deskripsi !== '' ? $deskripsi : null,
            ]);

            set_flash('success', 'Kategori berhasil ditambahkan.');

        } elseif ($aksi === 'edit') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            $nama = trim($_POST['nama'] ?? '');
            $deskripsi = trim($_POST['deskripsi'] ?? '');

            if (!$id || $id < 1) {
                throw new RuntimeException('ID kategori tidak valid.');
            }

            if ($nama === '') {
                throw new RuntimeException('Nama kategori wajib diisi.');
            }

            if (mb_strlen($nama) > 100) {
                throw new RuntimeException('Nama kategori maksimal 100 karakter.');
            }

            $cekKategori = db()->prepare(
                'SELECT COUNT(*) FROM kategori WHERE id = :id'
            );

            $cekKategori->execute(['id' => $id]);

            if ((int) $cekKategori->fetchColumn() === 0) {
                throw new RuntimeException('Kategori tidak ditemukan.');
            }

            $query = db()->prepare(
                'UPDATE kategori
                 SET nama = :nama, deskripsi = :deskripsi
                 WHERE id = :id'
            );

            $query->execute([
                'nama' => $nama,
                'deskripsi' => $deskripsi !== '' ? $deskripsi : null,
                'id' => $id,
            ]);

            set_flash('success', 'Kategori berhasil diperbarui.');

        } elseif ($aksi === 'hapus') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

            if (!$id || $id < 1) {
                throw new RuntimeException('ID kategori tidak valid.');
            }

            // Memastikan kategori tersedia
            $cekKategori = db()->prepare(
                'SELECT COUNT(*) FROM kategori WHERE id = :id'
            );

            $cekKategori->execute(['id' => $id]);

            if ((int) $cekKategori->fetchColumn() === 0) {
                throw new RuntimeException('Kategori tidak ditemukan.');
            }

            // Memeriksa apakah kategori masih digunakan oleh alat
            $cek = db()->prepare(
                'SELECT COUNT(*) FROM alat WHERE kategori_id = :id'
            );

            $cek->execute(['id' => $id]);

            if ((int) $cek->fetchColumn() > 0) {
                throw new RuntimeException(
                    'Kategori tidak dapat dihapus karena masih digunakan oleh data alat.'
                );
            }

            // Menghapus kategori
            $query = db()->prepare(
                'DELETE FROM kategori WHERE id = :id'
            );

            $query->execute(['id' => $id]);

            set_flash('success', 'Kategori berhasil dihapus.');
        
        } else {
            throw new RuntimeException('Aksi tidak dikenal.');
        }

        header('Location: ' . url('admin/kategori/index.php'));
        exit;

    } catch (PDOException $error) {
        if ((int) ($error->errorInfo[1] ?? 0) === 1062) {
            $pesanError = 'Nama kategori sudah digunakan. Silakan gunakan nama lain.';
        } else {
            $pesanError = 'Terjadi kesalahan saat memproses data kategori.';
        }
    } catch (RuntimeException $error) {
        $pesanError = $error->getMessage();
    } 
}

if (isset($_GET['edit'])) {
    $idEdit = filter_var($_GET['edit'], FILTER_VALIDATE_INT);

    if ($idEdit && $idEdit > 0) {
        $query = db()->prepare(
            'SELECT * FROM kategori WHERE id = :id'
        );

        $query->execute(['id' => $idEdit]);
        $kategoriEdit = $query->fetch();

        if (!$kategoriEdit) {
            set_flash('warning', 'Kategori tidak ditemukan.');
            header('Location: ' . url('admin/kategori/index.php'));
            exit;
        }
    }
}

$query = db()->query(
    'SELECT * FROM kategori ORDER BY id DESC'
);

$daftarKategori = $query->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="section-title">
    <div>
        <h1>Manajemen Kategori</h1>
        <p class="text-muted mb-0">
            Kelola kategori alat dan ruang laboratorium.
        </p>
    </div>
</div>

<?php if ($pesanError !== ''): ?>
    <div class="alert alert-danger">
        <?= e($pesanError) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2><?= $kategoriEdit ? 'Edit Kategori' : 'Tambah Kategori' ?></h2>
    </div>

    <div class="card-body">
        <form method="POST" action="<?= e(url('admin/kategori/index.php')) ?>">

            <input
                type="hidden"
                name="aksi"
                value="<?= $kategoriEdit ? 'edit' : 'tambah' ?>"
            >

            <?php if ($kategoriEdit): ?>
                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) $kategoriEdit['id'] ?>"
                >
            <?php endif; ?>

            <div class="form-group">
                <label for="nama" class="form-label">
                    Nama Kategori
                </label>

                <input
                    type="text"
                    id="nama"
                    name="nama"
                    class="form-control"
                    maxlength="100"
                    placeholder="Masukkan nama kategori"
                    value="<?= e($kategoriEdit['nama'] ?? '') ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="deskripsi" class="form-label">
                    Deskripsi
                </label>

                <textarea
                    id="deskripsi"
                    name="deskripsi"
                    class="form-control"
                    placeholder="Masukkan deskripsi kategori"
                ><?= e($kategoriEdit['deskripsi'] ?? '') ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= $kategoriEdit ? 'Simpan Perubahan' : 'Tambah Kategori' ?>
                </button>

                <?php if ($kategoriEdit): ?>
                    <a
                        href="<?= e(url('admin/kategori/index.php')) ?>"
                        class="btn btn-outline"
                    >
                        Batal
                    </a>
                <?php endif; ?>
            </div>

        </form>
    </div>
</div>

<div class="card mt-6">
    <div class="card-header">
        <h2>Daftar Kategori</h2>
        <span class="badge badge-info">
            <?= count($daftarKategori) ?> kategori
        </span>
    </div>

    <div class="card-body">
        <?php if (empty($daftarKategori)): ?>

            <div class="empty-state">
                <p>Belum ada data kategori.</p>
            </div>

        <?php else: ?>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nama Kategori</th>
                            <th>Deskripsi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($daftarKategori as $index => $kategori): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>

                                <td>
                                    <strong><?= e($kategori['nama']) ?></strong>
                                </td>

                                <td>
                                    <?= e($kategori['deskripsi'] ?: '-') ?>
                                </td>

                                <td>
                                    <div class="form-actions mt-0">
                                        <a
                                            href="<?= e(url('admin/kategori/index.php?edit=' . $kategori['id'])) ?>"
                                            class="btn btn-warning btn-sm"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            method="POST"
                                            action="<?= e(url('admin/kategori/index.php')) ?>"
                                            onsubmit="return confirm('Yakin ingin menghapus kategori ini?')"
                                        >
                                            <input
                                                type="hidden"
                                                name="aksi"
                                                value="hapus"
                                            >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int) $kategori['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-danger btn-sm"
                                            >
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>