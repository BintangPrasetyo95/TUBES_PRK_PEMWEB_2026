<?php
// Halaman PJ-05: Pratinjau Kuis (Pengajar)

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/fungsi/kuis.php';
require_once __DIR__ . '/../includes/fungsi/soal.php';

// Hanya pengajar yang boleh mengakses halaman pratinjau ini
wajibPeran(['pengajar']);

$pengajarId = (int) $_SESSION['user']['id'];

// Ambil ID kuis dari URL (?id=)
$kuisId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Ambil data kuis dan pastikan kuis tersebut milik pengajar yang login
$kuis = ambilKuisById($kuisId, $pengajarId);
if (!$kuis) {
    http_response_code(404);
    require_once __DIR__ . '/../404.php';
    exit;
}

// Ambil seluruh daftar soal beserta opsi jawabannya (sudah termasuk kunci & pembahasan karena milik pengajar sendiri)
$daftarSoal = ambilSoalByKuis($kuisId);
$totalSoal  = count($daftarSoal);

// Siapkan variabel untuk header-fokus.php
$root         = '../';
$judulHalaman = 'Pratinjau: ' . $kuis['judul'] . ' - DynaQuiz';
$judulKuis    = $kuis['judul'];
$sisaWaktu    = sprintf('%02d:00', (int) $kuis['durasi_menit']);
$linkKeluar   = 'kuis.php';

// Muat header mode
require_once __DIR__ . '/../includes/partials/header-fokus.php';
?>
