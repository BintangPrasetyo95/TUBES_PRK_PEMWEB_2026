<?php

function validasi_foto(array $foto): string
{
    if ($foto['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload foto gagal.');
    }

    if ($foto['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Ukuran foto maksimal 2 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($foto['tmp_name']);

    $daftarMime = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($daftarMime[$mime])) {
        throw new RuntimeException('Foto harus berformat JPG, PNG, atau WEBP.');
    }

    return bin2hex(random_bytes(16)) . '.' . $daftarMime[$mime];
}

function hapus_foto(string $namaFoto, string $folderUpload): void
{
    if ($namaFoto === '') {
        return;
    }

    $lokasiFoto = $folderUpload . basename($namaFoto);

    if (is_file($lokasiFoto)) {
        unlink($lokasiFoto);
    }
}

function get_alat_by_id(int $id): ?array
{
    $query = db()->prepare(
        'SELECT * FROM alat WHERE id = :id'
    );

    $query->execute(['id' => $id]);

    $data = $query->fetch();

    return $data ?: null;
}

function get_all_kategori(): array
{
    return db()->query(
        'SELECT id, nama FROM kategori ORDER BY nama ASC'
    )->fetchAll();
}

function get_all_alat(): array
{
    return db()->query(
        'SELECT alat.*, kategori.nama AS nama_kategori
         FROM alat
         INNER JOIN kategori ON alat.kategori_id = kategori.id
         ORDER BY alat.id DESC'
    )->fetchAll();
}

