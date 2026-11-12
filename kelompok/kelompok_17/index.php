  <?php
  require_once __DIR__ . '/config/database.php';
  include_once __DIR__ . '/components/header.php';

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

  function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
  $ticker = ['Transparansi Informasi', 'Suara Aspirasi Mahasiswa', 'Respon Terintegrasi', 'Layanan Publikasi Resmi', 'FISIP Universitas Lampung'];
  ?>

  <style>
  :root { 
    --ink: #141414; 
    --paper: #F6F2E9; 
    --card: #FFFDF8; 
    --or: #FF5A1F; 
    --or-soft: #FFD9C7; 
    --muted: #6B655B; 
    --border-line: #141414;
  }

  .kb * { box-sizing: border-box; }
  .kb { 
    font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
    color: var(--ink); 
    background: var(--paper); 
    overflow-x: hidden; 
  }

  .kb a { color: inherit; text-decoration: none; }
  .kb a:focus-visible, .kb button:focus-visible { outline: 2px solid var(--or); outline-offset: 2px; }

  .clamp2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
  .clamp3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
  .hide { display: none !important; }

  /* Clean Border Card */
  .ed-card {
    background: var(--card);
    border: 1px solid var(--border-line);
    transition: border-color 0.2s ease, transform 0.2s ease;
  }
  .ed-card:hover {
    border-color: var(--or);
  }
  .ed-card:hover .ttl-card {
    color: var(--or);
  }

  /* Badge Kategori */
  .ed-tag {
    display: inline-block;
    font-weight: 700;
    font-size: 0.68rem;
    padding: 0.2rem 0.55rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border: 1px solid var(--border-line);
    background: var(--card);
    color: var(--ink);
    border-radius: 4px;
  }
  .ed-tag-or {
    background: var(--or);
    color: #ffffff;
    border-color: var(--or);
  }

  /* Tombol Standard */
  .btn-ed {
    display: inline-block;
    font-weight: 700;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    padding: 0.75rem 1.4rem;
    border: 1px solid var(--border-line);
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.2s ease;
  }
  .btn-ed-or { background: var(--or); color: #ffffff !important; border-color: var(--or); }
  .btn-ed-or:hover { background: var(--ink); border-color: var(--ink); }
  .btn-ed-light { background: var(--card); color: var(--ink) !important; }
  .btn-ed-light:hover { background: var(--or-soft); }
  .btn-ed-ink { background: var(--ink); color: var(--paper) !important; }
  .btn-ed-ink:hover { background: var(--or); border-color: var(--or); }

  /* Hero Header */
  .hero-title {
    font-size: clamp(2.8rem, 7vw, 5.2rem);
    line-height: 1.05;
    font-weight: 800;
    letter-spacing: -0.03em;
  }
  .hero-title span {
    color: var(--or);
  }

  /* Ticker running line */
  .ticker { border-top: 1px solid var(--border-line); border-bottom: 1px solid var(--border-line); background: var(--card); color: var(--ink); overflow: hidden; white-space: nowrap; }
  .ticker-track { display: inline-flex; gap: 2.5rem; padding: 0.75rem 0; animation: marq 30s linear infinite; }
  .ticker-track span { font-weight: 600; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 2.5rem; text-transform: uppercase; letter-spacing: 0.04em; }
  .ticker-track span::after { content: "•"; color: var(--or); }
  @keyframes marq { to { transform: translateX(-50%); } }

  /* Section Header */
  .sec-title {
    font-size: clamp(1.8rem, 4vw, 2.6rem);
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -0.02em;
  }
  .sec-title span {
    font-weight: 400;
    color: var(--muted);
  }

  /* Filter Chips */
  .chip {
    font-weight: 700;
    font-size: 0.7rem;
    text-transform: uppercase;
    border: 1px solid var(--border-line);
    background: var(--card);
    padding: 0.4rem 0.85rem;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.15s;
  }
  .chip:hover { background: var(--or-soft); }
  .chip[aria-pressed="true"] { background: var(--ink); color: var(--paper); }

  .more-btn { background: none; border: 0; padding: 0; font-weight: 700; font-size: 0.75rem; text-decoration: underline; cursor: pointer; color: var(--ink); }
  </style>

  <div class="kb">

  <!-- ===== HERO SECTION ===== -->
  <section class="max-w-6xl mx-auto px-4 pt-14 pb-16 md:pt-20 grid lg:grid-cols-12 gap-12 items-center">
    <div class="lg:col-span-7">
      <div class="text-xs uppercase font-bold text-slate-500 mb-3 tracking-wider">Portal Resmi FISIP Unila</div>
      <h1 class="hero-title">
        Suara <span>Kritis.</span><br>
        Kabar Terbuka.
      </h1>
      <p class="mt-6 max-w-md text-slate-700 leading-relaxed text-base font-normal">
        Wadah resmi civitas akademika untuk menyampaikan aspirasi dan mengakses publikasi berita kampus secara transparan dan terintegrasi.
      </p>
      <div class="mt-8 flex flex-wrap gap-4">
        <a href="user/dashboard.php" class="btn-ed btn-ed-or">Sampaikan Aspirasi &rarr;</a>
        <a href="#aspirasi" class="btn-ed btn-ed-light">Pantau Isu Kampus</a>
      </div>
    </div>

    <div class="lg:col-span-5">
      <?php if ($heroBerita): ?>
        <a href="berita_detail.php?id=<?= (int)$heroBerita['id_berita'] ?>" class="ed-card block p-7 rounded-xl">
          <div class="flex justify-between items-center mb-4 border-b border-stone-200 pb-3">
            <span class="ed-tag ed-tag-or"><?= e($heroBerita['nama_kategori'] ?? 'Umum') ?></span>
            <span class="text-xs text-stone-500 font-medium"><?= date('d M Y', strtotime($heroBerita['tanggal_publikasi'])) ?></span>
          </div>
          <h2 class="ttl-card text-2xl font-bold leading-snug clamp3 mb-3 transition-colors"><?= e($heroBerita['judul_berita']) ?></h2>
          <p class="text-xs text-stone-600 leading-relaxed clamp3 mb-6"><?= e(strip_tags($heroBerita['isi_berita'])) ?></p>
          <div class="text-xs flex justify-between items-center pt-3 border-t border-stone-200">
            <span class="text-stone-500 font-medium">Berita Terbaru</span>
            <b style="color:var(--or)">Baca Selengkapnya &rarr;</b>
          </div>
        </a>
      <?php else: ?>
        <div class="ed-card p-6 text-center text-xs text-stone-500 rounded-xl">Belum ada berita yang diterbitkan.</div>
      <?php endif; ?>
    </div>
  </section>

  <!-- ===== RUNNING TICKER ===== -->
  <div class="ticker" aria-hidden="true">
    <div class="ticker-track">
      <?php for ($r = 0; $r < 4; $r++) foreach ($ticker as $t): ?><span><?= e($t) ?></span><?php endforeach; ?>
    </div>
  </div>

  <main class="max-w-6xl mx-auto px-4 py-16 space-y-20">

    <!-- ===== BERITA ===== -->
    <section id="berita" class="scroll-mt-6">
      <div class="flex justify-between items-end mb-8 flex-wrap gap-4 border-b border-stone-300 pb-4">
        <div>
          <h2 class="sec-title">Kabar Kilat <span><?= e($singkatan) ?></span></h2>
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
        <div class="ed-card p-10 text-center text-xs text-stone-500 rounded-xl">Belum ada publikasi berita lainnya.</div>
      <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <?php foreach ($daftarBerita as $berita): ?>
            <a href="berita_detail.php?id=<?= (int)$berita['id_berita'] ?>"
              class="ed-card p-6 flex flex-col gap-4 item rounded-xl" data-group="berita" data-val="<?= e($berita['nama_kategori'] ?? 'Umum') ?>">
              <div class="flex justify-between items-center border-b border-stone-200 pb-2">
                <span class="text-xs font-semibold text-stone-400">BERITA</span>
                <span class="ed-tag"><?= e($berita['nama_kategori'] ?? 'Umum') ?></span>
              </div>
              <h3 class="ttl-card text-lg font-bold leading-snug clamp2 transition-colors"><?= e($berita['judul_berita']) ?></h3>
              <p class="text-xs text-stone-600 leading-relaxed clamp3"><?= e(strip_tags($berita['isi_berita'])) ?></p>
              <div class="text-xs flex justify-between mt-auto pt-3 border-t border-stone-200">
                <span class="text-stone-500 font-medium"><?= date('d/m/Y', strtotime($berita['tanggal_publikasi'])) ?></span>
                <span class="font-bold" style="color:var(--or)">&rarr;</span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- ===== ASPIRASI ===== -->
    <section id="aspirasi" class="scroll-mt-6">
      <div class="flex justify-between items-end mb-8 flex-wrap gap-4 border-b border-stone-300 pb-4">
        <div>
          <h2 class="sec-title">Daftar Aspirasi <span>Mahasiswa</span></h2>
        </div>
        <a href="user/dashboard.php" class="btn-ed btn-ed-ink">+ Buat Laporan</a>
      </div>

      <?php if (empty($aspirasiList)): ?>
        <div class="ed-card p-10 text-center text-xs text-stone-500 rounded-xl">Belum ada data aspirasi yang masuk. Silakan sampaikan aspirasi pertama Anda.</div>
      <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <?php foreach ($aspirasiList as $aspirasi):
              $status = $aspirasi['status'];
              $isi    = strip_tags($aspirasi['isi_aspirasi']);
              $idAsp  = (int)($aspirasi['id_aspirasi'] ?? 0);
          ?>
            <article class="ed-card p-6 flex flex-col gap-4 item rounded-xl" data-group="asp" data-val="<?= e($status) ?>">
              <div class="flex flex-wrap items-center gap-2 border-b border-stone-200 pb-3">
                <span class="ed-tag"><?= e($aspirasi['nama_kategori'] ?? 'Umum') ?></span>
                <span class="text-xs font-semibold text-stone-700"><?= $aspirasi['is_anonim'] ? '👤 Anonim' : '🎓 ' . e($aspirasi['nama_lengkap'] ?? 'Mahasiswa') ?></span>
                <span class="text-xs ml-auto text-stone-500"><?= date('d/m/Y', strtotime($aspirasi['tanggal_kirim'])) ?></span>
              </div>

              <div class="flex-1">
                <h3 class="ttl-card text-lg font-bold leading-snug mb-2 clamp2">
                  <a href="aspirasi_detail.php?id=<?= $idAsp ?>"><?= e($aspirasi['judul_aspirasi']) ?></a>
                </h3>
                <p class="text-xs text-stone-600 leading-relaxed clamp3 body"><?= e($isi) ?></p>
                <?php if (mb_strlen($isi) > 140): ?>
                    <button type="button" class="more-btn mt-2" aria-expanded="false">Tampilkan Selengkapnya</button>
                <?php endif; ?>
              </div>

              <div class="text-xs flex justify-end pt-3 border-t border-stone-200">
                  <a href="aspirasi_detail.php?id=<?= $idAsp ?>" class="font-bold" style="color:var(--or)">Lihat Detail &rarr;</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- ===== CTA BANNER ===== -->
    <section class="ed-card p-8 md:p-12 rounded-xl" style="background:var(--or); border-color:var(--or);">
      <h2 class="text-2xl md:text-4xl font-extrabold leading-tight max-w-2xl text-white">Ingin Menyampaikan Aspirasi atau Pengaduan Kampus?</h2>
      <p class="mt-3 max-w-lg text-amber-100 text-sm leading-relaxed">Sampaikan masukan Anda secara rinci. Laporan dapat disampaikan dengan nama terang maupun secara anonim, dan status tindak lanjutnya dapat dipantau berkala.</p>
      <div class="mt-8 flex flex-wrap gap-4">
        <a href="user/dashboard.php" class="btn-ed btn-ed-ink">Sampaikan Aspirasi Sekarang &rarr;</a>
        <a href="#berita" class="btn-ed btn-ed-light">Pelajari Berita Kampus</a>
      </div>
    </section>

  </main>
  </div>

  <script>
  (function () {
    // Filter generik kategori
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
        });
      });
    });

    // Buka/tutup ringkasan isi aspirasi
    document.querySelectorAll('.more-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const body = btn.parentElement.querySelector('.body');
        const open = !body.classList.toggle('clamp3');
        btn.setAttribute('aria-expanded', open);
        btn.textContent = open ? 'Ringkas' : 'Tampilkan Selengkapnya';
      });
    });
  })();
  </script>

  <?php include_once __DIR__ . '/components/footer.php'; ?>