# KebunKu

Sistem Informasi Manajemen Agrikultur berbasis web untuk memonitor dan mengelola operasional pertanian secara terpadu, mencakup pemetaan lahan, pemantauan siklus tanam, pengelolaan inventaris logistik, pendataan tenaga kerja, serta pencatatan hasil panen.

Aplikasi dibangun menggunakan PHP Native dengan pola MVC ringan, koneksi database PDO, dan interaksi antarmuka berbasis AJAX (Fetch API) tanpa framework CSS eksternal.

---

## Fitur

- **Dashboard** — Ringkasan metrik utama operasional kebun dan integrasi widget prakiraan cuaca.
- **Manajemen Lahan** — Pendataan lokasi, luas area, dan klasifikasi jenis tanah.
- **Siklus Tanam** — Monitoring fase penanaman per petak lahan dari tanam, perawatan, hingga estimasi panen.
- **Inventaris** — Pengendalian stok sarana produksi (bibit, pupuk, pestisida, peralatan) beserta biaya satuan.
- **Tenaga Kerja** — Pengelolaan data pekerja, jabatan fungsional, dan skema kompensasi.
- **Hasil Panen** — Pencatatan log panen per siklus, volume produksi (kg), dan grading kualitas hasil.

---

## Struktur Direktori

```
kebunku/
├── app/
│   ├── Config/
│   │   └── Database.php          # Konfigurasi koneksi PDO & helper response JSON
│   ├── Controllers/              # Handler request & logika bisnis API
│   ├── Models/                   # Layer abstraksi data & query SQL (PDO)
│   └── Views/                    # Template antarmuka antarmuka
│       └── layouts/              # Komponen bersama (header, sidebar, footer)
├── database/
│   └── schema.sql                # DDL tabel & data inisialisasi awal
└── public/                       # Web root publik
    ├── index.php                 # Front controller (page router)
    ├── api.php                   # Gateway endpoint REST API
    └── assets/
        ├── css/                  # Stylesheet kustom & design tokens
        └── js/                   # Skrip controller AJAX (Fetch API)
```

---

## Alur Kerja Sistem

1. **Routing Halaman**: Semua permintaan antarmuka diproses melalui `public/index.php` menggunakan parameter query `?page=<nama_halaman>` yang memuat view terkait beserta layout dasarnya.
2. **Komunikasi Data**: Setiap halaman memuat script JavaScript masing-masing di direktori `public/assets/js/` yang berkomunikasi ke `public/api.php` secara asinkron (AJAX Fetch).
3. **API Gateway**: `public/api.php` menerima permintaan berbasis parameter `?resource=<nama_modul>&id=<id>` dan metode HTTP (`GET`, `POST`, `PUT`, `DELETE`), kemudian mendelegasikannya ke controller yang sesuai.
4. **Data Access**: Controller memanggil model terkait untuk mengeksekusi prepared statement PDO, kemudian mengembalikan respons terstandarisasi dalam format JSON.

---

## Kebutuhan Sistem

- PHP >= 8.0 (ekstensi `pdo_mysql` aktif)
- MySQL / MariaDB >= 10.3
- Web Server Apache / Nginx, atau PHP Built-in Server

---

## Instalasi & Menjalankan

1. **Clone / Pasang Repositori**  
   Tempatkan folder proyek ke dalam direktori server web (misalnya `laragon/www/` atau `xampp/htdocs/`).

2. **Setup Database**  
   Buat database baru di MySQL dan impor skema awal:
   ```bash
   mysql -u root -p -e "CREATE DATABASE kebunku;"
   mysql -u root -p kebunku < database/schema.sql
   ```

3. **Konfigurasi Lingkungan**  
   Sesuaikan parameter koneksi database pada `app/Config/Database.php` bila diperlukan:
   ```php
   private $host = "localhost";
   private $db_name = "kebunku";
   private $username = "root";
   private $password = "";
   ```

4. **Menjalankan Aplikasi**  
   - **Melalui Web Server (Laragon / XAMPP):**  
     Akses melalui browser di:
     ```
     http://localhost/kelompok_19/public/
     ```
   - **Melalui PHP Built-in Server:**  
     Buka terminal pada root proyek:
     ```bash
     cd public
     php -S localhost:8000
     ```
     Lalu buka `http://localhost:8000` pada browser.
