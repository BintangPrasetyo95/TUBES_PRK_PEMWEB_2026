<?php
require_once '../auth.php';
require_once '../../config/koneksi.php';

// Ambil data kategori
$result = $conn->query("SELECT * FROM kategori ORDER BY id_kategori DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kategori - KlikObat</title>
    <!-- Hubungkan CSS dari folder frontend/assets/css -->
    <link rel="stylesheet" href="../../frontend/assets/css/style.css">
    <link rel="stylesheet" href="../../frontend/assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar Admin -->
        <aside class="admin-sidebar">
            <div class="admin-logo">KlikObat</div>
            <ul class="admin-menu">
                <li><a href="index.php" class="active">Kelola Kategori</a></li>
                <li><a href="../obat/index.php">Kelola Obat</a></li>
                <li><a href="../logout.php" style="color: #ef4444;">Logout</a></li>
            </ul>
        </aside>

        <!-- Konten Utama Admin -->
        <main class="admin-main">
            <header class="admin-header">
                <h1>Manajemen Kategori Obat</h1>
                <div class="admin-user">Admin Panel</div>
            </header>

            <div class="admin-content">
                <div style="margin-bottom: 20px;">
                    <a href="tambah.php" class="admin-btn">+ Tambah Kategori Baru</a>
                </div>

                <!-- Tabel Data Kategori -->
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Kategori</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= htmlspecialchars($row['nama_kategori']); ?></td>
                                <td>
                                    <a href="ubah.php?id=<?= $row['id_kategori']; ?>" style="color: var(--admin-primary); font-weight: 600; margin-right: 10px;">Ubah</a>
                                    <a href="hapus.php?id=<?= $row['id_kategori']; ?>" onclick="return confirm('Yakin ingin menghapus kategori ini?');" style="color: #ef4444; font-weight: 600;">Hapus</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
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