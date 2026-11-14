<?php
session_start();

require_once __DIR__ . '/includes/role_guard.php';
requireRole(['Admin']);

$pageTitle = 'Tambah Pengguna';
require_once __DIR__ . '/includes/header.php';
?>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="app-layout d-flex flex-grow-1">

    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content flex-grow-1 p-4">

        <div class="mb-4">
            <h3 class="fw-bold">
                <i class="bi bi-person-plus me-2"></i>Tambah Pengguna
            </h3>
            <p class="text-muted">
                Tambahkan akun Admin atau Kasir baru ke dalam sistem FEBMart.
            </p>
        </div>

        <div class="card border-0 shadow-sm" style="max-width: 650px;">
            <div class="card-body p-4">

                <form action="proses_tambah_user.php" method="POST" enctype="multipart/form-data">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control"
                               placeholder="Masukkan nama lengkap" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Username</label>
                        <input type="text" name="username" class="form-control"
                               placeholder="Masukkan username" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" class="form-control"
                               placeholder="Masukkan email">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Password</label>
                        <input type="password" name="password" class="form-control"
                               placeholder="Masukkan password" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Role Pengguna</label>
                        <select name="role" class="form-select" required>
                            <option value="">-- Pilih Role --</option>
                            <option value="admin">Admin</option>
                            <option value="kasir">Kasir</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Foto Profil</label>
                        <input type="file" name="photo" class="form-control"
                               accept="image/jpeg,image/png,image/webp">
                        <small class="text-muted">
                            Format JPG/PNG/WebP, maks 2 MB. Opsional.
                        </small>
                    </div>

                    <div class="d-flex justify-content-between flex-wrap gap-2 pt-3 border-top">
                        <a href="index.php" class="btn btn-secondary">Kembali</a>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Simpan Pengguna
                        </button>
                    </div>

                </form>

            </div>
        </div>

    </main>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>