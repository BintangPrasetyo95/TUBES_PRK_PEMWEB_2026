<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

// Status adalah contoh teks biasa; tidak diasumsikan sebagai enum final.
$rows = [
    ['1', '2025-GENAP', '2025/2026 Genap', '2026-01-05', '2026-06-30', 'selesai', '2026-01-01 09:00:00', '2026-06-30 23:59:00'],
    ['2', '2026-GANJIL', '2026/2027 Ganjil', '2026-07-13', '2026-12-19', 'aktif', '2026-06-01 09:00:00', '2026-07-13 09:00:00'],
];

admin_page_start('Data Semester', 'semester.php');
?>
<section class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-lg font-semibold text-[#20392a]">Daftar Semester</h2><p class="mt-1 text-sm text-slate-600">Status ditampilkan sesuai nilai teks pada data.</p></div><?php admin_ui_button('Tambah Semester'); ?></div>
    <?php admin_render_table(['ID', 'Kode', 'Nama', 'Tanggal Mulai', 'Tanggal Selesai', 'Status', 'Dibuat', 'Diperbarui'], $rows, 'Belum ada data semester.', ['Edit']); ?>
</section>
<section class="rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:p-6">
    <h2 class="text-base font-semibold text-[#20392a]">Form Semester</h2>
    <form class="mt-4 grid gap-4 sm:grid-cols-2" onsubmit="return false;">
        <?php attendara_ui_input('Kode', 'kode_semester'); ?>
        <?php attendara_ui_input('Nama', 'nama_semester'); ?>
        <?php attendara_ui_input('Tanggal mulai', 'tanggal_mulai_semester', '', 'date'); ?>
        <?php attendara_ui_input('Tanggal selesai', 'tanggal_selesai_semester', '', 'date'); ?>
        <div class="sm:col-span-2"><label for="status-semester" class="mb-1.5 block text-sm font-medium text-slate-700">Status (teks)</label><input id="status-semester" type="text" class="min-h-10 w-full rounded-lg border border-[#d8e2d8] px-3 text-sm" placeholder="Masukkan nilai status yang ditetapkan aplikasi"></div>
        <div class="sm:col-span-2"><?php admin_ui_button('Simpan'); ?></div>
    </form>
</section>
<?php admin_page_end(); ?>
