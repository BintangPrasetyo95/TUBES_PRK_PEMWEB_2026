<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
startAppSession();
requireRole('guru');
require_once __DIR__ . '/../includes/ui/layout.php';
require_once __DIR__ . '/_helpers.php';

$guruNavigation = [
    ['label' => 'Dashboard', 'href' => 'dashboard.php', 'icon' => 'home'],
    ['label' => 'Kelas', 'href' => 'kelas.php', 'icon' => 'school'],
    ['label' => 'Arsip Pembelajaran', 'href' => 'arsip-pembelajaran.php', 'icon' => 'file'],
    ['label' => 'Logout', 'href' => '../logout.php', 'icon' => 'logout', 'type' => 'logout'],
];

/** Open the compact shared shell for Guru pages. */
function guru_page_start(string $title, string $activeHref): void
{
    global $guruNavigation;
    $headerIcon = 'file';
    foreach ($guruNavigation as $item) {
        if (($item['href'] ?? '') === $activeHref) {
            $headerIcon = (string) ($item['icon'] ?? 'file');
            break;
        }
    }
    attendara_ui_page_start($title, $guruNavigation, $activeHref, true, false, null, null, $headerIcon, true);
}

function guru_page_end(): void
{
    attendara_ui_page_end(false, true);
}

/** Keep the session identity, controls, and section navigation visible on every session page. */
function guru_session_header(array $session, string $activeMenu = '', bool $showNavigation = true): void
{
    $sessionId = (int) $session['id'];
    $status = (string) ($session['status'] ?? '');
    $isOngoing = $status === 'berlangsung';
    $attendanceCounts = $isOngoing
        ? guruSessionAttendanceCounts($sessionId)
        : ['roster_count' => 0, 'nonpresent_count' => 0];
    $finishReady = !$isOngoing
        || (int) ($session['jumlah_tap'] ?? 0) + $attendanceCounts['nonpresent_count'] === $attendanceCounts['roster_count'];
    $statusLabels = ['berlangsung' => 'Berlangsung', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan'];
    $statusLabel = $statusLabels[$status] ?? $status;
    $scheduleText = ($session['schedule_id'] ?? null) === null
        ? 'Tanpa jadwal'
        : (($session['day_label'] ?? '') . ' · ' . substr((string) ($session['scheduled_start'] ?? ''), 0, 5)
            . '–' . substr((string) ($session['scheduled_end'] ?? ''), 0, 5));
    $menuItems = [
        'tap' => ['Tap Kehadiran', 'tap-sesi.php?session_id=' . $sessionId],
        'attendance' => ['Data Siswa & Poin', 'absensi.php?session_id=' . $sessionId],
        'groups' => ['Kelompok', 'kelompok.php?session_id=' . $sessionId],
    ];
    ?>
    <section class="flex flex-col gap-4 rounded-2xl border border-[#dfe7dd] bg-white p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
        <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><span class="inline-flex rounded-full bg-[#e8f1e7] px-3 py-1 text-xs font-semibold text-[#31583c]"><?= attendara_ui_escape($statusLabel) ?></span><span class="text-sm text-slate-500"><?= attendara_ui_escape($session['subject_name'] ?? '') ?></span></div><h2 class="mt-2 text-lg font-semibold text-[#303832]"><?= attendara_ui_escape($session['class_name'] ?? '') ?></h2><p class="mt-1 text-sm text-slate-600"><?= attendara_ui_escape($scheduleText . ' · ' . ($session['semester'] ?? '') . ' · ' . date('d M Y', strtotime((string) $session['tanggal']))) ?></p></div>
        <?php if ($isOngoing): ?>
            <div class="flex shrink-0 flex-wrap gap-2"><button id="open-finish" type="button" data-tap-count="<?= (int) ($session['jumlah_tap'] ?? 0) ?>" data-nonpresent-count="<?= $attendanceCounts['nonpresent_count'] ?>" data-roster-count="<?= $attendanceCounts['roster_count'] ?>"<?= $finishReady ? '' : ' disabled title="Samakan tap dan kehadiran dulu."' ?> class="min-h-10 rounded-lg bg-[#4e7a5d] px-4 text-sm font-semibold text-white hover:bg-[#41684f] disabled:cursor-not-allowed disabled:opacity-50">Selesaikan Sesi</button><button id="open-cancel" type="button" class="min-h-10 rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Batalkan Sesi</button></div>
            <?php if (!$finishReady): ?><p id="finish-disabled-help" class="text-sm text-amber-800 sm:w-full">Samakan tap dan kehadiran siswa.</p><?php endif; ?>
        <?php elseif ($status === 'selesai'): ?>
            <form method="post" action="sesi-aktif.php?id=<?= $sessionId ?>" onsubmit="return window.confirm('Tandai sesi ini sebagai dibatalkan?')"><input type="hidden" name="_csrf_token" value="<?= attendara_ui_escape(csrfToken()) ?>"><input type="hidden" name="session_id" value="<?= $sessionId ?>"><input type="hidden" name="action" value="cancel_session"><button type="submit" class="min-h-10 rounded-lg border border-red-200 bg-white px-4 text-sm font-semibold text-red-700 hover:bg-red-50">Tandai Sesi Dibatalkan</button></form>
        <?php endif; ?>
    </section>
    <?php if ($showNavigation): ?>
        <nav aria-label="Menu sesi" class="flex flex-wrap gap-2">
            <?php foreach ($menuItems as $key => [$label, $href]): ?><a href="<?= attendara_ui_escape($href) ?>"<?= $activeMenu === $key ? ' aria-current="page"' : '' ?> class="rounded-lg px-4 py-2 text-sm font-semibold <?= $activeMenu === $key ? 'bg-[#4e7a5d] text-white' : 'border border-[#dfe6de] bg-white text-[#526257] hover:bg-[#f2f6f2]' ?>"><?= attendara_ui_escape($label) ?></a><?php endforeach; ?>
        </nav>
    <?php endif; ?>
    <?php if ($isOngoing): ?>
        <dialog id="finish-dialog" class="w-[min(28rem,calc(100%-2rem))] rounded-2xl border border-[#dfe7dd] p-0 shadow-xl backdrop:bg-slate-900/30"><div class="p-6"><h2 class="text-lg font-semibold text-[#303832]">Selesaikan sesi?</h2><p class="mt-2 text-sm text-slate-600">Pastikan status kehadiran dan poin sudah diperiksa.</p><div class="mt-6 flex justify-end gap-2"><button type="button" data-close="finish-dialog" class="min-h-10 rounded-lg border border-[#dfe6de] px-4 text-sm font-semibold">Kembali</button><form method="post" action="sesi-aktif.php?id=<?= $sessionId ?>" data-session-navigation><input type="hidden" name="_csrf_token" value="<?= attendara_ui_escape(csrfToken()) ?>"><input type="hidden" name="session_id" value="<?= $sessionId ?>"><input type="hidden" name="action" value="finish_session"><button type="submit"<?= $finishReady ? '' : ' disabled' ?> class="min-h-10 rounded-lg bg-[#4e7a5d] px-4 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">Selesaikan</button></form></div></div></dialog>
        <dialog id="cancel-dialog" class="w-[min(28rem,calc(100%-2rem))] rounded-2xl border border-[#dfe7dd] p-0 shadow-xl backdrop:bg-slate-900/30"><div class="p-6"><h2 class="text-lg font-semibold text-[#303832]">Batalkan sesi?</h2><p class="mt-2 text-sm text-slate-600">Sesi akan dibatalkan dan Anda kembali ke halaman Kelas.</p><div class="mt-6 flex justify-end gap-2"><button type="button" data-close="cancel-dialog" class="min-h-10 rounded-lg border border-[#dfe6de] px-4 text-sm font-semibold">Kembali</button><button id="confirm-cancel-session" type="button" class="min-h-10 rounded-lg bg-red-600 px-4 text-sm font-semibold text-white">Ya, Batalkan Sesi</button></div></div></dialog>
        <dialog id="leave-session-dialog" class="w-[min(28rem,calc(100%-2rem))] rounded-2xl border border-[#dfe7dd] p-0 shadow-xl backdrop:bg-slate-900/30"><div class="p-6"><h2 class="text-lg font-semibold text-[#303832]">Pindah halaman?</h2><p class="mt-2 text-sm text-slate-600">Sesi yang berlangsung akan dibatalkan sebelum Anda pindah.</p><div class="mt-6 flex justify-end gap-2"><button type="button" data-close="leave-session-dialog" class="min-h-10 rounded-lg border border-[#dfe6de] px-4 text-sm font-semibold">Tetap di Sesi</button><button id="confirm-leave-session" type="button" class="min-h-10 rounded-lg bg-red-600 px-4 text-sm font-semibold text-white">Batalkan &amp; Pindah</button></div></div></dialog>
        <form method="post" action="sesi-aktif.php?id=<?= $sessionId ?>" data-cancel-session class="hidden"><input type="hidden" name="_csrf_token" value="<?= attendara_ui_escape(csrfToken()) ?>"><input type="hidden" name="session_id" value="<?= $sessionId ?>"><input type="hidden" name="action" value="cancel_session"><input type="hidden" name="return_to" value=""><button type="submit" tabindex="-1" aria-hidden="true"></button></form>
        <script>
        const finishDialog = document.getElementById('finish-dialog');
        const cancelDialog = document.getElementById('cancel-dialog');
        const leaveDialog = document.getElementById('leave-session-dialog');
        let pendingSessionLeave = null;
        document.getElementById('open-finish').addEventListener('click', () => finishDialog.showModal());
        document.getElementById('open-cancel').addEventListener('click', () => cancelDialog.showModal());
        document.querySelectorAll('[data-close]').forEach(button => button.addEventListener('click', () => document.getElementById(button.dataset.close).close()));
        document.getElementById('confirm-leave-session').addEventListener('click', async () => {
            const action = pendingSessionLeave;
            pendingSessionLeave = null;
            leaveDialog.close();
            if (action) await action();
        });
        const confirmSessionLeave = action => {
            pendingSessionLeave = action;
            leaveDialog.showModal();
        };
        const finishButton = document.getElementById('open-finish');
        window.addEventListener('attendara:tap-count', event => {
            const tapCount = Number(event.detail.count);
            finishButton.dataset.tapCount = String(tapCount);
            const ready = tapCount + Number(finishButton.dataset.nonpresentCount) === Number(finishButton.dataset.rosterCount);
            finishButton.disabled = !ready;
            const finishSubmit = document.querySelector('#finish-dialog button[type="submit"]');
            if (finishSubmit) finishSubmit.disabled = !ready;
            document.getElementById('finish-disabled-help')?.toggleAttribute('hidden', ready);
        });
        let sessionNavigationAllowed = false;
        let sessionCancelledForNavigation = false;
        const internalSessionPages = new Set([
            'tap-sesi.php', 'absensi.php', 'absensi-edit.php', 'poin-tambah.php',
            'kelompok.php', 'kelompok-baru.php', 'kelompok-edit.php', 'sesi-aktif.php',
        ]);
        const isInternalSessionUrl = target => {
            const url = new URL(target, window.location.href);
            return url.origin === window.location.origin && internalSessionPages.has(url.pathname.split('/').pop());
        };
        const cancelSessionBeforeLeaving = async () => {
            const cancelForm = document.querySelector('[data-cancel-session]');
            const response = await fetch(cancelForm.action, {
                method: 'POST',
                body: new FormData(cancelForm),
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error((await response.text()) || 'Sesi tidak dapat dibatalkan.');
            sessionCancelledForNavigation = true;
            sessionNavigationAllowed = true;
        };
        document.getElementById('confirm-cancel-session').addEventListener('click', () => {
            const cancelForm = document.querySelector('[data-cancel-session]');
            const target = new URL('kelas.php', window.location.href);
            cancelForm.querySelector('[name="return_to"]').value = target.pathname + target.search;
            sessionNavigationAllowed = true;
            cancelDialog.close();
            cancelForm.submit();
        });
        document.addEventListener('click', event => {
            const link = event.target.closest('a[href]');
            if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            if (link.getAttribute('href')?.startsWith('#')) return;
            if (isInternalSessionUrl(link.href)) {
                sessionNavigationAllowed = true;
                return;
            }
            event.preventDefault();
            const target = new URL(link.href);
            confirmSessionLeave(() => {
                const cancelForm = document.querySelector('[data-cancel-session]');
                cancelForm.querySelector('[name="return_to"]').value = target.pathname + target.search + target.hash;
                sessionNavigationAllowed = true;
                cancelForm.submit();
            });
        });
        document.addEventListener('submit', async event => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;
            if (form.hasAttribute('data-session-navigation') || form.closest('#finish-dialog') || isInternalSessionUrl(form.action)) {
                sessionNavigationAllowed = true;
                return;
            }
            event.preventDefault();
            confirmSessionLeave(async () => {
                try {
                    await cancelSessionBeforeLeaving();
                    HTMLFormElement.prototype.submit.call(form);
                } catch (error) {
                    window.alert(error.message);
                }
                sessionNavigationAllowed = true;
            });
        });
        window.addEventListener('beforeunload', event => {
            if (sessionNavigationAllowed || sessionCancelledForNavigation) return;
            event.preventDefault();
            event.returnValue = '';
        });
        window.addEventListener('pagehide', () => {
            if (sessionNavigationAllowed || sessionCancelledForNavigation) return;
            const cancelForm = document.querySelector('[data-cancel-session]');
            if (cancelForm) navigator.sendBeacon(cancelForm.action, new URLSearchParams(new FormData(cancelForm)));
        });
        </script>
    <?php endif; ?>
    <?php
}

/** Render a visual-only action button. */
function guru_ui_button(string $label, string $variant = 'primary'): void
{
    $classes = $variant === 'secondary'
        ? 'border border-[#dfe6de] bg-white text-[#41684f] hover:bg-[#f2f6f2]'
        : 'bg-[#4e7a5d] text-white hover:bg-[#41684f]';
    ?>
    <button type="button" class="inline-flex min-h-10 items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#679173] focus-visible:ring-offset-2 <?= $classes ?>">
        <?= attendara_ui_escape($label) ?>
    </button>
    <?php
}

/** Render a responsive table from escaped text values. */
function guru_render_table(array $headers, array $rows, string $emptyMessage = 'Belum ada data.'): void
{
    ?>
    <div class="overflow-x-auto rounded-2xl border border-[#dfe6de] bg-white shadow-[0_2px_8px_rgba(48,56,50,0.05)]">
        <table class="min-w-full divide-y divide-[#e6ebe5] text-left text-sm">
            <thead class="bg-[#eff3ee] text-xs uppercase tracking-wide text-slate-500"><tr>
                <?php foreach ($headers as $header): ?><th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5"><?= attendara_ui_escape((string) $header) ?></th><?php endforeach; ?>
            </tr></thead>
            <tbody class="divide-y divide-[#e6ebe5] text-slate-700">
                <?php if ($rows === []): ?>
                    <tr><td colspan="<?= max(count($headers), 1) ?>" class="px-4 py-8 text-center text-slate-500"><?= attendara_ui_escape($emptyMessage) ?></td></tr>
                <?php else: foreach ($rows as $row): ?>
                    <tr class="transition-colors hover:bg-[#fafbf9]"><?php foreach ($row as $cell): ?><td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= attendara_ui_escape((string) $cell) ?></td><?php endforeach; ?></tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
