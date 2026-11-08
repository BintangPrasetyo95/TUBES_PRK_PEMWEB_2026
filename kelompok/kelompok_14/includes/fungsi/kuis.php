<?php
// Fungsi-fungsi untuk mengelola data kuis

// Ambil semua daftar topik untuk dropdown
function ambilSemuaTopik() {
    global $pdo;
    $sql = "SELECT * FROM topik ORDER BY nama ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Ambil semua daftar tingkat diurutkan dari yang terendah
function ambilSemuaTingkat() {
    global $pdo;
    $sql = "SELECT * FROM tingkat ORDER BY urutan ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Ambil 1 data kuis berdasarkan id
function ambilKuisById($kuisId, $pengajarId = null) {
    global $pdo;

    $sql = "SELECT kuis.*, 
                   topik.nama AS nama_topik, 
                   tingkat.nama AS nama_tingkat, 
                   user.nama AS nama_pengajar
            FROM kuis
            JOIN topik ON kuis.topik_id = topik.id
            JOIN tingkat ON kuis.tingkat_id = tingkat.id
            JOIN user ON kuis.pengajar_id = user.id
            WHERE kuis.id = ?";
    
    $params = [$kuisId];

    // Kalau pengajarId diisi, cek sekalian apakah kuis ini milik pengajar tersebut
    if ($pengajarId != null) {
        $sql .= " AND kuis.pengajar_id = ?";
        $params[] = $pengajarId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $kuis = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$kuis) {
        return null;
    }

    // Hitung jumlah soal di kuis ini
    $stmtSoal = $pdo->prepare("SELECT COUNT(*) FROM soal WHERE kuis_id = ?");
    $stmtSoal->execute([$kuisId]);
    $kuis['jumlah_soal'] = (int) $stmtSoal->fetchColumn();

    return $kuis;
}

// Ambil daftar kuis milik pengajar (untuk halaman Kuis Saya)
function daftarKuisPengajar($pengajarId, $status = '') {
    global $pdo;

    $sql = "SELECT kuis.*, 
                   topik.nama AS nama_topik, 
                   tingkat.nama AS nama_tingkat,
                   COUNT(soal.id) AS jumlah_soal
            FROM kuis
            JOIN topik ON kuis.topik_id = topik.id
            JOIN tingkat ON kuis.tingkat_id = tingkat.id
            LEFT JOIN soal ON soal.kuis_id = kuis.id
            WHERE kuis.pengajar_id = ?";
    
    $params = [$pengajarId];

    if ($status != '') {
        $sql .= " AND kuis.status = ?";
        $params[] = $status;
    }

    $sql .= " GROUP BY kuis.id ORDER BY kuis.dibuat_pada DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
