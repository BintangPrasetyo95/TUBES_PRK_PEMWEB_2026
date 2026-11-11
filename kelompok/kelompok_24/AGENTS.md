# Panduan Proyek ADVOKITA

## Stack dan batasan

ADVOKITA menggunakan PHP 8.1 atau lebih baru, PostgreSQL melalui PDO, serta HTML dan CSS. JavaScript dilarang sepenuhnya: jangan menambahkan kode inline atau eksternal, elemen `<script>`, berkas `.js`, Node.js, npm/pnpm, Vite, atau antarmuka yang memerlukan skrip.

Gunakan form HTML POST, validasi dan rendering server-side, sesi PHP, serta komponen Bootstrap CSS-only. Pertahankan dukungan browser tanpa JavaScript.

## Struktur sumber

- `public/index.php` adalah front controller dan satu-satunya titik masuk web; arah halaman memakai parameter `page`.
- `app/bootstrap.php` mengatur sesi, koneksi PDO, proteksi CSRF, validasi, serta akses data bersama.
- `templates/layout.php` dan `templates/pages/` berisi markup HTML server-side.
- `public/assets/css/` dan `public/assets/` memuat CSS, ikon, dan gambar yang disajikan publik.
- `database/schema.sql` adalah skema PostgreSQL.
- `bin/` berisi utilitas PHP untuk menyiapkan tabel dan membuat akun admin.
- `scripts/setup-local-db.ps1` menyiapkan role dan database PostgreSQL lokal di Laragon.
- `tests/validation.php` menjalankan pengujian validasi tanpa framework tambahan.
- `storage/uploads/` menyimpan bukti secara privat di luar document root `public/`.

## Ketentuan keamanan

- Validasi input di server; gunakan prepared statements PDO.
- Lindungi seluruh aksi POST dengan token CSRF. Cek autentikasi, peran, dan kepemilikan pada setiap operasi laporan maupun lampiran.
- Gunakan `password_hash`/`password_verify`, rotasi ID sesi saat autentikasi, dan cookie HTTP-only/SameSite.
- Batasi serta deteksi tipe konten berkas berdasarkan isi; jangan menaruh unggahan di direktori publik.
- Escape nilai dinamis saat dirender ke HTML. Jangan mengandalkan validasi browser sebagai pengganti validasi server.
- Jangan commit `.env`, kata sandi, token, atau rahasia.

## Verifikasi

- `php tests/validation.php` menjalankan tes validasi.
- `php -l <berkas.php>` memeriksa sintaks PHP.
- `php bin/setup-database.php` menerapkan skema; perintah ini memerlukan koneksi database terkonfigurasi.
- Pastikan berkas sumber maupun keluaran publik tidak memuat JavaScript atau elemen `<script>`.

Lihat `README.md` untuk cara instalasi dan penggunaan lokal.
