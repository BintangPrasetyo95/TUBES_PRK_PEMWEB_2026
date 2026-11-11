
<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$sql = 'SELECT id, nama, deskripsi
        FROM document_types
        WHERE aktif = 1
        ORDER BY nama ASC';

$st = db()->prepare($sql);
$st->execute();
$jenis_dokumen = $st->fetchAll();

$page_title = 'Pustaka Persyaratan';
require __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h1>Pustaka Persyaratan</h1>
    <p>
        Temukan berbagai jenis dokumen dan lihat
        persyaratan administratif yang perlu disiapkan.
    </p>
    <p class="muted">
        Informasi persyaratan yang ditampilkan dapat berupa
        data contoh dan belum tentu merupakan ketentuan hukum resmi.
    </p>
</div>

<h2>Daftar Jenis Dokumen</h2>

<div class="card">
    <div class="form-group">
        <label for="search">Cari Jenis Dokumen</label>
        <input
            type="search"
            id="search"
            placeholder="Contoh: Surat Kuasa"
            autocomplete="off"
        >
    </div>

    <div class="form-group">
        <label for="type-filter">Filter Jenis Dokumen</label>
        <select id="type-filter">
            <option value="0">Semua Jenis Dokumen</option>
            <?php foreach ($jenis_dokumen as $jenis): ?>
                <option value="<?= (int) $jenis['id'] ?>">
                    <?= e($jenis['nama']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <button type="button" class="btn" id="reset-filter">
        Reset Filter
    </button>

    <p id="search-status" aria-live="polite"></p>
</div>

<div class="grid" id="library-list">
    <?php if (empty($jenis_dokumen)): ?>
        <div class="card">
            <p>Belum ada jenis dokumen yang tersedia.</p>
        </div>
    <?php else: ?>
        <?php foreach ($jenis_dokumen as $jenis): ?>
            <div class="card">
                <h3><?= e($jenis['nama']) ?></h3>
                <p><?= e($jenis['deskripsi'] ?? 'Belum ada deskripsi.') ?></p>
                <a
                    href="<?= e(base_url('modules/pustaka/detail.php?id=' . (int) $jenis['id'])) ?>"
                    class="btn"
                >Lihat Detail</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
$extra_js = ['assets/pustaka/search.js'];
require __DIR__ . '/../../includes/footer.php';
?>
