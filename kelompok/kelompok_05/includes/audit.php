<?php
// Pencatatan aktivitas penting ke tabel audit_logs. Hanya PIC yang boleh mengubah file ini.
//   audit_log('buat_pemeriksaan', 'cases', $case_id, 'Surat Kuasa');

function audit_log(string $aksi, string $entitas, ?int $id = null, string $detail = ''): void
{
    try {
        $user = current_user();
        $st = db()->prepare(
            'INSERT INTO audit_logs (user_id, aksi, entitas, entitas_id, detail, ip) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $st->execute([$user['id'] ?? null, $aksi, $entitas, $id, $detail, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (Throwable $e) {
        // Kegagalan mencatat log tidak boleh menghentikan aplikasi.
    }
}
