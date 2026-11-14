<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/koneksi.php';

$error = '';

$nama = '';
$email = '';
$nim_nip = '';
$no_hp = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nim_nip = trim($_POST['nim_nip'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';

    if ($nama === '' || $email === '' || $password === '') {
        $error = 'Nama, email, dan password wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak sesuai.';
    } else {
        try {
            $pdo = db();

            $cek = $pdo->prepare(
                'SELECT id FROM users WHERE email = :email'
            );

            $cek->execute(['email' => $email]);

            if ($cek->fetch()) {
                $error = 'Email sudah terdaftar. Gunakan email lain.';
            } else {
                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $query = $pdo->prepare(
                    'INSERT INTO users
                    (nama, email, password, role, nim_nip, no_hp)
                    VALUES
                    (:nama, :email, :password, :role, :nim_nip, :no_hp)'
                );

                $query->execute([
                    'nama' => $nama,
                    'email' => $email,
                    'password' => $passwordHash,
                    'role' => 'peminjam',
                    'nim_nip' => $nim_nip !== '' ? $nim_nip : null,
                    'no_hp' => $no_hp !== '' ? $no_hp : null
                ]);

                set_flash(
                    'success',
                    'Registrasi berhasil. Silakan login menggunakan akun kamu.'
                );

                header('Location: ' . url('auth/login.php'));
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Registrasi gagal. Silakan coba kembali.';
        }
    }
}

$judul = 'Daftar Akun';
$bodyClass = 'auth-body';
require __DIR__ . '/../includes/head.php';
?>
<main id="konten" class="auth-shell auth-shell--balik">
    <?php auth_panel('Gabung &', 'mulai pinjam.', 'Sudah punya akun? Masuk untuk mulai menggunakan layanan peminjaman alat dan ruang laboratorium.', 'Sudah punya akun? Masuk', url('auth/login.php')); ?>

    <section class="auth-form" aria-labelledby="auth-judul">
        <h2 id="auth-judul">Buat Akun LabLoan</h2>
        <p class="auth-subtitle">Lengkapi data berikut untuk mendaftar sebagai peminjam.</p>

<?php if ($error !== ''): ?>
        <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
<?php endif; ?>

        <form method="POST" action="">
            <div class="grid grid-2">
                <div class="form-group">
                    <label for="nama" class="form-label">Nama Lengkap</label>
                    <input type="text" id="nama" name="nama" class="form-control" placeholder="Masukkan nama lengkap" value="<?= e($nama) ?>" autocomplete="name" required>
                </div>
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="nama@email.com" value="<?= e($email) ?>" autocomplete="email" required>
                </div>
                <div class="form-group">
                    <label for="nim_nip" class="form-label">NIM/NIP</label>
                    <input type="text" id="nim_nip" name="nim_nip" class="form-control" placeholder="Masukkan NIM/NIP" value="<?= e($nim_nip) ?>">
                </div>
                <div class="form-group">
                    <label for="no_hp" class="form-label">Nomor HP</label>
                    <input type="tel" id="no_hp" name="no_hp" class="form-control" placeholder="08xxxxxxxxxx" value="<?= e($no_hp) ?>" autocomplete="tel">
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" class="form-control" placeholder="Minimal 8 karakter" minlength="8" autocomplete="new-password" required>
                        <button type="button" class="toggle-password" data-toggle-password="password" aria-controls="password" aria-pressed="false">Lihat</button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="konfirmasi_password" class="form-label">Konfirmasi Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="konfirmasi_password" name="konfirmasi_password" class="form-control" placeholder="Ulangi password" minlength="8" autocomplete="new-password" required>
                        <button type="button" class="toggle-password" data-toggle-password="konfirmasi_password" aria-controls="konfirmasi_password" aria-pressed="false">Lihat</button>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-neon btn-lg btn-block">Daftar Akun<?= ikon('panah', 'ikon ikon--sm') ?></button>
        </form>

        <p class="auth-alt">Sudah punya akun? <a href="<?= e(url('auth/login.php')) ?>">Masuk di sini</a></p>
    </section>
</main>
</body>
</html>
