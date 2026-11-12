<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

function analisis_pemeriksaan(int $case_id): array
{
    if ($case_id < 1) throw new InvalidArgumentException('ID pemeriksaan tidak valid.');
    $pdo = db();
    $pdo->beginTransaction();
    $st = $pdo->prepare('SELECT document_type_id FROM cases WHERE id = ? FOR UPDATE');
    $st->execute([$case_id]);
    $case = $st->fetch();
    if (!$case) {
        $pdo->rollBack();
        throw new RuntimeException('Pemeriksaan tidak ditemukan.');
    }

    $st = $pdo->prepare('SELECT r.id, r.label, r.tipe_input, r.wajib, r.aturan_format, r.status_verifikasi, d.nilai, EXISTS(SELECT 1 FROM case_files f WHERE f.case_id = ? AND f.requirement_id = r.id) AS ada_berkas FROM requirements r LEFT JOIN case_data d ON d.case_id = ? AND d.requirement_id = r.id WHERE r.document_type_id = ? ORDER BY r.urutan, r.id');
    $st->execute([$case_id, $case_id, $case['document_type_id']]);
    $hasil = [];
    $jumlahBerlaku = 0;
    $jumlahTerpenuhi = 0;
    $siapDitinjau = true;
    foreach ($st->fetchAll() as $req) {
        $nilai = trim((string) ($req['nilai'] ?? ''));
        $adaNilai = $req['tipe_input'] === 'file' ? (bool) $req['ada_berkas'] : ($req['tipe_input'] === 'checkbox' ? $nilai === '1' : $nilai !== '');
        $formatValid = !$adaNilai || $req['tipe_input'] === 'file' || !$req['aturan_format']
            || cek_format((string) $req['aturan_format'], $nilai);
        $berlaku = (bool) $req['wajib'] || $adaNilai;
        if ($berlaku) $jumlahBerlaku++;
        if ($adaNilai && $formatValid) $jumlahTerpenuhi++;

        if (!$adaNilai && $req['wajib']) {
            $status = 'belum_lengkap';
            $pesan = 'Wajib diisi atau berkas belum diunggah.';
            $siapDitinjau = false;
        } elseif (!$formatValid) {
            $status = 'perlu_diperiksa';
            $pesan = 'Format ' . $req['aturan_format'] . ' tidak valid.';
            $siapDitinjau = false;
        } elseif ($req['status_verifikasi'] !== 'terverifikasi' && $adaNilai) {
            $status = 'perlu_diperiksa';
            $pesan = 'Persyaratan belum diverifikasi.';
        } else {
            $status = 'sesuai';
            $pesan = '';
        }
        $hasil[] = ['requirement_id' => (int) $req['id'], 'status' => $status, 'pesan' => $pesan, 'verifikasi' => $req['status_verifikasi']];
    }

    $persentase = $jumlahBerlaku ? round($jumlahTerpenuhi * 100 / $jumlahBerlaku, 2) : 100.0;
    $statusDisarankan = $siapDitinjau ? 'siap_ditinjau' : 'belum_lengkap';
    $json = json_encode($hasil, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $st = $pdo->prepare('INSERT INTO analysis_results (case_id, persentase, jumlah_terpenuhi, jumlah_berlaku, hasil_json) VALUES (?, ?, ?, ?, ?)');
    $st->execute([$case_id, $persentase, $jumlahTerpenuhi, $jumlahBerlaku, $json]);
    $pdo->prepare('UPDATE cases SET status = ? WHERE id = ?')->execute([$statusDisarankan, $case_id]);
    $pdo->commit();
    return ['persentase' => $persentase, 'hasil' => $hasil, 'status_disarankan' => $statusDisarankan,
        'jumlah_terpenuhi' => $jumlahTerpenuhi, 'jumlah_berlaku' => $jumlahBerlaku];
}

if (basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) === 'engine.php') {
    require_role(['pengguna']);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: POST');
        http_response_code(405);
        exit('Gunakan POST untuk menjalankan analisis.');
    }
    csrf_check();
    $caseId = filter_var($_POST['case_id'] ?? null, FILTER_VALIDATE_INT);
    $st = db()->prepare('SELECT status FROM cases WHERE id = ? AND user_id = ?');
    $st->execute([$caseId, current_user()['id']]);
    $case = $st->fetch();
    if (!$case) {
        http_response_code(404);
        exit('Pemeriksaan tidak ditemukan.');
    }
    if (in_array($case['status'], ['sedang_ditinjau', 'selesai'], true)) {
        flash('error', 'Pemeriksaan yang sedang atau sudah ditinjau tidak dapat dianalisis ulang.');
        redirect('modules/hasil/index.php?case=' . $caseId);
    }
    analisis_pemeriksaan((int) $caseId);
    flash('success', 'Analisis pemeriksaan berhasil diperbarui.');
    redirect('modules/hasil/index.php?case=' . $caseId);
}
