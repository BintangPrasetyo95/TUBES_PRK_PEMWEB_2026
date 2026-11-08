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


// Buat kode acak 6 karakter untuk kuis privat (tanpa huruf/angka yang mirip seperti 0, O, 1, I)
function buatKodeKuis() {
    global $pdo;
    $karakter = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $panjangKarakter = strlen($karakter) - 1;

    while (true) {
        $kode = '';
        for ($i = 0; $i < 6; $i++) {
            $indexAcak = random_int(0, $panjangKarakter);
            $kode .= $karakter[$indexAcak];
        }

        // Cek apakah kode sudah dipakai di database
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM kuis WHERE kode = ?");
        $stmt->execute([$kode]);
        $jumlah = (int) $stmt->fetchColumn();

        if ($jumlah == 0) {
            return $kode;
        }
    }
}

// Tambah kuis baru sebagai draft
function tambahKuis($pengajarId, $data) {
    global $pdo;

    $judul = trim($data['judul'] ?? '');
    $deskripsi = trim($data['deskripsi'] ?? '');
    $topikId = (int) ($data['topik_id'] ?? 0);
    $tingkatId = (int) ($data['tingkat_id'] ?? 0);
    $durasi = (int) ($data['durasi_menit'] ?? 15);
    $akses = $data['akses'] ?? 'publik';

    // Validasi sederhana
    if (strlen($judul) < 3 || strlen($judul) > 150) {
        return ['berhasil' => false, 'pesan' => 'Judul kuis harus 3 sampai 150 karakter.'];
    }
    if ($topikId <= 0 || $tingkatId <= 0) {
        return ['berhasil' => false, 'pesan' => 'Topik dan tingkat harus dipilih.'];
    }
    if ($durasi < 1 || $durasi > 240) {
        return ['berhasil' => false, 'pesan' => 'Durasi kuis harus 1 sampai 240 menit.'];
    }
    if ($akses != 'publik' && $akses != 'privat') {
        $akses = 'publik';
    }

    // Kalau akses privat, buatkan kode otomatis
    $kode = null;
    if ($akses == 'privat') {
        $kode = buatKodeKuis();
    }

    $sql = "INSERT INTO kuis (pengajar_id, topik_id, tingkat_id, judul, deskripsi, durasi_menit, akses, kode, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft')";
    $stmt = $pdo->prepare($sql);
    $berhasil = $stmt->execute([
        $pengajarId,
        $topikId,
        $tingkatId,
        $judul,
        $deskripsi != '' ? $deskripsi : null,
        $durasi,
        $akses,
        $kode
    ]);

    if ($berhasil) {
        return [
            'berhasil' => true,
            'kuis_id'  => $pdo->lastInsertId(),
            'kode'     => $kode
        ];
    }

    return ['berhasil' => false, 'pesan' => 'Gagal menyimpan kuis.'];
}

// Edit data kuis milik pengajar
function editKuis($kuisId, $pengajarId, $data) {
    global $pdo;

    $kuisLama = ambilKuisById($kuisId, $pengajarId);
    if (!$kuisLama) {
        return ['berhasil' => false, 'pesan' => 'Kuis tidak ditemukan.'];
    }

    // Kuis yang sedang menunggu persetujuan atau sudah terbit tidak boleh diedit
    if ($kuisLama['status'] == 'menunggu' || $kuisLama['status'] == 'terbit') {
        return ['berhasil' => false, 'pesan' => 'Kuis yang sedang menunggu atau sudah terbit tidak bisa diedit.'];
    }

    $judul = trim($data['judul'] ?? '');
    $deskripsi = trim($data['deskripsi'] ?? '');
    $topikId = (int) ($data['topik_id'] ?? 0);
    $tingkatId = (int) ($data['tingkat_id'] ?? 0);
    $durasi = (int) ($data['durasi_menit'] ?? 15);
    $akses = $data['akses'] ?? 'publik';

    if (strlen($judul) < 3 || strlen($judul) > 150) {
        return ['berhasil' => false, 'pesan' => 'Judul kuis harus 3 sampai 150 karakter.'];
    }
    if ($topikId <= 0 || $tingkatId <= 0) {
        return ['berhasil' => false, 'pesan' => 'Topik dan tingkat harus dipilih.'];
    }
    if ($durasi < 1 || $durasi > 240) {
        return ['berhasil' => false, 'pesan' => 'Durasi kuis harus 1 sampai 240 menit.'];
    }
    if ($akses != 'publik' && $akses != 'privat') {
        $akses = 'publik';
    }

    // Atur kode kuis jika akses berubah
    $kode = $kuisLama['kode'];
    if ($akses == 'publik') {
        $kode = null;
    } else if ($akses == 'privat' && empty($kode)) {
        $kode = buatKodeKuis();
    }

    // Kalau sebelumnya ditolak atau ditarik, kembalikan ke draft dulu
    if ($kuisLama['status'] == 'ditolak' || $kuisLama['status'] == 'ditarik') {
        if (function_exists('ubahStatusKuis')) {
            ubahStatusKuis($kuisId, $kuisLama['status'], 'draft', $pengajarId, null);
        }
    }

    $sql = "UPDATE kuis 
            SET topik_id = ?, tingkat_id = ?, judul = ?, deskripsi = ?, durasi_menit = ?, akses = ?, kode = ?
            WHERE id = ? AND pengajar_id = ?";
    $stmt = $pdo->prepare($sql);
    $berhasil = $stmt->execute([
        $topikId,
        $tingkatId,
        $judul,
        $deskripsi != '' ? $deskripsi : null,
        $durasi,
        $akses,
        $kode,
        $kuisId,
        $pengajarId
    ]);

    if ($berhasil) {
        return ['berhasil' => true, 'kode' => $kode];
    }

    return ['berhasil' => false, 'pesan' => 'Gagal mengubah data kuis.'];
}

// Hapus kuis yang masih draft dan belum pernah dikerjakan
function hapusKuis($kuisId, $pengajarId) {
    global $pdo;

    $kuis = ambilKuisById($kuisId, $pengajarId);
    if (!$kuis) {
        return ['berhasil' => false, 'pesan' => 'Kuis tidak ditemukan.'];
    }

    if ($kuis['status'] != 'draft') {
        return ['berhasil' => false, 'pesan' => 'Hanya kuis berstatus draft yang boleh dihapus.'];
    }

    // Cek apakah sudah pernah dikerjakan pelajar
    $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM percobaan_kuis WHERE kuis_id = ?");
    $stmtCek->execute([$kuisId]);
    $jumlahPercobaan = (int) $stmtCek->fetchColumn();

    if ($jumlahPercobaan > 0) {
        return ['berhasil' => false, 'pesan' => 'Kuis tidak bisa dihapus karena sudah pernah dikerjakan pelajar.'];
    }

    $stmtHapus = $pdo->prepare("DELETE FROM kuis WHERE id = ? AND pengajar_id = ?");
    $stmtHapus->execute([$kuisId, $pengajarId]);

    return ['berhasil' => true];
}
