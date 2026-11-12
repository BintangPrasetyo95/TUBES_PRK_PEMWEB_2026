<?php
// index.php
// Halaman Utama / Landing Page (PB-01) - DynaQuiz

// 1. Jalankan sesi jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Muat fungsi pembantu
require_once __DIR__ . '/includes/helpers.php';

// 3. Aturan AUT-06: Jika pengguna sudah login, arahkan langsung ke dashboard masing-masing
$sesiUser = $_SESSION['user'] ?? $_SESSION['pengguna'] ?? null;
if ($sesiUser && isset($sesiUser['peran'])) {
    $peran = $sesiUser['peran'];
    if ($peran === 'admin') {
        redirect('admin/dashboard.php');
    } elseif ($peran === 'pengajar') {
        redirect('pengajar/dashboard.php');
    } elseif ($peran === 'pelajar') {
        redirect('pelajar/dashboard.php');
    } elseif ($peran === 'guest') {
        redirect('guest/dashboard.php');
    }
}

// 4. Konfigurasi halaman dan template publik
$root = './';
$judulHalaman = 'DynaQuiz - Platform Latihan Soal Interaktif';
$halamanAktif = 'beranda';

// 5. Muat header layout publik
require_once __DIR__ . '/includes/partials/header-publik.php';
?>

<!-- Bagian Hero Banner Landing Page -->
<div class="landing-hero card">
  <span class="badge badge-sedang landing-badge">Tugas Akhir Pemrograman Web 2026 &bull; Kelompok 14</span>
  <h1 class="landing-judul">
    Latihan Soal Jadi Lebih Asyik di <span>DynaQuiz</span>!
  </h1>
  <p class="landing-deskripsi">
    Platform kuis dan latihan soal interaktif dengan umpan balik seketika, timer pengerjaan, pembahasan mendalam, serta papan peringkat untuk menemani proses belajarmu.
  </p>

  <!-- Tombol Aksi Utama (Call to Action) -->
  <div class="landing-cta">
    <a href="guest.php" class="btn btn-primary">
      &#128640; Coba Langsung (Mode Tamu)
    </a>
    <a href="login.php" class="btn btn-secondary">
      &#128273; Masuk Akun
    </a>
    <a href="register.php" class="btn btn-outline">
      &#128221; Daftar Pelajar
    </a>
  </div>
</div>

<!-- Grid Bento Fitur Unggulan DynaQuiz -->
<div class="section-fitur">
  <div class="fitur-header">
    <h2 class="fitur-judul">Kenapa Belajar di DynaQuiz?</h2>
    <p class="fitur-deskripsi">Dirancang khusus dengan nuansa Claymorphism yang ramah, santai, dan interaktif.</p>
  </div>

  <div class="bento-grid">
    <!-- Fitur 1: Interaktif & Timer -->
    <div class="card">
      <div class="fitur-ikon" aria-hidden="true">&#9201;</div>
      <h3 class="card-title">Timer &amp; Pembahasan Instan</h3>
      <p>
        Kerjakan soal pilihan ganda dengan hitung mundur waktu. Langsung dapatkan skor dan pembahasan lengkap setelah menjawab tiap soal.
      </p>
    </div>

    <!-- Fitur 2: 4 Pilihan Tema Pastel -->
    <div class="card">
      <div class="fitur-ikon" aria-hidden="true">&#127912;</div>
      <h3 class="card-title">4 Pilihan Tema Pastel</h3>
      <p>
        Sesuaikan gaya tampilan favoritmu: <em>Sky Bubblegum, Lavender Dream, Mint Cloud,</em> atau <em>Peach Pop</em> tanpa kedip layar.
      </p>
    </div>

    <!-- Fitur 3: Papan Peringkat & Riwayat -->
    <div class="card">
      <div class="fitur-ikon" aria-hidden="true">&#127942;</div>
      <h3 class="card-title">Peringkat &amp; Riwayat Skor</h3>
      <p>
        Pantau performamu, pertahankan streak belajar harian, dan bersaing secara sehat dengan pelajar lainnya di leaderboard.
      </p>
    </div>

    <!-- Fitur 4: Kuis Terverifikasi -->
    <div class="card">
      <div class="fitur-ikon" aria-hidden="true">&#128218;</div>
      <h3 class="card-title">Kualitas Soal Terjaga</h3>
      <p>
        Kuis disusun langsung oleh pengajar terdaftar dan melalui kurasi moderasi admin sebelum dapat diakses di katalog publik.
      </p>
    </div>

    <!-- Fitur 5: Mode Tamu Praktis -->
    <div class="card">
      <div class="fitur-ikon" aria-hidden="true">&#10024;</div>
      <h3 class="card-title">Mode Tamu Tanpa Ribet</h3>
      <p>
        Ingin langsung berlatih tanpa mendaftar akun? Cukup masukkan nama panggilan dan kamu bisa langsung mencoba kuis publik.
      </p>
    </div>

    <!-- Fitur 6: Aksesibilitas Mobile-First -->
    <div class="card">
      <div class="fitur-ikon" aria-hidden="true">&#128241;</div>
      <h3 class="card-title">Nyaman di Semua Perangkat</h3>
      <p>
        Tampilan mobile-first yang responsif dari layar smartphone 360 px hingga desktop dengan kontras warna yang nyaman di mata.
      </p>
    </div>
  </div>
</div>

<!-- Banner Ajakan Bergabung -->
<div class="card cta-bawah">
  <h2 class="cta-bawah-judul">Mulai Latihan Pertamamu Sekarang</h2>
  <p class="cta-bawah-deskripsi">
    Pilih kuis yang kamu sukai, uji pemahaman materi, dan lihat seberapa jauh skor belajarmu berkembang!
  </p>
  <a href="guest.php" class="btn btn-primary">
    Masuk Mode Tamu Sekarang &rarr;
  </a>
</div>

<?php
// 6. Muat penutup footer layout publik
require_once __DIR__ . '/includes/partials/footer-publik.php';
?>
