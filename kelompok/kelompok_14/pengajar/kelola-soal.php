<?php
// PJ-04: Kelola Soal (tambah, edit, hapus, urutkan soal, dan ajukan publish)

// Muat file
if (file_exists(__DIR__ . '/../includes/config.php')) {
    require_once __DIR__ . '/../includes/config.php';
}
if (file_exists(__DIR__ . '/../includes/db.php')) {
    require_once __DIR__ . '/../includes/db.php';
}
if (file_exists(__DIR__ . '/../includes/auth.php')) {
    require_once __DIR__ . '/../includes/auth.php';
}
if (file_exists(__DIR__ . '/../includes/helpers.php')) {
    require_once __DIR__ . '/../includes/helpers.php';
}
if (file_exists(__DIR__ . '/../includes/csrf.php')) {
    require_once __DIR__ . '/../includes/csrf.php';
}

require_once __DIR__ . '/../includes/fungsi/kuis.php';
require_once __DIR__ . '/../includes/fungsi/soal.php';

// Fungsi e() sementara 
if (!function_exists('e')) {
    function e($teks) {
        return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
    }
}

// Hanya pengajar yang boleh membuka halaman ini
if (function_exists('wajibPeran')) {
    wajibPeran(['pengajar']);
}

$pengajarId = $_SESSION['user']['id'] ?? ($_SESSION['pengajar_id'] ?? 0);

// Ambil id kuis dari URL 
$kuisId = isset($_GET['kuis_id']) ? (int) $_GET['kuis_id'] : 0;

// Cek kuis ada dan milik pengajar ini
$kuis = ambilKuisById($kuisId, $pengajarId);
if (!$kuis) {
    http_response_code(404);
    if (file_exists(__DIR__ . '/../404.php')) {
        require __DIR__ . '/../404.php';
        exit;
    }
    die('Kuis tidak ditemukan atau Anda tidak memiliki akses.');
}

// Kuis yang menunggu persetujuan atau sudah terbit dikunci (hanya boleh dilihat)
$terkunci = false;
if ($kuis['status'] == 'menunggu' || $kuis['status'] == 'terbit') {
    $terkunci = true;
}

// Ambil data soal dan cek kelengkapan kuis
$daftarSoal = ambilSoalByKuis($kuisId);
$kelengkapan = validasiKelengkapanKuis($kuisId);

$pesanError = '';
$daftarError = [];
