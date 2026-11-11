<aside class="sidebar" id="sidebar">
    <?php $role = $_SESSION['user']['role'] ?? ''; ?>

    <div class="brand">
        <div class="brand-logo">S</div>
        <div class="brand-info">
            <h2>SaveSmart</h2>
            <small><?= $role === 'admin' ? 'Administrator' : 'Family Finance' ?></small>
        </div>
    </div>

    <nav class="sidebar-menu">

        <?php if ($role === 'admin'): ?>

            <span class="menu-heading">ADMINISTRATOR</span>

            <a href="admin_dashboard.php"
               class="menu-item <?= ($pageTitle ?? '') === 'Admin Dashboard' ? 'active' : '' ?>">
                <span>▦</span>
                <span>Dashboard</span>
            </a>

            <a href="admin_pengguna.php"
               class="menu-item <?= ($pageTitle ?? '') === 'Manajemen Pengguna' ? 'active' : '' ?>">
                <span>♙</span>
                <span>Manajemen Pengguna</span>
            </a>

            <a href="admin_kategori.php"
               class="menu-item <?= ($pageTitle ?? '') === 'Manajemen Kategori' ? 'active' : '' ?>">
                <span>▤</span>
                <span>Manajemen Kategori</span>
            </a>

        <?php else: ?>

            <span class="menu-heading">MENU UTAMA</span>

            <a href="dashboard.php"
               class="menu-item <?= ($pageTitle ?? '') === 'Dashboard' ? 'active' : '' ?>">
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

            <?php if ($role === 'kepala_keluarga'): ?>
                <span class="menu-heading">KELOLA KELUARGA</span>

                <a href="anggota_keluarga.php"
                   class="menu-item <?= ($pageTitle ?? '') === 'Anggota Keluarga' ? 'active' : '' ?>">
                    <span>♧</span> Anggota Keluarga
                </a>
            <?php endif; ?>

        <?php endif; ?>

    </nav>

    <a href="../logout.php" class="sidebar-logout">
        <span>↪</span>
        <span>Logout</span>
    </a>
</aside>