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

// Proses saat form dikirim (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cek token CSRF
    if (function_exists('verifikasiCsrf')) {
        verifikasiCsrf();
    }

    // Ambil input dari form
    $nilaiForm['judul']        = trim($_POST['judul'] ?? '');
    $nilaiForm['deskripsi']    = trim($_POST['deskripsi'] ?? '');
    $nilaiForm['topik_id']     = (int) ($_POST['topik_id'] ?? 0);
    $nilaiForm['tingkat_id']   = (int) ($_POST['tingkat_id'] ?? 0);
    $nilaiForm['durasi_menit'] = (int) ($_POST['durasi_menit'] ?? 15);
    $nilaiForm['akses']        = $_POST['akses'] ?? 'publik';

    // Validasi per kolom supaya error bisa ditampilkan di bawah masing-masing input
    if ($nilaiForm['judul'] === '') {
        $errors['judul'] = 'Judul kuis wajib diisi.';
    } elseif (strlen($nilaiForm['judul']) < 3 || strlen($nilaiForm['judul']) > 150) {
        $errors['judul'] = 'Judul kuis harus 3 sampai 150 karakter.';
    }

    if ($nilaiForm['topik_id'] <= 0) {
        $errors['topik_id'] = 'Silakan pilih topik kuis.';
    }

    if ($nilaiForm['tingkat_id'] <= 0) {
        $errors['tingkat_id'] = 'Silakan pilih tingkat jenjang.';
    }

    if ($nilaiForm['durasi_menit'] < 1 || $nilaiForm['durasi_menit'] > 240) {
        $errors['durasi_menit'] = 'Durasi pengerjaan harus antara 1 sampai 240 menit.';
    }

    if ($nilaiForm['akses'] !== 'publik' && $nilaiForm['akses'] !== 'privat') {
        $errors['akses'] = 'Pilih jenis akses publik atau privat.';
    }

    // Kalau tidak ada error validasi, simpan ke database
    if (empty($errors)) {
        if ($modeEdit) {
            $hasil = editKuis($kuisId, $pengajarId, $nilaiForm);
            if ($hasil['berhasil']) {
                if (function_exists('flash')) {
                    flash('sukses', 'Perubahan kuis berhasil disimpan.');
                }

                // Cek apakah pengajar menekan tombol lanjut kelola soal
                $tujuan = isset($_POST['lanjut_soal'])
                    ? 'soal.php?kuis_id=' . $kuisId
                    : 'kuis.php';

                if (function_exists('redirect')) {
                    redirect('pengajar/' . $tujuan);
                }
                header('Location: ' . $tujuan);
                exit;
            } else {
                $pesanErrorUmum = $hasil['pesan'] ?? 'Gagal memperbarui kuis.';
            }
        } else {
            $hasil = tambahKuis($pengajarId, $nilaiForm);
            if ($hasil['berhasil']) {
                $idBaru = (int) $hasil['kuis_id'];
                if (function_exists('flash')) {
                    flash('sukses', 'Draft kuis berhasil dibuat. Silakan tambahkan soal.');
                }

                // Setelah buat kuis baru, mengarah ke halaman kelola soal
                $tujuan = 'soal.php?kuis_id=' . $idBaru;
                if (function_exists('redirect')) {
                    redirect('pengajar/' . $tujuan);
                }
                header('Location: ' . $tujuan);
                exit;
            } else {
                $pesanErrorUmum = $hasil['pesan'] ?? 'Gagal membuat kuis baru.';
            }
        }
    }
}
