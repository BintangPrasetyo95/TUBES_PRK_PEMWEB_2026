<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/komponen.php';

const KATALOG_KETERSEDIAAN = ['semua' => 'Semua', 'tersedia' => 'Tersedia', 'habis' => 'Habis'];

function katalog_filter_dari_request(array $input, string $ketersediaanBawaan = 'semua'): array
{
    $kataKunci = trim((string) ($input['q'] ?? ''));
    $kategori = filter_var($input['kategori'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $jenis = (string) ($input['jenis'] ?? '');
    $ketersediaan = (string) ($input['ketersediaan'] ?? $ketersediaanBawaan);

    return [
        'q' => mb_substr($kataKunci, 0, 100),
        'kategori' => $kategori === false ? null : $kategori,
        'jenis' => array_key_exists($jenis, JENIS_ALAT) ? $jenis : '',
        'ketersediaan' => array_key_exists($ketersediaan, KATALOG_KETERSEDIAAN) ? $ketersediaan : $ketersediaanBawaan,
    ];
}

function katalog_filter_aktif(array $filter, string $ketersediaanBawaan): bool
{
    return $filter['q'] !== ''
        || $filter['kategori'] !== null
        || $filter['jenis'] !== ''
        || $filter['ketersediaan'] !== $ketersediaanBawaan;
}

function katalog_cari(array $filter = [], ?int $batas = null): array
{
    $syarat = [];
    $parameter = [];

    if (($filter['q'] ?? '') !== '') {
        $syarat[] = '(a.nama LIKE :cari_nama OR a.kode LIKE :cari_kode)';
        $parameter['cari_nama'] = '%' . $filter['q'] . '%';
        $parameter['cari_kode'] = '%' . $filter['q'] . '%';
    }

    if (($filter['kategori'] ?? null) !== null) {
        $syarat[] = 'a.kategori_id = :kategori';
        $parameter['kategori'] = $filter['kategori'];
    }

    if (($filter['jenis'] ?? '') !== '') {
        $syarat[] = 'a.jenis = :jenis';
        $parameter['jenis'] = $filter['jenis'];
    }

    $ketersediaan = $filter['ketersediaan'] ?? 'semua';

    if ($ketersediaan === 'tersedia') {
        $syarat[] = 'a.stok > 0';
    } elseif ($ketersediaan === 'habis') {
        $syarat[] = 'a.stok = 0';
    }

    $sql = 'SELECT a.id, a.kategori_id, a.kode, a.nama, a.jenis, a.deskripsi, a.stok, a.lokasi, a.foto,
                   k.nama AS kategori
            FROM alat a
            JOIN kategori k ON k.id = a.kategori_id'
        . ($syarat === [] ? '' : ' WHERE ' . implode(' AND ', $syarat))
        . ($batas === null ? ' ORDER BY a.nama ASC' : ' ORDER BY a.created_at DESC, a.id DESC LIMIT :batas');

    $statement = db()->prepare($sql);

    foreach ($parameter as $nama => $nilai) {
        $statement->bindValue(':' . $nama, $nilai, is_int($nilai) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }

    if ($batas !== null) {
        $statement->bindValue(':batas', $batas, PDO::PARAM_INT);
    }

    $statement->execute();

    return $statement->fetchAll();
}

function katalog_kategori(): array
{
    return db()->query('SELECT id, nama FROM kategori ORDER BY nama ASC')->fetchAll();
}

function katalog_ringkasan(): array
{
    $ringkasan = db()->query(
        "SELECT
            (SELECT COUNT(*) FROM alat WHERE jenis = 'alat') AS alat,
            (SELECT COUNT(*) FROM alat WHERE jenis = 'ruang') AS ruang,
            (SELECT COUNT(*) FROM kategori) AS kategori,
            (SELECT COUNT(*) FROM alat WHERE jenis = 'alat' AND stok > 0) AS alat_tersedia,
            (SELECT COUNT(*) FROM alat WHERE jenis = 'ruang' AND stok > 0) AS ruang_tersedia"
    )->fetch();

    return array_map('intval', $ringkasan ?: []);
}

function render_katalog_filter(array $filter, array $daftarKategori, string $action): void
{
    ?>
<form method="get" action="<?= e($action) ?>" class="filter-bar" role="search">
    <div class="filter-field filter-field--search">
        <label for="filter-q" class="form-label">Cari nama atau kode</label>
        <input type="search" id="filter-q" name="q" class="form-control" placeholder="Contoh: Osiloskop atau EL-002" value="<?= e($filter['q']) ?>">
    </div>
    <div class="filter-field">
        <label for="filter-kategori" class="form-label">Kategori</label>
        <select id="filter-kategori" name="kategori" class="form-control">
            <option value="">Semua kategori</option>
<?php foreach ($daftarKategori as $kategori): ?>
            <option value="<?= (int) $kategori['id'] ?>" <?= $filter['kategori'] === (int) $kategori['id'] ? 'selected' : '' ?>><?= e($kategori['nama']) ?></option>
<?php endforeach; ?>
        </select>
    </div>
    <div class="filter-field">
        <label for="filter-jenis" class="form-label">Jenis</label>
        <select id="filter-jenis" name="jenis" class="form-control">
            <option value="">Alat &amp; ruang</option>
<?php foreach (JENIS_ALAT as $nilai => $label): ?>
            <option value="<?= e($nilai) ?>" <?= $filter['jenis'] === $nilai ? 'selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
        </select>
    </div>
    <div class="filter-field">
        <label for="filter-ketersediaan" class="form-label">Ketersediaan</label>
        <select id="filter-ketersediaan" name="ketersediaan" class="form-control">
<?php foreach (KATALOG_KETERSEDIAAN as $nilai => $label): ?>
            <option value="<?= e($nilai) ?>" <?= $filter['ketersediaan'] === $nilai ? 'selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
        </select>
    </div>
    <div class="filter-actions">
        <button type="submit" class="btn btn-primary">Terapkan</button>
        <a href="<?= e($action) ?>" class="btn btn-outline">Reset</a>
    </div>
</form>
    <?php
}

function render_katalog_hasil(array $daftarAlat, array $filter, string $action, string $ketersediaanBawaan): void
{
    $filterAktif = katalog_filter_aktif($filter, $ketersediaanBawaan);
    ?>
<p class="katalog-info" aria-live="polite">
    Menampilkan <strong><?= count($daftarAlat) ?></strong> alat &amp; ruang<?= $filter['q'] !== '' ? ' untuk "' . e($filter['q']) . '"' : '' ?>.
</p>
<?php if ($daftarAlat === []): ?>
<div class="empty-state card">
    <?= ikon('cari') ?>
    <h3><?= $filterAktif ? 'Tidak ada hasil yang cocok' : 'Belum ada data alat' ?></h3>
    <p class="text-muted"><?= $filterAktif ? 'Coba kata kunci lain atau longgarkan filter.' : 'Data alat dan ruang belum tersedia.' ?></p>
<?php if ($filterAktif): ?>
    <a href="<?= e($action) ?>" class="btn btn-outline">Reset pencarian</a>
<?php endif; ?>
</div>
<?php else: ?>
<div class="alat-grid">
<?php foreach ($daftarAlat as $alat): ?>
    <?php alat_card($alat); ?>
<?php endforeach; ?>
</div>
<?php endif; ?>
    <?php
}
