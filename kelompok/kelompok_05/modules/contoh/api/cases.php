<?php
// ============================================================================
// ENDPOINT CONTOH: pola standar REST API CEKHAK. Tiru pola ini untuk endpoint kamu.
// SEMENTARA: folder modules/contoh/ dihapus sebelum PR final ke LabKom.
//
//   GET    cases.php                -> daftar milik pengguna (?search=kata untuk mencari judul)
//   GET    cases.php?id=3           -> satu data
//   POST   cases.php                -> buat baru        body JSON: {"judul":"...","document_type_id":1}
//   PUT    cases.php?id=3           -> ubah             body JSON: {"judul":"...","document_type_id":1}
//   DELETE cases.php?id=3           -> hapus
// Kode status: 200 OK, 201 Created, 400 data tidak valid, 401/403 akses, 404 tidak ada,
//              405 metode tidak didukung, 419 token CSRF salah, 500 kesalahan server.
// ============================================================================

// 1) Muat bootstrap, lalu SATU baris pembuka: metode yang diizinkan, login, peran, CSRF.
require_once __DIR__ . '/../../../includes/bootstrap.php';
$user = api_guard(['pengguna'], ['GET', 'POST', 'PUT', 'DELETE']);

$metode = api_method();
$id = api_id(); // null bila tidak ada ?id=

/** Satu pemeriksaan milik pengguna (atau null). Selalu filter user_id agar tidak melihat milik orang lain. */
function ambil_case(int $id, int $user_id): ?array
{
    $st = db()->prepare(
        'SELECT c.id, c.judul, c.status, c.document_type_id, t.nama AS jenis, c.created_at
         FROM cases c JOIN document_types t ON t.id = c.document_type_id
         WHERE c.id = ? AND c.user_id = ?'
    );
    $st->execute([$id, $user_id]);
    $row = $st->fetch();
    return $row ?: null;
}

/** Memeriksa input judul dan jenis dokumen. Mengembalikan array galat per kolom (kosong bila valid). */
function validasi_case(array $input): array
{
    $galat = [];
    $judul = trim((string) ($input['judul'] ?? ''));
    if ($judul === '') {
        $galat['judul'] = 'Judul wajib diisi.';
    } elseif (mb_strlen($judul) > 200) {
        $galat['judul'] = 'Judul maksimal 200 karakter.';
    }
    $jenis = filter_var($input['document_type_id'] ?? null, FILTER_VALIDATE_INT);
    $st = db()->prepare('SELECT COUNT(*) FROM document_types WHERE id = ? AND aktif = 1');
    $st->execute([(int) $jenis]);
    if ($jenis === false || (int) $st->fetchColumn() === 0) {
        $galat['document_type_id'] = 'Pilih jenis dokumen yang valid.';
    }
    return $galat;
}

switch ($metode) {
    case 'GET':
        if ($id !== null) {
            $row = ambil_case($id, (int) $user['id']);
            if ($row === null) {
                api_error('Data tidak ditemukan.', 404);
            }
            api_ok($row);
        }
        $sql = 'SELECT c.id, c.judul, c.status, c.document_type_id, t.nama AS jenis, c.created_at
                FROM cases c JOIN document_types t ON t.id = c.document_type_id
                WHERE c.user_id = ?';
        $param = [(int) $user['id']];
        $cari = trim((string) ($_GET['search'] ?? ''));
        if ($cari !== '') {
            $sql .= ' AND c.judul LIKE ?';
            $param[] = '%' . addcslashes($cari, '%_\\') . '%';
        }
        $sql .= ' ORDER BY c.id DESC';
        $st = db()->prepare($sql);
        $st->execute($param);
        api_ok($st->fetchAll(), 'Data berhasil dimuat.');

    case 'POST':
        $input = json_input();
        $galat = validasi_case($input);
        if ($galat) {
            api_error('Data tidak valid.', 400, $galat);
        }
        $st = db()->prepare('INSERT INTO cases (user_id, document_type_id, judul, status) VALUES (?, ?, ?, ?)');
        $st->execute([(int) $user['id'], (int) $input['document_type_id'], trim((string) $input['judul']), 'draft']);
        $baru = (int) db()->lastInsertId();
        audit_log('buat_pemeriksaan', 'cases', $baru, 'API contoh');
        api_ok(ambil_case($baru, (int) $user['id']), 'Pemeriksaan contoh berhasil dibuat.', 201);

    case 'PUT':
        if ($id === null) {
            api_error('Parameter id wajib untuk mengubah data.', 400);
        }
        if (ambil_case($id, (int) $user['id']) === null) {
            api_error('Data tidak ditemukan.', 404);
        }
        $input = json_input();
        $galat = validasi_case($input);
        if ($galat) {
            api_error('Data tidak valid.', 400, $galat);
        }
        $st = db()->prepare('UPDATE cases SET judul = ?, document_type_id = ? WHERE id = ? AND user_id = ?');
        $st->execute([trim((string) $input['judul']), (int) $input['document_type_id'], $id, (int) $user['id']]);
        audit_log('ubah_pemeriksaan', 'cases', $id, 'API contoh');
        api_ok(ambil_case($id, (int) $user['id']), 'Pemeriksaan contoh berhasil diperbarui.');

    case 'DELETE':
        if ($id === null) {
            api_error('Parameter id wajib untuk menghapus data.', 400);
        }
        if (ambil_case($id, (int) $user['id']) === null) {
            api_error('Data tidak ditemukan.', 404);
        }
        $st = db()->prepare('DELETE FROM cases WHERE id = ? AND user_id = ?');
        $st->execute([$id, (int) $user['id']]);
        audit_log('hapus_pemeriksaan', 'cases', $id, 'API contoh');
        api_ok(null, 'Pemeriksaan contoh berhasil dihapus.');
}
