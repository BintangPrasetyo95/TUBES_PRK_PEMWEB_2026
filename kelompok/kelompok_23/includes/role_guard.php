<?php
function requireRole(array $allowedRoles): void
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: /TUBES_PRK_PEMWEB_2026/kelompok/kelompok_23/auth/login.php");
        exit;
    }

    $userRole = strtolower(trim($_SESSION['role'] ?? ''));

    $allowedRoles = array_map('strtolower', $allowedRoles);

    if (!in_array($userRole, $allowedRoles, true)) {
        header("Location: /TUBES_PRK_PEMWEB_2026/kelompok/kelompok_23/dashboard.php");
        exit;
    }
}