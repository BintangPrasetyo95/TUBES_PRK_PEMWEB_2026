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
