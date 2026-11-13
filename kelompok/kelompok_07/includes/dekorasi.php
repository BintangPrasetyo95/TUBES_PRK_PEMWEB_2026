<?php

require_once __DIR__ . '/komponen.php';

function dekor_swoosh(string $class): string
{
    return '<svg class="dekor dekor-swoosh ' . e($class) . '" viewBox="0 0 600 320" fill="none" aria-hidden="true" focusable="false" preserveAspectRatio="none">'
        . '<path d="M-40 290C90 150 210 330 330 190S520 30 640 110" stroke="currentColor" stroke-width="28" stroke-linecap="round"/>'
        . '<path d="M-40 330C140 250 250 320 380 260S560 180 660 220" stroke="currentColor" stroke-width="10" stroke-linecap="round" opacity=".55"/>'
        . '</svg>';
}

function dekor_panah(string $class): string
{
    return '<svg class="dekor dekor-panah ' . e($class) . '" viewBox="0 0 160 110" fill="none" aria-hidden="true" focusable="false">'
        . '<path d="M8 86C34 18 92 6 102 46c8 34-40 44-32 12 7-30 52-34 78-14" stroke="currentColor" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>'
        . '<path d="M126 22l23 22-29 9" stroke="currentColor" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>'
        . '</svg>';
}

function dekor_spiral(string $class): string
{
    return '<svg class="dekor dekor-spiral ' . e($class) . '" viewBox="0 0 120 120" fill="none" aria-hidden="true" focusable="false">'
        . '<path d="M60 62c0-5 6-6 8-2 3 6-4 12-10 10-9-3-9-16-1-21 11-7 25 1 25 14 0 16-17 25-31 20-17-6-21-28-10-41 13-16 41-14 51 4" stroke="currentColor" stroke-width="7" stroke-linecap="round"/>'
        . '</svg>';
}

function dekor_grid(): string
{
    return '<span class="dekor dekor-grid" aria-hidden="true"></span>';
}

function lencana_putar(string $id, string $teks, string $href, string $label): string
{
    $pathId = 'lencana-path-' . $id;

    return '<a class="lencana" href="' . e($href) . '" aria-label="' . e($label) . '">'
        . '<svg viewBox="0 0 200 200" aria-hidden="true" focusable="false">'
        . '<defs><path id="' . e($pathId) . '" d="M100 100m-72 0a72 72 0 1 1 144 0a72 72 0 1 1-144 0"/></defs>'
        . '<circle cx="100" cy="100" r="98" class="lencana__latar"/>'
        . '<g class="lencana__putar"><text class="lencana__teks"><textPath href="#' . e($pathId) . '" textLength="440">' . e($teks) . '</textPath></text></g>'
        . '<path class="lencana__coretan" d="M74 116c10-30 30-40 34-26 4 13-24 22-14 4 9-16 32-12 34 4" fill="none" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>'
        . '</svg>'
        . '</a>';
}

function auth_panel(string $judul, string $sorotan, string $teks, string $tautanLabel, string $tautanHref): void
{
    ?>
<section class="auth-panel">
    <?= dekor_grid() ?>
    <?= dekor_swoosh('auth-panel__swoosh') ?>
    <a class="logo-bubble logo-bubble--terang" href="<?= e(url('index.php')) ?>" aria-label="<?= e(APP_NAME) ?> - kembali ke beranda">
        <span class="logo-bubble__lab">Lab</span><span class="logo-bubble__loan">Loan</span>
    </a>
    <div class="auth-panel__isi">
        <h1 class="auth-panel__judul"><?= e($judul) ?> <span class="sorot-neon"><?= e($sorotan) ?></span></h1>
        <p><?= e($teks) ?></p>
        <a href="<?= e($tautanHref) ?>" class="btn btn-ghost-light btn-lg"><?= e($tautanLabel) ?><?= ikon('panah', 'ikon ikon--sm') ?></a>
    </div>
    <?= dekor_panah('auth-panel__panah') ?>
</section>
    <?php
}

function page_head(string $tag, string $judul, string $sorotan, string $teks): void
{
    ?>
<section class="page-head on-dark">
    <?= dekor_grid() ?>
    <?= dekor_swoosh('page-head__swoosh') ?>
    <span class="tag tag--neon"><?= e($tag) ?></span>
    <h1><?= e($judul) ?> <span class="sorot-neon"><?= e($sorotan) ?></span></h1>
    <p><?= e($teks) ?></p>
</section>
    <?php
}
