
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

wajibLogin();

$userId = $_SESSION['user_id'];

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

<div style="margin-bottom: 2rem;">

    <!-- Header Halaman -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
        <div>
            <h1 style="color: var(--color-primary); font-size: 1.75rem; margin-bottom: 0.35rem;">
                Riwayat Laporan Saya
            </h1>
            <p style="color: var(--color-text-muted); font-size: 0.95rem; margin: 0;">
                Pantau perkembangan penanganan pengaduan & aspirasi yang telah Anda kirimkan.
            </p>
        </div>

        <a href="/user/kirim.php" class="btn btn-primary" style="font-size: 0.9rem;">
            + Buat Laporan Baru
        </a>
    </div>

    <!-- Kotak Filter -->
    <div class="card" style="padding: 1.5rem; margin-bottom: 1.5rem;">
        <form method="GET" action="/user/laporan_saya.php">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem; align-items: end;">

                <div>
                    <label for="filter-jenis" class="form-label">Jenis</label>
                    <select id="filter-jenis" name="jenis" class="form-control">
                        <option value="">Semua Jenis</option>
                        <option value="pengaduan" <?= ($filterJenis === 'pengaduan') ? 'selected' : '' ?>>
                            Pengaduan
                        </option>
                        <option value="aspirasi" <?= ($filterJenis === 'aspirasi') ? 'selected' : '' ?>>
                            Aspirasi
                        </option>
                    </select>
                </div>

                <div>
                    <label for="filter-status" class="form-label">Status</label>
                    <select id="filter-status" name="status" class="form-control">
                        <option value="">Semua Status</option>
                        <option value="menunggu" <?= ($filterStatus === 'menunggu') ? 'selected' : '' ?>>
                            Menunggu
                        </option>
                        <option value="diproses" <?= ($filterStatus === 'diproses') ? 'selected' : '' ?>>
                            Diproses
                        </option>
                        <option value="selesai" <?= ($filterStatus === 'selesai') ? 'selected' : '' ?>>
                            Selesai
                        </option>
                    </select>
                </div>

                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        Terapkan Filter
                    </button>
                    <a href="/user/laporan_saya.php" class="btn btn-secondary">
                        Reset
                    </a>
                </div>

            </div>
        </form>
    </div>

    <!-- Jumlah Laporan -->
    <div style="margin-bottom: 1rem; font-size: 0.95rem; color: var(--color-text-muted);">
        Menampilkan <strong><?= count($daftarLaporan) ?></strong> laporan yang ditemukan.
    </div>

    <!-- Daftar Laporan -->
    <?php if (empty($daftarLaporan)): ?>

        <div class="card" style="text-align: center; padding: 3rem 1rem; color: var(--color-text-muted);">

            <?php if ($isFiltered): ?>

                <p style="font-size: 1.1rem; margin-bottom: 0.75rem;">
                    Tidak ada laporan yang sesuai dengan filter Anda.
                </p>

                <a href="/user/laporan_saya.php" class="btn btn-secondary">
                    Reset Filter
                </a>

            <?php else: ?>

                <p style="font-size: 1.15rem; color: var(--color-primary); font-weight: 600; margin-bottom: 0.5rem;">
                    Anda belum pernah mengirimkan laporan.
                </p>

                <p style="font-size: 0.95rem; margin-bottom: 1.5rem;">
                    Punya keluhan fasilitas atau usulan untuk kampus? Sampaikan sekarang secara online.
                </p>

                <a href="/user/kirim.php" class="btn btn-primary">
                    Sampaikan Laporan Sekarang →
                </a>

            <?php endif; ?>

        </div>

    <?php else: ?>

        <div style="display: flex; flex-direction: column; gap: 1.25rem;">

            <?php foreach ($daftarLaporan as $lap): ?>

                <div class="card" style="padding: 1.5rem; margin-bottom: 0;">

                    <!-- Header Kartu -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.75rem;">

                        <div>
                            <span style="font-size: 0.8rem; font-weight: 700; color: var(--color-secondary); text-transform: uppercase;">
                                <?= sanitize($lap['jenis']) ?> •
                                <?= sanitize($lap['nama_kategori'] ?? 'Umum') ?>
                            </span>

                            <h2 style="font-size: 1.35rem; color: var(--color-primary); margin: 0.25rem 0;">
                                <a href="/detail.php?id=<?= (int)$lap['id'] ?>" style="color: inherit; text-decoration: none;">
                                    <?= sanitize($lap['judul']) ?>
                                </a>
                            </h2>
                        </div>

                        <!-- Badge Publik / Privat -->
                        <div style="display: flex; gap: 0.5rem; align-items: center;">

                            <?php if ((int)$lap['is_public'] === 1): ?>

                                <span class="badge" style="background: #E8ECE2; color: #34402A; border: 1px solid #C4CEB5;">
                                    Publik
                                </span>

                            <?php else: ?>

                                <span class="badge" style="background: #F0E6CB; color: #6B5420; border: 1px solid #DDD5C0;">
                                    Privat
                                </span>

                            <?php endif; ?>

                            <?= badgeStatus($lap['status']) ?>

                        </div>
                    </div>

                    <!-- Keterangan Laporan -->
                    <p style="color: var(--color-text); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1rem;">
                        <?= nl2br(sanitize(mb_strimwidth($lap['isi'], 0, 140, '...'))) ?>
                    </p>

                    <!-- Balasan Admin -->
                    <?php if (!empty($lap['tanggapan_terakhir'])): ?>

                        <div style="background-color: var(--color-bg); border-left: 3px solid var(--color-secondary); border-radius: 4px; padding: 1rem; margin-bottom: 1rem;">

                            <div style="font-size: 0.82rem; font-weight: 700; color: var(--color-primary); text-transform: uppercase; margin-bottom: 0.25rem;">
                                Tanggapan Resmi Kampus:
                            </div>

                            <div style="font-size: 0.92rem; color: var(--color-text); line-height: 1.5;">
                                "<?= sanitize($lap['tanggapan_terakhir']) ?>"
                            </div>

                        </div>

                    <?php endif; ?>

                    <!-- Footer Kartu -->
                    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; padding-top: 1rem; border-top: 1px solid var(--color-border);">

                        <!-- Metadata -->
                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; flex: 1;">

                            <span style="display: inline-flex; align-items: center; padding: 4px 12px; background: #f1f5f9; border: 1px solid #e2e8f0; color: #475569; border-radius: 9999px; font-size: 0.8rem; font-weight: 500;">
                                <?= sanitize($lap['lokasi'] ?? '-') ?>
                            </span>

                            <span style="display: inline-flex; align-items: center; padding: 4px 12px; background: #f1f5f9; border: 1px solid #e2e8f0; color: #475569; border-radius: 9999px; font-size: 0.8rem; font-weight: 500;">
                                <?= date('d M Y, H:i', strtotime($lap['created_at'])) ?>
                            </span>

                            <?php if (!empty($lap['foto'])): ?>

                                <span style="display: inline-flex; align-items: center; padding: 4px 12px; background: #E8ECE2; border: 1px solid #C4CEB5; color: #34402A; border-radius: 9999px; font-size: 0.8rem; font-weight: 600;">
                                    Ada Foto
                                </span>

                            <?php endif; ?>

                        </div>

                        <!-- Tombol Aksi -->
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: flex-end;">

                            <a href="/detail.php?id=<?= (int)$lap['id'] ?>" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; height: 34px;">
                                Detail Lengkap →
                            </a>

                            <?php if ($lap['status'] === 'menunggu'): ?>

                                <a href="/user/edit.php?id=<?= (int)$lap['id'] ?>" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; height: 34px;">
                                    Edit
                                </a>

                                <form action="/user/hapus.php" method="POST" style="display: inline-flex; margin: 0;"
                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus laporan ini? Tindakan ini tidak dapat dibatalkan.');">

                                    <?= csrfField() ?>

                                    <input type="hidden" name="id" value="<?= (int)$lap['id'] ?>">

                                    <button type="submit" class="btn btn-danger btn-sm" style="display: inline-flex; align-items: center; height: 34px;">
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
