<?php
require __DIR__ . '/bootstrap.php';

if ($method !== 'GET') {
    respond(null, 'Metode tidak didukung.', 405);
}

try {
    $kelasId = trim($_GET['kelas_id'] ?? '0');

$sql = "
        SELECT 
            s.id_siswa,
            s.nisn AS nis,
            s.nama_siswa AS name,
            s.id_kelas AS kelas,
            ROUND(COALESCE((COUNT(DISTINCT CASE WHEN LOWER(TRIM(p.jenis_komponen)) = 'tugas' AND LOWER(TRIM(np.status_pengumpulan)) = 'tepat waktu' THEN np.id_penilaian END) 
            / NULLIF(COUNT(DISTINCT CASE WHEN LOWER(TRIM(p.jenis_komponen)) = 'tugas' THEN p.id_penilaian END), 0)) * 100, 0), 1) AS tugas,
            ROUND(COALESCE(AVG(CASE WHEN LOWER(TRIM(p.jenis_komponen)) IN ('uas', 'uts', 'ujian') AND np.nilai IS NOT NULL THEN np.nilai END), 0), 1) AS ujian
        FROM SISWA s
        LEFT JOIN PENUGASAN_MENGAJAR pm ON pm.id_kelas = s.id_kelas
        LEFT JOIN PENILAIAN p ON p.id_penugasan = pm.id_penugasan
        LEFT JOIN NILAI_DAN_PENGUMPULAN np ON np.id_penilaian = p.id_penilaian 
                                          AND np.id_siswa = s.id_siswa
    ";

    $params = [];
    if ($kelasId !== '0' && $kelasId !== '' && strtolower($kelasId) !== 'semua kelas') {
        $sql .= " WHERE s.id_kelas = ? ";
        $params[] = $kelasId;
    }

    $sql .= " GROUP BY s.id_siswa, s.nisn, s.nama_siswa, s.id_kelas ORDER BY s.nama_siswa ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($data as &$row) {
        $row['tugas'] = (float)$row['tugas'];
        $row['ujian'] = (float)$row['ujian'];
        $row['kelas'] = (string)$row['kelas'];
    }

    respond($data, 'Data klasifikasi tersinkronisasi presisi per kelas.');
} catch (Exception $e) {
    respond(null, 'Gagal mengambil data: ' . $e->getMessage(), 500);
}