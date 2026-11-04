-- ==========================================================
-- DATABASE: klikobat (Kelompok 22)
-- Sistem Informasi KlikObat - Katalog Farmasi & Edukasi
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `klikobat`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `klikobat`;

DROP TABLE IF EXISTS `obat`;
DROP TABLE IF EXISTS `kategori`;
DROP TABLE IF EXISTS `admin`;

-- 1. Struktur Tabel kategori
CREATE TABLE `kategori` (
  `id_kategori` INT(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id_kategori`),
  UNIQUE KEY `uk_nama_kategori` (`nama_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Struktur Tabel obat
CREATE TABLE `obat` (
  `id_obat` INT(11) NOT NULL AUTO_INCREMENT,
  `nama_obat` VARCHAR(150) NOT NULL,
  `id_kategori` INT(11) NOT NULL,
  `stok` INT(11) NOT NULL DEFAULT 0,
  `indikasi` TEXT NOT NULL,
  `aturan_pakai` TEXT NOT NULL,
  `peringatan` TEXT NOT NULL,
  PRIMARY KEY (`id_obat`),
  KEY `idx_id_kategori` (`id_kategori`),
  KEY `idx_nama_obat` (`nama_obat`),
  CONSTRAINT `fk_obat_kategori`
    FOREIGN KEY (`id_kategori`)
    REFERENCES `kategori` (`id_kategori`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Struktur Tabel admin
CREATE TABLE `admin` (
  `id_admin` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id_admin`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Seed Data: 5 Kategori Obat
INSERT INTO `kategori` (`id_kategori`, `nama_kategori`) VALUES
(1, 'Pereda Nyeri & Demam'),
(2, 'Obat Batuk, Pilek & Flu'),
(3, 'Saluran Pencernaan'),
(4, 'Vitamin & Suplemen'),
(5, 'Antiseptik & Perawatan Luka');

-- 5. Seed Data: 20 Obat (Bagian 1: Obat 1 - 10)
INSERT INTO `obat` (`id_obat`, `nama_obat`, `id_kategori`, `stok`, `indikasi`, `aturan_pakai`, `peringatan`) VALUES
(1, 'Paracetamol 500 mg (Strip 10 Tablet)', 1, 45,
 'Meredakan demam serta nyeri ringan hingga sedang seperti sakit kepala, sakit gigi, dan nyeri otot.',
 'Dewasa: 1-2 tablet, 3-4 kali sehari sesudah makan. Maksimal 8 tablet per hari.',
 'Hati-hati penggunaan pada penderita gangguan fungsi hati dan ginjal. Jangan melebihi dosis yang dianjurkan.'),

(2, 'Ibuprofen 400 mg (Strip 10 Tablet)', 1, 8,
 'Mengatasi nyeri inflamasi ringan hingga sedang, nyeri haid (dismenore), sakit gigi, nyeri sendi, dan menurunkan demam.',
 'Dewasa: 1 tablet, 3 kali sehari segera sesudah makan.',
 'Tidak dianjurkan untuk penderita tukak lambung, ibu hamil trimester akhir, dan pasien dengan riwayat alergi OAINS.'),

(3, 'Asam Mefenamat 500 mg (Strip 10 Kaplet)', 1, 25,
 'Meredakan nyeri akut dan kronik ringan sampai sedang sehubungan dengan sakit kepala, sakit gigi, dan nyeri pasca operasi.',
 'Dewasa: Dosis awal 1 kaplet, dilanjutkan 1/2 - 1 kaplet tiap 6 jam sesudah makan.',
 'Jangan digunakan lebih dari 7 hari. Kontraindikasi pada pasien radang usus dan gangguan ginjal berat.'),

(4, 'Sanmol Sirup 60 ml', 1, 0,
 'Menurunkan panas demam dan meredakan rasa sakit pada anak-anak seperti sakit kepala dan nyeri tumbuh gigi.',
 'Anak 1-2 tahun: 5 ml (1 sendok takar), 3-4 kali sehari. Anak 2-6 tahun: 5-10 ml, 3-4 kali sehari.',
 'Kocok dahulu sebelum diminum. Hubungi dokter bila demam tidak turun setelah 2 hari penggunaan.'),

(5, 'OBH Combi Batuk Berdahak Menthol 100 ml', 2, 30,
 'Meredakan batuk berdahak yang disertai gejala flu seperti demam, sakit kepala, hidung tersumbat, dan bersin-bersin.',
 'Dewasa & anak di atas 12 tahun: 3 kali sehari 3 sendok takar (15 ml) sesudah makan.',
 'Dapat menyebabkan kantuk. Jangan mengendarai kendaraan bermotor atau menjalankan mesin setelah meminum obat ini.'),

(6, 'Siladex Mucolytic & Expectorant 60 ml', 2, 5,
 'Mengencerkan dahak pada saluran pernapasan dan mempermudah pengeluaran dahak saat batuk.',
 'Dewasa & anak di atas 10 tahun: 3 kali sehari 1 sendok takar (5 ml) sesudah makan.',
 'Bebas gula dan alkohol. Hati-hati penggunaan pada penderita tukak lambung dan ibu hamil trimester pertama.'),

(7, 'Tremenza Sirup 60 ml', 2, 18,
 'Meringankan gejala-gejala flu karena alergi pada saluran pernapasan bagian atas seperti hidung tersumbat dan bersin.',
 'Dewasa: 3-4 kali sehari 2 sendok takar (10 ml). Anak 6-12 tahun: 3-4 kali sehari 1 sendok takar (5 ml).',
 'Tidak dianjurkan untuk penderita hipertensi berat, penyakit jantung koroner, dan pasien yang mengonsumsi antidepresan MAOI.'),

(8, 'Degrol Hisap Dequalinium (Strip 10 Tablet)', 2, 0,
 'Tablet hisap antiseptik untuk membantu meredakan radang tenggorokan, sariawan, dan infeksi ringan rongga mulut.',
 'Hisap perlahan 1 tablet setiap 3-4 jam. Maksimal 8 tablet sehari.',
 'Hindari penggunaan jangka panjang secara terus-menerus. Tidak untuk anak di bawah usia 6 tahun.'),

(9, 'Promag Tablet Kunyah (Strip 12 Tablet)', 3, 50,
 'Meringankan gejala sakit maag dan kelebihan asam lambung seperti perih, mual, kembung, dan nyeri ulu hati.',
 'Dewasa: 1-2 tablet dikunyah, 3-4 kali sehari. Diminum 1 jam sebelum makan atau 2 jam setelah makan dan menjelang tidur.',
 'Beri jeda minimal 2 jam jika sedang mengonsumsi antibiotik tetrasiklin atau suplemen zat besi.'),

(10, 'Mylanta Cair 150 ml', 3, 7,
 'Mengurangi gejala kelebihan asam lambung, gastritis, tukak lambung, dan tukak usus dua belas jari.',
 'Dewasa: 1-2 sendok takar (5-10 ml), 3-4 kali sehari di antara waktu makan dan sebelum tidur.',
 'Tidak dianjurkan digunakan terus-menerus lebih dari 2 minggu kecuali atas petunjuk dokter.');