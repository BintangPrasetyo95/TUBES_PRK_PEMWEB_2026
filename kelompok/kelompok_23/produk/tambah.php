<?php
session_start();

require_once __DIR__ . '/../includes/role_guard.php';
requireRole(['Admin']);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helper.php';

$kategori = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$error = '';

if (isset($_POST['simpan'])) {
    $category_id = $_POST['category_id'];
    $sku         = trim($_POST['sku']);
    $name        = trim($_POST['name']);
    $price       = $_POST['price'];
    $stock       = $_POST['stock'];
    $min_stock   = $_POST['min_stock'];

    $image = null;

    // --- Upload gambar (opsional) ---
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $error = "Gagal mengunggah gambar.";
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['image']['tmp_name']);

            $allowedMime = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
            ];

            if (!isset($allowedMime[$mime])) {
                $error = "Format gambar harus JPG, PNG, atau WEBP.";
            } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                $error = "Ukuran gambar maksimal 2 MB.";
            } else {
                $ext = $allowedMime[$mime];
                $fileName = bin2hex(random_bytes(16)) . '.' . $ext;
                $targetPath = __DIR__ . '/../assets/img/' . $fileName;

                if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $error = "Gagal menyimpan gambar.";
                } else {
                    $image = $fileName;
                }
            }
        }
    }

    if (empty($category_id) || empty($sku) || empty($name) || empty($price)) {
        $error = "Semua kolom bertanda (*) wajib diisi!";
    } elseif (empty($error)) {
        $stmt = $conn->prepare("INSERT INTO products (category_id, sku, name, price, stock, min_stock, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdiis", $category_id, $sku, $name, $price, $stock, $min_stock, $image);

        if ($stmt->execute()) {
            header("Location: index.php?status=berhasil");
            exit;
        } else {
            $error = "Gagal menyimpan produk: " . $conn->error;

            // Hapus file gambar jika penyimpanan produk gagal, supaya tidak jadi sampah.
            if ($image !== null) {
                @unlink(__DIR__ . '/../assets/img/' . $image);
            }
        }
    }
}

$pageTitle = 'Tambah Produk';
require_once __DIR__ . '/../includes/header.php';
?>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="app-layout d-flex flex-grow-1">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main class="main-content flex-grow-1 p-4">
        <div class="mb-4">
            <h3 class="fw-bold">
                <i class="bi bi-box-seam me-2"></i>Tambah Produk
            </h3>
            <p class="text-muted">
                Tambahkan produk baru ke dalam sistem FEBMart.
            </p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" style="max-width: 800px;"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm" style="max-width: 800px;">
            <div class="card-body p-4">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Kategori <span class="text-danger">*</span></label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php while($kat = $kategori->fetch_assoc()): ?>
                                        <option value="<?= $kat['id']; ?>"><?= htmlspecialchars($kat['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">SKU <span class="text-danger">*</span></label>
                                <input type="text" name="sku" class="form-control" placeholder="Contoh: PRD-001" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Harga <span class="text-danger">*</span></label>
                                <input type="number" name="price" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Stok <span class="text-danger">*</span></label>
                                <input type="number" name="stock" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Minimal Stok</label>
                                <input type="number" name="min_stock" class="form-control" value="5" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Gambar Produk</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                            <a href="index.php" class="btn btn-secondary">Kembali</a>
                            <button type="submit" name="simpan" class="btn btn-primary">Simpan Produk</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>