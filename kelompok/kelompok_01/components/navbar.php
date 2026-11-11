<header class="navbar">
    <div class="navbar-left">
        <button class="menu-toggle" id="menuToggle" type="button">
            ☰
        </button>

        <div>
            <h2><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h2>
            <p>Cerdas Mengelola, Bebas Finansial</p>
        </div>
    </div>

    <div class="navbar-right">
        <div class="user-avatar">
            <?= strtoupper(substr($_SESSION['user']['nama_lengkap'] ?? 'P', 0, 1)) ?>
        </div>
        <div class="user-info">
            <strong><?= htmlspecialchars($_SESSION['user']['nama_lengkap'] ?? 'Pengguna') ?></strong>
            <small><?= htmlspecialchars(ucwords(str_replace('_', ' ', $_SESSION['user']['role'] ?? 'pengguna'))) ?></small>
        </div>
    </div>
</header>