<?php
require_once '../auth.php';
require_once '../../config/koneksi.php';
$adminSection = 'kategori';
$judulHalaman = 'Ubah Kategori Obat';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

// Ambil data lama
$stmt = $conn->prepare("SELECT * FROM kategori WHERE id_kategori = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$kategori = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$kategori) {
    header("Location: index.php");
    exit;
}

$pesan = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kategori = trim($_POST['nama_kategori']);

    if (!empty($nama_kategori)) {
        $update = $conn->prepare("UPDATE kategori SET nama_kategori = ? WHERE id_kategori = ?");
        $update->bind_param("si", $nama_kategori, $id);
        if ($update->execute()) {
            header("Location: index.php");
            exit;
        } else {
            $pesan = "Gagal memperbarui kategori.";
        }
        $update->close();
    } else {
        $pesan = "Nama kategori tidak boleh kosong!";
    }
}
?>
<?php require_once '../../backend/admin/layout/header.php'; ?>
<div class="admin-content">
    <h2>Form Ubah Kategori</h2>

                <?php if(!empty($pesan)): ?>
                    <div style="padding: 12px 15px; margin-bottom: 20px; background: #fee2e2; color: #b91c1c; border-radius: 10px; font-weight: 600; font-size: 14px;">
                        <?= $pesan; ?>
                    </div>
                <?php endif; ?>

                <!-- Form Ubah -->
                <div class="admin-table-container" style="max-width: 600px; padding: 25px;">
                    <form method="POST">
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--admin-text);">Nama Kategori:</label>
                            <input type="text" name="nama_kategori" value="<?= htmlspecialchars($kategori['nama_kategori']); ?>" required autocomplete="off">
                        </div>
                        
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <button type="submit" class="admin-btn">Perbarui Kategori</button>
                            <a href="index.php" style="color: var(--admin-secondary); text-decoration: none; font-weight: 600; font-size: 14px; padding: 11px 19px;">Kembali</a>
                        </div>
                    </form>
                </div>
    </div>
</div>
<?php require_once '../../backend/admin/layout/footer.php'; ?>