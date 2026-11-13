<?php
// user/laporan_saya.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

wajibLogin();

$userId = $_SESSION['user_id'];

// Mengambil filter dari URL
$filterJenis  = trim($_GET['jenis'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

try {
    $sql = "
        SELECT 
            l.id,
            l.jenis,
            l.judul,
            l.isi,
            l.lokasi,
            l.foto,
            l.is_public,
            l.status,
            l.created_at,
            k.nama_kategori,
            (
                SELECT isi_tanggapan 
                FROM tanggapan t 
                WHERE t.laporan_id = l.id 
                ORDER BY t.created_at DESC 
                LIMIT 1
            ) AS tanggapan_terakhir
        FROM laporan l
        LEFT JOIN kategori k ON l.kategori_id = k.id
        WHERE l.user_id = :user_id
    ";

    $params = [':user_id' => $userId];

    if (!empty($filterJenis) && in_array($filterJenis, ['pengaduan', 'aspirasi'], true)) {
        $sql .= " AND l.jenis = :jenis";
        $params[':jenis'] = $filterJenis;
    }

    if (!empty($filterStatus) && in_array($filterStatus, ['menunggu', 'diproses', 'selesai'], true)) {
        $sql .= " AND l.status = :status";
        $params[':status'] = $filterStatus;
    }

    $sql .= " ORDER BY l.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $daftarLaporan = $stmt->fetchAll();

} catch (PDOException $e) {
    $daftarLaporan = [];
}

$isFiltered = (!empty($filterJenis) || !empty($filterStatus));
$pageTitle  = 'Laporan Saya';
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 950px; margin: 0 auto; padding-bottom: 2rem;">
    
    <!-- Header Halaman -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.75rem;">
        <div>
            <h1 style="color: var(--color-primary); font-size: 1.75rem; margin-bottom: 0.35rem;">Riwayat Laporan Saya</h1>
            <p style="color: var(--color-text-muted); font-size: 0.95rem; margin: 0;">
                Pantau perkembangan penanganan pengaduan & aspirasi yang telah Anda kirimkan.
            </p>
        </div>
        <a href="/user/kirim.php" class="btn btn-primary" style="font-size: 0.95rem;">
            + Buat Laporan Baru
        </a>
    </div>

    <!-- Kotak Filter Lega -->
    <div class="card" style="padding: 1.5rem; margin-bottom: 2rem;">
        <form method="GET" action="/user/laporan_saya.php" style="display: flex; gap: 1.25rem; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 180px;">
                <label for="filter-jenis" class="form-label">Jenis Laporan:</label>
                <select id="filter-jenis" name="jenis" class="form-control">
                    <option value="">-- Semua Jenis --</option>
                    <option value="pengaduan" <?= ($filterJenis === 'pengaduan') ? 'selected' : '' ?>>Pengaduan</option>
                    <option value="aspirasi" <?= ($filterJenis === 'aspirasi') ? 'selected' : '' ?>>Aspirasi</option>
                </select>
            </div>

            <div style="flex: 1; min-width: 180px;">
                <label for="filter-status" class="form-label">Status Penanganan:</label>
                <select id="filter-status" name="status" class="form-control">
                    <option value="">-- Semua Status --</option>
                    <option value="menunggu" <?= ($filterStatus === 'menunggu') ? 'selected' : '' ?>>Menunggu</option>
                    <option value="diproses" <?= ($filterStatus === 'diproses') ? 'selected' : '' ?>>Diproses</option>
                    <option value="selesai" <?= ($filterStatus === 'selesai') ? 'selected' : '' ?>>Selesai</option>
                </select>
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                <?php if ($isFiltered): ?>
                    <a href="/user/laporan_saya.php" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Daftar Laporan (Format Kartu - Lega & Enak Dibaca) -->
    <?php if (empty($daftarLaporan)): ?>
        <div class="card" style="text-align: center; padding: 3.5rem 1.5rem; color: var(--color-text-muted);">
            <?php if ($isFiltered): ?>
                <p style="font-size: 1.1rem; margin-bottom: 0.75rem;">Tidak ada laporan yang sesuai dengan filter Anda.</p>
                <a href="/user/laporan_saya.php" class="btn btn-secondary">Tampilkan Semua Laporan</a>
            <?php else: ?>
                <p style="font-size: 1.15rem; color: var(--color-primary); font-weight: 600; margin-bottom: 0.5rem;">
                    Anda belum pernah mengirimkan laporan.
                </p>
                <p style="font-size: 0.95rem; margin-bottom: 1.5rem;">
                    Punya keluhan fasilitas atau usulan untuk kampus? Sampaikan sekarang secara online.
                </p>
                <a href="/user/kirim.php" class="btn btn-primary">Sampaikan Laporan Sekarang &rarr;</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <?php foreach ($daftarLaporan as $lap): ?>
                <div class="card" style="padding: 1.75rem; margin-bottom: 0;">
                    
                    <!-- Header Kartu -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.85rem;">
                        <div>
                            <span style="font-size: 0.8rem; font-weight: 700; color: var(--color-secondary); text-transform: uppercase;">
                                <?= sanitize($lap['jenis']) ?> &bull; <?= sanitize($lap['nama_kategori'] ?? 'Umum') ?>
                            </span>
                            <h2 style="font-size: 1.35rem; color: var(--color-primary); margin: 0.35rem 0;">
                                <a href="/detail.php?id=<?= (int)$lap['id'] ?>" style="color: inherit; text-decoration: none;">
                                    <?= sanitize($lap['judul']) ?>
                                </a>
                            </h2>
                        </div>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <?php if (!$lap['is_public']): ?>
                                <span class="badge" style="background: #F0E6CB; color: #6B5420; border: 1px solid #DDD5C0;">Privat 🔒</span>
                            <?php else: ?>
                                <span class="badge" style="background: #E8ECE2; color: #34402A; border: 1px solid #C4CEB5;">Publik</span>
                            <?php endif; ?>

                            <?= badgeStatus($lap['status']) ?>
                        </div>
                    </div>

                    <!-- Keterangan Laporan -->
                    <p style="color: var(--color-text); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.25rem;">
                        <?= nl2br(sanitize(mb_strimwidth($lap['isi'], 0, 140, '...'))) ?>
                    </p>

                    <!-- Balasan Admin (Jika Ada) -->
                    <?php if (!empty($lap['tanggapan_terakhir'])): ?>
                        <div style="background-color: var(--color-bg); border-left: 3px solid var(--color-secondary); border-radius: 4px; padding: 1rem; margin-bottom: 1.25rem;">
                            <div style="font-size: 0.82rem; font-weight: 700; color: var(--color-primary); text-transform: uppercase; margin-bottom: 0.25rem;">
                                Tanggapan Resmi Kampus:
                            </div>
                            <div style="font-size: 0.92rem; color: var(--color-text); line-height: 1.5;">
                                "<?= sanitize($lap['tanggapan_terakhir']) ?>"
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Footer Kartu: Lokasi, Waktu, & Tombol Aksi -->
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; padding-top: 1rem; border-top: 1px solid var(--color-border); font-size: 0.88rem; color: var(--color-text-muted);">
                        <div>
                            📍 <strong><?= sanitize($lap['lokasi']) ?></strong> &bull; <?= date('d M Y, H:i', strtotime($lap['created_at'])) ?> WIB
                            <?php if (!empty($lap['foto'])): ?>
                                &bull; <span style="color: var(--color-secondary); font-weight: 600;">📷 Ada Foto</span>
                            <?php endif; ?>
                        </div>

                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <a href="/detail.php?id=<?= (int)$lap['id'] ?>" class="btn btn-secondary btn-sm">
                                Lihat Detail
                            </a>

                            <!-- Tombol Edit & Hapus (Hanya muncul jika status Menunggu) -->
                            <?php if ($lap['status'] === 'menunggu'): ?>
                                <a href="/user/edit.php?id=<?= (int)$lap['id'] ?>" class="btn btn-primary btn-sm">
                                    Edit
                                </a>

                                <form action="/user/hapus.php" method="POST" style="display: inline;" 
                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus laporan ini? Tindakan ini tidak dapat dibatalkan.');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= (int)$lap['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        Hapus
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
