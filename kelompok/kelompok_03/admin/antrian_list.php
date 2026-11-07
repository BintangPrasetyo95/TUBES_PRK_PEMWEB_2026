<?php
require_once '../includes/header.php';
require_once '../config/koneksi.php';

// hanya admin yang bisa mengakses halaman ini
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak.');
}

$hari_ini = date('Y-m-d');

// Query untuk mengambil daftar antrian hari ini
$query = "SELECT a.id, a.nomor AS nomor_antrian, a.status, u.nama AS nama_pasien, d.nama AS nama_dokter 
          FROM antrian a
          JOIN users u ON a.user_id = u.id
          JOIN dokter d ON a.dokter_id = d.id
          WHERE a.tanggal = :tanggal
          ORDER BY a.nomor ASC";

$stmt = $pdo->prepare($query);
$stmt->execute(['tanggal' => $hari_ini]);
$antrian_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-2 small">
        <li class="breadcrumb-item"><a href="admin.php" class="text-decoration-none">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Daftar Antrian</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-primary mb-1">Daftar Antrian Pasien</h2>
        <p class="text-muted small mb-0">Pantau daftar antrian pasien yang masuk hari ini, panggil pasien, atau selesaikan antrian.</p>
    </div>
    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill small">
        Hari Ini: <?= date('d M Y'); ?>
    </span>
</div>

<!-- Menampilkan Pesan Sukses / Error dari Backend -->
<?php if (isset($_SESSION['flash_pesan'])): ?>
    <div class="alert alert-<?= htmlspecialchars($_SESSION['flash_tipe']) ?> alert-dismissible fade show shadow-sm" role="alert">
        <?= htmlspecialchars($_SESSION['flash_pesan']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php 
    // Hapus pesan setelah ditampilkan
    unset($_SESSION['flash_tipe'], $_SESSION['flash_pesan']); 
    ?>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="py-3" width="15%">No. Antrian</th>
                        <th scope="col" class="py-3" width="25%">Nama Pasien</th>
                        <th scope="col" class="py-3" width="25%">Dokter Tujuan</th>
                        <th scope="col" class="py-3" width="15%">Status</th>
                        <th scope="col" class="py-3 text-center" width="20%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($antrian_list) > 0): ?>
                        <?php foreach ($antrian_list as $row): ?>
                            <tr>
                                <td>
                                    <!-- Lingkaran Lembut Bergaris Tipis Biru -->
                                    <span class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary border border-primary-subtle fw-bold rounded-circle" style="width: 36px; height: 36px; font-size: 0.9rem;">
                                        <?= htmlspecialchars($row['nomor_antrian']) ?>
                                    </span>
                                </td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($row['nama_pasien']) ?></td>
                                <td><?= htmlspecialchars($row['nama_dokter']) ?></td>
                                
                                <!-- Status Badge Ramping -->
                                <td>
                                    <?php if ($row['status'] === 'menunggu'): ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-pill small">
                                            Menunggu
                                        </span>
                                    <?php elseif ($row['status'] === 'dipanggil'): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill small">
                                            Dipanggil
                                        </span>
                                    <?php elseif ($row['status'] === 'selesai'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill small">
                                            Selesai
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Aksi Tombol -->
                                <td class="text-center">
                                    <?php if ($row['status'] === 'menunggu'): ?>
                                        <form action="antrian_panggil.php" method="POST" class="d-inline">
                                            <input type="hidden" name="antrian_id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="btn btn-outline-primary btn-sm px-3">Panggil</button>
                                        </form>
                                    <?php elseif ($row['status'] === 'dipanggil'): ?>
                                        <form action="antrian_selesai.php" method="POST" class="d-inline">
                                            <input type="hidden" name="antrian_id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="btn btn-outline-success btn-sm px-3">Selesai</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border px-3 py-1">Selesai</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                Belum ada pasien yang mengambil antrian hari ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
require_once '../includes/footer.php'; 
?>