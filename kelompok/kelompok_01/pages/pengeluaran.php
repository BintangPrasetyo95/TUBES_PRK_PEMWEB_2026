<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$pageTitle = 'Pengeluaran';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SaveSmart - Pengeluaran</title>

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
                    <h2>Kelola Pengeluaran</h2>
                    <p>Catat dan pantau seluruh pengeluaran keuangan keluarga.</p>
                </div>
            </section>

            <!-- Ringkasan -->
            <section class="transaction-summary">

                <div class="summary-card">
                    <div class="summary-icon expense">↗</div>
                    <p>Total Pengeluaran</p>
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

            <!-- Form Pengeluaran -->
            <section class="transaction-panel">

                <div class="panel-heading">
                    <h3>Tambah Pengeluaran</h3>
                    <p>Masukkan informasi pengeluaran keluarga.</p>
                </div>

                <form id="expenseForm">

                    <div class="form-grid">

                        <div class="form-group">
                            <label for="expenseName">Nama Pengeluaran</label>
                            <input type="text" id="expenseName"
                                placeholder="Contoh: Belanja bulanan" required>
                        </div>

                        <div class="form-group">
                            <label for="expenseAmount">Nominal</label>
                            <input type="number" id="expenseAmount"
                                placeholder="Contoh: 500000"
                                min="1" required>
                        </div>

                        <div class="form-group">
                            <label for="expenseCategory">Kategori</label>
                            <select id="expenseCategory" required>
                                <option value="">Pilih kategori</option>
                                <option value="Makanan">Makanan</option>
                                <option value="Transportasi">Transportasi</option>
                                <option value="Tagihan">Tagihan</option>
                                <option value="Pendidikan">Pendidikan</option>
                                <option value="Kesehatan">Kesehatan</option>
                                <option value="Belanja">Belanja</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="expenseDate">Tanggal</label>
                            <input type="date" id="expenseDate" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="expenseDescription">Keterangan</label>
                            <textarea id="expenseDescription"
                                placeholder="Tambahkan keterangan (opsional)"
                                rows="3"></textarea>
                        </div>

                    </div>

                    <div class="form-actions">
                        <button type="reset" class="btn-reset">
                            Reset
                        </button>

                        <button type="submit" class="btn-primary">
                            + Simpan Pengeluaran
                        </button>
                    </div>

                </form>

            </section>

            <!-- Riwayat Pengeluaran -->
            <section class="transaction-panel">

                <div class="panel-heading">
                    <h3>Riwayat Pengeluaran</h3>
                    <p>Daftar pengeluaran yang telah dicatat.</p>
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

                        <tbody id="expenseTable">
                            <tr>
                                <td colspan="5" class="table-empty">
                                    Belum ada data pengeluaran.
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
<script src="../assets/js/pengeluaran.js"></script>

</body>
</html>