<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

// Mengambil daftar kategori beserta jumlah artikel di setiap kategori
$query = "SELECT 
            categories.id, 
            categories.name, 
            categories.description,
            COUNT(articles.id) AS total_articles
          FROM categories
          LEFT JOIN articles 
            ON categories.id = articles.category_id
          GROUP BY categories.id, categories.name, categories.description
          ORDER BY categories.name ASC";

$stmt = $pdo->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

function escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - Sehatin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            background: #f5f7fa;
        }
        h2 { color: #333; }
        .nav-links {
            margin-bottom: 20px;
        }
        .nav-links a {
            margin-right: 15px;
            text-decoration: none;
            color: #0d6efd;
            font-weight: bold;
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
        .edit { color: #0d6efd; text-decoration: none; }
        .delete { color: #dc3545; text-decoration: none; }
    </style>
</head>
<body>

    <h2>Kelola Kategori Sehatin</h2>

    <div class="nav-links">
        <a href="dashboard.php">&larr; Kembali ke Dashboard</a>
        <a href="articles.php">Kelola Artikel</a>
    </div>

    <a href="category-add.php" class="add-button">
        + Tambah Kategori
    </a>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Kategori</th>
                <th>Deskripsi</th>
                <th>Jumlah Artikel</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($categories) > 0): ?>
                <?php foreach ($categories as $index => $category): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= escape($category['name']) ?></td>
                        <td><?= escape($category['description'] ?? '-') ?></td>
                        <td><?= (int) $category['total_articles'] ?> Artikel</td>
                        <td>
                            <a class="edit" href="category-edit.php?id=<?= (int) $category['id'] ?>">
                                Edit
                            </a>
                            |
                            <a class="delete" href="category-delete.php?id=<?= (int) $category['id'] ?>"
                               onclick="return confirm('Yakin ingin menghapus kategori ini?')">
                                Hapus
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align:center;">Belum ada kategori.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>