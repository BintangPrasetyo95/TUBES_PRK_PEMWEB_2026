
<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';

if (current_user() === null) {
    api_boot(['GET']);
} else {
    api_guard(['pengguna', 'reviewer', 'admin'], ['GET']);
}

$search = trim((string) ($_GET['search'] ?? ''));

if (mb_strlen($search) > 100) {
    api_error('Pencarian maksimal 100 karakter.', 400);
}

$sql = 'SELECT t.id AS type_id, t.nama AS jenis, t.deskripsi,
               r.kode, r.label, r.wajib
        FROM document_types t
        LEFT JOIN requirements r ON r.document_type_id = t.id
        WHERE t.aktif = 1';

$params = [];

if ($search !== '') {
    $sql .= ' AND (
        t.nama LIKE ? OR t.deskripsi LIKE ?
        OR r.kode LIKE ? OR r.label LIKE ?
    )';

    $term = '%' . addcslashes($search, '%_\\') . '%';
    $params = [$term, $term, $term, $term];
}

$sql .= ' ORDER BY t.nama, r.urutan, r.id';

$st = db()->prepare($sql);
$st->execute($params);

api_ok($st->fetchAll(), 'Pustaka berhasil dimuat.');
