<?php
// Hubungkan dengan konfigurasi database
require_once 'config/database.php'; // Sesuaikan path jika diletakkan di root kelompok

// Ambil data artikel terbaru dari database
try {
    $stmtLatest = $pdo->query("SELECT a.*, c.name as category_name FROM articles a LEFT JOIN categories c ON a.category_id = c.id ORDER BY a.created_at DESC LIMIT 4");
    $latestArticles = $stmtLatest->fetchAll(PDO::FETCH_ASSOC);

    // Ambil daftar kategori untuk tombol filter
    $stmtCategories = $pdo->query("SELECT * FROM categories");
    $categories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $latestArticles = [];
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sehatin - Portal Informasi Kesehatan</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">

    <!-- Include Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <header class="hero-section text-white text-center py-5 mb-5 bg-success shadow-sm">
        <div class="container py-5">
            <h1 class="display-4 fw-bold">Selamat Datang di Sehatin</h1>
            <p class="lead col-lg-8 mx-auto">Temukan informasi kesehatan terpercaya, tips gaya hidup sehat, dan artikel medis terbaru untuk kualitas hidup yang lebih baik.</p>
            <a href="#articles" class="btn btn-light text-success fw-bold px-4 py-2 mt-3 shadow-sm">Jelajahi Artikel</a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mb-5" id="articles">
        
        <!-- Filter Kategori Cepat -->
        <div class="mb-4 text-center">
            <span class="d-block text-muted small mb-2">Filter Berdasarkan Kategori:</span>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="index.php" class="btn btn-success btn-sm">Semua</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="categories.php?id=<?= (int)$cat['id'] ?>" class="btn btn-outline-success btn-sm"><?= htmlspecialchars($cat['name']) ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Section Artikel Terbaru -->
        <section class="mb-5">
            <h2 class="mb-4 border-bottom pb-2 text-dark fs-4">🌿 Artikel Terbaru</h2>
            <div class="row">
                <?php if (count($latestArticles) > 0): ?>
                    <?php foreach ($latestArticles as $article): ?>
                        <div class="col-md-3 mb-4">
                            <div class="card card-article h-100 shadow-sm border-0">
                                <?php if (!empty($article['image'])): ?>
                                    <img src="<?= htmlspecialchars($article['image']) ?>" class="card-img-top" alt="Gambar Artikel" style="height: 160px; object-fit: cover;">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/300x160?text=Sehatin" class="card-img-top" alt="Default Image">
                                <?php endif; ?>
                                <div class="card-body d-flex flex-column">
                                    <span class="badge bg-success mb-2 align-self-start"><?= htmlspecialchars($article['category_name'] ?? 'Umum') ?></span>
                                    <h5 class="card-title fs-6 fw-bold"><?= htmlspecialchars($article['title']) ?></h5>
                                    <p class="card-text text-muted small flex-grow-1"><?= htmlspecialchars(substr(strip_tags($article['content']), 0, 75)) ?>...</p>
                                    <a href="article-detail.php?id=<?= (int)$article['id'] ?>" class="btn btn-outline-success btn-sm mt-auto">Baca Selengkapnya</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center" role="alert">
                            Belum ada artikel terbaru yang dipublikasikan saat ini.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </main>

    <!-- Include Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/script.js"></script>
</body>
</html>