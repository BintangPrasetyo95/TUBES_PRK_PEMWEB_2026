
<?php

require_once __DIR__ . '/../includes/cek_login.php';

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
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Dokter - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">Sistem Antrian Klinik - Admin</span>
            <div class="text-white">
                <?= htmlspecialchars($_SESSION['nama']) ?> | 
                <a href="../auth/logout.php" class="text-white">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Kelola Dokter</h2>
            <a href="dokter_form.php" class="btn btn-primary">Tambah Dokter</a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <?php if (empty($dokterList)): ?>
                    <p class="text-muted">Belum ada data dokter.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Spesialis</th>
                                    <th>Kuota/Hari</th>
                                    <th>Hari Praktik</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dokterList as $dokter): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($dokter['nama']) ?></td>
                                        <td><?= htmlspecialchars($dokter['spesialis']) ?></td>
                                        <td><?= htmlspecialchars($dokter['kuota_per_hari']) ?></td>
                                        <td><?= htmlspecialchars($dokter['hari_praktik']) ?></td>
                                        <td>
                                            <a href="dokter_form.php?id=<?= $dokter['id'] ?>" 
                                               class="btn btn-sm btn-warning">Edit</a>
                                            <a href="dokter_hapus.php?id=<?= $dokter['id'] ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Yakin ingin menghapus dokter ini?')">Hapus</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
