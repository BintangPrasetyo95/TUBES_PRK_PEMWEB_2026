<?php
session_start();
require_once '../config/koneksi.php';
require_once '../config/app.php';

$judul = 'Ajukan Peminjaman';
$pdo = db();

// Tangkap pencarian (tetap dipertahankan jika user menekan Enter)
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($search !== '') {
    // Parameter PDO dibedakan (:s1, :s2, :s3, :s4) agar tidak error HY093
    $query = "SELECT * FROM alat WHERE stok > 0 AND (nama LIKE :s1 OR kode LIKE :s2 OR deskripsi LIKE :s3 OR lokasi LIKE :s4) ORDER BY nama ASC";
    $stmt = $pdo->prepare($query);
    $keyword = '%' . $search . '%';
    $stmt->execute([
        's1' => $keyword,
        's2' => $keyword,
        's3' => $keyword,
        's4' => $keyword
    ]);
} else {
    $query = "SELECT * FROM alat WHERE stok > 0 ORDER BY nama ASC";
    $stmt = $pdo->query($query);
}

$alats = $stmt->fetchAll();

include '../includes/header.php';
?>

<!-- Bagian Biru di Judul (Hero) -->
<section class="hero">
    <h1>Katalog Alat Laboratorium</h1>
    <p>Pilih dan ajukan peminjaman alat atau ruang laboratorium yang tersedia.</p>
</section>

<!-- Form Pencarian Full Width & Live Search -->
<section class="mt-6 mb-6">
    <form action="index.php" method="GET" style="display: flex; gap: 12px; width: 100%;">
        <input type="text" id="liveSearchInput" name="q" value="<?= e($search) ?>" 
            placeholder="Ketik nama alat, kode, atau lokasi untuk langsung mencari..." 
            autocomplete="off"
            style="flex: 1; width: 100%; padding: 0.85rem 1.2rem; border: 1px solid #d1d5db; border-radius: 8px; outline: none; font-size: 1rem; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
        
        <button type="submit" style="background-color: #2563eb; color: white; border: none; padding: 0 1.75rem; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 1rem;">
            Cari
        </button>
        
        <button type="button" id="resetSearchBtn" style="display: <?= $search !== '' ? 'inline-block' : 'none' ?>; background-color: #f3f4f6; color: #4b5563; border: 1px solid #d1d5db; padding: 0 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 1rem;">
            Reset
        </button>
    </form>
</section>

<!-- Grid Katalog -->
<section class="mt-6">
    <?php if (count($alats) > 0) : ?>
        <div class="grid grid-3" id="katalogGrid">
            <?php foreach ($alats as $row) : ?>
                <article class="card item-card katalog-item">
                    <div class="item-photo" style="aspect-ratio: 16/9; background-color: #f1f5f9; display: flex; align-items: center; justify-content: center; overflow: hidden; border-radius: 8px 8px 0 0;">
                        <?php $foto_url = !empty($row['foto']) ? "../uploads/alat/" . $row['foto'] : "../assets/img/default-alat.png"; ?>
                        <img src="<?= e($foto_url) ?>" alt="<?= e($row['nama']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div class="card-body" style="display: flex; flex-direction: column; min-height: 250px;">
                        <div class="item-meta mb-2">
                            <span class="badge badge-info">Lokasi: <?= e($row['lokasi']) ?></span>
                            <span class="badge badge-neutral">Stok: <?= (int)$row['stok'] ?></span>
                        </div>
                        <h3><?= e($row['nama']) ?></h3>
                        <p class="text-muted mb-0">Kode: <?= e($row['kode']) ?></p>
                        <p class="text-muted mb-4" style="flex-grow: 1;"><?= e($row['deskripsi']) ?></p>
                        
                        <a href="ajukan.php?id=<?= e($row['id']) ?>" class="btn btn-outline" style="display: block; text-align: center; margin-top: auto;">Pinjam Alat</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- Pesan jika hasil ketikan tidak ada yang cocok -->
        <div id="emptyLiveSearch" class="card empty-state" style="display: none;">
            Tidak ada alat yang cocok dengan kata kunci pencarian Anda.
        </div>
    <?php else : ?>
        <div class="card empty-state">
            Maaf, saat ini tidak ada alat yang tersedia untuk dipinjam.
        </div>
    <?php endif; ?>
</section>

<!-- JavaScript untuk Pencarian Interaktif (Real-time saat mengetik) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('liveSearchInput');
    const resetBtn = document.getElementById('resetSearchBtn');
    const items = document.querySelectorAll('.katalog-item');
    const emptyMsg = document.getElementById('emptyLiveSearch');

    if (searchInput && items.length > 0) {
        searchInput.addEventListener('input', function() {
            const filter = this.value.toLowerCase().trim();
            let visibleCount = 0;

            // Tampilkan/sembunyikan tombol Reset secara otomatis
            if (resetBtn) {
                resetBtn.style.display = filter !== '' ? 'inline-block' : 'none';
            }

            items.forEach(function(item) {
                // Ambil seluruh teks di dalam kartu (nama, kode, lokasi, deskripsi)
                const text = item.textContent.toLowerCase();
                if (text.includes(filter)) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            // Munculkan pesan kosong jika tidak ada kartu yang cocok
            if (emptyMsg) {
                emptyMsg.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            // Jika URL memiliki parameter ?q=..., bersihkan URL
            if (window.location.search.includes('q=')) {
                window.location.href = 'index.php';
            } else {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
                searchInput.focus();
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>