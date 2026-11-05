# Database Sistem Antrian Klinik - Kelompok 03

## 1. Teknologi

- PHP Native
- PDO
- MySQL 8.0.16+
- MariaDB 10.3+
- InnoDB
- Bootstrap 5
- UTF-8 (utf8mb4)

## 2. Struktur Database

Database yang digunakan adalah `klinik_antrian`.

Database memiliki tiga tabel utama:

| Tabel   | Fungsi                                         |
| ------- | ---------------------------------------------- |
| users   | Menyimpan akun admin dan pasien                |
| dokter  | Menyimpan informasi dokter dan jadwal praktik  |
| antrian | Menyimpan pendaftaran dan nomor antrean pasien |

## 3. Struktur Relasi

- Satu pengguna dapat memiliki banyak data antrean.
- Satu dokter dapat memiliki banyak data antrean.
- Setiap antrean terhubung dengan satu pengguna.
- Setiap antrean terhubung dengan satu dokter.

Relasi menggunakan foreign key dengan InnoDB.

## 4. Cara Instalasi Database

1. Jalankan Apache dan MySQL melalui XAMPP.
2. Buka phpMyAdmin melalui `http://localhost/phpmyadmin`.
3. Pilih menu Import.
4. Pilih file `database/klinik.sql`.
5. Klik Import.
6. Pastikan database `klinik_antrian` berhasil dibuat.
7. Periksa tabel users, dokter, dan antrian.

## 5. Konfigurasi Koneksi

File koneksi berada di:

`config/koneksi.php`

Konfigurasi database lokal:

| Parameter | Nilai          |
| --------- | -------------- |
| Host      | 127.0.0.1      |
| Port      | 3306           |
| Database  | klinik_antrian |
| Username  | root           |
| Password  | Kosong         |

Koneksi database menggunakan PDO.

File PHP yang membutuhkan koneksi dapat memanggil:

```php
require_once __DIR__ . '/../config/koneksi.php';
```

Objek koneksi database tersedia melalui variabel `$pdo`.

## 6. Akun Pengujian

| Role   | Email                     | Password         |
| ------ | ------------------------- | ---------------- |
| Admin  | admin@klinik.local        | AdminKlinik#2026 |
| Pasien | andi.pasien@klinik.local  | Pasien01#2026    |
| Pasien | bunga.pasien@klinik.local | Pasien02#2026    |
| Pasien | citra.pasien@klinik.local | Pasien03#2026    |

Password disimpan dalam bentuk hash menggunakan PHP `password_hash()` dengan algoritma PASSWORD_DEFAULT.

Gunakan `password_verify()` untuk proses verifikasi password.

Akun tersebut hanya digunakan untuk pengujian praktikum.

## 7. Aturan Implementasi

1. Gunakan PDO prepared statement untuk query yang menerima input pengguna.
2. Periksa role pengguna pada setiap halaman admin dan pasien.
3. Gunakan transaksi database ketika pasien mengambil nomor antrean.
4. Gunakan SELECT FOR UPDATE untuk mengunci baris dokter ketika menentukan nomor berikutnya.
5. Periksa jadwal praktik dan kuota harian sebelum menyimpan antrean.
6. Cegah pendaftaran ganda pada dokter dan tanggal yang sama.
7. Gunakan htmlspecialchars() ketika menampilkan data pengguna ke HTML.
8. Jangan menghapus dokter atau pengguna yang masih memiliki catatan antrean.

## 8. Batasan Fase 1

Database ini memiliki tiga tabel utama, yaitu users, dokter, dan antrian.

Fitur jadwal praktik berbasis jam, poli, rekam medis, pembayaran, pembatalan, dan no-show belum termasuk dalam cakupan Fase 1.
