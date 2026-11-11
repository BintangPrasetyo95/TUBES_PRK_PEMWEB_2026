<?php
// ============================================================================
// Halaman Kerjakan Kuis (PL-05 - kuis/kerjakan.php Mode Fokus)
// Kelompok 14 - Tugas Besar Praktikum Pemrograman Web 2026
// PIC: Wulan (Sesi 5 - PL-05 Server & CSS)
//
// Aturan & Fitur (KRJ-01 s.d. KRJ-10, SEC-07, AC-12, AC-13, AC-24):
// 1. Mode Fokus tanpa navbar utama dan tanpa pemilih tema
// 2. Bar atas: Timer countdown server, tombol keluar dengan dialog konfirmasi
// 3. Progress bar menunjukkan persentase soal yang sudah terjawab
// 4. Kartu soal satu per layar dengan pilihan jawaban (A, B, C, D)
// 5. HTML awal TIDAK membocorkan kunci jawaban untuk soal yang belum dijawab (SEC-07)
// 6. Refresh tidak mengulang timer dan memuat ulang jawaban yang sudah terkirim (AC-12)
// 7. Grid nomor soal untuk navigasi cepat antar soal
// ============================================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/fungsi/percobaan.php';
require_once __DIR__ . '/../includes/fungsi/soal.php';
require_once __DIR__ . '/../includes/fungsi/kuis.php';

// Pastikan kuis yang sudah kedaluwarsa ditutup otomatis
tutupPercobaanKedaluwarsa();

// Jika pengunjung belum login, aktifkan sesi Guest default
if (!sudahMasuk()) {
    if (!isset($_SESSION['guest'])) {
        $_SESSION['guest'] = [
            'username' => 'Tamu DynaQuiz'
        ];
    }
}

$peran = peranSaatIni();
$isGuest = ($peran === 'guest');
$pelajarId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;

$kuisId = (int) ($_GET['id'] ?? 0);

// ----------------------------------------------------------------------------
// 1. Ambil Data Kuis & Validasi Status (KAT-05)
// ----------------------------------------------------------------------------
global $pdo;

$stmtKuis = $pdo->prepare("SELECT * FROM kuis WHERE id = ? AND status = 'terbit' LIMIT 1");
$stmtKuis->execute([$kuisId]);
$kuis = $stmtKuis->fetch(PDO::FETCH_ASSOC);

if (!$kuis) {
    http_response_code(404);
    flash('galat', 'Kuis tidak ditemukan atau belum dipublikasikan.');
    redirect('katalog.php');
}

// ----------------------------------------------------------------------------
// 2. Validasi Hak Akses Kuis Privat (KAT-05, BR-12, BR-14, AC-17)
// ----------------------------------------------------------------------------
if ($kuis['akses'] === 'privat') {
    if ($isGuest) {
        http_response_code(404);
        flash('galat', 'Pengguna tamu tidak dapat mengakses kuis privat.');
        redirect('katalog.php');
    }

    $sudahPunyaIzinSesi = !empty($_SESSION['akses_kuis_privat'][$kuisId]);
    $stmtCekRiwayat = $pdo->prepare("SELECT id FROM percobaan_kuis WHERE kuis_id = ? AND pelajar_id = ? LIMIT 1");
    $stmtCekRiwayat->execute([$kuisId, $pelajarId]);
    $sudahPunyaPercobaan = (bool) $stmtCekRiwayat->fetch();

    if (!$sudahPunyaIzinSesi && !$sudahPunyaPercobaan) {
        http_response_code(404);
        flash('galat', 'Silakan masukkan kode kuis terlebih dahulu di katalog.');
        redirect('katalog.php');
    }
}

// ----------------------------------------------------------------------------
// 3. Ambil Daftar Soal dan Opsi Jawaban Kuis Ini
// ----------------------------------------------------------------------------
$stmtSoal = $pdo->prepare("SELECT id, kuis_id, urutan, pertanyaan, poin, pembahasan FROM soal WHERE kuis_id = ? ORDER BY urutan ASC");
$stmtSoal->execute([$kuisId]);
$daftarSoal = $stmtSoal->fetchAll(PDO::FETCH_ASSOC);

if (empty($daftarSoal)) {
    flash('galat', 'Kuis ini belum memiliki daftar pertanyaan.');
    redirect('detail.php?id=' . $kuisId);
}

$totalSoal = count($daftarSoal);

// Hitung total poin untuk inisialisasi skor maksimal
$skorMaksimal = 0;
foreach ($daftarSoal as $s) {
    $skorMaksimal += (int) $s['poin'];
}

// ----------------------------------------------------------------------------
// 4. Inisialisasi atau Lanjutkan Sesi Percobaan (KRJ-01, KRJ-08)
// ----------------------------------------------------------------------------
$percobaanId = null;
$sisaDetik = 0;
$jawabanTersimpan = [];

if ($peran === 'pelajar') {
    // Mulai percobaan baru atau lanjutkan yang sedang aktif
    $percobaan = mulaiPercobaanKuis($kuisId, $pelajarId);

    if (!$percobaan) {
        flash('galat', 'Gagal memulai sesi pengerjaan kuis.');
        redirect('detail.php?id=' . $kuisId);
    }

    $percobaanId = (int) $percobaan['id'];
    $sisaDetik = hitungSisaWaktuDetik($percobaan);

    // Jika waktu sudah habis saat halaman dibuka, langsung selesaikan kuis (BR-09, AC-15)
    if ($sisaDetik <= 0) {
        selesaikanPercobaan($percobaanId, 'waktu_habis');
        flash('info', 'Waktu pengerjaan kuis telah habis.');
        redirect('hasil.php?id=' . $percobaanId);
    }

    // Ambil jawaban yang sudah pernah dikirim sebelumnya jika refresh (KRJ-08, AC-12)
    $jawabanTersimpan = ambilJawabanPercobaanByPercobaanId($percobaanId);

} elseif ($peran === 'guest') {
    // Mode Guest (Sesi PHP)
    $percobaanGuest = ambilPercobaanGuest($kuisId);

    if (!$percobaanGuest || $percobaanGuest['status'] !== 'berjalan') {
        $percobaanGuest = mulaiPercobaanGuest($kuisId, $kuis['durasi_menit'], $skorMaksimal);
    }

    $mulai = strtotime($percobaanGuest['waktu_mulai']);
    $totalDurasiDetik = (int) $percobaanGuest['durasi_menit'] * 60;
    $sisaDetik = max(0, ($mulai + $totalDurasiDetik) - time());

    if ($sisaDetik <= 0) {
        selesaikanPercobaanGuest($kuisId, $totalSoal);
        flash('info', 'Waktu pengerjaan kuis telah habis.');
        redirect('hasil.php?id=' . $kuisId);
    }

    $jawabanTersimpan = $percobaanGuest['jawaban'] ?? [];
}

// ----------------------------------------------------------------------------
// 5. Muat Pilihan Opsi untuk Setiap Soal (Aman dari Bocor Kunci Jawaban, SEC-07)
// ----------------------------------------------------------------------------
$stmtOpsi = $pdo->prepare("SELECT id, soal_id, label, teks FROM opsi_jawaban WHERE soal_id = ? ORDER BY label ASC");
$stmtKunci = $pdo->prepare("SELECT id, label FROM opsi_jawaban WHERE soal_id = ? AND benar = 1 LIMIT 1");

$jumlahTerjawabAwal = 0;

foreach ($daftarSoal as $idx => $s) {
    $stmtOpsi->execute([(int) $s['id']]);
    $daftarOpsi = $stmtOpsi->fetchAll(PDO::FETCH_ASSOC);

    $soalId = (int) $s['id'];
    $sudahDijawab = false;
    $opsiTerpilihId = null;
    $statusBenar = false;
    $kunciOpsiId = null;
    $kunciLabel = '';

    // Cek apakah soal ini sudah dijawab di database atau sesi guest
    if ($peran === 'pelajar' && isset($jawabanTersimpan[$soalId])) {
        $sudahDijawab = true;
        $opsiTerpilihId = (int) $jawabanTersimpan[$soalId]['opsi_id'];
        $statusBenar = ((int) $jawabanTersimpan[$soalId]['benar'] === 1);
        $jumlahTerjawabAwal++;

        // Kunci jawaban HANYA dimuat jika soal SUDAH dijawab (PRD 9.5 baris 545)
        $stmtKunci->execute([$soalId]);
        $kunci = $stmtKunci->fetch(PDO::FETCH_ASSOC);
        if ($kunci) {
            $kunciOpsiId = (int) $kunci['id'];
            $kunciLabel = $kunci['label'];
        }

    } elseif ($peran === 'guest' && isset($jawabanTersimpan[$soalId])) {
        $sudahDijawab = true;
        $opsiTerpilihId = (int) $jawabanTersimpan[$soalId]['opsi_id'];
        $statusBenar = (bool) $jawabanTersimpan[$soalId]['benar'];
        $jumlahTerjawabAwal++;

        $stmtKunci->execute([$soalId]);
        $kunci = $stmtKunci->fetch(PDO::FETCH_ASSOC);
        if ($kunci) {
            $kunciOpsiId = (int) $kunci['id'];
            $kunciLabel = $kunci['label'];
        }
    }

    $daftarSoal[$idx]['opsi'] = $daftarOpsi;
    $daftarSoal[$idx]['sudah_dijawab'] = $sudahDijawab;
    $daftarSoal[$idx]['opsi_terpilih_id'] = $opsiTerpilihId;
    $daftarSoal[$idx]['status_benar'] = $statusBenar;
    $daftarSoal[$idx]['kunci_opsi_id'] = $kunciOpsiId;
    $daftarSoal[$idx]['kunci_label'] = $kunciLabel;
}

// ----------------------------------------------------------------------------
// 6. Konfigurasi Template Mode Fokus (header-fokus.php)
// ----------------------------------------------------------------------------
$root = '../';
$judulHalaman = 'Mengerjakan Kuis - ' . $kuis['judul'];
$judulKuis = $kuis['judul'];
$sisaWaktu = sprintf('%02d:%02d', floor($sisaDetik / 60), $sisaDetik % 60);
$linkKeluar = 'detail.php?id=' . $kuisId;

require_once __DIR__ . '/../includes/partials/header-fokus.php';
?>

<!-- Berkas CSS Khusus Halaman Kerjakan Kuis (PL-05) -->
<link rel="stylesheet" href="<?= $root ?>assets/css/kerjakan.css">

<!-- Wadah Utama Interaktif Pengerjaan Kuis -->
<div 
  class="kerjakan-wrapper" 
  id="appKerjakan"
  data-kuis-id="<?= $kuisId ?>"
  data-percobaan-id="<?= (int)$percobaanId ?>"
  data-total-soal="<?= $totalSoal ?>"
  data-sisa-detik="<?= (int)$sisaDetik ?>"
  data-peran="<?= e($peran) ?>"
  data-terjawab-awal="<?= $jumlahTerjawabAwal ?>"
>
  <!-- Token CSRF untuk Pengiriman Jawaban via AJAX/Fetch API -->
  <input type="hidden" id="tokenCsrf" value="<?= e(ambilTokenCsrf()) ?>">

  <!-- ========================================================================
       KARTU SOAL (SLIDE PERTANYAAN SATU PER LAYAR - KRJ-02)
       ======================================================================== -->
  <div id="wadahSlideSoal">
    <?php foreach ($daftarSoal as $index => $soal): ?>
      <?php
      $nomorSoal = $index + 1;
      $isSlidePertama = ($index === 0);
      $sudah = $soal['sudah_dijawab'];
      ?>
      <section 
        class="soal-slide" 
        id="slideSoal-<?= $nomorSoal ?>" 
        data-nomor="<?= $nomorSoal ?>" 
        data-soal-id="<?= (int)$soal['id'] ?>"
        style="<?= $isSlidePertama ? 'display: block;' : 'display: none;' ?>"
        aria-label="Soal nomor <?= $nomorSoal ?>"
      >
        <div class="soal-card">
          <!-- Baris Header Soal -->
          <div class="soal-header">
            <span class="soal-nomor-label">Soal <?= $nomorSoal ?> dari <?= $totalSoal ?></span>
            <span class="soal-poin-badge">&#9733; <?= (int)$soal['poin'] ?> Poin</span>
          </div>

          <!-- Teks Pertanyaan Soal -->
          <div class="soal-pertanyaan">
            <?= nl2br(e($soal['pertanyaan'])) ?>
          </div>

          <!-- Daftar Pilihan Jawaban (A, B, C, D) -->
          <div class="opsi-list" role="radiogroup" aria-label="Pilihan jawaban soal <?= $nomorSoal ?>">
            <?php foreach ($soal['opsi'] as $opsi): ?>
              <?php
              $opsiId = (int)$opsi['id'];
              $isPilihanUser = ($sudah && $soal['opsi_terpilih_id'] === $opsiId);
              
              // Tentukan kelas CSS opsi jika soal sudah pernah dijawab
              $kelasOpsi = '';
              if ($sudah) {
                  $kelasOpsi .= ' terkunci';
                  if ($isPilihanUser) {
                      $kelasOpsi .= ' opsi-terpilih';
                      $kelasOpsi .= $soal['status_benar'] ? ' opsi-benar' : ' opsi-salah';
                  } elseif ($soal['kunci_opsi_id'] === $opsiId) {
                      // Kunci jawaban ditampilkan jika user salah memilih
                      $kelasOpsi .= ' opsi-kunci';
                  }
              }
              ?>
              <button 
                type="button" 
                class="opsi-item <?= $kelasOpsi ?>" 
                data-soal-id="<?= (int)$soal['id'] ?>"
                data-opsi-id="<?= $opsiId ?>"
                data-label="<?= e($opsi['label']) ?>"
                <?= $sudah ? 'disabled aria-disabled="true"' : '' ?>
                role="radio"
                aria-checked="<?= $isPilihanUser ? 'true' : 'false' ?>"
              >
                <span class="opsi-label"><?= e($opsi['label']) ?></span>
                <span class="opsi-teks"><?= e($opsi['teks']) ?></span>
              </button>
            <?php endforeach; ?>
          </div>

          <!-- Kotak Umpan Balik & Pembahasan (KRJ-02, ACC-03) -->
          <div 
            class="umpan-balik-box <?= $sudah ? ($soal['status_benar'] ? 'benar' : 'salah') : '' ?>" 
            id="umpanBalik-<?= $nomorSoal ?>"
            style="<?= $sudah ? 'display: block;' : 'display: none;' ?>"
            role="status"
            aria-live="polite"
          >
            <div class="umpan-balik-status">
              <?php if ($sudah): ?>
                <?php if ($soal['status_benar']): ?>
                  <span aria-hidden="true">&#10004;</span>
                  <span>Benar! Jawaban kamu tepat.</span>
                <?php else: ?>
                  <span aria-hidden="true">&#10008;</span>
                  <span>Belum tepat. Kunci jawaban: <?= e($soal['kunci_label']) ?></span>
                <?php endif; ?>
              <?php endif; ?>
            </div>

            <div class="umpan-balik-pembahasan">
              <strong>Pembahasan:</strong>
              <div class="teks-pembahasan">
                <?= $sudah ? nl2br(e($soal['pembahasan'] ?? 'Tidak ada pembahasan tambahan.')) : '' ?>
              </div>
            </div>
          </div>
        </div>
      </section>
    <?php endforeach; ?>
  </div>

  <!-- ========================================================================
       GRID NOMOR SOAL (NAVIGASI CEPAT - KRJ-06)
       ======================================================================== -->
  <div class="grid-nomor-wrapper">
    <div class="grid-nomor-header">
      <span>Daftar Nomor Soal</span>
      <span style="font-weight: normal; color: var(--teks-2); font-size: 0.85rem;" id="labelRingkasanTerjawab">
        Terjawab: <strong id="angkaTerjawab"><?= $jumlahTerjawabAwal ?></strong> / <?= $totalSoal ?>
      </span>
    </div>

    <div class="grid-nomor-list" role="navigation" aria-label="Navigasi nomor soal">
      <?php foreach ($daftarSoal as $index => $soal): ?>
        <?php
        $nomorSoal = $index + 1;
        $sudah = $soal['sudah_dijawab'];
        $kelasBtn = '';
        if ($index === 0) {
            $kelasBtn .= ' nomor-aktif';
        }
        if ($sudah) {
            $kelasBtn .= ' nomor-terjawab';
            $kelasBtn .= $soal['status_benar'] ? ' nomor-benar' : ' nomor-salah';
        }
        ?>
        <button 
          type="button" 
          class="btn-nomor <?= $kelasBtn ?>" 
          id="btnNomor-<?= $nomorSoal ?>"
          data-target-nomor="<?= $nomorSoal ?>"
          aria-label="Buka soal nomor <?= $nomorSoal ?>"
        >
          <?= $nomorSoal ?>
        </button>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ========================================================================
       BARIS NAVIGASI BAWAH (SEBELUMNYA, LANJUT, SELESAI - KRJ-02)
       ======================================================================== -->
  <div class="nav-kerjakan">
    <button type="button" class="btn btn-outline" id="btnSebelumnya" disabled style="min-width: 130px;">
      &larr; Sebelumnya
    </button>

    <div style="display: flex; gap: 10px; align-items: center;">
      <button type="button" class="btn btn-primer-2" id="btnSelesaiCepat" style="display: none;">
        Selesai Kuis
      </button>
      <button type="button" class="btn btn-primary" id="btnLanjut" style="min-width: 140px;">
        Lanjut &rarr;
      </button>
    </div>
  </div>
</div>

<!-- ========================================================================
     MODAL KONFIRMASI KELUAR (KRJ-02)
     ======================================================================== -->
<div class="modal-backdrop" id="modalKeluar" role="dialog" aria-modal="true" aria-labelledby="judulModalKeluar">
  <div class="modal-card">
    <div style="font-size: 2.8rem; margin-bottom: 12px;" aria-hidden="true">&#9888;</div>
    <h2 id="judulModalKeluar" style="font-size: 1.35rem; margin-bottom: 8px;">Keluar dari Kuis?</h2>
    <p style="color: var(--teks-2); font-size: 0.95rem; line-height: 1.5; margin-bottom: 20px;">
      Jawaban yang sudah kamu kirim tetap tersimpan aman. Namun, waktu pengerjaan akan terus berjalan di server!
    </p>
    <div class="modal-aksi">
      <button type="button" class="btn btn-outline" id="btnBatalKeluar">
        Lanjut Mengerjakan
      </button>
      <a href="detail.php?id=<?= $kuisId ?>" class="btn btn-batal" style="text-decoration: none;">
        Ya, Keluar
      </a>
    </div>
  </div>
</div>

<!-- ========================================================================
     MODAL KONFIRMASI SELESAI KUIS (KRJ-07)
     ======================================================================== -->
<div class="modal-backdrop" id="modalSelesai" role="dialog" aria-modal="true" aria-labelledby="judulModalSelesai">
  <div class="modal-card">
    <div style="font-size: 2.8rem; margin-bottom: 12px;" aria-hidden="true">&#127937;</div>
    <h2 id="judulModalSelesai" style="font-size: 1.35rem; margin-bottom: 8px;">Kumpulkan Jawaban Kuis?</h2>
    <p style="color: var(--teks); font-size: 0.95rem; line-height: 1.5; margin-bottom: 8px;" id="pesanKonfirmasiSelesai">
      Apakah kamu yakin ingin mengakhiri sesi kuis ini?
    </p>
    <div id="peringatanBelumSelesai" style="color: var(--galat); font-size: 0.9rem; font-weight: 600; margin-bottom: 20px; display: none;">
      Perhatian: Masih ada soal yang belum kamu jawab! Soal kosong akan dihitung salah.
    </div>
    <div class="modal-aksi">
      <button type="button" class="btn btn-outline" id="btnBatalKumpul">
        Periksa Kembali
      </button>
      <button type="button" class="btn btn-primary" id="btnKonfirmasiKumpul">
        Ya, Selesaikan Kuis
      </button>
    </div>
  </div>
</div>

<!-- Muat Skrip JavaScript Interaksi Kerjakan Kuis (Disiapkan untuk Sesi 6) -->
<script src="<?= $root ?>assets/js/kerjakan.js"></script>

<?php
require_once __DIR__ . '/../includes/partials/footer-fokus.php';
?>
