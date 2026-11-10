<?php
/**
 * ENDPOINT API: Kalkulasi Nilai Akhir & Pengaturan Bobot
 * Modul       : Perhitungan Nilai & Laporan Akademik
 * Dikerjakan oleh: Jehan Reza Pahlevi (NPM. 2415061067)
 *
 * Kuadran Belajar):
 *   Rajin & nilai tinggi          → Nilai >= 75 & Disiplin >= 80
 *   Jarang kumpul & nilai tinggi  → Nilai >= 75 & Disiplin <  80
 *   Rajin & nilai rendah          → Nilai <  75 & Disiplin >= 80
 *   Jarang kumpul & nilai rendah  → Nilai <  75 & Disiplin <  80
 */
require __DIR__ . '/bootstrap.php';
$action = $_GET['action'] ?? '';

function hitungPredikat($nilaiAkhir) {
    if ($nilaiAkhir === null) return '-';
    if ($nilaiAkhir >= 85) return 'A';
    if ($nilaiAkhir >= 75) return 'B';
    if ($nilaiAkhir >= 65) return 'C';
    if ($nilaiAkhir >= 55) return 'D';
    return 'E';
}

function hitungDistribusiPredikat(array $nilaiArr): array {
    $distribusi = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0];
    foreach ($nilaiArr as $nilai) {
        $predikat = hitungPredikat((float)$nilai);
        if (isset($distribusi[$predikat])) $distribusi[$predikat]++;
    }
    return $distribusi;
}

function hitungDistribusiKuadran(array $rekapAll): array {
    $distribusi = [
        'Rajin & nilai tinggi'         => 0,
        'Jarang kumpul & nilai tinggi' => 0,
        'Rajin & nilai rendah'         => 0,
        'Jarang kumpul & nilai rendah' => 0
    ];
    foreach ($rekapAll as $r) {
        $k = $r['kategori_kuadran'] ?? '';
        if (isset($distribusi[$k])) $distribusi[$k]++;
    }
    return $distribusi;
}

function validasiPenugasan(PDO $pdo, int $idPenugasan): array {
    $stmt = $pdo->prepare(
        'SELECT pm.*, k.nama_kelas, k.tingkat, m.nama_mapel, m.kode_mapel,
                g.nama_guru, p.tahun_ajaran, p.semester
         FROM PENUGASAN_MENGAJAR pm
         JOIN KELAS k ON k.id_kelas = pm.id_kelas
         JOIN MATA_PELAJARAN m ON m.id_mapel = pm.id_mapel
         JOIN GURU g ON g.id_guru = pm.id_guru
         JOIN PERIODE_PENILAIAN p ON p.id_periode = pm.id_periode
         WHERE pm.id_penugasan = ?'
    );
    $stmt->execute([$idPenugasan]);
    $penugasan = $stmt->fetch();
    if (!$penugasan) respond(null, 'Penugasan tidak ditemukan.', 404);
    if (!isAdmin() && (int)$penugasan['id_guru'] !== (int)$_SESSION['guru']['id_guru']) {
        respond(null, 'Anda tidak memiliki akses ke penugasan ini.', 403);
    }
    return $penugasan;
}

function ambilBobot(PDO $pdo, int $idPenugasan): array {
    $stmt = $pdo->prepare('SELECT * FROM PENGATURAN_BOBOT WHERE id_penugasan = ?');
    $stmt->execute([$idPenugasan]);
    $bobot = $stmt->fetch();
    if (!$bobot) {
        return [
            'id_bobot'     => null,
            'id_penugasan' => $idPenugasan,
            'bobot_tugas'  => 25,
            'bobot_kuis'   => 25,
            'bobot_uts'    => 25,
            'bobot_uas'    => 25
        ];
    }
    return $bobot;
}

function ambilNilaiBulk(PDO $pdo, array $idPenilaianAll): array {
    $nilaiMap = [];
    if (!empty($idPenilaianAll)) {
        $marks = implode(',', array_fill(0, count($idPenilaianAll), '?'));
        $stmt = $pdo->prepare(
            "SELECT * FROM NILAI_DAN_PENGUMPULAN WHERE id_penilaian IN ($marks)"
        );
        $stmt->execute($idPenilaianAll);
        foreach ($stmt->fetchAll() as $n) {
            $nilaiMap[$n['id_siswa']][$n['id_penilaian']] = $n;
        }
    }
    return $nilaiMap;
}

function kelompokkanPenilaian(array $daftarPenilaian): array {
    $penilaianPerJenis = ['Tugas' => [], 'Kuis' => [], 'UTS' => [], 'UAS' => []];
    foreach ($daftarPenilaian as $p) {
        if (isset($penilaianPerJenis[$p['jenis_komponen']])) {
            $penilaianPerJenis[$p['jenis_komponen']][] = $p['id_penilaian'];
        }
    }
    return $penilaianPerJenis;
}

function hitungRataPerJenis(array $penilaianPerJenis, array $nilaiSiswa): array {
    $rataPerJenis = [];
    foreach ($penilaianPerJenis as $jenis => $ids) {
        $nilaiJenis = [];
        foreach ($ids as $idP) {
            if (isset($nilaiSiswa[$idP]) && $nilaiSiswa[$idP]['nilai'] !== null) {
                $nilaiJenis[] = (float)$nilaiSiswa[$idP]['nilai'];
            }
        }
        $rataPerJenis[$jenis] = !empty($nilaiJenis)
            ? round(array_sum($nilaiJenis) / count($nilaiJenis), 2)
            : null;
    }
    return $rataPerJenis;
}

function hitungNilaiAkhir(array $rataPerJenis, array $bobot): float {
    $na = (
        ($rataPerJenis['Tugas'] ?? 0) * (float)$bobot['bobot_tugas'] +
        ($rataPerJenis['Kuis']  ?? 0) * (float)$bobot['bobot_kuis']  +
        ($rataPerJenis['UTS']   ?? 0) * (float)$bobot['bobot_uts']   +
        ($rataPerJenis['UAS']   ?? 0) * (float)$bobot['bobot_uas']
    ) / 100;
    return round($na, 2);
}

function tentukanKuadran(float $nilaiAkhir, float $indeksKedisiplinan): string {
    if ($nilaiAkhir >= 75 && $indeksKedisiplinan >= 80) return 'Rajin & nilai tinggi';
    if ($nilaiAkhir >= 75 && $indeksKedisiplinan <  80) return 'Jarang kumpul & nilai tinggi';
    if ($nilaiAkhir <  75 && $indeksKedisiplinan >= 80) return 'Rajin & nilai rendah';
    return 'Jarang kumpul & nilai rendah';
}

function ambilRekomendasi(string $kuadran): string {
    return [
        'Rajin & nilai tinggi'         => 'Pertahankan konsistensi belajar. Siswa siap diberikan materi pengayaan dan dapat ditugaskan sebagai tutor sebaya.',
        'Jarang kumpul & nilai tinggi' => 'Kemampuan kognitif tinggi namun kedisiplinan rendah. Perlu peningkatan motivasi serta pemantauan pengumpulan tugas harian.',
        'Rajin & nilai rendah'         => 'Kedisiplinan belajar sangat baik namun hasil nilai belum optimal. Disarankan evaluasi cara belajar atau pendampingan remedial konsep dasar.',
        'Jarang kumpul & nilai rendah' => 'Perlu perhatian segera. Jadwalkan sesi pendampingan belajar khusus dan komunikasi bersama wali kelas/orang tua.'
    ][$kuadran] ?? 'Perlu evaluasi berkala.';
}

if ($action === 'penugasan') {
    if ($method !== 'GET') respond(null, 'Metode tidak didukung.', 405);

    $where  = '';
    $params = [];
    if (!isAdmin()) {
        $where  = 'WHERE pm.id_guru = ?';
        $params = [$_SESSION['guru']['id_guru']];
    }

    $stmt = $pdo->prepare(
        "SELECT pm.id_penugasan, pm.id_guru, pm.id_kelas, pm.id_mapel, pm.id_periode,
                g.nama_guru, k.nama_kelas, k.tingkat,
                m.nama_mapel, m.kode_mapel,
                p.tahun_ajaran, p.semester, p.status_aktif
         FROM PENUGASAN_MENGAJAR pm
         JOIN GURU g ON g.id_guru = pm.id_guru
         JOIN KELAS k ON k.id_kelas = pm.id_kelas
         JOIN MATA_PELAJARAN m ON m.id_mapel = pm.id_mapel
         JOIN PERIODE_PENILAIAN p ON p.id_periode = pm.id_periode
         $where
         ORDER BY p.status_aktif DESC, p.tahun_ajaran DESC, k.nama_kelas ASC"
    );
    $stmt->execute($params);
    respond($stmt->fetchAll());
}

$idPenugasan = positiveId($_GET['id_penugasan'] ?? 0);
$penugasan   = validasiPenugasan($pdo, $idPenugasan);

if ($action === 'bobot' && $method === 'GET') {
    respond(ambilBobot($pdo, $idPenugasan));
}

if ($action === 'bobot' && in_array($method, ['POST', 'PUT'])) {
    $input = inputData();

    $bobotTugas = filter_var($input['bobot_tugas'] ?? '', FILTER_VALIDATE_FLOAT);
    $bobotKuis  = filter_var($input['bobot_kuis']  ?? '', FILTER_VALIDATE_FLOAT);
    $bobotUts   = filter_var($input['bobot_uts']   ?? '', FILTER_VALIDATE_FLOAT);
    $bobotUas   = filter_var($input['bobot_uas']   ?? '', FILTER_VALIDATE_FLOAT);

    if ($bobotTugas === false || $bobotKuis === false ||
        $bobotUts === false  || $bobotUas === false) {
        respond(null, 'Semua bobot harus berupa angka.', 422);
    }
    if ($bobotTugas < 0 || $bobotKuis < 0 || $bobotUts < 0 || $bobotUas < 0) {
        respond(null, 'Bobot tidak boleh bernilai negatif.', 422);
    }
    $total = $bobotTugas + $bobotKuis + $bobotUts + $bobotUas;
    if (abs($total - 100) > 0.01) {
        respond(null, 'Total bobot harus tepat 100%. Saat ini: ' . round($total, 2) . '%', 422);
    }

    $stmt = $pdo->prepare('SELECT id_bobot FROM PENGATURAN_BOBOT WHERE id_penugasan = ?');
    $stmt->execute([$idPenugasan]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare(
            'UPDATE PENGATURAN_BOBOT
             SET bobot_tugas = ?, bobot_kuis = ?, bobot_uts = ?, bobot_uas = ?
             WHERE id_penugasan = ?'
        );
        $stmt->execute([$bobotTugas, $bobotKuis, $bobotUts, $bobotUas, $idPenugasan]);
        respond(['id_bobot' => (int)$existing['id_bobot']], 'Bobot berhasil diperbarui.');
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO PENGATURAN_BOBOT
             (id_penugasan, bobot_tugas, bobot_kuis, bobot_uts, bobot_uas)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$idPenugasan, $bobotTugas, $bobotKuis, $bobotUts, $bobotUas]);
        respond(['id_bobot' => (int)$pdo->lastInsertId()], 'Bobot berhasil disimpan.', 201);
    }
}

if ($action === 'rekap' && $method === 'GET') {
    $bobot = ambilBobot($pdo, $idPenugasan);

    $stmt = $pdo->prepare(
        'SELECT id_siswa, nisn, nama_siswa, jenis_kelamin
         FROM SISWA WHERE id_kelas = ? ORDER BY nama_siswa'
    );
    $stmt->execute([$penugasan['id_kelas']]);
    $daftarSiswa = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT * FROM PENILAIAN WHERE id_penugasan = ?');
    $stmt->execute([$idPenugasan]);
    $daftarPenilaian   = $stmt->fetchAll();
    $penilaianPerJenis = kelompokkanPenilaian($daftarPenilaian);
    $totalPenilaian    = count($daftarPenilaian);

    $idPenilaianAll = array_column($daftarPenilaian, 'id_penilaian');
    $nilaiMap       = ambilNilaiBulk($pdo, $idPenilaianAll);

    $stmt = $pdo->prepare('SELECT * FROM REKAP_AKADEMIK_KUADRAN WHERE id_penugasan = ?');
    $stmt->execute([$idPenugasan]);
    $rekapMap = [];
    foreach ($stmt->fetchAll() as $r) {
        $rekapMap[$r['id_siswa']] = $r;
    }

    $hasilRekap = [];
    foreach ($daftarSiswa as $siswa) {
        $idSiswa    = $siswa['id_siswa'];
        $nilaiSiswa = $nilaiMap[$idSiswa] ?? [];

        $rataPerJenis = hitungRataPerJenis($penilaianPerJenis, $nilaiSiswa);

        $sudahDinilai = count($nilaiSiswa);
        $nilaiAkhir   = null;
        $predikat     = '-';
        if ($sudahDinilai > 0) {
            $nilaiAkhir = hitungNilaiAkhir($rataPerJenis, $bobot);
            $predikat   = hitungPredikat($nilaiAkhir);
        }

        $statusKelengkapan = ($sudahDinilai >= $totalPenilaian && $totalPenilaian > 0)
            ? 'Lengkap' : 'Belum lengkap';

        $rekap = $rekapMap[$idSiswa] ?? null;

        $hasilRekap[] = [
            'id_siswa'                => $idSiswa,
            'nisn'                    => $siswa['nisn'],
            'nama_siswa'              => $siswa['nama_siswa'],
            'jenis_kelamin'           => $siswa['jenis_kelamin'],
            'rata_tugas'              => $rataPerJenis['Tugas'],
            'rata_kuis'               => $rataPerJenis['Kuis'],
            'rata_uts'                => $rataPerJenis['UTS'],
            'rata_uas'                => $rataPerJenis['UAS'],
            'nilai_akhir'             => $nilaiAkhir,
            'predikat'                => $predikat,
            'status_kelengkapan_nilai'=> $statusKelengkapan,
            'jumlah_dinilai'          => $sudahDinilai,
            'jumlah_penilaian'        => $totalPenilaian,
            'indeks_kedisiplinan'     => $rekap['indeks_kedisiplinan'] ?? null,
            'kategori_kuadran'        => $rekap['kategori_kuadran'] ?? null,
            'rekomendasi_sistem'      => $rekap['rekomendasi_sistem'] ?? null
        ];
    }

    respond([
        'penugasan' => [
            'id_penugasan' => $idPenugasan,
            'nama_guru'    => $penugasan['nama_guru'],
            'nama_kelas'   => $penugasan['nama_kelas'],
            'tingkat'      => $penugasan['tingkat'],
            'nama_mapel'   => $penugasan['nama_mapel'],
            'kode_mapel'   => $penugasan['kode_mapel'],
            'tahun_ajaran' => $penugasan['tahun_ajaran'],
            'semester'     => $penugasan['semester']
        ],
        'bobot' => [
            'bobot_tugas' => (float)$bobot['bobot_tugas'],
            'bobot_kuis'  => (float)$bobot['bobot_kuis'],
            'bobot_uts'   => (float)$bobot['bobot_uts'],
            'bobot_uas'   => (float)$bobot['bobot_uas']
        ],
        'jumlah_penilaian' => $totalPenilaian,
        'jumlah_siswa'     => count($daftarSiswa),
        'siswa'            => $hasilRekap
    ]);
}

if ($action === 'ringkasan' && $method === 'GET') {
    $stmt = $pdo->prepare(
        'SELECT r.*, s.nama_siswa
         FROM REKAP_AKADEMIK_KUADRAN r
         JOIN SISWA s ON s.id_siswa = r.id_siswa
         WHERE r.id_penugasan = ?'
    );
    $stmt->execute([$idPenugasan]);
    $rekapAll = $stmt->fetchAll();

    if (empty($rekapAll)) {
        respond([
            'sudah_dihitung'      => false,
            'jumlah_siswa'        => 0,
            'rata_rata_kelas'     => null,
            'nilai_tertinggi'     => null,
            'nilai_terendah'      => null,
            'persen_lengkap'      => 0,
            'distribusi_predikat' => ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0],
            'distribusi_kuadran'  => [
                'Rajin & nilai tinggi'         => 0,
                'Jarang kumpul & nilai tinggi' => 0,
                'Rajin & nilai rendah'         => 0,
                'Jarang kumpul & nilai rendah' => 0
            ]
        ]);
    }

    $nilaiAkhirArr = array_column($rekapAll, 'nilai_akhir');
    $jumlahLengkap = count(array_filter(
        $rekapAll, fn($r) => $r['status_kelengkapan_nilai'] === 'Lengkap'
    ));
    $jumlahSiswa   = count($rekapAll);

    respond([
        'sudah_dihitung'      => true,
        'jumlah_siswa'        => $jumlahSiswa,
        'rata_rata_kelas'     => round(array_sum($nilaiAkhirArr) / $jumlahSiswa, 2),
        'nilai_tertinggi'     => round(max($nilaiAkhirArr), 2),
        'nilai_terendah'      => round(min($nilaiAkhirArr), 2),
        'persen_lengkap'      => round(($jumlahLengkap / $jumlahSiswa) * 100, 1),
        'distribusi_predikat' => hitungDistribusiPredikat($nilaiAkhirArr),
        'distribusi_kuadran'  => hitungDistribusiKuadran($rekapAll)
    ]);
}

if ($action === 'hitung' && $method === 'POST') {
    $bobot = ambilBobot($pdo, $idPenugasan);

    $stmt = $pdo->prepare('SELECT * FROM PENILAIAN WHERE id_penugasan = ?');
    $stmt->execute([$idPenugasan]);
    $daftarPenilaian   = $stmt->fetchAll();
    $penilaianPerJenis = kelompokkanPenilaian($daftarPenilaian);
    $totalPenilaian    = count($daftarPenilaian);

    $stmt = $pdo->prepare('SELECT id_siswa FROM SISWA WHERE id_kelas = ?');
    $stmt->execute([$penugasan['id_kelas']]);
    $daftarSiswa = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($daftarSiswa)) {
        respond(null, 'Tidak ada siswa di kelas ini.', 422);
    }

    $idPenilaianAll = array_column($daftarPenilaian, 'id_penilaian');
    $nilaiMap       = ambilNilaiBulk($pdo, $idPenilaianAll);

    $pdo->beginTransaction();
    $hasilRekap = [];

    foreach ($daftarSiswa as $idSiswa) {
        $nilaiSiswa = $nilaiMap[$idSiswa] ?? [];

        $rataPerJenis = hitungRataPerJenis($penilaianPerJenis, $nilaiSiswa);
        $nilaiAkhir   = hitungNilaiAkhir($rataPerJenis, $bobot);

        $tepatWaktu   = 0;
        $sudahDinilai = 0;
        foreach ($nilaiSiswa as $n) {
            $sudahDinilai++;
            if (strtolower(trim($n['status_pengumpulan'] ?? '')) === 'tepat waktu') $tepatWaktu++;
        }
        $indeksKedisiplinan = ($totalPenilaian > 0)
            ? round(($tepatWaktu / $totalPenilaian) * 100, 2)
            : 0;

        $statusKelengkapan = ($sudahDinilai >= $totalPenilaian && $totalPenilaian > 0)
            ? 'Lengkap' : 'Belum lengkap';

        $kuadran     = tentukanKuadran($nilaiAkhir, $indeksKedisiplinan);
        $rekomendasi = ambilRekomendasi($kuadran);
        $predikat    = hitungPredikat($nilaiAkhir);

        $stmt = $pdo->prepare(
            'SELECT id_rekap FROM REKAP_AKADEMIK_KUADRAN
             WHERE id_penugasan = ? AND id_siswa = ?'
        );
        $stmt->execute([$idPenugasan, $idSiswa]);
        $existingRekap = $stmt->fetch();

        if ($existingRekap) {
            $stmt = $pdo->prepare(
                'UPDATE REKAP_AKADEMIK_KUADRAN
                 SET nilai_akhir = ?, indeks_kedisiplinan = ?,
                     status_kelengkapan_nilai = ?, kategori_kuadran = ?,
                     rekomendasi_sistem = ?
                 WHERE id_rekap = ?'
            );
            $stmt->execute([
                $nilaiAkhir, $indeksKedisiplinan, $statusKelengkapan,
                $kuadran, $rekomendasi, $existingRekap['id_rekap']
            ]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO REKAP_AKADEMIK_KUADRAN
                 (id_penugasan, id_siswa, nilai_akhir, indeks_kedisiplinan,
                  status_kelengkapan_nilai, kategori_kuadran, rekomendasi_sistem)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $idPenugasan, $idSiswa, $nilaiAkhir, $indeksKedisiplinan,
                $statusKelengkapan, $kuadran, $rekomendasi
            ]);
        }

        $hasilRekap[] = [
            'id_siswa'                => $idSiswa,
            'nilai_akhir'             => $nilaiAkhir,
            'predikat'                => $predikat,
            'indeks_kedisiplinan'     => $indeksKedisiplinan,
            'status_kelengkapan_nilai'=> $statusKelengkapan,
            'kategori_kuadran'        => $kuadran,
            'rekomendasi_sistem'      => $rekomendasi
        ];
    }

    $pdo->commit();
    respond($hasilRekap, 'Rekap berhasil dihitung untuk ' . count($hasilRekap) . ' siswa.');
}

respond(null, 'Aksi tidak dikenali. Gunakan: penugasan, bobot, rekap, ringkasan, atau hitung.', 400);
