<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../config/database.php';

$daftarBerita = $pdo->query(
    'SELECT b.id_berita, b.judul_berita, b.gambar_url, b.tanggal_publikasi,
            k.nama_kategori, u.nama_lengkap
       FROM berita b
       LEFT JOIN kategori k ON k.id_kategori = b.id_kategori
       LEFT JOIN users u ON u.id_user = b.id_user
      ORDER BY b.tanggal_publikasi DESC'
)->fetchAll();

$daftarPesan = [
    'tambah' => 'Berita berhasil ditambahkan.',
    'edit'   => 'Berita berhasil diperbarui.',
    'hapus'  => 'Berita berhasil dihapus.',
];
$kodePesan = $_GET['pesan'] ?? '';
$pesan = is_string($kodePesan) ? ($daftarPesan[$kodePesan] ?? '') : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Berita - KabarSih</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">
<?php include __DIR__ . '/../../components/admin_nav.php'; ?>

<main class="max-w-6xl mx-auto px-4 py-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold">Kelola Berita</h1>
            <p class="text-sm text-slate-500 mt-1">Daftar artikel yang sudah dipublikasikan.</p>
        </div>
        <a href="tambah.php" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">
            Tambah Berita
        </a>
    </div>

    <?php if ($pesan !== ''): ?>
        <div class="mb-4 rounded-lg bg-emerald-100 p-3 text-emerald-800"><?= e($pesan) ?></div>
    <?php endif; ?>

    <div class="overflow-x-auto rounded-lg bg-white shadow">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-100">
                <tr>
                    <th class="p-3">Judul</th>
                    <th class="p-3">Kategori</th>
                    <th class="p-3">Penulis</th>
                    <th class="p-3">Tanggal Publikasi</th>
                    <th class="p-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$daftarBerita): ?>
                <tr>
                    <td colspan="5" class="p-6 text-center text-slate-500">Belum ada berita.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($daftarBerita as $berita): ?>
                    <tr class="border-t align-top">
                        <td class="p-3 font-medium"><?= e($berita['judul_berita']) ?></td>
                        <td class="p-3"><?= e($berita['nama_kategori'] ?? '-') ?></td>
                        <td class="p-3"><?= e($berita['nama_lengkap'] ?? '-') ?></td>
                        <td class="p-3"><?= e(date('d M Y H:i', strtotime($berita['tanggal_publikasi']))) ?></td>
                        <td class="p-3">
                            <div class="flex items-center gap-3">
                                <a class="font-medium text-teal-700 hover:underline"
                                   href="edit.php?id=<?= (int) $berita['id_berita'] ?>">Edit</a>
                                <form method="POST" action="hapus.php"
                                      onsubmit="return confirm('Hapus berita ini?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id_berita" value="<?= (int) $berita['id_berita'] ?>">
                                    <button type="submit" class="font-medium text-red-600 hover:underline">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>
