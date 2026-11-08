<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$class = $guruUiData['classes'][0];
$schedule = $guruUiData['schedules'][0];

guru_page_start('Sesi Pembelajaran', 'kelas.php');
?>
<section class="flex flex-col gap-4 rounded-2xl border border-[#dfe7dd] bg-white p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
    <div><span id="session-status" class="inline-flex rounded-full bg-[#e8f1e7] px-3 py-1 text-xs font-semibold text-[#31583c]">Berlangsung</span><h2 class="mt-3 text-xl font-semibold text-[#20392a]"><?= attendara_ui_escape($class['name']) ?></h2><p class="mt-1 text-sm text-slate-600"><?= attendara_ui_escape($schedule['day_label'] . ' · ' . $schedule['start'] . '–' . $schedule['end'] . ' · ' . $class['semester']) ?></p></div>
    <div id="session-actions" class="flex flex-wrap gap-2"><button id="open-finish" type="button" class="min-h-10 rounded-lg bg-[#1e5638] px-4 text-sm font-semibold text-white hover:bg-[#17452d]">Selesaikan Sesi</button><button id="open-cancel" type="button" class="min-h-10 rounded-lg border border-red-200 bg-white px-4 text-sm font-semibold text-red-700 hover:bg-red-50">Batalkan Sesi</button></div>
</section>

<section class="space-y-4">
    <div><h2 class="text-lg font-semibold text-[#20392a]">Menu Dalam Sesi</h2></div>
    <div class="grid gap-4 md:grid-cols-3">
        <a href="tap-sesi.php" class="rounded-2xl border border-[#e2e9e2] bg-white p-5 hover:border-[#a9c3ad] hover:bg-[#fbfcfa]"><h3 class="font-semibold text-[#20392a]">Tap Kehadiran</h3><p class="mt-2 text-sm text-slate-500">Tandai siswa yang sudah melakukan tap.</p></a>
        <a href="absensi.php" class="rounded-2xl border border-[#e2e9e2] bg-white p-5 hover:border-[#a9c3ad] hover:bg-[#fbfcfa]"><h3 class="font-semibold text-[#20392a]">Data Siswa &amp; Poin</h3><p class="mt-2 text-sm text-slate-500">Atur status kehadiran dan poin siswa.</p></a>
        <a href="kelompok.php" class="rounded-2xl border border-[#e2e9e2] bg-white p-5 hover:border-[#a9c3ad] hover:bg-[#fbfcfa]"><h3 class="font-semibold text-[#20392a]">Kelompok</h3><p class="mt-2 text-sm text-slate-500">Buat dan acak kelompok siswa.</p></a>
    </div>
</section>
<div id="session-result" class="hidden rounded-xl border border-[#dfe7dd] bg-[#f3f6f1] p-4 text-sm text-[#526257]" role="status"></div>
<a href="kelas-detail.php?id=1" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#284b35] hover:bg-[#f2f6f2]">Kembali ke Kelas</a>

<dialog id="finish-dialog" class="w-[min(28rem,calc(100%-2rem))] rounded-2xl border border-[#dfe7dd] p-0 shadow-xl backdrop:bg-slate-900/30"><div class="p-6"><h2 class="text-lg font-semibold text-[#20392a]">Selesaikan sesi?</h2><p class="mt-2 text-sm text-slate-600">Pastikan status kehadiran dan poin sudah diperiksa.</p><div class="mt-6 flex justify-end gap-2"><button data-close="finish-dialog" class="min-h-10 rounded-lg border border-[#d8e2d8] px-4 text-sm font-semibold">Kembali</button><button id="confirm-finish" class="min-h-10 rounded-lg bg-[#1e5638] px-4 text-sm font-semibold text-white">Selesaikan</button></div></div></dialog>
<dialog id="cancel-dialog" class="w-[min(28rem,calc(100%-2rem))] rounded-2xl border border-[#dfe7dd] p-0 shadow-xl backdrop:bg-slate-900/30"><div class="p-6"><h2 class="text-lg font-semibold text-[#20392a]">Batalkan sesi?</h2><p class="mt-2 text-sm text-slate-600">Sesi ini akan ditandai batal pada tampilan.</p><div class="mt-6 flex justify-end gap-2"><button data-close="cancel-dialog" class="min-h-10 rounded-lg border border-[#d8e2d8] px-4 text-sm font-semibold">Kembali</button><button id="confirm-cancel" class="min-h-10 rounded-lg bg-red-600 px-4 text-sm font-semibold text-white">Batalkan Sesi</button></div></div></dialog>
<script>
const finishDialog = document.getElementById('finish-dialog');
const cancelDialog = document.getElementById('cancel-dialog');
document.getElementById('open-finish').addEventListener('click', () => finishDialog.showModal());
document.getElementById('open-cancel').addEventListener('click', () => cancelDialog.showModal());
document.querySelectorAll('[data-close]').forEach(button => button.addEventListener('click', () => document.getElementById(button.dataset.close).close()));
const closeSession = (label, message, tone) => {
    document.getElementById('session-status').textContent = label;
    document.getElementById('session-status').className = `inline-flex rounded-full px-3 py-1 text-xs font-semibold ${tone}`;
    document.getElementById('session-actions').classList.add('hidden');
    const result = document.getElementById('session-result');
    result.textContent = message;
    result.classList.remove('hidden');
};
document.getElementById('confirm-finish').addEventListener('click', () => { finishDialog.close(); closeSession('Selesai', 'Sesi ditandai selesai pada tampilan ini. Pencatatan ke arsip belum terhubung.', 'bg-[#e8f1e7] text-[#31583c]'); });
document.getElementById('confirm-cancel').addEventListener('click', () => { cancelDialog.close(); closeSession('Dibatalkan', 'Sesi ditandai batal pada tampilan ini. Tidak ada perubahan yang disimpan.', 'bg-red-50 text-red-700'); });
</script>
<?php guru_page_end(); ?>
