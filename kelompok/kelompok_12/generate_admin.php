<?php
// kelompok/kelompok_12/generate_admin.php
require_once 'config/database.php';

$username = 'admin';
$password_plain = 'admin123'; // Password baru yang mau kamu pakai

// Hash password dengan algoritma BCRYPT bawaan PHP
$password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);

try {
    // Gunakan UPDATE untuk menimpa password admin yang sudah ada
    $stmt = $pdo->prepare("UPDATE admins SET password = :password WHERE username = :username");
    $stmt->execute([
        ':password' => $password_hashed,
        ':username' => $username
    ]);

    echo "Password akun <b>$username</b> berhasil di-update!<br>";
    echo "Sekarang kamu bisa login dengan password: <b>$password_plain</b>";
} catch (PDOException $e) {
    echo "Gagal meng-update password: " . $e->getMessage();
}