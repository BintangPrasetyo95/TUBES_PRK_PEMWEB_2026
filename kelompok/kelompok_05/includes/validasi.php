<?php
// Aturan format bersama, sisi PHP. Pasangannya di JavaScript: assets/js/validate.js.
// Kedua sisi WAJIB identik 

// cek_format('tanggal', '2026-11-03')  -> true
// Nilai kosong ditangani aturan "wajib" oleh pemanggil, bukan di sini. Aturan tak dikenal tidak memblokir.

function cek_format(string $aturan, string $nilai): bool
{
    $nilai = trim($nilai);
    switch ($aturan) {
        case 'tanggal':
            $d = DateTime::createFromFormat('Y-m-d', $nilai);
            if ($d === false || $d->format('Y-m-d') !== $nilai) {
                return false;
            }
            $tahun = (int) $d->format('Y');
            return $tahun >= 1900 && $tahun <= 2100;
        case 'angka':
            return preg_match('/^-?[0-9]+(\.[0-9]+)?$/', $nilai) === 1;
        case 'nomor':
            if (preg_match('/^[0-9 ()+-]+$/', $nilai) !== 1) {
                return false;
            }
            $digit = (string) preg_replace('/[ ()+-]/', '', $nilai);
            return preg_match('/^[0-9]{8,15}$/', $digit) === 1;
        case 'email':
            return preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $nilai) === 1;
    }
    return true;
}
