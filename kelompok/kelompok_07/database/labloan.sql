-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 13, 2026 at 10:07 AM
-- Server version: 8.0.30
-- PHP Version: 8.5.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `labloan`
--

-- --------------------------------------------------------

--
-- Table structure for table `alat`
--

CREATE TABLE `alat` (
  `id` int UNSIGNED NOT NULL,
  `kategori_id` int UNSIGNED NOT NULL,
  `kode` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis` enum('alat','ruang') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'alat',
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `stok` int UNSIGNED NOT NULL DEFAULT '0',
  `lokasi` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kondisi` enum('baik','rusak_ringan','rusak_berat') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'baik',
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `alat`
--

INSERT INTO `alat` (`id`, `kategori_id`, `kode`, `nama`, `jenis`, `deskripsi`, `stok`, `lokasi`, `kondisi`, `foto`, `created_at`, `updated_at`) VALUES
(1, 1, 'KJ-001', 'Laptop Lenovo ThinkPad E14', 'alat', 'Intel Core i5, RAM 8 GB, SSD 512 GB', 10, 'Lemari A1', 'baik', '7db6f3bd6db82efbbb02ab87d4ccd025.webp', '2026-11-08 04:13:49', '2026-11-12 12:41:49'),
(2, 1, 'KJ-002', 'Router MikroTik hAP ac2', 'alat', 'Router untuk praktikum jaringan', 8, 'Lemari A2', 'baik', '2552c8f964574bd62314bfc1808929e6.jpg', '2026-11-08 04:13:49', '2026-11-12 12:42:11'),
(3, 1, 'KJ-003', 'Kabel UTP Cat6 5 m', 'alat', 'Kabel jaringan siap pakai', 30, 'Laci A3', 'baik', '6ae13f42d07aa655ccc71fe4bf0c3fc7.jpg', '2026-11-08 04:13:49', '2026-11-12 12:42:24'),
(4, 2, 'EL-001', 'Multimeter Digital Sanwa', 'alat', 'Pengukuran tegangan, arus, dan resistansi', 15, 'Lemari B1', 'baik', '4f30f84148930ba4ed92f8b9ad8e3d4d.jpg', '2026-11-08 04:13:49', '2026-11-12 12:42:39'),
(5, 2, 'EL-002', 'Osiloskop Rigol DS1054Z', 'alat', 'Osiloskop digital 4 kanal 50 MHz', 4, 'Meja B2', 'baik', '2213dc5e1fc432a1c44470ef1f16ec7f.jpg', '2026-11-08 04:13:49', '2026-11-12 12:43:00'),
(6, 2, 'EL-003', 'Kit Arduino Uno', 'alat', 'Arduino Uno R3 lengkap dengan sensor dasar', 12, 'Lemari B3', 'rusak_ringan', '022c8b9b6be8c96efa5ca4008b0f9f5f.jpg', '2026-11-08 04:13:49', '2026-11-12 12:36:14'),
(8, 4, 'RL-001', 'Lab Komputer 1', 'ruang', 'Kapasitas 40 orang, dilengkapi AC dan proyektor', 1, 'Gedung Teknik Lt. 2', 'baik', 'b6ee91760d7ebf80919c39c03cc5274f.jpg', '2026-11-08 04:13:49', '2026-11-12 12:35:07'),
(9, 4, 'RL-002', 'Ruang Diskusi', 'ruang', 'Kapasitas 12 orang dengan papan tulis', 1, 'Gedung Teknik Lt. 1', 'baik', '29caaf79c5afe2cec770ebcde10aa63b.jpg', '2026-11-08 04:13:49', '2026-11-12 12:43:16');

-- --------------------------------------------------------

--
-- Table structure for table `detail_peminjaman`
--

CREATE TABLE `detail_peminjaman` (
  `id` int UNSIGNED NOT NULL,
  `peminjaman_id` int UNSIGNED NOT NULL,
  `alat_id` int UNSIGNED NOT NULL,
  `jumlah` int UNSIGNED NOT NULL DEFAULT '1'
) ;

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id` int UNSIGNED NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id`, `nama`, `deskripsi`, `created_at`, `updated_at`) VALUES
(1, 'Komputer & Jaringan', 'Laptop, router, switch, dan perangkat jaringan', '2026-11-08 04:13:49', '2026-11-08 04:13:49'),
(2, 'Elektronika', 'Multimeter, osiloskop, power supply, dan komponen praktikum', '2026-11-08 04:13:49', '2026-11-08 04:13:49'),
(3, 'Multimedia', 'Proyektor, kamera, dan perangkat presentasi', '2026-11-08 04:13:49', '2026-11-08 04:13:49'),
(4, 'Ruang Laboratorium', 'Ruang praktikum dan ruang diskusi yang dapat dipinjam', '2026-11-08 04:13:49', '2026-11-08 04:13:49');

-- --------------------------------------------------------

--
-- Table structure for table `peminjaman`
--

CREATE TABLE `peminjaman` (
  `id` int UNSIGNED NOT NULL,
  `kode` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `tanggal_pinjam` datetime NOT NULL,
  `tanggal_kembali_rencana` datetime NOT NULL,
  `tanggal_kembali_aktual` datetime DEFAULT NULL,
  `keperluan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('menunggu','disetujui','ditolak','dikembalikan') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'menunggu',
  `catatan_admin` text COLLATE utf8mb4_unicode_ci,
  `diproses_oleh` int UNSIGNED DEFAULT NULL,
  `diproses_pada` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','peminjam') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'peminjam',
  `nim_nip` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_hp` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `nim_nip`, `no_hp`, `created_at`, `updated_at`) VALUES
(1, 'Administrator Lab', 'admin@labloan.test', '$2y$10$SXh1lgKoqyFS6mgoY2np6O.8mKy40P6W.Utw3AyP88EJjf3Blwfr2', 'admin', NULL, NULL, '2026-11-08 04:13:49', '2026-11-08 04:13:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alat`
--
ALTER TABLE `alat`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_alat_kode` (`kode`),
  ADD KEY `idx_alat_kategori` (`kategori_id`),
  ADD KEY `idx_alat_jenis` (`jenis`),
  ADD KEY `idx_alat_nama` (`nama`);

--
-- Indexes for table `detail_peminjaman`
--
ALTER TABLE `detail_peminjaman`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_detail_peminjaman_alat` (`peminjaman_id`,`alat_id`),
  ADD KEY `idx_detail_alat` (`alat_id`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_kategori_nama` (`nama`);

--
-- Indexes for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_peminjaman_kode` (`kode`),
  ADD KEY `idx_peminjaman_user` (`user_id`),
  ADD KEY `idx_peminjaman_status` (`status`),
  ADD KEY `idx_peminjaman_jadwal` (`tanggal_pinjam`,`tanggal_kembali_rencana`),
  ADD KEY `idx_peminjaman_admin` (`diproses_oleh`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_users_email` (`email`),
  ADD KEY `idx_users_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alat`
--
ALTER TABLE `alat`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `detail_peminjaman`
--
ALTER TABLE `detail_peminjaman`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `alat`
--
ALTER TABLE `alat`
  ADD CONSTRAINT `fk_alat_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `detail_peminjaman`
--
ALTER TABLE `detail_peminjaman`
  ADD CONSTRAINT `fk_detail_alat` FOREIGN KEY (`alat_id`) REFERENCES `alat` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detail_peminjaman` FOREIGN KEY (`peminjaman_id`) REFERENCES `peminjaman` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `fk_peminjaman_admin` FOREIGN KEY (`diproses_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_peminjaman_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
