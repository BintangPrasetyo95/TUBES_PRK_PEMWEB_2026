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

<!-- Banner Penanda Mode Pratinjau -->
<div class="alert alert-info" role="status" style="margin-top: 20px;">
    <strong>Mode Pratinjau:</strong> Anda sedang mencoba tampilan kuis ini sebagai pengajar. Jawaban dinilai langsung di browser dan tidak disimpan ke database.
</div>

<?php if ($totalSoal === 0): ?>
    <!-- Tampilan jika kuis belum memiliki soal -->
    <div class="card" style="text-align: center; padding: 40px;">
        <h2 style="margin-bottom: 12px;">Belum Ada Soal</h2>
        <p class="text-secondary" style="margin-bottom: 20px;">
            Kuis ini belum memiliki soal. Tambahkan minimal 1 soal terlebih dahulu untuk melihat pratinjau.
        </p>
        <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
            <a href="kelola-soal.php?kuis_id=<?= e($kuisId) ?>" class="btn btn-primary">Kelola Soal</a>
            <a href="kuis.php" class="btn btn-batal">Kembali ke Kuis Saya</a>
        </div>
    </div>
<?php else: ?>
    <!-- Informasi Progress Nomor Soal -->
    <p id="teksProgress" class="text-secondary" style="font-weight: bold; margin-bottom: 16px;">
        Soal 1 dari <?= e($totalSoal) ?>
    </p>

    <!-- Area Kartu Soal (Satu Soal per Layar) -->
    <div id="wadahKuis" data-durasi-menit="<?= e($kuis['durasi_menit']) ?>" data-total-soal="<?= e($totalSoal) ?>">
        <?php foreach ($daftarSoal as $index => $soal): ?>
            <div class="card kartu-soal-pratinjau"
                 id="kartu-soal-<?= e($index) ?>"
                 data-index="<?= e($index) ?>"
                 data-poin="<?= e($soal['poin']) ?>"
                 data-pembahasan="<?= e($soal['pembahasan'] ?? '') ?>"
                 <?= $index > 0 ? 'hidden' : '' ?>>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <span class="badge badge-sedang">Soal <?= e($index + 1) ?></span>
                    <span class="text-secondary" style="font-size: 14px; font-weight: 700;">
                        <?= e($soal['poin']) ?> Poin
                    </span>
                </div>

                <!-- Teks Pertanyaan -->
                <h2 style="font-size: 1.2rem; margin-bottom: 20px; line-height: 1.5;">
                    <?= e($soal['pertanyaan']) ?>
                </h2>

                <!-- Daftar Pilihan Jawaban (A - E) -->
                <div class="daftar-opsi-pratinjau" style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($soal['opsi'] as $opsi): ?>
                        <button type="button"
                                class="btn btn-outline btn-block tombol-opsi-pratinjau"
                                data-soal-index="<?= e($index) ?>"
                                data-label="<?= e($opsi['label']) ?>"
                                data-benar="<?= (int) $opsi['benar'] ?>"
                                style="justify-content: flex-start; text-align: left; padding: 14px 18px;">
                            <span class="badge badge-sedang" style="min-width: 32px; justify-content: center;">
                                <?= e($opsi['label']) ?>
                            </span>
                            <span><?= e($opsi['teks']) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Kotak Umpan Balik & Pembahasan (Muncul setelah opsi diklik oleh JS) -->
                <div class="kotak-umpan-balik alert" id="umpan-balik-<?= e($index) ?>" hidden style="margin-top: 20px; flex-direction: column; align-items: flex-start;">
                    <strong class="judul-umpan-balik"></strong>
                    <p class="isi-pembahasan" style="margin: 0; font-weight: 400;"></p>
                </div>

                <!-- Tombol Navigasi Bawah Kartu -->
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-top: 24px; flex-wrap: wrap;">
                    <?php if ($index < $totalSoal - 1): ?>
                        <button type="button" class="btn btn-primary tombol-lanjut-pratinjau" data-target="<?= e($index + 1) ?>">
                            Soal Berikutnya &rarr;
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-accent" id="tombolSelesaiPratinjau">
                            Lihat Hasil Pratinjau
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Grid Navigasi Nomor Soal -->
    <div class="card" style="padding: 20px;">
        <h3 class="card-title" style="font-size: 1rem; margin-bottom: 12px;">Navigasi Nomor Soal</h3>
        <div style="display: flex; flex-wrap: wrap; gap: 8px;" id="gridNomorSoal">
            <?php foreach ($daftarSoal as $index => $soal): ?>
                <button type="button"
                        class="btn btn-sm btn-outline tombol-nomor-pratinjau"
                        data-target="<?= e($index) ?>">
                    <?= e($index + 1) ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Ringkasan Hasil Akhir Pratinjau (Disembunyikan sampai tombol Selesai diklik) -->
    <div class="card" id="kartuHasilPratinjau" hidden style="text-align: center; padding: 36px 24px;">
        <span class="badge badge-sedang" style="margin-bottom: 12px;">Hasil Pratinjau (Tidak Disimpan)</span>
        <h2 style="margin-bottom: 12px;">Simulasi Selesai!</h2>
        <p style="font-size: 2rem; font-weight: 800; margin-bottom: 8px;" id="hasilPersenPratinjau">0%</p>
        <p class="text-secondary" style="margin-bottom: 24px;" id="hasilRincianPratinjau">
            Skor: 0 / 0 &bull; Benar: 0 &bull; Salah: 0
        </p>

        <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
            <a href="pratinjau.php?id=<?= e($kuisId) ?>" class="btn btn-primary">Ulangi Pratinjau</a>
            <a href="kelola-soal.php?kuis_id=<?= e($kuisId) ?>" class="btn btn-secondary">Kelola Soal</a>
            <a href="kuis.php" class="btn btn-batal">Kembali ke Kuis Saya</a>
        </div>
    </div>
<?php endif; ?>

<?php
// Muat template penutup mode fokus
require_once __DIR__ . '/../includes/partials/footer-fokus.php';
