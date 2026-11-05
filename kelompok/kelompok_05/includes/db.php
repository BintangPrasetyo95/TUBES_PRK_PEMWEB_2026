<?php
// Konfigurasi dan koneksi database. Hanya PIC yang boleh mengubah file ini.

/**
 * Membaca pengaturan dari config/config.local.php.
 * config('db_name')            -> nilai satu pengaturan
 * config('dev_mode', false)    -> dengan nilai bawaan bila tidak ada
 */
function config(?string $key = null, $default = null)
{
    static $cfg = null;
    if ($cfg === null) {
        $file = __DIR__ . '/../config/config.local.php';
        if (!is_file($file)) {
            http_response_code(500);
            exit('File config/config.local.php belum ada. Salin config/config.example.php menjadi config.local.php.');
        }
        $cfg = require $file;
    }
    if ($key === null) {
        return $cfg;
    }
    return $cfg[$key] ?? $default;
}

/**
 * Koneksi database (PDO). Selalu pakai prepared statement:
 *   $st = db()->prepare('SELECT * FROM users WHERE id = ?');
 *   $st->execute([$id]);
 *   $row = $st->fetch();
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            config('db_host', '127.0.0.1'),
            (int) config('db_port', 3306),
            config('db_name', 'cekhak')
        );
        try {
            $pdo = new PDO($dsn, config('db_user', 'root'), config('db_pass', ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            $detail = config('dev_mode', false) ? ' Detail: ' . $e->getMessage() : '';
            $pesan = 'Tidak dapat terhubung ke database. Pastikan MySQL di XAMPP menyala dan database/schema.sql sudah diimpor.' . $detail;
            if (defined('API_MODE')) {
                api_error($pesan, 500); // endpoint API selalu membalas JSON
            }
            http_response_code(500);
            exit($pesan);
        }
    }
    return $pdo;
}
