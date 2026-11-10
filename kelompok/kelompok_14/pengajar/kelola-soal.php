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

// Nilai form soal (dipakai lagi jika ada error supaya isian tidak hilang)
$nilaiSoal = [
    'pertanyaan' => '',
    'pembahasan' => '',
    'poin'       => 10,
    'opsi'       => ['A' => '', 'B' => '', 'C' => '', 'D' => '', 'E' => ''],
    'kunci'      => ''
];

// Proses saat ada form yang dikirim (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (function_exists('verifikasiCsrf')) {
        verifikasiCsrf();
    }

    $aksi = $_POST['aksi'] ?? '';
    $soalId = (int) ($_POST['soal_id'] ?? 0);

    // Kuis yang terkunci tidak boleh diubah
    if ($terkunci) {
        $pesanError = 'Kuis sedang menunggu persetujuan atau sudah terbit, jadi tidak bisa diubah.';
    } else {
        // Ambil isian form soal
        $nilaiSoal['pertanyaan'] = trim($_POST['pertanyaan'] ?? '');
        $nilaiSoal['pembahasan'] = trim($_POST['pembahasan'] ?? '');
        $nilaiSoal['poin']       = (int) ($_POST['poin'] ?? 10);
        $nilaiSoal['kunci']      = $_POST['kunci'] ?? '';

        $opsiPost = $_POST['opsi'] ?? [];
        foreach ($nilaiSoal['opsi'] as $huruf => $teks) {
            $nilaiSoal['opsi'][$huruf] = trim($opsiPost[$huruf] ?? '');
        }

        $hasil = null;
        $pesanSukses = '';

        if ($aksi == 'tambah') {
            $hasil = tambahSoal($kuisId, $pengajarId, $nilaiSoal);
            $pesanSukses = 'Soal berhasil ditambahkan.';
        } elseif ($aksi == 'edit') {
            $hasil = editSoal($soalId, $kuisId, $pengajarId, $nilaiSoal);
            $pesanSukses = 'Soal berhasil diubah.';
        } elseif ($aksi == 'hapus') {
            $hasil = hapusSoal($soalId, $kuisId, $pengajarId);
            $pesanSukses = 'Soal berhasil dihapus.';
        } elseif ($aksi == 'naik' || $aksi == 'turun') {
            $hasil = geserUrutanSoal($soalId, $kuisId, $pengajarId, $aksi);
            $pesanSukses = 'Urutan soal berhasil diubah.';
        }

        if ($hasil != null) {
            if ($hasil['berhasil']) {
                // Berhasil: simpan pesan lalu muat ulang halaman supaya data terbaru tampil
                if (function_exists('flash')) {
                    flash('sukses', $pesanSukses);
                }
                header('Location: kelola-soal.php?kuis_id=' . $kuisId);
                exit;
            } else {
                $pesanError = $hasil['pesan'] ?? 'Terjadi kesalahan.';
            }
        }

        // Ambil ulang data soal untuk jaga-jaga
        $daftarSoal = ambilSoalByKuis($kuisId);
    }
}
