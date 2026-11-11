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


// 4. Ambil data papan peringkat Pelajar berdasarkan rumus KT-03 & BR-15:
// Total poin dari skor terbaik tiap kuis per Pelajar. Tie-breaker: total waktu tercepat.
function ambilPeringkatPelajar($pdo, $topikId = 0) {
    $filterTopikSql = '';
    $params = [];

    if ($topikId > 0) {
        $filterTopikSql = ' AND k.topik_id = ?';
        $params[] = (int) $topikId;
    }

    $sql = "SELECT
                u.id AS pelajar_id,
                u.nama,
                SUM(best.skor_terbaik) AS total_poin,
                COUNT(best.kuis_id) AS jumlah_kuis,
                SUM(best.waktu_tercepat_detik) AS total_waktu_detik
            FROM (
                SELECT
                    pk.pelajar_id,
                    pk.kuis_id,
                    MAX(pk.skor) AS skor_terbaik,
                    MIN(GREATEST(0, TIMESTAMPDIFF(SECOND, pk.waktu_mulai, COALESCE(pk.waktu_selesai, pk.waktu_mulai)))) AS waktu_tercepat_detik
                FROM percobaan_kuis pk
                JOIN kuis k ON pk.kuis_id = k.id
                WHERE pk.status IN ('selesai', 'waktu_habis')
                {$filterTopikSql}
                GROUP BY pk.pelajar_id, pk.kuis_id
            ) AS best
            JOIN user u ON best.pelajar_id = u.id
            WHERE u.peran = 'pelajar'
            GROUP BY u.id, u.nama
            ORDER BY total_poin DESC, total_waktu_detik ASC, u.id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $daftar = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tambahkan nomor peringkat secara berurutan
    $peringkat = 1;
    foreach ($daftar as $index => $baris) {
        $daftar[$index]['peringkat'] = $peringkat;
        $daftar[$index]['pelajar_id'] = (int) $baris['pelajar_id'];
        $daftar[$index]['total_poin'] = (int) $baris['total_poin'];
        $daftar[$index]['jumlah_kuis'] = (int) $baris['jumlah_kuis'];
        $daftar[$index]['total_waktu_detik'] = (int) $baris['total_waktu_detik'];
        $peringkat++;
    }

    return $daftar;
}

// 5. Ubah detik menjadi format durasi yang mudah dibaca (mis. "12m 30d")
function formatDurasiSingkat($totalDetik) {
    $totalDetik = max(0, (int) $totalDetik);
    $jam = floor($totalDetik / 3600);
    $menit = floor(($totalDetik % 3600) / 60);
    $detik = $totalDetik % 60;

    if ($jam > 0) {
        return $jam . 'j ' . $menit . 'm';
    } elseif ($menit > 0) {
        return $menit . 'm ' . $detik . 'd';
    }
    return $detik . 'd';
}
