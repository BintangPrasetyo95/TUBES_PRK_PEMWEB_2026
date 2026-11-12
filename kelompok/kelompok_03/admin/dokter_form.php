
<?php
require_once __DIR__ . '/../includes/cek_login.php';

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak. Hanya admin yang dapat mengakses halaman ini.');
}

$id = $_GET['id'] ?? null;
$isEdit = !empty($id);

$dokter = [
    'id' => '',
    'nama' => '',
    'spesialis' => '',
    'kuota_per_hari' => '',
    'hari_praktik' => ''
];

$error = '';

if ($isEdit) {
    try {
        $stmt = $pdo->prepare('SELECT id, nama, spesialis, kuota_per_hari, hari_praktik FROM dokter WHERE id = ?');
        $stmt->execute([$id]);
        $fetchedDokter = $stmt->fetch();
        
        if ($fetchedDokter) {
            $dokter = $fetchedDokter;
        } else {
            header('Location: dokter_list.php?error=' . urlencode('Dokter tidak ditemukan.'));
            exit;
        }
    } catch (PDOException $e) {
        error_log('[Dokter Form] ' . $e->getMessage());
        $error = 'Gagal memuat data dokter.';
    }
}

$hariOptions = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
$selectedHari = $isEdit ? explode(',', $dokter['hari_praktik']) : [];

// Memanggil Header Global (sudah include CSS & Nav)
require_once '../includes/header.php';
?>

<div class="wadah-form" style="padding: 0;">
    <div class="baris-judul">
        <div>
            <h2 class="judul"><?= $isEdit ? 'Edit' : 'Tambah' ?> Dokter</h2>
            <p class="muted small">Silakan isi data dokter di bawah ini.</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="peringatan peringatan-bahaya" role="alert">
            <span><?= htmlspecialchars($error) ?></span>
            <button type="button" class="tutup" data-tutup aria-label="Tutup">&times;</button>
        </div>
    <?php endif; ?>

    <div class="kartu">
        <div class="kartu-isi">
            <form id="form-dokter" method="POST" action="dokter_simpan.php">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= htmlspecialchars($dokter['id']) ?>">
                <?php endif; ?>

                <div class="grup-form">
                    <label for="nama">Nama Dokter</label>
                    <input type="text" class="masukan" id="nama" name="nama" value="<?= htmlspecialchars($dokter['nama']) ?>" required>
                </div>

                <div class="grup-form">
                    <label for="spesialis">Spesialis</label>
                    <input type="text" class="masukan" id="spesialis" name="spesialis" value="<?= htmlspecialchars($dokter['spesialis']) ?>" required>
                </div>

                <div class="grup-form">
                    <label for="kuota_per_hari">Kuota Per Hari</label>
                    <input type="number" class="masukan" id="kuota_per_hari" name="kuota_per_hari" value="<?= htmlspecialchars($dokter['kuota_per_hari']) ?>" min="1" required>
                </div>

                <div class="grup-form">
                    <label>Hari Praktik</label>
                    <div class="kotak-hari">
                        <?php foreach ($hariOptions as $hari): ?>
                            <div class="cek">
                                <input type="checkbox" name="hari_praktik[]" id="hari_<?= $hari ?>" value="<?= $hari ?>" <?= in_array($hari, $selectedHari) ? 'checked' : '' ?>>
                                <label for="hari_<?= $hari ?>"><?= $hari ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <hr class="garis">

                <div class="baris-aksi">
                    <a href="dokter_list.php" class="tombol tombol-terang">Batal</a>
                    <button type="submit" class="tombol tombol-utama">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="admin.js"></script>
<?php 
require_once '../includes/footer.php'; 
?>