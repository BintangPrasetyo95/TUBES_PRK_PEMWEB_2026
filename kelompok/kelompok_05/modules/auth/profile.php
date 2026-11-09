
<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

// Halaman profil hanya bisa dibuka oleh pengguna yang sudah login.
require_login();

$user = current_user();
$error = '';

$nama = $user['nama'];
$email = $user['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // Validasi nama.
    if ($nama === '' || strlen($nama) > 100) {
        $error = 'Nama wajib diisi dan maksimal 100 karakter.';
    }
    // Validasi email.
    elseif (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 150
    ) {
        $error = 'Format email tidak valid atau terlalu panjang.';
    } else {
        // Pastikan email belum digunakan oleh akun lain.
        $st = db()->prepare(
            'SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1'
        );
        $st->execute([$email, $user['id']]);

        if ($st->fetch()) {
            $error = 'Email sudah digunakan oleh akun lain.';
        } else {
            // Simpan perubahan nama dan email.
            $st = db()->prepare(
                'UPDATE users SET nama = ?, email = ? WHERE id = ?'
            );
            $st->execute([$nama, $email, $user['id']]);

            audit_log(
                'update_profile',
                'users',
                (int) $user['id'],
                'Pengguna memperbarui profil'
            );

            flash('success', 'Profil berhasil diperbarui.');

            // Muat ulang halaman agar data terbaru ditampilkan.
            redirect('modules/auth/profile.php');
        }
    }
}

$page_title = 'Profil Saya';
require __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h1>Profil Saya</h1>
    <p>Lihat dan perbarui informasi akun CEKHAK kamu.</p>

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
            <label>Peran Akun</label>
            <input
                type="text"
                value="<?= e(ucfirst($user['role'])) ?>"
                disabled
            >
        </div>

        <button type="submit" class="btn">
            Simpan Perubahan
        </button>

        <a href="../../index.php" class="btn">
            Kembali
        </a>
    </form>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
