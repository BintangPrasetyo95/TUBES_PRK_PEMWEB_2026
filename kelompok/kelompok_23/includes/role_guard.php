<?php
function requireRole(array $allowedRoles): void
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: /TUBES_PRK_PEMWEB_2026/kelompok/kelompok_23/auth/login.php");
        exit;
    }

    if (!in_array($_SESSION['role'] ?? '', $allowedRoles, true)) {
        header("Location: /TUBES_PRK_PEMWEB_2026/kelompok/kelompok_23/dashboard.php");
        exit;
    }
}