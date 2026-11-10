<?php
require_once __DIR__ . '/../config/auth_check.php';
if (!isLoggedIn()) {
    header('Location: ' . base_url('auth/login.php'));
    exit;
}
cekRole(['pendonor']);

$query = $db->prepare('SELECT kode_tiket, status_skrining, catatan_medis, nama_event, lokasi, tanggal, jam_mulai, jam_selesai
    FROM antrean_skrining a JOIN events e ON e.id = a.event_id WHERE a.user_id = ? ORDER BY e.tanggal DESC, a.id DESC');
$query->execute([currentUser()['id']]);
$riwayat = $query->fetchAll();
$pageTitle = 'Riwayat Donor | OneBlood';
require __DIR__ . '/../includes/header.php';
?>
<main class="inv-wrapper">
    <div class="inv-head"><h1>Riwayat Donor</h1></div>
    <section class="inv-card">
        <p><a href="dashboard.php">Kembali ke Dashboard</a> · <a href="booking.php">Booking Donor</a></p>
        <?php if (!$riwayat): ?>
            <p>Belum ada riwayat donor.</p>
        <?php else: ?>
            <table aria-label="Riwayat booking dan hasil skrining">
                <thead><tr><th scope="col">Tiket</th><th scope="col">Jadwal</th><th scope="col">Status</th><th scope="col">Catatan Petugas</th></tr></thead>
                <tbody>
                    <?php foreach ($riwayat as $item): ?>
                        <tr>
                            <td><strong><?= e($item['kode_tiket']) ?></strong></td>
                            <td><?= e($item['nama_event']) ?> · <?= e($item['lokasi']) ?><br>
                                <?= e(tgl_indo($item['tanggal'])) ?>, <?= e(substr($item['jam_mulai'], 0, 5)) ?>–<?= e(substr($item['jam_selesai'], 0, 5)) ?> WIB</td>
                            <td><span class="inv-badge"><?= e(ucfirst($item['status_skrining'])) ?></span></td>
                            <td><?= e($item['catatan_medis'] ?: '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>
<?php
$hideSidebar = true;
require __DIR__ . '/../includes/footer.php';
