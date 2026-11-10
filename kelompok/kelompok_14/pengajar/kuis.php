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

// Bagian Tampilan Halaman (HTML)
$judulHalaman = 'Kuis Saya';
$halamanAktif = 'kuis';
$root = '../';

// Token CSRF untuk form aksi
$tokenCsrf = csrfToken();

// Muat template header panel pengajar
require_once __DIR__ . '/../includes/partials/header-panel.php';
?>

<div class="panel-header" style="margin-bottom: 24px;">
    <div>
        <h1 style="margin: 0 0 8px 0;">Kuis Saya</h1>
        <p class="text-secondary" style="margin: 0;">Kelola daftar kuis latihan soal yang telah Anda buat.</p>
    </div>
    <div style="margin-top: 12px;">
        <a href="kuis-form.php" class="btn btn-primary">+ Buat Kuis Baru</a>
    </div>
</div>

<?php if ($pesanError !== ''): ?>
    <div class="alert alert-galat" style="margin-bottom: 20px;">
        <?= e($pesanError) ?>
    </div>
<?php endif; ?>

<!-- Baris Filter Status dan Pencarian -->
<div class="card" style="padding: 16px; margin-bottom: 24px;">
    <div style="display: flex; flex-wrap: wrap; gap: 16px; justify-content: space-between; align-items: center;">
        
        <!-- Filter Status Kuis (Tab / Tombol Filter) -->
        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
            <a href="kuis.php<?= $kataKunci !== '' ? '?cari=' . urlencode($kataKunci) : '' ?>" 
               class="badge <?= $statusFilter === '' ? 'badge-primary' : 'badge-neutral' ?>" 
               style="text-decoration: none; padding: 8px 14px; font-size: 14px;">
                Semua
            </a>
            <a href="kuis.php?status=draft<?= $kataKunci !== '' ? '&cari=' . urlencode($kataKunci) : '' ?>" 
               class="badge <?= $statusFilter === 'draft' ? 'badge-primary' : 'badge-neutral' ?>" 
               style="text-decoration: none; padding: 8px 14px; font-size: 14px;">
                Draft
            </a>
            <a href="kuis.php?status=menunggu<?= $kataKunci !== '' ? '&cari=' . urlencode($kataKunci) : '' ?>" 
               class="badge <?= $statusFilter === 'menunggu' ? 'badge-primary' : 'badge-neutral' ?>" 
               style="text-decoration: none; padding: 8px 14px; font-size: 14px;">
                Menunggu
            </a>
            <a href="kuis.php?status=terbit<?= $kataKunci !== '' ? '&cari=' . urlencode($kataKunci) : '' ?>" 
               class="badge <?= $statusFilter === 'terbit' ? 'badge-primary' : 'badge-neutral' ?>" 
               style="text-decoration: none; padding: 8px 14px; font-size: 14px;">
                Terbit
            </a>
            <a href="kuis.php?status=ditolak<?= $kataKunci !== '' ? '&cari=' . urlencode($kataKunci) : '' ?>" 
               class="badge <?= $statusFilter === 'ditolak' ? 'badge-primary' : 'badge-neutral' ?>" 
               style="text-decoration: none; padding: 8px 14px; font-size: 14px;">
                Ditolak
            </a>
            <a href="kuis.php?status=ditarik<?= $kataKunci !== '' ? '&cari=' . urlencode($kataKunci) : '' ?>" 
               class="badge <?= $statusFilter === 'ditarik' ? 'badge-primary' : 'badge-neutral' ?>" 
               style="text-decoration: none; padding: 8px 14px; font-size: 14px;">
                Ditarik
            </a>
        </div>

        <!-- Form Pencarian Judul Kuis -->
        <form method="get" action="kuis.php" style="display: flex; gap: 8px; margin: 0;">
            <?php if ($statusFilter !== ''): ?>
                <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
            <?php endif; ?>
            <input type="text" name="cari" class="form-input" placeholder="Cari judul kuis..." 
                   value="<?= e($kataKunci) ?>" style="padding: 8px 14px; width: 200px;">
            <button type="submit" class="btn btn-secondary" style="padding: 8px 14px;">Cari</button>
            <?php if ($kataKunci !== ''): ?>
                <a href="kuis.php<?= $statusFilter !== '' ? '?status=' . urlencode($statusFilter) : '' ?>" 
                   class="btn btn-neutral" style="padding: 8px 12px; text-decoration: none;">Reset</a>
            <?php endif; ?>
        </form>

    </div>
</div>
