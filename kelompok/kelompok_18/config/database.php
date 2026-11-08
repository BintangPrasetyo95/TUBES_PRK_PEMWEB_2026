<?php

declare(strict_types=1);

// Supply credentials through the PHP process environment; never place them in
// frontend code or commit them to the repository.
$dbHost = getenv('ATTENDARA_DB_HOST') ?: '127.0.0.1';
$dbName = getenv('ATTENDARA_DB_NAME') ?: 'attendara';
$dbUser = getenv('ATTENDARA_DB_USER');
$dbPassword = getenv('ATTENDARA_DB_PASSWORD');
$dbCharset = 'utf8mb4';

if ($dbUser === false || $dbUser === '') {
    error_log('ATTENDARA database configuration is missing ATTENDARA_DB_USER.');
    http_response_code(500);
    exit('Konfigurasi database belum lengkap.');
}

if ($dbPassword === false) {
    $dbPassword = '';
}

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
    exit('Koneksi database gagal.');
}
