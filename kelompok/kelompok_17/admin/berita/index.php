<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../config/database.php'; // menyediakan $pdo

// Pesan singkat setelah tambah, edit, atau hapus. Hanya kode yang diizinkan yang ditampilkan.
$pesanTeks = [
    'tambah' => 'Berita berhasil ditambahkan.',
    'edit'   => 'Berita berhasil diperbarui.',
    'hapus'  => 'Berita berhasil dihapus.',
];
$kode = $_GET['pesan'] ?? '';
$pesan = $pesanTeks[$kode] ?? '';

// Ambil daftar berita beserta nama kategori dan penulisnya.
$stmt = $pdo->prepare(
    'SELECT b.id_berita, b.judul_berita, b.gambar_url, b.tanggal_publikasi,
            k.nama_kategori, u.nama_lengkap
       FROM berita b
       JOIN kategori k ON k.id_kategori = b.id_kategori
       JOIN users u ON u.id_user = b.id_user
      ORDER BY b.tanggal_publikasi DESC'
);
$stmt->execute();
$daftarBerita = $stmt->fetchAll();
?>
<?php
$judulTab   = 'Berita';
$breadcrumb = [['Dashboard', '/admin/index.php'], ['Berita', null]];
include __DIR__ . '/../../includes/admin_header.php';
?>

<section class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Kelola Berita</h1>
        <p class="mt-1 text-sm text-slate-500">Tambah, ubah, atau hapus artikel berita.</p>
    </div>
    <a href="tambah.php" class="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-600 focus-visible:ring-offset-2">+ Tambah Berita</a>
</section>

<?php if ($pesan !== ''): ?>
    <div class="kaca mb-4 rounded-2xl p-4 text-sm font-medium text-emerald-800"><?= e($pesan) ?></div>
<?php endif; ?>

<section class="kaca overflow-hidden rounded-2xl" aria-label="Daftar berita">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[48rem] text-left text-sm">
            <thead class="bg-white/40 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th scope="col" class="px-5 py-3 font-medium">Gambar</th>
                    <th scope="col" class="px-5 py-3 font-medium">Judul</th>
                    <th scope="col" class="px-5 py-3 font-medium">Kategori</th>
                    <th scope="col" class="px-5 py-3 font-medium">Penulis</th>
                    <th scope="col" class="px-5 py-3 font-medium">Tanggal</th>
                    <th scope="col" class="px-5 py-3 font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200/70">
            <?php if (!$daftarBerita): ?>
                <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Belum ada berita.</td></tr>
            <?php endif; ?>
            <?php foreach ($daftarBerita as $b): ?>
                <tr class="hover:bg-white/50">
                    <td class="px-5 py-3">
                        <?php if (!empty($b['gambar_url'])): ?>
                            <img src="../../uploads/<?= e($b['gambar_url']) ?>" alt="" class="h-12 w-16 rounded-lg object-cover">
                        <?php else: ?>
                            <span class="text-slate-400">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-3 font-medium text-slate-900"><?= e($b['judul_berita']) ?></td>
                    <td class="px-5 py-3 text-slate-700"><?= e($b['nama_kategori']) ?></td>
                    <td class="px-5 py-3 text-slate-700"><?= e($b['nama_lengkap']) ?></td>
                    <td class="whitespace-nowrap px-5 py-3 text-slate-500"><?= e(date('d M Y H:i', strtotime($b['tanggal_publikasi']))) ?></td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <a href="edit.php?id=<?= (int) $b['id_berita'] ?>" class="rounded font-medium text-orange-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-600">Edit</a>
                            <form method="POST" action="hapus.php" onsubmit="return confirm('Hapus berita ini? Komentar di berita ini juga akan ikut terhapus.');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id_berita" value="<?= (int) $b['id_berita'] ?>">
                                <button type="submit" class="rounded font-medium text-red-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
</main></div>
</body>
</html>