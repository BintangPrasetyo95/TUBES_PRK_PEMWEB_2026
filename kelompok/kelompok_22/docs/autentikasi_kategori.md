#DOKUMENTASI TEKNIS MODUL AUTENTIKASI ADMIN & CRUD KATEGORI
Proyek Sistem Informasi: "KlikObat" (Katalog Farmasi & Edukasi)
Kelompok : Kelompok 22
Anggota Pengembang (Jobdesk): Anggota 3 (Autentikasi & Manajemen Kategori)  

##1. PENDAHULUAN
Modul Backend Admin dan Manajemen Kategori merupakan fondasi utama dalam sistem informasi "KlikObat". Modul ini dirancang untuk memastikan bahwa seluruh pengelolaan data etalase kategori obat hanya dapat dilakukan oleh pihak yang memiliki hak akses sah (admin terverifikasi). Dokumen ini merinci struktur file, logika keamanan, implementasi basis data, hingga penanganan validasi bisnis yang telah dibangun.

##2. STRUKTUR DIREKTORI FILE MODUL
Seluruh file yang dikembangkan berlokasi di dalam folder spesifik kelompok (`kelompok/kelompok_22/`) dengan rincian struktur sebagai berikut:

```text
kelompok_22/
│
├── admin/
│   ├── auth.php              # Gerbang pengaman pengecekan sesi (Session Guard)
│   ├── login.php             # Antarmuka form login & pemrosesan autentikasi
│   ├── logout.php            # Skrip penghancuran sesi (Session Destruction)
│   │
│   └── kategori/             # Direktori Manajemen Kategori Obat
│       ├── index.php         # Tabel utama daftar data kategori (Read)
│       ├── tambah.php        # Form & proses penambahan kategori baru (Create)
│       ├── ubah.php          # Form & proses pengeditan data kategori (Update)
│       └── hapus.php         # Eksekusi penghapusan data & validasi relasi (Delete)
│
└── config/
    └── koneksi.php           # File koneksi pusat database menggunakan MySQLi

##3. DETAIL IMPLEMENTASI KODE & KEAMANAN
A. Modul Autentikasi Admin (admin/)
Modul ini mengamankan area administratif dari akses ilegal.

    admin/auth.php (Session Security Guard)
        Fungsi: Berfungsi sebagai filter global yang wajib disisipkan di setiap halaman backend. Skrip ini mendeteksi apakah variabel global $_SESSION['id_admin'] sudah terdaftar atau belum.
        Mekanisme: Jika sesi tidak ditemukan, sistem otomatis memicu perintah redirect (header("Location: ../login.php")) untuk mengembalikan pengguna ke halaman masuk, sehingga halaman internal tidak dapat dibobol via URL langsung.

    admin/login.php (Proses Validasi & Keamanan Enkripsi)
        Fungsi: Menyediakan antarmuka form masuk dan memvalidasi kredensial admin.
        Keamanan (Prepared Statement): Menggunakan teknik prepared statement ($conn->prepare) dengan binding parameter (bind_param) untuk menutup celah keamanan dari serangan SQL Injection.
        Verifikasi Hash: Menggunakan fungsi bawaan PHP password_verify($password, $row['password']) untuk mencocokkan kata sandi teks mentah (plain text) input pengguna dengan string hash aman yang tersimpan di dalam database.

    admin/logout.php (Pengakhiran Sesi)
        Fungsi: Membersihkan seluruh memori sesi aktif menggunakan fungsi session_unset() dan menghancurkan data sesi via session_destroy(), lalu mengalihkan halaman kembali ke form login.

B. Modul CRUD Kategori Obat (admin/kategori/)
Modul ini menangani manipulasi data kategori obat secara interaktif.

    index.php (Tabel Tampilan Data)
        Melakukan query SQL SELECT * FROM kategori ORDER BY id_kategori DESC untuk menarik seluruh data kategori terbaru.
        Menampilkan data ke dalam bentuk tabel HTML responsif yang dilengkapi dengan tombol navigasi aksi (Tambah, Ubah, Hapus).

    tambah.php (Form Penambahan Data)
        Menyediakan form masukan berbasis metode POST.
        Menerapkan validasi pencegahan input kosong (!empty()) sebelum perintah SQL INSERT INTO kategori dieksekusi ke database.

    ubah.php (Form Pembaruan Data)
        Mengambil parameter ID dari URL menggunakan $_GET['id'] untuk melakukan fetching data lama dari database.
        Menampilkan data lama ke dalam form input, lalu mengeksekusi query UPDATE kategori SET ... WHERE id_kategori = ? setelah tombol simpan ditekan.

    hapus.php (Eksekusi Hapus & Validasi Integritas Relasional)
        Aturan Bisnis (Business Logic): Sesuai standar spesifikasi sistem, kategori obat yang masih terikat atau digunakan oleh data obat di tabel obat dilarang keras untuk dihapus guna mencegah rusaknya integritas referensial data (foreign key constraint).
        Mekanisme Pengecekan: Sebelum perintah DELETE dijalankan, sistem menjalankan query pengecekan relasi:
        $cek =$conn->prepare("SELECT COUNT(*) as total FROM obat WHERE id_kategori = ?");
        Jika nilai total > 0, eksekusi dibatalkan dan sistem memunculkan jendela peringatan (alert('Gagal Hapus! Kategori masih digunakan oleh data obat.')). Jika 0 (aman), data kategori berhasil dihapus.

4. PENANGANAN BUG & PENGUJIAN SISTEM (DEBUGGING)
    Kasus Perbaikan Bug Path Redirect: Sebelumnya, skrip redirect pengaman pada auth.php sempat mengalami error Not Found (404) ketika diakses dari dalam subfolder kategori karena salah membaca jalur folder relatif. Masalah ini berhasil diselesaikan dengan mengubah struktur jalur pengalihan menjadi mundur satu tingkat (../login.php).
    Hasil Pengujian Lokal: Seluruh fitur telah diuji secara menyeluruh menggunakan Local Server Laragon (PHP versi terbaru & MySQL). Pengujian fungsionalitas login, pembatasan akses URL ilegal, operasi CRUD, hingga penolakan hapus data berelasi berjalan lancar tanpa ada error runtime.
