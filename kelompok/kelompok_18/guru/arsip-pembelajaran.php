<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$archiveRows = array_map(static fn (array $session): array => [
    $session['date'], $session['class_name'], $session['semester'], $session['time'], (string) $session['attendance_count'] . ' siswa', $session['status'],
], $guruUiData['archives']);

guru_page_start('Arsip Pembelajaran', 'arsip-pembelajaran.php');
?>
<section class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3"><div><h2 class="text-lg font-semibold text-[#20392a]">Riwayat Sesi</h2><p class="mt-1 text-sm text-slate-600">Sesi pembelajaran yang telah selesai.</p></div></div>
    <?php guru_render_table(['Tanggal', 'Kelas', 'Semester', 'Waktu', 'Jumlah Siswa', 'Status'], $archiveRows, 'Belum ada sesi pembelajaran.'); ?>
    <?php foreach ($guruUiData['archives'] as $session): ?>
        <a href="arsip-detail.php?id=<?= (int) $session['id'] ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#284b35] hover:bg-[#f2f6f2]">Lihat Detail Sesi #<?= (int) $session['id'] ?></a>
    <?php endforeach; ?>
</section>
<?php guru_page_end(); ?>
