<?php

$tautanCepatByRole = [
    'tamu' => [
        'Beranda' => 'index.php',
        'Daftar Alat' => 'alat/index.php',
        'Masuk' => 'auth/login.php',
        'Daftar Akun' => 'auth/register.php',
    ],
    'peminjam' => [
        'Beranda' => 'index.php',
        'Daftar Alat' => 'alat/index.php',
        'Ajukan Peminjaman' => 'peminjaman/index.php',
        'Riwayat Peminjaman' => 'peminjaman/riwayat.php',
    ],
    'admin' => [
        'Dashboard' => 'admin/dashboard.php',
        'Data Alat' => 'admin/alat/index.php',
        'Peminjaman' => 'admin/peminjaman/index.php',
        'Laporan' => 'admin/laporan.php',
    ],
];

$tautanBantuan = [
    'FAQ' => 'index.php#faq',
    'Cara Pinjam' => 'index.php#cara-pinjam',
    'Ketentuan Peminjaman' => 'index.php#ketentuan',
];

$tautanCepat = $tautanCepatByRole[current_user()['role'] ?? 'tamu'] ?? $tautanCepatByRole['tamu'];
?>
<?php if (!($halamanPenuh ?? false)): ?>
    </div>
<?php endif; ?>
</main>
<footer class="footer">
    <div class="footer-panel">
        <?= dekor_swoosh('footer-swoosh') ?>
        <div class="container footer-grid">
            <div class="footer-col footer-brand">
                <a class="logo-bubble logo-bubble--terang" href="<?= e(url('index.php')) ?>" aria-label="<?= e(APP_NAME) ?> - beranda">
                    <span class="logo-bubble__lab">Lab</span><span class="logo-bubble__loan">Loan</span>
                </a>
                <p>Sistem peminjaman alat dan ruang laboratorium secara online: ajukan, pantau status, dan kembalikan dengan tercatat rapi.</p>
                <span class="tag tag--neon">Pinjam cepat, kembali tepat.</span>
            </div>
            <nav class="footer-col" aria-label="Tautan cepat">
                <h2 class="footer-title">Tautan Cepat</h2>
                <ul class="footer-links">
<?php foreach ($tautanCepat as $label => $path): ?>
                    <li><a href="<?= e(url($path)) ?>"><?= e($label) ?></a></li>
<?php endforeach; ?>
                </ul>
            </nav>
            <nav class="footer-col" aria-label="Bantuan">
                <h2 class="footer-title">Bantuan</h2>
                <ul class="footer-links">
<?php foreach ($tautanBantuan as $label => $path): ?>
                    <li><a href="<?= e(url($path)) ?>"><?= e($label) ?></a></li>
<?php endforeach; ?>
                </ul>
            </nav>
            <div class="footer-col">
                <h2 class="footer-title">Kontak &amp; Jam Layanan</h2>
                <ul class="footer-kontak">
                    <li><?= ikon('lokasi', 'ikon ikon--sm') ?><span><?= e(LAB_NAMA) ?>, <?= e(LAB_ALAMAT) ?></span></li>
                    <li><?= ikon('email', 'ikon ikon--sm') ?><a href="mailto:<?= e(LAB_EMAIL) ?>"><?= e(LAB_EMAIL) ?></a></li>
                    <li><?= ikon('telepon', 'ikon ikon--sm') ?><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', LAB_TELEPON)) ?>"><?= e(LAB_TELEPON) ?></a></li>
                </ul>
                <dl class="footer-jam">
<?php foreach (LAB_JAM_OPERASIONAL as $hari => $jam): ?>
                    <div><dt><?= e($hari) ?></dt><dd><?= e($jam) ?></dd></div>
<?php endforeach; ?>
                </dl>
            </div>
        </div>
        <div class="container footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?> &middot; Kelompok 07 &middot; Praktikum Pemrograman Web</p>
            <a href="#atas" class="btn-top" aria-label="Kembali ke atas halaman"><?= ikon('atas') ?></a>
        </div>
    </div>
</footer>
</body>
</html>
