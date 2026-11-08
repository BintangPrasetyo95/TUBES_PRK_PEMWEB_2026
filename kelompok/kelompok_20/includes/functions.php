<?php
// includes/functions.php
// Kumpulan fungsi pembantu (helper) bersama untuk kelompok 20

// Mulai sesi jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Membersihkan input/output string untuk mencegah celah XSS
 */
function sanitize($data) {
    if ($data === null) {
        return '';
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Memeriksa apakah pengguna sudah login
 */
function isLogin() {
    return isset($_SESSION['user_id']);
}

/**
 * Memeriksa apakah pengguna yang login memiliki peran admin
 */
function isAdmin() {
    return isLogin() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Membatasi akses hanya untuk pengguna yang sudah login
 */
function wajibLogin() {
    if (!isLogin()) {
        setFlash('danger', 'Silakan masuk terlebih dahulu untuk mengakses halaman ini.');
        header('Location: /login.php');
        exit;
    }
}

/**
 * Membatasi akses khusus untuk admin
 */
function wajibAdmin() {
    if (!isAdmin()) {
        setFlash('danger', 'Anda tidak memiliki hak akses ke halaman administrator.');
        header('Location: /index.php');
        exit;
    }
}

/**
 * Menyimpan pesan flash sementara ke sesi (tipe: success / danger)
 */
function setFlash($tipe, $pesan) {
    $_SESSION['flash'] = [
        'tipe' => $tipe,
        'pesan' => $pesan
    ];
}

/**
 * Mengambil dan menghapus pesan flash dari sesi
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Menghasilkan elemen lencana (badge) status penanganan laporan
 */
function badgeStatus($status) {
    $statusLower = strtolower(trim((string)$status));
    $label = ucfirst($statusLower);
    $badgeClass = 'badge-menunggu';

    if ($statusLower === 'diproses') {
        $badgeClass = 'badge-diproses';
    } elseif ($statusLower === 'selesai') {
        $badgeClass = 'badge-selesai';
    }

    return '<span class="badge ' . $badgeClass . '">' . sanitize($label) . '</span>';
}

/**
 * Membuat token CSRF acak dan menyimpannya di sesi
 */
function buatCsrf() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/**
 * Menghasilkan input tersembunyi (hidden input) token CSRF untuk formulir POST
 */
function csrfField() {
    $token = buatCsrf();
    return '<input type="hidden" name="csrf" value="' . $token . '">';
}

/**
 * Memvalidasi token CSRF dari formulir POST
 */
function verifikasiCsrf() {
    if (!isset($_POST['csrf']) || !isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        setFlash('danger', 'Validasi keamanan formulir (CSRF) gagal. Silakan coba kembali.');
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/index.php'));
        exit;
    }
}

