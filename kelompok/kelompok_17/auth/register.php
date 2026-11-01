<?php
session_start();
require_once '../config/database.php';

$pesan = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_lengkap = htmlspecialchars($_POST['nama_lengkap']);
    $npm = htmlspecialchars($_POST['npm']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 
    $role = 'mahasiswa';

    try {
        $stmt = $pdo->prepare("INSERT INTO users (nama_lengkap, npm, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nama_lengkap, $npm, $password, $role]);
        $pesan = "<p class='text-green-500 mb-4'>Registrasi berhasil! Silakan login.</p>";
    } catch (PDOException $e) {
        $pesan = "<p class='text-red-500 mb-4'>Error: NPM sudah terdaftar atau terjadi kesalahan.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register - KabarSi</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-96">
        <h2 class="text-2xl font-bold mb-6 text-center">Daftar Akun Mahasiswa</h2>
        <?= $pesan; ?>
        <form method="POST" action="">
            <div class="mb-4">
                <label class="block text-gray-700">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" required class="w-full px-3 py-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">NPM</label>
                <input type="text" name="npm" required class="w-full px-3 py-2 border rounded">
            </div>
            <div class="mb-6">
                <label class="block text-gray-700">Password</label>
                <input type="password" name="password" required class="w-full px-3 py-2 border rounded">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Daftar</button>
        </form>
        <p class="mt-4 text-sm text-center">Sudah punya akun? <a href="login.php" class="text-blue-600">Login di sini</a></p>
    </div>
</body>
</html>