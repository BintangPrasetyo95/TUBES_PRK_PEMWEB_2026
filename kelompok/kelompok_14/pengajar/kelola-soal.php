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
        $halamanTujuan = 'kelola-soal.php?kuis_id=' . $kuisId;

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
        } elseif ($aksi == 'ajukan') {
            // Ajukan publish: kalau berhasil, pindah ke halaman Kuis Saya
            $hasil = ajukanPublishKuis($kuisId, $pengajarId);
            $pesanSukses = 'Kuis berhasil diajukan, tunggu persetujuan admin.';
            $halamanTujuan = 'kuis.php';
        }

        if ($hasil != null) {
            if ($hasil['berhasil']) {
                // Berhasil: simpan pesan lalu muat ulang halaman supaya data terbaru tampil
                if (function_exists('flash')) {
                    flash('sukses', $pesanSukses);
                }
                header('Location: ' . $halamanTujuan);
                exit;
            } else {
                $pesanError = $hasil['pesan'] ?? 'Terjadi kesalahan.';
                // Kalau ajukan gagal, ada daftar alasan kenapa kuis belum lengkap
                if (isset($hasil['errors'])) {
                    $daftarError = $hasil['errors'];
                }
            }
        }

        // Ambil ulang data soal dan cek kelengkapan untuk jaga-jaga
        $daftarSoal = ambilSoalByKuis($kuisId);
        $kelengkapan = validasiKelengkapanKuis($kuisId);
    }
}

// Bagian tampilan halaman (HTML)
$judulHalaman = 'Kelola Soal';

// Ambil token CSRF
$tokenCsrf = '';
if (function_exists('csrfToken')) {
    $tokenCsrf = csrfToken();
} elseif (isset($_SESSION['csrf_token'])) {
    $tokenCsrf = $_SESSION['csrf_token'];
}

// Muat header dan sidebars
if (file_exists(__DIR__ . '/../includes/partials/header.php')) {
    include __DIR__ . '/../includes/partials/header.php';
}
if (file_exists(__DIR__ . '/../includes/partials/sidebar.php')) {
    include __DIR__ . '/../includes/partials/sidebar.php';
}
?>
<main class="konten-panel">
    <h1><?= e($judulHalaman) ?></h1>
    <p>
        Kuis: <strong><?= e($kuis['judul']) ?></strong>
        (status: <?= e($kuis['status']) ?>)
    </p>

    <?php if ($pesanError !== '') { ?>
        <div class="pesan pesan-galat" role="alert">
            <p><?= e($pesanError) ?></p>
            <?php if (!empty($daftarError)) { ?>
                <ul>
                    <?php foreach ($daftarError as $alasan) { ?>
                        <li><?= e($alasan) ?></li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>
    <?php } ?>

    <?php if ($terkunci) { ?>
        <div class="pesan pesan-info" role="status">
            Kuis ini sedang menunggu persetujuan atau sudah terbit, jadi soal hanya bisa dilihat.
        </div>
    <?php } ?>

    <!-- Indikator kelengkapan kuis -->
    <section class="kotak-kelengkapan">
        <h2>Kelengkapan Kuis</h2>
        <p>
            Jumlah soal: <strong><?= e($kelengkapan['jumlah_soal']) ?></strong>,
            soal lengkap: <strong><?= e($kelengkapan['soal_lengkap']) ?></strong>
        </p>
        <?php if ($kelengkapan['lengkap']) { ?>
            <p class="teks-sukses">&#10003; Kuis sudah lengkap dan siap diajukan.</p>
        <?php } else { ?>
            <p class="teks-galat">&#10007; Kuis belum lengkap:</p>
            <ul>
                <?php foreach ($kelengkapan['errors'] as $alasan) { ?>
                    <li><?= e($alasan) ?></li>
                <?php } ?>
            </ul>
        <?php } ?>
    </section>

    <!-- Daftar soal -->
    <section class="daftar-soal">
        <h2>Daftar Soal</h2>

        <?php if (empty($daftarSoal)) { ?>
            <p>Belum ada soal. Silakan tambah soal pertama kamu.</p>
        <?php } ?>

        <?php foreach ($daftarSoal as $soal) { ?>
            <article class="kartu-soal">
                <h3>Soal <?= e($soal['urutan']) ?> (<?= e($soal['poin']) ?> poin)</h3>
                <p><?= e($soal['pertanyaan']) ?></p>

                <ul>
                    <?php foreach ($soal['opsi'] as $opsi) { ?>
                        <li>
                            <?= e($opsi['label']) ?>. <?= e($opsi['teks']) ?>
                            <?php if ($opsi['benar'] == 1) { ?>
                                <strong>(kunci jawaban)</strong>
                            <?php } ?>
                        </li>
                    <?php } ?>
                </ul>

                <?php if (!empty($soal['pembahasan'])) { ?>
                    <p>Pembahasan: <?= e($soal['pembahasan']) ?></p>
                <?php } ?>

                <?php if (!$terkunci) { ?>
                    <div class="baris-tombol">
                        <a href="kelola-soal.php?kuis_id=<?= e($kuisId) ?>&amp;edit=<?= e($soal['id']) ?>"
                           class="tombol tombol-sekunder">Edit</a>

                        <!-- Tombol naik, turun, dan hapus memakai form POST kecil -->
                        <form method="post" action="">
                            <input type="hidden" name="csrf_token" value="<?= e($tokenCsrf) ?>">
                            <input type="hidden" name="soal_id" value="<?= e($soal['id']) ?>">
                            <button type="submit" name="aksi" value="naik" class="tombol">Naik</button>
                            <button type="submit" name="aksi" value="turun" class="tombol">Turun</button>
                            <button type="submit" name="aksi" value="hapus" class="tombol tombol-berisiko">Hapus</button>
                        </form>
                    </div>
                <?php } ?>
            </article>
        <?php } ?>
    </section>

    <!-- Tombol ajukan publish -->
    <?php if (!$terkunci) { ?>
        <form method="post" action="" class="form-ajukan">
            <input type="hidden" name="csrf_token" value="<?= e($tokenCsrf) ?>">
            <button type="submit" name="aksi" value="ajukan" class="tombol tombol-utama">
                Ajukan Publish
            </button>
        </form>
    <?php } ?>

    <p><a href="kuis.php">&laquo; Kembali ke Kuis Saya</a></p>
</main>
<?php
if (file_exists(__DIR__ . '/../includes/partials/footer.php')) {
    include __DIR__ . '/../includes/partials/footer.php';
}
