<?php

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/katalog.php';

$judul = 'Beranda';
$halamanPenuh = true;
$statistik = ['alat' => 0, 'ruang' => 0, 'kategori' => 0, 'alat_tersedia' => 0, 'ruang_tersedia' => 0];
$alatTerbaru = [];
$pesanError = null;

try {
    $statistik = katalog_ringkasan() + $statistik;
    $alatTerbaru = katalog_cari([], 6);
} catch (PDOException $exception) {
    $pesanError = 'Database belum terhubung. Pastikan database labloan sudah di-import dan pengaturan di config/koneksi.php sudah benar.';
}

if (has_role('admin')) {
    $aksiUtama = ['label' => 'Buka Dashboard', 'href' => url('admin/dashboard.php')];
    $aksiKedua = ['label' => 'Kelola Data Alat', 'href' => url('admin/alat/index.php')];
    $mulaiPinjamUrl = url('admin/peminjaman/index.php');
} elseif (has_role('peminjam')) {
    $aksiUtama = ['label' => 'Ajukan Peminjaman', 'href' => url('peminjaman/index.php')];
    $aksiKedua = ['label' => 'Riwayat Saya', 'href' => url('peminjaman/riwayat.php')];
    $mulaiPinjamUrl = url('peminjaman/index.php');
} else {
    $aksiUtama = ['label' => 'Daftar Sekarang', 'href' => url('auth/register.php')];
    $aksiKedua = ['label' => 'Lihat Daftar Alat', 'href' => url('alat/index.php')];
    $mulaiPinjamUrl = url('auth/login.php');
}

$kartuStatistik = [
    ['label' => 'Jenis alat terdaftar', 'nilai' => $statistik['alat'], 'ikon' => 'alat', 'gaya' => 'neon'],
    ['label' => 'Ruang laboratorium', 'nilai' => $statistik['ruang'], 'ikon' => 'ruang', 'gaya' => 'dark'],
    ['label' => 'Kategori inventaris', 'nilai' => $statistik['kategori'], 'ikon' => 'kategori', 'gaya' => 'blue'],
];

$langkahPinjam = [
    ['judul' => 'Pilih alat', 'teks' => 'Cari alat atau ruang di katalog, cek stok dan lokasinya, lalu klik kartu yang ingin dipinjam.', 'ikon' => 'pilih', 'gaya' => 'putih'],
    ['judul' => 'Ajukan & tunggu persetujuan', 'teks' => 'Isi tanggal pinjam, tanggal kembali, jumlah, dan keperluan. Pantau statusnya di halaman Riwayat.', 'ikon' => 'ajukan', 'gaya' => 'neon'],
    ['judul' => 'Ambil & kembalikan', 'teks' => 'Setelah disetujui, ambil alat sesuai jadwal dan kembalikan sebelum batas kembali agar dicatat admin.', 'ikon' => 'kembali', 'gaya' => 'hitam'],
];

$daftarFaq = [
    [
        'id' => 'faq-siapa',
        'tanya' => 'Siapa yang bisa meminjam alat dan ruang?',
        'jawab' => 'Semua pengguna yang sudah memiliki akun peminjam. Buat akun lewat menu Daftar dengan nama, email, dan password minimal 8 karakter, lalu masuk untuk mulai mengajukan peminjaman.',
    ],
    [
        'id' => 'faq-cara',
        'tanya' => 'Bagaimana cara mengajukan peminjaman?',
        'jawab' => 'Masuk sebagai peminjam, buka Ajukan Peminjaman, lalu pilih alat yang stoknya tersedia. Isi tanggal pinjam (paling cepat hari ini), tanggal kembali (tidak boleh sebelum tanggal pinjam), jumlah, dan keperluan. Satu pengajuan berisi satu jenis alat atau ruang.',
    ],
    [
        'id' => 'faq-jumlah',
        'tanya' => 'Berapa jumlah maksimal yang boleh saya pinjam?',
        'jawab' => 'Jumlah pinjam tidak boleh melebihi stok yang tersedia saat pengajuan dibuat. Jika stok tidak mencukupi, sistem otomatis menolak pengajuan tersebut. Alat atau ruang dengan stok habis tidak bisa dipilih.',
    ],
    [
        'id' => 'faq-status',
        'tanya' => 'Apa arti status menunggu, disetujui, ditolak, dan dikembalikan?',
        'jawab' => 'Menunggu berarti pengajuan belum diproses admin. Disetujui berarti alat boleh diambil sesuai jadwal. Ditolak berarti pengajuan tidak dapat dipenuhi. Dikembalikan berarti alat sudah diterima kembali oleh admin dan transaksi selesai.',
    ],
    [
        'id' => 'faq-lama',
        'tanya' => 'Berapa lama pengajuan saya diproses?',
        'jawab' => 'Admin memproses pengajuan secara manual. Pengajuan yang belum diproses admin selama lebih dari 3 hari akan ditolak otomatis oleh sistem, dan kamu dapat mengajukan ulang.',
    ],
    [
        'id' => 'faq-stok',
        'tanya' => 'Kapan stok alat berkurang dan bertambah kembali?',
        'jawab' => 'Stok berkurang saat admin menyetujui pengajuan, bukan saat kamu mengajukan. Stok bertambah kembali setelah admin mencatat pengembalian.',
    ],
    [
        'id' => 'faq-kembali',
        'tanya' => 'Bagaimana cara mengembalikan alat?',
        'jawab' => 'Serahkan alat kepada petugas laboratorium sebelum batas kembali. Petugas akan memeriksa lalu mencatat pengembalian di sistem sehingga status berubah menjadi dikembalikan.',
    ],
    [
        'id' => 'ketentuan',
        'tanya' => 'Bagaimana jika alat terlambat dikembalikan atau rusak?',
        'jawab' => 'Ketentuan keterlambatan dan kerusakan mengikuti kebijakan laboratorium yang berlaku. Segera hubungi petugas laboratorium melalui kontak di bagian bawah halaman jika ada kendala.',
    ],
];

require __DIR__ . '/includes/header.php';
?>
<div class="hero-shell">
    <section class="hero on-dark" aria-labelledby="hero-judul">
        <?= dekor_grid() ?>
        <?= dekor_swoosh('hero-swoosh') ?>
        <?= dekor_spiral('hero-spiral') ?>
        <div class="hero-grid">
            <div class="hero-copy">
                <p class="hero-eyebrow"><span>Baru</span> Peminjaman lab serba online</p>
                <h1 id="hero-judul" class="hero-title">Pinjam alat &amp; ruang lab <span class="sorot-neon">tanpa ribet</span></h1>
                <p>Ajukan peminjaman alat dan ruang laboratorium dari mana saja, pantau statusnya secara langsung, dan biarkan admin memproses persetujuan serta pengembalian dengan rapi.</p>
                <div class="hero-actions">
                    <a href="<?= e($aksiUtama['href']) ?>" class="btn btn-neon btn-lg"><?= e($aksiUtama['label']) ?><?= ikon('panah', 'ikon ikon--sm') ?></a>
                    <a href="<?= e($aksiKedua['href']) ?>" class="btn btn-ghost-light btn-lg"><?= e($aksiKedua['label']) ?></a>
                </div>
                <form class="hero-search" method="get" action="<?= e(katalog_url()) ?>" role="search">
                    <?= ikon('cari') ?>
                    <label for="hero-q" class="visually-hidden">Cari alat atau ruang berdasarkan nama atau kode</label>
                    <input type="search" id="hero-q" name="q" placeholder="Cari alat atau ruang, misal: osiloskop">
                    <button type="submit" class="btn btn-primary">Cari</button>
                </form>
            </div>
            <div class="hero-visual">
                <?= dekor_panah('hero-panah') ?>
                <div class="glass-card">
                    <span class="glass-card__ikon"><?= ikon('alat') ?></span>
                    <span class="glass-card__nilai"><?= (int) $statistik['alat_tersedia'] ?></span>
                    <span class="glass-card__label">alat siap dipinjam</span>
                </div>
                <div class="glass-card">
                    <span class="glass-card__ikon"><?= ikon('ruang') ?></span>
                    <span class="glass-card__nilai"><?= (int) $statistik['ruang_tersedia'] ?></span>
                    <span class="glass-card__label">ruang lab tersedia</span>
                </div>
                <?= lencana_putar('hero', 'Mulai Pinjam • Mulai Pinjam • ', $mulaiPinjamUrl, 'Mulai pinjam') ?>
            </div>
        </div>
    </section>

    <section class="hero-panel" aria-label="Ringkasan inventaris">
        <div class="stat-tiles">
<?php foreach ($kartuStatistik as $kartu): ?>
            <div class="stat-card stat-card--<?= e($kartu['gaya']) ?>">
                <span class="stat-card__ikon"><?= ikon($kartu['ikon']) ?></span>
                <div class="stat-value"><?= (int) $kartu['nilai'] ?></div>
                <div class="stat-label"><?= e($kartu['label']) ?></div>
            </div>
<?php endforeach; ?>
        </div>
    </section>
</div>

<div class="container">
<?php if ($pesanError !== null): ?>
    <div class="alert alert-warning mt-6"><?= e($pesanError) ?></div>
<?php endif; ?>

    <section class="section" aria-labelledby="terbaru-judul">
        <div class="section-head">
            <div>
                <span class="tag tag--dark">Katalog</span>
                <h2 id="terbaru-judul">Alat &amp; Ruang Terbaru</h2>
            </div>
            <p>Inventaris yang baru ditambahkan ke laboratorium. Klik kartu untuk langsung mengajukan peminjaman.</p>
        </div>
<?php if ($alatTerbaru === []): ?>
        <div class="card empty-state">
            <?= ikon('alat') ?>
            <h3>Belum ada data alat</h3>
            <p>Data alat dan ruang akan tampil di sini setelah ditambahkan admin.</p>
        </div>
<?php else: ?>
        <div class="alat-grid alat-grid--3">
<?php foreach ($alatTerbaru as $alat): ?>
            <?php alat_card($alat); ?>
<?php endforeach; ?>
        </div>
        <div class="section-more">
            <a href="<?= e(katalog_url()) ?>" class="btn btn-dark btn-lg">Lihat Lebih Banyak<?= ikon('panah', 'ikon ikon--sm') ?></a>
        </div>
<?php endif; ?>
    </section>

    <section id="cara-pinjam" class="section" aria-labelledby="cara-judul">
        <div class="blok-biru on-dark">
            <?= dekor_grid() ?>
            <?= dekor_swoosh('cara-swoosh') ?>
            <div class="section-head">
                <div>
                    <span class="tag tag--neon">3 langkah</span>
                    <h2 id="cara-judul">Cara Pinjam</h2>
                </div>
                <p>Dari memilih alat sampai mengembalikannya, semua tercatat di LabLoan.</p>
            </div>
            <ol class="steps">
<?php foreach ($langkahPinjam as $nomor => $langkah): ?>
<?php if ($nomor > 0): ?>
                <li class="step-panah" aria-hidden="true"><?= dekor_panah('') ?></li>
<?php endif; ?>
                <li class="step step--<?= e($langkah['gaya']) ?>">
                    <span class="step__nomor"><?= ikon($langkah['ikon']) ?>Langkah 0<?= $nomor + 1 ?></span>
                    <h3><?= e($langkah['judul']) ?></h3>
                    <p><?= e($langkah['teks']) ?></p>
                </li>
<?php endforeach; ?>
            </ol>
        </div>
    </section>

    <section id="faq" class="section" aria-labelledby="faq-judul">
        <div class="faq-layout">
            <div class="faq-intro">
                <div class="section-head">
                    <div>
                        <span class="tag tag--dark">Bantuan</span>
                        <h2 id="faq-judul">Pertanyaan Umum</h2>
                    </div>
                    <p>Jawaban singkat seputar aturan peminjaman di LabLoan.</p>
                </div>
                <div class="faq-kontak on-dark">
                    <h3>Masih bingung?</h3>
                    <p class="mb-0">Hubungi petugas laboratorium di <a href="mailto:<?= e(LAB_EMAIL) ?>"><?= e(LAB_EMAIL) ?></a> pada jam layanan.</p>
                </div>
            </div>
            <div class="faq-list">
<?php foreach ($daftarFaq as $faq): ?>
                <details id="<?= e($faq['id']) ?>" class="faq-item">
                    <summary><?= e($faq['tanya']) ?></summary>
                    <div class="faq-item__isi">
                        <p><?= e($faq['jawab']) ?></p>
                    </div>
                </details>
<?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section" aria-labelledby="cta-judul">
        <div class="blok-biru cta-banner on-dark">
            <?= dekor_swoosh('cta-swoosh') ?>
            <?= dekor_spiral('cta-spiral') ?>
            <div>
                <h2 id="cta-judul">Siap praktikum? <span class="sorot-neon">Pinjam sekarang.</span></h2>
                <p>Cek ketersediaan alat dan ruang, lalu ajukan dalam hitungan menit.</p>
            </div>
            <div class="cta-banner__aksi">
                <a href="<?= e($aksiUtama['href']) ?>" class="btn btn-neon btn-lg"><?= e($aksiUtama['label']) ?><?= ikon('panah', 'ikon ikon--sm') ?></a>
                <a href="<?= e(katalog_url()) ?>" class="btn btn-ghost-light btn-lg">Lihat Katalog</a>
            </div>
        </div>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
