<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('APP_NAME')) {
    define('APP_NAME', 'LabLoan');
    define('APP_TIMEZONE', 'Asia/Jakarta');
    define('APP_ROOT', str_replace('\\', '/', dirname(__DIR__)));
    define('BASE_URL', detect_base_url());

    define('LAB_NAMA', 'Laboratorium Teknik Elektro');
    define('LAB_ALAMAT', 'Jl. Prof. Dr. Ir. Sumantri Brojonegoro, Gedong Meneng, Kec. Rajabasa, Kota Bandar Lampung, Lampung 35141');
    define('LAB_EMAIL', 'labkom@eng.unila.ac.id');
    define('LAB_TELEPON', '(0721) 701609');
    define('LAB_JAM_OPERASIONAL', [
        'Senin - Kamis' => '08.00 - 17.00 WIB',
        'Jumat' => '08.00 - 17.00 WIB',
        'Sabtu - Minggu' => 'Tutup',
    ]);
}

date_default_timezone_set(APP_TIMEZONE);

function detect_base_url(): string
{
    $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $projectRoot = realpath(dirname(__DIR__));

    if ($documentRoot === false || $projectRoot === false) {
        return '/';
    }

    $documentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');
    $projectRoot = str_replace('\\', '/', $projectRoot);

    if (!str_starts_with($projectRoot, $documentRoot)) {
        return '/';
    }

    $relativePath = trim(substr($projectRoot, strlen($documentRoot)), '/');

    return $relativePath === '' ? '/' : '/' . $relativePath . '/';
}

function url(string $path = ''): string
{
    return BASE_URL . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function has_role(string $role): bool
{
    return (current_user()['role'] ?? null) === $role;
}

function normalize_page_path(string $path): string
{
    $path = (string) preg_replace('#/index\.php$#', '', $path);

    return rtrim($path, '/');
}

function is_current_page(string $path): bool
{
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    $targetPath = parse_url(url($path), PHP_URL_PATH) ?: '';

    return normalize_page_path($requestPath) === normalize_page_path($targetPath);
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return $flash;
}

function format_tanggal(?string $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    $timestamp = strtotime($value);

    return $timestamp === false ? '—' : date('d/m/Y', $timestamp);
}

function katalog_url(): string
{
    return has_role('peminjam') ? url('peminjaman/index.php') : url('alat/index.php');
}

function inisial_nama(string $nama): string
{
    $kata = preg_split('/\s+/', trim($nama)) ?: [];
    $inisial = '';

    foreach (array_slice($kata, 0, 2) as $bagian) {
        $inisial .= mb_strtoupper(mb_substr($bagian, 0, 1));
    }

    return $inisial !== '' ? $inisial : '?';
}
