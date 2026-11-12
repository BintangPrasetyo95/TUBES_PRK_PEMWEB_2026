<?php
// kelompok/kelompok_12/admin/dashboard.php
require_once '../includes/auth.php';
require_once '../config/database.php';

$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

// ---------- Statistik ----------
$stat = [
    ['📰', 'Total Artikel', $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn()],
    ['✅', 'Published',     $pdo->query("SELECT COUNT(*) FROM articles WHERE status='published'")->fetchColumn()],
    ['📝', 'Draft',         $pdo->query("SELECT COUNT(*) FROM articles WHERE status='draft'")->fetchColumn()],
    ['🗂️', 'Kategori',      $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn()],
    ['💡', 'Tips Sehat',    $pdo->query("SELECT COUNT(*) FROM tips")->fetchColumn()],
    ['👁️', 'Total Views',   $pdo->query("SELECT COALESCE(SUM(views),0) FROM articles")->fetchColumn()],
];

// ---------- Artikel draft (perlu dicek) ----------
$draft = $pdo->query(
    "SELECT a.title, a.created_at, c.name AS kategori
     FROM articles a JOIN categories c ON a.category_id = c.id
     WHERE a.status = 'draft'
     ORDER BY a.created_at DESC LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);

// ---------- Artikel terbaru ----------
$terbaru = $pdo->query(
    "SELECT a.title, a.status, a.views, a.created_at, c.name AS kategori
     FROM articles a JOIN categories c ON a.category_id = c.id
     ORDER BY a.created_at DESC LIMIT 6"
)->fetchAll(PDO::FETCH_ASSOC);

// ---------- Jumlah artikel per kategori ----------
$per_kategori = $pdo->query(
    "SELECT c.name, COUNT(a.id) AS total
     FROM categories c LEFT JOIN articles a ON a.category_id = c.id
     GROUP BY c.id, c.name
     ORDER BY total DESC, c.name"
)->fetchAll(PDO::FETCH_ASSOC);
$maks = max(1, ...array_map(fn($k) => (int)$k['total'], $per_kategori ?: [['total' => 1]]));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard Admin - SehatIn</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  <link rel="stylesheet" href="../assets/css/admin-theme.css?v=1">
  :root { --g1:#0f9d58; --g2:#7ddc9b; --g3:#e8f8ee; --ink:#12372a; --muted:#5f7d6f; --warn:#f5a623; }
  * { box-sizing: border-box; }
  body { margin:0; font-family:'Plus Jakarta Sans', system-ui, sans-serif; background:#f6fbf8; color:var(--ink); }
  nav { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; padding:14px 6vw; background:#fff; position:sticky; top:0; z-index:5; box-shadow:0 1px 12px rgba(15,157,88,.08); }
  nav b { font-size:20px; color:var(--g1); }
  .menu { display:flex; gap:8px; flex-wrap:wrap; }
  .menu a {
    color: var(--ink);
    transition: 0.2s;
}

.menu a:hover {
    background: var(--g3);
    color: var(--g1);
}

.menu .aktif {
    background: var(--g3);
    color: var(--g1);
    font-weight: 600;
}

.menu .logout {
    background: var(--ink);
    color: white;
}
  .menu .aktif { background:var(--g3); color:var(--g1); font-weight:600; }
  .menu .off { color:#9db5a8; border:1px dashed #cfe3d7; cursor:not-allowed; }
  .menu .logout { background:var(--ink); color:#fff; }
  
.hero {
    margin: 24px 6vw 0;
    padding: 28px 32px;
    border-radius: 24px;
    color: #ffffff !important;
    background: linear-gradient(
        135deg,
        #146c43,
        #198754 65%,
        #75bd8c
    ) !important;
    box-shadow: 0 5px 18px rgba(25, 135, 84, 0.12);
}

.hero h1 {
    margin: 0 0 8px;
    font-size: clamp(22px, 4vw, 32px);
    font-weight: 800;
    color: #ffffff !important;
}

.hero p {
    margin: 0;
    color: #ffffff !important;
    opacity: 0.95;
}

  main { padding:0 6vw 48px; }
  h2 { font-size:20px; margin:32px 0 14px; }
  .stats { display:grid; gap:14px; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); margin-top:24px; }
  .stat { background:#fff; border-radius:20px; padding:18px; box-shadow:0 4px 18px rgba(15,157,88,.08); }
  .stat .ic { font-size:22px; }
  .stat .n { font-size:30px; font-weight:800; color:var(--g1); margin-top:6px; }
  .meta { color:var(--muted); font-size:13px; }
  .cols { display:grid; gap:18px; grid-template-columns:1.6fr 1fr; align-items:start; }
  .panel { background:#fff; border-radius:20px; padding:20px; box-shadow:0 4px 18px rgba(15,157,88,.08); }
  .panel h2 { margin-top:0; }
  .scroll { overflow-x:auto; }
  table { width:100%; border-collapse:collapse; min-width:420px; }
  th { text-align:left; font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); padding:8px 6px; }
  td { padding:12px 6px; border-top:1px solid #edf4f0; font-size:14px; }
  .badge { font-size:12px; font-weight:600; padding:4px 10px; border-radius:999px; background:var(--g3); color:var(--g1); }
  .badge.draft { background:#fff3dc; color:#b27300; }
  .badge.archived { background:#eee; color:#666; }
  .item { padding:10px 0; border-top:1px solid #edf4f0; }
  .item:first-of-type { border-top:0; }
  .item b { font-size:14px; display:block; margin-bottom:2px; }
  .bar { height:8px; background:var(--g3); border-radius:999px; overflow:hidden; margin-top:6px; }
  .bar i { display:block; height:100%; background:linear-gradient(90deg,var(--g1),var(--g2)); border-radius:999px; }
  .kat { margin-bottom:14px; font-size:14px; }
  .kat div:first-child { display:flex; justify-content:space-between; }
  .empty { color:var(--muted); font-size:14px; }
  @media (max-width:820px){ .cols{ grid-template-columns:1fr; } }
</style>
</head>
<body>
<nav>
  <b>🌿 SehatIn Admin</b>

  <div class="menu">
    <a href="dashboard.php" class="aktif">Dashboard</a>
    <a href="articles.php">Artikel</a>
    <a href="categories.php">Kategori</a>
    <a href="tips.php">Tips Kesehatan</a>
    <a href="logout.php" class="logout">Logout</a>
  </div>
</nav>


<section class="hero">
  <h1>Halo, <?= $e($_SESSION['admin_username']) ?>! 👋</h1>
  <p>Ini ringkasan konten SehatIn hari ini.</p>
</section>

<main>
  <div class="stats">
    <?php foreach ($stat as [$ikon, $label, $n]): ?>
      <div class="stat">
        <div class="ic"><?= $ikon ?></div>
        <div class="n"><?= (int)$n ?></div>
        <div class="meta"><?= $e($label) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="cols">
    <div>
      <div class="panel" style="margin-top:32px">
        <h2>🆕 Artikel Terbaru</h2>
        <div class="scroll">
        <table>
          <tr><th>Judul</th><th>Kategori</th><th>Status</th><th>Views</th><th>Tanggal</th></tr>
          <?php if (!$terbaru): ?>
            <tr><td colspan="5" class="empty">Belum ada artikel.</td></tr>
          <?php else: foreach ($terbaru as $a): ?>
            <tr>
              <td><?= $e($a['title']) ?></td>
              <td><?= $e($a['kategori']) ?></td>
              <td><span class="badge <?= $e($a['status']) ?>"><?= $e($a['status']) ?></span></td>
              <td><?= (int)$a['views'] ?></td>
              <td><?= date('d M Y', strtotime($a['created_at'])) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </table>
        </div>
      </div>
    </div>

    <div>
      <div class="panel" style="margin-top:32px">
        <h2>⏳ Perlu Dicek (Draft)</h2>
        <?php if (!$draft): ?>
          <div class="empty">Tidak ada draft. Semua beres! 🎉</div>
        <?php else: foreach ($draft as $d): ?>
          <div class="item">
            <b><?= $e($d['title']) ?></b>
            <span class="meta"><?= $e($d['kategori']) ?> • <?= date('d M Y', strtotime($d['created_at'])) ?></span>
          </div>
        <?php endforeach; endif; ?>
      </div>

      <div class="panel" style="margin-top:18px">
        <h2>🗂️ Artikel per Kategori</h2>
        <?php if (!$per_kategori): ?>
          <div class="empty">Belum ada kategori.</div>
        <?php else: foreach ($per_kategori as $k): ?>
          <div class="kat">
            <div><span><?= $e($k['name']) ?></span><b><?= (int)$k['total'] ?></b></div>
            <div class="bar"><i style="width:<?= round(((int)$k['total'] / $maks) * 100) ?>%"></i></div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</main>
</body>
</html>