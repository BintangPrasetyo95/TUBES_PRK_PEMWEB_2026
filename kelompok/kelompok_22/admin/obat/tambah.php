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
    <!-- Hubungkan CSS UI Admin -->
    <link rel="stylesheet" href="../../frontend/assets/css/style.css">
    <link rel="stylesheet" href="../../frontend/assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar Admin -->
        <aside class="admin-sidebar">
            <div class="admin-logo">KlikObat</div>
            <ul class="admin-menu">
                <li><a href="../kategori/index.php">Kelola Kategori</a></li>
                <li><a href="index.php" class="active">Kelola Obat</a></li>
                <li><a href="../logout.php" style="color: #ef4444;">Logout</a></li>
            </ul>
        </aside>

        <!-- Konten Utama Admin -->
        <main class="admin-main">
            <header class="admin-header">
                <h1>Tambah Obat</h1>
                <div class="admin-user">Admin Panel</div>
            </header>

            <div class="admin-content">
                <h2>Form Tambah Obat</h2>

                <?php if ($errors): ?>
                    <div style="padding: 12px 15px; margin-bottom: 20px; background: #fee2e2; color: #b91c1c; border-radius: 10px; font-weight: 600; font-size: 14px;">
                        <ul style="margin: 0; padding-left: 20px;">
                            <?php foreach ($errors as $e): ?>
                                <li><?= htmlspecialchars($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="admin-table-container" style="max-width: 700px; padding: 25px;">
                    <form method="post" enctype="multipart/form-data">
                        <div style="margin-bottom: 15px;">
                            <label for="nama_obat" style="display: block; font-weight: 600; margin-bottom: 6px; color: var(--admin-text);">Nama Obat</label>
                            <input type="text" id="nama_obat" name="nama_obat" maxlength="150"
                                   value="<?= htmlspecialchars($data['nama_obat']) ?>" autocomplete="off">
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label for="id_kategori" style="display: block; font-weight: 600; margin-bottom: 6px; color: var(--admin-text);">Kategori</label>
                            <select id="id_kategori" name="id_kategori">
                                <option value="">-- Pilih kategori --</option>
                                <?php foreach ($kategori as $k): ?>
                                    <option value="<?= (int)$k['id_kategori'] ?>"
                                        <?= (string)$k['id_kategori'] === $data['id_kategori'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($k['nama_kategori']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label for="stok" style="display: block; font-weight: 600; margin-bottom: 6px; color: var(--admin-text);">Stok</label>
                            <input type="number" id="stok" name="stok" min="0"
                                   value="<?= htmlspecialchars($data['stok']) ?>">
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label for="gambar" style="display: block; font-weight: 600; margin-bottom: 6px; color: var(--admin-text);">Gambar Obat <span style="font-size: 12px; color: var(--admin-secondary); font-weight: normal;">(opsional, JPG/PNG, maks. 2 MB)</span></label>
                            <input type="file" id="gambar" name="gambar" accept="image/jpeg,image/png" style="padding: 8px 0; border: none; background: none;">
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label for="indikasi" style="display: block; font-weight: 600; margin-bottom: 6px; color: var(--admin-text);">Deskripsi & Indikasi</label>
                            <textarea id="indikasi" name="indikasi" style="min-height: 80px; width: 100%;"><?= htmlspecialchars($data['indikasi']) ?></textarea>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label for="aturan_pakai" style="display: block; font-weight: 600; margin-bottom: 6px; color: var(--admin-text);">Aturan Pakai & Dosis</label>
                            <textarea id="aturan_pakai" name="aturan_pakai" style="min-height: 80px; width: 100%;"><?= htmlspecialchars($data['aturan_pakai']) ?></textarea>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label for="peringatan" style="display: block; font-weight: 600; margin-bottom: 6px; color: var(--admin-text);">Peringatan / Kontraindikasi</label>
                            <textarea id="peringatan" name="peringatan" style="min-height: 80px; width: 100%;"><?= htmlspecialchars($data['peringatan']) ?></textarea>
                        </div>

                        <div style="display: flex; gap: 12px; align-items: center;">
                            <button type="submit" class="admin-btn">Simpan</button>
                            <a href="index.php" style="color: var(--admin-secondary); text-decoration: none; font-weight: 600; font-size: 14px; padding: 11px 19px; background: #e2e8f0; border-radius: 10px;">Batal</a>
                        </div>
                    </form>
                </div>
            </div>

            <footer class="admin-footer">
                <p>&copy; 2026 KlikObat. Sistem Informasi Katalog Farmasi & Edukasi.</p>
            </footer>
        </main>
    </div>
</body>
</html>