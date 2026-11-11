<?php
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/database.php'; // harus menyediakan $pdo (PDO)

$totalBerita = (int) $pdo->query('SELECT COUNT(*) FROM berita')->fetchColumn();
$totalUser   = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'mahasiswa'")->fetchColumn();

$status = ['menunggu' => 0, 'ditinjau' => 0, 'selesai' => 0];
foreach ($pdo->query('SELECT status, COUNT(*) AS n FROM aspirasi GROUP BY status') as $r) {
    $status[$r['status']] = (int) $r['n'];
}
$totalAspirasi = array_sum($status);

$terbaru = $pdo->query(
    'SELECT a.id_aspirasi, a.judul_aspirasi, a.status, a.tanggal_kirim, a.is_anonim,
            u.nama_lengkap, k.nama_kategori
       FROM aspirasi a
       JOIN users u ON u.id_user = a.id_user
       LEFT JOIN kategori k ON k.id_kategori = a.id_kategori
      ORDER BY a.tanggal_kirim DESC
      LIMIT 5'
)->fetchAll(PDO::FETCH_ASSOC);

// Empat kartu statistik. Semua angka berasal dari database di atas.
$kartu = [
    ['label' => 'Total Berita',      'nilai' => $totalBerita,         'ikon' => 'berita',   'catatan' => 'Artikel yang sudah dipublikasikan', 'warna' => 'bg-blue-50 text-blue-600'],
    ['label' => 'Total Aspirasi',    'nilai' => $totalAspirasi,       'ikon' => 'aspirasi', 'catatan' => 'Seluruh aspirasi yang masuk',       'warna' => 'bg-blue-50 text-blue-600'],
    ['label' => 'Aspirasi Menunggu', 'nilai' => $status['menunggu'],  'ikon' => 'jam',      'catatan' => 'Belum ditinjau admin',              'warna' => 'bg-amber-50 text-amber-600'],
    ['label' => 'Total Mahasiswa',   'nilai' => $totalUser,           'ikon' => 'pengguna', 'catatan' => 'Akun mahasiswa terdaftar',          'warna' => 'bg-blue-50 text-blue-600'],
];

$judulTab   = 'Dashboard';
$breadcrumb = [['Dashboard', null]];
include __DIR__ . '/../includes/admin_header.php';
?>

<section class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Dashboard</h1>
        <p class="mt-1 text-sm text-slate-500">
            Selamat datang, <?= e($_SESSION['nama_lengkap'] ?? 'Admin') ?>. Berikut ringkasan portal berita dan aspirasi KabarSih.
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= e(admin_url('/admin/berita/tambah.php')) ?>"
           class="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-600 focus-visible:ring-offset-2">
            <?= ikon('tambah', 'h-4 w-4') ?> Tambah Berita
        </a>
        <a href="<?= e(admin_url('/admin/aspirasi/aspirasi.php')) ?>"
           class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-600 focus-visible:ring-offset-2">
            <?= ikon('aspirasi', 'h-4 w-4') ?> Kelola Aspirasi
        </a>
    </div>
</section>

<section class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4" aria-label="Statistik">
    <?php foreach ($kartu as $k): ?>
        <div class="kaca rounded-2xl p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500"><?= e($k['label']) ?></p>
                <span class="rounded-lg p-2 <?= $k['warna'] ?>"><?= ikon($k['ikon'], 'h-5 w-5') ?></span>
            </div>
            <p class="mt-3 text-3xl font-semibold text-slate-900"><?= (int) $k['nilai'] ?></p>
            <p class="mt-1 text-xs text-slate-500"><?= e($k['catatan']) ?></p>
        </div>
    <?php endforeach; ?>
</section>

<section class="kaca rounded-2xl" aria-labelledby="judul-terbaru">
    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
        <h2 id="judul-terbaru" class="text-lg font-semibold text-slate-900">Aspirasi Terbaru</h2>
        <a href="<?= e(admin_url('/admin/aspirasi/aspirasi.php')) ?>" class="rounded text-sm font-medium text-orange-600 hover:text-orange-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-600">Lihat semua</a>
    </div>

    <?php if (!$terbaru): ?>
        <div class="flex flex-col items-center px-6 py-12 text-center">
            <span class="rounded-full bg-slate-100 p-3 text-slate-400"><?= ikon('aspirasi', 'h-6 w-6') ?></span>
            <p class="mt-3 text-sm font-medium text-slate-900">Belum ada aspirasi</p>
            <p class="mt-1 text-sm text-slate-500">Aspirasi yang dikirim mahasiswa akan muncul di sini.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[40rem] text-left text-sm">
                <thead class="bg-white/40 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-medium">Judul</th>
                        <th scope="col" class="px-5 py-3 font-medium">Pengirim</th>
                        <th scope="col" class="px-5 py-3 font-medium">Kategori</th>
                        <th scope="col" class="px-5 py-3 font-medium">Status</th>
                        <th scope="col" class="px-5 py-3 font-medium">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php foreach ($terbaru as $a): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-medium text-slate-900"><?= e($a['judul_aspirasi']) ?></td>
                            <td class="px-5 py-3 text-slate-700"><?= $a['is_anonim'] ? '<em>Anonim</em>' : e($a['nama_lengkap']) ?></td>
                            <td class="px-5 py-3 text-slate-700"><?= e($a['nama_kategori'] ?? '-') ?></td>
                            <td class="px-5 py-3"><?= badge_status((string) $a['status']) ?></td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-500"><?= e(date('d M Y H:i', strtotime($a['tanggal_kirim']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
</main></div>
</body>
</html>