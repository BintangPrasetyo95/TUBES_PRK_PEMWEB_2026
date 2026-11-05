<?php
// Fondasi REST API CEKHAK.

function api_method(): string
{
    return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
}

/** Balasan sukses lalu berhenti. $status: 200 (bawaan) atau 201 untuk data baru. */
function api_ok($data = null, string $message = 'OK', int $status = 200): never
{
    http_response_code($status);
    echo json_encode(
        ['status' => 'success', 'message' => $message, 'data' => $data],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

/** Balasan gagal lalu berhenti. $errors: pesan per kolom, contoh ['judul' => 'Wajib diisi.'] */
function api_error(string $message, int $status = 400, array $errors = []): never
{
    http_response_code($status);
    $isi = ['status' => 'error', 'message' => $message];
    if ($errors) {
        $isi['errors'] = $errors;
    }
    echo json_encode($isi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

/** Membaca body JSON (POST/PUT). Body kosong menghasilkan array kosong, JSON rusak menghasilkan 400. */
function json_input(): array
{
    $mentah = file_get_contents('php://input');
    if ($mentah === false || trim($mentah) === '') {
        return [];
    }
    $data = json_decode($mentah, true);
    if (!is_array($data)) {
        api_error('Body permintaan harus berupa JSON yang valid.', 400);
    }
    return $data;
}

/** Parameter ?id=... sebagai bilangan bulat positif. null bila tidak dikirim, 400 bila tidak valid. */
function api_id(): ?int
{
    if (!isset($_GET['id']) || $_GET['id'] === '') {
        return null;
    }
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id === false || $id < 1) {
        api_error('Parameter id tidak valid.', 400);
    }
    return $id;
}

/** Header CORS. Bawaan: hanya satu asal (same-origin), karena aplikasi dilayani dari server yang sama. */
function api_cors(): void
{
    $asal = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    $diizinkan = (array) config('cors_origins', []);
    if ($asal !== '' && in_array($asal, $diizinkan, true)) {
        header('Access-Control-Allow-Origin: ' . $asal);
        header('Access-Control-Allow-Credentials: true');
        header('Vary: Origin');
    }
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, X-Requested-With');
    if (api_method() === 'OPTIONS') {
        http_response_code(200); // jawaban pre-flight
        exit;
    }
}

/** Persiapan endpoint: JSON, penangan error, CORS, dan pengecekan metode (405 bila tidak didukung). */
function api_boot(array $metode_diizinkan): void
{
    define('API_MODE', true); // db() membalas JSON bila koneksi gagal
    ini_set('display_errors', '0'); // error HTML akan merusak JSON ("Unexpected token <")
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');

    set_exception_handler(function (Throwable $e) {
        $pesan = config('dev_mode', false)
            ? 'Kesalahan server: ' . $e->getMessage()
            : 'Terjadi kesalahan pada server.';
        api_error($pesan, 500);
    });

    api_cors();

    if (!in_array(api_method(), $metode_diizinkan, true)) {
        header('Allow: ' . implode(', ', $metode_diizinkan));
        api_error('Metode ' . api_method() . ' tidak didukung oleh endpoint ini.', 405);
    }
}

/** Wajib login. Mengembalikan data pengguna. 401 bila belum login. */
function api_require_login(): array
{
    $user = current_user();
    if ($user === null) {
        api_error('Sesi berakhir. Silakan login kembali.', 401);
    }
    return $user;
}

/** Wajib login dan peran sesuai. 403 bila peran tidak diizinkan. */
function api_require_role(array $roles): array
{
    $user = api_require_login();
    if (!in_array($user['role'], $roles, true)) {
        api_error('Peran kamu tidak diizinkan mengakses endpoint ini.', 403);
    }
    return $user;
}

/** CSRF untuk permintaan yang mengubah data (selain GET). Token dikirim di header X-CSRF-Token oleh CEKHAK.api. */
function api_csrf_check(): void
{
    if (in_array(api_method(), ['GET', 'HEAD'], true)) {
        return;
    }
    $kirim = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($kirim) || !hash_equals(csrf_token(), $kirim)) {
        api_error('Token keamanan tidak valid. Muat ulang halaman lalu coba lagi.', 419);
    }
}

/** Satu baris pembuka endpoint: boot + peran + CSRF. Mengembalikan data pengguna yang login. */
function api_guard(array $roles, array $metode_diizinkan): array
{
    api_boot($metode_diizinkan);
    $user = api_require_role($roles);
    api_csrf_check();
    return $user;
}
