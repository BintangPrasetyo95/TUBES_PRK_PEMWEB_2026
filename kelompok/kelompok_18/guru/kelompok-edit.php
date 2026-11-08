<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$members = array_slice($guruUiData['students'], 0, 5);

guru_page_start('Edit Kelompok', 'kelas.php');
?>
<section class="max-w-3xl space-y-5 rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:p-6">
    <div><h2 class="text-lg font-semibold text-[#20392a]">Edit Kelompok 1</h2><p class="mt-1 text-sm text-slate-600">Kelas X-A · 2026/2027 Ganjil</p></div>
    <form onsubmit="return false;" class="space-y-4">
        <div><label for="group-name-edit" class="mb-1.5 block text-sm font-medium text-slate-700">Nama Kelompok</label><input id="group-name-edit" value="Kelompok 1" class="min-h-10 w-full rounded-lg border border-[#d8e2d8] px-3 text-sm"></div>
        <fieldset><legend class="mb-2 text-sm font-medium text-slate-700">Anggota</legend><div class="divide-y divide-[#edf1ec] rounded-xl border border-[#e2e9e2]">
            <?php foreach ($guruUiData['students'] as $index => $student): ?>
                <label class="flex items-center gap-3 px-3 py-2.5"><input type="checkbox" value="<?= (int) $student['id'] ?>" <?= $index < 5 ? 'checked' : '' ?> class="h-4 w-4 rounded border-[#bac9ba] text-[#1e5638] focus:ring-[#376c4d]"><span class="text-sm text-slate-700"><?= attendara_ui_escape($student['name'] . ' · ' . $student['number']) ?></span></label>
            <?php endforeach; ?>
        </div></fieldset>
        <div class="flex flex-wrap gap-2"><button id="save-group-edit" type="button" class="min-h-10 rounded-lg bg-[#1e5638] px-4 text-sm font-semibold text-white">Simpan Perubahan</button><a href="kelompok.php" class="inline-flex min-h-10 items-center rounded-lg border border-[#d8e2d8] px-4 text-sm font-semibold text-[#284b35]">Batal</a></div>
        <p id="edit-result" class="text-sm text-slate-500" role="status"></p>
    </form>
</section>
<script>document.getElementById('save-group-edit').addEventListener('click', () => { document.getElementById('edit-result').textContent = 'Perubahan belum disimpan ke database.'; });</script>
<?php guru_page_end(); ?>
