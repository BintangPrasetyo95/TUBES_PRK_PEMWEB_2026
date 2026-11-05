
<?php

date_default_timezone_set('Asia/Jakarta');

$dbHost = '127.0.0.1';
$dbPort = 3306;
$dbName = 'klinik_antrian';
$dbUser = 'root';
$dbPass = '';

$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);

    $pdo->exec("SET time_zone = '+07:00'");

} catch (PDOException $e) {
    error_log('[Database Klinik] ' . $e->getMessage());

    http_response_code(500);

    exit('Koneksi database gagal. Periksa konfigurasi database.');
}
