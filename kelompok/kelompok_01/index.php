<?php
session_start();

if (isset($_SESSION['user'])) {
    $role = $_SESSION['user']['role'] ?? '';

    if ($role === 'admin') {
        header('Location: pages/admin_dashboard.php');
    } elseif ($role === 'kepala_keluarga' || $role === 'anggota_keluarga') {
        header('Location: pages/dashboard.php');
    } else {
        header('Location: login.php');
    }
    exit;
}

header('Location: login.php');
exit;
