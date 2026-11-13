<?php
// admin/kelola.php - Halaman Semua Laporan (Publik & Privat)
require_once '../config/database.php';
require_once '../includes/functions.php';

wajibAdmin();

$admin_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;

// PROSES FORM POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (function_exists('verifikasiCsrf')) {
        verifikasiCsrf();
    }

    $aksi = $_POST['aksi'] ?? '';

    // UBAH STATUS & BERI TANGGAPAN
    if ($aksi === 'update_status') {
        $laporan_id    = (int)($_POST['laporan_id'] ?? 0);
        $status_baru   = trim($_POST['status'] ?? '');
        $isi_tanggapan = trim($_POST['isi_tanggapan'] ?? '');

        $status_valid = ['menunggu', 'diproses', 'selesai'];
        if (!in_array($status_baru, $status_valid)) {
            setFlash('danger', 'Status laporan tidak valid!');
            header('Location: kelola.php');
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE laporan SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $status_baru, ':id' => $laporan_id]);

            if (!empty($isi_tanggapan)) {
                $stmt_tanggapan = $pdo->prepare("INSERT INTO tanggapan (laporan_id, admin_id, isi_tanggapan) 
                                                 VALUES (:laporan_id, :admin_id, :isi_tanggapan)");
                $stmt_tanggapan->execute([
                    ':laporan_id'    => $laporan_id,
                    ':admin_id'      => $admin_id,
                    ':isi_tanggapan' => $isi_tanggapan
                ]);
            }

            setFlash('success', 'Status dan tanggapan laporan berhasil diperbarui.');
        } catch (PDOException $e) {
            setFlash('danger', 'Gagal memperbarui status laporan.');
        }

        header('Location: kelola.php');
        exit;
    }

    // HAPUS LAPORAN
    if ($aksi === 'hapus_laporan') {
        $laporan_id = (int)($_POST['laporan_id'] ?? 0);

        try {
            $stmt_foto = $pdo->prepare("SELECT foto FROM laporan WHERE id = :id");
            $stmt_foto->execute([':id' => $laporan_id]);
            $foto = $stmt_foto->fetchColumn();

            if (!empty($foto)) {
                $path_foto = '../assets/uploads/' . $foto;
                if (file_exists($path_foto)) {
                    unlink($path_foto);
                }
            }

            $stmt_hapus = $pdo->prepare("DELETE FROM laporan WHERE id = :id");
            $stmt_hapus->execute([':id' => $laporan_id]);

            setFlash('success', 'Laporan berhasil dihapus dari sistem.');
        } catch (PDOException $e) {
            setFlash('danger', 'Gagal menghapus laporan.');
        }

        header('Location: kelola.php');
        exit;
    }
}

// FILTER & PENCARIAN
$search         = trim($_GET['search'] ?? '');
$filter_status  = $_GET['status'] ?? 'semua';
$filter_jenis   = $_GET['jenis'] ?? 'semua';
$filter_privasi = $_GET['privasi'] ?? 'semua';

// Query Ambil Semua Laporan (Publik maupun Privat)
$sql = "SELECT laporan.*, users.nama AS nama_pelapor, kategori.nama_kategori 
        FROM laporan 
        JOIN users ON laporan.user_id = users.id 
        LEFT JOIN kategori ON laporan.kategori_id = kategori.id 
        WHERE 1=1";

$params = [];

// Filter Kata Kunci Pencarian
if (!empty($search)) {
    $sql .= " AND (laporan.judul LIKE :search OR laporan.isi LIKE :search OR laporan.lokasi LIKE :search OR users.nama LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($filter_status !== 'semua' && in_array($filter_status, ['menunggu', 'diproses', 'selesai'])) {
    $sql .= " AND laporan.status = :status";
    $params[':status'] = $filter_status;
}

if ($filter_jenis !== 'semua' && in_array($filter_jenis, ['pengaduan', 'aspirasi'])) {
    $sql .= " AND laporan.jenis = :jenis";
    $params[':jenis'] = $filter_jenis;
}

if ($filter_privasi === 'publik') {
    $sql .= " AND laporan.is_public = 1";
} elseif ($filter_privasi === 'privat') {
    $sql .= " AND laporan.is_public = 0";
}

$sql .= " ORDER BY laporan.created_at DESC";

$stmt_laporan = $pdo->prepare($sql);
$stmt_laporan->execute($params);
$daftar_laporan = $stmt_laporan->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Kelola Semua Laporan";
include '../includes/header.php';
?>

<div style="margin-bottom: 2rem;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
        <div>
            <h1 style="color: var(--color-primary); font-size: 1.75rem; margin-bottom: 0.35rem;">Semua Laporan (Publik & Privat)</h1>
            <p style="color: var(--color-text-muted); font-size: 0.95rem; margin: 0;">
                Pusat data seluruh pengaduan dan aspirasi warga kampus. Anda dapat mencari, memfilter, dan menindaklanjuti laporan.
            </p>
        </div>
        <a href="dashboard.php" class="btn btn-secondary" style="font-size: 0.9rem;">
            &larr; Dashboard
        </a>
    </div>

    <!-- Kotak Pencarian & Filter Luas -->
    <div class="card" style="padding: 1.5rem; margin-bottom: 2rem;">
        <form method="GET" action="kelola.php">
            <!-- Kolom Search -->
            <div style="margin-bottom: 1.25rem;">
                <label for="search" class="form-label">Cari Laporan:</label>
                <input type="text" id="search" name="search" value="<?= sanitize($search) ?>" 
                       placeholder="Ketik kata kunci judul, rincian, lokasi kejadian, atau nama pelapor..." 
                       class="form-control">
            </div>

            <!-- Deretan Dropdown Filter -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem; align-items: end;">
                <div>
                    <label for="filter-status" class="form-label">Status:</label>
                    <select id="filter-status" name="status" class="form-control">
                        <option value="semua" <?= $filter_status === 'semua' ? 'selected' : '' ?>>Semua Status</option>
                        <option value="menunggu" <?= $filter_status === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                        <option value="diproses" <?= $filter_status === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                        <option value="selesai" <?= $filter_status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                    </select>
                </div>

                <div>
                    <label for="filter-jenis" class="form-label">Jenis:</label>
                    <select id="filter-jenis" name="jenis" class="form-control">
                        <option value="semua" <?= $filter_jenis === 'semua' ? 'selected' : '' ?>>Semua Jenis</option>
                        <option value="pengaduan" <?= $filter_jenis === 'pengaduan' ? 'selected' : '' ?>>Pengaduan</option>
                        <option value="aspirasi" <?= $filter_jenis === 'aspirasi' ? 'selected' : '' ?>>Aspirasi</option>
                    </select>
                </div>

                <div>
                    <label for="filter-privasi" class="form-label">Visibilitas:</label>
                    <select id="filter-privasi" name="privasi" class="form-control">
                        <option value="semua" <?= $filter_privasi === 'semua' ? 'selected' : '' ?>>Semua Visibilitas</option>
                        <option value="publik" <?= $filter_privasi === 'publik' ? 'selected' : '' ?>>Laporan Publik</option>
                        <option value="privat" <?= $filter_privasi === 'privat' ? 'selected' : '' ?>>Laporan Privat 🔒</option>
                    </select>
                </div>

                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Filter Data</button>
                    <a href="kelola.php" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Tampilan Daftar Laporan (Bentuk Kartu Lega) -->
    <div style="margin-bottom: 1rem; font-size: 0.95rem; color: var(--color-text-muted);">
        Menampilkan <strong><?= count($daftar_laporan) ?></strong> laporan yang ditemukan.
    </div>

    <?php if (empty($daftar_laporan)): ?>
        <div class="card" style="text-align: center; padding: 3rem 1rem; color: var(--color-text-muted);">
            <p style="font-size: 1.1rem; margin-bottom: 0.5rem;">Tidak ada laporan yang sesuai dengan pencarian atau filter.</p>
            <a href="kelola.php" style="color: var(--color-primary); font-weight: 600;">Reset Filter</a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            <?php foreach ($daftar_laporan as $row): ?>
                <div class="card" style="padding: 1.5rem; margin-bottom: 0;">
                    
                    <!-- Header Kartu -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.75rem;">
                        <div>
                            <span style="font-size: 0.8rem; font-weight: 700; color: var(--color-secondary); text-transform: uppercase;">
                                <?= sanitize($row['jenis']) ?> &bull; <?= sanitize($row['nama_kategori'] ?? 'Umum') ?>
                            </span>
                            <h2 style="font-size: 1.35rem; color: var(--color-primary); margin: 0.25rem 0;">
                                <a href="../detail.php?id=<?= (int)$row['id'] ?>" style="color: inherit; text-decoration: none;">
                                    <?= sanitize($row['judul']) ?>
                                </a>
                            </h2>
                        </div>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <?php if ((int)$row['is_public'] === 1): ?>
                                <span class="badge" style="background: #E8ECE2; color: #34402A; border: 1px solid #C4CEB5;">Publik</span>
                            <?php else: ?>
                                <span class="badge" style="background: #F0E6CB; color: #6B5420; border: 1px solid #DDD5C0;">Privat 🔒</span>
                            <?php endif; ?>

                            <?= badgeStatus($row['status']) ?>
                        </div>
                    </div>

                    <!-- Isi Ringkas -->
                    <p style="color: var(--color-text); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1rem;">
                        <?= nl2br(sanitize($row['isi'])) ?>
                    </p>

                    <!-- Info Pelapor & Tanggal -->
                    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 0.75rem; padding-top: 1rem; border-top: 1px solid var(--color-border); font-size: 0.88rem; color: var(--color-text-muted);">
                        <div>
                            Pelapor: <strong style="color: var(--color-text);"><?= sanitize($row['nama_pelapor']) ?></strong>
                            &bull; Lokasi: <strong style="color: var(--color-text);"><?= sanitize($row['lokasi'] ?? '-') ?></strong>
                            &bull; Tanggal: <?= date('d M Y, H:i', strtotime($row['created_at'])) ?>
                            <?php if (!empty($row['foto'])): ?>
                                &bull; <span style="color: var(--color-secondary); font-weight: 600;">📷 Ada Foto</span>
                            <?php endif; ?>
                        </div>
                        
                        <div style="display: flex; gap: 0.5rem;">
                            <!-- Tombol Tindak Lanjuti -->
                            <button type="button" 
                                    onclick="document.getElementById('form-tindak-<?= $row['id'] ?>').style.display = document.getElementById('form-tindak-<?= $row['id'] ?>').style.display === 'none' ? 'block' : 'none'" 
                                    class="btn btn-primary btn-sm">
                                Tindak Lanjuti / Balas &darr;
                            </button>

                            <a href="../detail.php?id=<?= (int)$row['id'] ?>" class="btn btn-secondary btn-sm">
                                Detail Lengkap &rarr;
                            </a>

                            <!-- Tombol Hapus -->
                            <form method="POST" action="kelola.php" onsubmit="return confirm('Apakah Anda yakin ingin menghapus laporan ini?');" style="display: inline;">
                                <?= csrfField() ?>
                                <input type="hidden" name="aksi" value="hapus_laporan">
                                <input type="hidden" name="laporan_id" value="<?= (int)$row['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        </div>
                    </div>

                    <!-- Kotak Form Tindak Lanjuti / Ubah Status & Tanggapan (Bisa Buka Tutup) -->
                    <div id="form-tindak-<?= $row['id'] ?>" style="display: none; margin-top: 1.25rem; padding: 1.25rem; background: var(--color-bg); border-radius: var(--radius); border: 1px solid var(--color-border);">
                        <h4 style="color: var(--color-primary); font-size: 1.05rem; margin-bottom: 0.75rem;">
                            Tindak Lanjuti Laporan #<?= (int)$row['id'] ?>
                        </h4>
                        <form method="POST" action="kelola.php">
                            <?= csrfField() ?>
                            <input type="hidden" name="aksi" value="update_status">
                            <input type="hidden" name="laporan_id" value="<?= (int)$row['id'] ?>">

                            <div class="form-group">
                                <label class="form-label">Ubah Status Laporan:</label>
                                <select name="status" class="form-control" style="max-width: 250px;">
                                    <option value="menunggu" <?= $row['status'] === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                                    <option value="diproses" <?= $row['status'] === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                                    <option value="selesai" <?= $row['status'] === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Tulis Tanggapan Resmi Kampus (Opsional):</label>
                                <textarea name="isi_tanggapan" rows="3" class="form-control" placeholder="Tuliskan respon atau penjelasan resmi dari fakultas..."></textarea>
                            </div>

                            <div style="display: flex; gap: 0.5rem;">
                                <button type="submit" class="btn btn-primary btn-sm">Simpan Tanggapan & Status</button>
                                <button type="button" onclick="document.getElementById('form-tindak-<?= $row['id'] ?>').style.display='none'" class="btn btn-secondary btn-sm">Tutup</button>
                            </div>
                        </form>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php include '../includes/footer.php'; ?>