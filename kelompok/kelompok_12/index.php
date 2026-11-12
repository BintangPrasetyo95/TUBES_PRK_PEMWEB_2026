<?php
// Hubungkan dengan konfigurasi database
require_once 'config/database.php';

try {
    // Ambil data artikel terbaru
    $stmtLatest = $pdo->query("SELECT a.*, c.name as category_name FROM articles a LEFT JOIN categories c ON a.category_id = c.id ORDER BY a.created_at DESC LIMIT 4");
    $latestArticles = $stmtLatest->fetchAll(PDO::FETCH_ASSOC);

    // Ambil daftar kategori untuk menu fitur / filter cepat
    $stmtCategories = $pdo->query("SELECT * FROM categories LIMIT 6");
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
    <title>Sehatin - Solusi Kesehatan Terpercaya</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        
        /* Banner Slider Style */
        .promo-banner-left {
            background: linear-gradient(135deg, #2c1654 0%, #4a237d 100%);
            border-radius: 1rem;
            color: white;
            min-height: 260px;
        }
        .promo-banner-right {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            border-radius: 1rem;
            color: #1b5e20;
            min-height: 260px;
        }

        /* Feature Card Style */
        .feature-card {
            border: 1px solid #e0e0e0;
            border-radius: 0.75rem;
            transition: all 0.2s ease-in-out;
            background: white;
            text-decoration: none;
            color: inherit;
            display: block;
            padding: 1.25rem;
        }
        .feature-card:hover {
            border-color: #198754;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,.08);
            transform: translateY(-2px);
            color: inherit;
        }

        .card-article {
            transition: all 0.3s ease;
            border-radius: 1rem;
            overflow: hidden;
        }
        .card-article:hover {
            transform: translateY(-5px);
            box-shadow: 0 1rem 1.5rem rgba(0,0,0,.1) !important;
        }
        .card-img-top {
            height: 180px;
            object-fit: cover;
        }
    </style>
</head>
<body>

    <!-- Include Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- MAIN CONTAINER -->
    <main class="container my-4">
        
        <!-- BAGIAN BANNER PROMO / INFO (DUA KOLOM BERDAMPINGAN) -->
        <section class="row g-3 mb-5">
            <!-- Banner Kiri -->
            <div class="col-lg-8">
                <div class="promo-banner-left p-4 p-md-5 d-flex flex-column justify-content-center position-relative overflow-hidden shadow-sm">
                    <div class="col-md-7">
                        <span class="badge bg-light text-dark mb-2 px-2 py-1 small fw-semibold">Layanan Medis</span>
                        <h2 class="fw-bold fs-3 mb-2">Bingung Masalah Kesehatan?</h2>
                        <p class="small opacity-75 mb-3">Konsultasikan keluhanmu langsung dengan tenaga medis profesional secara cepat dan mudah.</p>
                        <a href="articles.php" class="btn btn-light text-success fw-bold px-4 py-2 rounded-pill shadow-sm d-inline-block">Cari Artikel & Solusi</a>
                    </div>
                </div>
            </div>
            <!-- Banner Kanan -->
            <div class="col-lg-4">
                <div class="promo-banner-right p-4 d-flex flex-column justify-content-center h-100 shadow-sm">
                    <span class="badge bg-success text-white mb-2 align-self-start px-2 py-1 small fw-semibold">Baru</span>
                    <h3 class="fw-bold fs-5 mb-2">Tips Sehat Harian</h3>
                    <p class="text-muted small mb-3">Dapatkan informasi kebugaran, pencegahan penyakit, dan gizi seimbang setiap hari.</p>
                    <a href="tips.php" class="btn btn-success btn-sm fw-semibold rounded-pill align-self-start px-3">Lihat Tips</a>
                </div>
            </div>
        </section>

        <!-- BAGIAN FITUR / SOLUSI KESEHATAN DI TANGANMU -->
        <section class="mb-5 bg-white p-4 p-md-5 rounded-4 shadow-sm">
            <div class="row align-items-center mb-4">
                <div class="col-md-4">
                    <h2 class="h4 fw-bold text-dark m-0">Solusi Kesehatan di Tanganmu</h2>
                    <p class="text-muted small m-0 mt-1">Akses cepat menu dan kategori artikel Sehatin</p>
                </div>
            </div>

            <!-- Grid Menu Kotak Fitur / Kategori -->
            <div class="row row-cols-1 row-cols-md-3 g-3">
                <div class="col">
                    <a href="articles.php" class="feature-card h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Daftar Artikel Lengkap</h6>
                                <p class="text-muted small mb-0">Baca berbagai topik kesehatan terkini</p>
                            </div>
                            <span class="text-success fs-5 fw-bold">&rarr;</span>
                        </div>
                    </a>
                </div>
                <div class="col">
                    <a href="tips.php" class="feature-card h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Tips & Gaya Hidup Sehat</h6>
                                <p class="text-muted small mb-0">Panduan praktis menjaga kebugaran tubuh</p>
                            </div>
                            <span class="text-success fs-5 fw-bold">&rarr;</span>
                        </div>
                    </a>
                </div>
                <div class="col">
                    <a href="search.php" class="feature-card h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Pencarian Cepat Medis</h6>
                                <p class="text-muted small mb-0">Cari informasi penyakit atau obat</p>
                            </div>
                            <span class="text-success fs-5 fw-bold">&rarr;</span>
                        </div>
                    </a>
                </div>
                
                <!-- Menampilkan Kategori dari Database secara Dinamis -->
                <?php foreach ($categories as $cat): ?>
                <div class="col">
                    <a href="categories.php?id=<?= (int)$cat['id'] ?>" class="feature-card h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($cat['name']) ?></h6>
                                <p class="text-muted small mb-0">Kategori topik kesehatan pilihan</p>
                            </div>
                            <span class="text-success fs-5 fw-bold">&rarr;</span>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- BAGIAN ARTIKEL TERBARU -->
        <section class="mb-5" id="articles">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h2 class="h4 fw-bold text-dark m-0">🌿 Artikel Kesehatan Terbaru</h2>
                <a href="articles.php" class="text-success text-decoration-none fw-semibold small">Lihat Semua &rarr;</a>
            </div>

            <div class="row row-cols-1 row-cols-md-4 g-4">
                <?php if (count($latestArticles) > 0): ?>
                    <?php foreach ($latestArticles as $article): ?>
                        <div class="col">
                            <div class="card card-article h-100 border-0 shadow-sm bg-white">
                                <?php if (!empty($article['image'])): ?>
                                    <img src="<?= htmlspecialchars($article['image']) ?>" class="card-img-top" alt="Gambar Artikel">
                                <?php else: ?>
                                    <img src="https://images.unsplash.com/photo-1505751172876-fa1923c5c528?auto=format&fit=crop&w=600&q=80" class="card-img-top" alt="Default Image">
                                <?php endif; ?>
                                
                                <div class="card-body d-flex flex-column p-3">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill small fw-semibold align-self-start mb-2">
                                        <?= htmlspecialchars($article['category_name'] ?? 'Umum') ?>
                                    </span>
                                    <h5 class="card-title fw-bold fs-6 text-dark mb-2"><?= htmlspecialchars($article['title']) ?></h5>
                                    <p class="card-text text-muted small flex-grow-1 mb-3"><?= htmlspecialchars(substr(strip_tags($article['content']), 0, 70)) ?>...</p>
                                    
                                    <a href="article-detail.php?id=<?= (int)$article['id'] ?>" class="btn btn-sm btn-outline-success rounded-pill w-100 fw-semibold mt-auto">Baca Selengkapnya</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-white border text-center py-4 rounded-4 shadow-sm" role="alert">
                            <p class="text-muted mb-0">Belum ada artikel terbaru yang dipublikasikan.</p>
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