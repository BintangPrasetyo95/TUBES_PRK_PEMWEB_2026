<?php
// PJ-02: Kuis Saya (Milik Pengajar)

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/fungsi/kuis.php';

// Proteksi halaman: hanya pengajar yang boleh masuk (AUT-05)
wajibPeran(['pengajar']);

// Ambil ID pengajar yang sedang login
$pengajarId = (int) $_SESSION['user']['id'];

// Ambil parameter filter status dan kata kunci pencarian dari URL (GET)
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
$kataKunci    = isset($_GET['cari']) ? trim($_GET['cari']) : '';

// Validasi status filter agar sesuai dengan enum status kuis
$daftarStatusValid = ['draft', 'menunggu', 'terbit', 'ditolak', 'ditarik'];
if ($statusFilter !== '' && !in_array($statusFilter, $daftarStatusValid, true)) {
    $statusFilter = '';
}

// Ambil daftar kuis milik pengajar menggunakan fungsi yang sudah dibuat di Sesi 2
$daftarKuis = daftarKuisPengajar($pengajarId, $statusFilter, $kataKunci);
