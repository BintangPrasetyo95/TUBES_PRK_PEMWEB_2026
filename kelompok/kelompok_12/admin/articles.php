
<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

// Mengambil data pencarian dan filter
$search = $_GET['search'] ?? '';
$category_id = $_GET['category_id'] ?? '';
$status = $_GET['status'] ?? '';

// Pengaturan pagination
$limit = 5;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

// Mengambil data kategori
$categoryQuery = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);

// Menyiapkan kondisi pencarian dan filter
$where = " WHERE 1=1 ";
$params = [];

if ($search !== '') {
    $where .= " AND articles.title LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

if ($category_id !== '' && ctype_digit((string) $category_id)) {
    $where .= " AND articles.category_id = :category_id";
    $params[':category_id'] = (int) $category_id;
}

if (in_array($status, ['draft', 'published', 'archived'], true)) {
    $where .= " AND articles.status = :status";
    $params[':status'] = $status;
}

// Menghitung seluruh artikel sesuai pencarian dan filter
$countQuery = "SELECT COUNT(*) FROM articles" . $where;
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);

$totalArticles = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalArticles / $limit));

// Memastikan halaman tidak melebihi jumlah halaman
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;

// Mengambil artikel berdasarkan halaman
$sql = "SELECT
            articles.id,
            articles.title,
            articles.content,
            articles.image,
            articles.author,
            articles.status,
            articles.created_at,
            categories.name AS category_name
        FROM articles
        LEFT JOIN categories
        ON articles.category_id = categories.id
        $where
        ORDER BY articles.created_at DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

// Memasukkan parameter pencarian dan filter
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

// Memasukkan batas jumlah data dan posisi awal
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fungsi untuk menampilkan teks dengan aman
function escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Membuat URL pagination dengan tetap membawa filter
function pageUrl($pageNumber, $search, $category_id, $status) {
    return '?' . http_build_query([
        'search' => $search,
        'category_id' => $category_id,
        'status' => $status,
        'page' => $pageNumber
    ]);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Artikel - Sehatin</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            background: #f5f7fa;
        }

        h2 {
            color: #333;
        }

        .filter-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        input, select, button {
            padding: 9px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            background: #198754;
            color: white;
            border: none;
            cursor: pointer;
        }

        .reset {
            background: #6c757d;
            color: white;
            padding: 9px 14px;
            text-decoration: none;
            border-radius: 5px;
        }

        .add-button {
            display: inline-block;
            background: #198754;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #198754;
            color: white;
        }

        img {
            width: 100px;
            height: 70px;
            object-fit: cover;
            border-radius: 5px;
        }

        .status {
            padding: 5px 8px;
            border-radius: 4px;
            color: white;
            font-size: 12px;
        }

        .draft {
            background: #6c757d;
        }

        .published {
            background: #198754;
        }

        .archived {
            background: #dc3545;
        }

        .edit {
            color: #0d6efd;
            text-decoration: none;
        }

        .delete {
            color: #dc3545;
            text-decoration: none;
        }

        .pagination {
            display: flex;
            gap: 6px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #198754;
            background: white;
        }

        .pagination .active {
            background: #198754;
            color: white;
            border-color: #198754;
        }

        .pagination .disabled {
            color: #aaa;
            pointer-events: none;
        }

        .info {
            margin-top: 15px;
            color: #555;
        }
    </style>
</head>

<body>

    <h2>Kelola Artikel Sehatin</h2>

    <a href="article-add.php" class="add-button">
        + Tambah Artikel
    </a>
    <a href="dashboard.php">Dashboard</a>
    <a href="articles.php">Artikel</a>
    <a href="categories.php">Kategori</a>
    <a href="tips.php">Tips Kesehatan</a>

    <!-- Form pencarian dan filter -->
    <form method="GET" class="filter-form">

        <input
            type="text"
            name="search"
            placeholder="Cari judul artikel..."
            value="<?= escape($search) ?>"
        >

        <select name="category_id">
            <option value="">Semua Kategori</option>

            <?php foreach ($categories as $category): ?>
                <option
                    value="<?= (int) $category['id'] ?>"
                    <?= (string) $category_id === (string) $category['id'] ? 'selected' : '' ?>
                >
                    <?= escape($category['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="status">
            <option value="">Semua Status</option>
            <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
            <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
            <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archived</option>
        </select>

        <button type="submit">Cari / Filter</button>

        <a href="articles.php" class="reset">Reset</a>

    </form>

    <!-- Tabel artikel -->
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Judul Artikel</th>
                <th>Kategori</th>
                <th>Penulis</th>
                <th>Isi Artikel</th>
                <th>Gambar</th>
                <th>Status</th>
                <th>Tanggal Dibuat</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody>

            <?php if (count($articles) > 0): ?>

                <?php $no = $offset + 1; ?>

                <?php foreach ($articles as $article): ?>
                    <tr>
                        <td><?= $no++ ?></td>

                        <td><?= escape($article['title']) ?></td>

                        <td><?= escape($article['category_name'] ?? '-') ?></td>

                        <td><?= escape($article['author']) ?></td>

                        <td>
                            <?= escape(substr(strip_tags($article['content']), 0, 100)) ?>...
                        </td>

                        <td>
                            <?php if (!empty($article['image'])): ?>
                                <img src="../<?= escape($article['image']) ?>" alt="Gambar artikel">
                            <?php else: ?>
                                Tidak ada gambar
                            <?php endif; ?>
                        </td>

                        <td>
                            <span class="status <?= escape($article['status']) ?>">
                                <?= escape($article['status']) ?>
                            </span>
                        </td>

                        <td><?= escape($article['created_at']) ?></td>

                        <td>
                            <a class="edit" href="article-edit.php?id=<?= (int) $article['id'] ?>">
                                Edit
                            </a>
                            |
                            <a
                                class="delete"
                                href="article-delete.php?id=<?= (int) $article['id'] ?>"
                                onclick="return confirm('Yakin ingin menghapus artikel ini?')"
                            >
                                Hapus
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="9" style="text-align:center;">
                        Tidak ada artikel ditemukan.
                    </td>
                </tr>

            <?php endif; ?>

        </tbody>
    </table>

    <p class="info">
        Menampilkan <?= count($articles) ?> dari <?= $totalArticles ?> artikel.
        Halaman <?= $page ?> dari <?= $totalPages ?>.
    </p>

    <!-- Navigasi pagination -->
    <div class="pagination">

        <?php if ($page > 1): ?>
            <a href="<?= escape(pageUrl($page - 1, $search, $category_id, $status)) ?>">
                &laquo; Sebelumnya
            </a>
        <?php else: ?>
            <span class="disabled">&laquo; Sebelumnya</span>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>

            <?php if ($i === $page): ?>
                <span class="active"><?= $i ?></span>
            <?php else: ?>
                <a href="<?= escape(pageUrl($i, $search, $category_id, $status)) ?>">
                    <?= $i ?>
                </a>
            <?php endif; ?>

        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="<?= escape(pageUrl($page + 1, $search, $category_id, $status)) ?>">
                Berikutnya &raquo;
            </a>
        <?php else: ?>
            <span class="disabled">Berikutnya &raquo;</span>
        <?php endif; ?>

    </div>

</body>
</html>
