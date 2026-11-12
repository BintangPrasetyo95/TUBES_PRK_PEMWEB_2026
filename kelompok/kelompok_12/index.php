<?php
// Hubungkan dengan konfigurasi database
require_once 'config/database.php';

try {
    // Ambil data artikel terbaru
    $stmtLatest = $pdo->query("SELECT a.*, c.name as category_name FROM articles a LEFT JOIN categories c ON a.category_id = c.id ORDER BY a.created_at DESC LIMIT 6");
    $latestArticles = $stmtLatest->fetchAll(PDO::FETCH_ASSOC);

    // Ambil daftar kategori untuk tombol filter cepat
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
    <title>Sehatin - Portal Informasi & Artikel Kesehatan</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .hero-banner {
            background: linear-gradient(135deg, #198754 0%, #146c43 100%);
            border-radius: 0 0 2.5rem 2.5rem;
            padding: 4rem 0;
            color: white;
        }
        .card-article {
            transition: all 0.3s ease;
            border-radius: 1rem;
            overflow: hidden;
        }
        .card-article:hover {
            transform: translateY(-6px);
            box-shadow: 0 1rem 2rem rgba(0,0,0,.1) !important;
        }
        .card-img-top {
            height: 190px;
            object-fit: cover;
        }
        .filter-btn {
            border-radius: 50rem;
            padding: 0.4rem 1.2rem;
            font-weight: 500;
            font-size: 0.9rem;
        }
    </style>
</head>
<body class="bg-light">

    <!-- Include Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- HERO SECTION / BANNER UTAMA -->
    <header class="hero-banner text-center shadow-sm mb-5">
        <div class="container px-4">
            <span class="badge bg-white text-success px-3 py-2 rounded-pill fw-semibold mb-3 shadow-sm">🌿 Portal Kesehatan Terpercaya</span>
            <h1 class="display-5 fw-bold mb-3">Wujudkan Hidup Sehat & Bugar Bersama Sehatin</h1>
            <p class="lead col-lg-7 mx-auto opacity-90 mb-4 fs-6">Jelajahi berbagai artikel medis terkini, tips gaya hidup sehat, dan panduan gizi seimbang langsung dari para ahli.</p>
            <div class="d-flex justify-content-center gap-2">
                <a href="#articles" class="btn btn-light text-success fw-bold px-4 py-2 rounded-pill shadow-sm">Jelajahi Artikel</a>
                <a href="tips.php" class="btn btn-outline-light fw-semibold px-4 py-2 rounded-pill">Tips Kesehatan</a>
            </div>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="container mb-5" id="articles">
        
        <!-- FILTER KATEGORI CEPAT -->
        <div class="mb-5 text-center">
            <p class="text-muted text-uppercase small fw-bold tracking-wider mb-3">Filter Berdasarkan Kategori</p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="index.php" class="btn btn-success filter-btn shadow-sm">Semua Topik</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="categories.php?id=<?= (int)$cat['id'] ?>" class="btn btn-outline-success filter-btn bg-white"><?= htmlspecialchars($cat['name']) ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- SECTION ARTIKEL TERBARU -->
        <section class="mb-5">
            <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                <h2 class="h4 fw-bold text-dark m-0">🔥 Artikel Terbaru Pilihan</h2>
                <a href="articles.php" class="text-success text-decoration-none fw-semibold small">Lihat Semua &rarr;</a>
            </div>

            <div class="row row-cols-1 row-cols-md-3 g-4">
                <?php if (count($latestArticles) > 0): ?>
                    <?php foreach ($latestArticles as $article): ?>
                        <div class="col">
                            <div class="card card-article h-100 border-0 shadow-sm bg-white">
                                <?php if (!empty($article['image'])): ?>
                                    <img src="<?= htmlspecialchars($article['image']) ?>" class="card-img-top" alt="Gambar Artikel">
                                <?php else: ?>
                                    <img src="https://images.unsplash.com/photo-1505751172876-fa1923c5c528?auto=format&fit=crop&w=600&q=80" class="card-img-top" alt="Default Image">
                                <?php endif; ?>
                                
                                <div class="card-body d-flex flex-column p-4">
                                    <div class="mb-2">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill small fw-semibold">
                                            <?= htmlspecialchars($article['category_name'] ?? 'Umum') ?>
                                        </span>
                                    </div>
                                    <h5 class="card-title fw-bold fs-6 text-dark mb-2"><?= htmlspecialchars($article['title']) ?></h5>
                                    <p class="card-text text-muted small flex-grow-1 mb-4"><?= htmlspecialchars(substr(strip_tags($article['content']), 0, 85)) ?>...</p>
                                    
                                    <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-auto">
                                        <span class="text-muted small" style="font-size: 0.75rem;"><?= date('d M Y', strtotime($article['created_at'] ?? 'now')) ?></span>
                                        <a href="article-detail.php?id=<?= (int)$article['id'] ?>" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-semibold">Baca</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-white border text-center py-4 rounded-4 shadow-sm" role="alert">
                            <p class="text-muted mb-0">Belum ada artikel terbaru yang dipublikasikan saat ini.</p>
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