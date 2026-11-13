<?php
session_start();
require_once '../config/koneksi.php';
require_once '../config/app.php';

$user = current_user();
if (!$user) {
    header("Location: ../auth/login.php");
    exit;
}

$pdo = db();
$user_id = (int)$user['id'];

// 1. Logika Auto-Reject jika status 'menunggu' lebih dari 3 hari
$autoRejectQuery = "UPDATE peminjaman 
                    SET status = 'ditolak' 
                    WHERE status = 'menunggu' 
                    AND DATEDIFF(NOW(), created_at) > 3";
$pdo->exec($autoRejectQuery);

// 2. Ambil data riwayat peminjaman (JOIN peminjaman -> detail_peminjaman -> alat)
$query = "SELECT 
            p.id,
            p.kode AS kode_peminjaman,
            p.tanggal_pinjam,
            p.tanggal_kembali_rencana,
            p.tanggal_kembali_aktual,
            p.keperluan,
            p.status,
            dp.jumlah,
            a.nama AS nama_alat,
            a.kode AS kode_alat
          FROM peminjaman p
          JOIN detail_peminjaman dp ON p.id = dp.peminjaman_id
          JOIN alat a ON dp.alat_id = a.id
          WHERE p.user_id = :user_id
          ORDER BY p.created_at DESC, p.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute(['user_id' => $user_id]);
$riwayats = $stmt->fetchAll();

$judul = 'Riwayat';
include '../includes/header.php';
?>

<section class="hero">
    <h1>Riwayat Peminjaman Saya</h1>
    <p>Pantau status pengajuan peminjaman alat dan ruang laboratorium Anda di sini.</p>
</section>

<!-- Tabel Riwayat Utama -->
<section class="card mt-6">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Kode Transaksi</th>
                    <th>Alat / Ruang</th>
                    <th>Tgl Pinjam</th>
                    <th>Rencana Kembali</th>
                    <th>Jumlah</th>
                    <th>Keperluan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($riwayats) > 0) : ?>
                    <?php foreach ($riwayats as $row) : ?>
                        <tr>
                            <td>
                                <span style="font-family: monospace; font-weight: 600;"><?= e($row['kode_peminjaman']) ?></span>
                            </td>
                            <td>
                                <strong><?= e($row['nama_alat']) ?></strong><br>
                                <span class="text-muted" style="font-size: 0.85em;">Kode Alat: <?= e($row['kode_alat']) ?></span>
                            </td>
                            <td><?= date('d M Y', strtotime($row['tanggal_pinjam'])) ?></td>
                            <td><?= date('d M Y', strtotime($row['tanggal_kembali_rencana'])) ?></td>
                            <td><?= (int)$row['jumlah'] ?> unit</td>
                            <td><?= e($row['keperluan']) ?></td>
                            <td>
                                <?php
                                    $status = strtolower($row['status']);
                                    $badgeClass = 'badge-neutral';
                                    if ($status === 'menunggu') $badgeClass = 'badge-menunggu';
                                    elseif ($status === 'disetujui') $badgeClass = 'badge-disetujui';
                                    elseif ($status === 'ditolak') $badgeClass = 'badge-ditolak';
                                    elseif ($status === 'dikembalikan') $badgeClass = 'badge-dikembalikan';
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= e($status) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem;" class="text-muted">
                            Anda belum memiliki riwayat pengajuan peminjaman.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Tabel Keterangan Status (Pindahan dari Dashboard Beranda) -->
<section class="card mt-6">
    <div class="card-header">
        <h3>Keterangan Status Peminjaman</h3>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <tr><td><span class="badge badge-menunggu">menunggu</span></td><td>Pengajuan belum diproses admin</td></tr>
                <tr><td><span class="badge badge-disetujui">disetujui</span></td><td>Alat boleh diambil sesuai jadwal</td></tr>
                <tr><td><span class="badge badge-ditolak">ditolak</span></td><td>Pengajuan ditolak (otomatis ditolak jika lewat 3 hari)</td></tr>
                <tr><td><span class="badge badge-dikembalikan">dikembalikan</span></td><td>Alat sudah diterima kembali oleh admin</td></tr>
            </tbody>
        </table>
    </div>
</section>

<?php include '../includes/footer.php'; ?>