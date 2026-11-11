
<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

// Ambil ID jenis dokumen dari URL
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(404);
    exit('Jenis dokumen tidak ditemukan.');
}

// Ambil informasi jenis dokumen
$stmt = db()->prepare(
    'SELECT id, nama, deskripsi
     FROM document_types
     WHERE id = :id AND aktif = 1'
);
$stmt->execute(['id' => $id]);
$jenis = $stmt->fetch();

if (!$jenis) {
    http_response_code(404);
    exit('Jenis dokumen tidak ditemukan.');
}

// Ambil daftar persyaratan
$stmt = db()->prepare(
    'SELECT kode, label, tipe_input, wajib, aturan_format,
            penjelasan, sumber, status_verifikasi
     FROM requirements
     WHERE document_type_id = :id
     ORDER BY urutan ASC, id ASC'
);
$stmt->execute(['id' => $id]);
$persyaratan = $stmt->fetchAll();

$page_title = 'Detail ' . $jenis['nama'];
require __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h1><?= htmlspecialchars($jenis['nama']) ?></h1>

    <p>
        <?= htmlspecialchars($jenis['deskripsi'] ?? '') ?>
    </p>

    <h2>Daftar Persyaratan</h2>

    <?php if (empty($persyaratan)): ?>
        <p>Belum ada persyaratan untuk jenis dokumen ini.</p>
    <?php else: ?>
        <ol>
            <?php foreach ($persyaratan as $item): ?>
                <li>
                    <strong>
                        <?= htmlspecialchars($item['label']) ?>
                    </strong>

                    <?php if ($item['wajib']): ?>
                        <span>(Wajib)</span>
                    <?php else: ?>
                        <span>(Opsional)</span>
                    <?php endif; ?>

                    <?php if (!empty($item['penjelasan'])): ?>
                        <p>
                            <?= htmlspecialchars($item['penjelasan']) ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($item['aturan_format'])): ?>
                        <p>
                            Format:
                            <?= htmlspecialchars($item['aturan_format']) ?>
                        </p>
                    <?php endif; ?>

                    <small>
                        Status:
                        <?= htmlspecialchars($item['status_verifikasi']) ?>
                    </small>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>

    <a href="index.php">Kembali ke Pustaka</a>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
