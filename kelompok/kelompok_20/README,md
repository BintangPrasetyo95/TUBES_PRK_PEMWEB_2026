# Aspiraku

**Portal Pengaduan dan Aspirasi Publik / Kampus**
Tugas Besar Praktikum Pemrograman Web 2026, Teknik Informatika Universitas Lampung
Tema 03: Fakultas FISIP (Portal Pengaduan & Aspirasi Publik)

Aspiraku adalah website tempat mahasiswa dan warga kampus menyampaikan **pengaduan** (keluhan) dan **aspirasi** (saran) secara daring, lengkap dengan foto pendukung. Admin menerima laporan, mengubah status penanganannya (Menunggu, Diproses, Selesai), lalu menulis tanggapan yang bisa dibaca oleh pelapor. Laporan yang ditandai publik bisa dilihat dan dicari oleh siapa saja.

## Anggota Kelompok 20

| Nama | Peran |
|---|---|
| Dhani Pratama | Ketua, fondasi proyek, integrator |
| Nabila Rahmadiani | Autentikasi dan profil |
| Ridho Azhari Putra | Halaman publik dan API |
| Ica Nuria Ilmawati | Laporan pengguna dan dokumentasi |
| Putri Nabilla Atifa | Panel admin dan data contoh |

## Teknologi

- HTML5, CSS3, JavaScript
- PHP Native (tanpa framework backend), PHP 8.2 atau lebih baru
- MySQL / MariaDB, diakses lewat PDO
- Tanpa library atau CDN dari luar

## Fitur

**Pengunjung (Guest)**
- Melihat beranda, statistik laporan, dan 5 laporan publik terbaru
- Melihat daftar laporan publik dengan pencarian langsung (live search tanpa memuat ulang halaman), filter kategori dan jenis, serta pagination (10 laporan per halaman)
- Melihat detail laporan publik beserta tanggapan admin
- Mendaftar akun dan masuk (kolom password punya tombol lihat atau sembunyikan)
- Membuat tautan reset lewat **Lupa Password** bila lupa kata sandi

**Pengguna (Pelapor)**
- Mengirim laporan (pengaduan atau aspirasi) dengan judul, isi, kategori, lokasi, foto opsional, dan pilihan tampil publik atau tidak
- Pratinjau foto sebelum dikirim
- Melihat daftar Laporan Saya beserta status dan tanggapan admin, dengan filter jenis dan status
- Mengedit dan menghapus laporan sendiri, **hanya selama status masih Menunggu**
- Mengubah profil (nama, email, password) dan melihat ringkasan aktivitas laporan (total, menunggu, diproses, selesai)

**Admin**
- Dashboard berisi statistik, 5 laporan terbaru, dan grafik batang persentase pengaduan dan aspirasi
- Semua Laporan: cari berdasarkan judul, isi, lokasi, atau nama pelapor; filter status, jenis, dan privasi (publik atau privat); ubah status, tulis tanggapan, hapus laporan
- Kelola Kategori: tambah dan hapus kategori
- Daftar Pengguna (hanya baca): lihat akun terdaftar, jumlah laporan tiap akun, cari berdasarkan nama atau email, dan filter peran
- Rekap laporan per periode yang bisa dicetak

**Keamanan yang dipakai**
- Query memakai PDO prepared statement
- Output PHP lewat `sanitize()` (`htmlspecialchars`), data dari API di JavaScript memakai fungsi `esc()`
- Password disimpan dengan `password_hash()` dan dicek dengan `password_verify()`
- Pengecekan hak akses (`wajibLogin()` dan `wajibAdmin()`) dan kepemilikan data pada query
- Unggahan foto hanya JPG atau PNG, maksimal 2 MB, MIME asli dicek dengan `finfo`, nama file diacak
- Token CSRF pada semua form POST (login, register, profil, halaman `user/*`, serta Kelola Laporan dan Kategori di admin)
- Pembatasan login gagal: 5 kali gagal dalam 15 menit pada satu email akan menunda login selama 15 menit
- Reset password memakai token acak yang disimpan dalam bentuk hash SHA-256, berlaku 15 menit, dan hanya bisa dipakai sekali

## Kebutuhan Sistem

- PHP 8.2 atau lebih baru dengan ekstensi `pdo_mysql` dan `fileinfo` aktif
- MySQL atau MariaDB yang sedang berjalan (bisa dari XAMPP, Laragon, atau instalasi sendiri)
- Git

Cek di terminal:

```bash
php -v
php -m
```

Pastikan `pdo_mysql` dan `fileinfo` muncul di daftar `php -m`.

## Instalasi

### 1. Ambil kode

Repositori kelompok (fork milik Dhani): https://github.com/dhani210905/TUBES_PRK_PEMWEB_2026

```bash
git clone https://github.com/dhani210905/TUBES_PRK_PEMWEB_2026.git
cd TUBES_PRK_PEMWEB_2026/kelompok/kelompok_20
```

Kode sistem kami ada di folder `kelompok/kelompok_20/`. Repositori pusat Tugas Besar ada di https://github.com/Praktikum-LabKom-TI-Unila/TUBES_PRK_PEMWEB_2026.

### 2. Nyalakan MySQL

Jalankan MySQL / MariaDB lewat XAMPP atau Laragon (tombol Start pada modul MySQL).

### 3. Cek pengaturan database

Buka `config/database.php`. Bawaannya seperti ini:

```php
$db_host = 'localhost';
$db_name = 'tubes_ppw';
$db_user = 'root';
$db_pass = '';
```

Kalau MySQL di komputermu memakai user atau password yang berbeda, ubah di file ini.

### 4. Database dibuat otomatis

Kamu **tidak perlu mengimpor SQL secara manual**. Saat halaman pertama dibuka, `config/database.php` akan:

1. Membuat database `tubes_ppw` bila belum ada
2. Mengecek tabel `users`, `kategori`, `laporan`, dan `tanggapan`
3. Jika ada tabel yang belum ada, otomatis mengimpor `database/database.sql` (struktur tabel dan 5 kategori awal) lalu `database/seed.sql` (akun contoh dan laporan contoh)

Tabel `percobaan_login` dibuat otomatis oleh `login.php` saat pertama kali dipakai.

> Kalau lebih suka manual, impor `database/database.sql` lalu `database/seed.sql` lewat phpMyAdmin ke database `tubes_ppw`.

### 5. Jalankan server

Jalankan perintah ini **dari dalam folder `kelompok_20`**:

```bash
php -S localhost:8000
```

Lalu buka **http://localhost:8000** di browser.

> Penting: semua tautan di aplikasi diawali `/`, jadi server harus dijalankan dari dalam folder `kelompok_20`. Jangan membuka file lewat `file://`, dan jangan menaruhnya di subfolder `htdocs` tanpa mengubah tautan.

## Akun Contoh (dari `seed.sql`)

| Peran | Email | Password |
|---|---|---|
| Admin | `admin@fisip.unila.ac.id` | `PasswordSementara123` |
| Pengguna | `budi.santoso@mhs.unila.ac.id` | `PasswordSementara123` |
| Pengguna | `siti.rahma@mhs.unila.ac.id` | `PasswordSementara123` |
| Pengguna | `ahmad.fauzi@mhs.unila.ac.id` | `PasswordSementara123` |

Password di atas hanya untuk demo dan pengujian. Ganti sebelum dipakai sungguhan. Akun admin tidak bisa dibuat lewat halaman Daftar, hanya lewat `seed.sql` atau SQL langsung.

## Cara Penggunaan

### Sebagai pengunjung
1. Buka beranda untuk melihat statistik dan laporan terbaru.
2. Buka **Laporan Publik**, lalu ketik kata kunci di kolom pencarian. Hasil muncul otomatis. Gunakan filter kategori dan jenis (tombol **Terapkan Filter** dan **Reset** tersedia), atau tombol halaman di bawah.
3. Klik sebuah laporan untuk melihat detail dan tanggapan admin.

### Sebagai pengguna (pelapor)
1. Klik **Daftar**, isi nama, email, dan password (minimal 6 karakter), lalu **Masuk**.
2. Klik **Sampaikan Laporan**, pilih jenis (pengaduan atau aspirasi), isi judul, isi laporan, kategori, dan lokasi. Foto boleh ditambahkan (JPG atau PNG, maksimal 2 MB). Hilangkan centang "tampilkan publik" bila laporan ingin dilihat sendiri dan admin saja.
3. Pantau status di **Laporan Saya**. Selama status masih *Menunggu*, laporan masih bisa diedit atau dihapus.
4. Ubah data akun di **Profil**. Di halaman ini juga ada ringkasan jumlah laporanmu berdasarkan status.

### Lupa password
1. Di halaman Masuk, klik **Lupa Password?**.
2. Isi email akun yang terdaftar, lalu klik **Buat Tautan Reset**.
3. Karena sistem belum mengirim email, tautan reset ditampilkan langsung di halaman (tertulis "Simulasi"). Klik tautan itu.
4. Isi password baru (minimal 6 karakter) dan konfirmasinya. Tautan berlaku 15 menit dan hanya bisa dipakai sekali.

### Sebagai admin
1. Masuk dengan akun admin, kamu diarahkan ke **Dashboard**.
2. Buka **Semua Laporan**. Cari dengan kata kunci atau pakai filter status, jenis, dan privasi. Klik tombol tindak lanjut pada laporan untuk mengubah status dan menulis tanggapan, atau hapus laporan yang tidak pantas.
3. Buka **Kelola Kategori** untuk menambah atau menghapus kategori. Menghapus kategori tidak menghapus laporan, kategorinya akan tampil `-`.
4. Buka **Pengguna** untuk melihat daftar akun dan jumlah laporan masing-masing. Halaman ini hanya baca, admin tidak bisa mengubah atau menghapus akun dari sini.
5. Untuk rekap cetak, klik **Rekap Cetak** di menu, pilih rentang tanggal, status, atau jenis, lalu cetak dari browser.

### Alur status laporan

```
Menunggu  ->  Diproses  ->  Selesai
```

Admin boleh mengubah status ke status mana pun, termasuk untuk mengoreksi kesalahan.

## Struktur Folder

```text
kelompok_20/
├── index.php            login.php         register.php      logout.php
├── laporan_publik.php   detail.php        profil.php
├── lupa_password.php    reset_password.php
├── api/
│   └── laporan.php          # endpoint JSON untuk live search
├── user/
│   ├── kirim.php  laporan_saya.php  edit.php  hapus.php
├── admin/
│   ├── dashboard.php  kelola.php  kategori.php  pengguna.php  rekap.php
├── config/
│   └── database.php         # koneksi PDO dan setup database otomatis
├── includes/
│   ├── functions.php  header.php  footer.php
├── assets/
│   ├── css/style.css
│   ├── js/app.js  auth.js  ui.js
│   └── uploads/             # foto laporan
├── database/
│   ├── database.sql         # struktur tabel dan kategori awal
│   └── seed.sql             # akun dan laporan contoh
└── README.md
```

## Database

Empat tabel utama:

| Tabel | Isi |
|---|---|
| `users` | akun pengguna dan admin (`role` = `user` atau `admin`) |
| `kategori` | daftar kategori laporan (nama unik) |
| `laporan` | pengaduan dan aspirasi (jenis, judul, isi, lokasi, foto, `is_public`, status) |
| `tanggapan` | tanggapan admin untuk tiap laporan |

Relasi penting: menghapus laporan ikut menghapus tanggapannya, dan menghapus kategori mengubah `kategori_id` laporan menjadi `NULL`.

Dua tabel tambahan dibuat otomatis saat halamannya pertama dipakai: `percobaan_login` (pembatasan login gagal, dibuat oleh `login.php`) dan `password_resets` (token reset password, dibuat oleh `lupa_password.php`).

## API

`GET /api/laporan.php?search=kata&kategori=ID&jenis=JENIS&page=N`

Dipakai oleh halaman Laporan Publik. Hanya mengembalikan laporan yang `is_public = 1` dan tidak pernah memuat email atau password pelapor. Mengembalikan JSON:

```json
{
  "status": "success",
  "message": "Data berhasil diambil",
  "data": [ ... ],
  "total_pages": 1,
  "current_page": 1
}
```

Kode respons: `200` berhasil, `405` bila bukan metode GET, `500` bila terjadi kesalahan server (pesan umum saja).

## Keterbatasan yang Diketahui

- Fitur Lupa Password masih **simulasi**: sistem tidak mengirim email, tautan reset ditampilkan langsung di halaman. Selain itu pesan "Email tidak ditemukan" memungkinkan orang menebak email yang terdaftar. Untuk dipakai sungguhan, tautan harus dikirim lewat email dan pesannya dibuat sama untuk semua kasus.
- Belum ada notifikasi email, chat antara pelapor dan admin, dan mode gelap.

## Pemecahan Masalah

| Masalah | Penyebab dan solusi |
|---|---|
| "Koneksi database gagal" | MySQL belum menyala, atau user dan password di `config/database.php` tidak cocok |
| "could not find driver" | Ekstensi `pdo_mysql` belum aktif di `php.ini` |
| Tampilan tanpa CSS atau tautan error 404 | Server tidak dijalankan dari dalam folder `kelompok_20` |
| Foto gagal diunggah | Ukuran di atas 2 MB, bukan JPG atau PNG, atau folder `assets/uploads/` tidak bisa ditulis |
| Tidak bisa login setelah beberapa kali salah | Pembatasan login aktif, tunggu 15 menit |
| "Tautan reset password tidak valid atau sudah kedaluwarsa" | Tautan sudah lewat 15 menit atau sudah dipakai. Buat tautan baru lewat Lupa Password |
| Ingin mengulang data dari awal | Hapus database `tubes_ppw` di phpMyAdmin, lalu buka aplikasi lagi, data akan dibuat ulang otomatis |

## Catatan Pengumpulan

File ini diletakkan di `kelompok/kelompok_20/README.md`. Seluruh kode berada di `kelompok/kelompok_20/`, dan Pull Request ke repositori pusat hanya boleh mengubah folder tersebut. Sebelum PR final, pastikan semua file `.php` lolos pengecekan sintaks:

```bash
find . -name "*.php" -exec php -l {} \;
```

Pastikan juga tidak ada file di atas 5 MB dan folder `assets/uploads/` hanya berisi `.gitkeep`.
