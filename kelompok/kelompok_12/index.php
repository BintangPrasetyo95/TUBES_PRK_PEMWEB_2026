<?php
require_once 'config/database.php';

// Ambil artikel terbaru (limit 4 artikel dengan status published)
$stmtLatest = $pdo->query("SELECT articles.*, categories.name AS category_name 
                           FROM articles 
                           LEFT JOIN categories ON articles.category_id = categories.id 
                           WHERE articles.status = 'published' 
                           ORDER BY articles.created_at DESC LIMIT 4");
$latestArticles = $stmtLatest->fetchAll(PDO::FETCH_ASSOC);

// Ambil artikel populer berdasarkan jumlah views terbanyak (limit 3 artikel)
$stmtPopular = $pdo->query("SELECT articles.*, categories.name AS category_name 
                            FROM articles 
                            LEFT JOIN categories ON articles.category_id = categories.id 
                            WHERE articles.status = 'published' 
                            ORDER BY articles.views DESC LIMIT 3");
$popularArticles = $stmtPopular->fetchAll(PDO::FETCH_ASSOC);

function escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

include 'includes/header.php';
include 'includes/navbar.php';
?>

<!-- HERO SECTION -->
<header class="bg-success text-white text-center py-5 mb-5 shadow">
    <div class="container py-4">
        <h1 class="display-4 fw-bold">Selamat Datang di Sehatin</h1>
        <p class="lead mb-4">Temukan berbagai informasi, tips gizi, dan artikel kesehatan terpercaya untuk hidup yang lebih baik.</p>
        <a href="#articles" class="btn btn-light btn-lg text-success fw-bold px-4">Jelajahi Artikel</a>
    </div>
</header>

<main class="container mb-5" id="articles">
    
    <!-- ARTIKEL TERBARU -->
    <section class="mb-5">
        <h2 class="mb-4 border-bottom pb-2">🌿 Artikel Terbaru</h2>
        <div class="row">
            <?php if (count($latestArticles) > 0): ?>
                <?php foreach ($latestArticles as $article): ?>
                    <div class="col-md-3 mb-4">
                        <div class="card card-article h-100 shadow-sm border-0">
                            <?php if (!empty($article['image'])): ?>
                                <img src="<?= escape($article['image']) ?>" class="card-img-top" alt="Gambar Artikel">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/300x200?text=Sehatin" class="card-img-top" alt="Default">
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <span class="badge bg-success mb-2 align-self-start"><?= escape($article['category_name'] ?? 'Umum') ?></span>
                                <h5 class="card-title fs-6 fw-bold"><?= escape($article['title']) ?></h5>
                                <p class="card-text text-muted small flex-grow-1"><?= escape(substr(strip_tags($article['content']), 0, 80)) ?>...</p>
                                <a href="article-detail.php?id=<?= (int)$article['id'] ?>" class="btn btn-outline-success btn-sm mt-auto">Baca Selengkapnya</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">Belum ada artikel terbaru yang dipublikasikan.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- ARTIKEL POPULER -->
    <section>
        <h2 class="mb-4 border-bottom pb-2">🔥 Artikel Populer</h2>
        <div class="row">
            <?php if (count($popularArticles) > 0): ?>
                <?php foreach ($popularArticles as $article): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm border-0">
                            <?php if (!empty($article['image'])): ?>
                                <img src="<?= escape($article['image']) ?>" class="card-img-top" style="height: 180px; object-fit: cover;" alt="Populer">
                            <?php endif; ?>
                            <div class="card-body">
                                <span class="badge bg-danger mb-2"><?= escape($article['category_name'] ?? 'Umum') ?></span>
                                <h5 class="card-title fw-bold fs-5"><?= escape($article['title']) ?></h5>
                                <p class="text-muted small">Dilihat: <?= (int)$article['views'] ?> kali</p>
                                <a href="article-detail.php?id=<?= (int)$article['id'] ?>" class="text-success text-decoration-none fw-bold">Baca &rarr;</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">Belum ada data artikel populer.</p>
            <?php endif; ?>
        </div>
    </section>

</main>

<?php 
include 'includes/footer.php'; 
?>