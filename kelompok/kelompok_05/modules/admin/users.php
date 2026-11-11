
<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

require_role(['admin']);

$stmt = db()->prepare(
    'SELECT id, nama, email, role, created_at
     FROM users
     ORDER BY id ASC'
);
$stmt->execute();
$users = $stmt->fetchAll();

$page_title = 'Kelola Akun';
require __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h1>Kelola Akun Pengguna</h1>
    <p>Halaman untuk melihat dan mengelola akun pengguna CEKHAK.</p>
</div>

<div class="card">
    <h2>Daftar Akun</h2>

    <?php if (empty($users)): ?>
        <p>Belum ada akun pengguna.</p>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Tanggal Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $i => $user): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($user['nama']) ?></td>
                            <td><?= e($user['email']) ?></td>
                            <td><?= e($user['role']) ?></td>
                            <td><?= e($user['created_at']) ?></td>
                            <td>
                                <a href="<?= e(base_url('modules/admin/user_edit.php?id=' . (int) $user['id'])) ?>">
                                    Ubah Role
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
