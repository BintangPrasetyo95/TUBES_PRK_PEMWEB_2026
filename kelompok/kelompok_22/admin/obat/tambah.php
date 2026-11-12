<?php
/**
 * File: admin/obat/tambah.php
 * Tambah obat (Create) - Kelompok 22 KlikObat
 */

require_once '../auth.php'; // halaman ini hanya boleh dibuka admin yang sudah login

require_once '../../config/koneksi.php';
require_once '../../config/helper.php';
require_once __DIR__ . '/gambar_helper.php';
 
$kategori = [];
$q = mysqli_query($conn, "SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori");
while ($k = mysqli_fetch_assoc($q)) {
    $kategori[] = $k;
}

$errors = [];
$data = [
    'nama_obat'    => '',
    'id_kategori'  => '',
    'stok'         => '',
    'indikasi'     => '',
    'aturan_pakai' => '',
    'peringatan'   => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($data as $key => $v) {
        $data[$key] = trim($_POST[$key] ?? '');
    }


    if ($data['nama_obat'] === '') {
        $errors[] = 'Nama obat wajib diisi.';
    } elseif (mb_strlen($data['nama_obat']) > 150) {
        $errors[] = 'Nama obat maksimal 150 karakter.';
    }

    $ada_kategori = false;
    foreach ($kategori as $k) {
        if ((string)$k['id_kategori'] === $data['id_kategori']) {
            $ada_kategori = true;
        }
    }
    if (!$ada_kategori) {
        $errors[] = 'Pilih kategori yang valid.';
    }

    if ($data['stok'] === '' || !ctype_digit($data['stok'])) {
        $errors[] = 'Stok harus berupa angka bulat 0 atau lebih.';
    }

    if ($data['indikasi'] === '')    $errors[] = 'Deskripsi & indikasi wajib diisi.';
    if ($data['aturan_pakai'] === '') $errors[] = 'Aturan pakai & dosis wajib diisi.';
    if ($data['peringatan'] === '')   $errors[] = 'Peringatan / kontraindikasi wajib diisi.';

    $ada_file = isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE;
    if ($ada_file) {
        $err_gambar = validasiGambar($_FILES['gambar']);
        if ($err_gambar !== null) {
            $errors[] = $err_gambar;
        }
    }

    $gambar = GAMBAR_DEFAULT;
    if (!$errors && $ada_file) {
        $nama_baru = simpanGambar($_FILES['gambar']);
        if ($nama_baru === null) {
            $errors[] = 'Gagal menyimpan file gambar ke server.';
        } else {
            $gambar = $nama_baru;
        }
    }

    if (!$errors) {
        $id_kat = (int)$data['id_kategori'];
        $stok   = (int)$data['stok'];

        $stmt = mysqli_prepare($conn,
            "INSERT INTO obat (nama_obat, id_kategori, stok, indikasi, aturan_pakai, peringatan, gambar)
             VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "siissss",
            $data['nama_obat'], $id_kat, $stok,
            $data['indikasi'], $data['aturan_pakai'], $data['peringatan'], $gambar);

        if (mysqli_stmt_execute($stmt)) {
            header('Location: index.php?status=tambah');
            exit;
        }
        hapusFileGambar($gambar);
        $errors[] = 'Gagal menyimpan data ke database.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Obat - Admin KlikObat</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; max-width: 640px; }
        label { display: block; margin-top: 12px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; box-sizing: border-box; margin-top: 4px; }
        textarea { min-height: 80px; }
        .hint { font-size: 12px; color: #6c757d; font-weight: normal; }
        .err { background: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 12px; }
        .btn { display: inline-block; padding: 8px 14px; background: #0d6efd; color: #fff; text-decoration: none; border: 0; border-radius: 4px; cursor: pointer; font-size: 14px; margin-top: 16px; }
        .btn.batal { background: #6c757d; }
    </style>
</head>
<body>
    <h1>Tambah Obat</h1>

    <?php if ($errors): ?>
        <div class="err">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <label for="nama_obat">Nama Obat</label>
        <input type="text" id="nama_obat" name="nama_obat" maxlength="150"
               value="<?= htmlspecialchars($data['nama_obat']) ?>">

        <label for="id_kategori">Kategori</label>
        <select id="id_kategori" name="id_kategori">
            <option value="">-- Pilih kategori --</option>
            <?php foreach ($kategori as $k): ?>
                <option value="<?= (int)$k['id_kategori'] ?>"
                    <?= (string)$k['id_kategori'] === $data['id_kategori'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($k['nama_kategori']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="stok">Stok</label>
        <input type="number" id="stok" name="stok" min="0"
               value="<?= htmlspecialchars($data['stok']) ?>">

        <label for="gambar">Gambar Obat <span class="hint">(opsional, JPG/PNG, maks. 2 MB)</span></label>
        <input type="file" id="gambar" name="gambar" accept="image/jpeg,image/png">

        <label for="indikasi">Deskripsi & Indikasi</label>
        <textarea id="indikasi" name="indikasi"><?= htmlspecialchars($data['indikasi']) ?></textarea>

        <label for="aturan_pakai">Aturan Pakai & Dosis</label>
        <textarea id="aturan_pakai" name="aturan_pakai"><?= htmlspecialchars($data['aturan_pakai']) ?></textarea>

        <label for="peringatan">Peringatan / Kontraindikasi</label>
        <textarea id="peringatan" name="peringatan"><?= htmlspecialchars($data['peringatan']) ?></textarea>

        <button type="submit" class="btn">Simpan</button>
        <a href="index.php" class="btn batal">Batal</a>
    </form>
</body>
</html>