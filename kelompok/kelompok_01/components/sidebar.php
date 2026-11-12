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
    <a class="brand" href="<?= $isAdmin ? 'admin_dashboard.php' : 'dashboard.php' ?>" aria-label="SaveSmart, halaman utama">
        <span class="brand-logo" aria-hidden="true">S</span>
        <span class="brand-info">
            <strong>SaveSmart</strong>
            <small><?= $isAdmin ? 'Ruang pengelola' : 'Catatan keluarga' ?></small>
        </span>
    </a>

    <nav class="sidebar-menu" aria-label="Menu utama">
        <p class="menu-heading"><?= $isAdmin ? 'Pengelolaan' : 'Keuangan keluarga' ?></p>
        <?php foreach ($navigation as $item): ?>
            <?php $isActive = $activePage === $item['title']; ?>
            <a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"
               class="menu-item<?= $isActive ? ' active' : '' ?>"
               <?= $isActive ? 'aria-current="page"' : '' ?>>
                <span class="menu-icon" aria-hidden="true">
                    <?php if ($item['icon'] === 'grid'): ?>
                        <svg viewBox="0 0 24 24" fill="none"><rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/></svg>
                    <?php elseif ($item['icon'] === 'up'): ?>
                        <svg viewBox="0 0 24 24" fill="none"><path d="M12 19V5m-6 6 6-6 6 6"/></svg>
                    <?php elseif ($item['icon'] === 'down'): ?>
                        <svg viewBox="0 0 24 24" fill="none"><path d="M12 5v14m6-6-6 6-6-6"/></svg>
                    <?php elseif ($item['icon'] === 'target'): ?>
                        <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3"/><path d="m14 10 5-5"/></svg>
                    <?php elseif ($item['icon'] === 'chart'): ?>
                        <svg viewBox="0 0 24 24" fill="none"><path d="M4 19V5m0 14h16"/><path d="m7 15 4-4 3 2 5-6"/></svg>
                    <?php elseif ($item['icon'] === 'people'): ?>
                        <svg viewBox="0 0 24 24" fill="none"><circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0m1-8a3 3 0 1 0 0-6m1 8a5 5 0 0 1 4 5"/></svg>
                    <?php else: ?>
                        <svg viewBox="0 0 24 24" fill="none"><path d="M6 5h12M6 12h12M6 19h12"/><circle cx="4" cy="5" r=".5"/><circle cx="4" cy="12" r=".5"/><circle cx="4" cy="19" r=".5"/></svg>
                    <?php endif; ?>
                </span>
                <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
        <?php endforeach; ?>
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
