<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = '';
$berhasil = false;

$stmt = $pdo->prepare("
    SELECT id, user_id
    FROM password_resets
    WHERE token_hash = :token_hash
    AND expires_at > NOW()
    AND used_at IS NULL
    LIMIT 1
");

$stmt->execute([
    ':token_hash' => hash('sha256', $token)
]);

$dataReset = $stmt->fetch();
$tokenValid = !empty($token) && $dataReset;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifikasiCsrf();

    if (!$tokenValid) {
        $error = 'Tautan reset tidak valid atau sudah kedaluwarsa.';
    } else {
        $password = $_POST['password_baru'] ?? '';
        $konfirmasi = $_POST['konfirmasi_password'] ?? '';

        if (strlen($password) < 6) {
            $error = 'Password minimal 6 karakter.';
        } elseif ($password !== $konfirmasi) {
            $error = 'Konfirmasi password tidak cocok.';
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("
                    SELECT id, user_id
                    FROM password_resets
                    WHERE id = :id
                    AND expires_at > NOW()
                    AND used_at IS NULL
                    FOR UPDATE
                ");

                $stmt->execute([
                    ':id' => $dataReset['id']
                ]);

                $tokenAktif = $stmt->fetch();

                if (!$tokenAktif) {
                    throw new Exception('Token tidak berlaku.');
                }

                $hashPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = $pdo->prepare("
                    UPDATE users
                    SET password = :password
                    WHERE id = :user_id
                ");

                $stmt->execute([
                    ':password' => $hashPassword,
                    ':user_id' => $tokenAktif['user_id']
                ]);

                $stmt = $pdo->prepare("
                    UPDATE password_resets
                    SET used_at = NOW()
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':id' => $tokenAktif['id']
                ]);

                $pdo->commit();

                $berhasil = true;

            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log($e->getMessage());
                $error = 'Gagal mengubah password. Silakan coba kembali.';
            }
        }
    }
}

$pageTitle = 'Reset Password';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card form-auth">
    <h2 class="card-title" style="text-align:center;">
        Reset Password
    </h2>

    <?php if ($berhasil): ?>

        <div class="alert alert-success">
            Password berhasil diubah. Silakan login menggunakan password baru.
        </div>

        <a href="login.php" class="btn btn-primary"
            style="display:block; text-align:center;">
            Kembali ke Login
        </a>

    <?php elseif (!$tokenValid): ?>

        <div class="alert alert-danger">
            Tautan reset password tidak valid atau sudah kedaluwarsa.
        </div>

        <a href="lupa_password.php">
            Buat tautan reset baru
        </a>

    <?php else: ?>

        <p style="text-align:center; color:var(--color-text-muted);">
            Masukkan password baru untuk akun Anda.
        </p>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= sanitize($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="reset_password.php">
            <?= csrfField() ?>

            <input type="hidden" name="token"
                value="<?= sanitize($token) ?>">

            <div class="form-group">
                <label for="password_baru" class="form-label">
                    Password Baru
                </label>

                <input
                    type="password"
                    id="password_baru"
                    name="password_baru"
                    class="form-control"
                    minlength="6"
                    required
                >
            </div>

            <div class="form-group">
                <label for="konfirmasi_password" class="form-label">
                    Konfirmasi Password
                </label>

                <input
                    type="password"
                    id="konfirmasi_password"
                    name="konfirmasi_password"
                    class="form-control"
                    minlength="6"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary"
                style="width:100%;">
                Simpan Password Baru
            </button>
        </form>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
