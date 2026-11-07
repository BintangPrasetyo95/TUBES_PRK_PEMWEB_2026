<?php
require_once __DIR__ . '/config/database.php';
include_once __DIR__ . '/includes/header.php';

// ===== PENGATURAN KAMPUS =====
$namaPortal = 'KabarSi FISIP';
$namaKampus = 'Universitas Lampung';
$singkatan  = 'FISIP';

// ===== DATA BERITA =====
$stmt = $pdo->prepare("SELECT b.*, k.nama_kategori
        FROM berita b
        LEFT JOIN kategori k ON b.id_kategori = k.id_kategori
        ORDER BY b.tanggal_publikasi DESC
        LIMIT 7");
$stmt->execute();
$beritaList   = $stmt->fetchAll(PDO::FETCH_ASSOC);
$heroBerita   = $beritaList[0] ?? null;
$daftarBerita = array_slice($beritaList, 1);

// Kategori unik untuk filter berita
$kategoriBerita = [];
foreach ($daftarBerita as $b) { $kategoriBerita[$b['nama_kategori'] ?? 'Umum'] = true; }
$kategoriBerita = array_keys($kategoriBerita);

// ===== DATA ASPIRASI =====
$stmt = $pdo->prepare("SELECT a.*, k.nama_kategori, u.nama_lengkap
        FROM aspirasi a
        LEFT JOIN kategori k ON a.id_kategori = k.id_kategori
        LEFT JOIN users u ON a.id_user = u.id_user
        ORDER BY a.tanggal_kirim DESC
        LIMIT 6");
$stmt->execute();
$aspirasiList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== STATISTIK =====
$totalAspirasi = (int) $pdo->query("SELECT COUNT(*) FROM aspirasi")->fetchColumn();
$totalSelesai  = (int) $pdo->query("SELECT COUNT(*) FROM aspirasi WHERE status = 'selesai'")->fetchColumn();
$totalDitinjau = (int) $pdo->query("SELECT COUNT(*) FROM aspirasi WHERE status = 'ditinjau'")->fetchColumn();
$totalBerita   = (int) $pdo->query("SELECT COUNT(*) FROM berita")->fetchColumn();

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$steps  = ['menunggu' => 'Terkirim', 'ditinjau' => 'Ditinjau', 'selesai' => 'Selesai'];
$ticker = ['Transparansi Informasi', 'Suara Aspirasi Mahasiswa', 'Respon Terintegrasi', 'Layanan Publikasi Resmi', 'FISIP Universitas Lampung'];
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=Space+Mono:wght@400;700&display=swap');

:root{ --ink:#141414; --paper:#F6F2E9; --card:#FFFDF8; --or:#FF5A1F; --or-soft:#FFD9C7; --hl:#FFE24A; --muted:#6B655B; }
.kb *{box-sizing:border-box}
.kb{font-family:'Space Grotesk',system-ui,sans-serif;color:var(--ink);background:var(--paper);overflow-x:hidden}
.kb .mono{font-family:'Space Mono',ui-monospace,monospace}
.kb h1,.kb h2,.kb h3{letter-spacing:-.03em}
.kb a{color:inherit;text-decoration:none}
.kb a:focus-visible,.kb button:focus-visible{outline:3px solid var(--or);outline-offset:3px}
.clamp2{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.clamp3{display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
.hide{display:none !important}
.eyebrow{font:700 .72rem 'Space Mono',monospace;color:var(--muted);margin-bottom:.6rem;letter-spacing:0.04em}

/* Kotak bergaya cetak Neo-Brutalism */
.box{background:var(--card);border:2px solid var(--ink);box-shadow:6px 6px 0 var(--ink);transition:transform .15s,box-shadow .15s;position:relative}
.box.hov:hover{transform:translate(-3px,-3px);box-shadow:9px 9px 0 var(--or)}
.box.hov:hover .ttl{color:var(--or)}
.box.hov:hover .num{color:var(--or)}
.tag{display:inline-block;background:var(--card);color:var(--ink);border:2px solid var(--ink);font:700 .65rem 'Space Mono',monospace;padding:.12rem .5rem;text-transform:uppercase;letter-spacing:.04em}
.tag-or{background:var(--or);color:#fff}
.tape{position:absolute;top:-11px;left:46%;width:72px;height:20px;background:var(--hl);opacity:.9;transform:rotate(-3deg);pointer-events:none}

/* Tombol dengan Teks Terang Presisi */
.btn{display:inline-block;font:700 .8rem 'Space Mono',monospace;text-transform:uppercase;letter-spacing:.04em;padding:.85rem 1.3rem;border:2px solid var(--ink);cursor:pointer;transition:transform .15s,box-shadow .15s;box-shadow:4px 4px 0 var(--ink)}
.btn:hover{transform:translate(-2px,-2px);box-shadow:6px 6px 0 var(--or)}
.btn:active{transform:translate(2px,2px);box-shadow:0 0 0 var(--ink)}
.btn-or{background:var(--or);color:#ffffff !important;} 
.btn-light{background:var(--card);color:var(--ink) !important;} 
.btn-ink{background:var(--ink);color:var(--paper) !important;}

/* Hero (Coretan SVG Presisi) */
.hero-h{font-size:clamp(3rem,8.5vw,6.2rem);line-height:.95;font-weight:700}
.hero-h .soft{color:#8a857b;display:block}
.scribble{position:relative;display:inline-block;z-index:1}
.scribble svg{position:absolute;left:-5%;top:-10%;width:110%;height:120%;pointer-events:none;overflow:visible;z-index:-1}
.scribble path{fill:none;stroke:var(--or);stroke-width:4.5;stroke-linecap:round;stroke-dasharray:700;stroke-dashoffset:700;animation:draw 1.1s .5s ease-out forwards}
@keyframes draw{to{stroke-dashoffset:0}}
.hero-card{transform:rotate(1.5deg)}

/* Running Ticker */
.ticker{background:var(--ink);color:var(--paper);overflow:hidden;white-space:nowrap}
.ticker-track{display:inline-flex;gap:2.2rem;padding:.9rem 0;animation:marq 28s linear infinite}
.ticker:hover .ticker-track{animation-play-state:paused}
.ticker-track span{font:400 .85rem 'Space Mono',monospace;display:inline-flex;align-items:center;gap:2.2rem}
.ticker-track span::after{content:"";width:8px;height:8px;border-radius:50%;background:var(--or)}
@keyframes marq{to{transform:translateX(-50%)}}

/* Panel Statistik Gelap */
.stats{background:var(--ink);color:var(--paper);border:2px solid var(--ink);box-shadow:8px 8px 0 var(--or);padding:1.6rem 2rem}
.stats b{font-size:clamp(2.2rem,5vw,3.4rem);line-height:1;font-weight:700;display:block}
.stats b i{font-style:normal;color:var(--or)}
.stats small{font:400 .68rem 'Space Mono',monospace;text-transform:uppercase;color:#a8a296;display:block;margin-top:.5rem}

/* Judul Section */
.sec-h{font-size:clamp(1.9rem,4.5vw,3rem);font-weight:700;line-height:1}
.sec-h span{color:#8a857b}

/* Chip Filter */
.chip{font:700 .7rem 'Space Mono',monospace;text-transform:uppercase;border:2px solid var(--ink);background:var(--card);padding:.4rem .8rem;cursor:pointer;transition:background .15s}
.chip:hover{background:var(--or-soft)}
.chip[aria-pressed="true"]{background:var(--ink);color:var(--paper)}

/* Berita Bernomor */
.num{font:400 1.7rem 'Space Grotesk',sans-serif;color:var(--or);transition:color .15s}
.hov .go{transition:transform .15s}
.hov:hover .go{transform:translateX(5px)}

/* Status Aspirasi */
.st-menunggu{background:var(--hl)} .st-ditinjau{background:var(--or);color:#fff} .st-selesai{background:#9BE7A9} .st-ditolak{background:#FF9A94}
.prog{display:flex;align-items:center;gap:.3rem;font:400 .66rem 'Space Mono',monospace;color:var(--muted)}
.prog .d{width:11px;height:11px;border:2px solid var(--ink);background:var(--card);flex:none}
.prog .l{height:2px;flex:1;background:var(--ink);opacity:.25}
.prog .on{background:var(--or)} .prog .l.on{opacity:1}
.prog .now{color:var(--ink);font-weight:700}
.more{background:none;border:0;padding:0;font:700 .72rem 'Space Mono',monospace;text-decoration:underline;cursor:pointer;color:var(--ink)}

@media (prefers-reduced-motion:reduce){
  .ticker-track{animation:none}
  .scribble path{animation:none;stroke-dashoffset:0}
  .box,.btn,.go{transition:none}
}
</style>

<div class="kb">

<!-- ===== HERO ===== -->
<section class="max-w-6xl mx-auto px-4 pt-12 pb-16 md:pt-20 grid lg:grid-cols-12 gap-12 items-center">
  <div class="lg:col-span-7">
    <h1 class="hero-h">
      Suara
      <span class="scribble">Kritis.
        <svg viewBox="0 0 300 90" preserveAspectRatio="none" aria-hidden="true">
          <path d="M 15 50 C 10 20, 130 8, 235 15 C 285 22, 290 60, 205 74 C 120 84, 15 75, 25 45 C 32 25, 95 18, 160 20"/>
        </svg>
      </span>
      <span class="soft">Kabar Terbuka.</span>
    </h1>
    <p class="mt-8 max-w-md text-slate-700 leading-relaxed font-normal">
      Wadah resmi civitas akademika untuk menyampaikan aspirasi dan mengakses publikasi berita kampus.
      <b>Pengiriman anonim dapat dilakukan secara aman dan transparan.</b>
    </p>
    <div class="mt-8 flex flex-wrap gap-4">
      <a href="aspirasi_detail.php" class="btn btn-or">Sampaikan Aspirasi &rarr;</a>
      <a href="#aspirasi" class="btn btn-light">Pantau Isu Kampus</a>
    </div>
  </div>

  <div class="lg:col-span-5">
    <?php if ($heroBerita): ?>
      <a href="berita_detail.php?id=<?= (int)$heroBerita['id_berita'] ?>" class="box hov hero-card block p-6">
        <span class="tape" aria-hidden="true"></span>
        <div class="flex justify-between items-center mb-4">
          <span class="tag tag-or"><?= e($heroBerita['nama_kategori'] ?? 'Umum') ?></span>
          <span class="mono text-xs" style="color:var(--muted)"><?= date('d/m/Y', strtotime($heroBerita['tanggal_publikasi'])) ?></span>
        </div>
        <h2 class="ttl text-2xl font-bold leading-tight clamp3 mb-2"><?= e($heroBerita['judul_berita']) ?></h2>
        <p class="text-sm mt-3 clamp3" style="color:var(--muted)"><?= e(strip_tags($heroBerita['isi_berita'])) ?></p>
        <div class="mono text-xs mt-5 flex justify-between items-center pt-3 border-t-2 border-dashed border-slate-900">
          <span><?= e($heroBerita['nama_kategori'] ?? 'Umum') ?></span>
          <b style="color:var(--or)">Baca Selengkapnya <span class="go inline-block">&rarr;</span></b>
        </div>
      </a>
    <?php else: ?>
      <div class="box hero-card p-6 text-center" style="color:var(--muted)">Belum ada berita yang diterbitkan.</div>
    <?php endif; ?>
  </div>
</section>

<!-- ===== TICKER ===== -->
<div class="ticker" aria-hidden="true">
  <div class="ticker-track">
    <?php for ($r = 0; $r < 4; $r++) foreach ($ticker as $t): ?><span><?= e($t) ?></span><?php endforeach; ?>
  </div>
</div>

<main class="max-w-6xl mx-auto px-4 py-16 space-y-24">

  <!-- ===== STATISTIK ===== -->
  <section class="stats" aria-label="Ringkasan Data">
    <div class="flex justify-between items-center mb-6">
      <span class="mono text-xs font-bold" style="color:var(--or)">&#9679; DATA REAL-TIME</span>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
      <div><b data-count="<?= $totalAspirasi ?>">0</b><small>Aspirasi Diterima</small></div>
      <div><b data-count="<?= $totalSelesai ?>">0</b><small>Telah Ditindak</small></div>
      <div><b data-count="<?= $totalDitinjau ?>">0</b><small>Dalam Proses Tinjauan</small></div>
      <div><b data-count="<?= $totalBerita ?>">0</b><small>Berita Terbit</small></div>
    </div>
  </section>

  <!-- ===== BERITA ===== -->
  <section id="berita" class="scroll-mt-6">
    <div class="flex justify-between items-end mb-8 flex-wrap gap-4">
      <div>
        <h2 class="sec-h">Kabar Kilat <span><?= e($singkatan) ?>.</span></h2>
      </div>
      <?php if (count($kategoriBerita) > 1): ?>
        <div class="flex flex-wrap gap-2" role="group" aria-label="Filter kategori berita" data-group="berita">
          <button class="chip" data-val="all" aria-pressed="true">Semua Kategori</button>
          <?php foreach ($kategoriBerita as $kat): ?>
            <button class="chip" data-val="<?= e($kat) ?>" aria-pressed="false"><?= e($kat) ?></button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <?php if (empty($daftarBerita)): ?>
      <div class="box p-10 text-center" style="color:var(--muted)">Belum ada publikasi berita lainnya.</div>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-7">
        <?php foreach ($daftarBerita as $berita): ?>
          <a href="berita_detail.php?id=<?= (int)$berita['id_berita'] ?>"
             class="box hov p-5 flex flex-col gap-3 item" data-group="berita" data-val="<?= e($berita['nama_kategori'] ?? 'Umum') ?>">
            <div class="flex justify-between items-start">
              <span class="num">00</span>
              <span class="tag"><?= e($berita['nama_kategori'] ?? 'Umum') ?></span>
            </div>
            <h3 class="ttl text-xl font-bold leading-snug clamp2 transition"><?= e($berita['judul_berita']) ?></h3>
            <p class="text-sm clamp3" style="color:var(--muted)"><?= e(strip_tags($berita['isi_berita'])) ?></p>
            <div class="mono text-xs flex justify-between mt-auto pt-3" style="border-top:2px dashed var(--ink)">
              <span style="color:var(--muted)"><?= date('d/m/Y', strtotime($berita['tanggal_publikasi'])) ?></span>
              <span class="go font-bold">&rarr;</span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- ===== ASPIRASI ===== -->
  <section id="aspirasi" class="scroll-mt-6">
    <div class="flex justify-between items-end mb-6 flex-wrap gap-4">
      <div>
        <h2 class="sec-h">Daftar Aspirasi <span>Mahasiswa.</span></h2>
      </div>
      <a href="aspirasi_detail.php" class="btn btn-ink">+ Buat Laporan</a>
    </div>

    <?php if (empty($aspirasiList)): ?>
      <div class="box p-10 text-center" style="color:var(--muted)">Belum ada data aspirasi yang masuk. Silakan sampaikan aspirasi pertama Anda.</div>
    <?php else: ?>
      <div class="flex flex-wrap gap-2 mb-8" role="group" aria-label="Filter status aspirasi" data-group="asp">
        <button class="chip" data-val="all" aria-pressed="true">Semua Status</button>
        <?php foreach ($steps as $key => $label): ?>
          <button class="chip" data-val="<?= $key ?>" aria-pressed="false"><?= e($label) ?></button>
        <?php endforeach; ?>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <?php foreach ($aspirasiList as $aspirasi):
            $status = $aspirasi['status'];
            $isi    = strip_tags($aspirasi['isi_aspirasi']);
            $idxNow = array_search($status, array_keys($steps));
        ?>
          <article class="box hov p-5 pt-6 flex flex-col gap-4 item" data-group="asp" data-val="<?= e($status) ?>">
            <span class="tape" aria-hidden="true"></span>
            <div class="flex flex-wrap items-center gap-2">
              <span class="tag st-<?= e($status) ?>"><?= e(strtoupper($status)) ?></span>
              <span class="tag"><?= e($aspirasi['nama_kategori'] ?? 'Umum') ?></span>
              <span class="mono text-xs font-bold"><?= $aspirasi['is_anonim'] ? '👤 Anonim' : '🎓 ' . e($aspirasi['nama_lengkap'] ?? 'Mahasiswa') ?></span>
              <span class="mono text-xs ml-auto" style="color:var(--muted)"><?= date('d/m/Y', strtotime($aspirasi['tanggal_kirim'])) ?></span>
            </div>

            <div class="flex-1">
              <h3 class="ttl text-xl font-bold leading-snug mb-2 clamp2">
                <a href="aspirasi_detail.php?id=<?= (int)$aspirasi['id_aspirasi'] ?>"><?= e($aspirasi['judul_aspirasi']) ?></a>
              </h3>
              <p class="text-sm clamp3 body" style="color:var(--muted)"><?= e($isi) ?></p>
              <?php if (mb_strlen($isi) > 140): ?>
                <button type="button" class="more mt-2" aria-expanded="false">Tampilkan Selengkapnya</button>
              <?php endif; ?>
            </div>

            <?php if ($idxNow !== false): ?>
              <div class="prog" aria-label="Progres: <?= e($steps[$status]) ?>">
                <?php $i = 0; foreach ($steps as $label): ?>
                  <?php if ($i > 0): ?><span class="l <?= $i <= $idxNow ? 'on' : '' ?>"></span><?php endif; ?>
                  <span class="d <?= $i <= $idxNow ? 'on' : '' ?>"></span>
                  <span class="<?= $i === $idxNow ? 'now' : '' ?>"><?= e($label) ?></span>
                <?php $i++; endforeach; ?>
              </div>
            <?php endif; ?>

            <div class="mono text-xs flex justify-between pt-3" style="border-top:2px dashed var(--ink)">
              <span style="color:var(--muted)">ID: ASP-<?= (int)$aspirasi['id_aspirasi'] ?></span>
              <a href="aspirasi_detail.php?id=<?= (int)$aspirasi['id_aspirasi'] ?>" class="font-bold" style="color:var(--or)">Rincian Status <span class="go inline-block">&rarr;</span></a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <p id="empty-asp" class="hide box p-8 text-center mt-6" style="color:var(--muted)">Belum ada aspirasi dengan status ini.</p>
    <?php endif; ?>
  </section>

  <!-- ===== CTA ===== -->
  <section class="box p-8 md:p-12" style="background:var(--or)">
    <h2 class="text-3xl md:text-5xl font-bold leading-none max-w-2xl text-white">Ingin Menyampaikan Aspirasi atau Pengaduan Kampus?</h2>
    <p class="mt-4 max-w-lg text-amber-100">Sampaikan masukan Anda secara rinci. Laporan dapat disampaikan dengan nama terang maupun secara anonim, dan status tindak lanjutnya dapat dipantau berkala.</p>
    <div class="mt-7 flex flex-wrap gap-4">
      <a href="aspirasi_detail.php" class="btn btn-ink">Sampaikan Aspirasi Sekarang &rarr;</a>
      <a href="#berita" class="btn btn-light">Pelajari Berita Kampus</a>
    </div>
  </section>

</main>
</div>

<script>
(function () {
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Hitung naik angka statistik
  document.querySelectorAll('[data-count]').forEach(function (el) {
    const target = parseInt(el.dataset.count, 10) || 0;
    if (reduce || !target) { el.textContent = target; return; }
    const t0 = performance.now(), dur = 900;
    (function tick(t) {
      const p = Math.min((t - t0) / dur, 1);
      el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3)));
      if (p < 1) requestAnimationFrame(tick);
    })(t0);
  });

  // Nomor berita (01, 02, ...)
  function renumber() {
    let n = 0;
    document.querySelectorAll('.item[data-group="berita"]').forEach(function (it) {
      if (it.classList.contains('hide')) return;
      it.querySelector('.num').textContent = String(++n).padStart(2, '0');
    });
  }
  renumber();

  // Filter generik
  document.querySelectorAll('[data-group][role="group"]').forEach(function (bar) {
    const group = bar.dataset.group;
    const chips = bar.querySelectorAll('.chip');
    chips.forEach(function (chip) {
      chip.addEventListener('click', function () {
        const val = chip.dataset.val;
        let shown = 0;
        chips.forEach(function (c) { c.setAttribute('aria-pressed', c === chip); });
        document.querySelectorAll('.item[data-group="' + group + '"]').forEach(function (it) {
          const ok = val === 'all' || it.dataset.val === val;
          it.classList.toggle('hide', !ok);
          if (ok) shown++;
        });
        const empty = document.getElementById('empty-' + group);
        if (empty) empty.classList.toggle('hide', shown > 0);
        if (group === 'berita') renumber();
      });
    });
  });

  // Buka/tutup isi aspirasi
  document.querySelectorAll('.more').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const body = btn.parentElement.querySelector('.body');
      const open = !body.classList.toggle('clamp3');
      btn.setAttribute('aria-expanded', open);
      btn.textContent = open ? 'Ringkas' : 'Tampilkan Selengkapnya';
    });
  });
})();
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>