<?php
require_once __DIR__ . '/../config/auth_check.php';
if (!isLoggedIn()) {
    header('Location: ' . base_url('auth/login.php'));
    exit;
}
cekRole(['pendonor']);

$userId = currentUser()['id'];
$_SESSION['booking_token'] ??= bin2hex(random_bytes(32));
$pertanyaan = [
    'q_sehat' => 'Apakah kamu merasa sehat hari ini?',
    'q_tidur_cukup' => 'Apakah kamu tidur setidaknya 5 jam?',
    'q_bebas_obat' => 'Apakah kamu bebas dari konsumsi antibiotik atau obat dalam 3 hari terakhir?',
];
$jawaban = array_map(fn($kolom) => $_POST[$kolom] ?? '', array_keys($pertanyaan));
$eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
$error = '';

// Gunakan waktu Jakarta dari konfigurasi proyek untuk menutup booking.
$terbuka = "status_event IN ('akan_datang', 'berlangsung') AND TIMESTAMP(tanggal, jam_selesai) > ?";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['booking_token'], $token)) {
        http_response_code(403);
        $error = 'Form sudah tidak berlaku. Silakan coba lagi.';
    } elseif ($eventId <= 0 || array_filter($jawaban, fn($nilai) => !in_array($nilai, ['0', '1'], true))) {
        $error = 'Pilih jadwal dan jawab semua pertanyaan.';
    } else {
        try {
            $db->beginTransaction();
            // Kunci event agar dua booking terakhir tidak melampaui kuota.
            $query = $db->prepare('SELECT kuota FROM events WHERE id = ? FOR UPDATE');
            $query->execute([$eventId]);
            $kuota = $query->fetchColumn();
            $query = $db->prepare("SELECT id FROM events WHERE id = ? AND $terbuka");
            $query->execute([$eventId, date('Y-m-d H:i:s')]);
            if (!$query->fetchColumn()) {
                $error = 'Jadwal ini sudah ditutup atau tidak tersedia.';
            } else {
                $query = $db->prepare('SELECT COUNT(*) AS total, COUNT(CASE WHEN user_id = ? THEN 1 END) AS milik_saya FROM antrean_skrining WHERE event_id = ?');
                $query->execute([$userId, $eventId]);
                $antrean = $query->fetch();
                if ($antrean['milik_saya'] > 0) {
                    $error = 'Kamu sudah mendaftar pada jadwal ini.';
                } elseif ($antrean['total'] >= $kuota) {
                    $error = 'Kuota jadwal ini sudah penuh.';
                } else {
                    $tiket = 'OB-' . strtoupper(bin2hex(random_bytes(8)));
                    $db->prepare('INSERT INTO antrean_skrining (user_id, event_id, kode_tiket, q_sehat, q_tidur_cukup, q_bebas_obat)
                        VALUES (?, ?, ?, ?, ?, ?)')->execute([$userId, $eventId, $tiket, ...$jawaban]);
                    $db->commit();
                    setFlash('success', 'Booking berhasil. Kode tiket: ' . $tiket);
                    header('Location: riwayat.php', true, 303);
                    exit;
                }
            }
            $db->rollBack();
        } catch (PDOException $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log($exception->getMessage());
            $error = 'Booking gagal disimpan. Silakan coba lagi.';
        }
    }
}

$query = $db->prepare("SELECT e.id, nama_event, lokasi, tanggal, jam_mulai, jam_selesai,
    kuota - COUNT(a.id) AS sisa, COUNT(CASE WHEN a.user_id = ? THEN 1 END) AS sudah_daftar
    FROM events e LEFT JOIN antrean_skrining a ON a.event_id = e.id WHERE $terbuka
    GROUP BY e.id, nama_event, lokasi, tanggal, jam_mulai, jam_selesai, kuota ORDER BY tanggal, jam_mulai, e.id");
$query->execute([$userId, date('Y-m-d H:i:s')]);
$jadwal = $query->fetchAll();
$bisaBooking = array_filter($jadwal, fn($event) => $event['sisa'] > 0 && !$event['sudah_daftar']);
$pageTitle = 'Booking Donor | OneBlood';
require __DIR__ . '/../includes/header.php';
?>
<main class="inv-wrapper">
    <div class="inv-head"><h1>Booking Donor</h1></div>
    <section class="inv-card">
        <p><a href="dashboard.php">Kembali ke Dashboard</a> · <a href="riwayat.php">Riwayat Donor</a></p>
        <?php if ($error !== ''): ?><p class="inv-error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <?php if (!$jadwal): ?>
            <p>Belum ada jadwal donor yang terbuka.</p>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="token" value="<?= e($_SESSION['booking_token']) ?>">
                <fieldset>
                    <legend>Pilih jadwal donor</legend>
                    <?php foreach ($jadwal as $event): ?>
                        <p><label>
                            <input type="radio" name="event_id" value="<?= (int)$event['id'] ?>" required
                                <?= $eventId === (int)$event['id'] ? 'checked' : '' ?> <?= $event['sisa'] <= 0 || $event['sudah_daftar'] ? 'disabled' : '' ?>>
                            <strong><?= e($event['nama_event']) ?></strong> · <?= e($event['lokasi']) ?> · <?= e(tgl_indo($event['tanggal'])) ?>,
                            <?= e(substr($event['jam_mulai'], 0, 5)) ?>–<?= e(substr($event['jam_selesai'], 0, 5)) ?> WIB ·
                            <?= $event['sudah_daftar'] ? 'Sudah terdaftar' : 'Sisa kuota ' . max(0, (int)$event['sisa']) ?>
                        </label></p>
                    <?php endforeach; ?>
                </fieldset>
                <?php if ($bisaBooking): ?>
                    <p>Isi jawaban awal berikut. Hasil skrining ditentukan oleh petugas.</p>
                    <?php foreach ($pertanyaan as $kolom => $label): ?>
                        <fieldset>
                            <legend><?= e($label) ?></legend>
                            <?php foreach (['1' => 'Ya', '0' => 'Tidak'] as $nilai => $teks): ?>
                                <label><input type="radio" name="<?= e($kolom) ?>" value="<?= $nilai ?>" required
                                    <?= ($_POST[$kolom] ?? '') === (string)$nilai ? 'checked' : '' ?>> <?= e($teks) ?></label>
                            <?php endforeach; ?>
                        </fieldset>
                    <?php endforeach; ?>
                    <p><button class="inv-btn" type="submit">Daftar Donor</button></p>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </section>
</main>
<?php
$hideSidebar = true;
require __DIR__ . '/../includes/footer.php';
