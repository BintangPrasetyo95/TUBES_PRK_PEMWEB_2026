<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/komponen.php';

$id_alat = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_alat === 0) {
    header('Location: ' . url('peminjaman/index.php'));
    exit;
}

$stmt = db()->prepare(
    'SELECT a.*, k.nama AS kategori
     FROM alat a
     JOIN kategori k ON k.id = a.kategori_id
     WHERE a.id = :id AND a.stok > 0'
);
$stmt->execute(['id' => $id_alat]);
$alat = $stmt->fetch();

if (!$alat) {
    set_flash('warning', 'Alat tidak ditemukan atau stok habis.');
    header('Location: ' . url('peminjaman/index.php'));
    exit;
}

$hariIni = date('Y-m-d');
$judul = 'Ajukan ' . $alat['nama'];
require __DIR__ . '/../includes/header.php';
?>
<?php page_head('Formulir pengajuan', 'Ajukan', 'peminjaman', 'Lengkapi jadwal, jumlah, dan keperluan. Pengajuan akan diproses admin dan dapat dipantau di halaman Riwayat.'); ?>

<div class="ajukan-layout">
    <aside class="ajukan-alat on-dark" aria-label="Detail alat yang diajukan">
        <?php foto_alat($alat); ?>
        <div class="ajukan-alat__isi">
            <span class="tag tag--neon"><?= e(JENIS_ALAT[$alat['jenis']] ?? $alat['jenis']) ?></span>
            <h2><?= e($alat['nama']) ?></h2>
            <p><?= e($alat['deskripsi'] ?: 'Belum ada deskripsi.') ?></p>
            <dl class="info-list">
                <div><dt>Kode</dt><dd><?= e($alat['kode']) ?></dd></div>
                <div><dt>Kategori</dt><dd><?= e($alat['kategori']) ?></dd></div>
                <div><dt>Lokasi</dt><dd><?= e($alat['lokasi'] ?: '—') ?></dd></div>
                <div><dt>Sisa stok</dt><dd class="sorot-neon"><?= (int) $alat['stok'] ?> unit</dd></div>
            </dl>
        </div>
    </aside>

    <div class="card">
        <div class="card-header">
            <h2>Data Peminjaman</h2>
        </div>
        <div class="card-body">
            <form action="<?= e(url('peminjaman/proses_ajukan.php')) ?>" method="POST">
                <input type="hidden" name="id_alat" value="<?= (int) $alat['id'] ?>">

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="tgl_pinjam" class="form-label">Tanggal Pinjam</label>
                        <input type="date" id="tgl_pinjam" name="tgl_pinjam" class="form-control" min="<?= e($hariIni) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="tgl_kembali" class="form-label">Tanggal Kembali</label>
                        <input type="date" id="tgl_kembali" name="tgl_kembali" class="form-control" min="<?= e($hariIni) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="jumlah" class="form-label">Jumlah Pinjam</label>
                    <input type="number" id="jumlah" name="jumlah" class="form-control" min="1" max="<?= (int) $alat['stok'] ?>" value="1" required aria-describedby="jumlah-hint">
                    <p id="jumlah-hint" class="form-hint">Maksimal <?= (int) $alat['stok'] ?> unit, tidak boleh melebihi stok tersedia.</p>
                </div>

                <div class="form-group">
                    <label for="keperluan" class="form-label">Keperluan / Keterangan</label>
                    <textarea id="keperluan" name="keperluan" class="form-control" rows="3" required placeholder="Contoh: Praktikum Jaringan Komputer, Tugas Akhir, dll."></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-neon btn-lg">Ajukan Peminjaman<?= ikon('panah', 'ikon ikon--sm') ?></button>
                    <a href="<?= e(url('peminjaman/index.php')) ?>" class="btn btn-outline btn-lg">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
