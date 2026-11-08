<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

// Jadwal selalu mengacu ke penugasan_guru_id; guru/kelas/semester berasal dari relasi itu.
$rows = [
    ['1', '1', 'Budi Santoso', 'Kelas X-A', '2026/2027 Ganjil', '1', '07:00:00', '08:30:00', '2026-07-13', '2026-12-19', '2026-06-01 09:00:00', '2026-06-01 09:00:00'],
    ['2', '2', 'Siti Rahmawati', 'Kelas X-B', '2026/2027 Ganjil', '2', '08:00:00', '09:30:00', '2026-07-13', '2026-12-19', '2026-06-01 09:00:00', '2026-06-01 09:00:00'],
    ['3', '3', 'Agus Setiawan', 'Kelas X-C', '2026/2027 Ganjil', '3', '09:00:00', '10:30:00', '2026-07-13', '2026-12-19', '2026-06-01 09:00:00', '2026-06-01 09:00:00'],
];

admin_page_start('Jadwal Mengajar', 'jadwal-mengajar.php');
?>
<section class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-lg font-semibold text-[#20392a]">Daftar Jadwal Mengajar</h2><p class="mt-1 text-sm text-slate-600">Guru, kelas, dan semester ditampilkan dari penugasan guru.</p></div><?php admin_ui_button('Tambah Jadwal'); ?></div>
    <?php admin_render_table(['ID', 'Penugasan ID', 'Guru', 'Kelas', 'Semester', 'Hari (angka)', 'Jam Mulai', 'Jam Selesai', 'Berlaku Mulai', 'Berlaku Sampai', 'Dibuat', 'Diperbarui'], $rows, 'Belum ada jadwal mengajar.', ['Edit']); ?>
</section>
<section class="rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:p-6">
    <h2 class="text-base font-semibold text-[#20392a]">Form Jadwal</h2>
    <form class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" onsubmit="return false;">
        <div class="lg:col-span-3"><label for="schedule-assignment" class="mb-1.5 block text-sm font-medium text-slate-700">Penugasan Guru</label><select id="schedule-assignment" class="min-h-10 w-full rounded-lg border border-[#d8e2d8] bg-white px-3 text-sm"><option value="1">#1 · Budi Santoso · Kelas X-A · 2026/2027 Ganjil</option><option value="2">#2 · Siti Rahmawati · Kelas X-B · 2026/2027 Ganjil</option><option value="3">#3 · Agus Setiawan · Kelas X-C · 2026/2027 Ganjil</option></select></div>
        <?php attendara_ui_input('Hari (angka sesuai konvensi aplikasi)', 'hari_jadwal', '', 'number'); ?>
        <?php attendara_ui_input('Jam mulai', 'jam_mulai_jadwal', '', 'text', 'HH:MM:SS'); ?>
        <?php attendara_ui_input('Jam selesai', 'jam_selesai_jadwal', '', 'text', 'HH:MM:SS'); ?>
        <?php attendara_ui_input('Berlaku mulai (opsional)', 'berlaku_mulai_jadwal', '', 'date'); ?>
        <?php attendara_ui_input('Berlaku sampai (opsional)', 'berlaku_sampai_jadwal', '', 'date'); ?>
        <div class="lg:col-span-3"><?php admin_ui_button('Simpan Jadwal'); ?></div>
    </form>
</section>
<?php admin_page_end(); ?>
