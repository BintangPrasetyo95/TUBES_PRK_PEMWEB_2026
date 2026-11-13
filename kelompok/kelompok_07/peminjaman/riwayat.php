<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/komponen.php';

if (!isset($_SESSION['user']['id'])) {
    set_flash('warning', 'Anda harus login untuk melihat riwayat.');
    header('Location: ' . url('auth/login.php'));
    exit;
}

$user_id = $_SESSION['user']['id'];
$pdo = db();

$stmtAutoReject = $pdo->prepare(
    "UPDATE peminjaman
     SET status = 'ditolak',
         catatan_admin = 'Ditolak otomatis oleh sistem karena belum diproses admin selama 3 hari.'
     WHERE user_id = ?
       AND status = 'menunggu'
       AND created_at < (NOW() - INTERVAL 3 DAY)"
);
$stmtAutoReject->execute([$user_id]);

$stmt = $pdo->prepare(
    'SELECT p.kode, p.tanggal_pinjam, p.tanggal_kembali_rencana, p.status,
            a.nama AS nama_alat, dp.jumlah
     FROM peminjaman p
     JOIN detail_peminjaman dp ON p.id = dp.peminjaman_id
     JOIN alat a ON dp.alat_id = a.id
     WHERE p.user_id = ?
     ORDER BY p.created_at DESC'
);
$stmt->execute([$user_id]);
$riwayats = $stmt->fetchAll();

$judul = 'Riwayat Peminjaman';
require __DIR__ . '/../includes/header.php';
?>
<?php page_head('Riwayat saya', 'Riwayat', 'peminjaman', 'Pantau status setiap pengajuan yang pernah kamu buat, dari menunggu sampai dikembalikan.'); ?>

<section class="card" aria-labelledby="keterangan-judul">
    <div class="card-header">
        <h2 id="keterangan-judul">Keterangan Status</h2>
        <?= ikon('info') ?>
    </div>
    <div class="card-body">
        <ul class="legend-status">
<?php foreach (STATUS_PEMINJAMAN as $status => $info): ?>
            <li>
                <?= status_badge($status) ?>
                <p><?= e($info['keterangan']) ?></p>
            </li>
<?php endforeach; ?>
        </ul>
    </div>
</section>

<section class="card mt-6" aria-labelledby="riwayat-judul">
    <div class="card-header">
        <h2 id="riwayat-judul">Daftar Pengajuan</h2>
        <span class="badge badge-info"><?= count($riwayats) ?> pengajuan</span>
    </div>
    <div class="card-body">
<?php if ($riwayats === []): ?>
        <div class="empty-state">
            <?= ikon('ajukan') ?>
            <h3>Belum ada riwayat peminjaman</h3>
            <p>Pengajuan yang kamu buat akan muncul di sini.</p>
            <a href="<?= e(url('peminjaman/index.php')) ?>" class="btn btn-neon">Ajukan Peminjaman</a>
        </div>
<?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Kode / Alat</th>
                        <th scope="col">Tgl Pinjam</th>
                        <th scope="col">Tgl Kembali</th>
                        <th scope="col">Jumlah</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
<?php foreach ($riwayats as $row): ?>
                    <tr>
                        <td>
                            <strong><?= e($row['kode']) ?></strong>
                            <span class="riwayat-alat"><?= e($row['nama_alat']) ?></span>
                        </td>
                        <td class="nowrap"><?= e(format_tanggal($row['tanggal_pinjam'])) ?></td>
                        <td class="nowrap"><?= e(format_tanggal($row['tanggal_kembali_rencana'])) ?></td>
                        <td class="nowrap"><?= (int) $row['jumlah'] ?> unit</td>
                        <td><?= status_badge($row['status']) ?></td>
                    </tr>
<?php endforeach; ?>
                </tbody>
            </table>
        </div>
<?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
