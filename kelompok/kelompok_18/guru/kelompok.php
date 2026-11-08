<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

guru_page_start('Kelompok Sesi', 'kelas.php');
?>
<nav aria-label="Menu sesi" class="flex flex-wrap gap-2">
    <a href="tap-sesi.php" class="rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#526257]">Tap Kehadiran</a>
    <a href="absensi.php" class="rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#526257]">Data Siswa &amp; Poin</a>
    <a href="kelompok.php" aria-current="page" class="rounded-lg bg-[#1e5638] px-4 py-2 text-sm font-semibold text-white">Kelompok</a>
</nav>
<section class="flex flex-wrap items-center justify-between gap-3">
    <div><h2 class="text-lg font-semibold text-[#20392a]">Kelompok Pembelajaran</h2><p class="mt-1 text-sm text-slate-600">Kelompok terhubung ke kelas dan semester.</p></div>
    <a href="kelompok-baru.php" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1e5638] px-4 py-2 text-sm font-semibold text-white hover:bg-[#17452d]">Tambah Kelompok</a>
</section>
<section class="space-y-3">
    <article class="rounded-2xl border border-[#e2e9e2] bg-white p-5">
        <div class="flex flex-wrap items-center justify-between gap-3"><div><h3 class="font-semibold text-[#20392a]">Kelompok 1</h3><p class="mt-1 text-sm text-slate-500">Kelas X-A · 2026/2027 Ganjil · 5 anggota</p></div><a href="kelompok-edit.php?id=1" class="inline-flex min-h-9 items-center rounded-lg border border-[#d8e2d8] px-3 text-sm font-semibold text-[#284b35] hover:bg-[#f2f6f2]">Edit Kelompok</a></div>
    </article>
    <article class="rounded-2xl border border-[#e2e9e2] bg-white p-5">
        <div class="flex flex-wrap items-center justify-between gap-3"><div><h3 class="font-semibold text-[#20392a]">Kelompok 2</h3><p class="mt-1 text-sm text-slate-500">Kelas X-A · 2026/2027 Ganjil · 5 anggota</p></div><a href="kelompok-edit.php?id=2" class="inline-flex min-h-9 items-center rounded-lg border border-[#d8e2d8] px-3 text-sm font-semibold text-[#284b35] hover:bg-[#f2f6f2]">Edit Kelompok</a></div>
    </article>
</section>
<a href="sesi-aktif.php?id=1" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#284b35]">Kembali ke Sesi</a>
<?php guru_page_end(); ?>
