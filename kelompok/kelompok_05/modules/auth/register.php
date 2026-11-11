
<?php
// Registrasi akun baru CEKHAK.
require_once __DIR__ . '/../../includes/bootstrap.php';

// Jika sudah login, kembali ke beranda.
if (current_user() !== null) {
    redirect('index.php');
}

$error = '';
$nama = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';

    // Validasi nama.
    if ($nama === '' || strlen($nama) > 100) {
        $error = 'Nama wajib diisi dan maksimal 100 karakter.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($email) > 150) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        // Periksa apakah email sudah digunakan.
        $st = db()->prepare(
            'SELECT id FROM users WHERE email = ? LIMIT 1'
        );
        $st->execute([$email]);

        if ($st->fetch()) {
            $error = 'Email sudah terdaftar. Gunakan email lain.';
        } else {
            // Hash password sebelum disimpan.
            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Role selalu pengguna.
            $st = db()->prepare(
                'INSERT INTO users (nama, email, password_hash, role)
                 VALUES (?, ?, ?, ?)'
            );

            $st->execute([
                $nama,
                $email,
                $passwordHash,
                'pengguna'
            ]);

            $userId = (int) db()->lastInsertId();

            audit_log(
                'register',
                'users',
                $userId,
                'Pengguna baru berhasil mendaftar'
            );

            flash(
                'success',
                'Registrasi berhasil. Silakan login.'
            );

            redirect('modules/auth/login.php');
        }
    }
}

$page_title = 'Daftar Akun';
require __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h1>Daftar Akun CEKHAK</h1>

    <p>Isi formulir berikut untuk membuat akun baru.</p>

    <?php if ($error !== ''): ?>
        <div class="alert alert-error">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="nama">Nama Lengkap</label>
            <input
                type="text"
                id="nama"
                name="nama"
                maxlength="100"
                value="<?= e($nama) ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                maxlength="150"
                value="<?= e($email) ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                required
            >
        </div>

        <div class="form-group">
            <label for="konfirmasi_password">
                Konfirmasi Password
            </label>
            <input
                type="password"
                id="konfirmasi_password"
                name="konfirmasi_password"
                minlength="8"
                required
            >
        </div>

        <button type="submit" class="btn">
            Daftar
        </button>
    </form>

    <p>
        Sudah punya akun?
        <a href="<?= e(base_url('modules/auth/login.php')) ?>">
            Masuk di sini
        </a>
    </p>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
