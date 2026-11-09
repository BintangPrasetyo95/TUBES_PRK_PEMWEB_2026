<?php
// guest/dashboard.php
// Dashboard Mode Tamu / Guest (PL-02) - DynaQuiz
// Menampilkan sapaan tamu, pilihan kuis publik, banner ajakan daftar, dan catatan sesi sementara

// 1. Jalankan sesi jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Muat fungsi pembantu
require_once __DIR__ . '/../includes/helpers.php';

// 3. Aturan Akses: Pengguna yang sudah login sebagai akun terdaftar diarahkan ke dashboard masing-masing
if (isset($_SESSION['pengguna']['peran'])) {
    $peran = $_SESSION['pengguna']['peran'];
    if ($peran === 'admin') {
        redirect('../admin/dashboard.php');
    } elseif ($peran === 'pengajar') {
        redirect('../pengajar/dashboard.php');
    } elseif ($peran === 'pelajar') {
        redirect('../pelajar/dashboard.php');
    }
}

// 4. Pastikan data sesi tamu tersedia (jika belum ada, buat nama default)
if (!isset($_SESSION['guest']['username'])) {
    $_SESSION['guest'] = [
        'username' => 'Tamu DynaQuiz'
    ];
}

$namaTamu = $_SESSION['guest']['username'];

// 5. Konfigurasi halaman dan template
$root = '../';
$judulHalaman = 'Dashboard Tamu - DynaQuiz';
$halamanAktif = 'beranda';

// 6. Data Pilihan Kuis Publik (Gunakan fungsi bersama jika sudah ada, atau data kuis contoh)
$daftarKuis = [];
if (function_exists('daftarKuisPublik')) {
    $daftarKuis = daftarKuisPublik();
} else {
    // Data kuis publik contoh siap pakai untuk pengujian tampilan
    $daftarKuis = [
        [
            'id' => 1,
            'judul' => 'Kuis Logika Matematika Dasar',
            'topik' => 'Matematika',
            'tingkat' => 'SMA / Umum',
            'jumlah_soal' => 10,
            'durasi' => 15,
            'kesulitan' => 'mudah'
        ],
        [
            'id' => 2,
            'judul' => 'Pemahaman Tata Bahasa & Ejaan',
            'topik' => 'Bahasa Indonesia',
            'tingkat' => 'SMP / SMA',
            'jumlah_soal' => 15,
            'durasi' => 20,
            'kesulitan' => 'sedang'
        ],
        [
            'id' => 3,
            'judul' => 'Konsep Logika Pemrograman Web',
            'topik' => 'Informatika',
            'tingkat' => 'Umum',
            'jumlah_soal' => 10,
            'durasi' => 15,
            'kesulitan' => 'sulit'
        ]
    ];
}

// 7. Muat header layout Pelajar & Guest
require_once __DIR__ . '/../includes/partials/header-pelajar.php';
?>

<!-- Sapaan Pengguna Tamu -->
<div class="card" style="margin: 28px 0; padding: 28px 24px;">
  <span class="badge badge-sedang" style="margin-bottom: 12px;">Mode Tamu (Guest)</span>
  <h1 style="font-size: 2rem; margin-bottom: 8px;">
    Halo, <span><?= e($namaTamu) ?></span>! &#128075;
  </h1>
  <p style="color: var(--teks); font-size: 1.05rem;">
    Selamat datang di DynaQuiz. Kamu bisa langsung mencoba latihan soal pilihan ganda di bawah ini tanpa perlu login.
  </p>
</div>

<!-- Catatan Informasi Mode Tamu (HSL-07) -->
<div class="alert alert-info" style="margin-bottom: 28px;">
  <span style="font-size: 1.4rem;" aria-hidden="true">&#9432;</span>
  <div>
    <strong>Catatan Mode Tamu:</strong> Skor dan riwayat pengerjaan hanya disimpan sementara di sesi browser ini. Jika ingin nilaimu tercatat permanen dan masuk ke papan peringkat, silakan daftarkan akun Pelajar.
  </div>
</div>

<!-- Banner Ajakan Mendaftar Akun Pelajar (HSL-07) -->
<div class="card" style="padding: 28px; margin-bottom: 36px; border: 2px dashed var(--teks-2); background-color: var(--kartu);">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
    <div style="max-width: 600px;">
      <h2 style="font-size: 1.4rem; margin-bottom: 8px;">Simpan Progres Belajarmu Secara Permanen!</h2>
      <p style="color: var(--teks); font-size: 0.95rem; margin: 0;">
        Daftar akun Pelajar gratis untuk mencatat seluruh riwayat pengerjaan, mempertahankan streak belajar harian, dan bersaing di papan peringkat siswa.
      </p>
    </div>
    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
      <a href="<?= $root ?>register.php" class="btn btn-primary" style="text-decoration: none;">
        &#128221; Daftar Akun Pelajar
      </a>
      <a href="<?= $root ?>login.php" class="btn btn-outline" style="text-decoration: none;">
        &#128273; Masuk Akun
      </a>
    </div>
  </div>
</div>

<!-- Bagian Pilihan Kuis Publik (HSL-07) -->
<div style="margin-bottom: 40px;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
    <div>
      <h2 style="font-size: 1.5rem; margin-bottom: 4px;">Pilihan Kuis Publik</h2>
      <p style="color: var(--teks-2); font-size: 0.95rem; margin: 0;">Pilih kuis yang ingin kamu coba kerjakan hari ini:</p>
    </div>
    <a href="<?= $root ?>kuis/katalog.php" class="btn btn-sm btn-outline" style="text-decoration: none;">
      Lihat Semua di Katalog &rarr;
    </a>
  </div>

  <!-- Grid Bento Kartu Kuis Publik -->
  <div class="bento-grid">
    <?php foreach ($daftarKuis as $kuis): ?>
      <?php
      // Tentukan warna badge kesulitan
      $kelasKesulitan = 'badge-mudah';
      if ($kuis['kesulitan'] === 'sedang') {
          $kelasKesulitan = 'badge-sedang';
      } elseif ($kuis['kesulitan'] === 'sulit') {
          $kelasKesulitan = 'badge-sulit';
      }
      ?>
      <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
        <div>
          <!-- Kategori & Tingkat Kesulitan -->
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; gap: 8px;">
            <span class="badge badge-sedang"><?= e($kuis['topik']) ?></span>
            <span class="badge <?= $kelasKesulitan ?>"><?= ucfirst(e($kuis['kesulitan'])) ?></span>
          </div>

          <!-- Judul Kuis -->
          <h3 class="card-title" style="margin-bottom: 8px;">
            <?= e($kuis['judul']) ?>
          </h3>

          <!-- Informasi Jumlah Soal dan Durasi -->
          <p style="font-size: 0.9rem; color: var(--teks-2); margin-bottom: 20px;">
            &#128221; <?= (int)$kuis['jumlah_soal'] ?> Soal &bull; &#9201; <?= (int)$kuis['durasi'] ?> Menit &bull; <?= e($kuis['tingkat']) ?>
          </p>
        </div>

        <!-- Tombol Aksi Mulai Kuis -->
        <div>
          <a href="<?= $root ?>kuis/detail.php?id=<?= (int)$kuis['id'] ?>" class="btn btn-block btn-primary" style="text-decoration: none;">
            Mulai Kerjakan &rarr;
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php
// 8. Muat penutup footer layout Pelajar & Guest
require_once __DIR__ . '/../includes/partials/footer-pelajar.php';
?>
