<?php
// Komponen form berita bersama untuk tambah.php dan edit.php.
// Variabel yang diharapkan sebelum include:
//   $action        : tujuan form (misal "tambah.php" atau "edit.php?id=3")
//   $nilai         : ['judul' => ..., 'kategori' => ..., 'isi' => ..., 'gambar_url' => ...]
//   $kategori      : daftar kategori dari tabel kategori
//   $errors        : daftar pesan error (boleh kosong)
//   $judulHalaman  : judul di atas form
//   csrf_field() harus sudah tersedia (dari includes/csrf.php)

$judulHalaman = $judulHalaman ?? 'Form Berita';
$fieldKelas = 'w-full rounded-lg border border-slate-300 bg-white/80 px-3 py-2 text-sm text-slate-900 focus:border-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-600/30';
?>
<section class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900"><?= e($judulHalaman) ?></h1>
</section>

<?php if (!empty($errors)): ?>
    <div class="mb-4 max-w-3xl rounded-2xl border border-red-200 bg-red-50/80 p-4 text-sm text-red-800">
        <ul class="list-disc space-y-1 pl-5">
            <?php foreach ($errors as $pesanError): ?>
                <li><?= e($pesanError) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="<?= e($action) ?>" enctype="multipart/form-data" class="kaca max-w-3xl space-y-5 rounded-2xl p-6">
    <?= csrf_field() ?>

    <div>
        <label class="mb-1 block text-sm font-medium text-slate-700" for="judul">Judul Berita</label>
        <input type="text" id="judul" name="judul" maxlength="255" required
               value="<?= e($nilai['judul']) ?>" class="<?= $fieldKelas ?>">
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium text-slate-700" for="kategori">Kategori</label>
        <select id="kategori" name="kategori" required class="<?= $fieldKelas ?>">
            <option value="">-- Pilih kategori --</option>
            <?php foreach ($kategori as $k): ?>
                <option value="<?= (int) $k['id_kategori'] ?>"
                    <?= ((string) $nilai['kategori'] === (string) $k['id_kategori']) ? 'selected' : '' ?>>
                    <?= e($k['nama_kategori']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium text-slate-700" for="isi">Isi Berita</label>
        <textarea id="isi" name="isi" rows="10" required class="<?= $fieldKelas ?>"><?= e($nilai['isi']) ?></textarea>
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium text-slate-700" for="gambar">Gambar (opsional, JPG/PNG/WEBP, maks. 2 MB)</label>
        <?php if (!empty($nilai['gambar_url'])): ?>
            <img src="../../uploads/<?= e($nilai['gambar_url']) ?>" alt="" class="mb-2 w-40 rounded-lg">
            <p class="mb-2 text-sm text-slate-500">Mengunggah gambar baru akan menggantikan gambar ini.</p>
        <?php endif; ?>
        <input type="file" id="gambar" name="gambar" accept="image/jpeg,image/png,image/webp" class="w-full text-sm">
    </div>

    <div class="flex gap-3 pt-2">
        <button type="submit" class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-600 focus-visible:ring-offset-2">Simpan</button>
        <a href="index.php" class="rounded-lg border border-slate-300 bg-white/70 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-600 focus-visible:ring-offset-2">Batal</a>
    </div>
</form>