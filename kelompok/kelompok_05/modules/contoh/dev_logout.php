<?php
// SEMENTARA, hanya untuk pengembangan. Dihapus bersama folder modules/contoh/.
// Catatan untuk modul auth (Anggota D): logout yang sebenarnya sebaiknya lewat POST + csrf_check().
require_once __DIR__ . '/../../includes/bootstrap.php';

logout_user();
flash('success', 'Kamu sudah keluar.');
redirect('index.php');
