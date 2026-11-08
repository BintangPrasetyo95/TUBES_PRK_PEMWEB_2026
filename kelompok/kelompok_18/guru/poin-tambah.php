<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$studentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 1;
$student = $guruUiData['students'][max(0, min(count($guruUiData['students']) - 1, $studentId - 1))];

guru_page_start('Tambah Poin Keaktifan', 'kelas.php');
?>
<section class="max-w-2xl rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:p-6">
    <div><h2 class="text-lg font-semibold text-[#20392a]"><?= attendara_ui_escape($student['name']) ?></h2><p class="mt-1 text-sm text-slate-500"><?= attendara_ui_escape($student['number']) ?> · Poin dicatat pada sesi ini</p></div>
    <form class="mt-5 space-y-4" onsubmit="return false;">
        <div><label for="participation-points" class="mb-1.5 block text-sm font-medium text-slate-700">Jumlah Poin</label><input id="participation-points" type="number" min="0" step="0.01" placeholder="Contoh: 5 atau 10" class="min-h-10 w-full rounded-lg border border-[#d8e2d8] px-3 text-sm focus:border-[#376c4d] focus:outline-none focus:ring-2 focus:ring-[#e6efe6]"></div>
        <div><label for="participation-note" class="mb-1.5 block text-sm font-medium text-slate-700">Catatan (opsional)</label><textarea id="participation-note" maxlength="500" rows="3" class="w-full rounded-lg border border-[#d8e2d8] px-3 py-2 text-sm focus:border-[#376c4d] focus:outline-none focus:ring-2 focus:ring-[#e6efe6]"></textarea></div>
        <div class="flex flex-wrap gap-2"><button id="save-points" type="button" class="min-h-10 rounded-lg bg-[#1e5638] px-4 text-sm font-semibold text-white">Tambah Poin</button><a href="absensi.php" class="inline-flex min-h-10 items-center rounded-lg border border-[#d8e2d8] px-4 text-sm font-semibold text-[#284b35]">Kembali</a></div>
        <p id="points-message" class="text-sm text-slate-500" role="status"></p>
    </form>
</section>
<script>document.getElementById('save-points').addEventListener('click', () => { document.getElementById('points-message').textContent = 'Poin hanya terlihat pada tampilan dan belum dicatat ke database.'; });</script>
<?php guru_page_end(); ?>
