
<?php
// Login pengguna CEKHAK.
// Menggunakan fungsi autentikasi dan keamanan yang disediakan PIC.

require_once __DIR__ . '/../../includes/bootstrap.php';

// Jika sudah login, arahkan ke halaman utama.
if (current_user() !== null) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validasi input.
    if ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        // Cari pengguna berdasarkan email.
        $st = db()->prepare(
            'SELECT id, nama, email, password_hash, role
             FROM users
             WHERE email = ?
             LIMIT 1'
        );

        $st->execute([$email]);
        $user = $st->fetch();

        // Verifikasi password.
        if ($user && password_verify($password, $user['password_hash'])) {

            login_user($user);

            audit_log(
                'login',
                'users',
                (int) $user['id'],
                'Pengguna berhasil login'
            );

            flash('success', 'Selamat datang, ' . $user['nama'] . '!');

            redirect('index.php');

        } else {
            $error = 'Email atau password salah.';
        }
    }
}

$page_title = 'Masuk';
require __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h1>Masuk ke CEKHAK</h1>
    <p class="muted">
        Silakan masuk menggunakan email dan password akun kamu.
    </p>

    <?php if ($error !== ''): ?>
        <div class="alert alert-error">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="email">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                maxlength="150"
                value="<?= e($_POST['email'] ?? '') ?>"
                required
                autocomplete="username"
            >
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="current-password"
            >
        </div>

        <button type="submit" class="btn">
            Masuk
        </button>
    </form>

    <p>
        Belum memiliki akun?
        <a href="<?= e(base_url('modules/auth/register.php')) ?>">
            Daftar sekarang
        </a>
    </p>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
