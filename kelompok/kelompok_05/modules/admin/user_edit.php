
<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

require_role(['admin']);

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    redirect('modules/admin/users.php');
}

// Ambil data akun.
$stmt = db()->prepare(
    'SELECT id, nama, email, role
     FROM users
     WHERE id = ?'
);
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    redirect('modules/admin/users.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $role = $_POST['role'] ?? '';

    $role_valid = ['pengguna', 'reviewer', 'admin'];

    if (!in_array($role, $role_valid, true)) {
        $error = 'Role yang dipilih tidak valid.';
    } elseif ($id === (int) ($_SESSION['user']['id'] ?? 0)) {
        $error = 'Kamu tidak dapat mengubah role akun sendiri.';
    } else {
        $stmt = db()->prepare(
            'UPDATE users SET role = ? WHERE id = ?'
        );
        $stmt->execute([$role, $id]);

        redirect('modules/admin/users.php');
    }
}

$page_title = 'Ubah Role Akun';
require __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h1>Ubah Role Akun</h1>

    <?php if ($error !== ''): ?>
        <p><?= e($error) ?></p>
    <?php endif; ?>

    <p><strong>Nama:</strong> <?= e($user['nama']) ?></p>
    <p><strong>Email:</strong> <?= e($user['email']) ?></p>
    <p><strong>Role saat ini:</strong> <?= e($user['role']) ?></p>

    <?php if ($id === (int) ($_SESSION['user']['id'] ?? 0)): ?>
        <p>Role akun yang sedang kamu gunakan tidak dapat diubah.</p>
    <?php else: ?>
        <form method="post">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="role">Pilih Role Baru</label>
                <select name="role" id="role" required>
                    <option value="pengguna"
                        <?= $user['role'] === 'pengguna' ? 'selected' : '' ?>>
                        Pengguna
                    </option>

                    <option value="reviewer"
                        <?= $user['role'] === 'reviewer' ? 'selected' : '' ?>>
                        Reviewer
                    </option>

                    <option value="admin"
                        <?= $user['role'] === 'admin' ? 'selected' : '' ?>>
                        Admin
                    </option>
                </select>
            </div>

            <button type="submit" class="btn">
                Simpan Perubahan
            </button>
        </form>
    <?php endif; ?>

    <a href="<?= e(base_url('modules/admin/users.php')) ?>">
        Kembali ke Daftar Akun
    </a>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
