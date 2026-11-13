
<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}

csrf_check();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(400);
    exit('ID jenis dokumen tidak valid.');
}

try {
    $stmt = db()->prepare(
        'DELETE FROM document_types WHERE id = ?'
    );
    $stmt->execute([$id]);

    redirect('modules/admin/index.php');
} catch (PDOException $e) {
    http_response_code(409);
    exit('Jenis dokumen tidak dapat dihapus karena masih digunakan oleh data lain.');
}
