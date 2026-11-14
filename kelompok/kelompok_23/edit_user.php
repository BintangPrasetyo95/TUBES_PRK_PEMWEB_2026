<?php
session_start();

require_once __DIR__ . '/includes/role_guard.php';
requireRole(['Admin']);

require_once __DIR__ . '/config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id']) || (int) $_GET['id'] <= 0) {
    header("Location: users.php");
    exit;
}

$id = (int) $_GET['id'];

$query = "SELECT id, name, username, email, role, photo
          FROM users
          WHERE id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();

if (!$user) {
    $conn->close();
    header("Location: users.php");
    exit;
}

$pageTitle = 'Edit Pengguna';
require_once __DIR__ . '/includes/header.php';
?>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="app-layout d-flex flex-grow-1">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content flex-grow-1 p-4">
        <div class="mb-4">
            <h3 class="fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Pengguna</h3>
            <p class="text-muted">Perbarui informasi akun pengguna FEBMart.</p>
        </div>

        <div class="card border-0 shadow-sm" style="max-width: 600px;">
            <div class="card-body p-4">
                <form action="proses_edit_user.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= $user['id']; ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Username</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="kasir" <?= $user['role'] === 'kasir' ? 'selected' : ''; ?>>Kasir</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Foto Profil (Opsional)</label><br>
                        <?php if (!empty($user['photo'])): ?>
                            <img src="assets/img/<?= htmlspecialchars($user['photo']); ?>" width="80" class="mb-2 rounded-circle">
                        <?php endif; ?>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                        <small class="text-muted">Biarkan kosong jika tidak ingin mengganti foto.</small>
                    </div>

                    <div class="d-flex justify-content-between pt-3 border-top">
                        <a href="index.php" class="btn btn-secondary">Kembali</a>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>