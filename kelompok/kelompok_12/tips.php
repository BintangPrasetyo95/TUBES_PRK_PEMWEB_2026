
<?php
require_once 'config/database.php';

// Ambil semua tips yang tersedia dari database
$stmt = $pdo->query("SELECT * FROM tips ORDER BY created_at DESC");
$tips = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tips Kesehatan - Sehatin</title>
    <link rel="stylesheet" href="assets/css/tips.css">
</head>
<body>

    <h1>Tips Kesehatan Sehatin</h1>
    <p>Temukan berbagai tips sederhana untuk menjaga kesehatan dan pola hidup sehat.</p>

    <hr>

    <h2>Daftar Tips Kesehatan</h2>

    <?php if (count($tips) > 0): ?>

        <div class="daftar-tips">

            <?php foreach ($tips as $tip): ?>

                <div class="kartu-tips">

                    <?php if (!empty($tip['image'])): ?>
                        <img
                            src="assets/uploads/tips/<?= htmlspecialchars($tip['image']) ?>"
                            alt="<?= htmlspecialchars($tip['title']) ?>"
                        >
                    <?php endif; ?>

                    <h3><?= htmlspecialchars($tip['title']) ?></h3>

                    <p>
                        <?= nl2br(htmlspecialchars($tip['description'])) ?>
                    </p>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <p>Belum ada tips kesehatan tersedia.</p>

    <?php endif; ?>

</body>
</html>
