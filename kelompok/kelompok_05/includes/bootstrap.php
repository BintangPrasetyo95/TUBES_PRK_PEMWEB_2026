<?php
// WAJIB di-require paling awal oleh SEMUA halaman. Dari modules/<fitur>/ tulis:
//   require_once __DIR__ . '/../../includes/bootstrap.php';
// Hanya PIC yang boleh mengubah file ini.

define('APP_ROOT', dirname(__DIR__));

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/api.php';
require_once __DIR__ . '/validasi.php';

if (config('dev_mode', false)) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

if (session_status() === PHP_SESSION_NONE) {
    // Nama sesi khusus agar tidak bentrok dengan aplikasi lain di localhost.
    session_name('cekhak_sid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
