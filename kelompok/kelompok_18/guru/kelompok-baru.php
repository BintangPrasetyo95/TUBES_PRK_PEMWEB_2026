<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$assignmentId = filter_input(INPUT_GET, 'assignment_id', FILTER_VALIDATE_INT);
if (!is_int($assignmentId) || $assignmentId < 1) {
    http_response_code(404);
    exit('Penugasan tidak ditemukan.');
}

$pageError = '';
$nameInput = 'Kelompok';
$groupRuleInput = 'groups';
$groupValueInput = '4';
$studentJson = '[]';
try {
    $profile = guruProfile();
    $activeSemester = guruActiveSemester();
    $assignment = $profile !== null && $activeSemester !== null
        ? guruOwnedActiveAssignment((int) $profile['id'], (int) $activeSemester['id'], $assignmentId)
        : null;
    if ($assignment === null) {
        http_response_code(404);
        exit('Penugasan tidak ditemukan.');
    }
    $students = guruGroupStudents($assignmentId);
    $studentJson = json_encode(array_map(static fn (array $student): array => [
        'id' => (int) $student['id'], 'name' => $student['name'],
        'number' => $student['number'], 'gender' => $student['gender'],
    ], $students), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: '[]';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrfToken($_POST['_csrf_token'] ?? null)) {
            http_response_code(403);
            exit('Permintaan tidak valid.');
        }
        $postedAssignmentId = filter_input(INPUT_POST, 'assignment_id', FILTER_VALIDATE_INT);
        $name = $_POST['group_name'] ?? null;
        $groupsJson = $_POST['groups_json'] ?? null;
        $groupRule = $_POST['group_rule'] ?? null;
        $groupValue = filter_input(INPUT_POST, 'group_value', FILTER_VALIDATE_INT);
        if (
            $postedAssignmentId !== $assignmentId || !is_string($name)
            || !is_string($groupsJson) || !is_string($groupRule)
            || !is_int($groupValue) || $groupValue < 1
        ) {
            http_response_code(422);
            $pageError = 'Data kelompok tidak valid. Acak ulang sebelum menyimpan.';
        } else {
            $nameInput = $name;
            $groupRuleInput = $groupRule;
            $groupValueInput = (string) $groupValue;
            $memberGroups = json_decode($groupsJson, true);
            if (!is_array($memberGroups) || json_last_error() !== JSON_ERROR_NONE) {
                $pageError = 'Acak anggota terlebih dahulu sebelum menyimpan kelompok.';
            } else {
                guruCreateGroups($assignmentId, $name, $memberGroups, $groupRule, $groupValue);
                header('Location: kelompok.php?assignment_id=' . $assignmentId);
                exit;
            }
        }
    }
} catch (DomainException $exception) {
    $pageError = $exception->getMessage();
} catch (Throwable $exception) {
    guruReportBackendError($exception);
}

guru_page_start('Tambah Kelompok', 'kelas.php');
?>
<?php if ($pageError !== ''): ?><p class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert"><?= attendara_ui_escape($pageError) ?></p><?php endif; ?>
<form id="group-form" method="post" action="kelompok-baru.php?assignment_id=<?= (int) $assignment['assignment_id'] ?>">
    <section class="rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:p-6">
        <div><h2 class="text-lg font-semibold text-[#20392a]">Bentuk Kelompok</h2><p class="mt-1 text-sm text-slate-600">Atur jumlah kelompok atau jumlah anggota, lalu acak anggota siswa.</p></div>
        <p class="mt-1 text-sm text-slate-500"><?= attendara_ui_escape($assignment['name'] . ' · ' . $assignment['subject_name'] . ' · ' . $assignment['semester']) ?></p>
        <input type="hidden" name="_csrf_token" value="<?= attendara_ui_escape(csrfToken()) ?>">
        <input type="hidden" name="assignment_id" value="<?= (int) $assignment['assignment_id'] ?>">
        <input id="groups-json" type="hidden" name="groups_json" value="">
        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <div><label for="group-name" class="mb-1.5 block text-sm font-medium text-slate-700">Nama Kelompok</label><input id="group-name" name="group_name" type="text" maxlength="80" required value="<?= attendara_ui_escape($nameInput) ?>" class="min-h-10 w-full rounded-lg border border-[#d8e2d8] px-3 text-sm"></div>
            <div><label for="group-rule" class="mb-1.5 block text-sm font-medium text-slate-700">Kriteria pembagian</label><select id="group-rule" name="group_rule" class="min-h-10 w-full rounded-lg border border-[#d8e2d8] bg-white px-3 text-sm"><option value="groups"<?= $groupRuleInput === 'groups' ? ' selected' : '' ?>>Jumlah kelompok</option><option value="members"<?= $groupRuleInput === 'members' ? ' selected' : '' ?>>Jumlah siswa per kelompok</option></select></div>
            <div><label id="group-value-label" for="group-value" class="mb-1.5 block text-sm font-medium text-slate-700"><?= $groupRuleInput === 'groups' ? 'Jumlah kelompok' : 'Jumlah siswa per kelompok' ?></label><input id="group-value" name="group_value" type="number" min="1" max="20" value="<?= attendara_ui_escape($groupValueInput) ?>" required class="min-h-10 w-full rounded-lg border border-[#d8e2d8] px-3 text-sm focus:border-[#376c4d] focus:outline-none focus:ring-2 focus:ring-[#e6efe6]"></div>
        </div>
        <div class="mt-5 flex flex-wrap gap-2"><button id="randomize-groups" type="button" class="min-h-10 rounded-lg bg-[#1e5638] px-4 text-sm font-semibold text-white hover:bg-[#17452d]">Acak Anggota</button><button id="save-groups" type="submit" class="min-h-10 rounded-lg border border-[#d8e2d8] bg-white px-4 text-sm font-semibold text-[#284b35] hover:bg-[#f2f6f2]">Simpan Kelompok</button></div>
        <p id="group-message" class="mt-3 text-sm text-slate-500" role="status">Hasil pengacakan hanya ditampilkan pada halaman ini.</p>
    </section>
</form>
<section class="space-y-4"><div><h2 class="text-lg font-semibold text-[#20392a]">Hasil Pembagian</h2></div><div id="group-results" class="grid gap-4 md:grid-cols-2"></div></section>
<a href="kelompok.php?assignment_id=<?= (int) $assignment['assignment_id'] ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#d8e2d8] bg-white px-4 py-2 text-sm font-semibold text-[#284b35]">Kembali ke Kelompok</a>
<script>
const students = <?= $studentJson ?>;
const rule = document.getElementById('group-rule');
const valueInput = document.getElementById('group-value');
const results = document.getElementById('group-results');
const groupsJson = document.getElementById('groups-json');
const groupName = document.getElementById('group-name');
const message = document.getElementById('group-message');
let generatedGroups = [];
function shuffle(items) {
    const output = [...items];
    for (let i = output.length - 1; i > 0; i--) {
        const swap = Math.floor(Math.random() * (i + 1));
        [output[i], output[swap]] = [output[swap], output[i]];
    }
    return output;
}
function invalidatePreview() {
    generatedGroups = [];
    groupsJson.value = '';
    results.replaceChildren();
    message.textContent = 'Acak ulang hasil setelah mengubah kriteria atau nama kelompok.';
}
rule.addEventListener('change', () => {
    document.getElementById('group-value-label').textContent = rule.value === 'groups' ? 'Jumlah kelompok' : 'Jumlah siswa per kelompok';
    valueInput.value = rule.value === 'groups' ? '4' : '5';
    valueInput.max = rule.value === 'groups' ? '20' : '20';
    invalidatePreview();
});
valueInput.addEventListener('input', invalidatePreview);
groupName.addEventListener('input', invalidatePreview);
document.getElementById('randomize-groups').addEventListener('click', () => {
    const requested = Number(valueInput.value);
    if (!Number.isInteger(requested) || requested < 1 || requested > 20) {
        message.textContent = 'Nilai kriteria harus berupa bilangan bulat antara 1 sampai 20.';
        invalidatePreview();
        return;
    }
    if (students.length === 0) {
        message.textContent = 'Roster aktif penugasan ini belum memiliki siswa.';
        invalidatePreview();
        return;
    }
    const count = rule.value === 'groups' ? requested : Math.ceil(students.length / requested);
    if (count > students.length || (rule.value === 'groups' && count > 20)) {
        message.textContent = 'Jumlah kelompok tidak boleh melebihi jumlah siswa dan maksimal 20.';
        invalidatePreview();
        return;
    }

    const indices = shuffle(Array.from({length: count}, (_, index) => index));
    const male = shuffle(students.filter(student => student.gender === 'L'));
    const female = shuffle(students.filter(student => student.gender === 'P'));
    const maleRemainder = male.length % count;
    const femaleRemainder = female.length % count;
    const maleExtra = new Set(indices.slice(0, maleRemainder));
    const femaleExtra = new Set();
    if (maleRemainder + femaleRemainder <= count) {
        const available = indices.filter(index => !maleExtra.has(index));
        shuffle(available).slice(0, femaleRemainder).forEach(index => femaleExtra.add(index));
    } else {
        indices.filter(index => !maleExtra.has(index)).forEach(index => femaleExtra.add(index));
        shuffle(indices.filter(index => maleExtra.has(index)))
            .slice(0, maleRemainder + femaleRemainder - count)
            .forEach(index => femaleExtra.add(index));
    }
    const maleTargets = indices.map(index => Math.floor(male.length / count) + (maleExtra.has(index) ? 1 : 0));
    const femaleTargets = indices.map(index => Math.floor(female.length / count) + (femaleExtra.has(index) ? 1 : 0));
    generatedGroups = Array.from({length: count}, () => []);
    let maleOffset = 0;
    let femaleOffset = 0;
    indices.forEach((groupIndex, position) => {
        generatedGroups[groupIndex].push(...male.slice(maleOffset, maleOffset + maleTargets[position]));
        maleOffset += maleTargets[position];
        generatedGroups[groupIndex].push(...female.slice(femaleOffset, femaleOffset + femaleTargets[position]));
        femaleOffset += femaleTargets[position];
        generatedGroups[groupIndex] = shuffle(generatedGroups[groupIndex]);
    });

    results.replaceChildren();
    generatedGroups.forEach((group, index) => {
        const card = document.createElement('article');
        card.className = 'rounded-2xl border border-[#e2e9e2] bg-white p-5';
        const heading = document.createElement('h3');
        heading.className = 'font-semibold text-[#20392a]';
        heading.textContent = groupName.value.trim() + ' ' + (index + 1) + ' · ' + group.length + ' siswa';
        const list = document.createElement('ul');
        list.className = 'mt-3 space-y-2 text-sm text-slate-600';
        group.forEach(student => {
            const item = document.createElement('li');
            item.textContent = student.name + ' · ' + student.number + ' (' + (student.gender === 'L' ? 'L' : 'P') + ')';
            list.append(item);
        });
        card.append(heading, list);
        results.append(card);
    });
    groupsJson.value = JSON.stringify(generatedGroups.map(group => group.map(student => student.id)));
    message.textContent = 'Ukuran dan sebaran gender seimbang. Hasil belum disimpan.';
});
document.getElementById('group-form').addEventListener('submit', event => {
    if (groupsJson.value === '') {
        event.preventDefault();
        message.textContent = 'Acak anggota terlebih dahulu sebelum menyimpan.';
    }
});
</script>
<?php guru_page_end(); ?>
