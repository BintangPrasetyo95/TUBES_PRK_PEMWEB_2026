<?php
// Navigasi per peran. Tautan hanya tampil bila filenya sudah ada (lihat link_if_exists).
$nav_user = current_user();
$nav_role = $nav_user['role'] ?? 'tamu';
?>
<header class="navbar">
    <div class="navbar-inner">
        <a class="brand" href="<?= e(base_url('index.php')) ?>">CEKHAK</a>
        <button type="button" class="nav-toggle" aria-label="Buka menu" data-nav-toggle>&#9776;</button>
        <nav class="navbar-menu" data-nav-menu>
<?php if ($nav_role === 'tamu'): ?>
            <?= link_if_exists('index.php', 'Beranda') ?>
            <?= link_if_exists('modules/pustaka/index.php', 'Pustaka Persyaratan') ?>
            <?= link_if_exists(login_url(), 'Masuk') ?>
            <?= link_if_exists('modules/auth/register.php', 'Daftar', 'btn btn-small') ?>
<?php else: ?>
<?php if ($nav_role === 'pengguna'): ?>
            <?= link_if_exists('modules/beranda/dashboard.php', 'Dashboard') ?>
            <?= link_if_exists('modules/pemeriksaan/buat.php', 'Pemeriksaan Baru') ?>
            <?= link_if_exists('modules/riwayat/index.php', 'Riwayat') ?>
<?php elseif ($nav_role === 'reviewer'): ?>
            <?= link_if_exists('modules/reviewer/index.php', 'Dashboard Reviewer') ?>
<?php elseif ($nav_role === 'admin'): ?>
            <?= link_if_exists('modules/admin/users.php', 'Akun') ?>
            <?= link_if_exists('modules/admin/index.php', 'Jenis Dokumen') ?>
            <?= link_if_exists('modules/admin/requirements.php', 'Persyaratan') ?>
            <?= link_if_exists('modules/audit/index.php', 'Log Aktivitas') ?>
<?php endif; ?>
            <?= link_if_exists('modules/pustaka/index.php', 'Pustaka') ?>
<?php if (config('dev_mode', false)): ?>
            <?= link_if_exists('modules/contoh/contoh_pemeriksaan.php', 'Contoh Pola', 'nav-dev') ?>
            <?= link_if_exists('modules/contoh/contoh_ajax.php', 'Contoh AJAX', 'nav-dev') ?>
<?php endif; ?>
            <span class="nav-user"><?= e($nav_user['nama']) ?> <span class="badge badge-info"><?= e($nav_role) ?></span></span>
            <?= link_if_exists(logout_url(), 'Keluar') ?>
<?php endif; ?>
        </nav>
    </div>
</header>
