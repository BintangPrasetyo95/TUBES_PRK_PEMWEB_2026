<?php
/**
 * File: auth/register.php
 * Modul 1 (Anggota 1): Pendaftaran Akun Pendonor Baru.
 * Menggunakan PDO Prepared Statement & password_hash() BCRYPT.
 */

require_once __DIR__ . '/../config/auth_check.php';

// Jika sudah login, langsung arahkan ke dashboard sesuai role
if (isLoggedIn()) {
    redirectByRole();
}

$pageTitle = 'Daftar Akun Pendonor — OneBlood FK Unila';
$errors    = [];

// Nilai default input (tidak ditampilkan langsung di atribut HTML bila ingin aman di Live Server,
// tapi disimpan untuk validasi server-side)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama        = trim((string)($_POST['nama'] ?? ''));
    $email       = trim((string)($_POST['email'] ?? ''));
    $no_hp       = trim((string)($_POST['no_hp'] ?? ''));
    $password    = (string)($_POST['password'] ?? '');
    $konfirmasi  = (string)($_POST['konfirmasi_password'] ?? '');
    $gol_darah   = (string)($_POST['gol_darah'] ?? '');
    $rhesus      = (string)($_POST['rhesus'] ?? '');
    $tgl_lahir   = (string)($_POST['tgl_lahir'] ?? '');
    $berat_badan = (float)($_POST['berat_badan'] ?? 0);
    $alamat      = trim((string)($_POST['alamat'] ?? ''));

    // 1. Validasi Input Server-Side
    if ($nama === '') {
        $errors[] = 'Nama lengkap wajib diisi.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format alamat email tidak valid.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password minimal terdiri dari 6 karakter.';
    }
    if ($password !== $konfirmasi) {
        $errors[] = 'Konfirmasi password tidak cocok.';
    }
    if (!in_array($gol_darah, ['A', 'B', 'AB', 'O'], true)) {
        $errors[] = 'Silakan pilih golongan darah (A, B, AB, atau O).';
    }
    if (!in_array($rhesus, ['+', '-'], true)) {
        $errors[] = 'Silakan pilih rhesus darah (+ atau -).';
    }
    if ($berat_badan > 0 && $berat_badan < 45) {
        $errors[] = 'Berat badan minimal untuk mendaftar sebagai pendonor adalah 45 kg.';
    }

    // 2. Cek apakah email sudah terdaftar di tabel users
    if (empty($errors)) {
        try {
            $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmtCheck->execute([':email' => $email]);

            if ($stmtCheck->fetch()) {
                $errors[] = 'Email tersebut sudah terdaftar. Silakan gunakan email lain atau langsung login.';
            } else {
                // 3. Simpan akun pendonor baru menggunakan PDO Prepared Statement
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmtInsert = $db->prepare("
                    INSERT INTO users 
                        (nama, email, password, no_hp, alamat, role, gol_darah, rhesus, tgl_lahir, berat_badan, status_aktif)
                    VALUES 
                        (:nama, :email, :password, :no_hp, :alamat, 'pendonor', :gol_darah, :rhesus, :tgl_lahir, :berat_badan, 1)
                ");

                $stmtInsert->execute([
                    ':nama'        => $nama,
                    ':email'       => $email,
                    ':password'    => $hashedPassword,
                    ':no_hp'       => $no_hp !== '' ? $no_hp : null,
                    ':alamat'      => $alamat !== '' ? $alamat : null,
                    ':gol_darah'   => $gol_darah,
                    ':rhesus'      => $rhesus,
                    ':tgl_lahir'   => $tgl_lahir !== '' ? $tgl_lahir : null,
                    ':berat_badan' => $berat_badan > 0 ? $berat_badan : null,
                ]);

                setFlash('success', 'Pendaftaran berhasil! Silakan masuk menggunakan akun pendonor Anda.');
                header('Location: ' . base_url('auth/login.php'));
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = 'Gagal menyimpan pendaftaran: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar Akun Pendonor</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

  <div class="login-wrapper">
    <!-- SISI KIRI: Tampilan Gambar -->
    <div class="login-illustration">
      <img src="Blood donation Customizable Isometric Illustrations _ Amico Style.jpg" alt="Ilustrasi OneBlood">
    </div>

    <!-- SISI KANAN: Tampilan Kartu Registrasi -->
    <div class="login-right">
      <div class="login-card">
        <h2 class="login-title">Daftar Akun Pendonor</h2>

        <?php if (!empty($errors)): ?>
          <div class="login-alert">
            <?php foreach ($errors as $err): ?>
              <div><?= e($err) ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form action="" method="POST" autocomplete="off">
          <div class="input-group">
            <i class="fa-solid fa-user"></i>
            <input type="text" name="nama" placeholder="Nama Lengkap" required>
          </div>

          <div class="input-group">
            <i class="fa-solid fa-envelope"></i>
            <input type="email" name="email" placeholder="Alamat Email Aktif" required>
          </div>

          <div class="input-group">
            <i class="fa-solid fa-phone"></i>
            <input type="text" name="no_hp" placeholder="Nomor WhatsApp / HP (Contoh: 0812xxxx)" required>
          </div>

          <div class="grid-2">
            <div class="input-group">
              <i class="fa-solid fa-droplet"></i>
              <select name="gol_darah" required>
                <option value="">Gol. Darah</option>
                <option value="A">Golongan A</option>
                <option value="B">Golongan B</option>
                <option value="AB">Golongan AB</option>
                <option value="O">Golongan O</option>
              </select>
            </div>

            <div class="input-group">
              <i class="fa-solid fa-plus-minus"></i>
              <select name="rhesus" required>
                <option value="">Rhesus</option>
                <option value="+">Positif (+)</option>
                <option value="-">Negatif (-)</option>
              </select>
            </div>
          </div>

          <div class="grid-2">
            <div class="input-group">
              <i class="fa-solid fa-calendar-day"></i>
              <input type="date" name="tgl_lahir" title="Tanggal Lahir" required>
            </div>

            <div class="input-group">
              <i class="fa-solid fa-weight-scale"></i>
              <input type="number" name="berat_badan" placeholder="Berat Badan (kg)" min="45" max="200" step="0.5" required>
            </div>
          </div>

          <div class="input-group">
            <i class="fa-solid fa-location-dot"></i>
            <input type="text" name="alamat" placeholder="Alamat Domisili Saat Ini" required>
          </div>

          <div class="grid-2">
            <div class="input-group">
              <i class="fa-solid fa-lock"></i>
              <input type="password" name="password" placeholder="Password" required>
            </div>

            <div class="input-group">
              <i class="fa-solid fa-shield-halved"></i>
              <input type="password" name="konfirmasi_password" placeholder="Ulangi Password" required>
            </div>
          </div>

          <div class="form-options">
            <span class="remember-me">Sudah punya akun?</span>
            <a href="login.php" class="forget-link">Masuk di sini</a>
          </div>

          <button type="submit" class="btn-login">Daftar Sekarang</button>
        </form>
      </div>
    </div>
  </div>

</body>
</html>