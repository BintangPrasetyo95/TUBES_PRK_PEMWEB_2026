<?php
// Memulai sesi untuk mengenali apakah ada pengguna (pasien/admin) yang sedang login
session_start();

// Mengatur zona waktu default ke Waktu Indonesia Barat (WIB)
date_default_timezone_set('Asia/Jakarta');

// Menghubungkan ke konfigurasi database klinik
require_once __DIR__ . '/config/koneksi.php';

// Memeriksa status login pengguna saat ini
$sudahLogin = isset($_SESSION['user_id']);
$userRole   = $_SESSION['role'] ?? '';
$myUserId   = $sudahLogin ? (int) $_SESSION['user_id'] : 0;

// Nama hari dan bulan dalam Bahasa Indonesia
$namaHari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
$namaBulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

$hariIni = $namaHari[(int) date('N')];
$tanggalHariIni = date('Y-m-d');
$tanggalTampil = $hariIni . ', ' . date('j') . ' ' . $namaBulan[(int) date('n')] . ' ' . date('Y');

// 1. Mengambil daftar seluruh dokter yang terdaftar di klinik
$stmtSemuaDokter = $pdo->query('SELECT id, nama, spesialis, kuota_per_hari, hari_praktik FROM dokter ORDER BY spesialis ASC, nama ASC');
$semuaDokter = $stmtSemuaDokter->fetchAll();

// 2. Menentukan dokter yang dipilih berdasarkan parameter URL ?dokter_id=...
$dokterIdDipilih = filter_input(INPUT_GET, 'dokter_id', FILTER_VALIDATE_INT);

// Jika tidak ada parameter atau dokter_id tidak valid, gunakan dokter pertama secara default
if (!$dokterIdDipilih && !empty($semuaDokter)) {
    $dokterIdDipilih = (int) $semuaDokter[0]['id'];
}

// 3. Mengambil informasi detail dokter yang sedang dibuka antreannya
$dokterAktif = null;
foreach ($semuaDokter as $d) {
    if ((int) $d['id'] === $dokterIdDipilih) {
        $dokterAktif = $d;
        break;
    }
}

// Jika dokter tidak ditemukan di database, alihkan kembali ke halaman utama
if (!$dokterAktif && !empty($semuaDokter)) {
    header('Location: index.php');
    exit;
}

// Memeriksa apakah dokter yang dipilih memiliki jadwal praktik pada hari ini
$hariPraktikDokter = array_map(
    fn($h) => mb_strtolower(trim($h)),
    explode(',', $dokterAktif['hari_praktik'])
);
$isPraktikHariIni = in_array(mb_strtolower($hariIni), $hariPraktikDokter, true);

// 4. Mengambil daftar antrean khusus hanya untuk dokter yang dipilih pada tanggal hari ini
$stmtAntrianDokter = $pdo->prepare(
    'SELECT a.id, a.nomor, a.status, a.created_at, a.user_id, u.nama AS nama_pasien
     FROM antrian a
     JOIN users u ON a.user_id = u.id
     WHERE a.dokter_id = ? AND a.tanggal = ?
     ORDER BY a.nomor ASC'
);
$stmtAntrianDokter->execute([$dokterIdDipilih, $tanggalHariIni]);
$daftarAntrianDokter = $stmtAntrianDokter->fetchAll();

// 5. Menghitung statistik antrean khusus dokter ini (Nomor yang sedang dipanggil, Menunggu, Selesai)
$nomorSedangDipanggil = '-';
$totalMenunggu = 0;
$totalSelesai  = 0;
$totalAntrian  = count($daftarAntrianDokter);

foreach ($daftarAntrianDokter as $ant) {
    if ($ant['status'] === 'dipanggil') {
        $nomorSedangDipanggil = (int) $ant['nomor'];
    } elseif ($ant['status'] === 'menunggu') {
        $totalMenunggu++;
    } elseif ($ant['status'] === 'selesai') {
        $totalSelesai++;
    }
}

// Sisa kuota dokter yang masih dapat diambil hari ini
$sisaKuotaDokter = max(0, (int) $dokterAktif['kuota_per_hari'] - $totalAntrian);

// Fungsi pembantu untuk menyamarkan nama pasien publik demi privasi medis (contoh: "Andi Pratama" -> "Andi P*****")
function sensorNamaPasien($namaLengkap, $isOwner) {
    if ($isOwner) {
        return htmlspecialchars($namaLengkap, ENT_QUOTES, 'UTF-8') . ' <span class="badge bg-success ms-1">Anda</span>';
    }
    $bagian = explode(' ', trim($namaLengkap));
    $namaDepan = $bagian[0] ?? '';
    if (count($bagian) > 1) {
        return htmlspecialchars($namaDepan . ' ' . mb_substr($bagian[1], 0, 1) . '***', ENT_QUOTES, 'UTF-8');
    }
    return htmlspecialchars(mb_substr($namaDepan, 0, 2) . '***', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Papan Antrean: <?= htmlspecialchars($dokterAktif['nama'], ENT_QUOTES, 'UTF-8') ?> - Klinik FK</title>

    <!-- Font Modern -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS Utama Sistem Antrian Klinik -->
    <link rel="stylesheet" href="assets/style.css">

    <!-- Auto Refresh setiap 15 detik agar layar monitor antrean selalu mutakhir secara otomatis -->
    <meta http-equiv="refresh" content="15">
</head>
<body class="bg-light">

<!-- Topbar Navigasi -->
<header class="topbar">
    <a href="index.php" class="brand fw-bold">Klinik FK Unila</a>
    <nav aria-label="Menu navigasi">
        <a href="index.php">Jadwal Umum</a>
        <?php if ($sudahLogin): ?>
            <?php if ($userRole === 'admin'): ?>
                <a href="admin/admin.php">Dashboard Admin</a>
            <?php else: ?>
                <a href="pasien/pasien.php">Antrean Saya</a>
            <?php endif; ?>
            <a href="auth/logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?');">Keluar</a>
        <?php else: ?>
            <a href="auth/login.php">Masuk</a>
            <a href="auth/register.php" class="tombol-kecil">Daftar</a>
        <?php endif; ?>
    </nav>
</header>

<main class="container py-4">

    <!-- Breadcrumb Navigasi -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
            <li class="breadcrumb-item active" aria-current="page">Antrean Dokter: <?= htmlspecialchars($dokterAktif['nama'], ENT_QUOTES, 'UTF-8') ?></li>
        </ol>
    </nav>

    <!-- Bagian Pilih Antrean Dokter: List Singkat Berjejer ke Bawah -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-2 px-3 border-bottom">
            <span class="small fw-bold text-muted text-uppercase">Pilih Antrean Dokter:</span>
        </div>
        <div class="list-group list-group-flush">
            <?php foreach ($semuaDokter as $docPilihan): ?>
                <?php $isDipilih = ((int) $docPilihan['id'] === $dokterIdDipilih); ?>
                <a href="antrian_dokter.php?dokter_id=<?= (int) $docPilihan['id'] ?>"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 <?= $isDipilih ? 'active fw-semibold' : '' ?>">
                    <div>
                        <span><?= htmlspecialchars($docPilihan['nama'], ENT_QUOTES, 'UTF-8') ?></span>
                        <small class="ms-2 <?= $isDipilih ? 'text-white-50' : 'text-muted' ?>">
                            &bull; <?= htmlspecialchars($docPilihan['spesialis'], ENT_QUOTES, 'UTF-8') ?>
                        </small>
                    </div>
                    <?php if ($isDipilih): ?>
                        <span class="badge bg-light text-primary">Sedang Dilihat</span>
                    <?php else: ?>
                        <span class="badge bg-light text-dark border">Buka Antrean &rarr;</span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Header Informasi Dokter yang Sedang Ditampilkan Antreannya -->
    <div class="card border-0 shadow-sm mb-4 text-white" style="background: linear-gradient(135deg, #0f766e, #115e59);">
        <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="badge bg-warning text-dark mb-2">Ruang Praktik: <?= htmlspecialchars($dokterAktif['spesialis'], ENT_QUOTES, 'UTF-8') ?></span>
                <h2 class="h3 fw-bold mb-1"><?= htmlspecialchars($dokterAktif['nama'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="mb-0 text-white-50">
                    Hari Praktik: <strong><?= htmlspecialchars($dokterAktif['hari_praktik'], ENT_QUOTES, 'UTF-8') ?></strong>
                    &bull; Hari ini: <strong><?= htmlspecialchars($tanggalTampil, ENT_QUOTES, 'UTF-8') ?></strong>
                </p>
            </div>
            <div class="text-end">
                <?php if (!$isPraktikHariIni): ?>
                    <span class="badge bg-danger fs-6 px-3 py-2">Tidak Praktik Hari Ini</span>
                <?php elseif ($sisaKuotaDokter <= 0): ?>
                    <span class="badge bg-secondary fs-6 px-3 py-2">Kuota Hari Ini Penuh</span>
                <?php else: ?>
                    <span class="badge bg-success fs-6 px-3 py-2">Sedang Buka Praktik</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Papan Monitor Statistik Antrean Dokter (Panggilan Berjalan) -->
    <div class="row g-3 mb-4">
        <!-- Kotak Nomor yang Sedang Dipanggil -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center p-3 h-100 border-start border-5 border-success bg-white">
                <span class="text-muted small fw-semibold text-uppercase">Nomor Sedang Dipanggil</span>
                <div class="display-3 fw-bold text-success my-1 <?= $nomorSedangDipanggil !== '-' ? 'animate-pulse' : '' ?>">
                    <?= $nomorSedangDipanggil !== '-' ? '#' . $nomorSedangDipanggil : '-' ?>
                </div>
                <span class="small text-muted">
                    <?= $nomorSedangDipanggil !== '-' ? 'Silakan masuk ke ruang periksa' : 'Belum ada panggilan' ?>
                </span>
            </div>
        </div>

        <!-- Kotak Jumlah Pasien Sedang Menunggu -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center p-3 h-100 border-start border-5 border-warning bg-white">
                <span class="text-muted small fw-semibold text-uppercase">Pasien Menunggu Giliran</span>
                <div class="display-3 fw-bold text-warning my-1">
                    <?= $totalMenunggu ?>
                </div>
                <span class="small text-muted">Orang dalam antrean menunggu</span>
            </div>
        </div>

        <!-- Kotak Sisa Kuota Pendaftaran Hari Ini -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center p-3 h-100 border-start border-5 border-primary bg-white">
                <span class="text-muted small fw-semibold text-uppercase">Sisa Kuota Hari Ini</span>
                <div class="display-3 fw-bold text-primary my-1">
                    <?= $sisaKuotaDokter ?>
                </div>
                <span class="small text-muted">Dari total kuota: <?= (int) $dokterAktif['kuota_per_hari'] ?> pasien</span>
            </div>
        </div>
    </div>

    <!-- TABEL DAFTAR ANTREAN KHUSUS DOKTER INI -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h4 class="h5 fw-bold mb-0 text-dark">Daftar Antrean Pasien: <?= htmlspecialchars($dokterAktif['nama'], ENT_QUOTES, 'UTF-8') ?></h4>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload();">
                    ↻ Refresh Manual
                </button>
                <?php if ($sudahLogin && $userRole === 'pasien' && $isPraktikHariIni && $sisaKuotaDokter > 0): ?>
                    <form action="pasien/ambil_antrian.php" method="POST" style="display:inline;" onsubmit="return confirm('Ambil nomor antrean untuk <?= htmlspecialchars($dokterAktif['nama'], ENT_QUOTES, 'UTF-8') ?>?');">
                        <input type="hidden" name="dokter_id" value="<?= (int) $dokterAktif['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-primary">+ Ambil Nomor Dokter Ini</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 120px;">No. Antrean</th>
                        <th>Nama Pasien</th>
                        <th>Waktu Pendaftaran</th>
                        <th>Status Antrean</th>
                        <th class="text-end pe-3">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarAntrianDokter)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                Belum ada pasien yang mengambil antrean untuk dokter ini pada hari ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftarAntrianDokter as $row): ?>
                            <?php $isMyTicket = ($myUserId > 0 && (int) $row['user_id'] === $myUserId); ?>
                            <tr class="<?= $isMyTicket ? 'table-warning fw-semibold' : '' ?>">
                                <!-- Nomor Antrean Khusus Dokter Ini -->
                                <td class="ps-3">
                                    <span class="badge bg-primary fs-6 px-3 py-2">
                                        #<?= (int) $row['nomor'] ?>
                                    </span>
                                </td>

                                <!-- Nama Pasien (Disamarkan untuk privasi umum) -->
                                <td>
                                    <?= sensorNamaPasien($row['nama_pasien'], $isMyTicket) ?>
                                </td>

                                <!-- Waktu Pendaftaran Antrean -->
                                <td class="small text-muted">
                                    <?= date('H:i', strtotime($row['created_at'])) ?> WIB
                                </td>

                                <!-- Status Antrean Pasien -->
                                <td>
                                    <?php if ($row['status'] === 'dipanggil'): ?>
                                        <span class="badge bg-success px-3 py-2 fs-6 animate-pulse">🔊 Sedang Dipanggil</span>
                                    <?php elseif ($row['status'] === 'selesai'): ?>
                                        <span class="badge bg-secondary px-3 py-2 fs-6">✓ Selesai Diperiksa</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark px-3 py-2 fs-6">⏳ Menunggu Giliran</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Keterangan Giliran -->
                                <td class="text-end pe-3 small">
                                    <?php if ($row['status'] === 'dipanggil'): ?>
                                        <strong class="text-success">Sedang berada di ruang dokter</strong>
                                    <?php elseif ($row['status'] === 'selesai'): ?>
                                        <span class="text-muted">Pemeriksaan selesai</span>
                                    <?php else: ?>
                                        <span class="text-muted">Harap menunggu di ruang tunggu</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

