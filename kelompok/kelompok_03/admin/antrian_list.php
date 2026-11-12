<?php
// Memuat header antarmuka admin dan konfigurasi koneksi database
require_once '../includes/header.php';
require_once '../config/koneksi.php';

// Memeriksa otorisasi: hanya pengguna dengan peran admin yang dapat mengakses
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak.');
}

// Tanggal hari ini untuk pencarian antrean
$hari_ini = date('Y-m-d');

// 1. Mengambil seluruh data dokter yang terdaftar
$stmtSemuaDokter = $pdo->query('SELECT id, nama, spesialis, kuota_per_hari, hari_praktik FROM dokter ORDER BY spesialis ASC, nama ASC');
$daftarSemuaDokter = $stmtSemuaDokter->fetchAll(PDO::FETCH_ASSOC);

// 2. Menangkap filter dokter_id jika admin memilih tab/filter dokter tertentu
$dokterFilterId = filter_input(INPUT_GET, 'dokter_id', FILTER_VALIDATE_INT);

// 3. Mengambil daftar antrean hari ini dan mengelompokkannya secara terpisah per dokter
$queryAntrian = "SELECT a.id, a.nomor AS nomor_antrian, a.status, a.created_at, a.dokter_id,
                        u.nama AS nama_pasien, u.email AS email_pasien,
                        d.nama AS nama_dokter, d.spesialis
                 FROM antrian a
                 JOIN users u ON a.user_id = u.id
                 JOIN dokter d ON a.dokter_id = d.id
                 WHERE a.tanggal = :tanggal
                 ORDER BY a.dokter_id ASC, a.nomor ASC";

$stmtAntrian = $pdo->prepare($queryAntrian);
$stmtAntrian->execute(['tanggal' => $hari_ini]);
$semuaAntrianHariIni = $stmtAntrian->fetchAll(PDO::FETCH_ASSOC);

// Mengelompokkan data antrean ke dalam array asosiatif berdasarkan ID Dokter
$antrianPerDokter = [];
foreach ($daftarSemuaDokter as $dok) {
    $antrianPerDokter[$dok['id']] = [];
}
foreach ($semuaAntrianHariIni as $ant) {
    $antrianPerDokter[$ant['dokter_id']][] = $ant;
}
?>

<nav aria-label="breadcrumb">
    <ol class="jejak">
        <li><a href="admin.php">Dashboard</a></li>
        <li aria-current="page">Antrean Khusus per Dokter</li>
    </ol>
</nav>

<div class="baris-judul">
    <div>
        <h2 class="judul">Kelola Antrean Terpisah per Dokter</h2>
        <p class="muted small">Setiap dokter memiliki antrean dan nomor urut tersendiri. Pilih dokter di bawah untuk memanggil pasien.</p>
    </div>
    <span class="lencana lencana-praktik">Tanggal: <?= date('d M Y'); ?></span>
</div>

<!-- Menampilkan pesan kilat (flash message) dari sesi panggil/selesai -->
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

<!-- Navigasi Pemilihan Dokter: List Singkat Berjejer ke Bawah -->
<div class="kartu spasi-bawah">
    <div class="kartu-header" style="background: #f8fafc; padding: 10px 16px; border-bottom: 1px solid #e2e8f0;">
        <span class="tebal small muted text-uppercase">Pilih Antrean Dokter:</span>
    </div>
    <div class="kartu-isi" style="padding: 10px 16px;">
        <div style="display: flex; flex-direction: column; gap: 6px;">
            <!-- Pilihan Semua Dokter -->
            <a href="antrian_list.php"
               class="tombol <?= !$dokterFilterId ? 'tombol-utama' : 'tombol-garis' ?> tombol-kecil-btn"
               style="display: flex; justify-content: space-between; align-items: center; text-align: left; padding: 8px 14px;">
                <span><strong>Semua Dokter</strong> (Tampilkan semua antrean)</span>
                <span class="lencana lencana-netral"><?= count($semuaAntrianHariIni) ?> Antrean</span>
            </a>
            <!-- List Dokter Berjejer ke Bawah -->
            <?php foreach ($daftarSemuaDokter as $docTab): ?>
                <?php
                $jmlAntrianDoc = count($antrianPerDokter[$docTab['id']] ?? []);
                $isActive = ($dokterFilterId === (int) $docTab['id']);
                ?>
                <a href="antrian_list.php?dokter_id=<?= (int) $docTab['id'] ?>"
                   class="tombol <?= $isActive ? 'tombol-utama' : 'tombol-garis' ?> tombol-kecil-btn"
                   style="display: flex; justify-content: space-between; align-items: center; text-align: left; padding: 8px 14px;">
                    <div>
                        <span><strong><?= htmlspecialchars($docTab['nama']) ?></strong></span>
                        <small class="muted" style="margin-left: 8px;">&bull; <?= htmlspecialchars($docTab['spesialis']) ?></small>
                    </div>
                    <span class="lencana <?= $isActive ? 'lencana-sukses' : 'lencana-netral' ?>">
                        <?= $jmlAntrianDoc ?> Antrean
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Menampilkan Tabel Antrean yang Terpisah untuk Setiap Dokter -->
<?php
// Menentukan daftar dokter yang akan ditampilkan (satu dokter terpilih atau seluruh dokter)
$dokterYangDitampilkan = [];
if ($dokterFilterId) {
    foreach ($daftarSemuaDokter as $d) {
        if ((int) $d['id'] === $dokterFilterId) {
            $dokterYangDitampilkan[] = $d;
            break;
        }
    }
} else {
    $dokterYangDitampilkan = $daftarSemuaDokter;
}
?>

<?php foreach ($dokterYangDitampilkan as $dokter): ?>
    <?php
    $listAntrianDokterIni = $antrianPerDokter[$dokter['id']] ?? [];

    // Mencari nomor yang saat ini sedang dipanggil oleh dokter bersangkutan
    $nomorDipanggilSaatIni = '-';
    $jmlMenunggu = 0;
    $jmlSelesai = 0;

    foreach ($listAntrianDokterIni as $rowCek) {
        if ($rowCek['status'] === 'dipanggil') {
            $nomorDipanggilSaatIni = (int) $rowCek['nomor_antrian'];
        } elseif ($rowCek['status'] === 'menunggu') {
            $jmlMenunggu++;
        } elseif ($rowCek['status'] === 'selesai') {
            $jmlSelesai++;
        }
    }
    ?>
    <div class="kartu spasi-bawah" id="antrian-dokter-<?= $dokter['id'] ?>">
        <!-- Header Panel Dokter -->
        <div class="kartu-header d-flex justify-content-between align-items-center flex-wrap gap-2" style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; padding: 16px 20px;">
            <div>
                <span class="lencana lencana-info"><?= htmlspecialchars($dokter['spesialis']) ?></span>
                <h3 class="tebal" style="margin: 4px 0 2px; font-size: 1.25rem;">
                    <?= htmlspecialchars($dokter['nama']) ?>
                </h3>
                <span class="muted small">
                    Hari Praktik: <?= htmlspecialchars($dokter['hari_praktik']) ?> &bull; Kuota Harian: <?= (int) $dokter['kuota_per_hari'] ?>
                </span>
            </div>
            <!-- Ringkasan Status Panggilan Dokter -->
            <div class="d-flex align-items-center gap-3">
                <div class="text-center" style="background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 14px;">
                    <span class="muted small" style="font-size: 0.75rem; display: block;">Sedang Dipanggil</span>
                    <strong style="font-size: 1.3rem; color: #16a34a;">
                        <?= $nomorDipanggilSaatIni !== '-' ? '#' . $nomorDipanggilSaatIni : '-' ?>
                    </strong>
                </div>
                <div class="text-center" style="background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 14px;">
                    <span class="muted small" style="font-size: 0.75rem; display: block;">Menunggu</span>
                    <strong style="font-size: 1.3rem; color: #d97706;">
                        <?= $jmlMenunggu ?>
                    </strong>
                </div>
                <div class="text-center" style="background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 14px;">
                    <span class="muted small" style="font-size: 0.75rem; display: block;">Selesai</span>
                    <strong style="font-size: 1.3rem; color: #64748b;">
                        <?= $jmlSelesai ?>
                    </strong>
                </div>
                <!-- Tombol Buka Layar Antrean Publik / Monitor -->
                <a href="../antrian_dokter.php?dokter_id=<?= $dokter['id'] ?>" target="_blank" class="tombol tombol-garis tombol-kecil-btn" title="Buka Tampilan Layar Antrean Publik">
                    Layar Antrean ↗
                </a>
            </div>
        </div>

        <div class="kartu-isi" style="padding: 0;">
            <div class="bungkus-tabel">
                <table class="tabel" style="margin: 0;">
                    <thead>
                        <tr>
                            <th style="width: 120px;">No. Antrean</th>
                            <th>Nama Pasien</th>
                            <th>Email Pasien</th>
                            <th>Waktu Ambil</th>
                            <th>Status</th>
                            <th class="tengah" style="width: 180px;">Aksi Admin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($listAntrianDokterIni) > 0): ?>
                            <?php foreach ($listAntrianDokterIni as $row): ?>
                                <tr style="<?= $row['status'] === 'dipanggil' ? 'background-color: #f0fdf4;' : '' ?>">
                                    <!-- Nomor Urut Antrean Spesifik Dokter Ini -->
                                    <td>
                                        <span class="nomor-bulat" style="<?= $row['status'] === 'dipanggil' ? 'background:#16a34a; color:#fff;' : '' ?>">
                                            <?= htmlspecialchars($row['nomor_antrian']) ?>
                                        </span>
                                    </td>
                                    <td class="tebal"><?= htmlspecialchars($row['nama_pasien']) ?></td>
                                    <td class="muted small"><?= htmlspecialchars($row['email_pasien']) ?></td>
                                    <td class="muted small"><?= date('H:i', strtotime($row['created_at'])) ?> WIB</td>
                                    <td>
                                        <?php if ($row['status'] === 'menunggu'): ?>
                                            <span class="lencana lencana-peringatan">Menunggu</span>
                                        <?php elseif ($row['status'] === 'dipanggil'): ?>
                                            <span class="lencana lencana-sukses animate-pulse">🔊 Sedang Dipanggil</span>
                                        <?php elseif ($row['status'] === 'selesai'): ?>
                                            <span class="lencana lencana-netral">✓ Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                    <!-- Tombol Aksi Panggil dan Selesai Khusus Pasien Dokter Ini -->
                                    <td class="tengah">
                                        <?php if ($row['status'] === 'menunggu'): ?>
                                            <form action="antrian_panggil.php" method="POST" style="display:inline;" onsubmit="return confirm('Panggil antrean nomor #<?= $row['nomor_antrian'] ?> untuk <?= htmlspecialchars($dokter['nama']) ?>?');">
                                                <input type="hidden" name="antrian_id" value="<?= $row['id'] ?>">
                                                <button type="submit" class="tombol tombol-garis tombol-kecil-btn">Panggil Pasien</button>
                                            </form>
                                        <?php elseif ($row['status'] === 'dipanggil'): ?>
                                            <form action="antrian_selesai.php" method="POST" style="display:inline;" onsubmit="return confirm('Selesaikan pemeriksaan untuk pasien ini?');">
                                                <input type="hidden" name="antrian_id" value="<?= $row['id'] ?>">
                                                <button type="submit" class="tombol tombol-garis-hijau tombol-kecil-btn">Tandai Selesai</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="muted small">Tuntas</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="tengah muted py-4">
                                    Belum ada pasien yang mengambil antrean ke <?= htmlspecialchars($dokter['nama']) ?> hari ini.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script src="admin.js"></script>
<?php 
require_once '../includes/footer.php'; 
?>
