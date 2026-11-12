<?php

// ==========================================
// SEHATIN - HALAMAN DAFTAR ARTIKEL
// Mengambil data dari database MySQL
// ==========================================

require_once __DIR__ . '/config/database.php';

// Mengamankan output HTML
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Mengambil input pencarian dan filter
$search = isset($_GET['q']) && is_string($_GET['q'])
    ? trim($_GET['q'])
    : '';

$selectedCategory = isset($_GET['category']) && is_string($_GET['category'])
    ? trim($_GET['category'])
    : '';

$sort = isset($_GET['sort']) && is_string($_GET['sort'])
    ? $_GET['sort']
    : 'latest';

// Memastikan pilihan pengurutan valid
if (!in_array($sort, ['latest', 'popular'], true)) {
    $sort = 'latest';
}

try {

    // Mengambil daftar kategori dari database
    $categoryQuery = $pdo->query(
        "SELECT name FROM categories ORDER BY name ASC"
    );

    $categories = $categoryQuery->fetchAll(PDO::FETCH_COLUMN);

    // Menyiapkan query artikel
    $sql = "
        SELECT
            a.id,
            a.title,
            a.content,
            c.name AS category,
            a.author,
            a.created_at AS date,
            a.views
        FROM articles a
        INNER JOIN categories c ON a.category_id = c.id
        WHERE a.status = 'published'
    ";

    $params = [];

    // Filter pencarian judul dan isi artikel
    if ($search !== '') {
        $sql .= " AND (a.title LIKE :search_title OR a.content LIKE :search_content)";

        $params['search_title'] = '%' . $search . '%';
        $params['search_content'] = '%' . $search . '%';
    }

    // Filter berdasarkan kategori
    if ($selectedCategory !== '') {
        $sql .= " AND c.name = :category";

        $params['category'] = $selectedCategory;
    }

    // Pengurutan artikel
    if ($sort === 'popular') {
        $sql .= " ORDER BY a.views DESC, a.created_at DESC";
    } else {
        $sql .= " ORDER BY a.created_at DESC";
    }

    // Menjalankan query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Membuat ringkasan dari isi artikel
    foreach ($articles as &$article) {
        $content = trim(strip_tags($article['content']));

        if (function_exists('mb_strlen')) {
            $article['excerpt'] = mb_strlen($content) > 150
                ? mb_substr($content, 0, 150) . '...'
                : $content;
        } else {
            $article['excerpt'] = strlen($content) > 150
                ? substr($content, 0, 150) . '...'
                : $content;
        }
    }

    unset($article);

} catch (PDOException $e) {
    die("Gagal mengambil data artikel: " . $e->getMessage());
}

?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artikel Kesehatan | Sehatin</title>
    <link rel="stylesheet" href="assets/css/articles.css">
</head>
<body>

    <!-- NAVBAR SEMENTARA
         Nantinya dapat diintegrasikan dengan navbar Fathan. -->
    <header class="navbar">
        <div class="container nav-content">
            <a href="articles.php" class="logo">
                Sehat<span>in.</span>
            </a>

            <nav class="nav-links">
                <a href="index.php">Beranda</a>
                <a href="articles.php" class="active">Artikel</a>
                <a href="categories.php">Kategori</a>
                <a href="tips.php">Tips Sehat</a>
            </nav>
        </div>
    </header>

    <main>
        <!-- HERO SECTION -->
        <section class="hero">
            <div class="container hero-content">
                <span class="eyebrow">INFORMASI KESEHATAN TERPERCAYA</span>

                <h1>Temukan Informasi untuk<br>
                    Hidup Lebih <span>Sehat.</span>
                </h1>

                <p>
                    Baca berbagai artikel kesehatan, temukan wawasan baru,
                    dan mulai langkah kecil menuju kehidupan yang lebih baik.
                </p>
            </div>
        </section>

        <!-- ARTICLE SECTION -->
        <section class="article-section">
            <div class="container">

                <div class="section-heading">
                    <div>
                        <span class="eyebrow">JELAJAHI PENGETAHUAN</span>
                        <h2>Artikel Kesehatan</h2>
                        <p>Informasi dan inspirasi untuk menjaga kesehatanmu.</p>
                    </div>

                    <span class="article-count">
                        <?= count($articles) ?> Artikel ditemukan
                    </span>
                </div>

                <!-- SEARCH AND FILTER -->
                <form action="articles.php" method="GET" class="filter-bar">

                    <div class="search-box">
                        <span class="search-icon">⌕</span>
                        <input
                            type="search"
                            name="q"
                            placeholder="Cari artikel kesehatan..."
                            value="<?= e($search) ?>"
                        >
                    </div>

                    <select name="category">
                        <option value="">Semua Kategori</option>

                        <?php foreach ($categories as $category): ?>
                            <option
                                value="<?= e($category) ?>"
                                <?= $selectedCategory === $category ? 'selected' : '' ?>
                            >
                                <?= e($category) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="sort">
                        <option value="latest" <?= $sort === 'latest' ? 'selected' : '' ?>>
                            Terbaru
                        </option>
                        <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>
                            Terpopuler
                        </option>
                    </select>

                    <button type="submit" class="filter-button">
                        Terapkan
                    </button>

                    <?php if ($search !== '' || $selectedCategory !== '' || $sort !== 'latest'): ?>
                        <a href="articles.php" class="reset-button">Reset</a>
                    <?php endif; ?>

                </form>

                <!-- ARTICLE CARDS -->
                <?php if (count($articles) > 0): ?>

                    <div class="article-grid">

                        <?php foreach ($articles as $article): ?>

                            <article class="article-card">

                                <div class="article-image">
                                    <span class="image-label">
                                        <?= e($article['category']) ?>
                                    </span>
                                    <span class="image-decoration">+</span>
                                </div>

                                <div class="article-body">

                                    <div class="article-meta">
                                        <span>
                                            <?= date('d M Y', strtotime($article['date'])) ?>
                                        </span>
                                        <span>•</span>
                                        <span><?= e($article['author']) ?></span>
                                    </div>

                                    <h3><?= e($article['title']) ?></h3>

                                    <p class="article-excerpt">
                                        <?= e($article['excerpt']) ?>
                                    </p>

                                    <div class="article-footer">
                                        <span class="views">
                                            ◉ <?= number_format($article['views']) ?> dibaca
                                        </span>

                                        <a
                                            href="article_detail.php?id=<?= (int) $article['id'] ?>"
                                            class="read-more"
                                        >
                                            Baca selengkapnya →
                                        </a>
                                    </div>

                                </div>
                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="empty-state">
                        <div class="empty-icon">⌕</div>
                        <h3>Artikel tidak ditemukan</h3>
                        <p>
                            Coba gunakan kata kunci atau kategori
                            yang berbeda.
                        </p>
                        <a href="articles.php" class="filter-button">
                            Lihat Semua Artikel
                        </a>
                    </div>

                <?php endif; ?>

            </div>
        </section>
    </main>

    <!-- FOOTER SEMENTARA
         Nantinya dapat diintegrasikan dengan footer Fathan. -->
    <footer class="footer">
        <div class="container">
            <a href="articles.php" class="logo">
                Sehat<span>in.</span>
            </a>
            <p>
                Teman perjalananmu menuju kehidupan yang lebih sehat.
            </p>
            <small>&copy; <?= date('Y') ?> Sehatin. All rights reserved.</small>
        </div>
    </footer>

</body>
</html>

