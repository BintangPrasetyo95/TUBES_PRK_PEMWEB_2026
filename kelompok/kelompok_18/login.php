<?php

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';
startAppSession();

if (isLoggedIn()) {
    redirectToDashboard();
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameInput = $_POST['username'] ?? null;
    $passwordInput = $_POST['password'] ?? null;
    $username = is_string($usernameInput) ? trim($usernameInput) : '';
    $password = is_string($passwordInput) ? $passwordInput : '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        require __DIR__ . '/config/database.php';

        try {
            $statement = $pdo->prepare(
                'SELECT id, username, password_hash, role, is_active
                 FROM users
                 WHERE username = :username
                 LIMIT 1'
            );
            $statement->execute(['username' => $username]);
            $user = $statement->fetch();
        } catch (PDOException $exception) {
            error_log('ATTENDARA login query failed: ' . $exception->getMessage());
            http_response_code(500);
            exit('Login sementara tidak dapat diproses.');
        }

        if (
            is_array($user)
            && (bool) $user['is_active']
            && in_array($user['role'], ['admin', 'guru'], true)
            && password_verify($password, $user['password_hash'])
        ) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['username'] = (string) $user['username'];
            $_SESSION['role'] = (string) $user['role'];

            redirectToDashboard();
        }

        $error = 'Username atau password tidak valid, atau akun tidak aktif.';
    }
}

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — ATTENDARA</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
    <main class="w-full max-w-md rounded-2xl bg-white p-8 shadow-lg">
        <h1 class="text-2xl font-bold text-slate-900">Masuk ke ATTENDARA</h1>
        <p class="mt-2 text-sm text-slate-600">Gunakan akun admin atau guru.</p>

        <?php if ($error !== ''): ?>
            <p class="mt-5 rounded-lg bg-red-50 p-3 text-sm text-red-700" role="alert"><?= escapeHtml($error) ?></p>
        <?php endif; ?>

        <form method="post" action="login.php" class="mt-6 space-y-5" autocomplete="on">
            <div>
                <label for="username" class="mb-1 block text-sm font-medium text-slate-700">Username</label>
                <input id="username" name="username" type="text" required maxlength="100" autocomplete="username"
                       value="<?= escapeHtml($username) ?>"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Password</label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>
            <button type="submit" class="w-full rounded-lg bg-blue-700 px-4 py-2.5 font-semibold text-white hover:bg-blue-800">Masuk</button>
        </form>
    </main>
</body>
</html>
