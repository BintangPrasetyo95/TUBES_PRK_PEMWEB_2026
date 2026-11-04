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