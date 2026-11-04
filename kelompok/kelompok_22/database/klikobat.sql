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