<?php
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Gunakan PUT pada api/aspirasi.php untuk mengubah status.']);
    exit;
}

csrf_verify();

http_response_code(410);
echo json_encode([
    'status' => 'error',
    'message' => 'Endpoint ini sudah tidak digunakan. Perbarui status melalui REST API.',
]);
