<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/koneksi.php';

$error = '';
$email = '';

$flash = get_flash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        try {
            $pdo = db();

            $query = $pdo->prepare(
                'SELECT id, nama, email, password, role, nim_nip, no_hp
                 FROM users
                 WHERE email = :email'
            );

            $query->execute(['email' => $email]);
            $user = $query->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);

                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'nama' => $user['nama'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'nim_nip' => $user['nim_nip'],
                    'no_hp' => $user['no_hp']
                ];

                set_flash('success', 'Login berhasil.');

                header('Location: ' . url('index.php'));
                exit;
            } else {
                $error = 'Email atau password salah. Periksa kembali data kamu.';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan pada server. Silakan coba kembali.';
        }
    }
}

$judul = 'Masuk';
$bodyClass = 'auth-body';
require __DIR__ . '/../includes/head.php';
?>
<main id="konten" class="auth-shell">
    <?php auth_panel('Selamat datang', 'kembali!', 'Masuk untuk mengajukan peminjaman alat dan ruang laboratorium serta memantau statusnya.', 'Belum punya akun? Daftar', url('auth/register.php')); ?>

    <section class="auth-form" aria-labelledby="auth-judul">
        <h2 id="auth-judul">Masuk Akun</h2>
        <p class="auth-subtitle">Silakan masuk untuk melanjutkan ke <?= e(APP_NAME) ?>.</p>

<?php if ($flash !== null): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
        <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
<?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="nama@email.com" value="<?= e($email) ?>" autocomplete="email" required>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" autocomplete="current-password" required>
                    <button type="button" class="toggle-password" data-toggle-password="password" aria-controls="password" aria-pressed="false">Lihat</button>
                </div>
            </div>

            <button type="submit" class="btn btn-neon btn-lg btn-block">Masuk<?= ikon('panah', 'ikon ikon--sm') ?></button>
        </form>

        <p class="auth-alt">Belum punya akun? <a href="<?= e(url('auth/register.php')) ?>">Daftar sekarang</a></p>
        <p class="auth-alt"><a href="<?= e(url('index.php')) ?>">Kembali ke beranda</a></p>
    </section>
</main>
</body>
</html>
