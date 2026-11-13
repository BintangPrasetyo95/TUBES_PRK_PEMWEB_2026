# SisaSense

**Sistem Manajemen Persediaan, Kasir POS, dan Analisis Performa Penjualan UMKM**  
*Tugas Besar Praktikum Pemrograman Web 2026 &bull; Kelompok 10*

SisaSense adalah aplikasi web *enterprise-grade* untuk membantu UMKM mengelola stok barang, mencatat transaksi penjualan di kasir (POS), memantau masa simpan/kedaluwarsa barang (*FEFO*), memberikan rekomendasi restok otomatis (*ADS & ROP*), serta menganalisis performa laba kotor secara transparan.

---

## 1. Tiga Peran Pengguna (Multi-Role System)

Sistem ini mendukung 3 peran pengguna dengan hak akses yang terisolasi secara ketat:

| Peran | Hak Akses Utama | Halaman Utama |
|---|---|---|
| **Administrator** | Akses penuh: manajemen produk & kategori, pencatatan stok masuk, penyesuaian/koreksi opname, laporan keuangan, analitik omzet & HPP, rekomendasi restok, simulasi diskon. | `dashboard.php`, `products/`, `inventory/`, `reports/`, `sales/` |
| **Kasir (Cashier)** | Akses operasional: antarmuka kasir POS, pencarian produk, perhitungan uang tunai & kembalian, pemrosesan transaksi belanja, riwayat invoice, cetak struk nota. | `dashboard.php`, `sales/pos.php`, `sales/history.php`, `inventory/index.php` |
| **Pembeli (Customer)** | Portal pelanggan: pendaftaran mandiri (`register.php`), eksplorasi katalog produk toko secara real-time, melihat harga & ketersediaan stok, riwayat struk belanjaan pribadi. | `dashboard.php`, `products/index.php`, `sales/my_orders.php` |

### Akun Demo Pengujian (Tinggal Pakai):

Setelah mengimpor database `database/sisa_sense.sql`, Anda dapat langsung masuk menggunakan akun demo berikut:

| Peran | Username / Email | Password Default |
|---|---|---|
| **Administrator** | `admin` *(atau admin@sisasense.id)* | `123` |
| **Kasir** | `kasir` *(atau kasir@sisasense.id)* | `123` |
| **Pembeli** | `pembeli` *(atau pembeli@sisasense.id)* | `123` |

*(Pelanggan baru juga dapat mendaftar mandiri kapan saja melalui menu **Daftar Akun Pembeli** pada halaman `register.php`)*.

---

## 2. Fitur Unggulan Sistem

1. **Kasir POS Modern & Modal Checkout Pop-Up (`sales/pos.php`):**
   - Antarmuka visual bersih terinspirasi dari **Ecavo (`ecavo.in`)**, **Majoo**, dan **Pano SaaS**.
   - Pencarian instan nama produk atau kode SKU serta filter navigasi pill kategori.
   - 24 Produk UMKM dengan foto nyata 100% sinkron dan autentik (Minyak Goreng Sawit 2L dalam botol kemasan jernih, Madu Hutan Murni dalam toples keemasan dengan honey dipper, Beras Ramos, Dodol Garut, Abon Sapi, Jamu Kunyit Asam, dll).
   - Bebas gangguan: Menghilangkan header WhatsApp/telepon yang berlebihan dan menyederhanakan halaman login/register tanpa dinding teks berbelit-belit.
   - Keranjang belanja interaktif dengan proteksi stok fisik (tidak dapat melebihi sisa stok).
   - **Modal Pop-Up Multi-Metode Pembayaran:**
     - 💵 **Tunai (Cash):** Kalkulasi kembalian otomatis real-time & quick cash buttons (`Uang Pas`, `+10rb`, `+20rb`, `+50rb`, `+100rb`).
     - 📱 **QRIS (Standar Bank Indonesia):** QR code dinamis terintegrasi e-wallet & mobile banking.
     - 🏦 **Transfer Bank:** Nomor rekening resmi BCA, Mandiri, dan BRI dengan tombol salin instan (*copy-to-clipboard*).
     - 💳 **Kartu Debit / EDC:** Pilihan mesin EDC bank dan pencatatan nomor approval kartu.
2. **Transaksi Database Atomik & Alokasi FEFO (`sales/checkout.php`):**
   - Menggunakan `BEGIN TRANSACTION` dengan *row-level locking* pada MySQL InnoDB.
   - Otomatis memotong stok dari batch yang tanggal kedaluwarsanya paling dekat (*First Expired, First Out*).
   - Menyimpan *snapshot* harga jual dan harga pokok modal (HPP) per baris item untuk akurasi laporan historis.
   - Mencatat otomatis mutasi keluar ke tabel audit `inventory_movements`.
   - Mengembalikan (*rollback*) seluruh data jika terjadi kegagalan atau stok tidak mencukupi di tengah proses.
3. **Struk Belanja Siap Cetak (`sales/receipt.php`):**
   - Desain nota belanja ala printer kasir termal mini (58mm/80mm) maupun kertas A4 standar dengan dukungan `@media print`.
4. **Manajemen Persediaan & Batch (`inventory/`):**
   - Ringkasan stok tersedia hasil agregasi `SUM(qty_remaining)` seluruh batch aktif.
   - Pelacak lot/batch dengan indikator hari menuju kedaluwarsa (Aman, Kritis <30 hari, Kedaluwarsa).
   - Pencatatan stok masuk (`inventory/incoming.php`) dan penyesuaian keluar non-penjualan (`inventory/adjustment.php` untuk barang rusak, hilang, atau kedaluwarsa).
   - Audit trail mutasi barang lengkap (`inventory/movements.php`).
5. **Dashboard Analitik & Rekomendasi Restok (`reports/`):**
   - Ringkasan Keuangan: Omzet Bersih, HPP Terjual, Laba Kotor, dan Persentase Margin.
   - Visualisasi tren penjualan 7 hari menggunakan **Native Pure SVG Micro-Chart** tanpa library JS eksternal.
   - Produk Terlaris (*Top Selling*) & Produk Lambat Terjual (*Slow Moving* > 30 hari).
   - **Sistem Pendukung Keputusan Restok:** Menghitung *Average Daily Sales* (ADS), *Safety Stock*, *Reorder Point* (ROP), dan saran kuantitas restok.
   - **Simulator Diskon:** Menguji skenario diskon harga dan membunyikan alarm peringatan jika harga jatuh di bawah HPP modal UMKM.
   - **Ekspor CSV:** Unduh laporan penjualan dan laporan stok persediaan berstandar RFC 4180 (dilengkapi UTF-8 BOM untuk Microsoft Excel).

---

## 3. Struktur Direktori Proyek

```text
kelompok_10/
├── assets/
│   └── css/
│       └── style.css            # Design system, layout kasir, responsif & print
├── config/
│   ├── auth.php                 # Autentikasi, session cookie unik, CSRF, dynamic URL
│   └── database.php             # Koneksi PDO Singleton fleksibel
├── database/
│   └── sisa_sense.sql           # DDL resmi 8 tabel + Data awal demo akun 3 role
├── includes/
│   ├── header.php               # Navigasi sidebar dinamis sesuai role (SVG icons)
│   └── footer.php               # Penutup layout bersama
├── products/                    # [MODUL 1 - PRODUK & KATEGORI]
│   ├── index.php                # Katalog produk & tabel stok
│   ├── create.php               # Form tambah produk baru + auto SKU
│   ├── edit.php                 # Form edit produk & toggle nonaktif aman
│   └── categories.php           # Manajemen kategori barang
├── inventory/                   # [MODUL 2 - PERSEDIAAN & BATCH]
│   ├── index.php                # Monitoring stok fisik real-time
│   ├── incoming.php             # Form catat stok masuk (Batch baru)
│   ├── batches.php              # Detail batch & countdown expired (FEFO)
│   ├── adjustment.php           # Form penyesuaian barang rusak / selisih opname
│   └── movements.php            # Riwayat mutasi persediaan (audit log)
├── sales/                       # [MODUL 3 - POS KASIR & PENJUALAN]
│   ├── pos.php                  # Antarmuka kasir belanja interaktif
│   ├── checkout.php             # Pemroses transaksi database atomik
│   ├── history.php              # Riwayat struk invoice toko
│   ├── receipt.php              # Tampilan nota cetak kasir
│   └── my_orders.php            # Riwayat belanja khusus akun pembeli
├── reports/                     # [MODUL 4 - ANALITIK & LAPORAN]
│   ├── index.php                # Executive Dashboard Analitik & Tren SVG
│   ├── sales_report.php         # Laporan penjualan detail berfilter tanggal
│   ├── inventory_report.php     # Laporan nilai aset modal persediaan (HPP)
│   ├── export_sales_csv.php     # Ekspor CSV penjualan (Excel-ready)
│   ├── export_inventory_csv.php # Ekspor CSV persediaan
│   ├── restock_recommendations.php # SPK Rekomendasi Restok (ADS & ROP)
│   └── discount_simulator.php   # Simulator diskon & alarm proteksi defisit
├── skills/                      # [SKILL AI & DESAIN SISTEM]
│   └── artisan-pos-design/
│       └── SKILL.md             # Panduan estetika Sarah's Snacks & Majoo
├── tests/
│   └── run.php                  # Test suite otomatis (6 suite)
├── dashboard.php                # Beranda utama (menyesuaikan role pengguna)
├── index.php                    # Gerbang masuk (redirect otomatis)
├── login.php                    # Halaman masuk akun (cheat sheet demo tersedia)
├── logout.php                   # Pembersihan sesi & cookie
├── register.php                 # Pendaftaran akun pembeli mandiri
├── setup.php                    # Inisialisasi awal admin toko
├── .gitignore
└── README.md
```

---

## 4. Persyaratan Sistem & Instalasi Lokal

### Persyaratan:
- **PHP:** Versi 8.2 atau lebih baru.
- **Ekstensi PHP:** `pdo_mysql`, `mbstring`, `session`.
- **Database:** MySQL 5.7+ atau MariaDB 10.3+.
- **Web Server:** Apache (Laragon / XAMPP / bawaan PHP).
- **Peramban:** Chrome, Edge, Firefox, atau Safari.

### Langkah Instalasi:

1. **Jalankan Web Server & MySQL:**
   Buka Laragon atau XAMPP, pastikan modul **Apache** dan **MySQL** aktif.

2. **Impor Database:**
   - Buka phpMyAdmin di `http://localhost/phpmyadmin`.
   - Pilih tab **Import**.
   - Pilih file: `kelompok/kelompok_10/database/sisa_sense.sql`.
   - Klik **Go / Kirim**. Database `sisasense_db` beserta 8 tabel dan akun demo akan dibuat secara otomatis.

3. **Buka Aplikasi:**
   Akses melalui browser di alamat:
   ```text
   http://localhost/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_10/
   ```

4. **Koneksi Database Bawaan (`config/database.php`):**
   - Host: `127.0.0.1` | Port: `3306`
   - Database: `sisasense_db`
   - User: `root` | Password: *(sesuai konfigurasi Laragon/MySQL lokal)*

---

## 5. Panduan Deployment ke InfinityFree (Free Hosting)

InfinityFree memiliki batasan hak akses MySQL cPanel (nama database harus diawali `if0_xxxxxxx_...` dan **dilarang** menggunakan perintah `CREATE DATABASE` atau `USE ...` di phpMyAdmin). 

SisaSense menyediakan dua opsi yang 100% kompatibel dan siap pakai di InfinityFree:

### Opsi A: Menggunakan Web Seeder Interaktif (`seeder.php`) — *Paling Mudah & Otomatis*
1. Unggah seluruh file folder proyek ke direktori `htdocs/` di InfinityFree (via File Manager atau FileZilla FTP).
2. Di vPanel InfinityFree, masuk ke menu **MySQL Databases** dan buat 1 database baru (misalnya: `if0_12345678_sisasense`).
3. Konfigurasi kredensial database di `config/database.php` atau buat file `config/database.local.php` (lihat template `config/database.local.example.php`):
   ```php
   <?php
   return [
       'host'     => 'sql300.infinityfree.com', // MySQL Host Name dari vPanel
       'database' => 'if0_12345678_sisasense',  // Nama Database dari vPanel
       'username' => 'if0_12345678',            // Username Akun vPanel
       'password' => 'PASSWORD_VPANEL_ANDA',    // Password Akun vPanel
       'port'     => '3306',
   ];
   ```
4. Buka URL seeder di browser:
   ```text
   http://<domain-anda>.infinityfreeapp.com/seeder.php
   ```
5. Klik **Eksekusi / Refresh**. Seeder akan secara otomatis membuat ke-8 tabel dan mengisi 3 akun demo, 5 kategori, 24 produk foto real, dan 24 batch stok FEFO!

### Opsi B: Impor Manual via phpMyAdmin InfinityFree (`infinityfree_seeder.sql`)
1. Buka vPanel InfinityFree &gt; **MySQL Databases** &gt; klik tombol **phpMyAdmin** pada database Anda.
2. Klik tab **Import**.
3. Pilih file `kelompok/kelompok_10/database/infinityfree_seeder.sql` (file ini **bebas** dari `CREATE DATABASE` dan `USE` sehingga 100% lolos tanpa error *Access Denied*).
4. Klik tombol **Go / Kirim**.

### Akun Demo Setelah Seeding (Password: `123`):
- **Admin:** `admin` / `123` *(Akses Penuh: Dashboard, Master Produk, Laporan)*
- **Kasir:** `kasir` / `123` *(Akses Kasir POS, Pembayaran Tunai/QRIS/Transfer/EDC, Cetak Struk)*
- **Pembeli:** `pembeli` / `123` *(Akses Katalog Produk & Keranjang Belanja)*

---

## 6. Menjalankan Pengujian Otomatis

Aplikasi dilengkapi test runner bawaan untuk memverifikasi logika bisnis, formula akuntansi, sanitasi keamanan XSS/CSRF, dan otorisasi 3 role:

```bash
php kelompok/kelompok_10/tests/run.php
```

Pemeriksaan sintaks seluruh file PHP (linter):

```powershell
Get-ChildItem kelompok\kelompok_10 -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

---

## 6. Standar Keamanan & Kepatuhan Aturan Praktikum

- **Anti-XSS:** Seluruh pencetakan variabel ke HTML dilewatkan fungsi `e()` (`htmlspecialchars` dengan flag `ENT_QUOTES | ENT_SUBSTITUTE`).
- **Anti-SQL Injection:** 100% kueri database menggunakan PDO Prepared Statements dengan parameter binding.
- **Proteksi CSRF:** Seluruh form bermetode POST diverifikasi dengan `csrf_token()` dan `require_valid_csrf()`.
- **Integritas Transaksi:** Checkout kasir menggunakan `beginTransaction()`, `FOR UPDATE` locking, dan `commit()` / `rollBack()`.
- **Isolasi Monorepo:** 100% kode berada di dalam folder `kelompok/kelompok_10/` tanpa mengubah file root repository.
- **Tanpa Framework:** Murni HTML5, CSS3, JavaScript Vanilla, PHP Native 8.2, dan MySQL.
