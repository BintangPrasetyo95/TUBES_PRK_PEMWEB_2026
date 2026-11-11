<?php
// Sidebar navigasi admin (desain baru). Dipanggil otomatis oleh admin_header.php.
// Link memakai admin_url(), jadi tetap benar dari subfolder seperti admin/berita/.
// Halaman yang sedang dibuka ditandai otomatis dan diberi aria-current="page".

$sbSkrip = $_SERVER['SCRIPT_NAME'] ?? '';
if (str_contains($sbSkrip, '/admin/berita/')) {
    $sbAktif = 'berita';
} elseif (str_contains($sbSkrip, '/admin/aspirasi/aspirasi.php')) {
    $sbAktif = 'aspirasi';
} else {
    $sbAktif = 'dashboard';
}

$sbMenu = [
    'dashboard' => ['Dashboard', '/admin/index.php', 'dashboard'],
    'berita'    => ['Berita', '/admin/berita/index.php', 'berita'],
    'aspirasi'  => ['Aspirasi', '/admin/aspirasi/aspirasi.php', 'aspirasi'],
];
$sbKelasLink = 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium md:justify-center lg:justify-start focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white';
?>
<div id="admin-sidebar-overlay" class="fixed inset-0 z-30 hidden bg-slate-900/50 md:hidden" aria-hidden="true"></div>

<aside id="admin-sidebar" aria-label="Navigasi admin"
       class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-stone-900 text-white transition-transform duration-200 motion-reduce:transition-none md:w-16 md:translate-x-0 lg:w-64">
    <div class="flex h-16 items-center gap-3 border-b border-stone-800 px-4 md:justify-center lg:justify-start">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-orange-600 text-sm font-bold" aria-hidden="true">K</span>
        <span class="md:sr-only lg:not-sr-only">
            <span class="block text-sm font-semibold leading-tight">KabarSih</span>
            <span class="block text-xs text-stone-400">Admin Panel</span>
        </span>
        <button type="button" id="admin-sidebar-tutup"
                class="ml-auto rounded-lg p-1.5 text-stone-300 hover:bg-stone-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white md:hidden"
                aria-label="Tutup menu navigasi">
            <?= ikon('tutup', 'h-5 w-5') ?>
        </button>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-2 py-4" aria-label="Menu utama">
        <?php foreach ($sbMenu as $kunci => [$label, $path, $namaIkon]): ?>
            <?php $aktif = ($sbAktif === $kunci); ?>
            <a href="<?= e(admin_url($path)) ?>" title="<?= e($label) ?>"
               class="<?= $sbKelasLink ?> <?= $aktif ? 'bg-orange-600 text-white' : 'text-stone-300 hover:bg-stone-800 hover:text-white' ?>"
               <?= $aktif ? 'aria-current="page"' : '' ?>>
                <?= ikon($namaIkon, 'h-5 w-5 shrink-0') ?>
                <span class="md:sr-only lg:not-sr-only"><?= e($label) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="border-t border-stone-800 p-2">
        <a href="<?= e(admin_url('/auth/logout.php')) ?>" title="Logout"
           class="<?= $sbKelasLink ?> text-stone-300 hover:bg-stone-800 hover:text-white">
            <?= ikon('logout', 'h-5 w-5 shrink-0') ?>
            <span class="md:sr-only lg:not-sr-only">Logout</span>
        </a>
    </div>
</aside>