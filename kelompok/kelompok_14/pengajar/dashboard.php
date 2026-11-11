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

// Muat template header panel (sidebar kiri)
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
