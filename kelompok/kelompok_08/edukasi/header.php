<?php
$judul_halaman = $judul_halaman ?? 'Edukasi | BuyPanen';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($judul_halaman) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root { --hijau: #2f6b3a; --hijau-muda: #e7f1e4; --tanah: #4a3426; }
    body { color: #26302a; }
    .navbar-brand, h1, h2 { color: var(--hijau); font-weight: 700; }
    .btn-panen { background: var(--hijau); color: #fff; }
    .btn-panen:hover { background: #24542d; color: #fff; }
    .badge-kategori { background: var(--hijau-muda); color: var(--hijau); }
    .isi-artikel { max-width: 70ch; line-height: 1.8; font-size: 1.05rem; }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom">
  <div class="container">
    <a class="navbar-brand" href="../index.php">BuyPanen</a>
    <div class="navbar-nav ms-auto">
      <a class="nav-link" href="../index.php">Katalog</a>
      <a class="nav-link active" href="index.php">Edukasi</a>
    </div>
  </div>
</nav>