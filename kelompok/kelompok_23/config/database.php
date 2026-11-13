<?php

// Koneksi DB otomatis: config lokal -> buat DB/tabel bila perlu.
mysqli_report(MYSQLI_REPORT_OFF);

require_once __DIR__ . '/setup_helpers.php';

$dbConfig = [
    'host'     => 'localhost',
    'username' => 'root',
    'password' => '',
    'dbname'   => 'febmart',
];

$localConfigFile = __DIR__ . '/database.local.php';

if (is_file($localConfigFile)) {
    $loaded = require $localConfigFile;

    if (is_array($loaded)) {
        $dbConfig = array_merge($dbConfig, $loaded);
    }
}

$host        = $dbConfig['host'];
$db_username = $dbConfig['username'];
$db_password = $dbConfig['password'];
$db_name     = febmart_sanitize_dbname($dbConfig['dbname']);

$conn = new mysqli($host, $db_username, $db_password, $db_name);

// Buat database otomatis jika belum ada (errno 1049).
if ($conn->connect_errno === 1049) {
    $tmp = new mysqli($host, $db_username, $db_password);

    if ($tmp->connect_errno === 0) {
        $tmp->query(
            'CREATE DATABASE IF NOT EXISTS `' . $db_name . '` '
            . 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        $tmp->close();
    }

    $conn = new mysqli($host, $db_username, $db_password, $db_name);
}

if ($conn->connect_errno) {
    $conn = null;

    $scriptFile = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
    if (basename($scriptFile) === 'install.php') {
        exit;
    }

    header('Location: ' . febmart_install_path());
    exit;
}

$conn->set_charset('utf8mb4');

$analisis = febmart_table_analysis($conn);

febmart_apply_migrations($conn, $analisis['ada']);

if ($analisis['hilang']) { // buat tabel yang hilang otomatis
    febmart_run_sql_file($conn, dirname(__DIR__) . '/database/febmart.sql', $db_name);
}
