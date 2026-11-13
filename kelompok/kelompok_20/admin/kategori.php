<?php

require_once '../config/database.php';
require_once '../includes/functions.php';

wajibAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (function_exists('verifikasiCsrf')) {
        verifikasiCsrf();
    }

    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $nama_kategori = trim($_POST['nama_kategori'] ?? '');

        if (empty($nama_kategori)) {
            setFlash('danger', 'Kategori Gagal Dibuat');
            header('Location: kategori.php');
            exit;
        }

        try {
            $stmt_cek = $pdo->prepare("SELECT COUNT(*) FROM kategori WHERE LOWER(nama_kategori) = LOWER(:nama)");
            $stmt_cek->execute([':nama' => $nama_kategori]);
            
            if ($stmt_cek->fetchColumn() > 0) {
                setFlash('danger', 'Kategori Sudah Ada');
            } else {
                $stmt_insert = $pdo->prepare("INSERT INTO kategori (nama_kategori) VALUES (:nama)");
                $stmt_insert->execute([':nama' => $nama_kategori]);
                setFlash('success', 'Kategori Berhasil Dibuat');
            }
        } catch (PDOException $e) {
            setFlash('danger', 'Kategori Gagal Dibuat');
        }

        header('Location: kategori.php');
        exit;
    }

    if ($aksi === 'hapus') {
        $kategori_id = (int)($_POST['kategori_id'] ?? 0);

        try {
            $stmt_hapus = $pdo->prepare("DELETE FROM kategori WHERE id = :id");
            $stmt_hapus->execute([':id' => $kategori_id]);
            setFlash('success', 'Kategori Berhasil Dihapus');
        } catch (PDOException $e) {
            setFlash('danger', 'Kategori Gagal Dihapus');
        }

        header('Location: kategori.php');
        exit;
    }
}

$query = "SELECT kategori.*, COUNT(laporan.id) AS total_laporan 
          FROM kategori 
          LEFT JOIN laporan ON kategori.id = laporan.kategori_id 
          GROUP BY kategori.id 
          ORDER BY kategori.nama_kategori ASC";
$daftar_kategori = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Kelola Kategori";
include '../includes/header.php';
?>

<div style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
        <div>
            <h1 style="color: var(--color-primary); font-size: 1.75rem; margin-bottom: 0.35rem;">Kelola Kategori Laporan</h1>
            <p style="color: var(--color-text-muted); font-size: 0.95rem; margin: 0;">Atur kategori pengelompokan aspirasi dan pengaduan kampus.</p>
        </div>
        <a href="dashboard.php" class="btn btn-secondary" style="font-size: 0.9rem;">
            &larr; Kembali ke Dashboard
        </a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem; align-items: start;">
        
        <div class="card" style="padding: 1.75rem;">
            <h3 class="card-title" style="font-size: 1.2rem; margin-bottom: 1rem;">+ Tambah Kategori Baru</h3>
            <form method="POST" action="kategori.php">
                <?= csrfField() ?>
                <input type="hidden" name="aksi" value="tambah">

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label for="nama_kategori" class="form-label">Nama Kategori</label>
                    <input type="text" id="nama_kategori" name="nama_kategori" class="form-control" placeholder="Contoh: Layanan IT & Wifi" required>
                    <small style="color: var(--color-text-muted); font-size: 0.85rem; display: block; margin-top: 0.35rem;">
                        Pastikan nama kategori belum pernah dibuat sebelumnya.
                    </small>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Simpan Kategori Baru
                </button>
            </form>
        </div>

        <div class="card" style="padding: 1.75rem;">
            <h3 class="card-title" style="font-size: 1.2rem; margin-bottom: 1rem;">Daftar Kategori Tersedia</h3>
            <div class="table-responsive" style="margin-bottom: 0;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="padding: 0.75rem 1rem; width: 50px;">No</th>
                            <th style="padding: 0.75rem 1rem;">Kategori</th>
                            <th style="padding: 0.75rem 1rem; text-align: center;">Jumlah</th>
                            <th style="padding: 0.75rem 1rem; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($daftar_kategori)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 1.5rem; color: var(--color-text-muted);">Belum ada kategori.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($daftar_kategori as $idx => $kat): ?>
                                <tr>
                                    <td style="padding: 0.85rem 1rem;"><?= $idx + 1 ?></td>
                                    <td style="padding: 0.85rem 1rem; font-weight: 600; color: var(--color-primary);">
                                        <?= sanitize($kat['nama_kategori']) ?>
                                    </td>
                                    <td style="padding: 0.85rem 1rem; text-align: center;">
                                        <span class="badge badge-diproses"><?= (int)$kat['total_laporan'] ?> laporan</span>
                                    </td>
                                    <td style="padding: 0.85rem 1rem; text-align: center;">
                                        <form method="POST" action="kategori.php" onsubmit="return confirm('Yakin ingin menghapus kategori ini? Laporan lama tidak akan hilang.');" style="display: inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="aksi" value="hapus">
                                            <input type="hidden" name="kategori_id" value="<?= (int)$kat['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" style="padding: 0.3rem 0.6rem;">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>