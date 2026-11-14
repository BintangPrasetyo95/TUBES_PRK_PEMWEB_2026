<?php
require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/laporan.php';

$pdo = db();
$adminId = (int) current_user()['id'];
$pesan = '';
$jenisPesan = 'success';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception('Permintaan tidak valid.');
        }

        $aksi = $_POST['aksi'] ?? '';

        if (in_array($aksi, ['setujui', 'tolak', 'kembali'], true)) {
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
                $detail = $stmt->fetchAll();

                foreach ($detail as $item) {
                    $perubahan = $aksi === 'setujui' ? -1 : 1;
                    $stmt = $pdo->prepare(
                        'UPDATE alat
                         SET stok=stok + (? * ?)
                         WHERE id=? AND (?=1 OR stok>=?)'
                    );
                    $stmt->execute([
                        $perubahan,
                        $item['jumlah'],
                        $item['alat_id'],
                        $aksi === 'kembali' ? 1 : 0,
                        $item['jumlah']
                    ]);

                    if (!$stmt->rowCount()) {
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
        } else {
            throw new Exception('Aksi tidak dikenal.');
        }
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $pesan = $error instanceof PDOException
            ? 'Gagal memproses data peminjaman. Silakan coba kembali.'
            : $error->getMessage();
        $jenisPesan = 'error';
    }
}

$kategori = $pdo->query('SELECT id, nama FROM kategori ORDER BY nama')->fetchAll();

$totalStok = $pdo->query(
    "SELECT COALESCE(SUM(stok),0) FROM alat WHERE jenis='alat'"
)->fetchColumn();

$menunggu = $pdo->query(
    "SELECT COUNT(*) FROM peminjaman WHERE status='menunggu'"
)->fetchColumn();

$aktif = $pdo->query(
    "SELECT p.*, u.nama AS peminjam,
        GROUP_CONCAT(CONCAT(a.nama, ' (', dp.jumlah, ')') ORDER BY a.nama SEPARATOR ', ') AS daftar_barang
     FROM peminjaman p
     JOIN users u ON u.id=p.user_id
     LEFT JOIN detail_peminjaman dp ON dp.peminjaman_id=p.id
     LEFT JOIN alat a ON a.id=dp.alat_id
     WHERE p.status='disetujui'
     GROUP BY p.id
     ORDER BY p.tanggal_kembali_rencana"
)->fetchAll();

$pengajuan = $pdo->query(
    "SELECT p.*, u.nama AS peminjam
     FROM peminjaman p
     JOIN users u ON u.id=p.user_id
     WHERE p.status='menunggu'
     ORDER BY p.created_at DESC"
)->fetchAll();

$periode = laporan_periode($_GET);
$laporan = laporan_data($periode['awal'], $periode['akhir']);

$judul = 'Dashboard Admin';
$bodyClass = 'page-laporan';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="section-title">
    <div>
        <h1>Dashboard Admin</h1>
        <p class="text-muted mb-0">Ringkasan dan pengelolaan sistem peminjaman alat serta ruang laboratorium.</p>
    </div>
</div>

<?php if ($pesan !== ''): ?>
    <div class="alert alert-<?= $jenisPesan === 'error' ? 'danger' : 'success' ?>">
        <?= e($pesan) ?>
    </div>
<?php endif; ?>

<div id="ringkasan" class="grid grid-3 mt-6">
    <div class="card stat-card stat-card--neon">
        <span class="stat-card__ikon"><?= ikon('alat') ?></span>
        <div class="stat-value"><?= (int) $totalStok ?></div>
        <div class="stat-label">Total stok alat</div>
    </div>

    <div class="card stat-card stat-card--dark">
        <span class="stat-card__ikon"><?= ikon('jam') ?></span>
        <div class="stat-value"><?= (int) $menunggu ?></div>
        <div class="stat-label">Pengajuan menunggu</div>
    </div>

    <div class="card stat-card stat-card--blue">
        <span class="stat-card__ikon"><?= ikon('kembali') ?></span>
        <div class="stat-value"><?= count($aktif) ?></div>
        <div class="stat-label">Peminjaman aktif</div>
    </div>
</div>

<div id="pengajuan" class="card mt-6">
    <div class="card-header">
        <h2>Pengajuan Peminjaman</h2>
        <span class="badge badge-menunggu"><?= count($pengajuan) ?> menunggu</span>
    </div>

    <div class="card-body">
        <?php if (!$pengajuan): ?>
            <div class="empty-state">
                <p>Belum ada pengajuan peminjaman.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Peminjam</th>
                            <th>Tanggal Pinjam</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pengajuan as $item): ?>
                            <tr>
                                <td><?= e($item['kode']) ?></td>
                                <td><?= e($item['peminjam']) ?></td>
                                <td><?= e(format_tanggal($item['tanggal_pinjam'])) ?></td>
                                <td>
                                    <div class="form-actions mt-0">
                                        <?php foreach (['setujui' => 'Setujui', 'tolak' => 'Tolak'] as $action => $label): ?>
                                            <form method="post">
                                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="peminjaman_id" value="<?= (int) $item['id'] ?>">
                                                <input type="hidden" name="aksi" value="<?= e($action) ?>">
                                                <button type="submit"
                                                        class="btn <?= $action === 'setujui' ? 'btn-success' : 'btn-danger' ?> btn-sm">
                                                    <?= e($label) ?>
                                                </button>
                                            </form>
                                        <?php endforeach; ?>
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

<div id="aktif" class="card mt-6">
    <div class="card-header">
        <h2>Peminjaman Aktif</h2>
        <span class="badge badge-disetujui"><?= count($aktif) ?> aktif</span>
    </div>

    <div class="card-body">
        <?php if (!$aktif): ?>
            <div class="empty-state">
                <p>Tidak ada peminjaman aktif.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Peminjam</th>
                            <th>Barang yang Dipinjam</th>
                            <th>Batas Kembali</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($aktif as $item): ?>
                            <tr>
                                <td><?= e($item['kode']) ?></td>
                                <td><?= e($item['peminjam']) ?></td>
                                <td><?= e($item['daftar_barang'] ?: 'Rincian barang tidak tersedia') ?></td>
                                <td><?= e(format_tanggal($item['tanggal_kembali_rencana'])) ?></td>
                                <td>
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="peminjaman_id" value="<?= (int) $item['id'] ?>">
                                        <input type="hidden" name="aksi" value="kembali">
                                        <button type="submit" class="btn btn-primary btn-sm">Proses Kembali</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="kategori" class="card mt-6">
    <div class="card-header">
        <h2>Daftar Kategori</h2>
        <span class="badge badge-info"><?= count($kategori) ?> kategori</span>
    </div>

    <div class="card-body">
        <?php if (!$kategori): ?>
            <div class="empty-state">
                <p>Belum ada kategori.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nama Kategori</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($kategori as $item): ?>
                            <tr>
                                <td><?= e($item['nama']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php render_laporan($laporan, $periode, url('admin/dashboard.php') . '#laporan'); ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>