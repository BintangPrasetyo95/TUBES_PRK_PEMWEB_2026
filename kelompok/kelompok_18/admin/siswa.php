<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

// Contoh statis seluruh kolom siswa dan kelas melalui anggota_kelas.
$rows = [];
for ($id = 1; $id <= 60; $id++) {
    $number = str_pad((string) $id, 3, '0', STR_PAD_LEFT);
    $currentClass = $id <= 20 ? 'Kelas X-A' : ($id <= 40 ? 'Kelas X-B' : 'Kelas X-C');
    $rows[] = [(string) $id, 'ATT-2026-' . $number, 'Siswa ' . str_pad((string) $id, 2, '0', STR_PAD_LEFT), '—', '—', 'Aktif', '2026-01-01 09:00:00', '2026-01-01 09:00:00', $currentClass];
}

admin_page_start('Data Siswa', 'siswa.php');
?>
<section class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-lg font-semibold text-[#20392a]">Daftar Siswa</h2><p class="mt-1 text-sm text-slate-600">Data profil serta kelas pada semester yang dipilih.</p></div><?php admin_ui_button('Tambah Siswa'); ?></div>
    <p class="text-sm text-slate-500">Kelas ditampilkan untuk 2026/2027 Ganjil.</p>
    <div class="max-w-xl"><?php attendara_ui_input('Cari siswa', 'cari_siswa', '', 'text', 'Nama atau nomor induk'); ?></div>
    <?php admin_render_table(['ID', 'Nomor Induk', 'Nama', 'Jenis Kelamin', 'Tanggal Lahir', 'Status', 'Dibuat', 'Diperbarui', 'Kelas Saat Ini'], $rows, 'Belum ada data siswa.', ['Edit']); ?>
    <p id="siswa-search-empty" class="hidden rounded-xl bg-white px-4 py-6 text-center text-sm text-slate-500">Tidak ada siswa yang cocok dengan pencarian.</p>
</section>
<section class="rounded-2xl border border-[#e2e9e2] bg-white p-5 sm:p-6">
    <h2 class="text-base font-semibold text-[#20392a]">Form Siswa</h2>
    <form class="mt-4 grid gap-4 sm:grid-cols-2" onsubmit="return false;">
        <?php attendara_ui_input('Nomor induk', 'nomor_induk_siswa'); ?>
        <?php attendara_ui_input('Nama', 'nama_siswa'); ?>
        <?php attendara_ui_input('Jenis kelamin (opsional)', 'jenis_kelamin_siswa'); ?>
        <?php attendara_ui_input('Tanggal lahir (opsional)', 'tanggal_lahir_siswa', '', 'date'); ?>
        <div class="sm:col-span-2"><?php admin_ui_button('Simpan'); ?></div>
    </form>
</section>
<script>
document.getElementById('cari_siswa').addEventListener('input', event => {
    const query = event.target.value.trim().toLocaleLowerCase('id');
    const rows = [...document.querySelectorAll('[data-admin-table] tbody tr')];
    let visible = 0;
    rows.forEach(row => {
        const matches = row.textContent.toLocaleLowerCase('id').includes(query);
        row.hidden = !matches;
        if (matches) visible++;
    });
    document.getElementById('siswa-search-empty').classList.toggle('hidden', visible > 0);
});
</script>
<?php admin_page_end(); ?>
