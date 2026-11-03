<?php
// Proteksi CSRF untuk form POST. Hanya PIC yang boleh mengubah file ini.
//   Di form, tulis csrf_field() di dalam tag form (dengan tag echo pendek).
//   Di handler:  csrf_check();   (panggil sebelum membaca $_POST)

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $kirim = $_POST['csrf_token'] ?? '';
    if (!is_string($kirim) || !hash_equals(csrf_token(), $kirim)) {
        http_response_code(419);
        exit('Token keamanan tidak valid atau sesi habis. Muat ulang halaman lalu coba lagi.');
    }
}
