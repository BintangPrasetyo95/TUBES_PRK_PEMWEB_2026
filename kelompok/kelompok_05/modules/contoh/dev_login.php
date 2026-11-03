<?php
// SEMENTARA, hanya untuk pengembangan. Dihapus sebelum PR final ke LabKom (bersama folder modules/contoh/).
// Memilih akun seed untuk masuk tanpa kata sandi, supaya semua anggota bisa menguji per peran
// sebelum modul auth (Anggota D) selesai.
require_once __DIR__ . '/../../includes/bootstrap.php';

if (!config('dev_mode', false)) {
    http_response_code(404);
    exit('Halaman tidak tersedia.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['user_id'] ?? 0);
    $st = db()->prepare('SELECT id, nama, email, role FROM users WHERE id = ?');
    $st->execute([$id]);
    $user = $st->fetch();
    if ($user) {
        login_user($user);
        audit_log('login_dev', 'users', (int) $user['id'], 'Login pengembangan');
        flash('success', 'Masuk sebagai ' . $user['nama'] . ' (' . $user['role'] . ').');
        redirect('index.php');
    }
    flash('error', 'Pengguna tidak ditemukan.');
    redirect('modules/contoh/dev_login.php');
}

$daftar = db()->query('SELECT id, nama, email, role FROM users ORDER BY id')->fetchAll();

$page_title = 'Login Pengembangan';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <h1>Login pengembangan</h1>
    <p class="muted">Hanya aktif saat <code>dev_mode</code> bernilai true. Pilih akun seed untuk masuk.</p>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($daftar as $u): ?>
                <tr>
                    <td><?= e($u['nama']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e($u['role']) ?></td>
                    <td>
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="user_id" value="<?= e($u['id']) ?>">
                            <button type="submit" class="btn btn-small">Masuk</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
