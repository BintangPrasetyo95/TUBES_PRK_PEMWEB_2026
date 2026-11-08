# 📍 SISTEM INFORMASI PRESENSI "ATTENDARA" (KELOMPOK 18)

Selamat datang di repositori internal pengerjaan Tugas Besar Praktikum Pemrograman Web 2026. Dokumen ini adalah panduan kerja, pembagian tugas, dan standardisasi kode untuk seluruh anggota Kelompok 18.

---

## 🛠️ TEKNOLOGI UTAMA
*   **Backend:** PHP Native (PDO Connection)
*   **Database:** MySQL / MariaDB (Laragon / XAMPP)
*   **Frontend:** Tailwind CSS v4 via CDN Jalur Cepat
*   **API Perangkat:** JavaScript Geolocation API

---

## 👥 PEMBAGIAN TUGAS (ROLE & RESPONSIBILITY)

Untuk menjaga efisiensi pengerjaan 5 Modul Utama, berikut adalah pembagian peran resmi tim Kelompok 18:

### 👨‍💻 ANGGOTA 1: Database Administrator & Backend Core
*   **Tanggung Jawab Utama:** 
    *   Merancang skema, relasi tabel, dan mengelola file SQL (`database/db_attendara.sql`).
    *   Membuat file koneksi basis data menggunakan standar keamanan PDO PHP (`config/database.php`).
    *   Menyusun fungsi matematika inti untuk Rumus Haversine (hitung jarak meter) dan fungsi kedaluwarsa waktu di file *helper*.

### 🎨 Kadek: UI/UX Designer & Frontend Engineer
*   **Tanggung Jawab Utama:**
    *   Menyusun kerangka komponen visual global web seperti `header.php`, `footer.php`, dan `sidebar.php`.
    *   Mengintegrasikan link CDN Tailwind v4 secara konsisten di seluruh halaman.
    *   Melakukan *slicing* halaman agar tampilan responsif di perangkat mobile maupun laptop.

### 🔐 ANGGOTA 3: Auth & Admin Module Specialist (Modul 1)
*   **Tanggung Jawab Utama:**
    *   Membangun sistem autentikasi masuk/keluar (*Login/Logout*) berbasis hak akses multi-role (Admin, Dosen, Siswa).
    *   Mengamankan variabel global sesi (`session_start()`).
    *   Membuat halaman manajemen data master (CRUD data siswa, dosen, kelas, serta penentuan koordinat kelas) khusus hak akses Admin.

### 🔑 ANGGOTA 4: Generator Token Dinamis Specialist (Modul 2)
*   **Tanggung Jawab Utama:**
    *   Membangun modul dashboard khusus dosen pengajar.
    *   Membuat logika tombol pengacak token 6 digit acak yang terikat dengan waktu pembuatan (*timestamp* saat itu).
    *   Membuat mekanisme penampilan token secara real-time di layar proyektor kelas.

### 📍 ANGGOTA 5: Geolocation & Data Visualization Specialist (Modul 3 & 4)
*   **Tanggung Jawab Utama:**
    *   Mengintegrasikan skrip pengambilan koordinat GPS perangkat siswa via JavaScript Geolocation API.
    *   Mengirimkan paket data koordinat siswa + kode token lewat AJAX ke backend PHP pemroses.
    *   Membangun halaman rekapitulasi kehadiran (grafik Chart.js) serta fitur ekspor laporan resmi (PDF/Excel).

---

## 🛡️ TUGAS KHUSUS KETUA KELOMPOK (TECH LEAD)
Sebagai Ketua Kelompok, tugas utama lu adalah menjaga keteraturan sistem:
1.  **Gatekeeper Branch Staging:** Memastikan tidak ada anggota yang merusak struktur kode di branch `staging`.
2.  **Code Review:** Memeriksa kualitas kode, kebersihan penulisan, keamanan password, dan potensi konflik (tabrakan file) sebelum melakukan proses penggabungan kode (*Merge*).
3.  **Final Pull Request:** Melakukan koordinasi akhir untuk memicu pengiriman proyek dari branch `staging` kelompok ke branch `main` repositori laboratorium utama.

---

## 🔀 ALUR KERJA GIT KELOMPOK 18 (SOP TIM)

Wajib dipatuhi oleh seluruh anggota kelompok demi menghindari konflik kode eror massal:

1.  **Isolasi Folder Kerja:** Semua perubahan, pembuatan file baru, atau penulisan kode **HANYA BOLEH** dilakukan di dalam ruang isolasi kita: `kelompok/kelompok_18/`. Jangan sekali-kali mengedit file di luar folder tersebut.
2.  **Unduh Pembaruan Sebelum Bekerja:** Sebelum kalian mulai menulis kode baru setiap harinya, biasakan jalankan perintah ini di terminal untuk menyamakan kode dengan server terbaru:
    ```bash
    git checkout staging
    git pull origin staging
    ```
3.  **Kirim Perubahan Kode:** Jika fitur/modul tugas kalian sudah selesai diketik dan dites lancar di komputer masing-masing, jalankan rangkaian perintah ini:
    ```bash
    git add .
    git commit -m "feat(kelompok_18): penjelasan singkat fitur yang lu selesaikan"
    git push origin staging
    ```
4.  **Konfirmasi Grup:** Beritahu Ketua Kelompok di grup WhatsApp setelah kalian melakukan *push* agar ketua bisa memeriksa perubahan kode kalian sebelum diintegrasikan secara penuh.