<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

// Contoh statis menampilkan seluruh kolom profil guru pada skema.
$rows = [
    ['1', '2', 'G-2026-001', 'Budi Santoso', 'budi.santoso@example.test', '081200000001', 'Aktif', '2026-01-01 09:00:00', '2026-01-01 09:00:00'],
    ['2', '3', 'G-2026-002', 'Siti Rahmawati', 'siti.rahmawati@example.test', '081200000002', 'Aktif', '2026-01-01 09:00:00', '2026-01-01 09:00:00'],
    ['3', '4', 'G-2026-003', 'Agus Setiawan', 'agus.setiawan@example.test', '081200000003', 'Aktif', '2026-01-01 09:00:00', '2026-01-01 09:00:00'],
    ['4', '5', 'G-2026-004', 'Dewi Anggraini', 'dewi.anggraini@example.test', '081200000004', 'Aktif', '2026-01-01 09:00:00', '2026-01-01 09:00:00'],
    ['5', '6', 'G-2026-005', 'Rizky Pratama', 'rizky.pratama@example.test', '081200000005', 'Aktif', '2026-01-01 09:00:00', '2026-01-01 09:00:00'],
];

admin_page_start('Data Guru', 'guru.php');
?>
<section class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-lg font-semibold text-[#20392a]">Daftar Guru</h2><p class="mt-1 text-sm text-slate-600">Profil guru dan keterkaitan akun.</p></div><?php admin_ui_button('Tambah Guru'); ?></div>
    <?php admin_render_table(['ID', 'User ID', 'Nomor Induk', 'Nama', 'Email', 'No. Telepon', 'Status', 'Dibuat', 'Diperbarui'], $rows, 'Belum ada data guru.', ['Edit', 'Nonaktifkan']); ?>
</section>
<section class="rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:p-6">
    <h2 class="text-base font-semibold text-[#20392a]">Form Guru</h2>
    <form class="mt-4 grid gap-4 sm:grid-cols-2" onsubmit="return false;">
        <?php attendara_ui_input('Nomor induk', 'nomor_induk_guru'); ?>
        <?php attendara_ui_input('Nama', 'nama_guru'); ?>
        <?php attendara_ui_input('Email (opsional)', 'email_guru', '', 'email'); ?>
        <?php attendara_ui_input('No. telepon (opsional)', 'telepon_guru'); ?>
        <div class="sm:col-span-2"><?php admin_ui_button('Simpan'); ?></div>
    </form>
</section>
<?php admin_page_end(); ?>
