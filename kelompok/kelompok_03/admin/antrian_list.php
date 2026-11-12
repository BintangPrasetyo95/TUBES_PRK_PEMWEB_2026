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
$tanggal_filter = (isset($_GET['tanggal']) && $_GET['tanggal'] !== '') ? $_GET['tanggal'] : $hari_ini;
$dokter_filter = isset($_GET['dokter_id']) ? (int) $_GET['dokter_id'] : 0;

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
<?php else: ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-3 flex-wrap">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- Dropdown Button Nama Dokter -->
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle px-3" type="button" id="dropdownDokter" data-bs-toggle="dropdown" aria-expanded="false">
                            <?= htmlspecialchars($dokterTerpilih['nama']) ?>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownDokter" style="max-height: 320px; overflow-y: auto;">
                            <?php foreach ($dokterBerantrian as $dk): ?>
                                <li>
                                    <a class="dropdown-item d-flex justify-content-between align-items-center gap-3 <?= ((int) $dk['id'] === (int) $dokterTerpilih['id']) ? 'active' : '' ?>"
                                       href="?tanggal=<?= urlencode($tanggal_filter) ?>&dokter_id=<?= (int) $dk['id'] ?>">
                                        <span><?= htmlspecialchars($dk['nama']) ?></span>
                                        <span class="badge <?= ((int) $dk['id'] === (int) $dokterTerpilih['id']) ? 'bg-light text-primary' : 'bg-secondary' ?>"><?= (int) $dk['jumlah'] ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <span class="text-muted small"><?= (int) $dokterTerpilih['jumlah'] ?> antrean &middot; <?= htmlspecialchars($dokterTerpilih['spesialis']) ?></span>
                </div>

                <div class="d-flex align-items-center gap-2 flex-nowrap">
                    <input type="text" id="cari-admin" class="form-control form-control-sm" style="max-width: 220px;" placeholder="Cari nama pasien...">
                    <form method="GET" action="" class="d-flex align-items-center gap-2 m-0">
                        <input type="hidden" name="dokter_id" value="<?= (int) $dokterTerpilih['id'] ?>">
                        <input type="date" name="tanggal" value="<?= htmlspecialchars($tanggal_filter) ?>" onchange="this.form.submit()" class="form-control form-control-sm" style="width: 150px; cursor: pointer;" title="Pilih tanggal riwayat antrian">
                        <?php if ($tanggal_filter !== $hari_ini): ?>
                            <a href="antrian_list.php?dokter_id=<?= (int) $dokterTerpilih['id'] ?>" class="btn btn-outline-secondary btn-sm text-nowrap">Hari Ini</a>
                        <?php endif; ?>
                    </form>
                    <select id="filter-status-admin" class="form-select form-select-sm" style="width: 150px;">
                        <option value="">Semua Status</option>
                        <option value="menunggu">Menunggu</option>
                        <option value="dipanggil">Dipanggil</option>
                        <option value="selesai">Selesai</option>
                    </select>
                </div>
            </div>

            <!-- Peringatan bila ada pasien yang sedang berjalan -->
            <?php if ($sedangDipanggil !== null): ?>
                <div class="alert alert-warning py-2 mb-3" role="alert">
                    Sedang melayani <strong>#<?= htmlspecialchars($sedangDipanggil['nomor_antrian']) ?> - <?= htmlspecialchars($sedangDipanggil['nama_pasien']) ?></strong>.
                    Selesaikan dulu sebelum memanggil pasien berikutnya.
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="py-3" width="15%">No. Antrian</th>
                            <th scope="col" class="py-3" width="45%">Nama Pasien</th>
                            <th scope="col" class="py-3" width="15%">Status</th>
                            <th scope="col" class="py-3 text-center" width="25%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($antrian_list) > 0): ?>
                            <?php foreach ($antrian_list as $row): ?>
                                <tr>
                                    <td>
                                        <span class="d-inline-flex align-items-center justify-content-center fw-bold rounded-circle" style="width: 36px; height: 36px; font-size: 0.9rem; background-color: #f0fdfa; color: #0f766e; border: 1px solid #99f6e4;">
                                            <?= htmlspecialchars($row['nomor_antrian']) ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($row['nama_pasien']) ?></td>

                                    <td>
                                        <?php if ($row['status'] === 'menunggu'): ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-pill small">Menunggu</span>
                                        <?php elseif ($row['status'] === 'dipanggil'): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill small">Dipanggil</span>
                                        <?php elseif ($row['status'] === 'selesai'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill small">Selesai</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-center">
                                        <?php if ($row['status'] === 'menunggu'): ?>
                                            <?php if ($sedangDipanggil !== null): ?>
                                                <!-- Dikunci: masih ada pasien yang sedang dipanggil -->
                                                <button type="button" class="btn btn-outline-secondary btn-sm px-3" disabled
                                                        title="Selesaikan antrean #<?= htmlspecialchars($sedangDipanggil['nomor_antrian']) ?> terlebih dahulu">
                                                    Panggil
                                                </button>
                                            <?php else: ?>
                                                <form action="antrian_panggil.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="antrian_id" value="<?= $row['id'] ?>">
                                                    <input type="hidden" name="tanggal" value="<?= htmlspecialchars($tanggal_filter) ?>">
                                                    <button type="submit" class="btn btn-outline-primary btn-sm px-3">Panggil</button>
                                                </form>
                                            <?php endif; ?>
                                        <?php elseif ($row['status'] === 'dipanggil'): ?>
                                            <form action="antrian_selesai.php" method="POST" class="d-inline">
                                                <input type="hidden" name="antrian_id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="tanggal" value="<?= htmlspecialchars($tanggal_filter) ?>">
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
                                <td colspan="4" class="text-center py-4 text-muted">
                                    Tidak ada antrean untuk dokter ini pada tanggal tersebut.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

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
