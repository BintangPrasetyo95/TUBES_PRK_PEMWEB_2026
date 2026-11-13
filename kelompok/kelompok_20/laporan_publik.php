<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Laporan Publik';

$stmtKategori = $pdo->query("SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori ASC");
$kategoriList = $stmtKategori->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 2rem;">
    <div style="margin-bottom: 1.5rem;">
        <h1 style="font-size: 1.75rem; color: var(--color-primary); margin-bottom: 0.35rem;">
            Daftar Laporan Publik
        </h1>
        <p style="color: var(--color-text-muted); font-size: 0.95rem; margin: 0;">
            Semua aspirasi dan pengaduan terbuka yang disampaikan warga kampus FISIP dapat dipantau dan ditelusuri di sini secara transparan.
        </p>
    </div>

    <div class="card" style="padding: 1.5rem; margin-bottom: 1.5rem;">
        <div style="margin-bottom: 1.25rem;">
            <label for="search-input" class="form-label">Cari Laporan</label>
            <input 
                type="text" 
                id="search-input" 
                class="form-control" 
                placeholder="Ketik kata kunci judul, rincian isi, atau lokasi kejadian..." 
                autocomplete="off"
            >
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem; align-items: end;">
            <div>
                <label for="filter-kategori" class="form-label">Kategori</label>
                <select id="filter-kategori" class="form-control">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($kategoriList as $kat): ?>
                        <option value="<?= sanitize($kat['id']) ?>"><?= sanitize($kat['nama_kategori']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="filter-jenis" class="form-label">Jenis</label>
                <select id="filter-jenis" class="form-control">
                    <option value="">Semua Jenis</option>
                    <option value="pengaduan">Pengaduan</option>
                    <option value="aspirasi">Aspirasi</option>
                </select>
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="button" id="btn-filter" class="btn btn-primary" style="flex: 1;">
                    Terapkan Filter
                </button>
                <button type="button" id="btn-reset" class="btn btn-secondary">
                    Reset
                </button>
            </div>
        </div>
    </div>

    <div id="search-info" style="margin-bottom: 1rem; font-size: 0.95rem; color: var(--color-text-muted);">
        Memuat data...
    </div>

    <section id="laporan-container">
        <div style="text-align: center; padding: 2.5rem 1rem; color: var(--color-text-muted);">
            <em>Memuat data dari server...</em>
        </div>
    </section>

    <nav id="pagination-container" style="margin-top: 2rem; display: flex; justify-content: center; align-items: center; gap: 0.5rem; flex-wrap: wrap;" aria-label="Navigasi Halaman"></nav>
</div>

<script src="/assets/js/app.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnFilter = document.getElementById('btn-filter');
    const filterJenis = document.getElementById('filter-jenis');
    if (btnFilter && filterJenis) {
        btnFilter.addEventListener('click', function () {
            filterJenis.dispatchEvent(new Event('change'));
        });
    }
});
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
