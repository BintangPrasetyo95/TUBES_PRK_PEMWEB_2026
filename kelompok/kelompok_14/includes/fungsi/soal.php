<?php
// Fungsi-fungsi untuk mengelola soal dan pilihan jawaban

require_once __DIR__ . '/kuis.php';

// Ambil semua soal beserta pilihan jawabannya berdasarkan id kuis
function ambilSoalByKuis($kuisId) {
    global $pdo;

    $stmtSoal = $pdo->prepare("SELECT * FROM soal WHERE kuis_id = ? ORDER BY urutan ASC");
    $stmtSoal->execute([$kuisId]);
    $daftarSoal = $stmtSoal->fetchAll(PDO::FETCH_ASSOC);

    $stmtOpsi = $pdo->prepare("SELECT * FROM opsi_jawaban WHERE soal_id = ? ORDER BY label ASC");

    foreach ($daftarSoal as $index => $soal) {
        $stmtOpsi->execute([$soal['id']]);
        $daftarSoal[$index]['opsi'] = $stmtOpsi->fetchAll(PDO::FETCH_ASSOC);
    }

    return $daftarSoal;
}

// Ambil 1 soal beserta pilihan jawabannya berdasarkan id soal dan id kuis
function ambilSoalById($soalId, $kuisId) {
    global $pdo;

    $stmtSoal = $pdo->prepare("SELECT * FROM soal WHERE id = ? AND kuis_id = ?");
    $stmtSoal->execute([$soalId, $kuisId]);
    $soal = $stmtSoal->fetch(PDO::FETCH_ASSOC);

    if (!$soal) {
        return null;
    }

    $stmtOpsi = $pdo->prepare("SELECT * FROM opsi_jawaban WHERE soal_id = ? ORDER BY label ASC");
    $stmtOpsi->execute([$soalId]);
    $soal['opsi'] = $stmtOpsi->fetchAll(PDO::FETCH_ASSOC);

    return $soal;
}
