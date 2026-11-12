
<aside class="admin-sidebar">
    <div class="admin-logo">
        KlikObat
    </div>

    <nav>
        <ul class="admin-menu">
            <li>
                <a href="../kategori/index.php" class="<?= ($adminSection ?? '') === 'kategori' ? 'active' : '' ?>">Data Kategori</a>
            </li>
            <li>
                <a href="../obat/index.php" class="<?= ($adminSection ?? '') === 'obat' ? 'active' : '' ?>">Data Obat</a>
            </li>
            <li>
                <a href="../logout.php">Keluar</a>
            </li>
        </ul>
    </nav>
</aside>
