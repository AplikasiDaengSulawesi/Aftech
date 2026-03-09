-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 09, 2026 at 09:57 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_aftech`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `action` varchar(100) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `action`, `details`, `timestamp`) VALUES
(1, 'PENGIRIMAN', 'Kirim 1 paket ke Rahmat (No. Resi #2-090320261208-1-RA)', '2026-03-09 04:08:43'),
(2, 'PENGIRIMAN', 'Tambah susulan 1 paket ke No. Resi #2-090320261208-2-RA', '2026-03-09 04:09:27'),
(3, 'LOGIN', 'User Administrator Utama (admin) berhasil login', '2026-03-09 04:16:58'),
(4, 'TAMBAH', 'Admin Update Template: SEDOTAN PCS - MACHINE 2', '2026-03-09 04:43:10'),
(5, 'TAMBAH', 'Admin Update Template: SEDOTAN 100 PCS - MACHINE 2', '2026-03-09 04:43:18'),
(6, 'TAMBAH', 'Admin Update Template: SEDOTAN 100PCS - MACHINE 2', '2026-03-09 04:43:31'),
(7, 'LOGOUT', 'User Administrator Utama (admin) keluar dari sistem', '2026-03-09 08:51:13'),
(8, 'LOGIN', 'User Staff Gudang (gudang) berhasil login', '2026-03-09 08:51:25'),
(9, 'LOGOUT', 'User Staff Gudang (gudang) keluar dari sistem', '2026-03-09 08:51:45'),
(10, 'LOGIN', 'User Tim Quality Control (qc) berhasil login', '2026-03-09 08:51:51');

-- --------------------------------------------------------

--
-- Table structure for table `app_config`
--

CREATE TABLE `app_config` (
  `id` int(11) NOT NULL DEFAULT 1,
  `pin_code` varchar(10) DEFAULT NULL,
  `note` varchar(100) DEFAULT NULL,
  `reset_pin` varchar(4) DEFAULT '1234'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `app_config`
--

INSERT INTO `app_config` (`id`, `pin_code`, `note`, `reset_pin`) VALUES
(1, '0503', 'Reset Database Lokal', '1234');

-- --------------------------------------------------------

--
-- Table structure for table `distributor_shipments`
--

CREATE TABLE `distributor_shipments` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `production_id` int(11) NOT NULL,
  `label_no` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `distributor_shipments`
--

INSERT INTO `distributor_shipments` (`id`, `shipment_id`, `production_id`, `label_no`) VALUES
(145, 64, 120, 8),
(146, 64, 120, 4),
(147, 64, 120, 7),
(148, 64, 120, 1),
(149, 64, 120, 5),
(150, 64, 120, 9),
(151, 64, 120, 6),
(152, 64, 120, 3),
(153, 64, 122, 1),
(154, 64, 122, 5),
(155, 64, 122, 2),
(156, 64, 122, 3),
(157, 64, 122, 4),
(158, 65, 120, 2),
(159, 65, 120, 10);

-- --------------------------------------------------------

--
-- Table structure for table `master_customers`
--

CREATE TABLE `master_customers` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_orders` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_customers`
--

INSERT INTO `master_customers` (`id`, `name`, `contact`, `address`, `created_at`, `total_orders`) VALUES
(1, 'Rahmat', '085213976352', 'JLN.PERINTIS KEMERDEKAAN VII. LORONG 2', '2026-03-08 07:44:14', 12),
(2, 'Tri Munazzar Abduh', '1234567890', 'JLN.SUKA-SUKA', '2026-03-08 07:44:14', 7),
(8, 'Lutfi Rasyid', '085174302323', 'Morowali', '2026-03-08 08:38:56', 3);

-- --------------------------------------------------------

--
-- Table structure for table `master_items`
--

CREATE TABLE `master_items` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `unit_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_items`
--

INSERT INTO `master_items` (`id`, `name`, `unit_id`) VALUES
(1, 'BOTOL', 1),
(2, 'SEDOTAN', 3),
(3, 'CUP', 1),
(4, 'TESTING', 1);

-- --------------------------------------------------------

--
-- Table structure for table `master_machines`
--

CREATE TABLE `master_machines` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` enum('active','maintenance') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_machines`
--

INSERT INTO `master_machines` (`id`, `name`, `status`) VALUES
(1, 'THERMO TINGGI 01', 'active'),
(7, 'THERMO 1', 'active'),
(8, 'THERMO 2', 'active'),
(9, 'THERMO 3', 'maintenance'),
(10, 'THERMO TINGGI 02', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `master_quantities`
--

CREATE TABLE `master_quantities` (
  `id` int(11) NOT NULL,
  `machine_id` int(11) NOT NULL,
  `qty_value` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_quantities`
--

INSERT INTO `master_quantities` (`id`, `machine_id`, `qty_value`) VALUES
(1, 1, '800'),
(2, 1, '1000'),
(4, 7, '200'),
(5, 7, '500'),
(6, 8, '600'),
(7, 7, '800'),
(8, 9, '1000'),
(9, 9, '1300'),
(10, 10, '1000'),
(11, 10, '1500'),
(12, 10, '2000');

-- --------------------------------------------------------

--
-- Table structure for table `master_shifts`
--

CREATE TABLE `master_shifts` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_shifts`
--

INSERT INTO `master_shifts` (`id`, `name`) VALUES
(1, 'SHIFT A'),
(2, 'SHIFT B'),
(7, 'SHIFT C');

-- --------------------------------------------------------

--
-- Table structure for table `master_sizes`
--

CREATE TABLE `master_sizes` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `size_value` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_sizes`
--

INSERT INTO `master_sizes` (`id`, `item_id`, `size_value`) VALUES
(1, 1, '330'),
(2, 1, '600'),
(3, 2, '100'),
(4, 3, '120'),
(5, 1, '1000'),
(6, 3, '200');

-- --------------------------------------------------------

--
-- Table structure for table `master_templates`
--

CREATE TABLE `master_templates` (
  `id` int(11) NOT NULL,
  `template_name` varchar(100) NOT NULL,
  `item` varchar(100) DEFAULT NULL,
  `size` varchar(20) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `machine` varchar(50) DEFAULT NULL,
  `shift` varchar(20) DEFAULT NULL,
  `quantity` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_templates`
--

INSERT INTO `master_templates` (`id`, `template_name`, `item`, `size`, `unit`, `machine`, `shift`, `quantity`) VALUES
(1, 'BOTOL 600ML - THERMO 1', 'BOTOL', '600', 'ML', 'THERMO 1', 'SHIFT A', '1200'),
(2, 'CUP 240ML - MACHINE 1', 'CUP', '240', 'ML', 'MACHINE 1', 'SHIFT B', '48'),
(3, 'SEDOTAN 100PCS - MACHINE 2', 'SEDOTAN', '100', 'PCS', 'MACHINE 2', 'SHIFT A', '2400'),
(4, 'CUP 120ML - THERMO 1', 'CUP', '120', 'ML', 'THERMO 1', 'SHIFT A', '100');

-- --------------------------------------------------------

--
-- Table structure for table `master_units`
--

CREATE TABLE `master_units` (
  `id` int(11) NOT NULL,
  `name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_units`
--

INSERT INTO `master_units` (`id`, `name`) VALUES
(2, 'GR'),
(4, 'KG'),
(5, 'LITER'),
(1, 'ML'),
(3, 'PCS');

-- --------------------------------------------------------

--
-- Table structure for table `outbound_shipments`
--

CREATE TABLE `outbound_shipments` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(150) NOT NULL,
  `customer_contact` varchar(100) DEFAULT NULL,
  `customer_address` text DEFAULT NULL,
  `shipment_date` date DEFAULT NULL,
  `total_qty` int(11) NOT NULL DEFAULT 0,
  `shipped_by` varchar(100) NOT NULL,
  `shipped_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_actual_qty` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `outbound_shipments`
--

INSERT INTO `outbound_shipments` (`id`, `customer_name`, `customer_contact`, `customer_address`, `shipment_date`, `total_qty`, `shipped_by`, `shipped_at`, `total_actual_qty`) VALUES
(64, 'Rahmat', '085213976352', 'JLN.PERINTIS KEMERDEKAAN VII. LORONG 2', '2026-03-09', 13, 'Administrator Utama', '2026-03-09 04:03:59', 10500),
(65, 'Rahmat', '085213976352', 'JLN.PERINTIS KEMERDEKAAN VII. LORONG 2', '2026-03-09', 2, 'Administrator Utama', '2026-03-09 04:08:43', 2000);

-- --------------------------------------------------------

--
-- Table structure for table `outbound_shipment_batches`
--

CREATE TABLE `outbound_shipment_batches` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `production_id` int(11) NOT NULL,
  `label_qty` int(11) NOT NULL,
  `unit_qty` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `outbound_shipment_batches`
--

INSERT INTO `outbound_shipment_batches` (`id`, `shipment_id`, `production_id`, `label_qty`, `unit_qty`) VALUES
(9, 64, 120, 8, 8000),
(10, 64, 122, 5, 2500),
(11, 65, 120, 2, 2000);

-- --------------------------------------------------------

--
-- Table structure for table `production_labels`
--

CREATE TABLE `production_labels` (
  `id` int(11) NOT NULL,
  `batch` varchar(100) NOT NULL,
  `item` varchar(100) DEFAULT NULL,
  `size` varchar(20) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `machine` varchar(50) DEFAULT NULL,
  `shift` varchar(20) DEFAULT NULL,
  `quantity` varchar(20) DEFAULT NULL,
  `operator` varchar(100) DEFAULT NULL,
  `qc` varchar(100) DEFAULT NULL,
  `copies` int(11) DEFAULT 0,
  `production_date` date DEFAULT NULL,
  `production_time` time DEFAULT NULL,
  `device_model` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `production_labels`
--

INSERT INTO `production_labels` (`id`, `batch`, `item`, `size`, `unit`, `machine`, `shift`, `quantity`, `operator`, `qc`, `copies`, `production_date`, `production_time`, `device_model`, `created_at`) VALUES
(120, '080326-01C-SED-1000-RAMA-100PCS', 'SEDOTAN', '100', 'PCS', 'THERMO TINGGI 01', 'SHIFT C', '1000', 'rahmat', 'mamat', 10, '2026-03-08', '02:53:49', '25040RP0AG', '2026-03-07 18:54:19'),
(122, '080326-1A-BOT-500-RAMA-330ML', 'BOTOL', '330', 'ML', 'THERMO 1', 'SHIFT A', '500', 'rahmat', 'mamat', 5, '2026-03-08', '02:53:09', '25040RP0AG', '2026-03-07 18:56:16'),
(123, '090326-2A-SED-2400-FAFA-1PCS', 'SEDOTAN', '1', 'PCS', 'MACHINE 2', 'SHIFT A', '2400', 'Fahrul', 'Fahrul', 2, '2026-03-09', '06:24:19', 'SM-A057F', '2026-03-08 22:29:07'),
(124, '090326-1A-CUP-100-FAFA-120ML', 'CUP', '120', 'ML', 'THERMO 1', 'SHIFT A', '100', 'Fahrul', 'Fahrul', 2, '2026-03-09', '06:24:11', 'SM-A057F', '2026-03-08 22:29:53'),
(126, '090326-1B-CUP-48-FARA-240ML', 'CUP', '240', 'ML', 'MACHINE 1', 'SHIFT B', '48', 'Fahrul', 'Rahmat', 2, '2026-03-09', '06:24:16', 'SM-A556E', '2026-03-08 22:31:02'),
(127, '090326-1A-CUP-100-FARA-120ML', 'CUP', '120', 'ML', 'THERMO 1', 'SHIFT A', '100', 'Fahrul', 'Rahmat', 2, '2026-03-09', '06:32:05', 'SM-A556E', '2026-03-08 22:32:12');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('admin','qc','gudang') DEFAULT 'gudang',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$qxVO0eGGxr4LmjwxdSIM0Ox5zRYR93OABInYXccJK.sRq9e2/snZa', 'Administrator Utama', 'admin', '2026-03-05 05:38:10'),
(2, 'qc', '$2y$10$WdcWWf3V1rEsbw1nYqTVHeDfWAcyh/VKahkPXVQBzRea/vnZj1ZOy', 'Tim Quality Control', 'qc', '2026-03-05 05:38:10'),
(3, 'gudang', '$2y$10$CKYKLCXPPMZ2f0vYmo4M..m4jggYSPn1nS2y4QYbSHdhW/kEyRUpK', 'Staff Gudang', 'gudang', '2026-03-05 05:38:10');

-- --------------------------------------------------------

--
-- Table structure for table `warehouse_items`
--

CREATE TABLE `warehouse_items` (
  `id` int(11) NOT NULL,
  `production_id` int(11) DEFAULT NULL,
  `label_no` int(11) DEFAULT NULL,
  `transferred_by` varchar(100) DEFAULT NULL,
  `transferred_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `warehouse_items`
--

INSERT INTO `warehouse_items` (`id`, `production_id`, `label_no`, `transferred_by`, `transferred_at`) VALUES
(221, 120, 5, 'Administrator Utama', '2026-03-08 07:16:04'),
(222, 120, 3, 'Administrator Utama', '2026-03-08 09:10:23'),
(223, 120, 8, 'Administrator Utama', '2026-03-08 09:10:27'),
(224, 120, 7, 'Administrator Utama', '2026-03-08 09:10:28'),
(225, 120, 6, 'Administrator Utama', '2026-03-08 09:10:30'),
(226, 120, 4, 'Administrator Utama', '2026-03-08 09:10:34'),
(227, 120, 10, 'Administrator Utama', '2026-03-08 09:10:36'),
(228, 120, 9, 'Administrator Utama', '2026-03-08 09:10:40'),
(229, 120, 2, 'Administrator Utama', '2026-03-08 09:10:42'),
(230, 120, 1, 'Administrator Utama', '2026-03-08 09:10:44'),
(231, 127, 1, 'Administrator Utama', '2026-03-08 22:33:10'),
(232, 126, 2, 'Administrator Utama', '2026-03-08 22:33:14'),
(233, 123, 1, 'Administrator Utama', '2026-03-08 22:33:27'),
(234, 127, 2, 'Administrator Utama', '2026-03-08 22:33:35'),
(235, 124, 1, 'Administrator Utama', '2026-03-08 22:33:38'),
(236, 123, 2, 'Administrator Utama', '2026-03-08 22:33:41'),
(237, 124, 2, 'Administrator Utama', '2026-03-08 22:33:51'),
(238, 126, 1, 'Administrator Utama', '2026-03-08 22:33:54'),
(239, 122, 1, 'Administrator Utama', '2026-03-09 02:50:29'),
(240, 122, 4, 'Administrator Utama', '2026-03-09 02:50:33'),
(241, 122, 3, 'Administrator Utama', '2026-03-09 02:50:36'),
(242, 122, 2, 'Administrator Utama', '2026-03-09 02:50:40'),
(243, 122, 5, 'Administrator Utama', '2026-03-09 02:50:42');

-- --------------------------------------------------------

--
-- Table structure for table `warehouse_transfers`
--

CREATE TABLE `warehouse_transfers` (
  `id` int(11) NOT NULL,
  `production_id` int(11) DEFAULT NULL,
  `transferred_by` varchar(100) DEFAULT NULL,
  `transferred_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `app_config`
--
ALTER TABLE `app_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `distributor_shipments`
--
ALTER TABLE `distributor_shipments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_label` (`production_id`,`label_no`),
  ADD KEY `shipment_id` (`shipment_id`);

--
-- Indexes for table `master_customers`
--
ALTER TABLE `master_customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `master_items`
--
ALTER TABLE `master_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `unit_id` (`unit_id`);

--
-- Indexes for table `master_machines`
--
ALTER TABLE `master_machines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `master_quantities`
--
ALTER TABLE `master_quantities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `machine_id` (`machine_id`);

--
-- Indexes for table `master_shifts`
--
ALTER TABLE `master_shifts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `master_sizes`
--
ALTER TABLE `master_sizes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `master_templates`
--
ALTER TABLE `master_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `template_name` (`template_name`);

--
-- Indexes for table `master_units`
--
ALTER TABLE `master_units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `outbound_shipments`
--
ALTER TABLE `outbound_shipments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `outbound_shipment_batches`
--
ALTER TABLE `outbound_shipment_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shipment_id` (`shipment_id`),
  ADD KEY `production_id` (`production_id`);

--
-- Indexes for table `production_labels`
--
ALTER TABLE `production_labels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `batch` (`batch`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `warehouse_items`
--
ALTER TABLE `warehouse_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `production_id` (`production_id`,`label_no`);

--
-- Indexes for table `warehouse_transfers`
--
ALTER TABLE `warehouse_transfers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `production_id` (`production_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `distributor_shipments`
--
ALTER TABLE `distributor_shipments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=160;

--
-- AUTO_INCREMENT for table `master_customers`
--
ALTER TABLE `master_customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `master_items`
--
ALTER TABLE `master_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `master_machines`
--
ALTER TABLE `master_machines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `master_quantities`
--
ALTER TABLE `master_quantities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `master_shifts`
--
ALTER TABLE `master_shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `master_sizes`
--
ALTER TABLE `master_sizes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `master_templates`
--
ALTER TABLE `master_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `master_units`
--
ALTER TABLE `master_units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `outbound_shipments`
--
ALTER TABLE `outbound_shipments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `outbound_shipment_batches`
--
ALTER TABLE `outbound_shipment_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `production_labels`
--
ALTER TABLE `production_labels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=128;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `warehouse_items`
--
ALTER TABLE `warehouse_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=244;

--
-- AUTO_INCREMENT for table `warehouse_transfers`
--
ALTER TABLE `warehouse_transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `distributor_shipments`
--
ALTER TABLE `distributor_shipments`
  ADD CONSTRAINT `distributor_shipments_ibfk_1` FOREIGN KEY (`shipment_id`) REFERENCES `outbound_shipments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `distributor_shipments_ibfk_2` FOREIGN KEY (`production_id`) REFERENCES `production_labels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `master_items`
--
ALTER TABLE `master_items`
  ADD CONSTRAINT `master_items_ibfk_1` FOREIGN KEY (`unit_id`) REFERENCES `master_units` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `master_quantities`
--
ALTER TABLE `master_quantities`
  ADD CONSTRAINT `master_quantities_ibfk_1` FOREIGN KEY (`machine_id`) REFERENCES `master_machines` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `master_sizes`
--
ALTER TABLE `master_sizes`
  ADD CONSTRAINT `master_sizes_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `master_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `outbound_shipment_batches`
--
ALTER TABLE `outbound_shipment_batches`
  ADD CONSTRAINT `outbound_shipment_batches_ibfk_1` FOREIGN KEY (`shipment_id`) REFERENCES `outbound_shipments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `outbound_shipment_batches_ibfk_2` FOREIGN KEY (`production_id`) REFERENCES `production_labels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `warehouse_items`
--
ALTER TABLE `warehouse_items`
  ADD CONSTRAINT `warehouse_items_ibfk_1` FOREIGN KEY (`production_id`) REFERENCES `production_labels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `warehouse_transfers`
--
ALTER TABLE `warehouse_transfers`
  ADD CONSTRAINT `warehouse_transfers_ibfk_1` FOREIGN KEY (`production_id`) REFERENCES `production_labels` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
