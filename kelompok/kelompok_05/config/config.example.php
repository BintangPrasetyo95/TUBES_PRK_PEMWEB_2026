<?php
// Salin file ini menjadi config.local.php lalu sesuaikan. config.local.php TIDAK di-commit.
// Pengaturan XAMPP bawaan sudah cocok untuk sebagian besar laptop.
return [
    'app_name' => 'CEKHAK',

    // Database. XAMPP bawaan: user root tanpa password.
    'db_host' => '127.0.0.1',
    'db_port' => 3306,
    'db_name' => 'cekhak',
    'db_user' => 'root',
    'db_pass' => '',

    // Kosongkan agar alamat dasar terdeteksi otomatis (disarankan).
    // Isi hanya bila link/CSS tidak termuat, contoh: '/Tubes/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_05'
    'base_url' => '',

    // Asal (origin) lain yang boleh memanggil API lewat CORS. Kosong = hanya halaman dari server ini.
    // Contoh: ['http://localhost:5500']
    'cors_origins' => [],

    // true = tampilkan error PHP dan aktifkan halaman login pengembangan (modules/contoh/).
    // Set false saat demo dan sebelum PR final.
    'dev_mode' => true,
];
