<?php
// PJ-01: Dashboard Pengajar

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/fungsi/kuis.php';

// Hanya pengajar yang boleh mengakses halaman ini
wajibPeran(['pengajar']);

// Ambil ID dan nama pengajar yang sedang login dari session
$pengajarId   = (int) $_SESSION['user']['id'];
$namaPengajar = $_SESSION['user']['nama'] ?? 'Pengajar';

// Ambil semua data statistik dashboard dari fungsi ambilStatistikPengajar()
$statistik = ambilStatistikPengajar($pengajarId);

$jumlahStatus     = $statistik['jumlah_status'];
$riwayatKeputusan = $statistik['riwayat_keputusan'];
$totalPercobaan   = $statistik['total_percobaan'];
$rataRataPersen   = $statistik['rata_rata_persen'];

// Hitung total semua kuis milik pengajar ini
$totalKuis = $jumlahStatus['draft']
           + $jumlahStatus['menunggu']
           + $jumlahStatus['terbit']
           + $jumlahStatus['ditolak']
           + $jumlahStatus['ditarik'];


// Bagian Tampilan Halaman (HTML)
$judulHalaman = 'Dashboard Pengajar';
$halamanAktif = 'dashboard';
$root = '../';

// Muat template header panel
require_once __DIR__ . '/../includes/partials/header-panel.php';
?>

<div class="panel-header" style="margin-bottom: 24px;">
    <div>
        <h1 style="margin: 0 0 8px 0;">Halo, <?= e($namaPengajar) ?>! 👋</h1>
        <p class="text-secondary" style="margin: 0;">Selamat datang di panel pengajar DynaQuiz. Berikut ringkasan kuis Anda.</p>
    </div>
    <div style="margin-top: 12px; display: flex; flex-wrap: wrap; gap: 10px;">
        <a href="kuis-form.php" class="btn btn-primary">+ Buat Kuis Baru</a>
        <a href="kuis.php" class="btn btn-secondary">Kelola Kuis Saya</a>
    </div>
</div>

<!-- Ringkasan Statistik Percobaan Pelajar -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="card" style="padding: 20px;">
        <p class="text-secondary" style="margin: 0 0 8px 0; font-size: 14px;">Total Kuis Dibuat</p>
        <h2 style="margin: 0; font-size: 32px;"><?= e($totalKuis) ?></h2>
    </div>
    <div class="card" style="padding: 20px;">
        <p class="text-secondary" style="margin: 0 0 8px 0; font-size: 14px;">Total Percobaan Pelajar</p>
        <h2 style="margin: 0; font-size: 32px;"><?= e($totalPercobaan) ?></h2>
    </div>
    <div class="card" style="padding: 20px;">
        <p class="text-secondary" style="margin: 0 0 8px 0; font-size: 14px;">Rata-rata Skor Pelajar</p>
        <h2 style="margin: 0; font-size: 32px;"><?= e($rataRataPersen) ?>%</h2>
    </div>
</div>

<!-- Ringkasan Jumlah Kuis per Status -->
<div class="card" style="padding: 20px; margin-bottom: 24px;">
    <h2 style="margin: 0 0 16px 0; font-size: 18px;">Status Kuis Anda</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px;">
        
        <a href="kuis.php?status=draft" class="card" style="padding: 14px; text-decoration: none; color: inherit; text-align: center;">
            <span class="badge badge-neutral" style="margin-bottom: 8px;">Draft</span>
            <div style="font-size: 24px; font-weight: bold;"><?= e($jumlahStatus['draft']) ?></div>
        </a>

        <a href="kuis.php?status=menunggu" class="card" style="padding: 14px; text-decoration: none; color: inherit; text-align: center;">
            <span class="badge badge-warning" style="margin-bottom: 8px;">Menunggu</span>
            <div style="font-size: 24px; font-weight: bold;"><?= e($jumlahStatus['menunggu']) ?></div>
        </a>

        <a href="kuis.php?status=terbit" class="card" style="padding: 14px; text-decoration: none; color: inherit; text-align: center;">
            <span class="badge badge-success" style="margin-bottom: 8px;">Terbit</span>
            <div style="font-size: 24px; font-weight: bold;"><?= e($jumlahStatus['terbit']) ?></div>
        </a>

        <a href="kuis.php?status=ditolak" class="card" style="padding: 14px; text-decoration: none; color: inherit; text-align: center;">
            <span class="badge badge-danger" style="margin-bottom: 8px;">Ditolak</span>
            <div style="font-size: 24px; font-weight: bold;"><?= e($jumlahStatus['ditolak']) ?></div>
        </a>

        <a href="kuis.php?status=ditarik" class="card" style="padding: 14px; text-decoration: none; color: inherit; text-align: center;">
            <span class="badge badge-neutral" style="margin-bottom: 8px;">Ditarik</span>
            <div style="font-size: 24px; font-weight: bold;"><?= e($jumlahStatus['ditarik']) ?></div>
        </a>

    </div>
</div>

<!-- Daftar Hasil Persetujuan & Moderasi Terbaru dari Admin -->
<div class="card" style="padding: 20px;">
    <h2 style="margin: 0 0 16px 0; font-size: 18px;">Hasil Persetujuan &amp; Moderasi Terbaru</h2>

    <?php if (empty($riwayatKeputusan)): ?>
        <p class="text-secondary" style="margin: 0;">
            Belum ada keputusan persetujuan atau moderasi dari admin untuk kuis Anda.
        </p>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($riwayatKeputusan as $log): ?>
                <?php
                $badgeKelas = 'badge-neutral';
                $labelKeputusan = ucfirst($log['status_ke']);
                if ($log['status_ke'] === 'terbit') {
                    $badgeKelas = 'badge-success';
                    $labelKeputusan = 'Disetujui (Terbit)';
                } elseif ($log['status_ke'] === 'ditolak') {
                    $badgeKelas = 'badge-danger';
                    $labelKeputusan = 'Ditolak';
                } elseif ($log['status_ke'] === 'ditarik') {
                    $badgeKelas = 'badge-neutral';
                    $labelKeputusan = 'Ditarik Admin';
                }
                ?>
                <div style="border: 1px solid rgba(0,0,0,0.08); border-radius: var(--radius-s); padding: 14px;">
                    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 6px;">
                        <strong style="font-size: 15px;"><?= e($log['judul_kuis']) ?></strong>
                        <span class="badge <?= $badgeKelas ?>" style="font-size: 12px;">
                            <?= e($labelKeputusan) ?>
                        </span>
                    </div>

                    <div class="text-secondary" style="font-size: 12px; margin-bottom: 8px;">
                        Tanggal: <?= e($log['dibuat_pada']) ?>
                    </div>

                    <?php if (!empty($log['catatan'])): ?>
                        <div class="<?= $log['status_ke'] === 'terbit' ? 'alert alert-sukses' : 'alert alert-galat' ?>" style="font-size: 13px; padding: 10px; margin: 0 0 8px 0;">
                            <strong>Catatan Admin:</strong> <?= e($log['catatan']) ?>
                        </div>
                    <?php endif; ?>

                    <div>
                        <?php if ($log['status_ke'] === 'terbit'): ?>
                            <a href="hasil.php?kuis_id=<?= e($log['kuis_id']) ?>" style="font-size: 13px;">Lihat Hasil Kuis &rarr;</a>
                        <?php else: ?>
                            <a href="kuis.php?status=<?= e($log['status_ke']) ?>" style="font-size: 13px;">Buka di Kuis Saya untuk Revisi &rarr;</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Muat template footer panel
require_once __DIR__ . '/../includes/partials/footer-panel.php';
