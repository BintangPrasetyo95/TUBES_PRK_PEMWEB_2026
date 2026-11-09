<?php
require_once __DIR__ . '/config/database.php';
include_once __DIR__ . '/components/header.php';

$id_aspirasi = $_GET['id'] ?? null;

if (!$id_aspirasi || !is_numeric($id_aspirasi)) {
    echo "<script>window.location.href='index.php';</script>";
    exit();
}

// PROSES SIMPAN TANGGAPAN / KOMENTAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_tanggapan'])) {
    $user_id_session = $_SESSION['id_user'] ?? $_SESSION['user_id'] ?? null;
    $isi_tanggapan   = trim($_POST['isi_tanggapan'] ?? '');

    if ($user_id_session && !empty($isi_tanggapan)) {
        try {
            $stmtInsert = $pdo->prepare("INSERT INTO komentar (id_aspirasi, id_user, isi_komentar) VALUES (?, ?, ?)");
            $stmtInsert->execute([$id_aspirasi, $user_id_session, $isi_tanggapan]);
            echo "<script>window.location.href='aspirasi_detail.php?id=" . (int)$id_aspirasi . "#diskusi';</script>";
            exit();
        } catch (PDOException $e) {
            error_log('Gagal menyimpan tanggapan aspirasi: ' . $e->getMessage());
        }
    }
}

try {
    // Ambil Detail Aspirasi
    $stmt = $pdo->prepare("SELECT aspirasi.*, kategori.nama_kategori, users.nama_lengkap 
                           FROM aspirasi 
                           LEFT JOIN kategori ON aspirasi.id_kategori = kategori.id_kategori
                           LEFT JOIN users ON aspirasi.id_user = users.id_user
                           WHERE aspirasi.id_aspirasi = ?");
    $stmt->execute([$id_aspirasi]);
    $aspirasi = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$aspirasi) {
        echo "<script>window.location.href='index.php';</script>";
        exit();
    }

    // Ambil List Tanggapan
    $stmtTanggapan = $pdo->prepare("SELECT k.*, u.nama_lengkap, u.role
                                    FROM komentar k
                                    LEFT JOIN users u ON k.id_user = u.id_user
                                    WHERE k.id_aspirasi = ?
                                    ORDER BY k.tanggal_komentar ASC");
    $stmtTanggapan->execute([$id_aspirasi]);
    $tanggapanList = $stmtTanggapan->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<script>window.location.href='index.php';</script>";
    exit();
}
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <a href="index.php" class="text-blue-600 hover:underline text-sm font-medium">&larr; Kembali ke Beranda</a>

    <!-- CARD DETAIL ASPIRASI -->
    <article class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sm:p-8 mt-4">
        <div class="flex justify-between items-center">
            <span class="bg-amber-100 text-amber-800 text-xs font-semibold px-2.5 py-1 rounded">
                <?= htmlspecialchars($aspirasi['nama_kategori'] ?? 'Umum'); ?>
            </span>
            
            <?php
            $statusClass = match(strtolower($aspirasi['status'] ?? '')) {
                'ditinjau' => 'bg-blue-100 text-blue-800',
                'selesai'  => 'bg-green-100 text-green-800',
                default    => 'bg-amber-50 text-amber-700',
            };
            ?>
            <span class="text-xs font-semibold px-3 py-1 rounded-full capitalize <?= $statusClass; ?>">
                Status: <?= htmlspecialchars($aspirasi['status'] ?? 'Menunggu'); ?>
            </span>
        </div>

        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 mt-4">
            <?= htmlspecialchars($aspirasi['judul_aspirasi']); ?>
        </h1>

        <div class="flex items-center text-xs text-gray-500 space-x-4 mt-3 pb-6 border-b border-gray-100">
            <span>Oleh: <strong class="text-gray-700"><?= ($aspirasi['is_anonim'] == 1) ? 'Anonim' : htmlspecialchars($aspirasi['nama_lengkap'] ?? 'Mahasiswa'); ?></strong></span>
            <span>&bull;</span>
            <span><?= date('d M Y, H:i', strtotime($aspirasi['tanggal_kirim'])); ?> WIB</span>
        </div>

        <div class="text-gray-800 leading-relaxed mt-6">
            <?= nl2br(htmlspecialchars($aspirasi['isi_aspirasi'])); ?>
        </div>
    </article>

    <!-- SECTION DISKUSI & TANGGAPAN -->
    <div id="diskusi" class="mt-8 bg-white rounded-xl shadow-sm border border-gray-100 p-6 sm:p-8">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-gray-900">Diskusi / Tanggapan</h3>
            <span class="text-xs bg-gray-100 text-gray-600 font-semibold px-2.5 py-1 rounded-full">
                <?= count($tanggapanList) ?> Tanggapan
            </span>
        </div>

        <!-- DAFTAR KOMENTAR/TANGGAPAN -->
        <div class="space-y-4 mb-8">
            <?php if (!empty($tanggapanList)): ?>
                <?php foreach ($tanggapanList as $t): ?>
                    <div class="p-4 rounded-lg bg-gray-50 border border-gray-100">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs font-bold text-gray-800 flex items-center gap-1">
                                <?= (isset($t['role']) && strtolower($t['role']) === 'admin')
                                    ? '<span class="bg-blue-600 text-white text-[10px] px-1.5 py-0.5 rounded uppercase font-semibold">Admin FISIP</span>'
                                    : (($t['is_anonim'] ?? 0) ? 'Anonim' : htmlspecialchars($t['nama_lengkap'] ?? 'Mahasiswa')) ?>
                            </span>
                            <span class="text-[11px] text-gray-400">
                                <?= date('d M Y, H:i', strtotime($t['tanggal_komentar'])) ?> WIB
                            </span>
                        </div>
                        <p class="text-sm text-gray-700 leading-relaxed mt-1">
                            <?= nl2br(htmlspecialchars($t['isi_komentar'])); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-6 border border-dashed border-gray-200 rounded-lg text-center text-xs text-gray-500">
                    Belum ada diskusi atau tanggapan. Jadilah yang pertama memberikan masukan!
                </div>
            <?php endif; ?>
        </div>

        <!-- FORM INPUT TANGGAPAN -->
        <?php if (isset($_SESSION['id_user']) || isset($_SESSION['user_id']) || isset($_SESSION['user'])): ?>
            <form action="aspirasi_detail.php?id=<?= (int)$id_aspirasi ?>" method="POST" class="space-y-4 border-t border-gray-100 pt-6">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-2">Berikan Tanggapan Anda:</label>
                    <textarea name="isi_tanggapan" rows="3" required
                        class="w-full p-3 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                        placeholder="Tuliskan pendapat atau masukan tambahan mengenai aspirasi ini..."></textarea>
                </div>

                <button type="submit" name="submit_tanggapan"
                    class="px-5 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-medium text-xs rounded-lg shadow-sm transition-all">
                    Kirim Tanggapan
                </button>
            </form>
        <?php else: ?>
            <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg text-xs flex justify-between items-center flex-wrap gap-2">
                <span class="text-amber-800 font-medium">🔒 Silakan login terlebih dahulu untuk memberikan tanggapan.</span>
                <a href="login.php" class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded text-xs transition-colors">
                    Login Sekarang
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/components/footer.php'; ?>