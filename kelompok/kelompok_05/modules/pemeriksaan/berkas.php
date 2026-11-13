<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/_lib.php';
require_login();
$user = current_user();
$pdo = db();

function berkas_gagal(string $pesan, int $caseId, int $status = 400): never
{
    http_response_code($status);
    flash('error', $pesan);
    redirect($caseId > 0 ? 'modules/pemeriksaan/form.php?id=' . $caseId : 'modules/pemeriksaan/buat.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caseHint = (int) filter_input(INPUT_GET, 'case_id', FILTER_VALIDATE_INT);

    if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        require_role(['pengguna']);
        berkas_gagal('Ukuran kiriman terlalu besar. Berkas maksimal 5 MB.', $caseHint, 413);
    }

    csrf_check();
    require_role(['pengguna']);
    $caseId = (int) filter_var($_POST['case_id'] ?? null, FILTER_VALIDATE_INT);
    $requirementId = (int) filter_var($_POST['requirement_id'] ?? null, FILTER_VALIDATE_INT);
    $aksi = (string) ($_POST['aksi'] ?? 'unggah');

    if ($aksi === 'hapus') {
        $fileId = (int) filter_var($_POST['file_id'] ?? null, FILTER_VALIDATE_INT);
        $st = $pdo->prepare("SELECT f.id, f.case_id, f.nama_asli, f.nama_tersimpan FROM case_files f JOIN cases c ON c.id = f.case_id WHERE f.id = ? AND c.user_id = ? AND c.status IN ('draft','belum_lengkap')");
        $st->execute([$fileId, $user['id']]);
        $f = $st->fetch();
        if (!$f) {
            berkas_gagal('Berkas tidak ditemukan atau pemeriksaan sudah dikirim.', $caseId, 404);
        }
        $pdo->prepare('DELETE FROM case_files WHERE id = ?')->execute([$f['id']]);
        berkas_hapus_fisik((string) $f['nama_tersimpan']);
        audit_log('hapus_berkas', 'case_files', (int) $f['id'], mb_substr((string) $f['nama_asli'], 0, 100));
        flash('success', 'Berkas dihapus.');
        redirect('modules/pemeriksaan/form.php?id=' . (int) $f['case_id']);
    }

    $upload = $_FILES['berkas'] ?? null;
    if (!$caseId || !$requirementId || !is_array($upload) || !isset($upload['error'], $upload['size'], $upload['tmp_name'], $upload['name']) || is_array($upload['error'])) {
        berkas_gagal('Data unggahan tidak valid.', $caseId);
    }
    $st = $pdo->prepare("SELECT c.id FROM cases c JOIN requirements r ON r.document_type_id = c.document_type_id WHERE c.id = ? AND c.user_id = ? AND c.status IN ('draft','belum_lengkap') AND r.id = ? AND r.tipe_input = 'file'");
    $st->execute([$caseId, $user['id'], $requirementId]);
    if (!$st->fetch()) {
        berkas_gagal('Pemeriksaan atau persyaratan tidak ditemukan, atau pemeriksaan sudah dikirim.', $caseId, 404);
    }
    if ($upload['error'] !== UPLOAD_ERR_OK) {
        berkas_gagal(berkas_pesan_galat((int) $upload['error']), $caseId);
    }
    if ($upload['size'] > BERKAS_MAKS_BYTE || !is_uploaded_file($upload['tmp_name']) || filesize($upload['tmp_name']) > BERKAS_MAKS_BYTE) {
        berkas_gagal('Ukuran berkas melebihi 5 MB.', $caseId);
    }
    if ($upload['size'] < 1) {
        berkas_gagal('Berkas kosong tidak dapat diunggah.', $caseId);
    }
    $nama = preg_replace('/[\x00-\x1F\x7F]/u', '', basename((string) $upload['name']));
    $mime = berkas_deteksi_mime($upload['tmp_name'], (string) $nama);
    if ($mime === null) {
        berkas_gagal('Format berkas harus PDF, gambar JPG/PNG/WebP, atau dokumen Word (isi berkas harus sesuai jenisnya).', $caseId);
    }
    $directory = APP_ROOT . '/uploads';
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        berkas_gagal('Folder penyimpanan berkas tidak dapat dibuat.', $caseId, 500);
    }
    $tersimpan = bin2hex(random_bytes(32)) . '.' . berkas_jenis_diizinkan()[$mime];
    if (!move_uploaded_file($upload['tmp_name'], $directory . '/' . $tersimpan)) {
        berkas_gagal('Berkas tidak dapat disimpan.', $caseId, 500);
    }

    $lama = $pdo->prepare('SELECT id, nama_tersimpan FROM case_files WHERE case_id = ? AND requirement_id = ?');
    $lama->execute([$caseId, $requirementId]);
    $lama = $lama->fetchAll();
    $st = $pdo->prepare('INSERT INTO case_files (case_id, requirement_id, nama_asli, nama_tersimpan, mime, ukuran) VALUES (?, ?, ?, ?, ?, ?)');
    $st->execute([$caseId, $requirementId, mb_substr((string) $nama, 0, 255), $tersimpan, $mime, (int) $upload['size']]);
    $baruId = (int) $pdo->lastInsertId();
    foreach ($lama as $l) {
        $pdo->prepare('DELETE FROM case_files WHERE id = ?')->execute([$l['id']]);
        berkas_hapus_fisik((string) $l['nama_tersimpan']);
    }
    audit_log($lama ? 'ganti_berkas' : 'unggah_berkas', 'case_files', $baruId, mb_substr((string) $nama, 0, 100) . ' (' . ukuran_label((int) $upload['size']) . ')');

    $catatan = '';
    if (is_array($_POST['field'] ?? null)) {
        $jenis = $pdo->prepare('SELECT document_type_id FROM cases WHERE id = ?');
        $jenis->execute([$caseId]);
        $reqs = ambil_persyaratan($pdo, (int) $jenis->fetchColumn());
        $hasil = periksa_isian($reqs, $_POST['field']);
        simpan_isian($pdo, $caseId, $hasil['valid']);
        $catatan = $hasil['errors'] ? ' Beberapa isian belum valid dan belum tersimpan.' : ' Isian form ikut tersimpan.';
    }
    flash('success', 'Berkas berhasil diunggah.' . $catatan);
    redirect('modules/pemeriksaan/form.php?id=' . $caseId . '#persyaratan-' . $requirementId);
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
$pemilik = (int) $file['user_id'] === (int) $user['id'];
if (!$pemilik && !in_array($user['role'], ['reviewer', 'admin'], true)) {
    http_response_code(403);
    exit('Kamu tidak diizinkan mengakses berkas ini.');
}
$stored = (string) $file['nama_tersimpan'];
if (!preg_match('/\A[a-f0-9]{64}\.(pdf|jpg|png|webp|doc|docx)\z/', $stored)) {
    http_response_code(404);
    exit('Lokasi berkas tidak valid.');
}
if (!isset(berkas_jenis_diizinkan()[$file['mime']])) {
    http_response_code(404);
    exit('Jenis berkas tidak valid.');
}
$path = APP_ROOT . '/uploads/' . $stored;
if (!is_file($path) || !is_readable($path)) {
    http_response_code(404);
    exit('Berkas tidak tersedia.');
}

$bisaInline = in_array($file['mime'], ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true);
$inline = $bisaInline && (($_GET['lihat'] ?? '') === '1');
$ascii = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $file['nama_asli']);
$utf8 = rawurlencode((string) $file['nama_asli']);
if (!$pemilik) {
    audit_log('akses_berkas', 'case_files', (int) $file['id'], 'Oleh ' . $user['role']);
}
header('Content-Type: ' . $file['mime']);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $ascii . '"; filename*=UTF-8\'\'' . $utf8);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($path);
