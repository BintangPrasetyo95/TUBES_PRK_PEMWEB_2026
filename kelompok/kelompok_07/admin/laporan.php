<?php
require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../config/koneksi.php';

$judul = 'Laporan Peminjaman';

$awal = $_GET['awal'] ?? date('Y-m-01');
$akhir = $_GET['akhir'] ?? date('Y-m-d');

$tanggalValid = static function ($value): bool {
    return is_string($value)
        && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1
        && checkdate(
            (int) substr($value, 5, 2),
            (int) substr($value, 8, 2),
            (int) substr($value, 0, 4)
        );
};

$pesanError = '';

if (!$tanggalValid($awal) || !$tanggalValid($akhir) || $awal > $akhir) {
    $awal = date('Y-m-01');
    $akhir = date('Y-m-d');
    $pesanError = 'Rentang tanggal tidak valid. Periode dikembalikan ke bulan ini.';
}

$stmt = db()->prepare("
    SELECT
        p.kode,
        u.nama AS peminjam,
        p.tanggal_pinjam,
        p.tanggal_kembali_rencana,
        p.tanggal_kembali_aktual,
        p.status,
        (
            SELECT GROUP_CONCAT(
                CONCAT(a.nama, ' (', dp.jumlah, ')')
                ORDER BY a.nama SEPARATOR ', '
            )
            FROM detail_peminjaman dp
            JOIN alat a ON a.id = dp.alat_id
            WHERE dp.peminjaman_id = p.id
        ) AS daftar_barang
    FROM peminjaman p
    JOIN users u ON u.id = p.user_id
    WHERE p.tanggal_pinjam >= ?
        AND p.tanggal_pinjam < DATE_ADD(?, INTERVAL 1 DAY)
    ORDER BY p.tanggal_pinjam DESC
");

$stmt->execute([$awal, $akhir]);
$laporan = $stmt->fetchAll();

function tanggalLaporan(string $value): string
{
    return date('d/m/Y', strtotime($value));
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>

    html {
        overflow-y: scroll;
    }

    .filter-form {
        display: flex;
        align-items: end;
        flex-wrap: wrap;
        gap: var(--space-4);
        width: 100%;
    }

    .filter-form .form-group {
        flex: 1;
        min-width: 180px;
        margin-bottom: 0;
    }

    .filter-form .form-actions {
        display: flex;
        flex-wrap: wrap;
        gap: var(--space-2);
        margin-top: 0;
    }

    .report-info {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: var(--space-3);
        margin-bottom: var(--space-4);
        color: var(--color-muted);
        font-size: 0.875rem;
    }

    .report-table {
        width: 100%;
        min-width: 850px;
    }

    .report-table th,
    .report-table td {
        vertical-align: middle;
    }

    .report-empty {
        padding: var(--space-7) var(--space-4);
        text-align: center;
        color: var(--color-muted);
    }

    @media (max-width: 600px) {
        .filter-form {
            align-items: stretch;
            flex-direction: column;
        }

        .filter-form .form-group {
            width: 100%;
        }

        .filter-form .form-actions {
            width: 100%;
        }

        .filter-form .form-actions .btn {
            flex: 1;
        }
    }

    @media print {
        .no-print,
        .filter-card,
        .section-title {
            display: none !important;
        }

        .card {
            border: none !important;
            box-shadow: none !important;
        }

        .card-body {
            padding: 0 !important;
        }

        .report-table {
            width: 100%;
            min-width: 0;
        }

        .report-table th,
        .report-table td {
            padding: 7px;
            border: 1px solid #999;
            color: #000;
            font-size: 10px;
        }

        .report-table th {
            background: #eeeeee !important;
        }
    }
</style>

<div class="section-title">
    <div>
        <h1>Laporan Peminjaman</h1>
        <p class="text-muted mb-0">
            Lihat dan cetak laporan transaksi peminjaman alat laboratorium.
        </p>
    </div>
</div>

<?php if ($pesanError !== ''): ?>
    <div class="alert alert-danger">
        <?= e($pesanError) ?>
    </div>
<?php endif; ?>

<!-- Filter Periode -->
<div class="card filter-card no-print">

    <div class="card-header">
        <h2>Filter Periode Laporan</h2>
    </div>

    <div class="card-body">

        <form
            method="GET"
            action="<?= e(url('admin/laporan.php')) ?>"
        >

            <div class="filter-form">

                <div class="form-group">
                    <label for="awal" class="form-label">
                        Dari Tanggal
                    </label>

                    <input
                        type="date"
                        id="awal"
                        name="awal"
                        class="form-control"
                        value="<?= e($awal) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="akhir" class="form-label">
                        Sampai Tanggal
                    </label>

                    <input
                        type="date"
                        id="akhir"
                        name="akhir"
                        class="form-control"
                        value="<?= e($akhir) ?>"
                        required
                    >
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Filter
                    </button>

                    <button
                        type="button"
                        class="btn btn-outline"
                        onclick="window.print()"
                    >
                        Cetak Laporan
                    </button>
                </div>

            </div>

        </form>

    </div>
</div>

<!-- Daftar Laporan -->
<div class="card mt-6">

    <div class="card-header">
        <h2>Daftar Barang Dipinjam</h2>

        <span class="badge badge-info">
            <?= count($laporan) ?> transaksi
        </span>
    </div>

    <div class="card-body">

        <div class="report-info" id="periode-cetak">
            <span>
                Periode <?= tanggalLaporan($awal) ?>
                sampai <?= tanggalLaporan($akhir) ?>
            </span>
        </div>

        <div class="table-responsive">

            <table class="table report-table">

                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Peminjam</th>
                        <th>Barang (Jumlah)</th>
                        <th>Tanggal Pinjam</th>
                        <th>Batas Kembali</th>
                        <th>Kembali Aktual</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($laporan as $item): ?>
                        <tr>

                            <td>
                                <?= e($item['kode']) ?>
                            </td>

                            <td>
                                <?= e($item['peminjam']) ?>
                            </td>

                            <td>
                                <?= e(
                                    $item['daftar_barang']
                                    ?: 'Rincian barang tidak tersedia'
                                ) ?>
                            </td>

                            <td>
                                <?= tanggalLaporan($item['tanggal_pinjam']) ?>
                            </td>

                            <td>
                                <?= tanggalLaporan($item['tanggal_kembali_rencana']) ?>
                            </td>

                            <td>
                                <?= $item['tanggal_kembali_aktual']
                                    ? tanggalLaporan($item['tanggal_kembali_aktual'])
                                    : '—' ?>
                            </td>

                            <td>
                                <span class="badge badge-info">
                                    <?= e(ucfirst($item['status'])) ?>
                                </span>
                            </td>

                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($laporan)): ?>
                        <tr>
                            <td colspan="7" class="report-empty">
                                Tidak ada data peminjaman pada periode ini.
                            </td>
                        </tr>
                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>