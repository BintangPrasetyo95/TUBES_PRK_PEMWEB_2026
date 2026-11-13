<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/komponen.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header('Location: ' . url('alat/index.php'));
    exit;
}

$stmt = db()->prepare(
    'SELECT a.*, k.nama AS nama_kategori
     FROM alat a
     LEFT JOIN kategori k ON a.kategori_id = k.id
     WHERE a.id = :id'
);
$stmt->execute(['id' => $id]);
$alat = $stmt->fetch();

if (!$alat) {
    header('Location: ' . url('alat/index.php'));
    exit;
}

$alat['kategori'] = $alat['nama_kategori'] ?? 'Umum';
$stokBadge = stok_badge($alat);
$tersedia = (int) $alat['stok'] > 0;

$judul = 'Detail Alat - ' . $alat['nama'];
require __DIR__ . '/../includes/header.php';
?>
<?php page_head('Detail inventaris', 'Detail', 'spesifikasi', 'Informasi lengkap mengenai kondisi, lokasi, dan deskripsi ' . $alat['nama'] . '.'); ?>

<div class="detail-layout">
    <div class="detail-foto">
        <?php foto_alat($alat); ?>
    </div>

    <section class="card detail-isi" aria-labelledby="detail-judul">
        <div class="card-body">
            <div class="alat-card__tags">
                <span class="tag tag--dark"><?= e(JENIS_ALAT[$alat['jenis']] ?? $alat['jenis']) ?></span>
                <span class="tag"><?= e($alat['kategori']) ?></span>
                <span class="tag <?= $tersedia ? 'tag--neon' : 'tag--dark' ?>"><?= e($stokBadge['label']) ?></span>
            </div>

            <h2 id="detail-judul"><?= e($alat['nama']) ?></h2>
            <p class="text-muted mb-0">Kode inventaris: <strong><?= e($alat['kode']) ?></strong></p>

            <dl class="detail-spek">
                <div><dt>Lokasi penyimpanan</dt><dd><?= e($alat['lokasi'] ?: '—') ?></dd></div>
                <div><dt>Kondisi fisik</dt><dd><?= e(ucwords(str_replace('_', ' ', $alat['kondisi'] ?? 'baik'))) ?></dd></div>
                <div><dt>Jumlah tersedia</dt><dd><?= (int) $alat['stok'] ?> unit</dd></div>
            </dl>

            <h3>Deskripsi &amp; kelengkapan</h3>
            <p class="detail-deskripsi"><?= e($alat['deskripsi'] ?: 'Belum ada keterangan deskripsi untuk alat ini.') ?></p>

            <div class="form-actions">
<?php if ($tersedia && is_logged_in() && !has_role('admin')): ?>
                <a href="<?= e(url('peminjaman/ajukan.php?id=' . (int) $alat['id'])) ?>" class="btn btn-neon btn-lg">Ajukan Peminjaman<?= ikon('panah', 'ikon ikon--sm') ?></a>
<?php elseif (!is_logged_in()): ?>
                <a href="<?= e(url('auth/login.php')) ?>" class="btn btn-neon btn-lg">Masuk untuk Meminjam<?= ikon('panah', 'ikon ikon--sm') ?></a>
<?php endif; ?>
                <a href="<?= e(url('alat/index.php')) ?>" class="btn btn-outline btn-lg">Kembali ke Daftar Alat</a>
            </div>
        </div>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
