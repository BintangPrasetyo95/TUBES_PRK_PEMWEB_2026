<?php
session_start();
require_once '../config/koneksi.php';
require_once '../config/app.php';

$pdo = db();

// Ambil SEMUA data alat
$query = "SELECT * FROM alat ORDER BY nama ASC";
$stmt = $pdo->query($query);
$alats = $stmt->fetchAll();

include '../includes/header.php';
?>
<script src="https://cdn.tailwindcss.com"></script>

<div class="container mx-auto mt-8 px-4">
    <!-- Header Bagian Atas -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <h2 class="text-2xl font-bold text-gray-800">Daftar Inventaris Alat</h2>
    </div>

    <!-- Tabel Data Alat (Hanya Baca) -->
    <div class="bg-white rounded-lg shadow-md overflow-x-auto border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Foto</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Identitas Alat</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi Singkat</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lokasi</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status Stok</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (count($alats) > 0) : ?>
                    <?php $no = 1; foreach ($alats as $row) : ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= $no++ ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php $foto_url = !empty($row['foto']) ? "../uploads/alat/" . $row['foto'] : "../assets/img/default-alat.png"; ?>
                                <img src="<?= e($foto_url) ?>" alt="<?= e($row['nama']) ?>" class="w-12 h-12 object-cover rounded shadow-sm">
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900"><?= e($row['nama']) ?></div>
                                <div class="text-xs text-gray-500 mt-1">Kode: <?= e($row['kode']) ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                 <span class="line-clamp-2"><?= e($row['deskripsi']) ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <?= e($row['lokasi']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <?php if ($row['stok'] > 0): ?>
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        Tersedia (<?= e($row['stok']) ?> Unit)
                                    </span>
                                <?php else: ?>
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Habis
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                            Belum ada data alat yang terdaftar di sistem.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>