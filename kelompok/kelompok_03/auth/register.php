<?php

require_once __DIR__ . '/../includes/init.php';

// Jika pengguna sudah login, langsung alihkan sesuai role (admin atau pasien)
if (isset($_SESSION['user_id'])) {
    $redirect = $_SESSION['role'] === 'admin' ? '../admin/dokter_list.php' : '../pasien/pasien.php';
    header("Location: $redirect");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($nama) || empty($email) || empty($password) || empty($password_confirm)) {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $password_confirm) {
        $error = 'Password dan konfirmasi password tidak sama.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email sudah terdaftar.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)');
                $stmt->execute([$nama, $email, $passwordHash, 'pasien']);
                $success = 'Registrasi berhasil. Silakan login.';
            }
        } catch (PDOException $e) {
            error_log('[Register] ' . $e->getMessage());
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
    <title>Register - Sistem Antrian Klinik</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="halaman-auth">
    <header class="topbar">
        <a href="../index.php" class="brand fw-bold">Klinik FK Unila</a>
        <nav aria-label="Menu utama">
            <a href="../index.php">Jadwal</a>
            <a href="login.php" class="tombol-kecil">Masuk</a>
        </nav>
    </header>
    <main class="wadah-sempit">
        <div class="kartu">
            <div class="kartu-isi">
                <p class="muted small">Sistem Antrian Klinik</p>
                <h3 class="tengah">Daftar Akun</h3>
                <?php if ($error): ?>
                    <div class="peringatan peringatan-bahaya" role="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="peringatan peringatan-sukses" role="alert"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="grup-form">
                        <label for="nama">Nama Lengkap</label>
                        <input type="text" class="masukan" id="nama" name="nama" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
                    </div>
                    <div class="grup-form">
                        <label for="email">Email</label>
                        <input type="email" class="masukan" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="grup-form">
                        <label for="password">Password</label>
                        <input type="password" class="masukan" id="password" name="password" required>
                        <small class="muted">Minimal 6 karakter</small>
                    </div>
                    <div class="grup-form">
                        <label for="password_confirm">Konfirmasi Password</label>
                        <input type="password" class="masukan" id="password_confirm" name="password_confirm" required>
                    </div>
                    <button type="submit" class="tombol tombol-utama tombol-penuh">Register</button>
                </form>
                <p class="tengah spasi-atas">Sudah punya akun? <a href="login.php">Login di sini</a></p>
            </div>
        </div>
    </main>
</body>
</html>