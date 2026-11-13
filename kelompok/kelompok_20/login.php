<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (isLogin()) {
    if (isAdmin()) {
        header('Location: /admin/dashboard.php');
    } else {
        header('Location: /index.php');
    }
    exit;
}

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS percobaan_login (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(100) NOT NULL,
            ip VARCHAR(45) NOT NULL,
            waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
    ");
} catch (PDOException $e) {
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifikasiCsrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    if (empty($email) || empty($password)) {
        $error = 'Silakan masukkan alamat email dan kata sandi Anda.';
    } else {
        $stmt = $pdo->prepare('
            SELECT id, nama, email, password, role
            FROM users
            WHERE email = :email
            LIMIT 1
        ');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        $stmtCek = $pdo->prepare('
            SELECT COUNT(*) AS total_gagal
            FROM percobaan_login
            WHERE email = :email
            AND waktu >= NOW() - INTERVAL 15 MINUTE
        ');
        $stmtCek->execute([':email' => $email]);
        $hasilCek = $stmtCek->fetch();
        $totalGagal = (int)($hasilCek['total_gagal'] ?? 0);

        if ($totalGagal >= 5) {
            $error = 'Terlalu banyak percobaan masuk yang gagal pada email ini. Untuk keamanan, silakan coba kembali setelah 15 menit.';
        } elseif ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            $stmtHapusGagal = $pdo->prepare('
                DELETE FROM percobaan_login
                WHERE email = :email
            ');
            $stmtHapusGagal->execute([':email' => $email]);

            setFlash('success', 'Selamat datang kembali, ' . $user['nama'] . '!');

            if ($user['role'] === 'admin') {
                header('Location: /admin/dashboard.php');
            } else {
                header('Location: /index.php');
            }
            exit;
        } else {
            $stmtCatat = $pdo->prepare('
                INSERT INTO percobaan_login (email, ip, waktu)
                VALUES (:email, :ip, NOW())
            ');
            $stmtCatat->execute([
                ':email' => $email,
                ':ip' => $ip
            ]);

            $sisaPercobaan = 5 - ($totalGagal + 1);

            if ($sisaPercobaan > 0) {
                $error = 'Email atau kata sandi yang Anda masukkan salah. (Sisa percobaan: ' . $sisaPercobaan . ' kali sebelum akun ditunda)';
            } else {
                $error = 'Terlalu banyak percobaan masuk yang gagal. Akun Anda ditunda selama 15 menit.';
            }
        }
    }
}

$pageTitle = 'Masuk ke Akun';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card form-auth">
    <h2 class="card-title" style="text-align: center; margin-bottom: 0.5rem;">Masuk ke Aspiraku</h2>
    <p style="text-align: center; color: var(--color-text-muted); margin-bottom: 1.5rem; font-size: 0.95rem;">
        Silakan masukkan email dan kata sandi untuk melanjutkan.
    </p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?= sanitize($error) ?>
        </div>
    <?php endif; ?>

    <form action="/login.php" method="POST">
        <?= csrfField() ?>

        <div class="form-group">
            <label for="email" class="form-label">Alamat Email</label>
            <input
                type="email"
                id="email"
                name="email"
                class="form-control"
                value="<?= sanitize($email) ?>"
                placeholder="nama@email.com"
                required
                autofocus
            >
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Kata Sandi</label>
            <div style="position: relative;">
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    style="padding-right: 3rem;"
                    placeholder="Masukkan kata sandi"
                    required
                >
                <button type="button" data-password-toggle="password" aria-label="Tampilkan kata sandi" aria-pressed="false" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); border: 0; background: transparent; color: var(--color-text-muted); padding: 0.25rem; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path data-eye-slash d="m3 3 18 18" style="display: none;" /></svg>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">Masuk</button>
    </form>

    <div style="margin-top: 1.5rem; text-align: center; font-size: 0.9rem; color: var(--color-text-muted);">
        Belum memiliki akun? <a href="/register.php" style="color: var(--color-primary); font-weight: 600;">Daftar di sini</a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.getElementById(button.getAttribute('data-password-toggle'));
            if (!input) return;

            var tampil = input.type === 'password';
            input.type = tampil ? 'text' : 'password';
            button.setAttribute('aria-pressed', tampil ? 'true' : 'false');
            button.setAttribute('aria-label', tampil ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            button.querySelector('[data-eye-slash]').style.display = tampil ? 'none' : 'block';
        });
    });
});
</script>

<script src="/assets/js/auth.js" defer></script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
