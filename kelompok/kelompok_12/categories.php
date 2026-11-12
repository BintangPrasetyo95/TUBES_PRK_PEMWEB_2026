

<?php
// ==========================================
// SEHATIN - CATEGORY PAGE
// Terhubung dengan database MySQL
// ==========================================

require_once __DIR__ . '/config/database.php';

// Mengamankan output HTML
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Membuat ringkasan artikel dari isi konten
function makeExcerpt($content, $length = 150) {
    $content = trim(strip_tags($content));

    if (function_exists('mb_strlen')) {
        return mb_strlen($content) > $length
            ? mb_substr($content, 0, $length) . '...'
            : $content;
    }

    return strlen($content) > $length
        ? substr($content, 0, $length) . '...'
        : $content;
}

// Ikon kategori sementara
$categoryIcons = [
    1 => '🥗',
    2 => '🧠',
    3 => '🏃',
];

// Deskripsi cadangan jika database belum memiliki deskripsi
$categoryDescriptions = [
    1 => 'Informasi seputar makanan bergizi, pola makan sehat, dan kebutuhan nutrisi tubuh.',
    2 => 'Artikel tentang menjaga kesehatan pikiran, mengelola stres, dan keseimbangan emosi.',
    3 => 'Tips aktivitas fisik dan olahraga untuk menjaga tubuh tetap sehat dan bugar.',
];

// Mengambil kategori yang dipilih dari URL
$selectedCategoryId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$selectedCategory = null;
$filteredArticles = [];
$categories = [];

try {

    // Mengambil semua kategori beserta jumlah artikel published
    $categoryQuery = $pdo->query("
        SELECT
            c.id,
            c.name,
            c.description,
            COUNT(a.id) AS article_count
        FROM categories c
        LEFT JOIN articles a
            ON c.id = a.category_id
            AND a.status = 'published'
        GROUP BY c.id, c.name, c.description
        ORDER BY c.id ASC
    ");

    $categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);

    // Menambahkan ikon dan deskripsi tampilan
    foreach ($categories as &$category) {

        $categoryId = (int) $category['id'];

        $category['icon'] = $categoryIcons[$categoryId] ?? '📚';

        if (empty($category['description'])) {
            $category['description'] =
                $categoryDescriptions[$categoryId]
                ?? 'Temukan berbagai informasi kesehatan dalam kategori ini.';
        }
    }

    unset($category);

    // Mengambil detail kategori berdasarkan ID
    if ($selectedCategoryId && $selectedCategoryId > 0) {

        $selectedCategoryQuery = $pdo->prepare("
            SELECT id, name, description
            FROM categories
            WHERE id = :id
            LIMIT 1
        ");

        $selectedCategoryQuery->execute([
            'id' => $selectedCategoryId
        ]);

        $selectedCategory = $selectedCategoryQuery->fetch(PDO::FETCH_ASSOC);

        if ($selectedCategory) {

            $categoryId = (int) $selectedCategory['id'];

            $selectedCategory['icon'] =
                $categoryIcons[$categoryId] ?? '📚';

            if (empty($selectedCategory['description'])) {
                $selectedCategory['description'] =
                    $categoryDescriptions[$categoryId]
                    ?? 'Temukan berbagai informasi kesehatan dalam kategori ini.';
            }

            // Mengambil artikel berdasarkan kategori
            $articleQuery = $pdo->prepare("
                SELECT
                    a.id,
                    a.category_id,
                    a.title,
                    a.content,
                    a.views,
                    a.created_at AS date
                FROM articles a
                WHERE a.category_id = :category_id
                AND a.status = 'published'
                ORDER BY a.created_at DESC
            ");

            $articleQuery->execute([
                'category_id' => $categoryId
            ]);

            $filteredArticles = $articleQuery->fetchAll(PDO::FETCH_ASSOC);

            // Membuat ringkasan setiap artikel
            foreach ($filteredArticles as &$article) {
                $article['excerpt'] = makeExcerpt($article['content']);
            }

            unset($article);
        }
    }

} catch (PDOException $e) {
    die("Gagal mengambil data kategori: " . $e->getMessage());
}

?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Artikel - Sehatin</title>
    <link rel="stylesheet" href="assets/css/articles.css">
</head>
<body>

    <!-- NAVBAR SEMENTARA -->
    <nav class="navbar">
        <div class="nav-content">
            <a href="index.php" class="logo">Sehatin.</a>

            <div class="nav-links">
                <a href="index.php">Beranda</a>
                <a href="articles.php">Artikel</a>
                <a href="categories.php">Kategori</a>
                <a href="tips.php">Tips Sehat</a>
            </div>
        </div>
    </nav>

    <!-- HERO -->
    <section class="hero">
        <div class="hero-content">
            <span class="eyebrow">JELAJAHI SEHATIN</span>
            <h1>
                <?= $selectedCategory
                    ? e($selectedCategory['name'])
                    : 'Kategori Artikel' ?>
            </h1>

            <p>
                <?= $selectedCategory
                    ? e($selectedCategory['description'])
                    : 'Temukan berbagai informasi kesehatan berdasarkan topik yang kamu butuhkan.' ?>
            </p>
        </div>
    </section>

    <main class="category-section">

        <?php if (!$selectedCategory): ?>

            <div class="category-heading">
                <div>
                    <span class="eyebrow">PILIH TOPIK</span>
                    <h2>Jelajahi Berdasarkan Kategori</h2>
                    <p>Pilih topik kesehatan yang ingin kamu pelajari.</p>
                </div>
                <span class="category-total">
                    <?= count($categories) ?> Kategori
                </span>
            </div>

            <div class="category-grid">
                <?php foreach ($categories as $category): ?>

        

                    <a
                        href="categories.php?id=<?= $category['id'] ?>"
                        class="category-card"
                    >
                        <div class="category-icon">
                            <?= e($category['icon']) ?>
                        </div>

                        <h3><?= e($category['name']) ?></h3>

                        <p><?= e($category['description']) ?></p>

                        <div class="category-card-footer">
                            <span><?= (int) $category['article_count'] ?> Artikel</span>
                            <span class="category-arrow">→</span>
                        </div>
                    </a>

                <?php endforeach; ?>
            </div>

        <?php elseif (!empty($filteredArticles)): ?>

            <div class="category-heading">
                <div>
                    <span class="eyebrow">ARTIKEL TERSEDIA</span>
                    <h2>Artikel <?= e($selectedCategory['name']) ?></h2>
                    <p>Berikut artikel yang tersedia dalam kategori ini.</p>
                </div>

                <a href="categories.php" class="category-back">
                    ← Semua Kategori
                </a>
            </div>

            <div class="category-article-grid">
                <?php foreach ($filteredArticles as $article): ?>

                    <article class="category-article-card">
                        <div class="category-article-body">

                            <span class="category-label">
                                <?= e($selectedCategory['name']) ?>
                            </span>

                            <h3><?= e($article['title']) ?></h3>

                            <p><?= e($article['excerpt']) ?></p>

                            <div class="category-article-meta">
                                <span><?= e($article['date']) ?></span>
                                <span><?= number_format($article['views']) ?> Dilihat</span>
                            </div>

                            <a
                                href="article_detail.php?id=<?= $article['id'] ?>"
                                class="category-read-more"
                            >
                                Baca Artikel →
                            </a>

                        </div>
                    </article>

                <?php endforeach; ?>
            </div>

        <?php else: ?>

            <div class="category-empty">
                <h2>Belum Ada Artikel</h2>
                <p>Belum tersedia artikel untuk kategori ini.</p>
                <a href="categories.php">Kembali ke Semua Kategori</a>
            </div>

        <?php endif; ?>

    </main>

    <!-- FOOTER SEMENTARA -->
    <footer class="footer">
        <p>&copy; 2026 Sehatin. Semua Hak Dilindungi.</p>
    </footer>

</body>
</html>
