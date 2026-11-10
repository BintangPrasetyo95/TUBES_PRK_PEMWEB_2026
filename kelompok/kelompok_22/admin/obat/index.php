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
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        .notif { padding: 10px; margin: 12px 0; border-radius: 4px; }
        .notif.ok { background: #d1e7dd; }
        .notif.err { background: #f8d7da; }
        .thumb { width: 48px; height: 48px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; }
        .badge { color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 12px; }
        .btn { display: inline-block; padding: 6px 12px; background: #0d6efd; color: #fff; text-decoration: none; border-radius: 4px; border: 0; cursor: pointer; font-size: 14px; }
        .btn.hapus { background: #dc3545; }
        .btn.ubah { background: #6c757d; }
    </style>
</head>
<body>
    <h1>Data Obat</h1>
    <p><a href="../kategori/index.php">Kelola Kategori</a> | <a href="../logout.php" style="color:red">Logout</a></p>
    <a class="btn" href="tambah.php">+ Tambah Obat</a>

    <?php if ($notif): ?>
        <div class="notif <?= $status_get === 'gagal' ? 'err' : 'ok' ?>">
            <?= htmlspecialchars($notif) ?>
        </div>
    <?php endif; ?>

    <?php if ($error_db): ?>
        <div class="notif err">
            <strong>Data obat tidak dapat dimuat.</strong><br>
            Detail: <?= htmlspecialchars($error_db) ?>
        </div>
    <?php endif; ?>

    <table style="margin-top:12px">
        <tr>
            <th>No</th><th>Gambar</th><th>Nama Obat</th><th>Kategori</th><th>Stok</th><th>Status</th><th>Aksi</th>
        </tr>
        <?php if ($hasil && mysqli_num_rows($hasil) > 0): ?>
            <?php $no = 1; while ($row = mysqli_fetch_assoc($hasil)): ?>
                <?php $status = getStatusStok((int)$row['stok']); ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><img class="thumb" src="../../assets/images/obat/<?= htmlspecialchars($row['gambar'] ?: 'default.jpg') ?>" alt=""></td>
                    <td><?= htmlspecialchars($row['nama_obat']) ?></td>
                    <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
                    <td><?= (int)$row['stok'] ?></td>
                    <td><span class="badge" style="background:<?= $warna[$status] ?>"><?= $status ?></span></td>
                    <td>
                        <a class="btn ubah" href="ubah.php?id=<?= (int)$row['id_obat'] ?>">Ubah</a>
                        <form action="hapus.php" method="post" style="display:inline"
                              onsubmit="return confirm('Yakin hapus obat ini?')">
                            <input type="hidden" name="id" value="<?= (int)$row['id_obat'] ?>">
                            <button type="submit" class="btn hapus">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7"><?= $error_db ? 'Data tidak dapat dimuat.' : 'Belum ada data obat.' ?></td></tr>
        <?php endif; ?>
    </table>
</body>
</html>