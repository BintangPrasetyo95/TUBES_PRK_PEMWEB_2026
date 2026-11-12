<?php
require_once '../auth.php';
require_once '../../config/koneksi.php';
$adminSection = 'kategori';
$judulHalaman = 'Tambah Kategori Obat';

$pesan = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kategori = trim($_POST['nama_kategori']);

    if (!empty($nama_kategori)) {
        $stmt = $conn->prepare("INSERT INTO kategori (nama_kategori) VALUES (?)");
        $stmt->bind_param("s", $nama_kategori);
        if ($stmt->execute()) {
            header("Location: index.php");
            exit;
        } else {
            $pesan = "Gagal menambah kategori (Mungkin nama sudah ada).";
        }
        $stmt->close();
    } else {
        $pesan = "Nama kategori tidak boleh kosong!";
    }
}
?>
<?php require_once '../../backend/admin/layout/header.php'; ?>
<div class="admin-content">
                <h2>Form Tambah Kategori</h2>

                <?php if(!empty($pesan)): ?>
                    <div style="padding: 12px 15px; margin-bottom: 20px; background: #fee2e2; color: #b91c1c; border-radius: 10px; font-weight: 600; font-size: 14px;">
                        <?= $pesan; ?>
                    </div>
                <?php endif; ?>

                <!-- Form Tambah -->
                <div class="admin-table-container" style="max-width: 600px; padding: 25px;">
                    <form method="POST">
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--admin-text);">Nama Kategori:</label>
                            <input type="text" name="nama_kategori" required placeholder="Masukkan nama kategori obat..." autocomplete="off">
                        </div>
                        
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <button type="submit" class="admin-btn">Simpan Kategori</button>
                            <a href="index.php" style="color: var(--admin-secondary); text-decoration: none; font-weight: 600; font-size: 14px; padding: 11px 19px;">Kembali</a>
                        </div>
                    </form>
                </div>
</div>
<?php require_once '../../backend/admin/layout/footer.php'; ?>