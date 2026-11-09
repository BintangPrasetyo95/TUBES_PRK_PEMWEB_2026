
<aside class="sidebar" id="sidebar">
    <div class="brand">
        <div class="brand-logo">S</div>
        <div class="brand-info">
            <h2>SaveSmart</h2>
            <small>Family Finance</small>
        </div>
    </div>

    <nav class="sidebar-menu">
        <span class="menu-heading">MENU UTAMA</span>

        <a href="dashboard.php" class="menu-item <?= ($pageTitle ?? '') === 'Dashboard' ? 'active' : '' ?>">
            <span>▦</span> Dashboard
        </a>

        <a href="pemasukan.php" class="menu-item">
            <span>↙</span> Pemasukan
        </a>

        <a href="pengeluaran.php" class="menu-item">
            <span>↗</span> Pengeluaran
        </a>

        <span class="menu-heading">KEUANGAN</span>

        <a href="budget.php" class="menu-item">
            <span>▤</span> Anggaran
        </a>

        <a href="tabungan.php" class="menu-item">
            <span>◎</span> Tabungan
        </a>

        <a href="laporan.php" class="menu-item">
            <span>▥</span> Laporan
        </a>
    </nav>
</aside> 