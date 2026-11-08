<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

// Satu baris penugasan menghubungkan satu guru, kelas, dan semester.
$rows = [
    ['1', '1', 'Budi Santoso · G-2026-001', '1 · Kelas X-A', '2 · 2026/2027 Ganjil', '2026-06-01 09:00:00'],
    ['2', '2', 'Siti Rahmawati · G-2026-002', '2 · Kelas X-B', '2 · 2026/2027 Ganjil', '2026-06-01 09:00:00'],
    ['3', '3', 'Agus Setiawan · G-2026-003', '3 · Kelas X-C', '2 · 2026/2027 Ganjil', '2026-06-01 09:00:00'],
];

admin_page_start('Penugasan Guru', 'penugasan-guru.php');
?>
<section class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-lg font-semibold text-[#20392a]">Daftar Penugasan</h2><p class="mt-1 text-sm text-slate-600">Relasi guru, kelas, dan semester.</p></div><?php admin_ui_button('Tambah Penugasan'); ?></div>
    <?php admin_render_table(['ID', 'Guru ID', 'Guru', 'Kelas', 'Semester', 'Dibuat'], $rows, 'Belum ada penugasan guru.', ['Edit']); ?>
</section>
<section class="rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:p-6">
    <h2 class="text-base font-semibold text-[#20392a]">Form Penugasan Guru</h2>
    <form class="mt-4 grid gap-4 sm:grid-cols-3" onsubmit="return false;">
        <div><label for="assignment-teacher" class="mb-1.5 block text-sm font-medium text-slate-700">Guru</label><select id="assignment-teacher" class="min-h-10 w-full rounded-lg border border-[#d8e2d8] bg-white px-3 text-sm"><option value="1">Budi Santoso · G-2026-001</option><option value="2">Siti Rahmawati · G-2026-002</option><option value="3">Agus Setiawan · G-2026-003</option></select></div>
        <div><label for="assignment-class" class="mb-1.5 block text-sm font-medium text-slate-700">Kelas</label><select id="assignment-class" class="min-h-10 w-full rounded-lg border border-[#d8e2d8] bg-white px-3 text-sm"><option value="1">Kelas X-A</option><option value="2">Kelas X-B</option><option value="3">Kelas X-C</option></select></div>
        <div><label for="assignment-semester" class="mb-1.5 block text-sm font-medium text-slate-700">Semester</label><select id="assignment-semester" class="min-h-10 w-full rounded-lg border border-[#d8e2d8] bg-white px-3 text-sm"><option value="2">2026/2027 Ganjil</option><option value="1">2025/2026 Genap</option></select></div>
        <div class="sm:col-span-3"><?php admin_ui_button('Simpan Penugasan'); ?></div>
    </form>
</section>
<?php admin_page_end(); ?>
