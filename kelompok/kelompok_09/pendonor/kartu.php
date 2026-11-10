<?php
require_once __DIR__ . '/../config/auth_check.php';
if (!isLoggedIn()) {
    header('Location: ' . base_url('auth/login.php'));
    exit;
}
cekRole(['pendonor']);

$query = $db->prepare('SELECT id, nama, gol_darah, rhesus, tgl_donor_terakhir FROM users WHERE id = ?');
$query->execute([currentUser()['id']]);
$profil = $query->fetch();
$pageTitle = 'Kartu Pendonor | OneBlood';
require __DIR__ . '/../includes/header.php';
?>
<style media="print">
    body > :not(main), .no-print { display: none !important; }
    .inv-wrapper, .inv-card { background: white; box-shadow: none; padding: 0; }
</style>
<main class="inv-wrapper">
    <div class="inv-head"><h1>Kartu Pendonor</h1></div>
    <section class="inv-card">
        <p class="no-print"><a href="dashboard.php">Kembali ke Dashboard</a></p>
        <?php if (!$profil): ?>
            <p>Profil pendonor tidak ditemukan.</p>
        <?php else: ?>
            <h2>OneBlood · <?= e($profil['nama']) ?></h2>
            <p>ID Pendonor <strong>#<?= (int)$profil['id'] ?></strong></p>
            <p>Golongan darah <strong class="inv-badge"><?= e($profil['gol_darah'] ? $profil['gol_darah'] . $profil['rhesus'] : 'Belum diisi') ?></strong></p>
            <p>Donor terakhir <strong><?= e(tgl_indo($profil['tgl_donor_terakhir'])) ?></strong></p>
            <button class="inv-btn no-print" type="button" onclick="window.print()">Cetak Kartu</button>
        <?php endif; ?>
    </section>
</main>
<?php
$hideSidebar = true;
require __DIR__ . '/../includes/footer.php';
