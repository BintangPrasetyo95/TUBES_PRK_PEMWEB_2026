<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$class = $guruUiData['classes'][0];

guru_page_start('Tap Kehadiran', 'kelas.php');
?>
<nav aria-label="Menu sesi" class="flex flex-wrap gap-2">
    <a href="tap-sesi.php" aria-current="page" class="rounded-lg bg-[#1e5638] px-4 py-2 text-sm font-semibold text-white">Tap Kehadiran</a>
    <a href="absensi.php" class="rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#526257]">Data Siswa &amp; Poin</a>
    <a href="kelompok.php" class="rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#526257]">Kelompok</a>
</nav>
<section class="flex flex-col gap-4 rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
    <div><h2 class="text-lg font-semibold text-[#20392a]">Tap Siswa · <?= attendara_ui_escape($class['name']) ?></h2><p class="mt-1 text-sm text-slate-600">Tap dikonfirmasi setelah jeda 2 detik. Hitungan dan waktu tap hanya berlaku pada tampilan.</p></div>
    <div class="rounded-xl bg-[#e9f0e8] px-4 py-3 text-center"><p class="text-xs font-medium text-[#526257]">Tap tercatat</p><p class="mt-1 text-2xl font-semibold text-[#173e2b]"><span id="tap-count">0</span> / <?= count($guruUiData['students']) ?></p></div>
</section>
<section class="space-y-4">
    <div><h2 class="text-lg font-semibold text-[#20392a]">Daftar Siswa</h2></div>
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <?php foreach ($guruUiData['students'] as $student): ?>
            <article class="flex items-center justify-between gap-3 rounded-2xl border border-[#e2e9e2] bg-white p-4" data-student-card data-student-id="<?= (int) $student['id'] ?>">
                <div class="min-w-0"><p class="truncate text-sm font-semibold text-[#20392a]"><?= attendara_ui_escape($student['name']) ?></p><p class="mt-1 text-xs text-slate-500"><?= attendara_ui_escape($student['number']) ?></p><p class="mt-1 text-xs text-slate-400" data-tap-time>Belum tap</p></div>
                <button type="button" data-tap-button class="min-h-9 shrink-0 rounded-lg border border-[#d8e2d8] px-3 text-xs font-semibold text-[#284b35] hover:bg-[#f2f6f2]">Tap Masuk</button>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<div class="flex flex-wrap gap-2"><a href="sesi-aktif.php?id=1" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#284b35]">Kembali ke Sesi</a><a href="absensi.php" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1e5638] px-4 py-2 text-sm font-semibold text-white">Lanjut ke Data Siswa</a></div>
<script>
let taps = 0;
let lastTapAt = null;
document.querySelectorAll('[data-tap-card]');
document.querySelectorAll('[data-student-card]').forEach(card => {
    const button = card.querySelector('[data-tap-button]');
    button.addEventListener('click', () => {
        if (button.dataset.tapped === 'true' || button.disabled) return;
        button.disabled = true;
        button.textContent = 'Memeriksa…';
        window.setTimeout(() => {
            const now = new Date();
            const time = now.toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit', second: '2-digit'});
            let detail = `Tap diterima ${time}`;
            if (lastTapAt !== null) detail += ` · jeda ${(now.getTime() - lastTapAt) / 1000} dtk dari tap sebelumnya`;
            lastTapAt = now.getTime();
            card.querySelector('[data-tap-time]').textContent = detail;
            card.classList.add('border-[#9db99f]', 'bg-[#f7faf6]');
            button.dataset.tapped = 'true';
            button.textContent = 'Sudah Tap';
            button.classList.add('border-[#cfe0ce]', 'bg-[#e8f1e7]', 'text-[#31583c]');
            taps++;
            document.getElementById('tap-count').textContent = String(taps);
        }, 2000);
    });
});
</script>
<?php guru_page_end(); ?>
