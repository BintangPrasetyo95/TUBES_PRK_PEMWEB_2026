<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$studentJson = json_encode(array_map(static fn (array $student): array => ['name' => $student['name'], 'number' => $student['number']], $guruUiData['students']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

guru_page_start('Tambah Kelompok', 'kelas.php');
?>
<section class="rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:p-6">
    <div><h2 class="text-lg font-semibold text-[#20392a]">Bentuk Kelompok</h2><p class="mt-1 text-sm text-slate-600">Atur jumlah kelompok atau jumlah anggota, lalu acak anggota siswa.</p></div>
    <p class="mt-1 text-sm text-slate-500">Kelas X-A · 2026/2027 Ganjil</p>
    <div class="mt-5 grid gap-4 sm:grid-cols-2">
        <div><label for="group-name" class="mb-1.5 block text-sm font-medium text-slate-700">Nama Kelompok</label><input id="group-name" type="text" value="Kelompok" class="min-h-10 w-full rounded-lg border border-[#d8e2d8] px-3 text-sm"></div>
        <div><label for="group-rule" class="mb-1.5 block text-sm font-medium text-slate-700">Kriteria pembagian</label><select id="group-rule" class="min-h-10 w-full rounded-lg border border-[#d8e2d8] bg-white px-3 text-sm"><option value="groups">Jumlah kelompok</option><option value="members">Jumlah siswa per kelompok</option></select></div>
        <div><label id="group-value-label" for="group-value" class="mb-1.5 block text-sm font-medium text-slate-700">Jumlah kelompok</label><input id="group-value" type="number" min="1" max="20" value="4" class="min-h-10 w-full rounded-lg border border-[#d8e2d8] px-3 text-sm focus:border-[#376c4d] focus:outline-none focus:ring-2 focus:ring-[#e6efe6]"></div>
    </div>
    <div class="mt-5 flex flex-wrap gap-2"><button id="randomize-groups" type="button" class="min-h-10 rounded-lg bg-[#1e5638] px-4 text-sm font-semibold text-white hover:bg-[#17452d]">Acak Anggota</button><button id="save-groups" type="button" class="min-h-10 rounded-lg border border-[#d8e2d8] bg-white px-4 text-sm font-semibold text-[#284b35] hover:bg-[#f2f6f2]">Simpan Kelompok</button></div>
    <p id="group-message" class="mt-3 text-sm text-slate-500" role="status">Hasil pengacakan hanya ditampilkan pada halaman ini.</p>
</section>
<section class="space-y-4"><div><h2 class="text-lg font-semibold text-[#20392a]">Hasil Pembagian</h2></div><div id="group-results" class="grid gap-4 md:grid-cols-2"></div></section>
<a href="kelompok.php" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#284b35]">Kembali ke Kelompok</a>
<script>
const students = <?= $studentJson ?>;
const rule = document.getElementById('group-rule');
const valueInput = document.getElementById('group-value');
const results = document.getElementById('group-results');
rule.addEventListener('change', () => {
    document.getElementById('group-value-label').textContent = rule.value === 'groups' ? 'Jumlah kelompok' : 'Jumlah siswa per kelompok';
    valueInput.value = rule.value === 'groups' ? '4' : '5';
});
document.getElementById('randomize-groups').addEventListener('click', () => {
    const value = Math.max(1, Math.min(students.length, Number(valueInput.value) || 1));
    const count = rule.value === 'groups' ? value : Math.ceil(students.length / value);
    const membersPerGroup = rule.value === 'members' ? value : Math.ceil(students.length / count);
    const shuffled = [...students];
    for (let index = shuffled.length - 1; index > 0; index--) {
        const swap = Math.floor(Math.random() * (index + 1));
        [shuffled[index], shuffled[swap]] = [shuffled[swap], shuffled[index]];
    }
    const groups = Array.from({length: count}, () => []);
    shuffled.forEach((student, index) => {
        const groupIndex = rule.value === 'groups' ? index % count : Math.floor(index / membersPerGroup);
        if (groups[groupIndex]) groups[groupIndex].push(student);
    });
    results.replaceChildren();
    groups.filter(group => group.length).forEach((group, index) => {
        const card = document.createElement('article');
        card.className = 'rounded-2xl border border-[#e2e9e2] bg-white p-5';
        const groupName = document.getElementById('group-name').value.trim() || 'Kelompok';
        const heading = document.createElement('h3'); heading.className = 'font-semibold text-[#20392a]'; heading.textContent = `${groupName} ${index + 1} · ${group.length} siswa`;
        const list = document.createElement('ul'); list.className = 'mt-3 space-y-2 text-sm text-slate-600';
        group.forEach(student => { const item = document.createElement('li'); item.textContent = `${student.name} · ${student.number}`; list.append(item); });
        card.append(heading, list); results.append(card);
    });
    document.getElementById('group-message').textContent = 'Anggota diacak pada tampilan ini dan belum disimpan.';
});
document.getElementById('save-groups').addEventListener('click', () => {
    document.getElementById('group-message').textContent = 'Penyimpanan kelompok belum terhubung.';
});
</script>
<?php guru_page_end(); ?>
