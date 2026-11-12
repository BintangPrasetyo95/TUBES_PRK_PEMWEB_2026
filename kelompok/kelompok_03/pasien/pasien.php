<?php
// Memastikan sesi telah berjalan dan pengguna sudah login sebagai pasien
require_once __DIR__ . '/../includes/cek_login.php';

// Memeriksa hak akses, jika role adalah admin maka diarahkan ke halaman panel admin
if ($_SESSION['role'] !== 'pasien') {
    header('Location: ../admin/admin.php');
    exit;
}

// Mengatur zona waktu WIB
date_default_timezone_set('Asia/Jakarta');

// Menangkap notifikasi pesan kilat (flash message) dari sesi jika baru saja mengambil antrean
$flashPesan = $_SESSION['flash_pesan'] ?? null;
$flashTipe  = $_SESSION['flash_tipe'] ?? 'info';
// Menghapus flash message agar tidak muncul kembali setelah halaman dimuat ulang
unset($_SESSION['flash_pesan'], $_SESSION['flash_tipe']);

// Mengambil data identitas pengguna yang sedang login
$userId = (int) $_SESSION['user_id'];
$namaPasien = $_SESSION['nama'] ?? 'Pasien';

// Data penanggalan hari ini dalam format Bahasa Indonesia
$namaHari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
$namaBulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

$hariIni = $namaHari[(int) date('N')];
$tanggalHariIni = date('Y-m-d');
$tanggalTampil = $hariIni . ', ' . date('j') . ' ' . $namaBulan[(int) date('n')] . ' ' . date('Y');

// 1. QUERY: Mengambil daftar tiket antrean aktif milik pasien ini pada hari ini
$stmtAntrianAktif = $pdo->prepare(
    'SELECT a.id, a.nomor, a.status, a.tanggal, a.created_at,
            d.id AS dokter_id, d.nama AS nama_dokter, d.spesialis, d.kuota_per_hari
     FROM antrian a
     JOIN dokter d ON a.dokter_id = d.id
     WHERE a.user_id = ? AND a.tanggal = ?
     ORDER BY a.id DESC'
);
$stmtAntrianAktif->execute([$userId, $tanggalHariIni]);
$daftarAntrianAktif = $stmtAntrianAktif->fetchAll();

// Menyiapkan prepared statement untuk mencari nomor antrean yang sedang dipanggil oleh dokter bersangkutan
$stmtSedangPanggil = $pdo->prepare(
    "SELECT nomor FROM antrian WHERE dokter_id = ? AND tanggal = ? AND status = 'dipanggil' ORDER BY nomor DESC LIMIT 1"
);

// Menyiapkan prepared statement untuk menghitung sisa antrean yang masih menunggu di depan nomor pasien
$stmtSisaDiDepan = $pdo->prepare(
    "SELECT COUNT(*) FROM antrian WHERE dokter_id = ? AND tanggal = ? AND status = 'menunggu' AND nomor < ?"
);

// Mengolah informasi status antrean real-time untuk setiap tiket yang dimiliki pasien hari ini
$antrianAktifDiproses = [];
$dokterSudahDiambil = [];

foreach ($daftarAntrianAktif as $antrian) {
    // Mencatat ID dokter yang sudah diambil antreannya oleh pasien hari ini agar tidak bisa mengambil ganda
    $dokterSudahDiambil[] = (int) $antrian['dokter_id'];

    // Mengambil nomor yang saat ini sedang dipanggil oleh dokter di poli
    $stmtSedangPanggil->execute([$antrian['dokter_id'], $tanggalHariIni]);
    $nomorPanggilan = $stmtSedangPanggil->fetchColumn();

    // Menghitung berapa orang yang sedang menunggu di depan nomor antrean pasien
    $stmtSisaDiDepan->execute([$antrian['dokter_id'], $tanggalHariIni, $antrian['nomor']]);
    $antreanDiDepan = (int) $stmtSisaDiDepan->fetchColumn();

    $antrianAktifDiproses[] = [
        'id'             => $antrian['id'],
        'nomor'          => (int) $antrian['nomor'],
        'status'         => $antrian['status'],
        'nama_dokter'    => $antrian['nama_dokter'],
        'spesialis'      => $antrian['spesialis'],
        'waktu_daftar'   => date('H:i', strtotime($antrian['created_at'])),
        'sedang_panggil' => $nomorPanggilan !== false ? (int) $nomorPanggilan : '-',
        'sisa_di_depan'  => $antreanDiDepan,
    ];
}

// 2. QUERY: Mengambil daftar dokter untuk form pendaftaran ambil nomor antrean hari ini
$stmtSemuaDokter = $pdo->query('SELECT id, nama, spesialis, kuota_per_hari, hari_praktik FROM dokter ORDER BY spesialis ASC, nama ASC');
$semuaDokter = $stmtSemuaDokter->fetchAll();

// Menyiapkan prepared statement untuk menghitung jumlah kuota antrean yang telah terisi hari ini
$stmtHitungTerisi = $pdo->prepare('SELECT COUNT(*) FROM antrian WHERE dokter_id = ? AND tanggal = ?');

$daftarPilihanDokter = [];
$daftarSpesialis = [];

foreach ($semuaDokter as $doc) {
    if (!in_array($doc['spesialis'], $daftarSpesialis, true)) {
        $daftarSpesialis[] = $doc['spesialis'];
    }

    // Memeriksa kecocokan jadwal hari praktik dokter dengan hari ini
    $jadwalHariArray = array_map(
        fn($h) => mb_strtolower(trim($h)),
        explode(',', $doc['hari_praktik'])
    );
    $apakahPraktik = in_array(mb_strtolower($hariIni), $jadwalHariArray, true);

    // Menghitung kuota terisi dan sisa kuota hari ini
    $stmtHitungTerisi->execute([$doc['id'], $tanggalHariIni]);
    $kuotaTerpakai = (int) $stmtHitungTerisi->fetchColumn();
    $sisaKuota = max(0, (int) $doc['kuota_per_hari'] - $kuotaTerpakai);

    // Cek apakah pasien ini sudah pernah mengambil nomor ke dokter ini pada hari ini
    $sudahAmbilKeDokterIni = in_array((int) $doc['id'], $dokterSudahDiambil, true);

    $daftarPilihanDokter[] = [
        'id'            => (int) $doc['id'],
        'nama'          => $doc['nama'],
        'spesialis'     => $doc['spesialis'],
        'hari_praktik'  => $doc['hari_praktik'],
        'kuota_total'   => (int) $doc['kuota_per_hari'],
        'sisa_kuota'    => $sisaKuota,
        'apakah_praktik'=> $apakahPraktik,
        'sudah_ambil'   => $sudahAmbilKeDokterIni,
        'bisa_ambil'    => $apakahPraktik && ($sisaKuota > 0) && !$sudahAmbilKeDokterIni,
    ];
}

// 3. QUERY: Mengambil riwayat kunjungan antrean pasien sebelumnya (selesai atau tanggal lampau)
$stmtRiwayat = $pdo->prepare(
    'SELECT a.nomor, a.status, a.tanggal, d.nama AS nama_dokter, d.spesialis
     FROM antrian a
     JOIN dokter d ON a.dokter_id = d.id
     WHERE a.user_id = ? AND a.tanggal < ?
     ORDER BY a.tanggal DESC, a.nomor DESC LIMIT 5'
);
$stmtRiwayat->execute([$userId, $tanggalHariIni]);
$riwayatList = $stmtRiwayat->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Pasien & Status Antrean - Klinik FK</title>

    <!-- Font Modern -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="halaman-depan">

<header class="topbar">
    <a href="../index.php" class="brand">Klinik FK Unila</a>
    <nav aria-label="Menu pengguna">
        <a href="../index.php">Jadwal Umum</a>
        <a href="pasien.php" class="aktif">Antrean Saya</a>
        <a href="../auth/logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?');">Keluar</a>
    </nav>
</header>

<main class="wadah">

    <?php if ($flashPesan): ?>
        <?php
        $kelasFlash = $flashTipe === 'success' ? 'peringatan-sukses' : ($flashTipe === 'danger' || $flashTipe === 'error' ? 'peringatan-bahaya' : 'peringatan-info');
        ?>
        <div class="peringatan <?= $kelasFlash ?>" role="alert">
            <span><strong>Pemberitahuan:</strong> <?= htmlspecialchars($flashPesan, ENT_QUOTES, 'UTF-8') ?></span>
            <button type="button" class="tutup" data-tutup aria-label="Tutup">&times;</button>
        </div>
    <?php endif; ?>

    <div class="kartu banner">
        <div class="kartu-isi">
            <div>
                <span class="lencana lencana-peringatan">Portal Pasien</span>
                <h2>Halo, <?= htmlspecialchars($namaPasien, ENT_QUOTES, 'UTF-8') ?>!</h2>
                <p>Hari ini: <strong><?= htmlspecialchars($tanggalTampil, ENT_QUOTES, 'UTF-8') ?></strong></p>
            </div>
            <div class="grup-tombol">
                <a href="#form-ambil" class="tombol tombol-peringatan">+ Ambil Nomor Baru</a>
                <button type="button" class="tombol tombol-utama" onclick="location.reload();">↻ Segarkan Status</button>
            </div>
        </div>
    </div>

    <div class="spasi-bawah">
        <div class="baris-judul">
            <h3 class="subjudul">Tiket Antrean Aktif Anda Hari Ini</h3>
            <span class="lencana lencana-utama"><?= count($antrianAktifDiproses) ?> Tiket Terdaftar</span>
        </div>

        <?php if (empty($antrianAktifDiproses)): ?>
            <div class="kartu kosong-besar">
                <div class="ikon">🎫</div>
                <h4>Belum Ada Nomor Antrean Aktif</h4>
                <p class="muted">Anda belum mengambil nomor antrean untuk hari ini. Silakan pilih dokter praktik pada daftar di bawah.</p>
                <a href="#form-ambil" class="tombol tombol-utama">Pilih Dokter & Ambil Nomor</a>
            </div>
        <?php else: ?>
            <div class="kisi">
                <?php foreach ($antrianAktifDiproses as $tiket): ?>
                    <?php
                    $kelasTiket = $tiket['status'] === 'dipanggil' ? 'tiket-dipanggil' : ($tiket['status'] === 'selesai' ? 'tiket-selesai' : '');
                    ?>
                    <div class="kartu tiket <?= $kelasTiket ?>">
                        <div class="kartu-isi">
                            <div>
                                <?php if ($tiket['status'] === 'menunggu'): ?>
                                    <span class="lencana lencana-peringatan">⏳ Menunggu Giliran</span>
                                <?php elseif ($tiket['status'] === 'dipanggil'): ?>
                                    <span class="lencana lencana-sukses animate-pulse">🔊 Giliran Anda Dipanggil!</span>
                                <?php else: ?>
                                    <span class="lencana lencana-abu">✓ Selesai Diperiksa</span>
                                <?php endif; ?>
                            </div>

                            <p class="muted small">Nomor Antrean Anda</p>
                            <div class="angka-besar">#<?= $tiket['nomor'] ?></div>

                            <h5 class="tebal"><?= htmlspecialchars($tiket['nama_dokter'], ENT_QUOTES, 'UTF-8') ?></h5>
                            <p class="muted small"><?= htmlspecialchars($tiket['spesialis'], ENT_QUOTES, 'UTF-8') ?></p>

                            <hr class="garis">

                            <div class="baris-info small">
                                <div>
                                    <span class="muted">Sedang Dilayani:</span>
                                    <p class="tebal">
                                        <?= $tiket['sedang_panggil'] !== '-' ? '#' . $tiket['sedang_panggil'] : 'Belum dimulai' ?>
                                    </p>
                                </div>
                                <div>
                                    <span class="muted">Antrean di Depan:</span>
                                    <p class="tebal">
                                        <?= $tiket['status'] === 'selesai' ? '-' : (int) $tiket['sisa_di_depan'] . ' orang' ?>
                                    </p>
                                </div>
                            </div>

                            <div class="muted small spasi-atas">
                                Didaftarkan pukul: <strong><?= $tiket['waktu_daftar'] ?> WIB</strong>
                            </div>

                            <?php if ($tiket['status'] === 'menunggu'): ?>
                                <form action="batal_antrian.php" method="POST" class="spasi-atas" onsubmit="return confirm('Batalkan antrean #<?= (int) $tiket['nomor'] ?> ke <?= htmlspecialchars($tiket['nama_dokter'], ENT_QUOTES, 'UTF-8') ?>?');">
                                    <input type="hidden" name="antrian_id" value="<?= (int) $tiket['id'] ?>">
                                    <button type="submit" class="tombol tombol-garis-merah tombol-kecil-btn">Batalkan Antrean</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div id="form-ambil" class="spasi-bawah">
        <div class="baris-judul">
            <div>
                <h3 class="subjudul">Ambil Nomor Antrean Dokter</h3>
                <p class="muted small">Pilih dokter yang bertugas hari ini dan konfirmasi pengambilan nomor.</p>
            </div>
            <div class="baris-aksi">
                <label for="filter-poli-pasien" class="small muted sedang">Poli:</label>
                <select id="filter-poli-pasien" class="masukan masukan-kecil">
                    <option value="">Semua Poli / Spesialis</option>
                    <?php foreach ($daftarSpesialis as $sp): ?>
                        <option value="<?= htmlspecialchars($sp, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($sp, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="kartu">
            <div class="bungkus-tabel">
                <table class="tabel" id="tabel-dokter-pasien">
                    <thead>
                        <tr>
                            <th>Nama Dokter</th>
                            <th>Poli / Spesialis</th>
                            <th>Hari Praktik</th>
                            <th>Sisa Kuota Hari Ini</th>
                            <th>Status Praktik</th>
                            <th class="kanan">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftarPilihanDokter as $dok): ?>
                            <tr data-spesialis="<?= htmlspecialchars($dok['spesialis'], ENT_QUOTES, 'UTF-8') ?>">
                                <td><strong><?= htmlspecialchars($dok['nama'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                <td>
                                    <span class="lencana lencana-netral"><?= htmlspecialchars($dok['spesialis'], ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td class="small muted"><?= htmlspecialchars($dok['hari_praktik'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <strong class="<?= $dok['sisa_kuota'] > 0 ? 'teks-sukses' : 'teks-bahaya' ?>">
                                        <?= (int) $dok['sisa_kuota'] ?>
                                    </strong>
                                    / <?= (int) $dok['kuota_total'] ?>
                                </td>
                                <td>
                                    <?php if (!$dok['apakah_praktik']): ?>
                                        <span class="lencana lencana-abu">Tidak Praktik Hari Ini</span>
                                    <?php elseif ($dok['sisa_kuota'] <= 0): ?>
                                        <span class="lencana lencana-bahaya">Kuota Penuh</span>
                                    <?php else: ?>
                                        <span class="lencana lencana-sukses">Praktik (Tersedia)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="kanan">
                                    <?php if ($dok['sudah_ambil']): ?>
                                        <button type="button" class="tombol tombol-garis-hijau tombol-kecil-btn" disabled>✓ Sudah Ambil</button>
                                    <?php elseif (!$dok['bisa_ambil']): ?>
                                        <button type="button" class="tombol tombol-garis-abu tombol-kecil-btn" disabled>Tidak Tersedia</button>
                                    <?php else: ?>
                                        <form action="ambil_antrian.php" method="POST" style="display:inline;" onsubmit="return confirm('Konfirmasi: Ambil nomor antrean untuk <?= htmlspecialchars($dok['nama'], ENT_QUOTES, 'UTF-8') ?> hari ini?');">
                                            <input type="hidden" name="dokter_id" value="<?= (int) $dok['id'] ?>">
                                            <button type="submit" class="tombol tombol-utama tombol-kecil-btn">Ambil Nomor</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if (!empty($riwayatList)): ?>
        <div class="spasi-bawah">
            <h4 class="subjudul">Riwayat Antrean Sebelumnya</h4>
            <div class="kartu">
                <div class="bungkus-tabel">
                    <table class="tabel">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Dokter</th>
                                <th>Spesialis</th>
                                <th>Nomor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($riwayatList as $rw): ?>
                                <tr>
                                    <td><?= htmlspecialchars($rw['tanggal'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($rw['nama_dokter'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($rw['spesialis'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>#<?= (int) $rw['nomor'] ?></td>
                                    <td>
                                        <span class="lencana lencana-abu"><?= htmlspecialchars($rw['status'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

</main>

<script>
document.querySelectorAll('[data-tutup]').forEach(function (tombol) {
    tombol.addEventListener('click', function () {
        var kotak = tombol.closest('.peringatan');
        if (kotak) {
            kotak.remove();
        }
    });
});
</script>
<script src="pasien.js"></script>
</body>
</html>

