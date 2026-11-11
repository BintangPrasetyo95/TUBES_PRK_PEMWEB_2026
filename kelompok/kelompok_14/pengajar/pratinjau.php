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

<!-- Banner Pemberitahuan Mode Pratinjau -->
<div class="alert alert-info" role="status" style="margin-top: 20px;">
    <strong>👁️ Mode Pratinjau Pengajar:</strong>
    Anda sedang mencoba tampilan pengerjaan kuis milik sendiri (status: <strong><?= e(ucfirst($kuis['status'])) ?></strong>).
    Hasil pratinjau tidak akan disimpan ke database.
</div>

<?php if ($totalSoal === 0): ?>
    <!-- Jika Kuis Belum Memiliki Soal -->
    <div class="card" style="text-align: center; padding: 40px;">
        <h2 style="margin-bottom: 12px;">Belum Ada Soal</h2>
        <p class="text-secondary" style="margin-bottom: 20px;">
            Kuis ini belum memiliki soal untuk dipratinjau. Silakan tambahkan soal terlebih dahulu.
        </p>
        <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
            <a href="kelola-soal.php?kuis_id=<?= e($kuisId) ?>" class="btn btn-primary">Kelola Soal</a>
            <a href="kuis.php" class="btn btn-secondary">Kembali ke Kuis Saya</a>
        </div>
    </div>
<?php else: ?>

    <!-- Area Pengerjaan Pratinjau (Satu Soal per Layar) -->
    <div id="areaPratinjau" data-durasi="<?= e((int) $kuis['durasi_menit']) ?>" data-total-soal="<?= e($totalSoal) ?>">
        
        <!-- Keterangan Nomor Soal -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <span id="infoNomorSoal" style="font-weight: bold;">Soal 1 dari <?= e($totalSoal) ?></span>
            <span class="badge badge-sedang" id="infoPoinSoal">10 Poin</span>
        </div>

        <!-- Daftar Kartu Soal (Hanya soal yang aktif yang ditampilkan) -->
        <?php foreach ($daftarSoal as $index => $soal): ?>
            <div class="card kartu-soal-pratinjau"
                 id="soal-<?= e($index) ?>"
                 data-index="<?= e($index) ?>"
                 data-poin="<?= e((int) $soal['poin']) ?>"
                 data-pembahasan="<?= e($soal['pembahasan'] ?? '') ?>"
                 <?= $index > 0 ? 'hidden' : '' ?>>
                
                <h2 style="font-size: 1.25rem; margin-bottom: 20px; line-height: 1.5;">
                    <?= e($soal['pertanyaan']) ?>
                </h2>

                <!-- Pilihan Jawaban A - E -->
                <div class="daftar-opsi-pratinjau" style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($soal['opsi'] as $opsi): ?>
                        <button type="button"
                                class="btn btn-outline tombol-opsi-pratinjau"
                                data-soal-index="<?= e($index) ?>"
                                data-label="<?= e($opsi['label']) ?>"
                                data-benar="<?= e((int) $opsi['benar']) ?>"
                                style="justify-content: flex-start; text-align: left; padding: 14px 18px;">
                            <span class="badge badge-sedang" style="margin-right: 10px;"><?= e($opsi['label']) ?></span>
                            <span><?= e($opsi['teks']) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Kotak Umpan Balik & Pembahasan (Muncul setelah pengajar memilih opsi) -->
                <div class="kotak-umpan-balik" id="umpan-balik-<?= e($index) ?>" hidden style="margin-top: 20px;">
                    <div class="alert isi-status-jawaban" style="margin-bottom: 10px;"></div>
                    <?php if (!empty($soal['pembahasan'])): ?>
                        <div class="card card-sm" style="background-color: var(--latar);">
                            <strong>💡 Pembahasan:</strong>
                            <p style="margin-top: 6px;"><?= e($soal['pembahasan']) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Grid Nomor Soal & Tombol Navigasi -->
        <div class="card card-sm" style="margin-top: 20px;">
            <p style="font-weight: bold; margin-bottom: 12px;">Navigasi Nomor Soal:</p>
            <div id="gridNomorSoal" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px;">
                <?php foreach ($daftarSoal as $index => $soal): ?>
                    <button type="button"
                            class="btn btn-sm btn-outline tombol-grid-soal"
                            data-target-index="<?= e($index) ?>"
                            style="min-width: 44px; min-height: 44px;">
                        <?= e($index + 1) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div style="display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                <button type="button" id="tombolSebelumnya" class="btn btn-secondary" disabled>
                    &larr; Sebelumnya
                </button>
                <div style="display: flex; gap: 10px;">
                    <button type="button" id="tombolBerikutnya" class="btn btn-primary">
                        Lanjut &rarr;
                    </button>
                    <button type="button" id="tombolSelesaiPratinjau" class="btn btn-accent">
                        Selesai Pratinjau
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- Ringkasan Hasil Akhir Pratinjau (Disembunyikan sampai Selesai diklik) -->
    <div id="hasilPratinjau" class="card" hidden style="text-align: center; padding: 36px 24px;">
        <span class="badge badge-sedang" style="margin-bottom: 12px;">Ringkasan Mode Pratinjau</span>
        <h2 style="font-size: 2rem; margin-bottom: 8px;">
            Skor Anda: <span id="hasilSkorAkhir">0</span> / <span id="hasilSkorMaks">0</span>
        </h2>
        <p id="hasilPersenAkhir" style="font-size: 1.2rem; font-weight: bold; margin-bottom: 20px;">0%</p>

        <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; margin-bottom: 24px;">
            <span class="badge badge-terbit">&#10003; Benar: <strong id="hasilJumlahBenar">0</strong></span>
            <span class="badge badge-ditolak">&#10007; Salah / Kosong: <strong id="hasilJumlahSalah">0</strong></span>
        </div>

        <p class="text-secondary" style="margin-bottom: 24px;">
            Catatan: Ini hanyalah simulasi pratinjau pengajar. Data pengerjaan ini tidak disimpan ke database.
        </p>

        <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
            <a href="pratinjau.php?id=<?= e($kuisId) ?>" class="btn btn-primary">Ulangi Pratinjau</a>
            <a href="kuis.php" class="btn btn-secondary">Kembali ke Kuis Saya</a>
        </div>
    </div>

<?php endif; ?>

<!-- Muat skrip interaktif mode pratinjau -->
<script src="../assets/js/pratinjau.js"></script>

<?php
// Muat template footer mode fokus
require_once __DIR__ . '/../includes/partials/footer-fokus.php';
