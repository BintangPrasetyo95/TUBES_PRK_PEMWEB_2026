<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/komponen.php';

const LAPORAN_JUDUL = 'Laporan Peminjaman Alat & Ruang Laboratorium';

function laporan_tanggal_valid(mixed $value): bool
{
    return is_string($value)
        && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1
        && checkdate((int) substr($value, 5, 2), (int) substr($value, 8, 2), (int) substr($value, 0, 4));
}

function laporan_periode(array $input): array
{
    $awal = $input['awal'] ?? date('Y-m-01');
    $akhir = $input['akhir'] ?? date('Y-m-d');

    if (!laporan_tanggal_valid($awal) || !laporan_tanggal_valid($akhir) || $awal > $akhir) {
        return [
            'awal' => date('Y-m-01'),
            'akhir' => date('Y-m-d'),
            'error' => 'Rentang tanggal tidak valid. Periode dikembalikan ke bulan ini.',
        ];
    }

    return ['awal' => $awal, 'akhir' => $akhir, 'error' => ''];
}

function laporan_data(string $awal, string $akhir): array
{
    $statement = db()->prepare(
        "SELECT
            p.kode,
            u.nama AS peminjam,
            p.tanggal_pinjam,
            p.tanggal_kembali_rencana,
            p.tanggal_kembali_aktual,
            p.status,
            (
                SELECT GROUP_CONCAT(CONCAT(a.nama, ' (', dp.jumlah, ')') ORDER BY a.nama SEPARATOR ', ')
                FROM detail_peminjaman dp
                JOIN alat a ON a.id = dp.alat_id
                WHERE dp.peminjaman_id = p.id
            ) AS daftar_barang
         FROM peminjaman p
         JOIN users u ON u.id = p.user_id
         WHERE p.tanggal_pinjam >= ?
           AND p.tanggal_pinjam < DATE_ADD(?, INTERVAL 1 DAY)
         ORDER BY p.tanggal_pinjam DESC"
    );
    $statement->execute([$awal, $akhir]);

    return $statement->fetchAll();
}

function render_laporan(array $laporan, array $periode, string $action): void
{
    $teksPeriode = format_tanggal($periode['awal']) . ' s.d. ' . format_tanggal($periode['akhir']);
    ?>
<section id="laporan" class="card laporan-card" aria-labelledby="laporan-judul">
    <div class="print-header print-only">
        <p class="print-header__brand"><?= e(APP_NAME) ?> &middot; <?= e(LAB_NAMA) ?></p>
        <h1><?= e(LAPORAN_JUDUL) ?></h1>
        <p>Periode: <?= e($teksPeriode) ?></p>
        <p>Tanggal cetak: <?= e(date('d/m/Y H:i')) ?> WIB</p>
    </div>
    <div class="card-header no-print">
        <div>
            <h2 id="laporan-judul">Laporan Peminjaman</h2>
            <p class="text-muted mb-0">Periode <?= e($teksPeriode) ?></p>
        </div>
        <span class="badge badge-info"><?= count($laporan) ?> transaksi</span>
    </div>
    <div class="card-body">
<?php if ($periode['error'] !== ''): ?>
        <div class="alert alert-danger no-print"><?= e($periode['error']) ?></div>
<?php endif; ?>
        <form method="get" action="<?= e($action) ?>" class="filter-form no-print">
            <div class="form-group">
                <label for="laporan-awal" class="form-label">Dari Tanggal</label>
                <input type="date" id="laporan-awal" name="awal" class="form-control" value="<?= e($periode['awal']) ?>" required>
            </div>
            <div class="form-group">
                <label for="laporan-akhir" class="form-label">Sampai Tanggal</label>
                <input type="date" id="laporan-akhir" name="akhir" class="form-control" value="<?= e($periode['akhir']) ?>" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Filter</button>
                <button type="button" class="btn btn-neon" data-print>Cetak / Simpan PDF</button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table report-table">
                <thead>
                    <tr>
                        <th scope="col">Kode</th>
                        <th scope="col">Peminjam</th>
                        <th scope="col">Barang (Jumlah)</th>
                        <th scope="col">Tanggal Pinjam</th>
                        <th scope="col">Batas Kembali</th>
                        <th scope="col">Kembali Aktual</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
<?php foreach ($laporan as $item): ?>
                    <tr>
                        <td class="nowrap"><?= e($item['kode']) ?></td>
                        <td><?= e($item['peminjam']) ?></td>
                        <td><?= e($item['daftar_barang'] ?: 'Rincian barang tidak tersedia') ?></td>
                        <td class="nowrap"><?= e(format_tanggal($item['tanggal_pinjam'])) ?></td>
                        <td class="nowrap"><?= e(format_tanggal($item['tanggal_kembali_rencana'])) ?></td>
                        <td class="nowrap"><?= e(format_tanggal($item['tanggal_kembali_aktual'])) ?></td>
                        <td><?= status_badge($item['status']) ?></td>
                    </tr>
<?php endforeach; ?>
<?php if ($laporan === []): ?>
                    <tr>
                        <td colspan="7" class="report-empty">Tidak ada data peminjaman pada periode ini.</td>
                    </tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
        <p class="print-only print-footer">Total <?= count($laporan) ?> transaksi.</p>
    </div>
</section>
    <?php
}
