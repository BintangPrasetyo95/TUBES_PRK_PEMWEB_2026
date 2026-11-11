<?php
// includes/fungsi/pelajar.php
// Kumpulan fungsi logika bisnis untuk halaman khusus Pelajar (Riwayat, Peringkat, Dashboard)
// PIC: Farrel

// 1. Ambil semua daftar topik / mata pelajaran untuk pilihan filter
function daftarSemuaTopik($pdo) {
    $stmt = $pdo->prepare("SELECT id, nama FROM topik ORDER BY nama ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 2. Hitung total riwayat pengerjaan kuis milik seorang pelajar (untuk pagination)
function hitungTotalRiwayatPelajar($pdo, $pelajarId, $topikId = 0, $tanggalMulai = '', $tanggalSelesai = '') {
    $sql = "SELECT COUNT(*)
            FROM percobaan_kuis pk
            JOIN kuis k ON pk.kuis_id = k.id
            WHERE pk.pelajar_id = ?";
    $params = [(int) $pelajarId];

    if ($topikId > 0) {
        $sql .= " AND k.topik_id = ?";
        $params[] = (int) $topikId;
    }

    if ($tanggalMulai !== '') {
        $sql .= " AND DATE(pk.waktu_mulai) >= ?";
        $params[] = $tanggalMulai;
    }

    if ($tanggalSelesai !== '') {
        $sql .= " AND DATE(pk.waktu_mulai) <= ?";
        $params[] = $tanggalSelesai;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

// 3. Ambil daftar riwayat pengerjaan kuis milik seorang pelajar dengan filter dan pagination (HSL-03)
function ambilRiwayatPelajar($pdo, $pelajarId, $topikId = 0, $tanggalMulai = '', $tanggalSelesai = '', $limit = 10, $offset = 0) {
    $sql = "SELECT pk.*,
                   k.judul AS judul_kuis,
                   k.status AS status_kuis,
                   t.nama AS nama_topik,
                   j.nama AS nama_tingkat
            FROM percobaan_kuis pk
            JOIN kuis k ON pk.kuis_id = k.id
            JOIN topik t ON k.topik_id = t.id
            JOIN tingkat j ON k.tingkat_id = j.id
            WHERE pk.pelajar_id = ?";
    $params = [(int) $pelajarId];

    if ($topikId > 0) {
        $sql .= " AND k.topik_id = ?";
        $params[] = (int) $topikId;
    }

    if ($tanggalMulai !== '') {
        $sql .= " AND DATE(pk.waktu_mulai) >= ?";
        $params[] = $tanggalMulai;
    }

    if ($tanggalSelesai !== '') {
        $sql .= " AND DATE(pk.waktu_mulai) <= ?";
        $params[] = $tanggalSelesai;
    }

    $sql .= " ORDER BY pk.waktu_mulai DESC LIMIT ? OFFSET ?";

    $stmt = $pdo->prepare($sql);

    // Bind parameter filter secara berurutan
    $index = 1;
    foreach ($params as $nilai) {
        $tipe = is_int($nilai) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($index, $nilai, $tipe);
        $index++;
    }

    // Bind LIMIT dan OFFSET sebagai integer agar aman di MySQL/MariaDB
    $stmt->bindValue($index++, (int) $limit, PDO::PARAM_INT);
    $stmt->bindValue($index, (int) $offset, PDO::PARAM_INT);

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
