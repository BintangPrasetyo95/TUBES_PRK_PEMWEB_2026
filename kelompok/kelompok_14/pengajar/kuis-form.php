<?php
// PJ-03: Form Buat dan Edit Kuis untuk Pengajar

// Muat file konfigurasi dan helper 
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

// Fallback helper sederhana jika file dari anggota lain belum digabung
if (!function_exists('e')) {
    function e($teks) {
        return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
    }
}

// Pastikan yang mengakses halaman ini adalah pengajar
if (function_exists('wajibPeran')) {
    wajibPeran(['pengajar']);
}

// Ambil id pengajar yang sedang login dari session
$pengajarId = $_SESSION['user']['id'] ?? ($_SESSION['pengajar_id'] ?? 0);

// Cek apakah ini mode Edit (ada parameter ?id= di URL) atau mode Buat Baru
$kuisId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$modeEdit = $kuisId > 0;
$kuis = null;

if ($modeEdit) {
    $kuis = ambilKuisById($kuisId, $pengajarId);

    // Kalau kuis tidak ditemukan atau bukan milik pengajar ini, tampilkan 404 / tolak akses
    if (!$kuis) {
        http_response_code(404);
        if (file_exists(__DIR__ . '/../404.php')) {
            require __DIR__ . '/../404.php';
            exit;
        }
        die('Kuis tidak ditemukan atau Anda tidak memiliki akses.');
    }

    // Kuis yang sedang menunggu persetujuan atau sudah terbit tidak boleh diedit
    if ($kuis['status'] == 'menunggu' || $kuis['status'] == 'terbit') {
        if (function_exists('flash')) {
            flash('error', 'Kuis yang sedang menunggu persetujuan atau sudah terbit tidak dapat diedit.');
        }
        if (function_exists('redirect')) {
            redirect('pengajar/kuis.php');
        }
        header('Location: kuis.php');
        exit;
    }
}

// Ambil daftar topik dan tingkat untuk pilihan dropdown di form
$daftarTopik = ambilSemuaTopik();
$daftarTingkat = ambilSemuaTingkat();

// Nilai awal untuk diisikan ke kolom-kolom form
$nilaiForm = [
    'judul'        => $kuis['judul'] ?? '',
    'deskripsi'    => $kuis['deskripsi'] ?? '',
    'topik_id'     => $kuis['topik_id'] ?? '',
    'tingkat_id'   => $kuis['tingkat_id'] ?? '',
    'durasi_menit' => $kuis['durasi_menit'] ?? 15,
    'akses'        => $kuis['akses'] ?? 'publik',
    'kode'         => $kuis['kode'] ?? ''
];

$errors = [];
$pesanErrorUmum = '';
