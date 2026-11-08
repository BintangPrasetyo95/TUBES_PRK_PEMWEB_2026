<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

$guruCount = 5;
$siswaCount = 60;
$kelasCount = 3;
$kelasAktif = [
    ['Kelas X-A', '2026/2027 Ganjil', '20 siswa'],
    ['Kelas X-B', '2026/2027 Ganjil', '20 siswa'],
    ['Kelas X-C', '2026/2027 Ganjil', '20 siswa'],
];

admin_page_start('Dashboard', 'dashboard.php');
?>
<section aria-label="Ringkasan administrasi" class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-4">
    <?php
    attendara_ui_stat_card('Total Guru', (string) $guruCount, 'Guru terdaftar', 'users');
    attendara_ui_stat_card('Total Siswa', (string) $siswaCount, 'Siswa terdaftar', 'users');
    attendara_ui_stat_card('Total Kelas', (string) $kelasCount, 'Kelas terdaftar', 'school');
    attendara_ui_stat_card('Semester Aktif', '2026/2027 Ganjil', 'Status data: aktif', 'calendar');
    ?>
</section>

<section class="space-y-4">
    <div>
        <h2 class="text-lg font-semibold text-[#20392a]">Kelas Aktif</h2>
        <p class="mt-1 text-sm text-slate-600">Jumlah siswa pada setiap kelas.</p>
    </div>
    <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
        <?php foreach ($kelasAktif as [$nama, $semester, $jumlah]): ?>
            <article class="rounded-2xl border border-[#e2e9e2] bg-white p-5 shadow-[0_2px_8px_rgba(30,55,38,0.04)]">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-[#20392a]"><?= attendara_ui_escape($nama) ?></h3>
                        <p class="mt-1 text-sm text-slate-500"><?= attendara_ui_escape($semester) ?></p>
                    </div>
                    <span class="rounded-xl bg-[#e9f0e8] p-2 text-[#28583b]"><?php attendara_ui_icon('school', 'h-5 w-5'); ?></span>
                </div>
                <p class="mt-5 text-sm font-medium text-[#31583c]"><?= attendara_ui_escape($jumlah) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php admin_page_end(); ?>
