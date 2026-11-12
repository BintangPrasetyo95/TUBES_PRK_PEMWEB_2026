<?php
/**
 * File: admin/obat/index.php
 * Daftar obat (Read) - Kelompok 22 KlikObat
 */

require_once '../auth.php'; // halaman ini hanya boleh dibuka admin yang sudah login

require_once '../../config/koneksi.php';
require_once '../../config/helper.php';

$daftar_pesan = [
    'tambah' => 'Obat berhasil ditambahkan.',
    'ubah'   => 'Data obat berhasil diperbarui.',
    'hapus'  => 'Obat berhasil dihapus.',
    'gagal'  => 'Terjadi kesalahan, silakan coba lagi.',
];
$status_get = $_GET['status'] ?? '';
$notif = $daftar_pesan[$status_get] ?? '';

$sql = "SELECT o.id_obat, o.nama_obat, o.stok, o.gambar, k.nama_kategori
        FROM obat o
        JOIN kategori k ON o.id_kategori = k.id_kategori
        ORDER BY o.id_obat DESC";
$hasil = mysqli_query($conn, $sql);
$error_db = $hasil ? '' : mysqli_error($conn); 

$warna = [
    'Tersedia' => '#198754',
    'Menipis'  => '#e69500',
    'Habis'    => '#dc3545',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Obat - Admin KlikObat</title>
    <!-- Hubungkan CSS UI Admin -->
    <link rel="stylesheet" href="../../frontend/assets/css/style.css">
    <link rel="stylesheet" href="../../frontend/assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar Admin -->
        <aside class="admin-sidebar">
            <div class="admin-logo">KlikObat</div>
            <ul class="admin-menu">
                <li><a href="../kategori/index.php">Kelola Kategori</a></li>
                <li><a href="index.php" class="active">Kelola Obat</a></li>
                <li><a href="../logout.php" style="color: #ef4444;">Logout</a></li>
            </ul>
        </aside>

        <!-- Konten Utama Admin -->
        <main class="admin-main">
            <header class="admin-header">
                <h1>Data Obat</h1>
                <div class="admin-user">Admin Panel</div>
            </header>

            <div class="admin-content">
                <div style="margin-bottom: 20px;">
                    <a class="admin-btn" href="tambah.php">+ Tambah Obat</a>
                </div>

                <?php if ($notif): ?>
                    <div style="padding: 12px 15px; margin-bottom: 20px; border-radius: 10px; font-weight: 600; font-size: 14px; background: <?= $status_get === 'gagal' ? '#fee2e2; color: #b91c1c;' : '#d1e7dd; color: #0f5132;' ?>">
                        <?= htmlspecialchars($notif) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_db): ?>
                    <div style="padding: 12px 15px; margin-bottom: 20px; background: #fee2e2; color: #b91c1c; border-radius: 10px; font-weight: 600; font-size: 14px;">
                        <strong>Data obat tidak dapat dimuat.</strong><br>
                        Detail: <?= htmlspecialchars($error_db) ?>
                    </div>
                <?php endif; ?>

                <!-- Tabel Data Obat -->
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Gambar</th>
                                <th>Nama Obat</th>
                                <th>Kategori</th>
                                <th>Stok</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($hasil && mysqli_num_rows($hasil) > 0): ?>
                                <?php $no = 1; while ($row = mysqli_fetch_assoc($hasil)): ?>
                                    <?php $status = getStatusStok((int)$row['stok']); ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><img style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd;" src="../../assets/images/obat/<?= htmlspecialchars($row['gambar'] ?: 'default.jpg') ?>" alt=""></td>
                                        <td><?= htmlspecialchars($row['nama_obat']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
                                        <td><?= (int)$row['stok'] ?></td>
                                        <td><span style="color: #fff; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; background: <?= $warna[$status] ?>"><?= $status ?></span></td>
                                        <td>
                                            <div style="display: flex; gap: 10px; align-items: center;">
                                                <a href="ubah.php?id=<?= (int)$row['id_obat'] ?>" style="color: var(--admin-primary); font-weight: 600; text-decoration: none;">Ubah</a>
                                                <form action="hapus.php" method="post" style="display:inline" onsubmit="return confirm('Yakin hapus obat ini?')">
                                                    <input type="hidden" name="id" value="<?= (int)$row['id_obat'] ?>">
                                                    <button type="submit" style="background: none; border: none; color: #ef4444; font-weight: 600; cursor: pointer; padding: 0;">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7" style="text-align: center; color: var(--admin-secondary); padding: 20px;"><?= $error_db ? 'Data tidak dapat dimuat.' : 'Belum ada data obat.' ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <footer class="admin-footer">
                <p>&copy; 2026 KlikObat. Sistem Informasi Katalog Farmasi & Edukasi.</p>
            </footer>
        </main>
    </div>
</body>
</html>