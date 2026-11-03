<?php
// Fungsi bantu umum. Hanya PIC yang boleh mengubah file ini.

/** Escape output HTML. WAJIB dipakai untuk SEMUA data yang ditampilkan: <?= e($nilai) ?> */
function e($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/** Alamat dasar aplikasi, terdeteksi otomatis. base_url('modules/auth/login.php') */
function base_url(string $path = ''): string
{
    static $base = null;
    if ($base === null) {
        $cfg = (string) config('base_url', '');
        if ($cfg !== '') {
            $base = rtrim($cfg, '/');
        } else {
            $root = str_replace('\\', '/', (string) realpath(APP_ROOT));
            $doc = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
            $base = ($doc !== '' && stripos($root, $doc) === 0) ? substr($root, strlen($doc)) : '';
        }
    }
    return $base . '/' . ltrim($path, '/');
}

/** Pindah halaman. Path selalu relatif terhadap folder kelompok_05: redirect('index.php') */
function redirect(string $path): never
{
    header('Location: ' . base_url($path));
    exit;
}

/**
 * Pesan satu kali tampil.
 *   flash('success', 'Data tersimpan.');   // menyimpan
 * Tampil otomatis di header.php. Kunci yang dipakai: success, error, info.
 */
function flash(string $key, ?string $msg = null): ?string
{
    if ($msg !== null) {
        $_SESSION['flash'][$key] = $msg;
        return null;
    }
    $val = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $val;
}

/** Mengambil dan menghapus semua pesan flash (dipakai header.php). */
function flash_all(): array
{
    $all = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $all;
}

/** Teks status yang enak dibaca: belum_lengkap -> Belum lengkap */
function status_label(string $status): string
{
    return ucfirst(str_replace('_', ' ', $status));
}

/** Lencana berwarna untuk status pemeriksaan maupun status per persyaratan. */
function status_badge(string $status): string
{
    $map = [
        'lengkap' => 'ok',
        'selesai' => 'ok',
        'terverifikasi' => 'ok',
        'perlu_diperiksa' => 'warn',
        'siap_ditinjau' => 'warn',
        'belum_lengkap' => 'bad',
        'sedang_ditinjau' => 'info',
        'draft' => 'muted',
        'belum_diverifikasi' => 'muted',
    ];
    $kelas = $map[$status] ?? 'muted';
    return '<span class="badge badge-' . $kelas . '">' . e(status_label($status)) . '</span>';
}

/** Apakah halaman yang sedang dibuka adalah $path (relatif terhadap kelompok_05). */
function is_current_page(string $path): bool
{
    return str_ends_with((string) ($_SERVER['SCRIPT_NAME'] ?? ''), '/' . $path);
}

/**
 * Tautan yang HANYA tampil kalau filenya sudah ada.
 * Menu tidak akan menuju halaman 404 selama fitur anggota belum dibuat.
 */
function link_if_exists(string $path, string $label, string $class = ''): string
{
    if (!is_file(APP_ROOT . '/' . $path)) {
        return '';
    }
    $cls = trim($class . (is_current_page($path) ? ' aktif' : ''));
    return '<a' . ($cls !== '' ? ' class="' . e($cls) . '"' : '')
        . ' href="' . e(base_url($path)) . '">' . e($label) . '</a>';
}
