
<?php

require_once __DIR__ . '/../includes/cek_login.php';

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak. Hanya admin yang dapat mengakses halaman ini.');
}

$id = $_GET['id'] ?? null;

if (empty($id)) {
    header('Location: dokter_list.php?error=' . urlencode('ID dokter tidak valid.'));
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM antrian WHERE dokter_id = ?');
    $stmt->execute([$id]);
    $result = $stmt->fetch();

    if ($result['total'] > 0) {
        header('Location: dokter_list.php?error=' . urlencode('Dokter tidak dapat dihapus karena memiliki data antrian.'));
        exit;
    }

    $stmt = $pdo->prepare('DELETE FROM dokter WHERE id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        header('Location: dokter_list.php?success=' . urlencode('Dokter berhasil dihapus.'));
    } else {
        header('Location: dokter_list.php?error=' . urlencode('Dokter tidak ditemukan.'));
    }
} catch (PDOException $e) {
    error_log('[Dokter Hapus] ' . $e->getMessage());
    header('Location: dokter_list.php?error=' . urlencode('Gagal menghapus dokter.'));
}
exit;
