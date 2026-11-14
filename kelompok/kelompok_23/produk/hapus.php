<?php
session_start();
require_once __DIR__ . '/../includes/role_guard.php';
requireRole(['Admin']);
require_once __DIR__ . '/../config/database.php';

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();

    if ($data) {
        if (!empty($data['image'])) {
            $oldImagePath = __DIR__ . '/../assets/img/' . basename($data['image']);

            if (file_exists($oldImagePath)) {
                @unlink($oldImagePath);
            }
        }

        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            header("Location: index.php?status=hapus_berhasil");
            exit;
        } else {
            echo "Gagal menghapus produk.";
        }
    } else {
        header("Location: index.php");
    }
} else {
    header("Location: index.php");
}
?>