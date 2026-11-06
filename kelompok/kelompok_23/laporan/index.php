<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

function parse_date(string $value): ?DateTimeImmutable
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $value) {
        return null;
    }
    return $date;
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function rupiah(float|string $amount): string
{
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

$today = new DateTimeImmutable('today');
$start_input = is_string($_GET['tanggal_mulai'] ?? null) ? trim($_GET['tanggal_mulai']) : $today->modify('first day of this month')->format('Y-m-d');
$end_input = is_string($_GET['tanggal_akhir'] ?? null) ? trim($_GET['tanggal_akhir']) : $today->format('Y-m-d');
$start_date = parse_date($start_input);
$end_date = parse_date($end_input);
$error = '';

if (!$start_date || !$end_date) {
    $error = 'Format tanggal tidak valid. Gunakan tanggal mulai dan akhir yang benar.';
} elseif ($start_date > $end_date) {
    $error = 'Tanggal mulai tidak boleh melewati tanggal akhir.';
}

$transactions = [];
$summary = ['transaction_count' => 0, 'revenue' => 0, 'items_sold' => 0];
if ($error === '') {
    $start_bound = $start_date->format('Y-m-d 00:00:00');
    $end_bound = $end_date->modify('+1 day')->format('Y-m-d 00:00:00');

    $summary_stmt = $conn->prepare("
        SELECT COUNT(*) AS transaction_count, COALESCE(SUM(total), 0) AS revenue
        FROM transactions
        WHERE transaction_date >= ? AND transaction_date < ?
    ");
    if (!$summary_stmt) {
        throw new RuntimeException("Gagal menyiapkan ringkasan laporan: " . $conn->error);
    }
    $summary_stmt->bind_param("ss", $start_bound, $end_bound);
    $summary_stmt->execute();
    $summary = array_merge($summary, $summary_stmt->get_result()->fetch_assoc());
    $summary_stmt->close();

    $items_stmt = $conn->prepare("
        SELECT COALESCE(SUM(td.quantity), 0) AS items_sold
        FROM transaction_details td
        INNER JOIN transactions t ON t.id = td.transaction_id
        WHERE t.transaction_date >= ? AND t.transaction_date < ?
    ");
    if (!$items_stmt) {
        throw new RuntimeException("Gagal menyiapkan jumlah barang laporan: " . $conn->error);
    }
    $items_stmt->bind_param("ss", $start_bound, $end_bound);
    $items_stmt->execute();
    $summary['items_sold'] = (int)$items_stmt->get_result()->fetch_assoc()['items_sold'];
    $items_stmt->close();

    $transactions_stmt = $conn->prepare("
        SELECT t.id, t.invoice_number, t.transaction_date, t.total, t.payment_method,
               u.name AS cashier_name, COALESCE(SUM(td.quantity), 0) AS items_sold
        FROM transactions t
        INNER JOIN users u ON u.id = t.user_id
        LEFT JOIN transaction_details td ON td.transaction_id = t.id
        WHERE t.transaction_date >= ? AND t.transaction_date < ?
        GROUP BY t.id, t.invoice_number, t.transaction_date, t.total, t.payment_method, u.name
        ORDER BY t.transaction_date DESC, t.id DESC
    ");
    if (!$transactions_stmt) {
        throw new RuntimeException("Gagal menyiapkan daftar laporan: " . $conn->error);
    }
    $transactions_stmt->bind_param("ss", $start_bound, $end_bound);
    $transactions_stmt->execute();
    $transactions = $transactions_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $transactions_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Penjualan | FEBMart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { min-height:100vh; margin:0; padding:35px 20px; background:#f3f4f6; font-family:Arial,sans-serif; }
        .main-container { width:100%; max-width:1200px; margin:0 auto; overflow:hidden; background:#fff; border-radius:14px; box-shadow:0 5px 25px rgba(0,0,0,.08); }
        .header-section { padding:30px 35px; color:#fff; background:linear-gradient(145deg,#29245c,#5142a5); }
        .brand { margin-bottom:25px; font-size:23px; font-weight:bold; }
        .brand i { margin-right:7px; color:#ffad42; }
        .header-section h2 { margin-bottom:8px; font-size:25px; font-weight:bold; }
        .header-section p { margin:0; color:#d5d9e2; font-size:14px; }
        .content-section { padding:35px; }
        .summary-card { height:100%; padding:20px; border:1px solid #e8e7f0; border-radius:10px; }
        .summary-card .label { color:#777; font-size:13px; }
        .summary-card .value { margin-top:8px; color:#29245c; font-size:22px; font-weight:bold; }
        .table { font-size:13px; }
        .table thead th { padding:14px 12px; color:#fff; background:#29245c; white-space:nowrap; }
        .table tbody td { padding:13px 12px; vertical-align:middle; }
        .btn-back { display:inline-block; padding:10px 16px; border:1px solid #ddd; border-radius:7px; color:#5142a5; background:#f5f4fb; font-weight:bold; text-decoration:none; }
        @media(max-width:768px) { body { padding:12px; } .header-section,.content-section { padding:22px; } }
    </style>
</head>
<body>
<main class="main-container">
    <header class="header-section">
        <div class="brand"><i class="bi bi-cart3"></i>FEBMart</div>
        <h2><i class="bi bi-bar-chart-line me-2"></i>Laporan Penjualan</h2>
        <p>Ringkasan dan daftar penjualan berdasarkan periode tanggal.</p>
    </header>
    <section class="content-section">
        <form method="GET" class="row g-3 align-items-end mb-4">
            <div class="col-sm-5 col-lg-3">
                <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" value="<?= h($start_input); ?>" required>
            </div>
            <div class="col-sm-5 col-lg-3">
                <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                <input type="date" class="form-control" id="tanggal_akhir" name="tanggal_akhir" value="<?= h($end_input); ?>" required>
            </div>
            <div class="col-sm-2">
                <button class="btn btn-primary w-100" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button>
            </div>
            <div class="col-sm-2 col-lg-auto">
                <a class="btn btn-outline-secondary w-100" href="index.php"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</a>
            </div>
            <div class="col-lg-auto ms-lg-auto">
                <?php if ($error === ''): ?>
                    <a class="btn btn-success" href="export.php?tanggal_mulai=<?= rawurlencode($start_input); ?>&amp;tanggal_akhir=<?= rawurlencode($end_input); ?>">
                        <i class="bi bi-file-earmark-excel me-1"></i>Ekspor Excel
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle me-2"></i><?= h($error); ?></div>
        <?php else: ?>
            <p class="text-muted mb-3">Periode <?= h($start_date->format('d/m/Y')); ?> – <?= h($end_date->format('d/m/Y')); ?></p>
            <div class="row g-3 mb-4">
                <div class="col-md-4"><div class="summary-card"><div class="label"><i class="bi bi-cash-stack me-1"></i>Total Omzet</div><div class="value"><?= rupiah($summary['revenue']); ?></div></div></div>
                <div class="col-md-4"><div class="summary-card"><div class="label"><i class="bi bi-receipt me-1"></i>Jumlah Transaksi</div><div class="value"><?= number_format((int)$summary['transaction_count']); ?></div></div></div>
                <div class="col-md-4"><div class="summary-card"><div class="label"><i class="bi bi-box-seam me-1"></i>Barang Terjual</div><div class="value"><?= number_format((int)$summary['items_sold']); ?></div></div></div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead><tr><th>No</th><th>No. Invoice</th><th>Tanggal</th><th>Kasir</th><th>Barang</th><th>Pembayaran</th><th>Total Penjualan</th></tr></thead>
                    <tbody>
                    <?php if (!$transactions): ?>
                        <tr><td colspan="7" class="py-5 text-center text-muted"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Tidak ada transaksi pada periode ini.</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $index => $transaction): ?>
                            <tr>
                                <td><?= $index + 1; ?></td>
                                <td><?= h($transaction['invoice_number']); ?></td>
                                <td><?= h(date('d/m/Y H:i', strtotime($transaction['transaction_date']))); ?></td>
                                <td><?= h($transaction['cashier_name']); ?></td>
                                <td><?= (int)$transaction['items_sold']; ?></td>
                                <td><?= h(strtoupper($transaction['payment_method'])); ?></td>
                                <td class="text-nowrap"><?= rupiah($transaction['total']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <a href="../dashboard.php" class="btn-back"><i class="bi bi-arrow-left me-1"></i>Kembali ke Dashboard</a>
            <a href="../riwayat/index.php" class="btn-back"><i class="bi bi-clock-history me-1"></i>Riwayat Transaksi</a>
        </div>
    </section>
</main>
</body>
</html>
