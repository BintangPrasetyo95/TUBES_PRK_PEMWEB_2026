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

// Ambil daftar kuis milik pengajar menggunakan fungsi yang sudah dibuat
$daftarKuis = daftarKuisPengajar($pengajarId, $statusFilter, $kataKunci);

// Pesan notifikasi error/sukses
$pesanError = '';

// Pemrosesan aksi saat ada form POST yang dikirim (Tarik ke Draft, Buka Revisi, Hapus)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifikasiCsrf();

    $aksi   = isset($_POST['aksi']) ? trim($_POST['aksi']) : '';
    $kuisId = isset($_POST['kuis_id']) ? (int) $_POST['kuis_id'] : 0;

    // Pastikan kuis yang dipilih ada dan benar milik pengajar yang sedang login
    $kuis = ambilKuisById($kuisId, $pengajarId);

    if (!$kuis) {
        $pesanError = 'Kuis tidak ditemukan atau Anda tidak memiliki akses.';
    } else {
        $hasil = null;
        $pesanSukses = '';

        // Aksi 1: Tarik kuis yang sudah terbit kembali ke draft untuk diedit (PJR-06)
        if ($aksi === 'tarik') {
            $catatan = isset($_POST['catatan']) ? trim($_POST['catatan']) : null;
            $hasil = tarikKuis($kuisId, $pengajarId, $catatan);
            $pesanSukses = 'Kuis berhasil ditarik kembali ke Draft.';
        }
        // Aksi 2: Pindahkan kuis ditolak / ditarik ke draft agar bisa diedit (PJR-07 / BR-04)
        elseif ($aksi === 'revisi') {
            $hasil = bukaRevisiKuis($kuisId, $pengajarId);
            $pesanSukses = 'Kuis berhasil dipindahkan ke Draft untuk direvisi.';
        }
        // Aksi 3: Hapus kuis draft yang belum pernah dikerjakan (PJR-08 / BR-06)
        elseif ($aksi === 'hapus') {
            $hasil = hapusKuis($kuisId, $pengajarId);
            $pesanSukses = 'Draft kuis berhasil dihapus.';
        }

        // Cek hasil proses aksi
        if ($hasil !== null) {
            if ($hasil['berhasil'] ?? false) {
                flash('sukses', $pesanSukses);
                header('Location: kuis.php' . ($statusFilter !== '' ? '?status=' . urlencode($statusFilter) : ''));
                exit;
            } else {
                $pesanError = $hasil['pesan'] ?? 'Gagal memproses aksi pada kuis.';
            }
        }
    }
}
