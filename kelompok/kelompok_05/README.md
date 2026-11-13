# CEKHAK — Cek Hak dan Ketentuan Hukum

**Tema Tugas Besar: Tema 04 — Fakultas Hukum**

CEKHAK membantu pengguna memeriksa kelengkapan administratif dokumen hukum. Pengguna memilih jenis dokumen, mengisi persyaratan, dan mengunggah berkas pendukung. Sistem menampilkan hasil pemeriksaan yang dapat ditinjau reviewer dan dicetak sebagai laporan.

CEKHAK hanya memeriksa kelengkapan administratif. Hasilnya bukan pendapat hukum final dan tidak menjamin dokumen sah atau diterima oleh instansi tertentu. Pastikan persyaratan diperiksa terhadap sumber resmi.

## Fitur Utama Berdasarkan Peran

### Tamu (Publik)

- Melihat beranda aplikasi.
- Mencari persyaratan melalui Pustaka Persyaratan dan melihat detail jenis dokumen serta butir persyaratannya.

### Pengguna

- Mendaftar akun dan masuk ke aplikasi.
- Membuat pemeriksaan untuk jenis dokumen yang tersedia.
- Mengisi formulir dinamis sesuai persyaratan dokumen dan mengunggah berkas pendukung berukuran maksimal **5 MB** per berkas.
- Menjalankan analisis otomatis untuk melihat kelengkapan dan format isian.
- Melihat hasil pemeriksaan serta membuka dan mencetak laporan.
- Memantau daftar dan status pemeriksaan melalui riwayat.
- Menerima dan membaca catatan reviewer melalui notifikasi pemeriksaan.

### Reviewer

- Melihat pemeriksaan berstatus `siap_ditinjau` atau `sedang_ditinjau` yang tersedia untuk ditangani.
- Memeriksa jawaban pemohon dan mengunduh berkas pendukung.
- Menambahkan catatan untuk pemohon.
- Menyimpan hasil akhir dengan status `selesai` atau `belum_lengkap`.

### Admin

- Mengelola jenis dokumen (`document_types`), termasuk informasi dan status aktifnya.
- Mengelola butir persyaratan (`requirements`), termasuk status verifikasi persyaratan.
- Mengatur role akun pengguna (`users`).
- Memantau log aktivitas sistem (`audit_logs`).

## Teknologi

Teknologi yang digunakan:

- **PHP 8.2 atau lebih baru** untuk sisi server.
- **MySQL atau MariaDB** sebagai basis data; akses melalui **PDO** dengan prepared statements.
- **HTML5** dan **CSS3** untuk struktur serta tampilan.
- **Vanilla JavaScript** dan Fetch API untuk interaksi sisi klien.

## Struktur Direktori

```text
kelompok_05/
├── index.php
├── README.md
├── assets/
│   ├── css/
│   │   └── theme.css
│   ├── js/
│   │   ├── api.js
│   │   ├── app.js
│   │   ├── ui.js
│   │   ├── validate.js
│   │   ├── pemeriksaan/
│   │   │   ├── pemeriksaan.css
│   │   │   └── pemeriksaan.js
│   │   └── pustaka/
│   │       └── search.js
├── config/
│   ├── config.example.php
│   └── config.local.php            
├── database/
│   ├── schema.sql
│   └── seed.sql
├── docs/
│   ├── BRIEF.md
│   ├── CONTRACT (1).md
│   ├── PR_CHECKLIST.md
│   ├── README.md
│   ├── RULES_AI (1).md
│   └── TUGAS.md
├── includes/
│   ├── api.php
│   ├── audit.php
│   ├── auth.php
│   ├── bootstrap.php
│   ├── csrf.php
│   ├── db.php
│   ├── footer.php
│   ├── header.php
│   ├── helpers.php
│   ├── navbar.php
│   └── validasi.php
├── modules/
│   ├── admin/       # pengelolaan jenis dokumen, persyaratan, dan akun
│   ├── analisis/    # mesin analisis kelengkapan
│   ├── audit/       # log aktivitas
│   ├── auth/        # registrasi, login, logout, dan profil
│   ├── beranda/     # dashboard
│   ├── contoh/      # contoh dan fitur login pengembangan
│   ├── hasil/       # hasil analisis
│   ├── laporan/     # laporan pemeriksaan dan cetak
│   ├── pemeriksaan/ # pembuatan, formulir, berkas, dan notifikasi
│   ├── pustaka/     # pustaka persyaratan dan API pencarian
│   ├── reviewer/    # peninjauan pemeriksaan
│   └── riwayat/     # riwayat pemeriksaan
├── tools/
│   ├── cek_aturan.php
│   └── test_e2e.php
└── uploads/         # penyimpanan berkas pemeriksaan
```

## Instalasi dan Konfigurasi Lokal

### Prasyarat

- XAMPP dengan **PHP 8.2+** dan MySQL/MariaDB, atau lingkungan lokal lain dengan versi PHP dan basis data tersebut.
- Ekstensi PHP PDO untuk MySQL.

### Langkah instalasi

1. Tempatkan folder proyek pada document root server lokal. Contoh URL di bawah mengasumsikan proyek tersedia pada path `Tubes/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_05/`.
2. Salin `config/config.example.php` menjadi `config/config.local.php`.
3. Buka `config/config.local.php` dan sesuaikan parameter koneksi basis data dengan lingkungan lokal. Untuk konfigurasi bawaan XAMPP, umumnya host `127.0.0.1`, port `3306`, nama basis data `cekhak`, pengguna `root`, dan kata sandi kosong.
4. Pastikan konfigurasi berisi `'dev_mode' => false` saat menjalankan demo. Jangan membagikan atau mengunggah `config.local.php`; file tersebut berisi konfigurasi khusus lingkungan lokal.
5. Jalankan Apache dan MySQL/MariaDB dari XAMPP.
6. Impor `database/schema.sql` ke MySQL/MariaDB terlebih dahulu, kemudian impor `database/seed.sql`. Kedua berkas menggunakan basis data `cekhak`; skema akan membuat basis data itu bila belum ada.

   Impor dapat dilakukan melalui tab **Import** di phpMyAdmin atau melalui klien MySQL:

   ```sh
   mysql -u root -p < database/schema.sql
   mysql -u root -p cekhak < database/seed.sql
   ```

   Sesuaikan opsi `-p` dengan konfigurasi server (hapus `-p` bila akun lokal tidak memakai kata sandi). **Perhatian:** `schema.sql` menghapus tabel CEKHAK yang ada sebelum membuat ulang tabel. Jangan jalankan ulang pada basis data berisi data yang ingin dipertahankan.

7. Buka aplikasi di:

   `http://localhost/Tubes/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_05/`

## Panduan Penggunaan

### Pustaka Persyaratan

Pustaka dapat dibuka tanpa login. Gunakan kolom pencarian atau filter jenis dokumen, lalu pilih **Lihat Detail** untuk membaca daftar persyaratan. Informasi seed yang tersedia masih berupa data contoh dan belum diverifikasi sebagai ketentuan hukum resmi.

### Pengguna

1. Pilih **Daftar**, isi nama, email, dan kata sandi (minimal 8 karakter), lalu masuk ke akun.
2. Pilih **Buat Pemeriksaan**, masukkan judul, dan pilih jenis dokumen. Aplikasi membuat pemeriksaan dalam status draft.
3. Isi butir persyaratan. Unggah berkas pada butir bertipe file; format yang diterima PDF, JPG, PNG, WebP, DOC, atau DOCX, dengan ukuran maksimal 5 MB per berkas.
4. Pilih **Simpan draft** jika ingin melanjutkan nanti. Untuk mengirim pemeriksaan, pilih **Kirim untuk ditinjau**. Isian wajib yang belum lengkap akan membuat status pemeriksaan `belum_lengkap`.
5. Untuk melihat analisis dan persentase kelengkapan, buka **Riwayat**, pilih **Hasil**, lalu tekan **Periksa Ulang**. Jika masih ada kekurangan, pilih **Lanjutkan** dari Riwayat untuk memperbaiki isian dan berkas. Pemeriksaan yang sudah siap ditinjau tidak dapat diedit.
6. Buka **Laporan** dari Riwayat untuk melihat rincian dan memilih **Cetak laporan**.
7. Catatan dan perubahan status dari reviewer dapat dilihat di halaman notifikasi: `modules/pemeriksaan/notifikasi.php`. Saat halaman dibuka, notifikasi yang belum dibaca ditandai sudah dibaca.

### Reviewer

1. Masuk dengan akun reviewer dan buka **Dashboard Reviewer**.
2. Periksa pemeriksaan berstatus `siap_ditinjau` atau `sedang_ditinjau`. Tinjau jawaban dan unduh berkas pemohon bila diperlukan.
3. Pilih hasil `selesai` atau `belum_lengkap`, isi catatan untuk pemohon bila diperlukan, lalu simpan. Hasil dan catatan akan muncul pada notifikasi pengguna.

### Admin

Gunakan menu admin setelah masuk dengan akun admin:

- **Kelola Dokumen** untuk menambah, mengubah, atau menonaktifkan jenis dokumen.
- **Persyaratan** untuk mengelola butir persyaratan, sumber, urutan, dan status verifikasinya.
- **Akun** untuk mengubah role akun.
- **Log Audit** untuk melihat aktivitas yang tercatat.

## Akun Demo

Akun berikut dimasukkan oleh `database/seed.sql`. Semuanya memakai kata sandi **`password`**; gunakan hanya pada lingkungan lokal.

| Peran | Nama pada seed | Email | Kata sandi |
|---|---|---|---|
| Admin | Admin CEKHAK | `admin@cekhak.test` | `password` |
| Reviewer | Reviewer Contoh | `reviewer@cekhak.test` | `password` |
| Pengguna | Pengguna Contoh | `pengguna@cekhak.test` | `password` |
| Pengguna | Pengguna Kedua | `pengguna2@cekhak.test` | `password` |




