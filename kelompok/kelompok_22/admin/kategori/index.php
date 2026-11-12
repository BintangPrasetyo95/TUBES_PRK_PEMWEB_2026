<?php
require_once '../auth.php';
require_once '../../config/koneksi.php';
$adminSection = 'kategori';
$judulHalaman = 'Manajemen Kategori Obat';

// Ambil data kategori
$result = $conn->query("SELECT * FROM kategori ORDER BY id_kategori DESC");
?>
<?php require_once '../../backend/admin/layout/header.php'; ?>
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
<?php require_once '../../backend/admin/layout/footer.php'; ?>