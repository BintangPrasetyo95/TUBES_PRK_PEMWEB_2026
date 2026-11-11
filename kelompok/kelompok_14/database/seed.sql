-- ============================================================================
-- DynaQuiz - Database Seed (Data Awal / Seeder)
-- Kelompok 14 - Tugas Besar Praktikum Pemrograman Web 2026
-- PIC: Wulan (Sesi 1 - Database Seed / seed.sql)
--
-- CATATAN UNTUK TEMAN-TEMAN KELOMPOK 14:
-- 1. Jalankan dulu file schema.sql sebelum menjalankan file ini.
-- 2. File ini berisi data awal: akun user (admin, guru, siswa), topik mapel,
--    tingkat kelas, kuis contoh (terbit, draft, menunggu, privat), soal-soal,
--    opsi jawaban, serta contoh riwayat pengerjaan kuis.
-- 3. Password default untuk SEMUA akun di bawah ini adalah: password123
-- ============================================================================

SET NAMES utf8mb4;
SET time_zone = '+07:00';
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- Bersihkan data lama jika ingin reset ulang database
-- ----------------------------------------------------------------------------
TRUNCATE TABLE `jawaban_percobaan`;
TRUNCATE TABLE `percobaan_kuis`;
TRUNCATE TABLE `opsi_jawaban`;
TRUNCATE TABLE `soal`;
TRUNCATE TABLE `log_status_kuis`;
TRUNCATE TABLE `log_login`;
TRUNCATE TABLE `kuis`;
TRUNCATE TABLE `tingkat`;
TRUNCATE TABLE `topik`;
TRUNCATE TABLE `user`;

-- ----------------------------------------------------------------------------
-- 1. Data Pengguna (user)
-- Catatan:
-- Password semua akun di-hash dengan password_hash('password123', PASSWORD_DEFAULT)
-- Hash: $2y$10$VSrxwue21OAXt/S57.j7oe5hx1JQ.3Fq3LVCn5QIfkHJiQ17zlncC
-- ----------------------------------------------------------------------------

-- Akun 1: Admin
INSERT INTO `user` (`id`, `nama`, `email`, `password`, `peran`, `dibuat_pada`) VALUES
(1, 'Admin DynaQuiz', 'admin@dynaquiz.com', '$2y$10$VSrxwue21OAXt/S57.j7oe5hx1JQ.3Fq3LVCn5QIfkHJiQ17zlncC', 'admin', NOW());

-- Akun 2 & 3: Pengajar / Guru
INSERT INTO `user` (`id`, `nama`, `email`, `password`, `peran`, `dibuat_pada`) VALUES
(2, 'Budi Santoso, S.Kom.', 'budi@guru.com', '$2y$10$VSrxwue21OAXt/S57.j7oe5hx1JQ.3Fq3LVCn5QIfkHJiQ17zlncC', 'pengajar', NOW()),
(3, 'Siti Aminah, M.Pd.', 'siti@guru.com', '$2y$10$VSrxwue21OAXt/S57.j7oe5hx1JQ.3Fq3LVCn5QIfkHJiQ17zlncC', 'pengajar', NOW());

-- Akun 4, 5, 6: Pelajar / Siswa
INSERT INTO `user` (`id`, `nama`, `email`, `password`, `peran`, `dibuat_pada`) VALUES
(4, 'Ani Wijaya', 'ani@siswa.com', '$2y$10$VSrxwue21OAXt/S57.j7oe5hx1JQ.3Fq3LVCn5QIfkHJiQ17zlncC', 'pelajar', NOW()),
(5, 'Bima Pratama', 'bima@siswa.com', '$2y$10$VSrxwue21OAXt/S57.j7oe5hx1JQ.3Fq3LVCn5QIfkHJiQ17zlncC', 'pelajar', NOW()),
(6, 'Citra Lestari', 'citra@siswa.com', '$2y$10$VSrxwue21OAXt/S57.j7oe5hx1JQ.3Fq3LVCn5QIfkHJiQ17zlncC', 'pelajar', NOW());


-- ----------------------------------------------------------------------------
-- 2. Data Topik / Mata Pelajaran (topik)
-- ----------------------------------------------------------------------------
INSERT INTO `topik` (`id`, `nama`, `deskripsi`, `dibuat_pada`) VALUES
(1, 'Matematika', 'Kumpulan kuis logika matematika, aljabar, geometri, dan hitung dasar.', NOW()),
(2, 'Ilmu Pengetahuan Alam', 'Kuis seputar biologi, sains alam, fisika dasar, dan tata surya.', NOW()),
(3, 'Bahasa Indonesia', 'Kuis tata bahasa, ejaan EYD, pemahaman bacaan, dan kosa kata.', NOW()),
(4, 'Bahasa Inggris', 'Latihan grammar, reading comprehension, vocabulary, dan tenses.', NOW()),
(5, 'Pemrograman Web', 'Konsep dasar pembuatan web: HTML, CSS, JavaScript, PHP, dan database MySQL.', NOW());


-- ----------------------------------------------------------------------------
-- 3. Data Tingkat Pendidikan (tingkat)
-- Memiliki kolom urutan dari yang paling rendah sampai umum
-- ----------------------------------------------------------------------------
INSERT INTO `tingkat` (`id`, `nama`, `urutan`, `dibuat_pada`) VALUES
(1, 'SD / Sederajat', 1, NOW()),
(2, 'SMP / Sederajat', 2, NOW()),
(3, 'SMA / Sederajat', 3, NOW()),
(4, 'Perguruan Tinggi', 4, NOW()),
(5, 'Umum', 5, NOW());


-- ----------------------------------------------------------------------------
-- 4. Data Kuis (kuis)
-- Berisi berbagai jenis status untuk keperluan testing fitur kelompok:
-- - Kuis 1: Terbit & Publik (Pemrograman Web, dibuat Pak Budi)
-- - Kuis 2: Terbit & Publik (Matematika SMP, dibuat Bu Siti)
-- - Kuis 3: Terbit & Privat dengan kode kuis (PHP Native, dibuat Pak Budi)
-- - Kuis 4: Menunggu Persetujuan Admin (IPA SD, dibuat Bu Siti)
-- - Kuis 5: Draft Pengajar (Bahasa Inggris SMP, dibuat Pak Budi)
-- ----------------------------------------------------------------------------
INSERT INTO `kuis` (`id`, `pengajar_id`, `topik_id`, `tingkat_id`, `judul`, `deskripsi`, `durasi_menit`, `akses`, `kode`, `status`, `diterbitkan_pada`, `dibuat_pada`) VALUES
(1, 2, 5, 3, 'Dasar-Dasar HTML & Pemrograman Web', 'Uji pemahaman dasar seputar tag HTML, elemen web, dan cara kerja website modern.', 15, 'publik', NULL, 'terbit', '2026-11-01 09:00:00', '2026-11-01 08:00:00'),
(2, 3, 1, 2, 'Matematika SMP: Operasi Hitung & Aljabar', 'Latihan soal berhitung cepat, operasi bilangan bulat, pecahan, dan persamaan aljabar.', 10, 'publik', NULL, 'terbit', '2026-11-02 10:00:00', '2026-11-02 09:30:00'),
(3, 2, 5, 3, 'Kuis Privat Logika PHP (Kelas Khusus)', 'Kuis privat untuk menguji dasar percabangan, fungsi, dan variabel pada PHP native.', 20, 'privat', 'WEB2026', 'terbit', '2026-11-03 13:00:00', '2026-11-03 11:00:00'),
(4, 3, 2, 1, 'Mengenal Tata Surya dan Planet Kita', 'Kuis interaktif sains seputar planet, matahari, dan benda langit untuk siswa SD.', 15, 'publik', NULL, 'menunggu', NULL, '2026-11-04 14:00:00'),
(5, 2, 4, 2, 'English Grammar: Simple Present & Past Tense', 'Latihan tata bahasa Inggris seputar waktu dan penggunaan kata kerja bentuk 1 & 2.', 15, 'publik', NULL, 'draft', NULL, '2026-11-05 16:00:00');


-- ----------------------------------------------------------------------------
-- 5. Data Soal (soal)
-- Menyimpan pertanyaan, pembahasan, dan poin untuk tiap kuis
-- ----------------------------------------------------------------------------

-- Soal untuk Kuis 1: Dasar-Dasar HTML (5 Soal, masing-masing 20 poin = Total 100)
INSERT INTO `soal` (`id`, `kuis_id`, `urutan`, `pertanyaan`, `pembahasan`, `poin`) VALUES
(1, 1, 1, 'Apakah kepanjangan dari HTML?', 'HTML merupakan singkatan dari HyperText Markup Language, yaitu bahasa markup standar untuk membuat halaman web.', 20),
(2, 1, 2, 'Tag HTML manakah yang digunakan untuk membuat judul utama atau heading dengan ukuran paling besar?', 'Tag <h1> adalah level heading tertinggi (terbesar) dalam hierarki dokumen HTML dari h1 sampai h6.', 20),
(3, 1, 3, 'Atribut apa yang wajib ada pada tag <a> untuk menentukan alamat tujuan tautan (link)?', 'Atribut href (Hypertext REFerence) digunakan untuk menentukan URL tujuan pada elemen hyperlink <a>.', 20),
(4, 1, 4, 'Tag HTML yang benar untuk menampilkan gambar pada halaman web adalah...', 'Tag <img> digunakan untuk menyisipkan gambar dengan atribut src yang menentukan sumber file gambar.', 20),
(5, 1, 5, 'Elemen HTML apa yang digunakan untuk membuat daftar berurutan menggunakan angka (1, 2, 3)?', 'Tag <ol> adalah singkatan dari Ordered List, digunakan untuk daftar item bernomor.', 20);

-- Soal untuk Kuis 2: Matematika SMP (5 Soal, masing-masing 20 poin = Total 100)
INSERT INTO `soal` (`id`, `kuis_id`, `urutan`, `pertanyaan`, `pembahasan`, `poin`) VALUES
(6, 2, 1, 'Berapakah hasil dari perhitungan: 15 + 25 : 5 ?', 'Dalam aturan matematika, pembagian dikerjakan terlebih dahulu: 25 : 5 = 5. Kemudian 15 + 5 = 20.', 20),
(7, 2, 2, 'Jika 2x + 6 = 16, maka nilai x adalah...', 'Pindahkan konstanta: 2x = 16 - 6 => 2x = 10 => x = 10 : 2 = 5.', 20),
(8, 2, 3, 'Sebuah persegi memiliki panjang sisi 8 cm. Berapakah luas persegi tersebut?', 'Rumus luas persegi adalah sisi x sisi. Jadi 8 cm x 8 cm = 64 cm².', 20),
(9, 2, 4, 'Bentuk pecahan paling sederhana dari 12/16 adalah...', 'Bagi pembilang dan penyebut dengan FPB-nya (4): 12:4 = 3 dan 16:4 = 4. Hasilnya 3/4.', 20),
(10, 2, 5, 'Berapakah hasil dari (-7) x 8 + 10 ?', 'Operasi perkalian dulu: (-7) x 8 = -56. Lalu -56 + 10 = -46.', 20);

-- Soal untuk Kuis 3: Kuis Privat Logika PHP (3 Soal, total 100 poin)
INSERT INTO `soal` (`id`, `kuis_id`, `urutan`, `pertanyaan`, `pembahasan`, `poin`) VALUES
(11, 3, 1, 'Simbol apa yang wajib digunakan untuk mengawali nama variabel dalam bahasa PHP?', 'Di PHP, setiap penulisan variabel selalu diawali dengan simbol tanda dolar ($), misalnya $nama atau $total.', 30),
(12, 3, 2, 'Perintah bawaan PHP manakah yang paling sering digunakan untuk menampilkan teks ke layar browser?', 'Perintah echo digunakan untuk mencetak satu atau lebih string ke layar secara langsung.', 35),
(13, 3, 3, 'Fungsi bawaan PHP yang direkomendasikan untuk mengenkripsi/meng-hash password pengguna secara aman adalah...', 'Fungsi password_hash() adalah fungsi standar PHP modern untuk hashing password dengan algoritma aman seperti BCRYPT.', 35);

-- Soal untuk Kuis 4: Mengenal Tata Surya (Menunggu persetujuan, 3 Soal)
INSERT INTO `soal` (`id`, `kuis_id`, `urutan`, `pertanyaan`, `pembahasan`, `poin`) VALUES
(14, 4, 1, 'Planet apakah yang letaknya paling dekat dengan matahari?', 'Merkurius adalah planet yang posisinya paling dekat dengan matahari dalam sistem tata surya kita.', 30),
(15, 4, 2, 'Planet yang sering dijuluki sebagai "Planet Merah" adalah...', 'Mars sering disebut Planet Merah karena kandungan besi oksida di permukaannya membuatnya tampak kemerahan.', 35),
(16, 4, 3, 'Benda langit yang menjadi pusat peredaran seluruh planet di tata surya adalah...', 'Matahari adalah bintang induk yang massanya sangat besar sehingga menjadi pusat gravitasi tata surya.', 35);

-- Soal untuk Kuis 5: English Grammar (Draft, 2 Soal)
INSERT INTO `soal` (`id`, `kuis_id`, `urutan`, `pertanyaan`, `pembahasan`, `poin`) VALUES
(17, 5, 1, 'She ___ to school every morning by bicycle.', 'Untuk subjek orang ketiga tunggal (She/He/It) pada Simple Present Tense, kata kerja ditambah akhiran -s/-es, yaitu "goes".', 50),
(18, 5, 2, 'They ___ football in the field yesterday afternoon.', 'Kata keterangan "yesterday" menandakan Simple Past Tense (lampau), sehingga menggunakan kata kerja bentuk kedua (V2): "played".', 50);


-- ----------------------------------------------------------------------------
-- 6. Data Opsi Jawaban (opsi_jawaban)
-- Setiap soal memiliki 4 pilihan (A, B, C, D).
-- Kolom `benar` bernilai 1 jika jawaban benar, dan 0 jika jawaban salah.
-- Tepat satu jawaban benar per soal!
-- ----------------------------------------------------------------------------

-- Opsi Soal 1: Kepanjangan HTML (Kunci: A)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(1, 1, 'A', 'HyperText Markup Language', 1),
(2, 1, 'B', 'HighText Machine Language', 0),
(3, 1, 'C', 'Hyperlinks Text Multi Language', 0),
(4, 1, 'D', 'Home Tool Markup Language', 0);

-- Opsi Soal 2: Heading terbesar (Kunci: B)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(5, 2, 'A', '<head>', 0),
(6, 2, 'B', '<h1>', 1),
(7, 2, 'C', '<h6>', 0),
(8, 2, 'D', '<header>', 0);

-- Opsi Soal 3: Atribut link (Kunci: C)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(9, 3, 'A', 'src', 0),
(10, 3, 'B', 'link', 0),
(11, 3, 'C', 'href', 1),
(12, 3, 'D', 'target', 0);

-- Opsi Soal 4: Tag gambar (Kunci: A)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(13, 4, 'A', '<img>', 1),
(14, 4, 'B', '<image>', 0),
(15, 4, 'C', '<picture>', 0),
(16, 4, 'D', '<pic>', 0);

-- Opsi Soal 5: Daftar berurutan angka (Kunci: D)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(17, 5, 'A', '<ul>', 0),
(18, 5, 'B', '<li>', 0),
(19, 5, 'C', '<dl>', 0),
(20, 5, 'D', '<ol>', 1);

-- Opsi Soal 6: 15 + 25 : 5 (Kunci: C)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(21, 6, 'A', '8', 0),
(22, 6, 'B', '15', 0),
(23, 6, 'C', '20', 1),
(24, 6, 'D', '25', 0);

-- Opsi Soal 7: 2x + 6 = 16 (Kunci: B)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(25, 7, 'A', '4', 0),
(26, 7, 'B', '5', 1),
(27, 7, 'C', '6', 0),
(28, 7, 'D', '10', 0);

-- Opsi Soal 8: Luas persegi sisi 8 cm (Kunci: A)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(29, 8, 'A', '64 cm²', 1),
(30, 8, 'B', '32 cm²', 0),
(31, 8, 'C', '16 cm²', 0),
(32, 8, 'D', '48 cm²', 0);

-- Opsi Soal 9: Pecahan 12/16 (Kunci: D)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(33, 9, 'A', '1/2', 0),
(34, 9, 'B', '2/3', 0),
(35, 9, 'C', '3/5', 0),
(36, 9, 'D', '3/4', 1);

-- Opsi Soal 10: (-7) x 8 + 10 (Kunci: B)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(37, 10, 'A', '-56', 0),
(38, 10, 'B', '-46', 1),
(39, 10, 'C', '46', 0),
(40, 10, 'D', '-66', 0);

-- Opsi Soal 11: Simbol variabel PHP (Kunci: A)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(41, 11, 'A', '$', 1),
(42, 11, 'B', '#', 0),
(43, 11, 'C', '@', 0),
(44, 11, 'D', '&', 0);

-- Opsi Soal 12: Menampilkan teks PHP (Kunci: C)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(45, 12, 'A', 'write()', 0),
(46, 12, 'B', 'display()', 0),
(47, 12, 'C', 'echo', 1),
(48, 12, 'D', 'console.log()', 0);

-- Opsi Soal 13: Enkripsi sandi PHP (Kunci: B)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(49, 13, 'A', 'md5()', 0),
(50, 13, 'B', 'password_hash()', 1),
(51, 13, 'C', 'sha1()', 0),
(52, 13, 'D', 'encrypt()', 0);

-- Opsi Soal 14: Planet terdekat matahari (Kunci: A)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(53, 14, 'A', 'Merkurius', 1),
(54, 14, 'B', 'Venus', 0),
(55, 14, 'C', 'Bumi', 0),
(56, 14, 'D', 'Mars', 0);

-- Opsi Soal 15: Planet merah (Kunci: C)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(57, 15, 'A', 'Jupiter', 0),
(58, 15, 'B', 'Saturnus', 0),
(59, 15, 'C', 'Mars', 1),
(60, 15, 'D', 'Uranus', 0);

-- Opsi Soal 16: Pusat tata surya (Kunci: D)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(61, 16, 'A', 'Bulan', 0),
(62, 16, 'B', 'Bumi', 0),
(63, 16, 'C', 'Komet', 0),
(64, 16, 'D', 'Matahari', 1);

-- Opsi Soal 17: English She ___ to school (Kunci: B)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(65, 17, 'A', 'go', 0),
(66, 17, 'B', 'goes', 1),
(67, 17, 'C', 'went', 0),
(68, 17, 'D', 'going', 0);

-- Opsi Soal 18: English They ___ football yesterday (Kunci: C)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(69, 18, 'A', 'plays', 0),
(70, 18, 'B', 'play', 0),
(71, 18, 'C', 'played', 1),
(72, 18, 'D', 'playing', 0);


-- ----------------------------------------------------------------------------
-- 7. Data Contoh Percobaan Kuis (percobaan_kuis)
-- Bermanfaat untuk mengetes halaman:
-- - Riwayat Pelajar (PL-07)
-- - Peringkat Nilai (PL-08)
-- - Hasil Kuis Pengajar (PJ-06)
-- ----------------------------------------------------------------------------

-- Percobaan 1: Ani Wijaya (id 4) mengerjakan Kuis 1 (Skor 80 dari 100, benar 4, salah 1)
INSERT INTO `percobaan_kuis` (`id`, `kuis_id`, `pelajar_id`, `waktu_mulai`, `waktu_selesai`, `durasi_menit`, `skor`, `skor_maksimal`, `jumlah_benar`, `jumlah_salah`, `status`, `catatan`) VALUES
(1, 1, 4, '2026-11-05 09:15:00', '2026-11-05 09:23:45', 15, 80, 100, 4, 1, 'selesai', 'Selesai tepat waktu');

-- Percobaan 2: Bima Pratama (id 5) mengerjakan Kuis 1 (Skor 100 dari 100, benar 5, salah 0)
INSERT INTO `percobaan_kuis` (`id`, `kuis_id`, `pelajar_id`, `waktu_mulai`, `waktu_selesai`, `durasi_menit`, `skor`, `skor_maksimal`, `jumlah_benar`, `jumlah_salah`, `status`, `catatan`) VALUES
(2, 1, 5, '2026-11-05 10:00:00', '2026-11-05 10:08:12', 15, 100, 100, 5, 0, 'selesai', 'Sempurna semua benar');

-- Percobaan 3: Ani Wijaya (id 4) mengerjakan Kuis 2 (Skor 100 dari 100, benar 5, salah 0)
INSERT INTO `percobaan_kuis` (`id`, `kuis_id`, `pelajar_id`, `waktu_mulai`, `waktu_selesai`, `durasi_menit`, `skor`, `skor_maksimal`, `jumlah_benar`, `jumlah_salah`, `status`, `catatan`) VALUES
(3, 2, 4, '2026-11-06 14:00:00', '2026-11-06 14:06:50', 10, 100, 100, 5, 0, 'selesai', 'Lulus matematika dengan nilai sempurna');


-- ----------------------------------------------------------------------------
-- 8. Data Jawaban Percobaan (jawaban_percobaan)
-- Menyimpan jawaban yang dipilih per soal beserta snapshot benar/salah & poin
-- ----------------------------------------------------------------------------

-- Jawaban untuk Percobaan 1 (Ani pada Kuis 1):
-- Soal 1: Opsi A (Benar, 20 poin)
-- Soal 2: Opsi B (Benar, 20 poin)
-- Soal 3: Opsi B (Salah, kunci adalah C href, Ani pilih link, 0 poin)
-- Soal 4: Opsi A (Benar, 20 poin)
-- Soal 5: Opsi D (Benar, 20 poin)
INSERT INTO `jawaban_percobaan` (`id`, `percobaan_id`, `soal_id`, `opsi_id`, `benar`, `poin`, `dijawab_pada`) VALUES
(1, 1, 1, 1, 1, 20, '2026-11-05 09:16:10'),
(2, 1, 2, 6, 1, 20, '2026-11-05 09:17:30'),
(3, 1, 3, 10, 0, 0, '2026-11-05 09:19:05'),
(4, 1, 4, 13, 1, 20, '2026-11-05 09:21:40'),
(5, 1, 5, 20, 1, 20, '2026-11-05 09:23:40');

-- Jawaban untuk Percobaan 2 (Bima pada Kuis 1 - Semua Benar):
INSERT INTO `jawaban_percobaan` (`id`, `percobaan_id`, `soal_id`, `opsi_id`, `benar`, `poin`, `dijawab_pada`) VALUES
(6, 2, 1, 1, 1, 20, '2026-11-05 10:01:05'),
(7, 2, 2, 6, 1, 20, '2026-11-05 10:02:40'),
(8, 2, 3, 11, 1, 20, '2026-11-05 10:04:15'),
(9, 2, 4, 13, 1, 20, '2026-11-05 10:06:00'),
(10, 2, 5, 20, 1, 20, '2026-11-05 10:08:10');

-- Jawaban untuk Percobaan 3 (Ani pada Kuis 2 - Semua Benar):
INSERT INTO `jawaban_percobaan` (`id`, `percobaan_id`, `soal_id`, `opsi_id`, `benar`, `poin`, `dijawab_pada`) VALUES
(11, 3, 6, 23, 1, 20, '2026-11-06 14:01:10'),
(12, 3, 7, 26, 1, 20, '2026-11-06 14:02:30'),
(13, 3, 8, 29, 1, 20, '2026-11-06 14:03:55'),
(14, 3, 9, 36, 1, 20, '2026-11-06 14:05:15'),
(15, 3, 10, 38, 1, 20, '2026-11-06 14:06:45');


-- ----------------------------------------------------------------------------
-- 9. Data Riwayat Status Kuis (log_status_kuis)
-- Mencatat jejak audit pengajuan dan persetujuan oleh Admin
-- ----------------------------------------------------------------------------
INSERT INTO `log_status_kuis` (`id`, `kuis_id`, `diubah_oleh`, `status_dari`, `status_ke`, `catatan`, `dibuat_pada`) VALUES
(1, 1, 2, 'draft', 'menunggu', 'Mengajukan kuis HTML untuk ditinjau oleh Admin.', '2026-11-01 08:30:00'),
(2, 1, 1, 'menunggu', 'terbit', 'Kuis disetujui. Soal dan pembahasan sudah sangat bagus dan sesuai standar.', '2026-11-01 09:00:00'),
(3, 2, 3, 'draft', 'menunggu', 'Kuis Matematika SMP siap dipublikasikan.', '2026-11-02 09:45:00'),
(4, 2, 1, 'menunggu', 'terbit', 'Disetujui. Pembahasan rumus sudah jelas.', '2026-11-02 10:00:00'),
(5, 3, 2, 'draft', 'menunggu', 'Kuis privat logika PHP untuk bahan evaluasi kelas.', '2026-11-03 12:00:00'),
(6, 3, 1, 'menunggu', 'terbit', 'Kuis privat disetujui.', '2026-11-03 13:00:00'),
(7, 4, 3, 'draft', 'menunggu', 'Mohon Admin meninjau kuis sains tata surya untuk siswa SD.', '2026-11-04 14:00:00');


-- ----------------------------------------------------------------------------
-- 10. Data Contoh Log Login (log_login)
-- ----------------------------------------------------------------------------
INSERT INTO `log_login` (`id`, `email`, `alamat_ip`, `waktu`) VALUES
(1, 'admin@dynaquiz.com', '127.0.0.1', '2026-11-07 08:00:00'),
(2, 'budi@guru.com', '127.0.0.1', '2026-11-07 08:15:00'),
(3, 'ani@siswa.com', '127.0.0.1', '2026-11-07 08:30:00');

SET FOREIGN_KEY_CHECKS = 1;
