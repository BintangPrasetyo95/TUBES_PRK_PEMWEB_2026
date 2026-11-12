<?php

require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../config/koneksi.php';


$judul = 'Manajemen Alat';

$pesanError = '';
$alatEdit = null;

$folderUpload = __DIR__ . '/../../uploads/alat/';
require_once __DIR__ . '/proses.php';

if (isset($_GET['edit'])) {
    $idEdit = filter_var($_GET['edit'], FILTER_VALIDATE_INT);

    if ($idEdit && $idEdit > 0) {
        $alatEdit = get_alat_by_id($idEdit);

        if (!$alatEdit) {
            set_flash('warning', 'Data alat tidak ditemukan.');
            header('Location: ' . url('admin/alat/index.php'));
            exit;
        }
    }
}

$kategori = get_all_kategori();

$daftarAlat = get_all_alat();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="section-title">
    <div>
        <h1>Manajemen Alat</h1>
        <p class="text-muted mb-0">
            Kelola data alat dan ruang laboratorium.
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
        <h2><?= $alatEdit ? 'Edit Data Alat' : 'Tambah Data Alat' ?></h2>
    </div>

    <div class="card-body">
        <?php if (empty($kategori)): ?>
            <div class="alert alert-warning">
                Belum ada kategori. Tambahkan kategori terlebih dahulu.
            </div>
        <?php else: ?>

            <form
                method="POST"
                action="<?= e(url('admin/alat/index.php')) ?>"
                enctype="multipart/form-data"
            >
                <input
                    type="hidden"
                    name="aksi"
                    value="<?= $alatEdit ? 'edit' : 'tambah' ?>"
                >

                <?php if ($alatEdit): ?>
                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int) $alatEdit['id'] ?>"
                    >
                <?php endif; ?>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="kode" class="form-label">Kode</label>
                        <input
                            type="text"
                            id="kode"
                            name="kode"
                            class="form-control"
                            maxlength="30"
                            placeholder="Contoh: RL-001"
                            value="<?= e($alatEdit['kode'] ?? '') ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="nama" class="form-label">Nama</label>
                        <input
                            type="text"
                            id="nama"
                            name="nama"
                            class="form-control"
                            maxlength="150"
                            placeholder="Nama alat atau ruang"
                            value="<?= e($alatEdit['nama'] ?? '') ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="kategori_id" class="form-label">Kategori</label>
                        <select
                            id="kategori_id"
                            name="kategori_id"
                            class="form-control"
                            required
                        >
                            <option value="">-- Pilih Kategori --</option>

                            <?php foreach ($kategori as $item): ?>
                                <option
                                    value="<?= (int) $item['id'] ?>"
                                    <?= (int) ($alatEdit['kategori_id'] ?? 0) === (int) $item['id'] ? 'selected' : '' ?>
                                >
                                    <?= e($item['nama']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="jenis" class="form-label">Jenis</label>
                        <select
                            id="jenis"
                            name="jenis"
                            class="form-control"
                            required
                        >
                            <option value="alat" <?= ($alatEdit['jenis'] ?? 'alat') === 'alat' ? 'selected' : '' ?>>
                                Alat
                            </option>
                            <option value="ruang" <?= ($alatEdit['jenis'] ?? '') === 'ruang' ? 'selected' : '' ?>>
                                Ruang
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="stok" class="form-label">Stok</label>
                        <input
                            type="number"
                            id="stok"
                            name="stok"
                            class="form-control"
                            min="0"
                            value="<?= e((string) ($alatEdit['stok'] ?? '0')) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="lokasi" class="form-label">Lokasi</label>
                        <input
                            type="text"
                            id="lokasi"
                            name="lokasi"
                            class="form-control"
                            maxlength="100"
                            placeholder="Contoh: Lab Komputer"
                            value="<?= e($alatEdit['lokasi'] ?? '') ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="kondisi" class="form-label">Kondisi</label>
                        <select
                            id="kondisi"
                            name="kondisi"
                            class="form-control"
                            required
                        >
                            <option value="baik" <?= ($alatEdit['kondisi'] ?? 'baik') === 'baik' ? 'selected' : '' ?>>
                                Baik
                            </option>
                            <option value="rusak_ringan" <?= ($alatEdit['kondisi'] ?? '') === 'rusak_ringan' ? 'selected' : '' ?>>
                                Rusak Ringan
                            </option>
                            <option value="rusak_berat" <?= ($alatEdit['kondisi'] ?? '') === 'rusak_berat' ? 'selected' : '' ?>>
                                Rusak Berat
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="foto" class="form-label">Foto</label>
                        <input
                            type="file"
                            id="foto"
                            name="foto"
                            class="form-control"
                            accept=".jpg,.jpeg,.png,.webp"
                        >
                        <p class="form-hint">
                            Format JPG, PNG, atau WEBP. Maksimal 2 MB.
                        </p>

                        <?php if (!empty($alatEdit['foto'])): ?>
                            <p class="form-hint">Foto saat ini:</p>
                            <img
                                src="<?= e(url('uploads/alat/' . rawurlencode($alatEdit['foto']))) ?>"
                                alt="Foto alat"
                                style="width: 120px; margin-top: 8px; border-radius: 8px;"
                            >
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="deskripsi" class="form-label">Deskripsi</label>
                    <textarea
                        id="deskripsi"
                        name="deskripsi"
                        class="form-control"
                        placeholder="Deskripsi alat atau ruang"
                    ><?= e($alatEdit['deskripsi'] ?? '') ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?= $alatEdit ? 'Simpan Perubahan' : 'Tambah Data' ?>
                    </button>

                    <?php if ($alatEdit): ?>
                        <a
                            href="<?= e(url('admin/alat/index.php')) ?>"
                            class="btn btn-outline"
                        >
                            Batal
                        </a>
                    <?php endif; ?>
                </div>
            </form>

        <?php endif; ?>
    </div>
</div>

<div class="card mt-6">
    <div class="card-header">
        <h2>Daftar Alat dan Ruang</h2>
        <span class="badge badge-info">
            <?= count($daftarAlat) ?> data
        </span>
    </div>

    <div class="card-body">
        <?php if (empty($daftarAlat)): ?>

            <div class="empty-state">
                <p>Belum ada data alat atau ruang.</p>
            </div>

        <?php else: ?>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Foto</th>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Kategori</th>
                            <th>Jenis</th>
                            <th>Stok</th>
                            <th>Lokasi</th>
                            <th>Kondisi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($daftarAlat as $index => $alat): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>

                                <td>
                                    <?php if (!empty($alat['foto'])): ?>
                                        <img
                                            src="<?= e(url('uploads/alat/' . rawurlencode($alat['foto']))) ?>"
                                            alt="<?= e($alat['nama']) ?>"
                                            style="width: 60px; height: 45px; object-fit: cover; border-radius: 6px;"
                                        >
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <td><?= e($alat['kode']) ?></td>
                                <td><?= e($alat['nama']) ?></td>
                                <td><?= e($alat['nama_kategori']) ?></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?= e($alat['jenis']) ?>
                                    </span>
                                </td>
                                <td><?= (int) $alat['stok'] ?></td>
                                <td><?= e($alat['lokasi'] ?: '-') ?></td>
                                <td><?= e(str_replace('_', ' ', $alat['kondisi'])) ?></td>

                                <td>
                                    <div class="form-actions mt-0">
                                        <a
                                            href="<?= e(url('admin/alat/index.php?edit=' . $alat['id'])) ?>"
                                            class="btn btn-warning btn-sm"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            method="POST"
                                            action="<?= e(url('admin/alat/index.php')) ?>"
                                            onsubmit="return confirm('Yakin ingin menghapus data ini?')"
                                        >
                                            <input type="hidden" name="aksi" value="hapus">
                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int) $alat['id'] ?>"
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