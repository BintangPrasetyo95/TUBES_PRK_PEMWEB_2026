<?php
// Halaman Kelola Profil dan Ganti Password (BS-01, AUT-08)
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/fungsi/pengguna.php';

// Halaman ini hanya untuk pengguna terdaftar (Pelajar, Pengajar, Admin)
// Guest dialihkan ke Dashboard Guest (AUT-08)
if (peranSaatIni() === 'guest') {
    redirect(BASE_URL . '/guest/dashboard.php');
}

// Wajib login dengan salah satu peran terdaftar
wajibPeran(['pelajar', 'pengajar', 'admin']);

$userSession = penggunaSaatIni();
$userId = (int)$userSession['id'];

// Ambil data terbaru dari database
$userData = ambilPenggunaById($userId);
if (!$userData) {
    redirect(BASE_URL . '/logout.php');
}

$pesanFlash = flash();
$errorNama = '';
$suksesNama = '';
$errorSandi = '';
$suksesSandi = '';

// Proses form saat tombol disimpan
if (isPost()) {
    verifikasiCsrf();
    $aksi = $_POST['aksi'] ?? '';

    // Aksi 1: Ubah Nama Lengkap
    if ($aksi === 'ubah_nama') {
        $namaBaru = $_POST['nama'] ?? '';
        $hasil = ubahNamaPengguna($userId, $namaBaru);
        if ($hasil['berhasil']) {
            $suksesNama = $hasil['pesan'];
            $userData['nama'] = $namaBaru;
        } else {
            $errorNama = $hasil['pesan'];
        }
    }

    // Aksi 2: Ganti Password
    if ($aksi === 'ganti_password') {
        $passwordLama = $_POST['password_lama'] ?? '';
        $passwordBaru = $_POST['password_baru'] ?? '';
        $konfirmasiBaru = $_POST['konfirmasi_password'] ?? '';

        $hasil = ubahPasswordPengguna($userId, $passwordLama, $passwordBaru, $konfirmasiBaru);
        if ($hasil['berhasil']) {
            $suksesSandi = $hasil['pesan'];
        } else {
            $errorSandi = $hasil['pesan'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - <?= e(NAMA_APLIKASI) ?></title>
    <script src="<?= e(BASE_URL) ?>/assets/js/tema-init.js"></script>
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/tema.css">
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/dasar.css">
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/komponen.css">
</head>
<body class="layout-publik">

    <header class="navbar-top">
        <div class="navbar-container">
            <a href="<?= e(urlDashboardPeran()) ?>" class="navbar-brand">
                <?= e(NAMA_APLIKASI) ?>
            </a>
            <ul class="navbar-menu">
                <li><a href="<?= e(urlDashboardPeran()) ?>" class="navbar-link">Dashboard</a></li>
                <li><a href="<?= e(BASE_URL) ?>/profil.php" class="navbar-link active">Profil</a></li>
                <li><a href="<?= e(BASE_URL) ?>/logout.php" class="navbar-link">Keluar</a></li>
                <li><?php require __DIR__ . '/includes/partials/pemilih-tema.php'; ?></li>
            </ul>
        </div>
    </header>

    <main>
        <div class="container-sm">

            <?php if ($pesanFlash): ?>
                <div class="alert alert-<?= $pesanFlash['tipe'] === 'sukses' ? 'sukses' : 'galat' ?>" role="alert">
                    <?= e($pesanFlash['pesan']) ?>
                </div>
            <?php endif; ?>

            <!-- Kartu 1: Data Profil & Ubah Nama -->
            <div class="card">
                <h1 class="card-title">Profil Pengguna</h1>
                <p class="form-helper">Kelola informasi data diri dan akun Anda.</p>
                <br>

                <?php if ($errorNama !== ''): ?>
                    <div class="alert alert-galat" role="alert"><?= e($errorNama) ?></div>
                <?php endif; ?>

                <?php if ($suksesNama !== ''): ?>
                    <div class="alert alert-sukses" role="alert"><?= e($suksesNama) ?></div>
                <?php endif; ?>

                <form action="" method="POST" novalidate>
                    <?= inputCsrf() ?>
                    <input type="hidden" name="aksi" value="ubah_nama">

                    <div class="form-group">
                        <label for="email" class="form-label">Alamat Email (Tidak dapat diubah)</label>
                        <input type="email" id="email" class="form-control" value="<?= e($userData['email']) ?>" readonly style="opacity: 0.8; cursor: not-allowed;">
                    </div>

                    <div class="form-group">
                        <label for="peran" class="form-label">Peran Akun</label>
                        <input type="text" id="peran" class="form-control" value="<?= ucfirst(e($userData['peran'])) ?>" readonly style="opacity: 0.8; cursor: not-allowed;">
                    </div>

                    <div class="form-group">
                        <label for="nama" class="form-label">Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" class="form-control" value="<?= e($userData['nama']) ?>" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Simpan Perubahan Nama</button>
                </form>
            </div>

            <!-- Kartu 2: Ganti Password -->
            <div class="card">
                <h2 class="card-title">Ganti Password</h2>
                <p class="form-helper">Pastikan menggunakan kombinasi password yang kuat dan aman.</p>
                <br>

                <?php if ($errorSandi !== ''): ?>
                    <div class="alert alert-galat" role="alert"><?= e($errorSandi) ?></div>
                <?php endif; ?>

                <?php if ($suksesSandi !== ''): ?>
                    <div class="alert alert-sukses" role="alert"><?= e($suksesSandi) ?></div>
                <?php endif; ?>

                <form action="" method="POST" novalidate>
                    <?= inputCsrf() ?>
                    <input type="hidden" name="aksi" value="ganti_password">

                    <div class="form-group">
                        <label for="password_lama" class="form-label">Password Lama</label>
                        <input type="password" id="password_lama" name="password_lama" class="form-control" placeholder="Masukkan password saat ini" required>
                    </div>

                    <div class="form-group">
                        <label for="password_baru" class="form-label">Password Baru</label>
                        <input type="password" id="password_baru" name="password_baru" class="form-control" placeholder="Minimal 8 karakter" required>
                    </div>

                    <div class="form-group">
                        <label for="konfirmasi_password" class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" id="konfirmasi_password" name="konfirmasi_password" class="form-control" placeholder="Ulangi password baru" required>
                    </div>

                    <button type="submit" class="btn btn-secondary btn-block">Perbarui Password</button>
                </form>
            </div>

        </div>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 <?= e(NAMA_APLIKASI) ?> - Kelompok 14</p>
    </footer>

    <script src="<?= e(BASE_URL) ?>/assets/js/tema.js"></script>
</body>
</html>
