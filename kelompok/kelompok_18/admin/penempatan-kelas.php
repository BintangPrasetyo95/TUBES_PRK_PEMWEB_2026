<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

// Fixture kecil untuk UI: siswa yang sudah punya anggota_kelas pada semester
// terpilih tidak ditawarkan lagi untuk kelas lain pada semester tersebut.
$students = [
    ['id' => 1, 'number' => 'ATT-2026-001', 'name' => 'Siswa 01'],
    ['id' => 2, 'number' => 'ATT-2026-002', 'name' => 'Siswa 02'],
    ['id' => 3, 'number' => 'ATT-2026-003', 'name' => 'Siswa 03'],
    ['id' => 4, 'number' => 'ATT-2026-004', 'name' => 'Siswa 04'],
    ['id' => 5, 'number' => 'ATT-2026-005', 'name' => 'Siswa 05'],
    ['id' => 6, 'number' => 'ATT-2026-006', 'name' => 'Siswa 06'],
    ['id' => 7, 'number' => 'ATT-2026-007', 'name' => 'Siswa 07'],
    ['id' => 8, 'number' => 'ATT-2026-008', 'name' => 'Siswa 08'],
];
$placements = [
    ['studentId' => 1, 'classId' => 1, 'semesterId' => 2],
    ['studentId' => 2, 'classId' => 1, 'semesterId' => 2],
    ['studentId' => 3, 'classId' => 2, 'semesterId' => 2],
];
$studentJson = json_encode($students, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$placementJson = json_encode($placements, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

admin_page_start('Penempatan Kelas', 'penempatan-kelas.php');
?>
<section class="grid gap-4 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
    <div class="space-y-4 rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:p-6">
        <div><h2 class="text-base font-semibold text-[#20392a]">Pilih Penempatan</h2><p class="mt-1 text-sm text-slate-600">Penempatan dibuat manual untuk satu kelas dan semester.</p></div>
        <div>
            <label for="semester-placement" class="mb-1.5 block text-sm font-medium text-slate-700">Semester</label>
            <select id="semester-placement" class="min-h-10 w-full rounded-lg border border-[#d8e2d8] bg-white px-3 text-sm"><option value="2">2026/2027 Ganjil</option><option value="1">2025/2026 Genap</option></select>
        </div>
        <div>
            <label for="class-placement" class="mb-1.5 block text-sm font-medium text-slate-700">Kelas</label>
            <select id="class-placement" class="min-h-10 w-full rounded-lg border border-[#d8e2d8] bg-white px-3 text-sm"><option value="1">Kelas X-A</option><option value="2">Kelas X-B</option><option value="3">Kelas X-C</option></select>
        </div>
        <div class="rounded-xl bg-[#f3f6f1] p-3 text-sm text-[#526257]">Siswa yang sudah memiliki penempatan pada semester terpilih tidak ditampilkan sebagai pilihan kelas lain.</div>
    </div>
    <div class="space-y-4 rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:p-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div><h2 class="text-base font-semibold text-[#20392a]">Pilih Siswa</h2><p id="candidate-count" class="mt-1 text-sm text-slate-600" aria-live="polite"></p></div>
            <label class="inline-flex items-center gap-2 text-sm text-slate-600"><input id="select-visible" type="checkbox" class="h-4 w-4 rounded border-[#bac9ba] text-[#1e5638] focus:ring-[#376c4d]"> Pilih semua hasil</label>
        </div>
        <div>
            <label for="student-search" class="mb-1.5 block text-sm font-medium text-slate-700">Cari siswa</label>
            <input id="student-search" type="search" placeholder="Nama atau nomor induk" class="min-h-10 w-full rounded-lg border border-[#d8e2d8] bg-white px-3 text-sm focus:border-[#376c4d] focus:outline-none focus:ring-2 focus:ring-[#e6efe6]">
        </div>
        <div id="student-options" class="max-h-72 space-y-1 overflow-y-auto rounded-xl border border-[#e2e9e2] p-2" aria-label="Daftar siswa tersedia"></div>
        <div class="flex flex-wrap items-center justify-between gap-3"><span id="selected-count" class="text-sm text-slate-500" aria-live="polite">0 siswa dipilih</span><?php admin_ui_button('Tempatkan Siswa'); ?></div>
    </div>
</section>

<section class="space-y-4">
    <div><h2 class="text-lg font-semibold text-[#20392a]">Siswa pada Kelas</h2><p id="placement-context" class="mt-1 text-sm text-slate-600"></p></div>
    <div id="placement-list"></div>
</section>

<script>
(() => {
    const students = <?= $studentJson ?>;
    let placements = <?= $placementJson ?>;
    const semester = document.getElementById('semester-placement');
    const classroom = document.getElementById('class-placement');
    const search = document.getElementById('student-search');
    const list = document.getElementById('student-options');
    const candidateCount = document.getElementById('candidate-count');
    const selectedCount = document.getElementById('selected-count');
    const allVisible = document.getElementById('select-visible');
    const classes = {1: 'Kelas X-A', 2: 'Kelas X-B', 3: 'Kelas X-C'};
    const semesters = {1: '2025/2026 Genap', 2: '2026/2027 Ganjil'};
    const selectedIds = new Set();

    const getAvailable = () => {
        const assigned = new Set(placements.filter(item => item.semesterId === Number(semester.value)).map(item => item.studentId));
        const query = search.value.trim().toLocaleLowerCase('id');
        return students.filter(student => !assigned.has(student.id) && `${student.number} ${student.name}`.toLocaleLowerCase('id').includes(query));
    };

    const render = () => {
        const available = getAvailable();
        list.replaceChildren();
        candidateCount.textContent = `${available.length} siswa tersedia`;
        available.forEach(student => {
            const label = document.createElement('label');
            label.className = 'flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 hover:bg-[#f3f6f1]';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.value = String(student.id);
            checkbox.checked = selectedIds.has(student.id);
            checkbox.className = 'student-choice h-4 w-4 rounded border-[#bac9ba] text-[#1e5638] focus:ring-[#376c4d]';
            const text = document.createElement('span');
            text.className = 'min-w-0';
            const name = document.createElement('span');
            name.className = 'block text-sm font-medium text-slate-700';
            name.textContent = student.name;
            const number = document.createElement('span');
            number.className = 'block text-xs text-slate-500';
            number.textContent = student.number;
            text.append(name, number);
            label.append(checkbox, text);
            list.append(label);
        });
        if (available.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'px-3 py-8 text-center text-sm text-slate-500';
            empty.textContent = 'Tidak ada siswa tersedia untuk pilihan ini.';
            list.append(empty);
        }
        allVisible.checked = false;
        updateSelected();
        renderPlaced();
    };

    const updateSelected = () => {
        selectedCount.textContent = `${list.querySelectorAll('.student-choice:checked').length} siswa dipilih`;
    };

    const renderPlaced = () => {
        const chosen = placements.filter(item => item.semesterId === Number(semester.value) && item.classId === Number(classroom.value));
        document.getElementById('placement-context').textContent = `${classes[classroom.value]} · ${semesters[semester.value]} · ${chosen.length} siswa`;
        const headers = ['Nomor Induk', 'Nama Siswa'];
        const rows = chosen.map(item => {
            const student = students.find(entry => entry.id === item.studentId);
            return student ? [student.number, student.name] : [];
        }).filter(row => row.length);
        const table = document.createElement('div');
        table.className = 'overflow-x-auto rounded-2xl border border-[#e2e9e2] bg-white';
        const tableEl = document.createElement('table');
        tableEl.className = 'min-w-full divide-y divide-[#edf1ec] text-left text-sm';
        const thead = document.createElement('thead');
        thead.className = 'bg-[#f3f6f1] text-xs uppercase tracking-wide text-slate-500';
        const headRow = document.createElement('tr');
        headers.forEach(value => { const th = document.createElement('th'); th.className = 'px-4 py-3.5 font-semibold'; th.textContent = value; headRow.append(th); });
        thead.append(headRow);
        const tbody = document.createElement('tbody');
        tbody.className = 'divide-y divide-[#edf1ec] text-slate-700';
        if (rows.length === 0) {
            const tr = document.createElement('tr'); const td = document.createElement('td');
            td.colSpan = 2; td.className = 'px-4 py-8 text-center text-slate-500'; td.textContent = 'Belum ada siswa di kelas ini.'; tr.append(td); tbody.append(tr);
        } else rows.forEach(row => { const tr = document.createElement('tr'); row.forEach(value => { const td = document.createElement('td'); td.className = 'px-4 py-3.5'; td.textContent = value; tr.append(td); }); tbody.append(tr); });
        tableEl.append(thead, tbody); table.append(tableEl);
        document.getElementById('placement-list').replaceChildren(table);
    };

    list.addEventListener('change', event => {
        if (event.target.matches('.student-choice')) {
            const id = Number(event.target.value);
            if (event.target.checked) selectedIds.add(id);
            else selectedIds.delete(id);
        }
        updateSelected();
    });
    search.addEventListener('input', render);
    semester.addEventListener('change', () => { selectedIds.clear(); render(); });
    classroom.addEventListener('change', renderPlaced);
    allVisible.addEventListener('change', () => {
        list.querySelectorAll('.student-choice').forEach(input => {
            const id = Number(input.value);
            input.checked = allVisible.checked;
            if (allVisible.checked) selectedIds.add(id);
            else selectedIds.delete(id);
        });
        updateSelected();
    });
    document.querySelector('#student-options').parentElement.querySelector('button').addEventListener('click', () => {
        const selected = [...selectedIds];
        selected.forEach(studentId => placements.push({studentId, classId: Number(classroom.value), semesterId: Number(semester.value)}));
        selectedIds.clear();
        render();
    });
    render();
})();
</script>
<?php admin_page_end(); ?>
