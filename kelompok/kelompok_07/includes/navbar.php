<?php

$user = current_user();

$menusByRole = [
    'tamu' => [
        ['label' => 'Beranda', 'path' => 'index.php'],
        ['label' => 'Daftar Alat', 'path' => 'alat/index.php', 'aktif' => ['alat/detail.php']],
        ['label' => 'Cara Pinjam', 'path' => 'index.php#cara-pinjam'],
        ['label' => 'FAQ', 'path' => 'index.php#faq'],
    ],
    'peminjam' => [
        ['label' => 'Beranda', 'path' => 'index.php'],
        ['label' => 'Daftar Alat', 'path' => 'alat/index.php', 'aktif' => ['alat/detail.php']],
        ['label' => 'Ajukan Peminjaman', 'path' => 'peminjaman/index.php', 'aktif' => ['peminjaman/ajukan.php']],
        ['label' => 'Riwayat', 'path' => 'peminjaman/riwayat.php'],
        ['label' => 'FAQ', 'path' => 'index.php#faq'],
    ],
    'admin' => [
        ['label' => 'Dashboard', 'path' => 'admin/dashboard.php'],
        ['label' => 'Kategori', 'path' => 'admin/kategori/index.php'],
        ['label' => 'Data Alat', 'path' => 'admin/alat/index.php'],
        ['label' => 'Peminjaman', 'path' => 'admin/peminjaman/index.php'],
        ['label' => 'Laporan', 'path' => 'admin/laporan.php'],
    ],
];

$activeRole = $user['role'] ?? 'tamu';
$menus = $menusByRole[$activeRole] ?? $menusByRole['tamu'];

$navMenuAktif = static function (array $menu): bool {
    if (str_contains($menu['path'], '#')) {
        return false;
    }

    foreach (array_merge([$menu['path']], $menu['aktif'] ?? []) as $path) {
        if (is_current_page($path)) {
            return true;
        }
    }

    return false;
};
?>
<header class="navbar" data-navbar>
    <div class="container navbar-inner">
        <a class="logo-bubble" href="<?= e(url($activeRole === 'admin' ? 'admin/dashboard.php' : 'index.php')) ?>" aria-label="<?= e(APP_NAME) ?> - beranda">
            <span class="logo-bubble__lab">Lab</span><span class="logo-bubble__loan">Loan</span>
        </a>
        <button type="button" class="navbar-burger" aria-expanded="false" aria-controls="navbar-menu" aria-label="Buka menu navigasi" data-navbar-toggle>
            <span></span><span></span><span></span>
        </button>
        <nav id="navbar-menu" class="navbar-menu" aria-label="Navigasi utama">
            <ul class="navbar-links">
<?php foreach ($menus as $menu): ?>
<?php $navAktif = $navMenuAktif($menu); ?>
                <li>
                    <a href="<?= e(url($menu['path'])) ?>" class="pill-link<?= $navAktif ? ' active' : '' ?>"<?= $navAktif ? ' aria-current="page"' : '' ?>><?= e($menu['label']) ?></a>
                </li>
<?php endforeach; ?>
            </ul>
            <div class="navbar-actions">
<?php if ($user === null): ?>
                <a href="<?= e(url('auth/login.php')) ?>" class="btn btn-outline btn-sm">Masuk</a>
                <a href="<?= e(url('auth/register.php')) ?>" class="btn btn-neon btn-sm">Daftar</a>
<?php else: ?>
                <span class="navbar-user">
                    <span class="avatar" aria-hidden="true"><?= e(inisial_nama($user['nama'])) ?></span>
                    <span class="navbar-user__teks">
                        <span class="navbar-user__nama"><?= e($user['nama']) ?></span>
                        <span class="navbar-user__role"><?= e($activeRole) ?></span>
                    </span>
                </span>
                <a href="<?= e(url('auth/logout.php')) ?>" class="btn btn-dark btn-sm">Keluar</a>
<?php endif; ?>
            </div>
        </nav>
    </div>
</header>
