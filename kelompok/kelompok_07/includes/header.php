<?php

require __DIR__ . '/head.php';

$flash = get_flash();
$halamanPenuh = $halamanPenuh ?? false;
?>
<?php require __DIR__ . '/navbar.php'; ?>
<main id="konten" class="main<?= $halamanPenuh ? ' main--penuh' : '' ?>">
<?php if (!$halamanPenuh): ?>
    <div class="container">
<?php endif; ?>
<?php if ($flash !== null): ?>
        <div class="<?= $halamanPenuh ? 'container ' : '' ?>flash">
            <div class="alert alert-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
        </div>
<?php endif; ?>
