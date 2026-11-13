<?php
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../config/koneksi.php';

$pdo = db();
$adminId = (int) current_user()['id'];
$pesan = '';
$jenisPesan = 'success';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (
            !isset($_POST['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            throw new Exception('Permintaan tidak valid.');
        }

        $aksi = $_POST['aksi'] ?? '';

        if (!in_array($aksi, ['setujui', 'tolak', 'kembali'], true)) {
            throw new Exception('Aksi tidak dikenal.');
        }

        $id = (int) ($_POST['peminjaman_id'] ?? 0);

        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT * FROM peminjaman WHERE id=? FOR UPDATE');
        $stmt->execute([$id]);
        $pinjam = $stmt->fetch();

        if (!$pinjam) {
            throw new Exception('Data peminjaman tidak ditemukan.');
        }

        if ($aksi === 'setujui' || $aksi === 'kembali') {
            $statusTujuan = $aksi === 'setujui' ? 'menunggu' : 'disetujui';

            if ($pinjam['status'] !== $statusTujuan) {
                throw new Exception('Status peminjaman sudah berubah.');
            }

            $stmt = $pdo->prepare(
                'SELECT alat_id, jumlah FROM detail_peminjaman WHERE peminjaman_id=?'
            );
            $stmt->execute([$id]);

            foreach ($stmt->fetchAll() as $item) {
                $perubahan = $aksi === 'setujui' ? -1 : 1;

                $stmtStok = $pdo->prepare(
                    'UPDATE alat
                     SET stok=stok + (? * ?)
                     WHERE id=? AND (?=1 OR stok>=?)'
                );

                $stmtStok->execute([
                    $perubahan,
                    $item['jumlah'],
                    $item['alat_id'],
                    $aksi === 'kembali' ? 1 : 0,
                    $item['jumlah']
                ]);

                if (!$stmtStok->rowCount()) {
                    throw new Exception('Stok tidak mencukupi atau alat tidak ditemukan.');
                }
            }

            if ($aksi === 'setujui') {
                $stmt = $pdo->prepare(
                    "UPDATE peminjaman
                     SET status='disetujui', diproses_oleh=?, diproses_pada=NOW()
                     WHERE id=?"
                );
                $stmt->execute([$adminId, $id]);
                $pesan = 'Peminjaman disetujui.';
            } else {
                $stmt = $pdo->prepare(
                    "UPDATE peminjaman
                     SET status='dikembalikan', tanggal_kembali_aktual=NOW()
                     WHERE id=?"
                );
                $stmt->execute([$id]);
                $pesan = 'Pengembalian berhasil diproses.';
            }
        } else {
            if ($pinjam['status'] !== 'menunggu') {
                throw new Exception('Pengajuan sudah diproses.');
            }

            $stmt = $pdo->prepare(
                "UPDATE peminjaman
                 SET status='ditolak', diproses_oleh=?, diproses_pada=NOW()
                 WHERE id=?"
            );
            $stmt->execute([$adminId, $id]);
            $pesan = 'Pengajuan ditolak.';
        }

        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $pesan = $error instanceof PDOException
            ? 'Gagal memproses peminjaman.'
            : $error->getMessage();
        $jenisPesan = 'error';
    }
}

$daftarPeminjaman = $pdo->query(
    "SELECT p.*, u.nama AS peminjam,
        (SELECT GROUP_CONCAT(
            CONCAT(a.nama, ' (', dp.jumlah, ')')
            ORDER BY a.nama SEPARATOR ', '
         )
         FROM detail_peminjaman dp
         JOIN alat a ON a.id=dp.alat_id
         WHERE dp.peminjaman_id=p.id) AS daftar_barang
     FROM peminjaman p
     JOIN users u ON u.id=p.user_id
     WHERE p.status IN ('menunggu', 'disetujui')
     ORDER BY FIELD(p.status, 'menunggu', 'disetujui'),
              p.tanggal_kembali_rencana"
)->fetchAll();

$judul = 'Daftar Peminjaman';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="section-title">
    <div>
        <h1>Daftar Peminjaman</h1>
        <p class="text-muted mb-0">
            Kelola pengajuan peminjaman dan proses pengembalian alat laboratorium.
        </p>
    </div>
</div>

<?php if ($pesan !== ''): ?>
    <div class="alert alert-<?= $jenisPesan === 'error' ? 'danger' : 'success' ?>">
        <?= e($pesan) ?>
    </div>
<?php endif; ?>

<div class="grid grid-2">
    <div class="card stat-card stat-card--neon">
        <span class="stat-card__ikon"><?= ikon('jam') ?></span>
        <div class="stat-value">
            <?= count(array_filter($daftarPeminjaman, static fn($item) => $item['status'] === 'menunggu')) ?>
        </div>
        <div class="stat-label">Pengajuan Menunggu</div>
    </div>

    <div class="card stat-card stat-card--dark">
        <span class="stat-card__ikon"><?= ikon('kembali') ?></span>
        <div class="stat-value">
            <?= count(array_filter($daftarPeminjaman, static fn($item) => $item['status'] === 'disetujui')) ?>
        </div>
        <div class="stat-label">Peminjaman Aktif</div>
    </div>
</div>

<div class="card mt-6">
    <div class="card-header">
        <h2>Data Peminjaman</h2>
        <span class="badge badge-info"><?= count($daftarPeminjaman) ?> data</span>
    </div>

    <div class="card-body">
        <?php if (!$daftarPeminjaman): ?>
            <div class="empty-state">
                <p>Belum ada pengajuan atau peminjaman aktif.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Peminjam</th>
                            <th>Barang (Jumlah)</th>
                            <th>Tanggal Pinjam</th>
                            <th>Batas Kembali</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftarPeminjaman as $item): ?>
                            <tr>
                                <td><?= e($item['kode']) ?></td>
                                <td><?= e($item['peminjam']) ?></td>
                                <td><?= e($item['daftar_barang'] ?: 'Rincian barang tidak tersedia') ?></td>
                                <td><?= e(format_tanggal($item['tanggal_pinjam'])) ?></td>
                                <td><?= e(format_tanggal($item['tanggal_kembali_rencana'])) ?></td>
                                <td>
                                    <?= status_badge($item['status']) ?>
                                </td>
                                <td>
                                    <div class="form-actions mt-0">
                                        <?php if ($item['status'] === 'menunggu'): ?>
                                            <?php foreach (['setujui' => 'Setujui', 'tolak' => 'Tolak'] as $aksi => $label): ?>
                                                <form method="post">
                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= e($_SESSION['csrf_token']) ?>"
                                                    >
                                                    <input
                                                        type="hidden"
                                                        name="peminjaman_id"
                                                        value="<?= (int) $item['id'] ?>"
                                                    >
                                                    <input
                                                        type="hidden"
                                                        name="aksi"
                                                        value="<?= e($aksi) ?>"
                                                    >
                                                    <button
                                                        type="submit"
                                                        class="btn <?= $aksi === 'setujui' ? 'btn-success' : 'btn-danger' ?> btn-sm"
                                                    >
                                                        <?= e($label) ?>
                                                    </button>
                                                </form>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <form method="post">
                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e($_SESSION['csrf_token']) ?>"
                                                >
                                                <input
                                                    type="hidden"
                                                    name="peminjaman_id"
                                                    value="<?= (int) $item['id'] ?>"
                                                >
                                                <input type="hidden" name="aksi" value="kembali">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    Proses Kembali
                                                </button>
                                            </form>
                                        <?php endif; ?>
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