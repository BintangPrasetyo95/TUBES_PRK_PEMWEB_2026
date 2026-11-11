<?php
require_once __DIR__ . '/../../includes/auth_admin.php';

$daftarStatus = ['menunggu' => 'Menunggu', 'ditolak' => 'Ditolak', 'publikasi' => 'Publikasi'];

header('Location: ' . admin_url('/admin/aspirasi/index.php'));
exit;