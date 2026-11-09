<?php
require __DIR__ . '/bootstrap.php';

if ($method !== 'GET') {
    respond(null, 'Metode tidak didukung.', 405);
}

try {
    $kelasId = $_GET['kelas_id'] ?? '0';

    $sql = "
        SELECT 
            s.id_siswa,
            s.nisn AS nis,
            s.nama_siswa AS name,
            s.id_kelas AS kelas,
            COALESCE(AVG(CASE WHEN p.jenis_komponen = 'Tugas' THEN np.nilai END), 0) AS tugas,
            COALESCE(AVG(CASE WHEN p.jenis_komponen = 'UAS' THEN np.nilai END), 0) AS ujian
        FROM SISWA s
        LEFT JOIN NILAI_DAN_PENGUMPULAN np ON s.id_siswa = np.id_siswa
        LEFT JOIN PENILAIAN p ON np.id_penilaian = p.id_penilaian
    ";

    $params = [];
    if ($kelasId !== '0') {
        $sql .= " WHERE s.id_kelas = ? ";
        $params[] = $kelasId;
    }

    $sql .= " GROUP BY s.id_siswa, s.nisn, s.nama_siswa, s.id_kelas ORDER BY s.id_siswa ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($data as &$row) {
        $row['tugas'] = round((float)$row['tugas'], 1);
        $row['ujian'] = round((float)$row['ujian'], 1);
        $row['kelas'] = (string)$row['kelas'];
    }

    respond($data, 'Data klasifikasi berhasil dimuat dari database.');
} catch (Exception $e) {
    respond(null, 'Gagal mengambil data dari database: ' . $e->getMessage(), 500);
}