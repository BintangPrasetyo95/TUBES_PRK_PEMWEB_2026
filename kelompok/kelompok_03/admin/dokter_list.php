
<?php
require_once '../includes/header.php';
require_once '../config/koneksi.php';

// Validasi role admin 
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak. Hanya admin yang dapat mengakses halaman ini.');
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

try {
    $stmt = $pdo->query('SELECT id, nama, spesialis, kuota_per_hari, hari_praktik FROM dokter ORDER BY nama');
    $dokterList = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('[Dokter List] ' . $e->getMessage());
    $dokterList = [];
    $error = 'Gagal memuat data dokter.';
}
?>

<nav aria-label="breadcrumb">
    <ol class="jejak">
        <li><a href="admin.php">Dashboard</a></li>
        <li aria-current="page">Kelola Dokter</li>
    </ol>
</nav>

<div class="baris-judul">
    <div>
        <h2 class="judul">Kelola Dokter</h2>
        <p class="muted small">Kelola daftar dokter praktik, spesialisasi, kuota harian, dan hari praktik.</p>
    </div>
    <a href="dokter_form.php" class="tombol tombol-utama">+ Tambah Dokter</a>
</div>

<?php if ($success): ?>
    <div class="peringatan peringatan-sukses" role="alert">
        <span><?= htmlspecialchars($success) ?></span>
        <button type="button" class="tutup" data-tutup aria-label="Tutup">&times;</button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="peringatan peringatan-bahaya" role="alert">
        <span><?= htmlspecialchars($error) ?></span>
        <button type="button" class="tutup" data-tutup aria-label="Tutup">&times;</button>
    </div>
<?php endif; ?>

<div class="kartu">
    <div class="kartu-isi">
        <?php if (empty($dokterList)): ?>
            <p class="muted tengah">Belum ada data dokter.</p>
        <?php else: ?>
        <div class="baris-filter">
            <input type="text" id="cari-admin" class="masukan masukan-kecil" placeholder="Cari nama dokter atau spesialis...">
            <select id="filter-status-admin" class="masukan masukan-kecil">
                <option value="">Semua Hari Praktik</option>
                <option value="senin">Senin</option>
                <option value="selasa">Selasa</option>
                <option value="rabu">Rabu</option>
                <option value="kamis">Kamis</option>
                <option value="jumat">Jumat</option>
                <option value="sabtu">Sabtu</option>
                <option value="minggu">Minggu</option>
            </select>
        </div>
        <div class="bungkus-tabel">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Spesialis</th>
                        <th>Kuota/Hari</th>
                        <th>Hari Praktik</th>
                        <th class="tengah">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dokterList as $dokter): ?>
                        <tr>
                            <td class="tebal"><?= htmlspecialchars($dokter['nama']) ?></td>
                            <td><span class="lencana lencana-info"><?= htmlspecialchars($dokter['spesialis']) ?></span></td>
                            <td><span class="sedang"><?= htmlspecialchars($dokter['kuota_per_hari']) ?></span> Pasien</td>
                            <td><span class="lencana lencana-netral"><?= htmlspecialchars(str_replace(',', ', ', $dokter['hari_praktik'])) ?></span></td>
                            <td class="tengah">
                                <a href="dokter_form.php?id=<?= $dokter['id'] ?>" class="tombol tombol-garis tombol-kecil-btn">Edit</a>
                                <a href="dokter_hapus.php?id=<?= $dokter['id'] ?>" class="tombol tombol-garis-merah tombol-kecil-btn" onclick="return confirm('Yakin ingin menghapus dokter ini?')">Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="admin.js"></script>
<?php 
require_once '../includes/footer.php'; 
?>