<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$sessionId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!is_int($sessionId) || $sessionId < 1) {
    http_response_code(404);
    exit('Sesi arsip tidak ditemukan.');
}

try {
    $session = guruArchiveDetail($sessionId);
    if ($session === null) {
        http_response_code(404);
        exit('Sesi arsip tidak ditemukan.');
    }
} catch (Throwable $exception) {
    guruReportBackendError($exception);
}

$dateLabel = date('d M Y', strtotime($session['tanggal']));
$studentTotal = count($session['students']);
$presentCount = count(array_filter(
    $session['students'],
    static fn (array $student): bool => $student['attendance_status'] === 'hadir'
));
$pointRecipientCount = count(array_filter(
    $session['students'],
    static fn (array $student): bool => (float) $student['points'] > 0
));
if ($session['waktu_mulai'] !== null && $session['waktu_selesai'] !== null) {
    $timeLabel = date('H:i', strtotime($session['waktu_mulai'])) . '–' . date('H:i', strtotime($session['waktu_selesai']));
} elseif ($session['scheduled_start'] !== null && $session['scheduled_end'] !== null) {
    $timeLabel = substr((string) $session['scheduled_start'], 0, 5) . '–' . substr((string) $session['scheduled_end'], 0, 5);
} else {
    $timeLabel = '—';
}
guru_page_start('Detail Arsip Pembelajaran', 'arsip-pembelajaran.php');
?>
<section aria-label="Ringkasan sesi" class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 xl:grid-cols-3">
    <?php
    attendara_ui_stat_card('Kelas', $session['class_name'], $session['semester'], 'school', true, true, true);
    attendara_ui_stat_card('Tanggal', $dateLabel, 'Tanggal sesi', 'calendar', true, true, true);
    attendara_ui_stat_card('Waktu', $timeLabel, 'Waktu aktual sesi', 'calendar', true, true, true);
    attendara_ui_stat_card('Status', ucfirst((string) $session['status']), 'Sesi pembelajaran', 'chart', true, true, true);
    attendara_ui_stat_card('Siswa Hadir', $session['status'] === 'dibatalkan' ? '-' : $presentCount . '/' . $studentTotal, 'Hadir dari total siswa', 'users', true, true, true);
    attendara_ui_stat_card('Penerima Poin', (string) $pointRecipientCount, 'Siswa mendapat poin', 'chart', true, true, true);
    ?>
</section>
<section class="space-y-4">
    <div><h2 class="text-lg font-semibold text-[#303832]">Kehadiran dan Poin</h2><p class="mt-1 text-sm text-slate-600"><?= $session['status'] === 'dibatalkan' ? 'Snapshot kehadiran sesi; poin sesi batal tidak masuk rekap.' : 'Snapshot kehadiran dan total poin per siswa.' ?></p></div>
    <?php if ($session['students'] === []): ?>
        <p class="rounded-2xl border border-[#dfe6de] bg-white px-5 py-8 text-center text-sm text-slate-500">Belum ada catatan siswa.</p>
    <?php else: ?>
        <div class="space-y-3 md:hidden">
            <?php foreach ($session['students'] as $student): ?>
                <?php
                $attendanceStatus = (string) $student['attendance_status'];
                $pointsWhileAbsent = $attendanceStatus !== 'hadir' && (float) $student['points'] > 0;
                $attendanceBadgeClass = match ($attendanceStatus) {
                    'hadir' => 'bg-[#e8f1e7] text-[#31583c]',
                    'alpha' => 'bg-slate-300 text-slate-800',
                    'sakit' => 'bg-amber-100 text-amber-800',
                    'izin' => 'bg-blue-100 text-blue-800',
                    default => 'bg-slate-100 text-slate-600',
                };
                ?>
                <article class="rounded-2xl border p-4 shadow-[0_2px_8px_rgba(30,55,38,0.04)] <?= $pointsWhileAbsent ? 'border-amber-300 bg-amber-50/70' : 'border-[#dfe6de] bg-white' ?>">
                    <div class="min-w-0">
                        <h3 class="truncate font-semibold text-[#303832]"><?= attendara_ui_escape($student['name']) ?></h3>
                        <p class="mt-1 text-xs text-slate-500">NIS <?= attendara_ui_escape($student['number']) ?></p>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-3 border-t border-[#e6ebe5] pt-3 text-sm">
                        <div><dt class="text-xs text-slate-500">Kehadiran</dt><dd class="mt-1"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium <?= $attendanceBadgeClass ?>"><?= attendara_ui_escape(ucfirst($attendanceStatus)) ?></span></dd></div>
                        <div><dt class="text-xs text-slate-500">Poin Keaktifan</dt><dd class="mt-1"><?php if ((float) $student['points'] > 0): ?><span class="inline-flex rounded-lg bg-blue-100 px-2 py-1 text-sm font-semibold tabular-nums text-blue-800"><?= number_format((float) $student['points'], 2, '.', ',') ?></span><?php else: ?><span class="font-semibold tabular-nums text-slate-700"><?= number_format((float) $student['points'], 2, '.', ',') ?></span><?php endif; ?></dd><?php if ($pointsWhileAbsent): ?><span class="mt-1 inline-flex rounded-full bg-amber-200 px-2 py-0.5 text-[10px] font-semibold text-amber-900">Poin saat tidak hadir</span><?php endif; ?></div>
                    </dl>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="hidden md:block">
            <div class="overflow-x-auto rounded-2xl border border-[#dfe6de] bg-white">
                <table class="min-w-full divide-y divide-[#e6ebe5] text-left text-sm">
                    <thead class="bg-[#eff3ee] text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3.5 font-semibold">Nomor Induk</th><th class="px-4 py-3.5 font-semibold">Nama Siswa</th><th class="px-4 py-3.5 font-semibold">Status Kehadiran</th><th class="px-4 py-3.5 font-semibold">Poin Keaktifan</th></tr></thead>
                    <tbody class="divide-y divide-[#e6ebe5] text-slate-700">
                        <?php foreach ($session['students'] as $student): ?>
                            <?php
                            $attendanceStatus = (string) $student['attendance_status'];
                            $pointsWhileAbsent = $attendanceStatus !== 'hadir' && (float) $student['points'] > 0;
                            $attendanceBadgeClass = match ($attendanceStatus) {
                                'hadir' => 'bg-[#e8f1e7] text-[#31583c]',
                                'alpha' => 'bg-slate-300 text-slate-800',
                                'sakit' => 'bg-amber-100 text-amber-800',
                                'izin' => 'bg-blue-100 text-blue-800',
                                default => 'bg-slate-100 text-slate-600',
                            };
                            ?>
                            <tr class="<?= $pointsWhileAbsent ? 'bg-amber-50' : 'hover:bg-[#fafbf9]' ?>">
                                <td class="whitespace-nowrap px-4 py-3.5"><?= attendara_ui_escape($student['number']) ?></td>
                                <td class="whitespace-nowrap px-4 py-3.5 font-medium"><?= attendara_ui_escape($student['name']) ?></td>
                                <td class="whitespace-nowrap px-4 py-3.5"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium <?= $attendanceBadgeClass ?>"><?= attendara_ui_escape(ucfirst($attendanceStatus)) ?></span></td>
                                <td class="whitespace-nowrap px-4 py-3.5"><?php if ((float) $student['points'] > 0): ?><span class="inline-flex rounded-lg bg-blue-100 px-2 py-1 font-semibold tabular-nums text-blue-800"><?= number_format((float) $student['points'], 2, '.', ',') ?></span><?php else: ?><span class="font-semibold tabular-nums text-slate-700"><?= number_format((float) $student['points'], 2, '.', ',') ?></span><?php endif; ?><?php if ($pointsWhileAbsent): ?><span class="ml-2 inline-flex rounded-full bg-amber-200 px-2 py-0.5 text-[10px] font-semibold text-amber-900">Poin saat tidak hadir</span><?php endif; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>
<?php guru_page_end(); ?>
