<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$pageTitle = 'Pemasukan';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SaveSmart - Pemasukan</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/transaksi.css">
</head>

<body>

<div class="app-container">

    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <main class="main-content">

        <?php include __DIR__ . '/../components/navbar.php'; ?>

        <div class="transaction-content">

            <section class="welcome-section">
                <div>
                    <h2>Kelola Pemasukan</h2>
                    <p>Catat dan pantau seluruh pemasukan keuangan keluarga.</p>
                </div>
            </section>

            <!-- Ringkasan -->
            <section class="transaction-summary">

                <div class="summary-card">
                    <div class="summary-icon income">↙</div>
                    <p>Total Pemasukan</p>
                    <h3>Rp 0</h3>
                    <small>Bulan ini</small>
                </div>

                <div class="summary-card">
                    <div class="summary-icon balance">▣</div>
                    <p>Jumlah Transaksi</p>
                    <h3>0</h3>
                    <small>Transaksi tercatat</small>
                </div>

            </section>

            <!-- Form Pemasukan -->
            <section class="transaction-panel">

                <div class="panel-heading">
                    <h3>Tambah Pemasukan</h3>
                    <p>Masukkan informasi pemasukan keluarga.</p>
                </div>

                <form id="incomeForm">

                    <div class="form-grid">

                        <div class="form-group">
                            <label for="incomeName">Nama Pemasukan</label>
                            <input type="text" id="incomeName"
                                placeholder="Contoh: Gaji bulanan" required>
                        </div>

                        <div class="form-group">
                            <label for="incomeAmount">Nominal</label>
                            <input type="number" id="incomeAmount"
                                placeholder="Contoh: 5000000"
                                min="1" required>
                        </div>

                        <div class="form-group">
                            <label for="incomeCategory">Kategori</label>
                            <select id="incomeCategory" required>
                                <option value="">Pilih kategori</option>
                                <option value="Gaji">Gaji</option>
                                <option value="Bisnis">Bisnis</option>
                                <option value="Investasi">Investasi</option>
                                <option value="Hadiah">Hadiah</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="incomeDate">Tanggal</label>
                            <input type="date" id="incomeDate" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="incomeDescription">Keterangan</label>
                            <textarea id="incomeDescription"
                                placeholder="Tambahkan keterangan (opsional)"
                                rows="3"></textarea>
                        </div>

                    </div>

                    <div class="form-actions">
                        <button type="reset" class="btn-reset">
                            Reset
                        </button>

                        <button type="submit" class="btn-primary">
                            + Simpan Pemasukan
                        </button>
                    </div>

                </form>

            </section>

            <!-- Riwayat -->
            <section class="transaction-panel">

                <div class="panel-heading">
                    <h3>Riwayat Pemasukan</h3>
                    <p>Daftar pemasukan yang telah dicatat.</p>
                </div>

                <div class="table-wrapper">
                    <table class="transaction-table">

                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Nama</th>
                                <th>Kategori</th>
                                <th>Nominal</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>

                        <tbody id="incomeTable">
                            <tr>
                                <td colspan="5" class="table-empty">
                                    Belum ada data pemasukan.
                                </td>
                            </tr>
                        </tbody>

                    </table>
                </div>

            </section>

        </div>

    </main>
</div>

<script src="../assets/js/script.js"></script>
<script src="../assets/js/transaksi.js"></script>

</body>
</html>