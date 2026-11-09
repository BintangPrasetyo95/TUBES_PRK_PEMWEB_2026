<?php
require_once __DIR__ . '/../config/auth_check.php';
if (!isLoggedIn()) {
    header('Location: ' . base_url('auth/login.php'));
    exit;
}
cekRole(['pendonor']);

$query = $db->prepare("SELECT COUNT(*) AS total, COUNT(CASE WHEN status_skrining = 'menunggu' THEN 1 END) AS menunggu,
    COUNT(CASE WHEN status_skrining = 'selesai' THEN 1 END) AS selesai FROM antrean_skrining WHERE user_id = ?");
$query->execute([currentUser()['id']]);
$ringkasan = $query->fetch();
$pageTitle = 'Dashboard Pendonor | OneBlood';
require __DIR__ . '/../includes/header.php';
?>
<main class="inv-wrapper">
    <div class="inv-head"><h1>Halo, <?= e(currentUser()['nama']) ?></h1></div>
    <section class="inv-card">
        <h2>Ringkasan Donor</h2>
        <p><?= (int)$ringkasan['total'] ?> booking · <?= (int)$ringkasan['menunggu'] ?> menunggu skrining · <?= (int)$ringkasan['selesai'] ?> donor selesai</p>
        <nav aria-label="Menu pendonor">
            <ul class="nav-menu">
                <li><a class="btn-pink" href="booking.php">Booking Donor</a></li>
                <li><a href="riwayat.php">Riwayat Donor</a></li>
                <li><a href="kartu.php">Kartu Pendonor</a></li>
                <li><a href="../auth/logout.php">Keluar</a></li>
            </ul>
        </nav>
    </section>
</main>
<?php
$hideSidebar = true;
require __DIR__ . '/../includes/footer.php';
