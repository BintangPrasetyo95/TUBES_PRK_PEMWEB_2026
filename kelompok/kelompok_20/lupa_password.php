<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo->exec("
    CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token_hash VARCHAR(64) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
            ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$error = '';
$sukses = '';
$linkReset = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifikasiCsrf();

    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Masukkan alamat email yang valid.';
    } else {
        $stmt = $pdo->prepare("
            SELECT id FROM users
            WHERE email = :email
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);

            $stmt = $pdo->prepare("
                DELETE FROM password_resets
                WHERE user_id = :user_id
            ");
            $stmt->execute([':user_id' => $user['id']]);

            $stmt = $pdo->prepare("
                INSERT INTO password_resets
                (user_id, token_hash, expires_at)
                VALUES (
                    :user_id,
                    :token_hash,
                    DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                )
            ");

            $stmt->execute([
                ':user_id' => $user['id'],
                ':token_hash' => $tokenHash
            ]);

            $linkReset = 'reset_password.php?token=' . urlencode($token);

            $sukses = 'Tautan reset password berhasil dibuat.';
        } else {
            $error = 'Email tidak ditemukan. Pastikan email sesuai dengan akun yang terdaftar.';
        }
    }
}

$pageTitle = 'Lupa Password';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card form-auth">
    <h2 class="card-title" style="text-align:center;">
        Lupa Password
    </h2>

    <p style="text-align:center; color:var(--color-text-muted);">
        Masukkan email akun Anda untuk membuat tautan reset password.
    </p>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= sanitize($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($sukses): ?>
        <div class="alert alert-success alert-persistent">
            <?= sanitize($sukses) ?>
        </div>

        <div class="alert alert-success alert-persistent" style="overflow-wrap:anywhere;">
            <strong>Tautan Reset (Simulasi):</strong><br>
            <a href="<?= sanitize($linkReset) ?>">
                Buka Halaman Reset Password
            </a>
        </div>
    <?php endif; ?>

    <form method="POST" action="lupa_password.php">
        <?= csrfField() ?>

        <div class="form-group">
            <label for="email" class="form-label">
                Alamat Email
            </label>

            <input
                type="email"
                id="email"
                name="email"
                class="form-control"
                placeholder="nama@email.com"
                required
            >
        </div>

        <button type="submit" class="btn btn-primary"
            style="width:100%;">
            Buat Tautan Reset
        </button>
    </form>

    <div style="margin-top:1.5rem; text-align:center;">
        <a href="login.php" style="color:var(--color-primary);">
            Kembali ke halaman login
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
