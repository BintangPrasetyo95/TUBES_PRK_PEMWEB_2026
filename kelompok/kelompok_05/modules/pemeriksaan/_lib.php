<?php

if (!defined('APP_ROOT')) {
    http_response_code(404);
    exit;
}

const BERKAS_MAKS_BYTE = 5 * 1024 * 1024;

function berkas_jenis_diizinkan(): array
{
    return [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];
}

function berkas_deteksi_mime(string $tmp, string $namaAsli): ?string
{
    $diizinkan = berkas_jenis_diizinkan();
    $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $ext = strtolower(pathinfo($namaAsli, PATHINFO_EXTENSION));

    if ($mime === 'application/zip' || $mime === 'application/octet-stream') {
        if ($ext === 'docx' && class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($tmp) === true) {
                $ada = $zip->locateName('word/document.xml') !== false;
                $zip->close();
                if ($ada) {
                    return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                }
            }
        }
        return null;
    }

    if (($mime === 'application/CDFV2' || $mime === 'application/x-ole-storage') && $ext === 'doc') {
        $mime = 'application/msword';
    }
    if (!isset($diizinkan[$mime])) {
        return null;
    }

    if ($mime === 'application/pdf') {
        $fh = fopen($tmp, 'rb');
        $kepala = $fh ? (string) fread($fh, 5) : '';
        if ($fh) {
            fclose($fh);
        }
        if ($kepala !== '%PDF-') {
            return null;
        }
    } elseif (str_starts_with($mime, 'image/') && @getimagesize($tmp) === false) {
        return null;
    }
    return $mime;
}

function berkas_pesan_galat(int $kode): string
{
    switch ($kode) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'Ukuran berkas melebihi batas (maksimal 5 MB).';
        case UPLOAD_ERR_PARTIAL:
            return 'Unggahan terputus. Coba lagi.';
        case UPLOAD_ERR_NO_FILE:
            return 'Pilih berkas terlebih dahulu.';
        default:
            return 'Unggahan gagal. Coba lagi.';
    }
}

function ukuran_label(int $byte): string
{
    if ($byte >= 1048576) {
        return number_format($byte / 1048576, 1, ',', '.') . ' MB';
    }
    if ($byte >= 1024) {
        return number_format($byte / 1024, 1, ',', '.') . ' KB';
    }
    return $byte . ' B';
}

function berkas_hapus_fisik(string $namaTersimpan): void
{
    if (preg_match('/\A[a-f0-9]{64}\.(pdf|jpg|png|webp|doc|docx)\z/', $namaTersimpan) !== 1) {
        return;
    }
    $path = APP_ROOT . '/uploads/' . $namaTersimpan;
    if (is_file($path)) {
        @unlink($path);
    }
}

function ambil_persyaratan(PDO $pdo, int $jenisId): array
{
    $st = $pdo->prepare('SELECT * FROM requirements WHERE document_type_id = ? ORDER BY urutan, id');
    $st->execute([$jenisId]);
    return $st->fetchAll();
}

function periksa_isian(array $requirements, array $fields): array
{
    $valid = [];
    $errors = [];
    foreach ($requirements as $req) {
        $rid = (int) $req['id'];
        $tipe = $req['tipe_input'];
        if ($tipe === 'file') {
            continue;
        }
        if ($tipe === 'checkbox') {
            $valid[$rid] = isset($fields[$rid]) ? '1' : '0';
            continue;
        }
        if (isset($fields[$rid]) && !is_scalar($fields[$rid])) {
            $errors[$rid] = 'Isian tidak valid.';
            continue;
        }
        $nilai = trim((string) ($fields[$rid] ?? ''));
        if (mb_strlen($nilai) > 5000) {
            $errors[$rid] = 'Isian terlalu panjang (maksimal 5000 karakter).';
            continue;
        }
        if ($nilai !== '') {
            $aturan = $req['aturan_format'] ?: ($tipe === 'number' ? 'angka' : ($tipe === 'date' ? 'tanggal' : ''));
            if ($aturan !== '' && !cek_format($aturan, $nilai)) {
                $pesan = [
                    'tanggal' => 'Tanggal harus berformat YYYY-MM-DD dan valid.',
                    'angka' => 'Harus berupa angka.',
                    'nomor' => 'Nomor tidak valid (8 sampai 15 digit).',
                    'email' => 'Format email tidak valid.',
                ];
                $errors[$rid] = $pesan[$aturan] ?? 'Format tidak valid.';
                continue;
            }
        }
        $valid[$rid] = $nilai;
    }
    return ['valid' => $valid, 'errors' => $errors];
}

function simpan_isian(PDO $pdo, int $caseId, array $valid): void
{
    $st = $pdo->prepare('INSERT INTO case_data (case_id, requirement_id, nilai) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)');
    foreach ($valid as $rid => $nilai) {
        $st->execute([$caseId, $rid, $nilai]);
    }
}

function ambil_berkas_per_persyaratan(PDO $pdo, int $caseId): array
{
    $st = $pdo->prepare('SELECT id, requirement_id, nama_asli, mime, ukuran, created_at FROM case_files WHERE case_id = ? ORDER BY id DESC');
    $st->execute([$caseId]);
    $peta = [];
    foreach ($st->fetchAll() as $b) {
        $peta[(int) $b['requirement_id']][] = $b;
    }
    return $peta;
}

function hitung_checklist(array $requirements, array $values, array $berkas): array
{
    $item = [];
    $wajibTotal = 0;
    $wajibTerpenuhi = 0;
    foreach ($requirements as $req) {
        $rid = (int) $req['id'];
        if ($req['tipe_input'] === 'file') {
            $isi = !empty($berkas[$rid]);
        } elseif ($req['tipe_input'] === 'checkbox') {
            $isi = ($values[$rid] ?? '0') === '1';
        } else {
            $isi = trim((string) ($values[$rid] ?? '')) !== '';
        }
        if ($req['wajib']) {
            $wajibTotal++;
            if ($isi) {
                $wajibTerpenuhi++;
            }
        }
        $item[$rid] = $isi ? 'terpenuhi' : ($req['wajib'] ? 'belum' : 'opsional');
    }
    $persen = $wajibTotal > 0 ? (int) floor($wajibTerpenuhi * 100 / $wajibTotal) : 100;
    return ['item' => $item, 'wajib_total' => $wajibTotal, 'wajib_terpenuhi' => $wajibTerpenuhi, 'persen' => $persen];
}

function jumlah_notifikasi_belum_dibaca(PDO $pdo, int $userId): int
{
    $st = $pdo->prepare('SELECT COUNT(*) FROM review_logs r JOIN cases c ON c.id = r.case_id WHERE c.user_id = ? AND r.dibaca_pengguna = 0');
    $st->execute([$userId]);
    return (int) $st->fetchColumn();
}
