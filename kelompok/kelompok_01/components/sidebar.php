<?php
$isAdmin = ($_SESSION['user']['role'] ?? '') === 'admin';
$activePage = $pageTitle ?? '';
$navigation = $isAdmin
    ? [
        ['href' => 'admin_dashboard.php', 'label' => 'Ringkasan', 'title' => 'Admin Dashboard', 'icon' => 'grid'],
        ['href' => 'admin_pengguna.php', 'label' => 'Pengguna', 'title' => 'Manajemen Pengguna', 'icon' => 'people'],
        ['href' => 'admin_kategori.php', 'label' => 'Kategori', 'title' => 'Manajemen Kategori', 'icon' => 'list'],
    ]
    : [
        ['href' => 'dashboard.php', 'label' => 'Ringkasan', 'title' => 'Dashboard', 'icon' => 'grid'],
        ['href' => 'pemasukan.php', 'label' => 'Pemasukan', 'title' => 'Pemasukan', 'icon' => 'up'],
        ['href' => 'pengeluaran.php', 'label' => 'Pengeluaran', 'title' => 'Pengeluaran', 'icon' => 'down'],
        ['href' => 'budget.php', 'label' => 'Anggaran', 'title' => 'Anggaran', 'icon' => 'list'],
        ['href' => 'tabungan.php', 'label' => 'Tabungan', 'title' => 'Tabungan', 'icon' => 'target'],
        ['href' => 'laporan.php', 'label' => 'Laporan', 'title' => 'Laporan', 'icon' => 'chart'],
    ];
?>

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

            <a href="pemasukan.php" class="menu-item <?= ($pageTitle ?? '') === 'Pemasukan' ? 'active' : '' ?>">
                <span>↙</span> Pemasukan
            </a>

            <a href="pengeluaran.php" class="menu-item <?= ($pageTitle ?? '') === 'Pengeluaran' ? 'active' : '' ?>">
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

    <a href="../logout.php" class="sidebar-logout" id="logoutBtn">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 5H5v14h5m5-4 4-3-4-3m4 3H9"/></svg>
        <span>Keluar</span>
    </a>
</aside>

<div class="modal-overlay" id="logoutModal" hidden>
    <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="logoutTitle">
        <span class="modal-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none"><path d="M10 5H5v14h5m5-4 4-3-4-3m4 3H9"/></svg>
        </span>
        <h3 id="logoutTitle">Keluar dari SaveSmart?</h3>
        <p>Anda perlu masuk lagi untuk melihat catatan keluarga.</p>

        <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn-cancel" id="logoutCancel">Tetap di sini</button>
            <a href="../logout.php" class="modal-btn modal-btn-danger">Keluar</a>
        </div>
    </div>
</div>
