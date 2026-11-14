<?php

require_once __DIR__ . '/../config/app.php';

const STATUS_PEMINJAMAN = [
    'menunggu' => [
        'label' => 'Menunggu',
        'keterangan' => 'Pengajuan sudah terkirim dan menunggu diproses admin. Jika tidak diproses dalam 3 hari, pengajuan ditolak otomatis oleh sistem.',
    ],
    'disetujui' => [
        'label' => 'Disetujui',
        'keterangan' => 'Pengajuan diterima. Alat atau ruang boleh diambil sesuai jadwal dan wajib dikembalikan sebelum batas kembali.',
    ],
    'ditolak' => [
        'label' => 'Ditolak',
        'keterangan' => 'Pengajuan tidak dapat dipenuhi, baik oleh admin maupun otomatis oleh sistem. Silakan ajukan ulang bila perlu.',
    ],
    'dikembalikan' => [
        'label' => 'Dikembalikan',
        'keterangan' => 'Alat atau ruang sudah diterima kembali oleh admin. Transaksi selesai.',
    ],
];

const JENIS_ALAT = ['alat' => 'Alat', 'ruang' => 'Ruang'];
const STOK_MENIPIS = 3;
const JUMLAH_WARNA_PLACEHOLDER = 5;

const IKON_PATH = [
    'alat' => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"/>',
    'ruang' => '<path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"/><path d="M16 8h2a2 2 0 0 1 2 2v11"/><path d="M2 21h20"/><path d="M8 7h4M8 11h4M8 15h4"/>',
    'kategori' => '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
    'cari' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
    'panah' => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
    'atas' => '<path d="M12 19V5"/><path d="m6 11 6-6 6 6"/>',
    'pilih' => '<path d="M9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
    'ajukan' => '<path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/>',
    'kembali' => '<path d="M3 12a9 9 0 0 1 15.5-6.2L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-15.5 6.2L3 16"/><path d="M3 21v-5h5"/>',
    'lokasi' => '<path d="M12 21s-7-6.1-7-11a7 7 0 0 1 14 0c0 4.9-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
    'email' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
    'telepon' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
    'jam' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><path d="M12 8h.01"/>',
];

function ikon(string $nama, string $class = 'ikon'): string
{
    $path = IKON_PATH[$nama] ?? IKON_PATH['info'];

    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $path . '</svg>';
}

function status_badge(string $status): string
{
    $label = STATUS_PEMINJAMAN[$status]['label'] ?? ucfirst($status);

    return '<span class="badge badge-' . e($status) . '">' . e($label) . '</span>';
}

function foto_alat_url(?string $foto): ?string
{
    return $foto === null || $foto === '' ? null : url('uploads/alat/' . rawurlencode($foto));
}

function stok_badge(array $alat): array
{
    $stok = (int) $alat['stok'];

    if ($stok === 0) {
        return ['class' => 'stok-badge--habis', 'label' => 'Habis'];
    }

    if ($alat['jenis'] === 'alat' && $stok <= STOK_MENIPIS) {
        return ['class' => 'stok-badge--menipis', 'label' => 'Tersisa ' . $stok];
    }

    return ['class' => 'stok-badge--tersedia', 'label' => 'Tersedia'];
}

function aksi_alat(array $alat): array
{
    if (!is_logged_in()) {
        return ['href' => url('auth/login.php'), 'label' => 'Masuk untuk meminjam'];
    }

    if (has_role('admin')) {
        return ['href' => url('admin/alat/index.php?edit=' . (int) $alat['id']), 'label' => 'Kelola data'];
    }

    return ['href' => url('peminjaman/ajukan.php?id=' . (int) $alat['id']), 'label' => 'Ajukan pinjam'];
}

function foto_alat(array $alat, string $class = 'alat-media'): void
{
    $fotoUrl = foto_alat_url($alat['foto'] ?? null);
    $warna = ((int) ($alat['kategori_id'] ?? 0)) % JUMLAH_WARNA_PLACEHOLDER;
    ?>
<div class="<?= e($class) ?><?= $fotoUrl === null ? ' alat-media--kosong alat-media--warna-' . $warna : '' ?>">
<?php if ($fotoUrl !== null): ?>
    <img src="<?= e($fotoUrl) ?>" alt="Foto <?= e($alat['nama']) ?>" loading="lazy">
<?php else: ?>
    <?= ikon($alat['jenis'] === 'ruang' ? 'ruang' : 'alat', 'alat-media__ikon') ?>
    <span class="visually-hidden">Foto <?= e($alat['nama']) ?> belum tersedia</span>
<?php endif; ?>
</div>
    <?php
}

function alat_card(array $alat): void
{
    $habis = (int) $alat['stok'] === 0;
    $badge = stok_badge($alat);
    $aksi = aksi_alat($alat);
    ?>
<article class="alat-card<?= $habis ? ' is-habis' : '' ?>">
    <div class="alat-card__media">
        <?php foto_alat($alat); ?>
        <span class="stok-badge <?= e($badge['class']) ?>"><?= e($badge['label']) ?></span>
    </div>
    <div class="alat-card__body">
        <div class="alat-card__tags">
            <span class="tag tag--dark"><?= e(JENIS_ALAT[$alat['jenis']] ?? $alat['jenis']) ?></span>
            <span class="tag"><?= e($alat['kategori']) ?></span>
        </div>
        <h3 class="alat-card__title">
<?php if ($habis): ?>
            <?= e($alat['nama']) ?>
<?php else: ?>
            <a href="<?= e($aksi['href']) ?>" class="alat-card__link" aria-label="<?= e($aksi['label'] . ': ' . $alat['nama']) ?>"><?= e($alat['nama']) ?></a>
<?php endif; ?>
        </h3>
        <p class="alat-card__kode"><?= e($alat['kode']) ?></p>
        <p class="alat-card__desc"><?= e($alat['deskripsi'] ?: 'Belum ada deskripsi.') ?></p>
        <div class="alat-card__footer">
            <p class="alat-card__info">
                <span>Stok <strong><?= (int) $alat['stok'] ?></strong></span>
                <span class="alat-card__lokasi"><?= ikon('lokasi', 'ikon ikon--sm') ?><?= e($alat['lokasi'] ?: 'Lokasi belum diisi') ?></span>
            </p>
            <div class="alat-card__aksi">
<?php if ($habis): ?>
                <span class="btn btn-disabled" aria-hidden="true">Stok habis</span>
<?php else: ?>
                <span class="btn btn-neon" aria-hidden="true"><?= e($aksi['label']) ?><?= ikon('panah', 'ikon ikon--sm') ?></span>
<?php endif; ?>
                <a href="<?= e(url('alat/detail.php?id=' . (int) $alat['id'])) ?>" class="btn btn-outline alat-card__detail" aria-label="Lihat detail <?= e($alat['nama']) ?>">Detail</a>
            </div>
        </div>
    </div>
</article>
    <?php
}
