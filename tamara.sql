-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2025 at 04:17 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tamara`
--

-- --------------------------------------------------------

--
-- Table structure for table `approval_log`
--

CREATE TABLE `approval_log` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `role` varchar(32) NOT NULL,
  `status` enum('APPROVED','REJECTED') NOT NULL,
  `created_by` int(11) NOT NULL COMMENT 'users.id',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `approval_log`
--

INSERT INTO `approval_log` (`id`, `invoice_id`, `role`, `status`, `created_by`, `created_at`) VALUES
(1, 7, 'ADMIN_WILAYAH', 'APPROVED', 4, '2025-10-24 19:27:02'),
(2, 7, 'PERWAKILAN_PI', 'APPROVED', 7, '2025-10-24 19:27:12');

-- --------------------------------------------------------

--
-- Table structure for table `gudang`
--

CREATE TABLE `gudang` (
  `id` int(11) NOT NULL,
  `nama_gudang` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gudang`
--

INSERT INTO `gudang` (`id`, `nama_gudang`) VALUES
(1, 'DC 1 Lampung (Yaporla)'),
(2, 'GP. Tulang Bawang'),
(3, 'DC Lampung 2'),
(4, 'DC Lampung 3'),
(5, 'GP Branti(PSP)'),
(6, 'GP. Lampung Timur'),
(7, 'GP. Padimas 1'),
(8, 'GP. Padimas 2'),
(9, 'GP. Padimas 3'),
(10, 'GP. Padimas 4');

-- --------------------------------------------------------

--
-- Table structure for table `gudang_tarif`
--

CREATE TABLE `gudang_tarif` (
  `id` int(11) NOT NULL,
  `gudang_id` int(11) NOT NULL,
  `jenis_transaksi` enum('BONGKAR','MUAT') NOT NULL,
  `tarif_normal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tarif_lembur` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gudang_tarif`
--

INSERT INTO `gudang_tarif` (`id`, `gudang_id`, `jenis_transaksi`, `tarif_normal`, `tarif_lembur`) VALUES
(2, 1, 'BONGKAR', 22400.00, 32600.00),
(3, 1, 'MUAT', 22400.00, 32600.00),
(4, 2, 'BONGKAR', 18750.00, 27125.00),
(5, 2, 'MUAT', 18750.00, 27125.00),
(6, 3, 'BONGKAR', 22400.00, 32600.00),
(7, 3, 'MUAT', 22400.00, 32600.00),
(8, 4, 'BONGKAR', 22400.00, 32600.00),
(9, 4, 'MUAT', 22400.00, 32600.00),
(10, 5, 'BONGKAR', 18000.00, 26000.00),
(11, 5, 'MUAT', 18000.00, 26000.00),
(12, 6, 'BONGKAR', 18800.00, 27200.00),
(13, 6, 'MUAT', 18800.00, 27200.00),
(14, 7, 'BONGKAR', 18500.00, 26750.00),
(15, 7, 'MUAT', 18500.00, 26750.00),
(16, 9, 'BONGKAR', 18500.00, 26750.00),
(17, 9, 'MUAT', 18500.00, 26750.00),
(18, 10, 'BONGKAR', 18500.00, 26750.00),
(19, 10, 'MUAT', 18500.00, 26750.00);

-- --------------------------------------------------------

--
-- Table structure for table `invoice`
--

CREATE TABLE `invoice` (
  `id` int(11) NOT NULL,
  `bulan` varchar(20) NOT NULL,
  `jenis_pupuk` varchar(255) NOT NULL,
  `gudang_id` int(11) NOT NULL,
  `jenis_transaksi` enum('BONGKAR','MUAT') NOT NULL,
  `uraian_pekerjaan` varchar(255) NOT NULL,
  `tarif_normal` decimal(10,2) NOT NULL,
  `tarif_lembur` decimal(10,2) NOT NULL,
  `total_bongkar_normal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_bongkar_lembur` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `current_role` enum('ADMIN_GUDANG','KEPALA_GUDANG','ADMIN_WILAYAH','PERWAKILAN_PI','ADMIN_PCS','KEUANGAN','SUPERADMIN') NOT NULL DEFAULT 'ADMIN_GUDANG',
  `no_mmj` varchar(100) DEFAULT NULL,
  `no_soj` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice`
--

INSERT INTO `invoice` (`id`, `bulan`, `jenis_pupuk`, `gudang_id`, `jenis_transaksi`, `uraian_pekerjaan`, `tarif_normal`, `tarif_lembur`, `total_bongkar_normal`, `total_bongkar_lembur`, `total`, `created_at`, `current_role`, `no_mmj`, `no_soj`) VALUES
(1, 'January', 'Urea Subsidi, NPK Subsidi', 2, 'BONGKAR', 'Bongkar Pupuk', 16750.00, 25125.00, 6406875.00, 0.00, 6406875.00, '2025-08-14 03:50:46', 'ADMIN_GUDANG', NULL, NULL),
(2, 'September', 'Urea Subsidi, NPK Subsidi', 2, 'BONGKAR', 'Bongkar Pupuk', 16750.00, 25125.00, 1675000.00, 502500.00, 2177500.00, '2025-09-18 14:24:31', 'ADMIN_GUDANG', NULL, NULL),
(3, 'January', 'Urea Subsidi, NPK Subsidi', 2, 'BONGKAR', 'Bongkar Pupuk', 16750.00, 25125.00, 1675000.00, 1256250.00, 2931250.00, '2025-09-22 08:08:04', 'ADMIN_GUDANG', NULL, NULL),
(4, 'January', 'Urea', 2, 'BONGKAR', 'Bongkar Pupuk', 18750.00, 27125.00, 375000.00, 813750.00, 1188750.00, '2025-10-17 03:08:36', 'ADMIN_GUDANG', NULL, NULL),
(5, 'January', 'Urea', 1, 'BONGKAR', 'Bongkar Pupuk', 22400.00, 32600.00, 672000.00, 978000.00, 1650000.00, '2025-10-17 03:18:12', 'ADMIN_GUDANG', NULL, NULL),
(6, 'January', 'Urea', 3, 'MUAT', 'Bongkar Pupuk', 22400.00, 32600.00, 448000.00, 1304000.00, 1752000.00, '2025-10-17 07:06:08', 'ADMIN_GUDANG', NULL, NULL),
(7, 'January', 'dws', 1, 'BONGKAR', 'sda', 22400.00, 32600.00, 448000.00, 0.00, 448000.00, '2025-10-24 12:26:12', 'ADMIN_PCS', '1212', '121');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_line`
--

CREATE TABLE `invoice_line` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `sto_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_line`
--

INSERT INTO `invoice_line` (`id`, `invoice_id`, `sto_id`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 2, 4),
(4, 3, 3),
(5, 4, 8),
(6, 5, 10),
(7, 5, 11),
(8, 6, 9),
(9, 7, 12);

-- --------------------------------------------------------

--
-- Table structure for table `sto`
--

CREATE TABLE `sto` (
  `id` int(11) NOT NULL,
  `nomor_sto` varchar(50) NOT NULL,
  `tanggal_terbit` date DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `gudang_id` int(11) NOT NULL,
  `jenis_transaksi` enum('BONGKAR','MUAT') NOT NULL,
  `transportir` varchar(100) DEFAULT NULL,
  `tonase_normal` decimal(10,2) DEFAULT 0.00,
  `tonase_lembur` decimal(10,2) DEFAULT 0.00,
  `status` enum('NOT_USED','USED') DEFAULT 'NOT_USED',
  `pilihan` enum('DIPILIH','BELUM_DIPILIH') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `jumlah` decimal(10,2) GENERATED ALWAYS AS (`tonase_normal` + `tonase_lembur`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sto`
--

INSERT INTO `sto` (`id`, `nomor_sto`, `tanggal_terbit`, `keterangan`, `gudang_id`, `jenis_transaksi`, `transportir`, `tonase_normal`, `tonase_lembur`, `status`, `pilihan`, `created_at`) VALUES
(1, '5520065735', '2025-01-03', NULL, 2, 'BONGKAR', 'Andi', 191.25, 0.00, 'USED', 'DIPILIH', '2025-08-14 03:44:51'),
(2, '5520065881', '2025-01-07', NULL, 2, 'BONGKAR', 'Andi', 191.25, 0.00, 'USED', 'DIPILIH', '2025-08-14 03:46:42'),
(3, '5120987654', '2025-09-18', NULL, 1, 'BONGKAR', 'ABC', 100.00, 50.00, 'USED', 'DIPILIH', '2025-09-18 14:18:48'),
(4, '5129876543', '2025-09-18', NULL, 2, 'BONGKAR', 'ABC', 100.00, 20.00, 'USED', 'DIPILIH', '2025-09-18 14:23:56'),
(6, '12345', '2025-10-08', NULL, 1, 'BONGKAR', 'pcs 3', 20.00, 30.00, 'NOT_USED', 'BELUM_DIPILIH', '2025-10-15 08:32:20'),
(7, '123466777', '2025-10-14', NULL, 1, 'BONGKAR', 'PCS', 20.00, 350.00, 'NOT_USED', 'BELUM_DIPILIH', '2025-10-15 09:32:52'),
(8, '1234567788787766', '2025-10-14', NULL, 2, 'BONGKAR', 'PCS', 20.00, 30.00, 'USED', 'DIPILIH', '2025-10-16 03:50:07'),
(9, '3333', '2025-10-15', NULL, 3, 'MUAT', 'test', 20.00, 40.00, 'USED', 'DIPILIH', '2025-10-17 03:11:53'),
(10, '00012', '2025-10-16', NULL, 1, 'BONGKAR', 'PCS', 20.00, 10.00, 'USED', 'DIPILIH', '2025-10-17 03:15:44'),
(11, '00013', '2025-10-16', NULL, 1, 'BONGKAR', 'ABC', 10.00, 20.00, 'USED', 'DIPILIH', '2025-10-17 03:16:17'),
(12, 'ijyhgfvdc', '2025-10-24', NULL, 1, 'BONGKAR', 'cxzxzc', 20.00, 0.00, 'USED', 'DIPILIH', '2025-10-24 12:25:38');

-- --------------------------------------------------------

--
-- Table structure for table `sto_files`
--

CREATE TABLE `sto_files` (
  `id` int(11) NOT NULL,
  `sto_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `mime` varchar(150) DEFAULT NULL,
  `size_bytes` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sto_files`
--

INSERT INTO `sto_files` (`id`, `sto_id`, `filename`, `stored_name`, `mime`, `size_bytes`, `created_at`) VALUES
(1, 1, 'AVOLUT - SKF 12 AGS 2025.pdf', 'sto_689d5bb30760b3.32993896.pdf', 'application/pdf', 123705, '2025-08-14 03:44:51'),
(2, 2, '2765 - Permohonan Pembayaran Starlink Periode 26 Juli - 26 Agustus 2025 (1).pdf', 'sto_689d5c227f1855.89499424.pdf', 'application/pdf', 140547, '2025-08-14 03:46:42'),
(3, 3, 'PR INFRASTRUKTUR TI.pdf', 'sto_68cc14c89bd214.84004303.pdf', 'application/pdf', 122065, '2025-09-18 14:18:48'),
(4, 4, 'PR INFRASTRUKTUR TI.pdf', 'sto_68cc15fc718653.78384470.pdf', 'application/pdf', 122065, '2025-09-18 14:23:56'),
(5, 9, 'TAMARA.pdf', 'sto_68f1b3f9bb6297.63534594.pdf', 'application/pdf', 94786, '2025-10-17 03:11:53');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('ADMIN_GUDANG','KEPALA_GUDANG','ADMIN_WILAYAH','PERWAKILAN_PI','ADMIN_PCS','KEUANGAN','SUPERADMIN') NOT NULL DEFAULT 'ADMIN_GUDANG'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `username`, `password`, `role`) VALUES
(1, 'Super Admin', 'superadmin', '$2y$10$mwqWU.iFgDvGF8RShoMl/ORB7AnOWaZwt7Ku3v8SU15MeMH4iG3FO', 'SUPERADMIN'),
(2, 'Admin Gudang', 'admin_gudang', '$2y$12$66yLOsKpfVJ1yn3E8uRrf.ZsEzbv.xp5fFT5OZ/KPCLffzcirR.N.', 'ADMIN_GUDANG'),
(3, 'Kepala Gudang', 'kepala_gudang', '$2y$12$BuyeH3BAR/T4iBSYEw.1QOvNBjHm3cggWBDGe5vOAFD4mOJxt/jPC', 'KEPALA_GUDANG'),
(4, 'Admin Wilayah', 'admin_wilayah', '$2y$12$MqSKT2MIZKjoR7UEw4lQPOpjnaZ2GkfSOtGM/n6c8gAEXW.YB/yr2', 'ADMIN_WILAYAH'),
(5, 'Admin PCS', 'admin_pcs', '$2y$12$EaNAbZUN1HTTAFGBPDRuN.Eya.qowpLMBN13.Y5.M1AQPNv5eHhhu', 'ADMIN_PCS'),
(6, 'Keuangan', 'keuangan', '$2y$12$7uhwSJOLGfYzm7G0uDRvWu359/Si.SY7qrODiXsXMJr/KkILfo8RO', 'KEUANGAN'),
(7, 'Perwakilan PI', 'perwakilan_pi', '$2y$12$5CDrlehv/FkXZfp8/kCyjevSysHPtcnmPzdFEkFu6mrhlzsjJVGS2', 'PERWAKILAN_PI');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `approval_log`
--
ALTER TABLE `approval_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `gudang`
--
ALTER TABLE `gudang`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gudang_tarif`
--
ALTER TABLE `gudang_tarif`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gudang_id` (`gudang_id`);

--
-- Indexes for table `invoice`
--
ALTER TABLE `invoice`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gudang_id` (`gudang_id`);

--
-- Indexes for table `invoice_line`
--
ALTER TABLE `invoice_line`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `sto_id` (`sto_id`);

--
-- Indexes for table `sto`
--
ALTER TABLE `sto`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nomor_sto` (`nomor_sto`);

--
-- Indexes for table `sto_files`
--
ALTER TABLE `sto_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sto_id` (`sto_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `approval_log`
--
ALTER TABLE `approval_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `gudang`
--
ALTER TABLE `gudang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `gudang_tarif`
--
ALTER TABLE `gudang_tarif`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `invoice`
--
ALTER TABLE `invoice`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `invoice_line`
--
ALTER TABLE `invoice_line`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `sto`
--
ALTER TABLE `sto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `sto_files`
--
ALTER TABLE `sto_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `approval_log`
--
ALTER TABLE `approval_log`
  ADD CONSTRAINT `approval_log_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoice` (`id`),
  ADD CONSTRAINT `approval_log_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `gudang_tarif`
--
ALTER TABLE `gudang_tarif`
  ADD CONSTRAINT `gudang_tarif_ibfk_1` FOREIGN KEY (`gudang_id`) REFERENCES `gudang` (`id`);

--
-- Constraints for table `invoice`
--
ALTER TABLE `invoice`
  ADD CONSTRAINT `invoice_ibfk_1` FOREIGN KEY (`gudang_id`) REFERENCES `gudang` (`id`);

--
-- Constraints for table `invoice_line`
--
ALTER TABLE `invoice_line`
  ADD CONSTRAINT `invoice_line_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoice` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_line_ibfk_2` FOREIGN KEY (`sto_id`) REFERENCES `sto` (`id`);

--
-- Constraints for table `sto_files`
--
ALTER TABLE `sto_files`
  ADD CONSTRAINT `fk_sto_files_sto` FOREIGN KEY (`sto_id`) REFERENCES `sto` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
