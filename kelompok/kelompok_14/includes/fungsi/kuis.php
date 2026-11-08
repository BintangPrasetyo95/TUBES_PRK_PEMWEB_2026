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

// Fungsi: cek apakah kuis sudah lengkap sebelum diajukan publish
function validasiKelengkapanKuis($kuisId) {
    global $pdo;
    $errors = [];

    // 1. Cek data kuis
    $stmtKuis = $pdo->prepare("SELECT * FROM kuis WHERE id = ?");
    $stmtKuis->execute([$kuisId]);
    $kuis = $stmtKuis->fetch(PDO::FETCH_ASSOC);

    if (!$kuis) {
        return [
            'lengkap'      => false,
            'errors'       => ['Data kuis tidak ditemukan.'],
            'jumlah_soal'  => 0,
            'soal_lengkap' => 0
        ];
    }

    if (strlen(trim($kuis['judul'])) < 3) {
        $errors[] = 'Judul kuis belum diisi dengan benar.';
    }
    if (empty($kuis['topik_id']) || empty($kuis['tingkat_id'])) {
        $errors[] = 'Topik dan tingkat kuis harus dipilih.';
    }
    if ((int) $kuis['durasi_menit'] < 1) {
        $errors[] = 'Durasi kuis harus diisi minimal 1 menit.';
    }
    if ($kuis['akses'] == 'privat' && empty($kuis['kode'])) {
        $errors[] = 'Kuis privat harus memiliki kode akses.';
    }

    // 2. Ambil semua soal di kuis ini
    $stmtSoal = $pdo->prepare("SELECT * FROM soal WHERE kuis_id = ? ORDER BY urutan ASC");
    $stmtSoal->execute([$kuisId]);
    $daftarSoal = $stmtSoal->fetchAll(PDO::FETCH_ASSOC);

    $jumlahSoal = count($daftarSoal);
    $soalLengkap = 0;

    if ($jumlahSoal == 0) {
        $errors[] = 'Kuis harus memiliki minimal 1 soal.';
    }

    // 3. Cek tiap soal beserta pilihan jawabannya
    $stmtOpsi = $pdo->prepare("SELECT * FROM opsi_jawaban WHERE soal_id = ?");

    foreach ($daftarSoal as $soal) {
        $nomor = $soal['urutan'];
        $soalOke = true;

        if (trim($soal['pertanyaan']) == '') {
            $errors[] = "Soal nomor $nomor belum ada teks pertanyaannya.";
            $soalOke = false;
        }

        $stmtOpsi->execute([$soal['id']]);
        $daftarOpsi = $stmtOpsi->fetchAll(PDO::FETCH_ASSOC);
        $jumlahOpsi = count($daftarOpsi);

        if ($jumlahOpsi < 2 || $jumlahOpsi > 5) {
            $errors[] = "Soal nomor $nomor harus punya 2 sampai 5 pilihan jawaban.";
            $soalOke = false;
        }

        // Hitung berapa opsi yang diset benar = 1
        $jumlahKunci = 0;
        foreach ($daftarOpsi as $opsi) {
            if (trim($opsi['teks']) == '') {
                $errors[] = "Soal nomor $nomor memiliki pilihan jawaban yang masih kosong.";
                $soalOke = false;
            }
            if ((int) $opsi['benar'] == 1) {
                $jumlahKunci++;
            }
        }

        if ($jumlahKunci != 1) {
            $errors[] = "Soal nomor $nomor harus memiliki tepat 1 kunci jawaban yang benar.";
            $soalOke = false;
        }

        if ($soalOke) {
            $soalLengkap++;
        }
    }

    return [
        'lengkap'      => count($errors) == 0,
        'errors'       => $errors,
        'jumlah_soal'  => $jumlahSoal,
        'soal_lengkap' => $soalLengkap
    ];
}

// Fungsi: ubah status kuis dan simpan ke log_status_kuis (dipakai Pengajar & Admin)
function ubahStatusKuis($kuisId, $dari, $ke, $pelaku, $catatan = null) {
    global $pdo;

    $catatan = $catatan != null ? trim($catatan) : null;
    if ($catatan == '') {
        $catatan = null;
    }

    // Kalau admin menolak atau menarik kuis, catatan alasan wajib diisi minimal 10 karakter
    if (($dari == 'menunggu' && $ke == 'ditolak') || ($dari == 'terbit' && $ke == 'ditarik')) {
        if ($catatan == null || strlen($catatan) < 10) {
            return ['berhasil' => false, 'pesan' => 'Catatan alasan wajib diisi minimal 10 karakter.'];
        }
    }

    // Mulai transaksi supaya update kuis dan insert log jalan bersamaan
    $pakaiTransaksiSendiri = !$pdo->inTransaction();
    if ($pakaiTransaksiSendiri) {
        $pdo->beginTransaction();
    }

    try {
        // Cek status kuis saat ini di database
        $stmtCek = $pdo->prepare("SELECT status FROM kuis WHERE id = ? FOR UPDATE");
        $stmtCek->execute([$kuisId]);
        $kuis = $stmtCek->fetch(PDO::FETCH_ASSOC);

        if (!$kuis) {
            if ($pakaiTransaksiSendiri) {
                $pdo->rollBack();
            }
            return ['berhasil' => false, 'pesan' => 'Kuis tidak ditemukan.'];
        }

        if ($kuis['status'] != $dari) {
            if ($pakaiTransaksiSendiri) {
                $pdo->rollBack();
            }
            return ['berhasil' => false, 'pesan' => 'Status kuis sudah berubah, silakan refresh halaman.'];
        }

        // Update status kuis (kalau terbit, isi juga diterbitkan_pada)
        if ($ke == 'terbit') {
            $stmtUpdate = $pdo->prepare("UPDATE kuis SET status = ?, diterbitkan_pada = NOW() WHERE id = ?");
            $stmtUpdate->execute([$ke, $kuisId]);
        } else {
            $stmtUpdate = $pdo->prepare("UPDATE kuis SET status = ? WHERE id = ?");
            $stmtUpdate->execute([$ke, $kuisId]);
        }

        // Simpan ke tabel log_status_kuis
        $stmtLog = $pdo->prepare(
            "INSERT INTO log_status_kuis (kuis_id, diubah_oleh, status_dari, status_ke, catatan)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmtLog->execute([$kuisId, $pelaku, $dari, $ke, $catatan]);

        // Kalau kuis ditarik dari status terbit, tutup percobaan pelajar yang masih berjalan
        if ($dari == 'terbit' && ($ke == 'draft' || $ke == 'ditarik')) {
            if (function_exists('tutupPercobaanKuis')) {
                tutupPercobaanKuis($kuisId);
            }
        }

        if ($pakaiTransaksiSendiri) {
            $pdo->commit();
        }

        return ['berhasil' => true];
    } catch (Exception $e) {
        if ($pakaiTransaksiSendiri && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['berhasil' => false, 'pesan' => 'Gagal mengubah status kuis.'];
    }
}

// Ajukan publish kuis ke admin
function ajukanPublishKuis($kuisId, $pengajarId, $catatan = null) {
    global $pdo;

    $kuis = ambilKuisById($kuisId, $pengajarId);
    if (!$kuis) {
        return ['berhasil' => false, 'pesan' => 'Kuis tidak ditemukan.'];
    }

    // Cek dulu apakah kuis dan soalnya sudah lengkap
    $cek = validasiKelengkapanKuis($kuisId);
    if (!$cek['lengkap']) {
        return [
            'berhasil' => false,
            'pesan'    => 'Kuis belum lengkap untuk diajukan.',
            'errors'   => $cek['errors']
        ];
    }

    // Kalau status sebelumnya ditolak atau ditarik, ubah ke draft dulu baru ke menunggu
    if ($kuis['status'] == 'ditolak' || $kuis['status'] == 'ditarik') {
        $keDraft = ubahStatusKuis($kuisId, $kuis['status'], 'draft', $pengajarId, null);
        if (!$keDraft['berhasil']) {
            return $keDraft;
        }
    }

    return ubahStatusKuis($kuisId, 'draft', 'menunggu', $pengajarId, $catatan);
}

// Tarik kuis yang sudah terbit kembali ke draft supaya bisa diedit pengajar
function tarikKuis($kuisId, $pengajarId, $catatan = null) {
    $kuis = ambilKuisById($kuisId, $pengajarId);
    if (!$kuis) {
        return ['berhasil' => false, 'pesan' => 'Kuis tidak ditemukan.'];
    }

    if ($kuis['status'] != 'terbit') {
        return ['berhasil' => false, 'pesan' => 'Hanya kuis berstatus terbit yang bisa ditarik ke draft.'];
    }

    return ubahStatusKuis($kuisId, 'terbit', 'draft', $pengajarId, $catatan);
}
