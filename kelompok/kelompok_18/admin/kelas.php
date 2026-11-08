<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

// Contoh tampilan statis mengikuti kelas, anggota_kelas, dan semester.
$rows = [
    ['1', 'X-A', 'Kelas X-A', '10', '20', '2026/2027 Ganjil', 'Aktif', '2026-01-01 09:00:00', '2026-01-01 09:00:00'],
    ['2', 'X-B', 'Kelas X-B', '10', '20', '2026/2027 Ganjil', 'Aktif', '2026-01-01 09:00:00', '2026-01-01 09:00:00'],
    ['3', 'X-C', 'Kelas X-C', '10', '20', '2026/2027 Ganjil', 'Aktif', '2026-01-01 09:00:00', '2026-01-01 09:00:00'],
];

admin_page_start('Data Kelas', 'kelas.php');
?>
<section class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-lg font-semibold text-[#20392a]">Daftar Kelas</h2><p class="mt-1 text-sm text-slate-600">Jumlah siswa dan semester mengikuti konteks penempatan.</p></div><?php admin_ui_button('Tambah Kelas'); ?></div>
    <?php admin_render_table(['ID', 'Kode', 'Nama', 'Tingkat', 'Jumlah Siswa', 'Semester', 'Status', 'Dibuat', 'Diperbarui'], $rows, 'Belum ada data kelas.', ['Edit']); ?>
</section>
<section class="rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:p-6">
    <h2 class="text-base font-semibold text-[#20392a]">Form Kelas</h2>
    <form class="mt-4 grid gap-4 sm:grid-cols-3" onsubmit="return false;">
        <?php attendara_ui_input('Kode', 'kode_kelas', '', 'text', 'Contoh: X-A'); ?>
        <?php attendara_ui_input('Nama', 'nama_kelas', '', 'text', 'Nama kelas'); ?>
        <?php attendara_ui_input('Tingkat (opsional)', 'tingkat_kelas', '', 'number'); ?>
        <div class="sm:col-span-3"><?php admin_ui_button('Simpan'); ?></div>
    </form>
</section>
<?php admin_page_end(); ?>
