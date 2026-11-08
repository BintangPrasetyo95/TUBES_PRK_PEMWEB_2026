<?php

declare(strict_types=1);

/** Start a hardened session once, before sending any response body. */
function startAppSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

/** Return the authenticated identity, or null when no valid identity is stored. */
function currentUser(): ?array
{
    if (
        !isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role'])
        || !is_int($_SESSION['user_id'])
        || !is_string($_SESSION['username'])
        || !in_array($_SESSION['role'], ['admin', 'guru'], true)
    ) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role'],
    ];
}

function isLoggedIn(): bool
{
    return currentUser() !== null;
}

/** Build a URL relative to this project, including when hosted in a subfolder. */
function appUrl(string $path): string
{
    $scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $lastDirectory = basename($scriptDirectory);
    if (in_array($lastDirectory, ['admin', 'guru'], true)) {
        $scriptDirectory = dirname($scriptDirectory);
    }

    $base = rtrim($scriptDirectory, '/');
    return $base . '/' . ltrim($path, '/');
}

/** Require an authenticated user; redirect guests to the login form. */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . appUrl('login.php'));
        exit;
    }
}

/** Require one role; authenticated users with another role receive HTTP 403. */
function requireRole(string $role): void
{
    if (!in_array($role, ['admin', 'guru'], true)) {
        throw new InvalidArgumentException('Role yang diminta tidak valid.');
    }

    requireLogin();
    if (currentUser()['role'] !== $role) {
        http_response_code(403);
        exit('Akses ditolak.');
    }
}

/** Send the user to the dashboard assigned to their authenticated role. */
function redirectToDashboard(): void
{
    requireLogin();
    $role = currentUser()['role'];
    header('Location: ' . appUrl($role . '/dashboard.php'));
    exit;
}
