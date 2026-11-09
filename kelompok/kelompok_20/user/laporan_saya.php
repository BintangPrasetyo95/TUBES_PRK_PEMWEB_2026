<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

wajibLogin();

$userId = $_SESSION['user_id'];

// Semua laporan milik user yang sedang aktif beserta nama kategori dan tanggapan admin terakhir
try {
    $sql = "
        SELECT 
            l.id,
            l.jenis,
            l.judul,
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
        ORDER BY l.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    $daftarLaporan = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Error mengambil laporan saya: " . $e->getMessage());
    $daftarLaporan = [];
}

$pageTitle = 'Laporan Saya';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--color-border); padding-bottom: 0.85rem;">
        <div>
            <h1 class="card-title" style="margin-bottom: 0.25rem;">Daftar Laporan Saya</h1>
            <p style="color: var(--color-text-light); font-size: 0.95rem;">
                Pantau perkembangan tindak lanjut pengaduan dan aspirasi yang telah Anda sampaikan.
            </p>
        </div>
        <div>
            <a href="/user/kirim.php" class="btn btn-primary">+ Buat Laporan Baru</a>
        </div>
    </div>

    <?php if (empty($daftarLaporan)): ?>
        <div style="text-align: center; padding: 3rem 1rem;">
            <p style="font-size: 1.1rem; color: var(--color-text-light); margin-bottom: 1.25rem;">
                Anda belum pernah mengirimkan pengaduan ataupun aspirasi.
            </p>
            <a href="/user/kirim.php" class="btn btn-primary">Sampaikan Laporan Sekarang</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">No</th>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Judul & Lokasi</th>
                        <th>Kategori</th>
                        <th>Status</th>
                        <th>Tanggapan Terakhir</th>
                        <th style="text-align: center; width: 180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daftarLaporan as $index => $lap): ?>
                        <tr>
                            <td style="text-align: center;"><?= $index + 1 ?></td>
                            <td style="white-space: nowrap; font-size: 0.88rem;">
                                <?= date('d M Y, H:i', strtotime($lap['created_at'])) ?>
                            </td>
                            <td>
                                <span style="font-weight: 600; text-transform: capitalize;">
                                    <?= sanitize($lap['jenis']) ?>
                                </span>
                                <?php if (!$lap['is_public']): ?>
                                    <div style="font-size: 0.75rem; color: var(--color-text-light); font-style: italic;">(Privat)</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= sanitize($lap['judul']) ?></strong>
                                <div style="font-size: 0.85rem; color: var(--color-text-light); margin-top: 0.2rem;">
                                    📍 <?= sanitize($lap['lokasi']) ?>
                                </div>
                            </td>
                            <td>
                                <?= sanitize($lap['nama_kategori'] ?? '-') ?>
                            </td>
                            <td>
                                <?= badgeStatus($lap['status']) ?>
                            </td>
                            <td>
                                <?php if (!empty($lap['tanggapan_terakhir'])): ?>
                                    <span style="font-size: 0.9rem; color: var(--color-text);">
                                        "<?= sanitize(mb_strimwidth($lap['tanggapan_terakhir'], 0, 80, '...')) ?>"
                                    </span>
                                <?php else: ?>
                                    <span style="font-size: 0.85rem; color: var(--color-text-light); font-style: italic;">
                                        Belum ada tanggapan
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <div style="display: flex; gap: 0.4rem; justify-content: center; flex-wrap: wrap;">
                                    <a href="/detail.php?id=<?= (int)$lap['id'] ?>" class="btn btn-sm btn-secondary" title="Lihat detail">
                                        Lihat
                                    </a>

                                    <?php if ($lap['status'] === 'menunggu'): ?>
                                        <a href="/user/edit.php?id=<?= (int)$lap['id'] ?>" class="btn btn-sm btn-primary" title="Ubah laporan">
                                            Edit
                                        </a>

                                        <form action="/user/hapus.php" method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus laporan ini? Tindakan ini tidak dapat dibatalkan.');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id" value="<?= (int)$lap['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Hapus laporan">
                                                Hapus
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

