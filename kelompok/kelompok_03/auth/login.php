<?php

require_once __DIR__ . '/../includes/init.php';

// Jika sudah login, langsung alihkan ke dashboard masing-masing role (admin atau pasien)
if (isset($_SESSION['user_id'])) {
    $redirect = $_SESSION['role'] === 'admin' ? '../admin/admin.php' : '../pasien/pasien.php';
    header("Location: $redirect");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id, nama, email, password, role FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                // Alihkan ke dashboard admin jika role admin, atau ke portal pasien (pasien.php) jika role pasien
                $redirect = $user['role'] === 'admin' ? '../admin/admin.php' : '../pasien/pasien.php';
                header("Location: $redirect");
                exit;
            } else {
                $error = 'Email atau password salah.';
            }
        } catch (PDOException $e) {
            error_log('[Login] ' . $e->getMessage());
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Antrian Klinik</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="halaman-auth">
    <header class="topbar">
        <a href="../index.php" class="brand">Klinik FK Unila</a>
        <nav aria-label="Menu utama">
            <a href="../index.php">Jadwal</a>
            <a href="register.php" class="tombol-kecil">Daftar</a>
        </nav>
    </header>
    <main class="wadah">
        <div class="layout-auth">
            <div>
                <span class="lencana-auth">Sistem Antrian Klinik</span>
                <h1>Layanan Antrian Online Klinik FK Unila</h1>
                <p class="muted">
                    Masuk untuk mengambil nomor antrian poli, memantau jadwal dokter praktik, dan melihat estimasi giliran pemeriksaan Anda secara real-time.
                </p>
                <div class="daftar-poin">
                    <div>✓ Cek jadwal & kuota dokter hari ini</div>
                    <div>✓ Ambil nomor antrian tanpa perlu antre lama di lokasi</div>
                </div>
            </div>

            <div class="kartu">
                <div class="kartu-isi">
                    <h3 class="tebal">Masuk</h3>
                    <p class="muted small">Gunakan akun yang sudah terdaftar</p>

                    <?php if ($error): ?>
                        <div class="peringatan peringatan-bahaya" role="alert">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="grup-form">
                            <label for="email">Email</label>
                            <input type="email" class="masukan" id="email" name="email"
                                   placeholder="nama@email.com"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                        <div class="grup-form">
                            <label for="password">Password</label>
                            <input type="password" class="masukan" id="password" name="password"
                                   placeholder="••••••••" required>
                        </div>
                        <button type="submit" class="tombol tombol-utama tombol-penuh">Login</button>
                    </form>

                    <p class="tengah small muted spasi-atas">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>