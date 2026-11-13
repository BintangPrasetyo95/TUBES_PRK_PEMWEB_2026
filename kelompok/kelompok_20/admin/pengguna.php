<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

wajibAdmin();

$search = trim($_GET['search'] ?? '');
$filter_role = $_GET['role'] ?? 'semua';

$total_semua = 0;
$total_admin = 0;
$total_user = 0;

try {
    $stmt_ringkas = $pdo->query("SELECT role, COUNT(*) AS jumlah FROM users GROUP BY role");
    foreach ($stmt_ringkas->fetchAll(PDO::FETCH_ASSOC) as $baris) {
        $total_semua += (int)$baris['jumlah'];
        if ($baris['role'] === 'admin') {
            $total_admin = (int)$baris['jumlah'];
        } else {
            $total_user = (int)$baris['jumlah'];
        }
    }
} catch (PDOException $e) {
    error_log('Gagal mengambil ringkasan pengguna: ' . $e->getMessage());
}

// Ambil daftar pengguna beserta jumlah laporannya
$sql = "SELECT users.id, users.nama, users.email, users.role, users.created_at,
               COUNT(laporan.id) AS total_laporan
        FROM users
        LEFT JOIN laporan ON laporan.user_id = users.id
        WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (users.nama LIKE :cari_nama OR users.email LIKE :cari_email)";
    $params[':cari_nama'] = '%' . $search . '%';
    $params[':cari_email'] = '%' . $search . '%';
}

if ($filter_role === 'admin' || $filter_role === 'user') {
    $sql .= " AND users.role = :role";
    $params[':role'] = $filter_role;
}

$sql .= " GROUP BY users.id, users.nama, users.email, users.role, users.created_at
          ORDER BY users.created_at DESC, users.id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $daftar_pengguna = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Gagal mengambil daftar pengguna: ' . $e->getMessage());
    $daftar_pengguna = [];
}

$sedang_filter = ($search !== '' || $filter_role !== 'semua');

$pageTitle = "Daftar Pengguna";
include '../includes/header.php';
?>

<div style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
        <div>
            <h1 style="color: var(--color-primary); font-size: 1.75rem; margin-bottom: 0.35rem;">Daftar Pengguna</h1>
            <p style="color: var(--color-text-muted); font-size: 0.95rem; margin: 0;">Lihat akun yang terdaftar beserta jumlah laporan yang pernah dikirim.</p>
        </div>
        <a href="dashboard.php" class="btn btn-secondary" style="font-size: 0.9rem;">
            &larr; Kembali ke Dashboard
        </a>
    </div>

    <!-- Ringkasan -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div class="card" style="padding: 1.25rem; margin-bottom: 0;">
            <div style="font-size: 0.85rem; color: var(--color-text-muted);">Total Akun</div>
            <div style="font-size: 1.75rem; font-weight: 700; color: var(--color-primary);"><?= $total_semua ?></div>
        </div>
        <div class="card" style="padding: 1.25rem; margin-bottom: 0;">
            <div style="font-size: 0.85rem; color: var(--color-text-muted);">Pengguna (Pelapor)</div>
            <div style="font-size: 1.75rem; font-weight: 700; color: var(--color-primary);"><?= $total_user ?></div>
        </div>
        <div class="card" style="padding: 1.25rem; margin-bottom: 0;">
            <div style="font-size: 0.85rem; color: var(--color-text-muted);">Admin</div>
            <div style="font-size: 1.75rem; font-weight: 700; color: var(--color-primary);"><?= $total_admin ?></div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card" style="padding: 1.5rem; margin-bottom: 1.5rem;">
        <form method="GET" action="pengguna.php">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                <div>
                    <label for="search" class="form-label">Cari Pengguna</label>
                    <input type="text" id="search" name="search" class="form-control" value="<?= sanitize($search) ?>" placeholder="Nama atau email..." autocomplete="off">
                </div>
                <div>
                    <label for="role" class="form-label">Peran</label>
                    <select id="role" name="role" class="form-control">
                        <option value="semua" <?= ($filter_role === 'semua') ? 'selected' : '' ?>>Semua Peran</option>
                        <option value="user" <?= ($filter_role === 'user') ? 'selected' : '' ?>>Pengguna</option>
                        <option value="admin" <?= ($filter_role === 'admin') ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Terapkan Filter</button>
                    <a href="pengguna.php" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <div style="margin-bottom: 1rem; font-size: 0.95rem; color: var(--color-text-muted);">
        Menampilkan <strong><?= count($daftar_pengguna) ?></strong> akun.
    </div>

    <!-- Tabel -->
    <div class="card" style="padding: 1.75rem;">
        <div class="table-responsive" style="margin-bottom: 0;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="padding: 0.75rem 1rem; width: 50px;">No</th>
                        <th style="padding: 0.75rem 1rem;">Nama</th>
                        <th style="padding: 0.75rem 1rem;">Email</th>
                        <th style="padding: 0.75rem 1rem; text-align: center;">Peran</th>
                        <th style="padding: 0.75rem 1rem; text-align: center;">Laporan</th>
                        <th style="padding: 0.75rem 1rem;">Terdaftar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftar_pengguna)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 1.5rem; color: var(--color-text-muted);">
                                <?= $sedang_filter ? 'Tidak ada pengguna yang sesuai dengan filter.' : 'Belum ada pengguna terdaftar.' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftar_pengguna as $idx => $u): ?>
                            <tr>
                                <td style="padding: 0.85rem 1rem;"><?= $idx + 1 ?></td>
                                <td style="padding: 0.85rem 1rem; font-weight: 600; color: var(--color-primary);">
                                    <?= sanitize($u['nama']) ?>
                                </td>
                                <td style="padding: 0.85rem 1rem; overflow-wrap: anywhere;">
                                    <?= sanitize($u['email']) ?>
                                </td>
                                <td style="padding: 0.85rem 1rem; text-align: center;">
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="badge badge-diproses">Admin</span>
                                    <?php else: ?>
                                        <span class="badge badge-selesai">Pengguna</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 0.85rem 1rem; text-align: center;">
                                    <?= (int)$u['total_laporan'] ?>
                                </td>
                                <td style="padding: 0.85rem 1rem; white-space: nowrap;">
                                    <?= sanitize(date('d/m/Y', strtotime($u['created_at']))) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

