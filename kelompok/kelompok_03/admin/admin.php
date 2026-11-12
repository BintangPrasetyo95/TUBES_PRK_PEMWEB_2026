<?php
require_once __DIR__ . '/../includes/cek_login.php';

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak. Hanya admin yang dapat mengakses halaman ini.');
}

$pageTitle = 'Dashboard Admin - Sistem Antrian Klinik';
require_once '../includes/header.php';
?>

<div class="spasi-bawah">
    <h2 class="judul">Dashboard Admin</h2>
    <p class="muted small">Selamat datang, <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong>. Silakan pilih menu kelola di bawah ini.</p>
</div>

<div class="kisi">
    <div class="kartu">
        <div class="kartu-isi">
            <span class="lencana lencana-utama">Antrian Pasien</span>
            <h4 class="tebal">Daftar Antrian Hari Ini</h4>
            <p class="muted small">Pantau daftar antrian pasien yang masuk hari ini, lakukan panggil pasien atau selesaikan antrian.</p>
            <a href="antrian_list.php" class="tombol tombol-utama">Buka Antrian</a>
        </div>
    </div>
    <div class="kartu">
        <div class="kartu-isi">
            <span class="lencana lencana-utama">Data Dokter</span>
            <h4 class="tebal">Kelola Data Dokter</h4>
            <p class="muted small">Tambah dokter baru, ubah spesialisasi, atur kuota harian pasien, serta jadwal hari praktik.</p>
            <a href="dokter_list.php" class="tombol tombol-utama">Kelola Dokter</a>
        </div>
    </div>
</div>

<?php 
require_once '../includes/footer.php'; 
?>