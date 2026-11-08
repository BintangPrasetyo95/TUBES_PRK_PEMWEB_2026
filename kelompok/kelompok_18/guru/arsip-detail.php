<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$session = $guruUiData['archives'][0];
$rows = [];
foreach ($guruUiData['students'] as $student) {
    $rows[] = [$student['number'], $student['name'], 'Hadir', ((int) $student['id'] % 2 === 0) ? '2' : '1'];
}

guru_page_start('Detail Arsip Pembelajaran', 'arsip-pembelajaran.php');
?>
<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <?php
    attendara_ui_stat_card('Kelas', $session['class_name'], $session['semester'], 'school');
    attendara_ui_stat_card('Tanggal', $session['date'], 'Tanggal sesi', 'calendar');
    attendara_ui_stat_card('Waktu', $session['time'], 'Jadwal sesi', 'calendar');
    attendara_ui_stat_card('Status', 'Selesai', 'Sesi pembelajaran', 'chart');
    ?>
</section>
<section class="space-y-4">
    <div><h2 class="text-lg font-semibold text-[#20392a]">Kehadiran dan Poin</h2><p class="mt-1 text-sm text-slate-600">Catatan per siswa dalam sesi.</p></div>
    <?php guru_render_table(['Nomor Induk', 'Nama Siswa', 'Status Kehadiran', 'Poin Keaktifan'], $rows, 'Belum ada catatan siswa.'); ?>
</section>
<section class="space-y-4">
    <h2 class="text-lg font-semibold text-[#20392a]">Kelompok</h2>
    <div class="rounded-2xl border border-[#e2e9e2] bg-white px-5 py-8 text-center text-sm text-slate-500">Tidak ada kelompok tercatat pada sesi ini.</div>
</section>
<a href="arsip-pembelajaran.php" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#284b35] hover:bg-[#f2f6f2]">Kembali ke Arsip</a>
<?php guru_page_end(); ?>
