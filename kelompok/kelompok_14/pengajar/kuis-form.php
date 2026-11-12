<?php
// PJ-03: Form Buat dan Edit Kuis untuk Pengajar

// Muat file konfigurasi dan helper
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/fungsi/kuis.php';

// Pastikan yang mengakses halaman ini adalah pengajar
wajibPeran(['pengajar']);

// Ambil id pengajar yang sedang login dari session
$pengajarId = (int) $_SESSION['user']['id'];

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
if (isPost()) {
    // Cek token CSRF
    verifikasiCsrf();

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
                    ? 'kelola-soal.php?kuis_id=' . $kuisId
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
                $tujuan = 'kelola-soal.php?kuis_id=' . $idBaru;
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

// ===== Bagian tampilan halaman (HTML) =====
$judulHalaman = $modeEdit ? 'Edit Kuis' : 'Buat Kuis Baru';

// Kalau kuis pernah ditolak atau ditarik, ambil alasannya untuk ditampilkan
$catatanRevisi = '';
if ($modeEdit && ($kuis['status'] == 'ditolak' || $kuis['status'] == 'ditarik')) {
    $catatanRevisi = ambilCatatanStatusTerakhir($kuisId);
}

// Ambil token CSRF dari file csrf.php
$tokenCsrf = ambilTokenCsrf();

// Muat header dan sidebar jika sudah dibuat oleh Farel
if (file_exists(__DIR__ . '/../includes/partials/header.php')) {
    include __DIR__ . '/../includes/partials/header.php';
}
if (file_exists(__DIR__ . '/../includes/partials/sidebar.php')) {
    include __DIR__ . '/../includes/partials/sidebar.php';
}
?>
<main class="konten-panel">
    <h1><?= e($judulHalaman) ?></h1>

    <?php if ($pesanErrorUmum !== '') { ?>
        <div class="pesan pesan-galat" role="alert">
            <?= e($pesanErrorUmum) ?>
        </div>
    <?php } ?>

    <?php if ($catatanRevisi !== '') { ?>
        <div class="pesan pesan-info" role="status">
            <strong>
                Kuis ini <?= ($kuis['status'] == 'ditolak') ? 'ditolak' : 'ditarik' ?> oleh admin.
            </strong>
            <p>Catatan: <?= e($catatanRevisi) ?></p>
            <p>Silakan perbaiki kuis lalu ajukan kembali.</p>
        </div>
    <?php } ?>

    <form method="post" action="" class="form-kuis" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($tokenCsrf) ?>">

        <!-- Judul -->
        <div class="kolom-form">
            <label for="judul">Judul Kuis</label>
            <input type="text" id="judul" name="judul" maxlength="150"
                   value="<?= e($nilaiForm['judul']) ?>" required>
            <?php if (isset($errors['judul'])) { ?>
                <p class="teks-galat"><?= e($errors['judul']) ?></p>
            <?php } ?>
        </div>

        <!-- Deskripsi -->
        <div class="kolom-form">
            <label for="deskripsi">Deskripsi (boleh dikosongkan)</label>
            <textarea id="deskripsi" name="deskripsi" rows="4"><?= e($nilaiForm['deskripsi']) ?></textarea>
        </div>

        <!-- Topik -->
        <div class="kolom-form">
            <label for="topik_id">Topik</label>
            <select id="topik_id" name="topik_id" required>
                <option value="">-- Pilih topik --</option>
                <?php foreach ($daftarTopik as $topik) { ?>
                    <option value="<?= e($topik['id']) ?>"
                        <?= ($nilaiForm['topik_id'] == $topik['id']) ? 'selected' : '' ?>>
                        <?= e($topik['nama']) ?>
                    </option>
                <?php } ?>
            </select>
            <?php if (isset($errors['topik_id'])) { ?>
                <p class="teks-galat"><?= e($errors['topik_id']) ?></p>
            <?php } ?>
        </div>

        <!-- Tingkat -->
        <div class="kolom-form">
            <label for="tingkat_id">Tingkat</label>
            <select id="tingkat_id" name="tingkat_id" required>
                <option value="">-- Pilih tingkat --</option>
                <?php foreach ($daftarTingkat as $tingkat) { ?>
                    <option value="<?= e($tingkat['id']) ?>"
                        <?= ($nilaiForm['tingkat_id'] == $tingkat['id']) ? 'selected' : '' ?>>
                        <?= e($tingkat['nama']) ?>
                    </option>
                <?php } ?>
            </select>
            <?php if (isset($errors['tingkat_id'])) { ?>
                <p class="teks-galat"><?= e($errors['tingkat_id']) ?></p>
            <?php } ?>
        </div>

        <!-- Durasi -->
        <div class="kolom-form">
            <label for="durasi_menit">Durasi (menit)</label>
            <input type="number" id="durasi_menit" name="durasi_menit" min="1" max="240"
                   value="<?= e($nilaiForm['durasi_menit']) ?>" required>
            <?php if (isset($errors['durasi_menit'])) { ?>
                <p class="teks-galat"><?= e($errors['durasi_menit']) ?></p>
            <?php } ?>
        </div>

        <!-- Jenis akses -->
        <div class="kolom-form">
            <p>Jenis Akses</p>
            <label for="akses_publik">
                <input type="radio" id="akses_publik" name="akses" value="publik"
                    <?= ($nilaiForm['akses'] == 'publik') ? 'checked' : '' ?>>
                Publik (tampil di katalog)
            </label>
            <label for="akses_privat">
                <input type="radio" id="akses_privat" name="akses" value="privat"
                    <?= ($nilaiForm['akses'] == 'privat') ? 'checked' : '' ?>>
                Privat (dibuka dengan kode kuis)
            </label>
            <?php if (isset($errors['akses'])) { ?>
                <p class="teks-galat"><?= e($errors['akses']) ?></p>
            <?php } ?>
        </div>

        <!-- Kode kuis hanya tampil jika kuis privat sudah punya kode -->
        <?php if ($modeEdit && $nilaiForm['akses'] == 'privat' && $nilaiForm['kode'] !== '') { ?>
            <div class="kolom-form kotak-kode">
                <p>Kode Kuis (bagikan ke pelajar)</p>
                <p class="kode-kuis"><strong><?= e($nilaiForm['kode']) ?></strong></p>
            </div>
        <?php } elseif ($nilaiForm['akses'] == 'privat') { ?>
            <p class="teks-bantu">Kode kuis akan dibuat otomatis setelah kuis disimpan.</p>
        <?php } ?>

        <div class="baris-tombol">
            <a href="kuis.php" class="tombol tombol-sekunder">Batal</a>
            <button type="submit" name="simpan" value="1" class="tombol tombol-utama">Simpan</button>
            <?php if ($modeEdit) { ?>
                <button type="submit" name="lanjut_soal" value="1" class="tombol tombol-aksen">
                    Simpan &amp; Kelola Soal
                </button>
            <?php } ?>
        </div>
    </form>
</main>
<?php
if (file_exists(__DIR__ . '/../includes/partials/footer.php')) {
    include __DIR__ . '/../includes/partials/footer.php';
}
