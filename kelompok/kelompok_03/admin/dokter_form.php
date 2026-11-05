
<?php

require_once __DIR__ . '/../includes/cek_login.php';

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak. Hanya admin yang dapat mengakses halaman ini.');
}

$id = $_GET['id'] ?? null;
$isEdit = !empty($id);

$dokter = [
    'id' => '',
    'nama' => '',
    'spesialis' => '',
    'kuota_per_hari' => '',
    'hari_praktik' => ''
];

$error = '';

if ($isEdit) {
    try {
        $stmt = $pdo->prepare('SELECT id, nama, spesialis, kuota_per_hari, hari_praktik FROM dokter WHERE id = ?');
        $stmt->execute([$id]);
        $fetchedDokter = $stmt->fetch();
        
        if ($fetchedDokter) {
            $dokter = $fetchedDokter;
        } else {
            header('Location: dokter_list.php?error=' . urlencode('Dokter tidak ditemukan.'));
            exit;
        }
    } catch (PDOException $e) {
        error_log('[Dokter Form] ' . $e->getMessage());
        $error = 'Gagal memuat data dokter.';
    }
}

$hariOptions = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
$selectedHari = $isEdit ? explode(',', $dokter['hari_praktik']) : [];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Edit' : 'Tambah' ?> Dokter - Admin</title>
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4><?= $isEdit ? 'Edit' : 'Tambah' ?> Dokter</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="dokter_simpan.php">
                            <?php if ($isEdit): ?>
                                <input type="hidden" name="id" value="<?= htmlspecialchars($dokter['id']) ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="nama" class="form-label">Nama Dokter</label>
                                <input type="text" class="form-control" id="nama" name="nama" 
                                       value="<?= htmlspecialchars($dokter['nama']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="spesialis" class="form-label">Spesialis</label>
                                <input type="text" class="form-control" id="spesialis" name="spesialis" 
                                       value="<?= htmlspecialchars($dokter['spesialis']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="kuota_per_hari" class="form-label">Kuota Per Hari</label>
                                <input type="number" class="form-control" id="kuota_per_hari" name="kuota_per_hari" 
                                       value="<?= htmlspecialchars($dokter['kuota_per_hari']) ?>" min="1" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Hari Praktik</label>
                                <div>
                                    <?php foreach ($hariOptions as $hari): ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="hari_praktik[]" 
                                                   id="hari_<?= $hari ?>" 
                                                   value="<?= $hari ?>"
                                                   <?= in_array($hari, $selectedHari) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="hari_<?= $hari ?>">
                                                <?= $hari ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Simpan</button>
                                <a href="dokter_list.php" class="btn btn-secondary">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
