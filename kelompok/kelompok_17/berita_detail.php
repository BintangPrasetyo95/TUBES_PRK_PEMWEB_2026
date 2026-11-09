<?php
require_once __DIR__ . '/config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_berita = $_GET['id'] ?? null;
$komentarError = null;

if (!$id_berita || !is_numeric($id_berita)) {
    header('Location: index.php', true, 303);
    exit();
}

// PROSES SIMPAN KOMENTAR BERITA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_komentar'])) {
    $user_id_session = $_SESSION['id_user'] ?? $_SESSION['user_id'] ?? null;
    $isi_komentar    = trim($_POST['isi_komentar'] ?? '');

    if ($user_id_session && !empty($isi_komentar)) {
        try {
            $stmtInsert = $pdo->prepare("INSERT INTO komentar (id_berita, id_user, isi_komentar) VALUES (?, ?, ?)");
            $stmtInsert->execute([$id_berita, $user_id_session, $isi_komentar]);
            header('Location: berita_detail.php?id=' . (int)$id_berita . '#komentar', true, 303);
            exit();
        } catch (PDOException $e) {
            error_log('Gagal menyimpan komentar berita: ' . $e->getMessage());
            $komentarError = 'Komentar gagal disimpan. Silakan coba lagi.';
        }
    }
}

$berita = null;
$komentarList = [];
try {
    // Ambil Detail Berita
    $stmt = $pdo->prepare("SELECT berita.*, kategori.nama_kategori, users.nama_lengkap AS penulis 
                           FROM berita 
                           LEFT JOIN kategori ON berita.id_kategori = kategori.id_kategori
                           LEFT JOIN users ON berita.id_user = users.id_user
                           WHERE berita.id_berita = ?");
    $stmt->execute([$id_berita]);
    $berita = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$berita) {
        header('Location: index.php', true, 303);
        exit();
    }

    // Ambil List Komentar Berita
    $stmtKomentar = $pdo->prepare("SELECT k.*, u.nama_lengkap
                                   FROM komentar k
                                   LEFT JOIN users u ON k.id_user = u.id_user
                                   WHERE k.id_berita = ?
                                   ORDER BY k.tanggal_komentar ASC");
    $stmtKomentar->execute([$id_berita]);
    $komentarList = $stmtKomentar->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $komentarList = [];
    error_log('Gagal mengambil komentar berita: ' . $e->getMessage());
    $komentarError = 'Komentar belum dapat ditampilkan. Silakan muat ulang halaman.';
}

if (!$berita) {
    header('Location: index.php', true, 303);
    exit();
}

include_once __DIR__ . '/components/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <a href="index.php" class="text-blue-600 hover:underline text-sm font-medium">&larr; Kembali ke Beranda</a>
    
    <!-- CARD DETAIL BERITA -->
    <article class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sm:p-8 mt-4">
        <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-1 rounded">
            <?= htmlspecialchars($berita['nama_kategori'] ?? 'Umum'); ?>
        </span>
        
        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 mt-3">
            <?= htmlspecialchars($berita['judul_berita']); ?>
        </h1>
        
        <div class="flex items-center text-xs text-gray-500 space-x-4 mt-3 pb-6 border-b border-gray-100">
            <span>Diterbitkan oleh: <strong class="text-gray-700"><?= htmlspecialchars($berita['penulis'] ?? 'Admin Kampus'); ?></strong></span>
            <span>&bull;</span>
            <span><?= date('d M Y, H:i', strtotime($berita['tanggal_publikasi'])); ?> WIB</span>
        </div>

        <?php if (!empty($berita['gambar_url'])): ?>
            <img src="uploads/<?= htmlspecialchars($berita['gambar_url']); ?>" alt="Gambar Berita" class="w-full max-h-96 object-cover rounded-lg my-6">
        <?php endif; ?>

        <div class="prose max-w-none text-gray-800 leading-relaxed space-y-4 mt-6">
            <?= nl2br(htmlspecialchars($berita['isi_berita'])); ?>
        </div>
    </article>

    <!-- SECTION KOMENTAR BERITA -->
    <div id="komentar" class="mt-8 bg-white rounded-xl shadow-sm border border-gray-100 p-6 sm:p-8">
        <?php if ($komentarError): ?>
            <p class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700" role="alert">
                <?= htmlspecialchars($komentarError); ?>
            </p>
        <?php endif; ?>
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-gray-900">Komentar</h3>
            <span class="text-xs bg-gray-100 text-gray-600 font-semibold px-2.5 py-1 rounded-full">
                <?= count($komentarList) ?> Komentar
            </span>
        </div>

        <!-- DAFTAR KOMENTAR -->
        <div class="space-y-4 mb-8">
            <?php if (!empty($komentarList)): ?>
                <?php foreach ($komentarList as $k): ?>
                    <div class="p-4 rounded-lg bg-gray-50 border border-gray-100">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs font-bold text-gray-800">
                                🎓 <?= htmlspecialchars($k['nama_lengkap'] ?? 'Mahasiswa') ?>
                            </span>
                            <span class="text-[11px] text-gray-400">
                                <?= date('d M Y, H:i', strtotime($k['tanggal_komentar'])) ?> WIB
                            </span>
                        </div>
                        <p class="text-sm text-gray-700 leading-relaxed mt-1">
                            <?= nl2br(htmlspecialchars($k['isi_komentar'])); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-6 border border-dashed border-gray-200 rounded-lg text-center text-xs text-gray-500">
                    Belum ada komentar untuk berita ini. Berikan pandangan Anda pertama kali!
                </div>
            <?php endif; ?>
        </div>

        <!-- FORM INPUT KOMENTAR -->
        <?php if (isset($_SESSION['id_user']) || isset($_SESSION['user_id']) || isset($_SESSION['user'])): ?>
            <form action="berita_detail.php?id=<?= (int)$id_berita ?>" method="POST" class="space-y-4 border-t border-gray-100 pt-6">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-2">Tulis Komentar Anda:</label>
                    <textarea name="isi_komentar" rows="3" required
                        class="w-full p-3 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                        placeholder="Tuliskan komentar atau tanggapan mengenai berita ini..."></textarea>
                </div>

                <button type="submit" name="submit_komentar"
                    class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs rounded-lg shadow-sm transition-all">
                    Kirim Komentar
                </button>
            </form>
        <?php else: ?>
            <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg text-xs flex justify-between items-center flex-wrap gap-2">
                <span class="text-amber-800 font-medium">🔒 Silakan login terlebih dahulu untuk memberikan komentar.</span>
                <a href="login.php" class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded text-xs transition-colors">
                    Login Sekarang
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/components/footer.php'; ?>