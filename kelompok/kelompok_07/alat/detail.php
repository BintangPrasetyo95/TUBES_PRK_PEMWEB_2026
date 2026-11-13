<?php
session_start();
require_once '../config/koneksi.php';
require_once '../config/app.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

$pdo = db();

// Ambil detail lengkap alat beserta nama kategorinya
$query = "SELECT a.*, k.nama AS nama_kategori 
          FROM alat a 
          LEFT JOIN kategori k ON a.kategori_id = k.id 
          WHERE a.id = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $id]);
$alat = $stmt->fetch();

// Jika ID tidak ditemukan di database, kembalikan ke daftar alat
if (!$alat) {
    header("Location: index.php");
    exit;
}

$judul = 'Detail Alat - ' . $alat['nama'];
$foto_url = !empty($alat['foto']) ? "../uploads/alat/" . $alat['foto'] : "../assets/img/default-alat.png";

include '../includes/header.php';
?>

<!-- Bagian Biru di Judul (Hero) -->
<section class="hero">
    <h1>Detail Spesifikasi Inventaris</h1>
    <p>Informasi lengkap mengenai kondisi, lokasi, dan deskripsi <?= e($alat['nama']) ?>.</p>
</section>

<!-- Kartu Detail Utama -->
<section class="card mt-6">
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; align-items: start;">
            
            <!-- Kolom Kiri: Foto Besar -->
            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; display: flex; align-items: center; justify-content: center; min-height: 280px;">
                <img src="<?= e($foto_url) ?>" alt="<?= e($alat['nama']) ?>" style="width: 100%; max-height: 360px; object-fit: cover;">
            </div>

            <!-- Kolom Kanan: Rincian Informasi -->
            <div>
                <div class="item-meta mb-2">
                    <span class="badge badge-info"><?= e(ucfirst($alat['jenis'] ?? 'alat')) ?></span>
                    <span class="badge badge-neutral"><?= e($alat['nama_kategori'] ?? 'Umum') ?></span>
                    <?php if ($alat['stok'] > 0): ?>
                        <span class="badge badge-disetujui">Tersedia (<?= (int)$alat['stok'] ?> Unit)</span>
                    <?php else: ?>
                        <span class="badge badge-ditolak">Stok Habis</span>
                    <?php endif; ?>
                </div>

                <h2 style="font-size: 1.75rem; margin-bottom: 0.25rem;"><?= e($alat['nama']) ?></h2>
                <p class="text-muted" style="margin-bottom: 1.5rem;">Kode Inventaris: <strong><?= e($alat['kode']) ?></strong></p>

                <!-- Tabel Spesifikasi Singkat -->
                <div class="table-responsive" style="margin-bottom: 1.5rem;">
                    <table class="table">
                        <tbody>
                            <tr>
                                <th style="width: 35%;">Lokasi Penyimpanan</th>
                                <td><?= e($alat['lokasi'] ?: '-') ?></td>
                            </tr>
                            <tr>
                                <th>Kondisi Fisik</th>
                                <td><?= e(ucwords(str_replace('_', ' ', $alat['kondisi'] ?? 'baik'))) ?></td>
                            </tr>
                            <tr>
                                <th>Jumlah Tersedia</th>
                                <td><strong><?= (int)$alat['stok'] ?> Unit</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Deskripsi Lengkap -->
                <div style="margin-bottom: 2rem;">
                    <h4 style="margin-bottom: 0.5rem; font-size: 1rem;">Deskripsi &amp; Kelengkapan:</h4>
                    <p class="text-muted" style="line-height: 1.6; white-space: pre-line;">
                        <?= e($alat['deskripsi'] ?: 'Belum ada keterangan deskripsi untuk alat ini.') ?>
                    </p>
                </div>

                <!-- Tombol Navigasi Bawah -->
                <div style="display: flex; flex-wrap: wrap; gap: 12px; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
                    <a href="index.php" class="btn btn-outline">&larr; Kembali ke Daftar Alat</a>
                    
                    <?php if ($alat['stok'] > 0 && is_logged_in() && !has_role('admin')): ?>
                        <a href="../peminjaman/ajukan.php?id=<?= (int)$alat['id'] ?>" class="btn btn-primary">Ajukan Peminjaman Alat Ini</a>
                    <?php elseif (!is_logged_in()): ?>
                        <a href="../auth/login.php" class="btn btn-primary">Masuk untuk Meminjam</a>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>