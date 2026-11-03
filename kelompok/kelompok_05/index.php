<?php
// Halaman awal CEKHAK. Bila modules/beranda/home.php ada (dibuat Anggota E), isinya dipakai menggantikan tampilan bawaan.
require_once __DIR__ . '/includes/bootstrap.php';

$page_title = 'Beranda';
require __DIR__ . '/includes/header.php';

$home_kustom = __DIR__ . '/modules/beranda/home.php';
if (is_file($home_kustom)) {
    require $home_kustom;
} else {
    $jenis_dokumen = db()->query('SELECT id, nama, deskripsi FROM document_types WHERE aktif = 1 ORDER BY nama')->fetchAll();
    $user = current_user();
    ?>
    <div class="card">
        <h1>CEKHAK</h1>
        <p>Cek Hak dan Ketentuan Hukum: periksa kelengkapan administratif dokumen hukum sebelum diajukan.</p>
        <p class="muted">Alat bantu administratif, bukan pendapat hukum final.</p>
        <p>
            <?php if ($user === null): ?>
                <?= link_if_exists(login_url(), 'Masuk', 'btn btn-primary') ?>
                <?= link_if_exists('modules/auth/register.php', 'Daftar', 'btn') ?>
            <?php else: ?>
                <?= link_if_exists('modules/pemeriksaan/buat.php', 'Mulai Pemeriksaan', 'btn') ?>
            <?php endif; ?>
        </p>
    </div>

    <h2>Jenis dokumen yang didukung</h2>
    <div class="grid">
        <?php foreach ($jenis_dokumen as $jenis): ?>
            <div class="card">
                <h3><?= e($jenis['nama']) ?></h3>
                <p class="muted"><?= e($jenis['deskripsi']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

require __DIR__ . '/includes/footer.php';
