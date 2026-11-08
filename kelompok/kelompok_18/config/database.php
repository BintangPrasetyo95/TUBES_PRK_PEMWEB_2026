<?php

declare(strict_types=1);

// Konfigurasi lokal Laragon/XAMPP. Sesuaikan host/credential jika server berbeda.
$dbHost = '127.0.0.1';
$dbName = 'attendara';
$dbUser = 'root';
$dbPassword = '';
$dbCharset = 'utf8mb4';

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    // Keep technical details out of the page; inspect server logs during development.
    error_log('ATTENDARA database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Koneksi database gagal. Periksa konfigurasi database dan pastikan server aktif.');
}
