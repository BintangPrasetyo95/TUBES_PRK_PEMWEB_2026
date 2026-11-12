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
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - LabLoan</title>

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
            max-width: 1100px;
            min-height: 650px;
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
            padding: 40px 55px;
        }

        .auth-form h2 {
            margin: 0 0 8px;
            text-align: center;
            font-size: 1.8rem;
        }

        .auth-subtitle {
            margin: 0 0 22px;
            color: #64748b;
            text-align: center;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 13px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 11px 13px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            outline: none;
            font: inherit;
            font-size: 0.88rem;
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
            padding-right: 100px;
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

        .btn-register {
            width: 100%;
            margin-top: 8px;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: #2563eb;
            color: #ffffff;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .btn-register:hover {
            background: #1d4ed8;
        }

        .alert {
            padding: 11px 14px;
            margin-bottom: 15px;
            border-radius: 8px;
            font-size: 0.85rem;
        }

        .alert-danger {
            background: #fee2e2;
            border: 1px solid #dc2626;
            color: #7f1d1d;
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
                padding: 30px 25px;
            }
        }
    </style>
</head>

<body>

    <main class="auth-container">

        <section class="auth-form">
            <h2>Buat Akun LabLoan</h2>

            <p class="auth-subtitle">
                Lengkapi data berikut untuk mendaftar.
            </p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="form-group">
                    <label for="nama">Nama Lengkap</label>
                    <input
                        type="text"
                        id="nama"
                        name="nama"
                        class="form-control"
                        placeholder="Masukkan nama lengkap"
                        value="<?= e($nama) ?>"
                        autocomplete="name"
                        required
                    >
                </div>

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
                    <label for="nim_nip">NIM/NIP</label>
                    <input
                        type="text"
                        id="nim_nip"
                        name="nim_nip"
                        class="form-control"
                        placeholder="Masukkan NIM/NIP"
                        value="<?= e($nim_nip) ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="no_hp">Nomor HP</label>
                    <input
                        type="tel"
                        id="no_hp"
                        name="no_hp"
                        class="form-control"
                        placeholder="Masukkan nomor HP"
                        value="<?= e($no_hp) ?>"
                        autocomplete="tel"
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
                            placeholder="Minimal 8 karakter"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            onclick="togglePassword('password', this)"
                        >Lihat</button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="konfirmasi_password">Konfirmasi Password</label>

                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="konfirmasi_password"
                            name="konfirmasi_password"
                            class="form-control"
                            placeholder="Ulangi password"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            onclick="togglePassword('konfirmasi_password', this)"
                        >Lihat</button>
                    </div>
                </div>

                <button type="submit" class="btn-register">
                    Daftar Akun
                </button>

            </form>
        </section>

        <section class="auth-panel">
            <h1>Selamat Bergabung!</h1>

            <p>
                Akun LabLoan kamu sudah siap dibuat.
                Jika sudah memiliki akun, silakan masuk untuk mulai
                menggunakan layanan peminjaman alat laboratorium.
            </p>

            <a href="<?= e(url('auth/login.php')) ?>" class="panel-button">
                Masuk Sekarang
            </a>
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
