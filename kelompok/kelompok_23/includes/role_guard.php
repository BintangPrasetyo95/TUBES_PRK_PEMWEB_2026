<?php
require_once __DIR__ . '/../config/setup_helpers.php';

function requireRole(array $allowedRoles): void
{
    $root = febmart_to_root();

    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . $root . 'auth/login.php');
        exit;
    }

    $userRole = strtolower(trim($_SESSION['role'] ?? ''));

    $allowedRoles = array_map('strtolower', $allowedRoles);

    if (!in_array($userRole, $allowedRoles, true)) {
        header('Location: ' . $root . 'dashboard.php');
        exit;
    }
}
