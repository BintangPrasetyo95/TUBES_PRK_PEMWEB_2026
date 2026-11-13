<?php
session_start();
require_once '../config/koneksi.php';
require_once '../config/app.php';

$id_alat = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_alat === 0) {
    header("Location: index.php");
    exit;
}

$pdo = db();

// Ambil detail alat berdasarkan ID
$query = "SELECT * FROM alat WHERE id = :id AND stok > 0";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $id_alat]);
$alat = $stmt->fetch();

// Jika alat tidak ditemukan atau stok habis
if (!$alat) {
    echo "<script>alert('Alat tidak ditemukan atau stok habis!'); window.location.href='index.php';</script>";
    exit;
}

$judul = 'Ajukan Peminjaman';
include '../includes/header.php';
?>

<!-- Bagian Biru di Judul (Hero) Full Width -->
<section class="hero">
    <h1>Formulir Pengajuan Peminjaman</h1>
    <p>Silakan isi detail pengajuan untuk meminjam <?= e($alat['nama'] ?? 'alat') ?>.</p>
</section>

<!-- Kontainer Utama (Card) Full Width sejajar Hero -->
<section class="card mt-6">
    <div class="card-body">
        
        <!-- Detail Alat Singkat -->
        <div style="display: flex; align-items: center; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid #e2e8f0;">
            <?php $foto_url = !empty($alat['foto']) ? "../uploads/alat/" . $alat['foto'] : "../assets/img/default-alat.png"; ?>
            <img src="<?= e($foto_url) ?>" alt="<?= e($alat['nama']) ?>" style="width: 96px; height: 96px; object-fit: cover; border-radius: 8px; margin-right: 1.5rem; border: 1px solid #e2e8f0;">
            <div>
                <h2 style="margin-bottom: 0.25rem; font-size: 1.4rem;"><?= e($alat['nama']) ?></h2>
                <p class="text-muted" style="margin-bottom: 0.5rem;">Kode: <?= e($alat['kode']) ?> | Lokasi: <?= e($alat['lokasi']) ?></p>
                <span class="badge badge-info">Sisa Stok: <?= (int)$alat['stok'] ?> Unit</span>
            </div>
        </div>

        <!-- Form Pengajuan diproses ke proses_ajukan.php -->
        <form action="proses_ajukan.php" method="POST">
            <input type="hidden" name="id_alat" value="<?= (int)$alat['id'] ?>">
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                <!-- Tanggal Pinjam -->
                <div>
                    <label for="tgl_pinjam" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Tanggal Pinjam</label>
                    <input type="date" id="tgl_pinjam" name="tgl_pinjam" min="<?= date('Y-m-d') ?>" required
                        style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box; font-family: inherit; font-size: 0.95rem;">
                </div>
                <!-- Tanggal Kembali -->
                <div>
                    <label for="tgl_kembali" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Tanggal Kembali</label>
                    <input type="date" id="tgl_kembali" name="tgl_kembali" min="<?= date('Y-m-d') ?>" required
                        style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box; font-family: inherit; font-size: 0.95rem;">
                </div>
            </div>

            <!-- Jumlah Pinjam -->
            <div style="margin-bottom: 1.5rem;">
                <label for="jumlah" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Jumlah Pinjam</label>
                <input type="number" id="jumlah" name="jumlah" min="1" max="<?= (int)$alat['stok'] ?>" value="1" required
                    style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box; font-family: inherit; font-size: 0.95rem;">
                <p class="text-muted" style="margin-top: 0.5rem; font-size: 0.85rem;">Maksimal peminjaman tidak boleh melebihi stok tersedia.</p>
            </div>

            <!-- Keperluan -->
            <div style="margin-bottom: 2rem;">
                <label for="keperluan" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Keperluan / Keterangan</label>
                <textarea id="keperluan" name="keperluan" rows="3" required placeholder="Contoh: Praktikum Jaringan Komputer, Tugas Akhir, dll."
                    style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box; resize: vertical; font-family: inherit; font-size: 0.95rem;"></textarea>
            </div>

            <!-- Tombol Aksi -->
            <div style="display: flex; justify-content: flex-end; align-items: center; gap: 12px; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
                <a href="index.php" class="btn btn-outline">Batal</a>
                <button type="submit" class="btn btn-primary" style="font-family: inherit;">
                    Ajukan Peminjaman
                </button>
            </div>
        </form>
    </div>
</section>

<?php include '../includes/footer.php'; ?>