
<?php
// Logout CEKHAK dengan perlindungan CSRF.
require_once __DIR__ . '/../../includes/bootstrap.php';

// Pengguna harus sudah login.
require_login();

// Logout hanya dilakukan melalui POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $user = current_user();

    audit_log(
        'logout',
        'users',
        (int) $user['id'],
        'Pengguna berhasil logout'
    );

    logout_user();

    flash('success', 'Kamu sudah berhasil keluar.');

    redirect('index.php');
}

$page_title = 'Konfirmasi Keluar';
require __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h1>Keluar dari CEKHAK?</h1>

    <p>Apakah kamu yakin ingin mengakhiri sesi login?</p>

    <form method="post">
        <?= csrf_field() ?>

        <button type="submit" class="btn">
            Ya, Keluar
        </button>

        <a href="<?= e(base_url('index.php')) ?>">
            Batal
        </a>
    </form>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
