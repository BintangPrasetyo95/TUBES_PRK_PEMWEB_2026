<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$pageError = '';
try {
    $profile = guruProfile();
    $activeSemester = guruActiveSemester();
    $classes = $profile !== null && $activeSemester !== null
        ? guruActiveAssignments((int) $profile['id'], (int) $activeSemester['id'])
        : [];
    $schedules = $profile !== null && $activeSemester !== null
        ? guruActiveSchedules((int) $profile['id'], (int) $activeSemester['id'])
        : [];
    $classCounts = [];
    foreach ($classes as $class) {
        $classCounts[(int) $class['id']] = (int) $class['student_count'];
    }
    $studentCount = array_sum($classCounts);
    $todayNumber = (int) date('N');
    $todaySchedules = array_values(array_filter(
        $schedules,
        static fn (array $schedule): bool => (int) $schedule['day_number'] === $todayNumber
    ));

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrfToken($_POST['_csrf_token'] ?? null)) {
            http_response_code(403);
            exit('Permintaan tidak valid.');
        }
        $assignmentId = filter_input(INPUT_POST, 'assignment_id', FILTER_VALIDATE_INT);
        $scheduleId = filter_input(INPUT_POST, 'schedule_id', FILTER_VALIDATE_INT);
        $selectedSchedule = null;
        foreach ($todaySchedules as $schedule) {
            if (
                (int) $schedule['assignment_id'] === $assignmentId
                && (int) $schedule['id'] === $scheduleId
            ) {
                $selectedSchedule = $schedule;
                break;
            }
        }

        if (($_POST['action'] ?? null) !== 'start_session' || $selectedSchedule === null) {
            http_response_code(422);
            $pageError = 'Jadwal sesi tidak valid. Muat ulang halaman lalu coba kembali.';
        } else {
            $newSessionId = guruStartSession((int) $selectedSchedule['assignment_id'], (int) $selectedSchedule['id']);
            header('Location: tap-sesi.php?session_id=' . $newSessionId);
            exit;
        }
    }
} catch (DomainException $exception) {
    $pageError = $exception->getMessage();
} catch (Throwable $exception) {
    guruReportBackendError($exception);
}

guru_page_start('Dashboard Guru', 'dashboard.php');
?>
<section aria-label="Ringkasan Guru" class="grid grid-cols-2 gap-3 sm:grid-cols-2 sm:gap-4 xl:grid-cols-3">
    <div class="col-span-2 sm:col-span-1">
        <?php attendara_ui_stat_card('Profil Guru', $profile['nomor_induk'] ?? 'Belum terhubung', $profile['nama'] ?? 'Profil Guru belum tersedia', 'users', true, false, true); ?>
    </div>
    <div class="col-span-1">
        <?php attendara_ui_stat_card('Kelas ditugaskan', (string) count($classCounts), $studentCount . ' siswa pada kelas aktif', 'school', true, false, true); ?>
    </div>
    <div class="col-span-1">
        <?php attendara_ui_stat_card('Jadwal mengajar', (string) count($schedules), $activeSemester['nama'] ?? 'Tidak ada semester aktif', 'calendar', true, false, true); ?>
    </div>
</section>

<section class="space-y-4">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-[#303832]">Jadwal Hari Ini</h2>
            <p class="mt-1 text-sm text-slate-600"><?= attendara_ui_escape(date('d M Y')) ?> · Pilih kelas untuk memulai sesi.</p>
        </div>
        <span class="text-sm font-medium text-slate-500"><?= count($todaySchedules) ?> jadwal</span>
    </div>
    <?php if ($pageError !== ''): ?><p class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert"><?= attendara_ui_escape($pageError) ?></p><?php endif; ?>
    <?php if ($todaySchedules === []): ?>
        <div class="rounded-2xl border border-dashed border-[#dfe6de] bg-white px-5 py-10 text-center">
            <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-[#e8f0e9] text-[#3f6548]"><?php attendara_ui_icon('calendar', 'h-6 w-6'); ?></span>
            <h3 class="mt-4 text-sm font-semibold text-[#303832]">Tidak ada jadwal hari ini</h3>
            <p class="mt-1 text-sm text-slate-500">Jadwal kelas hari ini akan tampil di sini.</p>
        </div>
    <?php else: ?>
        <div class="grid gap-3 lg:grid-cols-2">
            <?php foreach ($todaySchedules as $schedule): ?>
                <article class="flex min-w-0 flex-col gap-4 rounded-2xl border border-[#dfe6de] bg-white p-4 shadow-[0_2px_8px_rgba(30,55,38,0.04)] sm:flex-row sm:items-center sm:justify-between sm:p-5">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#64806a]"><?= attendara_ui_escape($schedule['subject_name']) ?></p>
                        <h3 class="mt-1 truncate text-lg font-semibold text-[#303832]"><?= attendara_ui_escape($schedule['class_name']) ?></h3>
                        <p class="mt-2 inline-flex rounded-lg bg-[#f1f5ef] px-3 py-1.5 text-sm font-semibold tabular-nums text-[#526257]"><?= attendara_ui_escape(substr((string) $schedule['start'], 0, 5) . '–' . substr((string) $schedule['end'], 0, 5)) ?></p>
                    </div>
                    <form method="post" action="dashboard.php" class="shrink-0">
                        <input type="hidden" name="_csrf_token" value="<?= attendara_ui_escape(csrfToken()) ?>">
                        <input type="hidden" name="action" value="start_session">
                        <input type="hidden" name="assignment_id" value="<?= (int) $schedule['assignment_id'] ?>">
                        <input type="hidden" name="schedule_id" value="<?= (int) $schedule['id'] ?>">
                        <button type="submit" class="inline-flex min-h-10 w-full items-center justify-center rounded-lg bg-[#4e7a5d] px-4 py-2 text-sm font-semibold text-white hover:bg-[#41684f] sm:w-auto">Mulai Sesi</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php guru_page_end(); ?>
