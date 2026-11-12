<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/koneksi.php';

$error = '';
$email = '';

$success = get_flash('success');

if (is_array($success)) {
    $success = $success['message'] ?? '';
}

if (!is_string($success)) {
    $success = '';
}

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

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LabLoan</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: "Plus Jakarta Sans", system-ui, -apple-system, "Segoe UI", sans-serif;
            background: #f8fafc;
            color: #0f172a;
        }

        .auth-container {
            width: 100%;
            max-width: 1050px;
            min-height: 590px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .auth-panel {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px;
            background: #2563eb;
            color: #ffffff;
            text-align: center;
        }

        .auth-panel h1 {
            margin: 0 0 16px;
            font-size: 2rem;
        }

        .auth-panel p {
            max-width: 330px;
            margin: 0 0 28px;
            line-height: 1.7;
            color: #dbeafe;
        }

        .panel-button {
            display: inline-block;
            padding: 12px 36px;
            border: 1px solid #ffffff;
            border-radius: 8px;
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .panel-button:hover {
            background: #ffffff;
            color: #2563eb;
        }

        .auth-form {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 55px 65px;
        }

        .auth-form h2 {
            margin: 0 0 8px;
            text-align: center;
            font-size: 1.9rem;
        }

        .auth-subtitle {
            margin: 0 0 30px;
            color: #64748b;
            text-align: center;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 13px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            outline: none;
            font: inherit;
            font-size: 0.9rem;
            background: #f8fafc;
            transition: 0.2s ease;
        }

        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px #dbeafe;
            background: #ffffff;
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper .form-control {
            padding-right: 75px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            border: none;
            background: none;
            color: #2563eb;
            font: inherit;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 8px;
            background: #2563eb;
            color: #ffffff;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .btn-login:hover {
            background: #1d4ed8;
        }

        .alert {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 0.875rem;
        }

        .alert-danger {
            background: #fee2e2;
            border: 1px solid #dc2626;
            color: #7f1d1d;
        }

        .alert-success {
            background: #dcfce7;
            border: 1px solid #16a34a;
            color: #14532d;
        }

        @media (max-width: 700px) {
            body {
                padding: 15px;
            }

            .auth-container {
                grid-template-columns: 1fr;
            }

            .auth-panel {
                padding: 35px 25px;
            }

            .auth-panel h1 {
                font-size: 1.6rem;
            }

            .auth-form {
                padding: 35px 25px;
            }
        }
    </style>
</head>

<body>

    <main class="auth-container">

        <section class="auth-panel">
            <h1>Selamat Datang!</h1>

            <p>
                LabLoan membantu proses peminjaman alat laboratorium
                agar lebih mudah dan terorganisasi.
            </p>

            <a href="<?= e(url('auth/register.php')) ?>" class="panel-button">
                Daftar Sekarang
            </a>
        </section>

        <section class="auth-form">
            <h2>Masuk Akun</h2>

            <p class="auth-subtitle">
                Silakan masuk untuk melanjutkan ke LabLoan.
            </p>

            <?php if ($success !== ''): ?>
                <div class="alert alert-success">
                    <?= e($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="form-group">
                    <label for="email">Email</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="Masukkan email"
                        value="<?= e($email) ?>"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>

                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Masukkan password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            onclick="togglePassword('password', this)"
                        >Lihat</button>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    Masuk
                </button>

            </form>
        </section>

    </main>

    <script>
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);

            if (input.type === 'password') {
                input.type = 'text';
                button.textContent = 'Sembunyikan';
            } else {
                input.type = 'password';
                button.textContent = 'Lihat';
            }
        }
    </script>

</body>
</html>
