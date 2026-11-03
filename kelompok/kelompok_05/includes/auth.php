<?php
// Sesi login dan pembatasan akses. Hanya PIC yang boleh mengubah file ini.
// Modul auth (Anggota D) memakai fungsi di sini; jangan menulis ulang logikanya.

/** Data pengguna yang sedang login (id, nama, email, role) atau null bila belum login. */
function current_user(): ?array
{
    static $cache = null;
    $id = (int) ($_SESSION['user_id'] ?? 0);
    if ($id === 0) {
        return null;
    }
    if ($cache === null || (int) $cache['id'] !== $id) {
        $st = db()->prepare('SELECT id, nama, email, role FROM users WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row) {
            unset($_SESSION['user_id']);
            return null;
        }
        $cache = $row;
    }
    return $cache;
}

/** Halaman login yang dipakai. Memakai halaman D bila sudah ada, kalau belum login pengembangan. */
function login_url(): string
{
    return is_file(APP_ROOT . '/modules/auth/login.php')
        ? 'modules/auth/login.php'
        : 'modules/contoh/dev_login.php';
}

function logout_url(): string
{
    return is_file(APP_ROOT . '/modules/auth/logout.php')
        ? 'modules/auth/logout.php'
        : 'modules/contoh/dev_logout.php';
}

/** Wajib login. Taruh di baris awal halaman yang butuh login. */
function require_login(): void
{
    if (current_user() === null) {
        flash('error', 'Silakan login terlebih dahulu.');
        redirect(login_url());
    }
}

/**
 * Wajib login DAN peran sesuai. Pengecekan di sisi server, bukan hanya menyembunyikan menu.
 *   require_role(['admin']);
 *   require_role(['reviewer', 'admin']);
 */
function require_role(array $roles): void
{
    require_login();
    $user = current_user();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        $page_title = 'Akses ditolak';
        require __DIR__ . '/header.php';
        echo '<div class="card"><h1>Akses ditolak</h1><p>Peran kamu tidak diizinkan membuka halaman ini.</p></div>';
        require __DIR__ . '/footer.php';
        exit;
    }
}

/** Dipanggil modul auth setelah password_verify() berhasil. $user minimal berisi 'id'. */
function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
}

function logout_user(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}
