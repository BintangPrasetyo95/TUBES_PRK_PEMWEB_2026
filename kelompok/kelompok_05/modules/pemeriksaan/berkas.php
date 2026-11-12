<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();
$user = current_user();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    require_role(['pengguna']);
    $caseId = filter_var($_POST['case_id'] ?? null, FILTER_VALIDATE_INT);
    $requirementId = filter_var($_POST['requirement_id'] ?? null, FILTER_VALIDATE_INT);
    $upload = $_FILES['berkas'] ?? null;
    if (!$caseId || !$requirementId || !is_array($upload) || !isset($upload['error'], $upload['size'], $upload['tmp_name'], $upload['name']) || is_array($upload['error'])) {
        http_response_code(400);
        exit('Data unggahan tidak valid.');
    }
    $st = $pdo->prepare("SELECT c.id FROM cases c JOIN requirements r ON r.document_type_id = c.document_type_id WHERE c.id = ? AND c.user_id = ? AND c.status IN ('draft','belum_lengkap') AND r.id = ? AND r.tipe_input = 'file'");
    $st->execute([$caseId, $user['id'], $requirementId]);
    if (!$st->fetch()) {
        http_response_code(404);
        exit('Pemeriksaan atau persyaratan tidak ditemukan.');
    }
    if ($upload['error'] !== UPLOAD_ERR_OK || $upload['size'] > 5 * 1024 * 1024 || !is_uploaded_file($upload['tmp_name']) || filesize($upload['tmp_name']) > 5 * 1024 * 1024) {
        http_response_code(400);
        exit('Unggahan gagal atau ukuran berkas melebihi 5 MB.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($upload['tmp_name']);
    $allowed = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
        'application/msword' => 'doc', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'];
    if (!isset($allowed[$mime])) {
        http_response_code(400);
        exit('Format berkas harus PDF, gambar JPG/PNG/WebP, atau dokumen Word.');
    }
    $directory = APP_ROOT . '/uploads';
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        http_response_code(500);
        exit('Folder penyimpanan berkas tidak dapat dibuat.');
    }
    $stored = bin2hex(random_bytes(32)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($upload['tmp_name'], $directory . '/' . $stored)) {
        http_response_code(500);
        exit('Berkas tidak dapat disimpan.');
    }
    $name = preg_replace('/[\x00-\x1F\x7F]/u', '', basename((string) $upload['name']));
    $st = $pdo->prepare('INSERT INTO case_files (case_id, requirement_id, nama_asli, nama_tersimpan, mime, ukuran) VALUES (?, ?, ?, ?, ?, ?)');
    $st->execute([$caseId, $requirementId, mb_substr($name, 0, 255), $stored, $mime, (int) $upload['size']]);
    flash('success', 'Berkas berhasil diunggah.');
    redirect('modules/pemeriksaan/form.php?id=' . $caseId);
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Metode permintaan tidak diizinkan.');
}

$fileId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$st = $pdo->prepare('SELECT f.*, c.user_id FROM case_files f JOIN cases c ON c.id = f.case_id WHERE f.id = ?');
$st->execute([$fileId]);
$file = $st->fetch();
if (!$file) {
    http_response_code(404);
    exit('Berkas tidak ditemukan.');
}
if ((int) $file['user_id'] !== (int) $user['id'] && !in_array($user['role'], ['reviewer', 'admin'], true)) {
    http_response_code(403);
    exit('Kamu tidak diizinkan mengakses berkas ini.');
}
$stored = (string) $file['nama_tersimpan'];
if (!preg_match('/\A[a-f0-9]{64}\.(pdf|jpg|png|webp|doc|docx)\z/', $stored)) {
    http_response_code(404);
    exit('Lokasi berkas tidak valid.');
}
$allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
if (!in_array($file['mime'], $allowedMimes, true)) {
    http_response_code(404);
    exit('Jenis berkas tidak valid.');
}
$path = APP_ROOT . '/uploads/' . $stored;
if (!is_file($path) || !is_readable($path)) {
    http_response_code(404);
    exit('Berkas tidak tersedia.');
}
$name = preg_replace('/[^A-Za-z0-9._ -]/', '_', (string) $file['nama_asli']);
header('Content-Type: ' . $file['mime']);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . addcslashes($name, '\\') . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
