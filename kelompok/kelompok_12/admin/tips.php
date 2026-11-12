
<?php
// kelompok/kelompok_12/admin/tips.php

require_once '../includes/auth.php';
require_once '../config/database.php';

// Ambil semua data tips dari database
$stmt = $pdo->query(
    "SELECT * FROM tips ORDER BY created_at DESC"
);

$tips = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success = '';

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Tips Kesehatan</title>
    <link rel="stylesheet" href="../assets/css/tips.css?v=2">
</head>
<body>

    <h1>Kelola Tips Kesehatan</h1>
    <?php if ($success !== ''): ?>
    <div class="pesan-sukses">
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

    <p>
        Selamat datang,
        <?= htmlspecialchars($_SESSION['admin_username']) ?>!
    </p>

    <div class="navigasi">
    <a href="dashboard.php" class="btn-dashboard">Kembali ke Dashboard</a>
    <a href="tip-add.php" class="btn-tambah">+ Tambah Tips</a>
    <a href="logout.php" class="btn-logout">Logout</a>
</div>

    <hr>

    <h2>Daftar Tips Kesehatan</h2>

    <?php if (count($tips) > 0): ?>

        <table border="1" cellpadding="10" cellspacing="0">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Judul</th>
                    <th>Deskripsi</th>
                    <th>Gambar</th>
                    <th>Tanggal Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                <?php $no = 1; ?>

                <?php foreach ($tips as $tip): ?>
                    <tr>
                        <td><?= $no++ ?></td>

                        <td>
                            <?= htmlspecialchars($tip['title']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($tip['description']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($tip['image'] ?? '-') ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($tip['created_at']) ?>
                        </td>

                        <td>
                           
<a href="tip-edit.php?id=<?= (int) $tip['id'] ?>" class="btn-edit">
    Edit
</a>

<form action="tip-delete.php" method="POST"
      class="form-hapus"
      onsubmit="return confirm('Yakin ingin menghapus tips ini?');">

    <input type="hidden" name="id" value="<?= (int) $tip['id'] ?>">

    <button type="submit" class="btn-hapus">
        Hapus
    </button>
</form>

                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php else: ?>

        <p>Belum ada tips kesehatan.</p>

    <?php endif; ?>

</body>
</html>