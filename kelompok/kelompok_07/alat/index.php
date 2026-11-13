<?php
session_start();
require_once '../config/koneksi.php';
require_once '../config/app.php';

$judul = 'Daftar Alat';
$pdo = db();

// Ambil daftar kategori untuk dropdown filter
$stmtKat = $pdo->query("SELECT id, nama FROM kategori ORDER BY nama ASC");
$kategoris = $stmtKat->fetchAll();

// Ambil SEMUA data alat beserta nama kategorinya
$query = "SELECT a.*, k.nama AS nama_kategori 
          FROM alat a 
          LEFT JOIN kategori k ON a.kategori_id = k.id 
          ORDER BY a.nama ASC";
$stmt = $pdo->query($query);
$alats = $stmt->fetchAll();

include '../includes/header.php';
?>

<!-- Bagian Biru di Judul (Hero) -->
<section class="hero">
    <h1>Daftar Inventaris Alat &amp; Ruang</h1>
    <p>Telusuri seluruh inventaris laboratorium dan klik lihat detail untuk membaca spesifikasi lengkapnya.</p>
</section>

<!-- Bar Pencarian Full Width & Filter Kategori -->
<section class="mt-6 mb-6">
    <div style="display: flex; flex-wrap: wrap; gap: 12px; width: 100%;">
        <input type="text" id="liveSearchAlat" 
            placeholder="Ketik nama alat, kode, atau lokasi untuk mencari..." 
            autocomplete="off"
            style="flex: 2; min-width: 250px; padding: 0.85rem 1.2rem; border: 1px solid #d1d5db; border-radius: 8px; outline: none; font-size: 1rem; background: #fff;">
        
        <select id="filterKategori" 
            style="flex: 1; min-width: 180px; padding: 0.85rem 1rem; border: 1px solid #d1d5db; border-radius: 8px; outline: none; font-size: 1rem; background: #fff; cursor: pointer;">
            <option value="">Semua Kategori</option>
            <?php foreach ($kategoris as $kat): ?>
                <option value="<?= e(strtolower($kat['nama'])) ?>"><?= e($kat['nama']) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="button" id="resetFilterBtn" 
            style="display: none; background-color: #f3f4f6; color: #4b5563; border: 1px solid #d1d5db; padding: 0 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 1rem;">
            Reset
        </button>
    </div>
</section>

<!-- Tabel Daftar Alat -->
<section class="card mt-6">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Foto</th>
                    <th>Identitas Alat</th>
                    <th>Kategori</th>
                    <th>Lokasi</th>
                    <th>Status Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($alats) > 0) : ?>
                    <?php $no = 1; foreach ($alats as $row) : ?>
                        <?php 
                            $foto_url = !empty($row['foto']) ? "../uploads/alat/" . $row['foto'] : "../assets/img/default-alat.png"; 
                            $kategori_nama = $row['nama_kategori'] ?? 'Umum';
                        ?>
                        <tr class="alat-row" data-kategori="<?= e(strtolower($kategori_nama)) ?>">
                            <td><?= $no++ ?></td>
                            <td>
                                <img src="<?= e($foto_url) ?>" alt="<?= e($row['nama']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid #e2e8f0;">
                            </td>
                            <td>
                                <strong><?= e($row['nama']) ?></strong><br>
                                <span class="text-muted" style="font-size: 0.85em;">Kode: <?= e($row['kode']) ?></span>
                            </td>
                            <td>
                                <span class="badge badge-neutral"><?= e($kategori_nama) ?></span>
                            </td>
                            <td><?= e($row['lokasi']) ?></td>
                            <td>
                                <?php if ($row['stok'] > 0): ?>
                                    <span class="badge badge-info">Tersedia (<?= (int)$row['stok'] ?>)</span>
                                <?php else: ?>
                                    <span class="badge badge-ditolak">Habis</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="detail.php?id=<?= (int)$row['id'] ?>" class="btn btn-outline btn-sm">Lihat Detail</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="emptySearchRow" style="display: none;">
                        <td colspan="7" style="text-align: center; padding: 2rem;" class="text-muted">
                            Tidak ada alat yang cocok dengan pencarian atau kategori tersebut.
                        </td>
                    </tr>
                <?php else : ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem;">Belum ada data alat yang terdaftar di sistem.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('liveSearchAlat');
    const kategoriSelect = document.getElementById('filterKategori');
    const resetBtn = document.getElementById('resetFilterBtn');
    const rows = document.querySelectorAll('.alat-row');
    const emptyRow = document.getElementById('emptySearchRow');

    function filterTable() {
        const keyword = searchInput.value.toLowerCase().trim();
        const selectedKat = kategoriSelect.value.toLowerCase().trim();
        let visibleCount = 0;

        resetBtn.style.display = (keyword !== '' || selectedKat !== '') ? 'inline-block' : 'none';

        rows.forEach(function(row) {
            const rowText = row.textContent.toLowerCase();
            const rowKat = row.dataset.kategori;

            const matchText = rowText.includes(keyword);
            const matchKat = selectedKat === '' || rowKat === selectedKat;

            if (matchText && matchKat) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (emptyRow) {
            emptyRow.style.display = visibleCount === 0 ? '' : 'none';
        }
    }

    searchInput.addEventListener('input', filterTable);
    kategoriSelect.addEventListener('change', filterTable);

    resetBtn.addEventListener('click', function() {
        searchInput.value = '';
        kategoriSelect.value = '';
        filterTable();
        searchInput.focus();
    });
});
</script>

<?php include '../includes/footer.php'; ?>