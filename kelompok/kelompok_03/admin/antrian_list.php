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
    <ol class="jejak">
        <li><a href="admin.php">Dashboard</a></li>
        <li aria-current="page">Daftar Antrian</li>
    </ol>
</nav>

<div class="baris-judul">
    <div>
        <h2 class="judul">Daftar Antrian Pasien</h2>
        <p class="muted small">Pantau daftar antrian pasien yang masuk hari ini, panggil pasien, atau selesaikan antrian.</p>
    </div>
    <span class="lencana lencana-praktik">Hari Ini: <?= date('d M Y'); ?></span>
</div>

<?php if (isset($_SESSION['flash_pesan'])): ?>
    <?php
    $tipeFlash = $_SESSION['flash_tipe'] ?? 'info';
    $kelasFlash = $tipeFlash === 'success' ? 'peringatan-sukses' : ($tipeFlash === 'danger' || $tipeFlash === 'error' ? 'peringatan-bahaya' : 'peringatan-info');
    ?>
    <div class="peringatan <?= $kelasFlash ?>" role="alert">
        <span><?= htmlspecialchars($_SESSION['flash_pesan']) ?></span>
        <button type="button" class="tutup" data-tutup aria-label="Tutup">&times;</button>
    </div>
    <?php unset($_SESSION['flash_tipe'], $_SESSION['flash_pesan']); ?>
<?php endif; ?>

<div class="kartu">
    <div class="kartu-isi">
        <div class="baris-filter">
            <input type="text" id="cari-admin" class="masukan masukan-kecil" placeholder="Cari nama pasien atau dokter...">
            <select id="filter-status-admin" class="masukan masukan-kecil">
                <option value="">Semua Status</option>
                <option value="menunggu">Menunggu</option>
                <option value="dipanggil">Dipanggil</option>
                <option value="selesai">Selesai</option>
            </select>
        </div>
        <div class="bungkus-tabel">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>No. Antrian</th>
                        <th>Nama Pasien</th>
                        <th>Dokter Tujuan</th>
                        <th>Status</th>
                        <th class="tengah">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($antrian_list) > 0): ?>
                        <?php foreach ($antrian_list as $row): ?>
                            <tr>
                                <td><span class="nomor-bulat"><?= htmlspecialchars($row['nomor_antrian']) ?></span></td>
                                <td class="tebal"><?= htmlspecialchars($row['nama_pasien']) ?></td>
                                <td><?= htmlspecialchars($row['nama_dokter']) ?></td>
                                <td>
                                    <?php if ($row['status'] === 'menunggu'): ?>
                                        <span class="lencana lencana-peringatan">Menunggu</span>
                                    <?php elseif ($row['status'] === 'dipanggil'): ?>
                                        <span class="lencana lencana-info">Dipanggil</span>
                                    <?php elseif ($row['status'] === 'selesai'): ?>
                                        <span class="lencana lencana-sukses">Selesai</span>
                                    <?php endif; ?>
                                </td>
                                <td class="tengah">
                                    <?php if ($row['status'] === 'menunggu'): ?>
                                        <form action="antrian_panggil.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="antrian_id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="tombol tombol-garis tombol-kecil-btn">Panggil</button>
                                        </form>
                                    <?php elseif ($row['status'] === 'dipanggil'): ?>
                                        <form action="antrian_selesai.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="antrian_id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="tombol tombol-garis-hijau tombol-kecil-btn">Selesai</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="lencana lencana-netral">Selesai</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="tengah muted">Belum ada pasien yang mengambil antrian hari ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="admin.js"></script>
<?php 
require_once '../includes/footer.php'; 
?>