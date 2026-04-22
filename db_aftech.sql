-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 12, 2026 at 03:06 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4
SET
  SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

SET
  time_zone = "+00:00";

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
CREATE TABLE
  `activity_logs` (
    `id` int (11) NOT NULL,
    `action` varchar(100) DEFAULT NULL,
    `details` text DEFAULT NULL,
    `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--
INSERT INTO
  `activity_logs` (`id`, `action`, `details`, `timestamp`)
VALUES
  (
    1,
    'PENGIRIMAN',
    'Kirim 1 paket ke Rahmat (No. Resi #2-090320261208-1-RA)',
    '2026-03-09 04:08:43'
  ),
  (
    2,
    'PENGIRIMAN',
    'Tambah susulan 1 paket ke No. Resi #2-090320261208-2-RA',
    '2026-03-09 04:09:27'
  ),
  (
    3,
    'LOGIN',
    'User Administrator Utama (admin) berhasil login',
    '2026-03-09 04:16:58'
  ),
  (
    4,
    'TAMBAH',
    'Admin Update Template: SEDOTAN PCS - MACHINE 2',
    '2026-03-09 04:43:10'
  ),
  (
    5,
    'TAMBAH',
    'Admin Update Template: SEDOTAN 100 PCS - MACHINE 2',
    '2026-03-09 04:43:18'
  ),
  (
    6,
    'TAMBAH',
    'Admin Update Template: SEDOTAN 100PCS - MACHINE 2',
    '2026-03-09 04:43:31'
  ),
  (
    7,
    'LOGOUT',
    'User Administrator Utama (admin) keluar dari sistem',
    '2026-03-09 08:51:13'
  ),
  (
    8,
    'LOGIN',
    'User Staff Gudang (gudang) berhasil login',
    '2026-03-09 08:51:25'
  ),
  (
    9,
    'LOGOUT',
    'User Staff Gudang (gudang) keluar dari sistem',
    '2026-03-09 08:51:45'
  ),
  (
    10,
    'LOGIN',
    'User Tim Quality Control (qc) berhasil login',
    '2026-03-09 08:51:51'
  ),
  (
    11,
    'LOGOUT',
    'User Tim Quality Control (qc) keluar dari sistem',
    '2026-03-09 09:06:55'
  ),
  (
    12,
    'LOGIN',
    'User Administrator Utama (admin) berhasil login',
    '2026-03-09 09:07:03'
  ),
  (
    13,
    'LOGOUT',
    'User Administrator Utama (admin) keluar dari sistem',
    '2026-03-09 17:39:17'
  ),
  (
    14,
    'LOGIN',
    'User Administrator Utama (admin) berhasil login',
    '2026-03-09 17:44:39'
  ),
  (
    15,
    'LOGOUT',
    'User Administrator Utama (admin) keluar dari sistem',
    '2026-03-09 17:46:22'
  ),
  (
    16,
    'LOGIN',
    'User Administrator Utama (admin) berhasil login',
    '2026-03-09 17:46:39'
  ),
  (
    17,
    'HAPUS',
    'Hapus Stok Gudang Batch: #100326-1A-BOT-200-AHSI-600ML',
    '2026-03-09 18:37:56'
  ),
  (
    18,
    'HAPUS',
    'Hapus Stok Gudang Batch: #100326-1A-BOT-200-AHSI-600ML',
    '2026-03-09 18:41:46'
  ),
  (
    19,
    'TAMBAH',
    'Koreksi Data Produksi: Batch #100326-1A-BOT-200-AHSI-600ML (BOTOL)',
    '2026-03-09 19:17:12'
  ),
  (
    20,
    'TAMBAH',
    'Koreksi Data Produksi: Batch #100326-1A-BOT-200-AHSI-600ML (BOTOL)',
    '2026-03-09 19:17:38'
  ),
  (
    21,
    'TAMBAH',
    'Koreksi Data Produksi: Batch #100326-1A-BOT-200-AHSI-600ML (BOTOL)',
    '2026-03-09 19:18:52'
  ),
  (
    22,
    'TAMBAH',
    'Koreksi Data Produksi: Batch #100326-1A-BOT-200-AHSI-600ML (BOTOL)',
    '2026-03-09 19:19:46'
  ),
  (
    23,
    'TAMBAH',
    'Koreksi Data Produksi: Batch #100326-1A-BOT-200-AHSI-600ML (BOTOL)',
    '2026-03-09 19:20:04'
  ),
  (
    24,
    'TAMBAH',
    'Koreksi Data Produksi: Batch #100326-1A-BOT-200-AHSI-600ML (BOTOL)',
    '2026-03-09 19:22:28'
  ),
  (
    25,
    'EDIT',
    'Koreksi Data Produksi: Batch #100326-1A-BOT-200-AHSI-600ML (BOTOL)',
    '2026-03-10 02:16:19'
  ),
  (
    26,
    'EDIT',
    'Koreksi Data Produksi: Batch #100326-1A-BOT-200-AHSI-600ML (BOTOL)',
    '2026-03-10 02:16:39'
  ),
  (
    27,
    'EDIT',
    'Koreksi Data Produksi: Batch #100326-1A-BOT-200-AHSI-600ML (BOTOL)',
    '2026-03-10 02:20:38'
  ),
  (
    28,
    'HAPUS',
    'Batalkan Pengiriman No. Resi #1-090320261203-13-RA ke Rahmat',
    '2026-03-10 02:21:03'
  ),
  (
    29,
    'HAPUS',
    'Batalkan Pengiriman No. Resi #1-090320261208-2-RA ke Rahmat',
    '2026-03-10 02:21:05'
  ),
  (
    30,
    'PENGIRIMAN',
    'Kirim 25 paket ke Rahmat (No. Resi #1-100320261023-25-RA)',
    '2026-03-10 02:23:45'
  ),
  (
    31,
    'HAPUS',
    'Batalkan Pengiriman No. Resi #1-100320261023-25-RA ke Rahmat',
    '2026-03-10 02:31:58'
  ),
  (
    32,
    'PENGIRIMAN',
    'Kirim 10 paket ke Rahmat (No. Resi #1-100320261036-10-RA)',
    '2026-03-10 02:36:35'
  ),
  (
    33,
    'PENGIRIMAN',
    'Kirim 1 paket ke Rahmat (No. Resi #2-100320261038-1-RA)',
    '2026-03-10 02:38:20'
  ),
  (
    34,
    'PENGIRIMAN',
    'Kirim 1 paket ke Rahmat (No. Resi #3-100320261039-1-RA)',
    '2026-03-10 02:39:58'
  ),
  (
    35,
    'HAPUS',
    'Batalkan Pengiriman No. Resi #2-100320261038-1-RA ke Rahmat',
    '2026-03-10 02:41:24'
  ),
  (
    36,
    'HAPUS',
    'Batalkan Pengiriman No. Resi #2-100320261039-1-RA ke Rahmat',
    '2026-03-10 02:41:25'
  ),
  (
    37,
    'HAPUS',
    'Batalkan Pengiriman No. Resi #1-100320261036-10-RA ke Rahmat',
    '2026-03-10 02:41:27'
  ),
  (
    38,
    'PENGIRIMAN',
    'Kirim 1 paket ke Rahmat (No. Resi #1-100320261049-1-RA)',
    '2026-03-10 02:49:31'
  ),
  (
    39,
    'PENGIRIMAN',
    'Tambah susulan 1 paket ke No. Resi #1-100320261049-2-RA',
    '2026-03-10 02:50:02'
  ),
  (
    40,
    'HAPUS',
    'Batalkan Pengiriman No. Resi #1-100320261049-2-RA ke Rahmat',
    '2026-03-10 02:50:18'
  ),
  (
    41,
    'PENGIRIMAN',
    'Kirim 3 paket ke Rahmat (No. Resi #1-100320261103-3-RA)',
    '2026-03-10 03:03:15'
  ),
  (
    42,
    'HAPUS',
    'Hapus Item: TESTING',
    '2026-03-10 03:34:27'
  ),
  (
    43,
    'PRODUKSI',
    '[SM-A556E] Batch #100326-1A-CUP-100-AHSI-120ML (CUP): +1 Label',
    '2026-03-10 03:37:52'
  ),
  (
    44,
    'PENGIRIMAN',
    'Kirim 3 paket ke Rahmat (No. Resi #2-100320261143-3-RA)',
    '2026-03-10 03:43:01'
  ),
  (
    45,
    'TAMBAH',
    'Admin Update Template: Mamat',
    '2026-03-10 03:54:46'
  ),
  (
    46,
    'EDIT',
    'Admin Update Template: Mamat update',
    '2026-03-10 03:55:31'
  ),
  (
    47,
    'TAMBAH',
    'Admin Update Item: TESTING',
    '2026-03-10 03:56:18'
  ),
  (
    48,
    'EDIT',
    'Admin Update Item: TESTING UPDATE',
    '2026-03-10 04:01:32'
  ),
  (
    49,
    'TAMBAH',
    'Admin Update Template: RAHMAT',
    '2026-03-10 04:05:21'
  ),
  (
    50,
    'EDIT',
    'Admin Update Template: RAHMAT UPDATE',
    '2026-03-10 04:06:00'
  ),
  (
    51,
    'SYNC',
    '[SM-A556E] Batch #100326-2A-BOT-2400-AHSI-50ML (BOTOL): +0 Label',
    '2026-03-10 04:06:41'
  ),
  (
    52,
    'PRODUKSI',
    '[SM-A556E] Batch #100326-2A-BOT-2400-AHSI-50ML (BOTOL): +1 Label',
    '2026-03-10 04:37:11'
  ),
  (
    53,
    'PRODUKSI',
    '[SM-A556E] Batch #100326-1B-CUP-48-MAYU-240ML (CUP): +1 Label',
    '2026-03-10 04:42:37'
  ),
  (
    54,
    'PRODUKSI',
    '[SM-A556E] Batch #100326-1B-CUP-48-MAYU-240ML (CUP): +1 Label',
    '2026-03-10 04:45:15'
  ),
  (
    55,
    'PRODUKSI',
    '[SM-A556E] Batch #100326-1B-CUP-48-MAYU-240ML (CUP): +1 Label',
    '2026-03-10 04:46:19'
  ),
  (
    56,
    'TAMBAH',
    'Admin Update Template: SEDOTAN PCS - MACHINE 2',
    '2026-03-10 05:30:21'
  ),
  (
    57,
    'EDIT',
    'Admin Update Template: RAHMAT UPDATE',
    '2026-03-10 05:30:35'
  ),
  (
    58,
    'EDIT',
    'Admin Update Template: RAHMAT UPDATE',
    '2026-03-10 05:30:36'
  ),
  (
    59,
    'EDIT',
    'Admin Update Template: RAHMAT UPDATE',
    '2026-03-10 05:30:45'
  ),
  (
    60,
    'EDIT',
    'Admin Update Template: RAHMAT UPDATE',
    '2026-03-10 05:30:45'
  ),
  (
    61,
    'EDIT',
    'Admin Update Template: RAHMAT UPDATE',
    '2026-03-10 05:30:46'
  ),
  (
    62,
    'EDIT',
    'Admin Update Template: RAHMAT UPDATE',
    '2026-03-10 05:30:47'
  ),
  (
    63,
    'EDIT',
    'Admin Update Template: RAHMAT UPDATE',
    '2026-03-10 05:30:47'
  ),
  (
    64,
    'EDIT',
    'Admin Update Template: RAHMAT UPDATE',
    '2026-03-10 05:30:47'
  ),
  (
    65,
    'EDIT',
    'Admin Update Template: RAHMAT UPDATE',
    '2026-03-10 05:30:48'
  ),
  (
    66,
    'EDIT',
    'Admin Update Template: RAHMAT UPDATE',
    '2026-03-10 05:30:48'
  ),
  (
    67,
    'EDIT',
    'Admin Update Template: RAHMAT UPDATE',
    '2026-03-10 05:30:48'
  ),
  (
    68,
    'EDIT',
    'Admin Update Template: RAHMAT UPDATE',
    '2026-03-10 05:30:48'
  ),
  (
    69,
    'EDIT',
    'Admin Update Template: RAHMAT UPDATE',
    '2026-03-10 05:30:48'
  ),
  (
    70,
    'EDIT',
    'Admin Update Template: RAHMAT UPDATE',
    '2026-03-10 05:35:29'
  ),
  (
    71,
    'TAMBAH',
    'Admin Update Template: RAHMAT UPDATE jj',
    '2026-03-10 05:35:49'
  ),
  (
    72,
    'EDIT',
    'Admin Update Template: RAHMAT UPDATE tt',
    '2026-03-10 05:37:36'
  ),
  (
    73,
    'EDIT',
    'Admin Update Template: RAHMAT UPDATE jj gg',
    '2026-03-10 05:37:46'
  ),
  (
    74,
    'EDIT',
    'Admin Update Item: TESTING UPDATE hh',
    '2026-03-10 05:37:57'
  ),
  (
    75,
    'LOGIN',
    'User Administrator Utama (admin) berhasil login',
    '2026-03-10 06:31:16'
  ),
  (
    76,
    'SYNC',
    '[Infinix X6871] Batch #100326-1A-BOT-200-WAWA-600ML (BOTOL): +0 Label',
    '2026-03-10 06:35:18'
  ),
  (
    77,
    'LOGIN',
    'User Administrator Utama (admin) berhasil login',
    '2026-03-10 07:00:30'
  ),
  (
    78,
    'LOGIN',
    'User Administrator Utama (admin) berhasil login',
    '2026-03-10 07:17:16'
  ),
  (
    79,
    'LOGOUT',
    'User Administrator Utama (admin) keluar dari sistem',
    '2026-03-10 13:56:00'
  ),
  (
    80,
    'LOGIN',
    'User Administrator Utama (admin) berhasil login',
    '2026-03-10 13:56:42'
  ),
  (
    81,
    'LOGOUT',
    'User Administrator Utama (admin) keluar dari sistem',
    '2026-03-10 13:56:53'
  ),
  (
    82,
    'LOGIN',
    'User Tim Quality Control (qc) berhasil login',
    '2026-03-10 13:57:01'
  ),
  (
    83,
    'LOGOUT',
    'User Tim Quality Control (qc) keluar dari sistem',
    '2026-03-10 13:57:10'
  ),
  (
    84,
    'LOGIN',
    'User Staff Gudang (gudang) berhasil login',
    '2026-03-10 13:57:32'
  ),
  (
    85,
    'LOGOUT',
    'User Staff Gudang (gudang) keluar dari sistem',
    '2026-03-10 13:57:44'
  ),
  (
    86,
    'LOGIN',
    'User Administrator Utama (admin) berhasil login',
    '2026-03-10 13:57:53'
  ),
  (
    87,
    'HAPUS',
    'Hapus Stok Gudang Batch: #100326-2A-BOT-2400-AHSI-50ML',
    '2026-03-10 13:59:11'
  ),
  (
    88,
    'HAPUS',
    'Hapus Stok Gudang Batch: #100326-1B-CUP-48-MAYU-240ML',
    '2026-03-10 13:59:14'
  ),
  (
    89,
    'HAPUS',
    'Hapus Stok Gudang Batch: #100326-1B-CUP-48-AHSI-240ML',
    '2026-03-10 13:59:20'
  ),
  (
    90,
    'HAPUS',
    'Batalkan Pengiriman No. Resi #2-100320261143-3-RA ke Rahmat',
    '2026-03-10 14:00:08'
  ),
  (
    91,
    'HAPUS',
    'Batalkan Pengiriman No. Resi #1-100320261103-3-RA ke Rahmat',
    '2026-03-10 14:00:11'
  ),
  (
    92,
    'HAPUS',
    'Hapus Stok Gudang Batch: #100326-1A-BOT-200-AHSI-1000ML',
    '2026-03-10 14:00:17'
  ),
  (
    93,
    'HAPUS',
    'Hapus Stok Gudang Batch: #100326-1A-CUP-100-AHSI-120ML',
    '2026-03-10 14:00:19'
  ),
  (
    94,
    'HAPUS',
    'Hapus Stok Gudang Batch: #100326-1A-BOT-200-AHSI-600ML',
    '2026-03-10 14:00:21'
  ),
  (
    95,
    'HAPUS',
    'Hapus Stok Gudang Batch: #080326-1A-BOT-500-RAMA-330ML',
    '2026-03-10 14:00:23'
  ),
  (
    96,
    'HAPUS',
    'Hapus Stok Gudang Batch: #080326-01C-SED-1000-RAMA-100PCS',
    '2026-03-10 14:00:25'
  ),
  (
    97,
    'HAPUS',
    'Hapus Stok Gudang Batch: #090326-2A-SED-2400-FAFA-1PCS',
    '2026-03-10 14:00:27'
  ),
  (
    98,
    'HAPUS',
    'Hapus Stok Gudang Batch: #090326-1A-CUP-100-FARA-120ML',
    '2026-03-10 14:00:29'
  ),
  (
    99,
    'HAPUS',
    'Hapus Stok Gudang Batch: #090326-1A-CUP-100-FAFA-120ML',
    '2026-03-10 14:00:31'
  ),
  (
    100,
    'HAPUS',
    'Hapus Stok Gudang Batch: #090326-1B-CUP-48-FARA-240ML',
    '2026-03-10 14:00:33'
  ),
  (
    101,
    'LOGIN',
    'User Administrator Utama (admin) berhasil login',
    '2026-03-10 14:03:18'
  ),
  (
    102,
    'PENGIRIMAN',
    'Kirim 2 paket ke Tri Munazzar Abduh (No. Resi #1-100320262204-2-TM)',
    '2026-03-10 14:04:41'
  ),
  (
    103,
    'HAPUS',
    'Hapus Stok Gudang Batch: #080326-01C-SED-1000-RAMA-100PCS',
    '2026-03-10 14:06:14'
  ),
  (
    104,
    'HAPUS',
    'Hapus Stok Gudang Batch: #080326-1A-BOT-500-RAMA-330ML',
    '2026-03-10 14:06:17'
  ),
  (
    105,
    'HAPUS',
    'Hapus Stok Gudang Batch: #090326-1A-CUP-100-FARA-120ML',
    '2026-03-10 14:06:19'
  ),
  (
    106,
    'PENGIRIMAN',
    'Kirim 1 paket ke Rahmat (No. Resi #2-100320262218-1-RA)',
    '2026-03-10 14:18:46'
  ),
  (
    107,
    'HAPUS',
    'Batalkan Pengiriman No. Resi #2-100320262218-1-RA ke Rahmat',
    '2026-03-10 14:46:12'
  ),
  (
    108,
    'HAPUS',
    'Batalkan Pengiriman No. Resi #1-100320262204-2-TM ke Tri Munazzar Abduh',
    '2026-03-10 14:46:15'
  ),
  (
    109,
    'HAPUS',
    'Hapus Stok Gudang Batch: #080326-01C-SED-1000-RAMA-100PCS',
    '2026-03-10 14:49:37'
  ),
  (
    110,
    'HAPUS',
    'Hapus Stok Gudang Batch: #090326-1A-CUP-100-FARA-120ML',
    '2026-03-10 14:49:38'
  ),
  (
    111,
    'HAPUS',
    'Hapus Stok Gudang Batch: #080326-1A-BOT-500-RAMA-330ML',
    '2026-03-10 14:49:40'
  ),
  (
    112,
    'PENGIRIMAN',
    'Kirim 7 paket ke Rahmat (No. Resi #1-100320262253-7-RA)',
    '2026-03-10 14:53:36'
  ),
  (
    113,
    'TAMBAH',
    'Admin Update Size: 1000',
    '2026-03-11 04:46:15'
  ),
  (
    114,
    'HAPUS',
    'Hapus Size: 1000',
    '2026-03-11 04:46:21'
  ),
  (
    115,
    'TAMBAH',
    'Admin Update Size: 100',
    '2026-03-11 04:46:29'
  ),
  (
    116,
    'LOGIN',
    'User Administrator Utama (admin) berhasil login',
    '2026-03-11 05:25:51'
  ),
  (
    117,
    'HAPUS',
    'Hapus Item: TESTING UPDATE hh',
    '2026-03-11 05:52:12'
  ),
  (
    118,
    'LOGIN',
    'User Administrator Utama (admin) berhasil login',
    '2026-03-11 08:52:19'
  ),
  (
    119,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 08:56:26'
  ),
  (
    120,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E',
    '2026-03-11 08:56:49'
  ),
  (
    121,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 08:57:49'
  ),
  (
    122,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E',
    '2026-03-11 09:00:28'
  ),
  (
    123,
    'SYNC',
    '[SM-A556E] Batch #110326-1A-BOT-200-HADI-600ML (BOTOL): +0 Label',
    '2026-03-11 09:00:51'
  ),
  (
    124,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 09:09:43'
  ),
  (
    125,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E',
    '2026-03-11 09:19:22'
  ),
  (
    126,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 09:31:20'
  ),
  (
    127,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E',
    '2026-03-11 09:32:16'
  ),
  (
    128,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 09:38:50'
  ),
  (
    129,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 09:39:55'
  ),
  (
    130,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E',
    '2026-03-11 09:47:41'
  ),
  (
    131,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 09:55:10'
  ),
  (
    132,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E',
    '2026-03-11 09:56:43'
  ),
  (
    133,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 10:04:53'
  ),
  (
    134,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E',
    '2026-03-11 10:06:46'
  ),
  (
    135,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 11:08:06'
  ),
  (
    136,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 11:13:08'
  ),
  (
    137,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E',
    '2026-03-11 11:15:14'
  ),
  (
    138,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 11:21:32'
  ),
  (
    139,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: Infinix X6871',
    '2026-03-11 11:29:26'
  ),
  (
    140,
    'HAPUS',
    'Hapus Api_key: Infinix X6871',
    '2026-03-11 11:31:09'
  ),
  (
    141,
    'HAPUS',
    'Hapus Api_key: Infinix X6871',
    '2026-03-11 11:31:27'
  ),
  (
    142,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E',
    '2026-03-11 11:31:59'
  ),
  (
    143,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 11:33:14'
  ),
  (
    144,
    'TAMBAH',
    'Admin Generate API Key untuk: halo',
    '2026-03-11 11:52:15'
  ),
  (
    145,
    'HAPUS',
    'Hapus Api_key: halo',
    '2026-03-11 11:52:23'
  ),
  (
    146,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E',
    '2026-03-11 11:53:19'
  ),
  (
    147,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 11:54:38'
  ),
  (
    148,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E',
    '2026-03-11 12:03:14'
  ),
  (
    149,
    'SYNC',
    '[SM-A556E] Batch #110326-1B-CUP-48-FXVV-240ML (CUP): +0 Label',
    '2026-03-11 12:03:50'
  ),
  (
    150,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 12:23:51'
  ),
  (
    151,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 1001)',
    '2026-03-11 12:24:32'
  ),
  (
    152,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 12:28:10'
  ),
  (
    153,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 6090)',
    '2026-03-11 12:28:54'
  ),
  (
    154,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 12:33:57'
  ),
  (
    155,
    'LOGIN',
    'User Administrator Utama (admin) berhasil login',
    '2026-03-11 12:57:28'
  ),
  (
    156,
    'LOGOUT',
    'User Administrator Utama (admin) keluar dari sistem',
    '2026-03-11 13:02:11'
  ),
  (
    157,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 0503)',
    '2026-03-11 13:13:15'
  ),
  (
    158,
    'EDIT',
    'Koreksi Data Produksi: Batch #110326-1A-BOT-200-HADI-600ML ()',
    '2026-03-11 13:22:18'
  ),
  (
    159,
    'EDIT',
    'Koreksi Data Produksi: Batch #110326-1A-BOT-200-HADI-600ML ()',
    '2026-03-11 13:22:19'
  ),
  (
    160,
    'EDIT',
    'Koreksi Data Produksi: Batch #110326-1A-BOT-200-HADI-600ML ()',
    '2026-03-11 13:22:20'
  ),
  (
    161,
    'EDIT',
    'Koreksi Data Produksi: Batch #110326-1A-BOT-200-HADI-600ML ()',
    '2026-03-11 13:22:24'
  ),
  (
    162,
    'HAPUS',
    'Hapus Produksi Batch: #110326-1A-BOT-200-HADI-600ML',
    '2026-03-11 13:22:30'
  ),
  (
    163,
    'SYNC',
    '[SM-A556E] Batch #110326-1A-BOT-1200-HSVJ-600ML (BOTOL): +0 Label',
    '2026-03-11 13:30:08'
  ),
  (
    164,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 13:34:48'
  ),
  (
    165,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 0503)',
    '2026-03-11 13:35:39'
  ),
  (
    166,
    'SYNC',
    '[SM-A556E] Batch #110326-2A-BOT-2400-DIHA-50ML (BOTOL): +0 Label',
    '2026-03-11 13:51:02'
  ),
  (
    167,
    'PRODUKSI',
    '[SM-A556E] Batch #110326-2A-BOT-2400-DIHA-50ML (BOTOL): +1 Label',
    '2026-03-11 13:54:17'
  ),
  (
    168,
    'PRODUKSI',
    '[SM-A556E] Batch #110326-1A-BOT-1200-TUOF-600ML (BOTOL): +1 Label',
    '2026-03-11 14:03:04'
  ),
  (
    169,
    'PRODUKSI',
    '[SM-A556E] Batch #110326-1A-BOT-200-FFGV-600ML (BOTOL): +1 Label',
    '2026-03-11 14:03:05'
  ),
  (
    170,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 14:19:14'
  ),
  (
    171,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 0503)',
    '2026-03-11 14:29:51'
  ),
  (
    172,
    'SYNC',
    '[SM-A556E] Batch #110326-1A-BOT-200-YHHH-600ML (BOTOL): +0 Label',
    '2026-03-11 14:33:09'
  ),
  (
    173,
    'PRODUKSI',
    '[SM-A556E] Batch #110326-1A-BOT-1200-TUOF-600ML (BOTOL): +1 Label',
    '2026-03-11 14:35:40'
  ),
  (
    174,
    'SYNC',
    '[SM-A556E] Batch #110326-01C-CUP-1500-CCHH-25KG (CUP): +0 Label',
    '2026-03-11 14:37:54'
  ),
  (
    175,
    'PRODUKSI',
    '[Unknown] Batch #110326-1A-BOT-1200-TUOF-600ML (BOTOL): +1 Label',
    '2026-03-11 14:38:30'
  ),
  (
    176,
    'HAPUS',
    'Hapus Produksi Batch: #110326-1A-BOT-1200-TUOF-600ML',
    '2026-03-11 14:39:46'
  ),
  (
    177,
    'PRODUKSI',
    '[Unknown] Batch #110326-1A-BOT-1200-TUOF-600ML (BOTOL): +1 Label',
    '2026-03-11 14:40:52'
  ),
  (
    178,
    'HAPUS',
    'Hapus Produksi Batch: #110326-1A-BOT-1200-TUOF-600ML',
    '2026-03-11 14:49:51'
  ),
  (
    179,
    'PRODUKSI',
    '[SM-A556E] Batch #110326-1A-CUP-100-RARA-120ML (CUP): +1 Label',
    '2026-03-11 14:51:21'
  ),
  (
    180,
    'PRODUKSI',
    '[SM-A556E] Batch #110326-1A-CUP-100-RARA-120ML (CUP): +3 Label',
    '2026-03-11 14:51:40'
  ),
  (
    181,
    'HAPUS',
    'Hapus Produksi Batch: #110326-1A-CUP-100-RARA-120ML',
    '2026-03-11 14:51:51'
  ),
  (
    182,
    'PRODUKSI',
    '[SM-A556E] Batch #110326-2A-BOT-2400-OFOF-50ML (BOTOL): +1 Label',
    '2026-03-11 14:56:45'
  ),
  (
    183,
    'PRODUKSI',
    '[SM-A556E] Batch #110326-01C-CUP-1500-CCHH-25KG (CUP): +1 Label',
    '2026-03-11 14:57:42'
  ),
  (
    184,
    'EDIT',
    'Admin Update PIN Reset Perangkat: SM-A556E menjadi 1010',
    '2026-03-11 15:02:07'
  ),
  (
    185,
    'EDIT',
    'Admin Update PIN Reset Perangkat: SM-A556E menjadi 1111',
    '2026-03-11 16:15:07'
  ),
  (
    186,
    'PRODUKSI',
    '[Unknown] Batch #110326-2A-BOT-2400-DIHA-50ML (BOTOL): +1 Label',
    '2026-03-11 16:23:03'
  ),
  (
    187,
    'PRODUKSI',
    '[Unknown] Batch #110326-2A-BOT-2400-DIHA-50ML (BOTOL): +1 Label',
    '2026-03-11 16:23:14'
  ),
  (
    188,
    'PRODUKSI',
    '[Unknown] Batch #110326-2A-BOT-2400-DIHA-50ML (BOTOL): +1 Label',
    '2026-03-11 16:23:50'
  ),
  (
    189,
    'PRODUKSI',
    '[Unknown] Batch #110326-2A-BOT-2400-DIHA-50ML (BOTOL): +1 Label',
    '2026-03-11 16:26:17'
  ),
  (
    190,
    'TAMBAH ANTREAN',
    '[SM-A556E] Menambah antrean baru: #120326-1A-BOT-200-GYGF-600ML (BOTOL) sebanyak 1 label.',
    '2026-03-11 16:30:05'
  ),
  (
    191,
    'PRODUKSI',
    '[SM-A556E] Batch #120326-1A-BOT-200-GYGF-600ML (BOTOL): +1 Label',
    '2026-03-11 16:30:21'
  ),
  (
    192,
    'PRODUKSI',
    '[SM-A556E] Batch #120326-1A-BOT-200-GYGF-600ML (BOTOL): +1 Label',
    '2026-03-11 16:35:29'
  ),
  (
    193,
    'PRODUKSI',
    '[Unknown] Batch #120326-1A-BOT-200-GYGF-600ML (BOTOL): +1 Label',
    '2026-03-11 16:39:29'
  ),
  (
    194,
    'PRODUKSI',
    '[Unknown] Batch #120326-1A-BOT-200-GYGF-600ML (BOTOL): +1 Label',
    '2026-03-11 16:54:13'
  ),
  (
    195,
    'RESET DATABASE',
    '[SM-A556E] Operator melakukan pembersihan total data lokal di perangkat ini.',
    '2026-03-11 17:00:08'
  ),
  (
    196,
    'RESET DATABASE',
    '[SM-A556E] Operator melakukan pembersihan total data lokal di perangkat ini.',
    '2026-03-11 17:00:20'
  ),
  (
    197,
    'RESET DATABASE',
    '[SM-A556E] Operator melakukan pembersihan total data lokal di perangkat ini.',
    '2026-03-11 17:03:08'
  ),
  (
    198,
    'TAMBAH ANTREAN',
    '[SM-A556E] Menambah antrean baru: #120326-1A-BOT-1200-RRRR-600ML (BOTOL) sebanyak 1 label.',
    '2026-03-11 17:03:55'
  ),
  (
    199,
    'PRODUKSI',
    '[SM-A556E] Batch #120326-1A-BOT-1200-RRRR-600ML (BOTOL): +1 Label',
    '2026-03-11 17:04:21'
  ),
  (
    200,
    'PRODUKSI',
    '[SM-A556E] Batch #120326-1A-BOT-1200-RRRR-600ML (BOTOL): +1 Label',
    '2026-03-11 17:04:33'
  ),
  (
    201,
    'PRODUKSI',
    '[SM-A556E] Batch #120326-1A-BOT-1200-RRRR-600ML (BOTOL): +1 Label',
    '2026-03-11 17:04:57'
  ),
  (
    202,
    'PRODUKSI',
    '[SM-A556E] Batch #120326-1A-BOT-1200-RRRR-600ML (BOTOL): +2 Label',
    '2026-03-11 17:05:15'
  ),
  (
    203,
    'PRODUKSI',
    '[SM-A556E] Batch #120326-1A-BOT-1200-RRRR-600ML (BOTOL): +1 Label',
    '2026-03-11 17:10:30'
  ),
  (
    204,
    'PRODUKSI',
    '[SM-A556E] Batch #120326-1A-BOT-1200-RRRR-600ML (BOTOL): +1 Label',
    '2026-03-11 17:16:11'
  ),
  (
    205,
    'SINKRON CLOUD',
    '[SM-A556E] Batch 120326-1A-BOT-1200-RRRR-600ML: Database: +1 Dus (Total: 7)',
    '2026-03-11 17:16:11'
  ),
  (
    206,
    'PRODUKSI',
    '[SM-A556E] Batch #120326-1A-BOT-1200-RRRR-600ML (BOTOL): +1 Label',
    '2026-03-11 17:16:30'
  ),
  (
    207,
    'SINKRON CLOUD',
    '[SM-A556E] Batch 120326-1A-BOT-1200-RRRR-600ML: Database: +1 Dus (Total: 8)',
    '2026-03-11 17:16:31'
  ),
  (
    208,
    'RESET DATABASE',
    '[SM-A556E] Operator melakukan pembersihan total data lokal di perangkat ini.',
    '2026-03-11 17:17:07'
  ),
  (
    209,
    'PRODUKSI',
    '[Unknown] Batch #120326-1A-BOT-200-GYGF-600ML (BOTOL): +1 Label',
    '2026-03-11 17:17:25'
  ),
  (
    210,
    'SINKRON CLOUD',
    '[SM-A556E] Batch 120326-1A-BOT-200-GYGF-600ML: Database: +1 Dus (Total: 5)',
    '2026-03-11 17:17:25'
  ),
  (
    211,
    'GANTI RUSAK',
    '[SM-A556E] Batch 120326-1A-BOT-200-GYGF-600ML: no 5-5',
    '2026-03-11 17:18:12'
  ),
  (
    212,
    'PRODUKSI',
    '[SM-A556E] Batch #120326-1A-BOT-200-GYGF-600ML (BOTOL): +1 Label',
    '2026-03-11 17:18:44'
  ),
  (
    213,
    'UPDATE PRODUKSI',
    '[SM-A556E] Batch 120326-1A-BOT-200-GYGF-600ML: Selesai mencetak tambahan: +1 Dus (Total: 6)',
    '2026-03-11 17:18:44'
  ),
  (
    214,
    'SINKRON CLOUD',
    '[SM-A556E] Batch 120326-1A-BOT-200-GYGF-600ML: Database: +1 Dus (Total: 7)',
    '2026-03-11 17:21:04'
  ),
  (
    215,
    'RESET DATABASE',
    '[SM-A556E] Operator melakukan pembersihan total data lokal di perangkat ini.',
    '2026-03-11 17:22:37'
  ),
  (
    216,
    'SINKRON CLOUD',
    '[SM-A556E] Batch 120326-1A-BOT-200-GYGF-600ML: Database: +1 Dus (Total: 8)',
    '2026-03-11 17:22:56'
  ),
  (
    217,
    'UPDATE PRODUKSI',
    '[SM-A556E] Batch 120326-1A-BOT-200-GYGF-600ML: Selesai mencetak tambahan: +1 Dus (Total: 9)',
    '2026-03-11 17:23:39'
  ),
  (
    218,
    'GANTI RUSAK',
    '[SM-A556E] Batch 120326-1A-BOT-200-GYGF-600ML: no 8-8',
    '2026-03-11 17:24:14'
  ),
  (
    219,
    'UPDATE PRODUKSI',
    '[SM-A556E] Batch 120326-1A-BOT-1200-RRRR-600ML: Selesai mencetak tambahan: +1 Dus (Total: 9)',
    '2026-03-11 17:25:18'
  ),
  (
    220,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 17:26:30'
  ),
  (
    221,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 8989)',
    '2026-03-11 17:31:08'
  ),
  (
    222,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 17:32:41'
  ),
  (
    223,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 0503)',
    '2026-03-11 17:33:47'
  ),
  (
    224,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 17:37:40'
  ),
  (
    225,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 0503)',
    '2026-03-11 17:41:41'
  ),
  (
    226,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 17:41:53'
  ),
  (
    227,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 0503)',
    '2026-03-11 17:43:05'
  ),
  (
    228,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 17:43:17'
  ),
  (
    229,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 0503)',
    '2026-03-11 17:48:08'
  ),
  (
    230,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 17:48:23'
  ),
  (
    231,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 0503)',
    '2026-03-11 17:52:42'
  ),
  (
    232,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 17:53:06'
  ),
  (
    233,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 0503)',
    '2026-03-11 17:54:08'
  ),
  (
    234,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 17:55:47'
  ),
  (
    235,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 0503)',
    '2026-03-11 17:59:42'
  ),
  (
    236,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 18:00:01'
  ),
  (
    237,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 0503)',
    '2026-03-11 18:06:04'
  ),
  (
    238,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 18:07:07'
  ),
  (
    239,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 0503)',
    '2026-03-11 18:11:41'
  ),
  (
    240,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 18:17:21'
  ),
  (
    241,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 0503)',
    '2026-03-11 18:21:13'
  ),
  (
    242,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 18:22:28'
  ),
  (
    243,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 0503)',
    '2026-03-11 18:23:45'
  ),
  (
    244,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 18:26:37'
  ),
  (
    245,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 0503)',
    '2026-03-11 18:27:21'
  ),
  (
    246,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 18:27:34'
  ),
  (
    247,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 0503)',
    '2026-03-11 18:32:47'
  ),
  (
    248,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 18:33:45'
  ),
  (
    249,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 0503)',
    '2026-03-11 18:34:20'
  ),
  (
    250,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 18:34:30'
  ),
  (
    251,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 0503)',
    '2026-03-11 18:39:38'
  ),
  (
    252,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 18:40:05'
  ),
  (
    253,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 0503)',
    '2026-03-11 18:41:09'
  ),
  (
    254,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 18:42:02'
  ),
  (
    255,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 0503)',
    '2026-03-11 18:43:31'
  ),
  (
    256,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 18:43:35'
  ),
  (
    257,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 0503)',
    '2026-03-11 18:45:26'
  ),
  (
    258,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 18:45:30'
  ),
  (
    259,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 0503)',
    '2026-03-11 18:52:28'
  ),
  (
    260,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 18:52:41'
  ),
  (
    261,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 0503)',
    '2026-03-11 18:53:08'
  ),
  (
    262,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 18:53:33'
  ),
  (
    263,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 0503)',
    '2026-03-11 18:55:31'
  ),
  (
    264,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 18:55:42'
  ),
  (
    265,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 0503)',
    '2026-03-11 18:59:53'
  ),
  (
    266,
    'HAPUS',
    'Hapus Api_key: SM-A556E',
    '2026-03-11 19:00:06'
  ),
  (
    267,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A556E (PIN Reset: 0503)',
    '2026-03-11 19:02:13'
  ),
  (
    268,
    'RESET DATABASE',
    '[SM-A556E] Operator melakukan pembersihan total data lokal di perangkat ini.',
    '2026-03-11 19:02:47'
  ),
  (
    269,
    'EDIT',
    'Admin Update Mesin: THERMO 3 (active)',
    '2026-03-11 19:34:35'
  ),
  (
    270,
    'EDIT',
    'Admin Update Mesin: THERMO 3 (maintenance)',
    '2026-03-11 19:34:41'
  ),
  (
    271,
    'HAPUS',
    'Hapus Template: Mamat update',
    '2026-03-11 19:35:11'
  ),
  (
    272,
    'HAPUS',
    'Hapus Template: SEDOTAN 100PCS - MACHINE 2',
    '2026-03-11 19:35:14'
  ),
  (
    273,
    'HAPUS',
    'Hapus Template: RAHMAT UPDATE tt',
    '2026-03-11 19:35:18'
  ),
  (
    274,
    'HAPUS',
    'Hapus Template: RAHMAT UPDATE jj gg',
    '2026-03-11 19:35:23'
  ),
  (
    275,
    'TAMBAH',
    'Admin Update Item: testing',
    '2026-03-11 19:35:58'
  ),
  (
    276,
    'HAPUS',
    'Hapus Item: testing',
    '2026-03-11 19:36:10'
  ),
  (
    277,
    'EDIT',
    'Admin Update PIN Reset Perangkat: SM-A556E menjadi 1111',
    '2026-03-11 19:39:21'
  ),
  (
    278,
    'RESET DATABASE',
    '[SM-A556E] Operator melakukan pembersihan total data lokal di perangkat ini.',
    '2026-03-11 19:39:39'
  ),
  (
    279,
    'EDIT',
    'Admin Update User: mamang',
    '2026-03-11 19:45:06'
  ),
  (
    280,
    'EDIT',
    'Admin Update User: mumu',
    '2026-03-11 19:47:08'
  ),
  (
    281,
    'TAMBAH',
    'Admin Update User: coki',
    '2026-03-11 19:47:26'
  ),
  (
    282,
    'EDIT',
    'Admin Update Customer: Rahmat ramadhan',
    '2026-03-11 19:49:23'
  ),
  (
    283,
    'TAMBAH',
    'Admin Update Customer: tes',
    '2026-03-11 19:49:38'
  ),
  (
    284,
    'HAPUS',
    'Hapus Customer: tes',
    '2026-03-11 19:49:47'
  ),
  (
    285,
    'TAMBAH',
    'Admin Update Item: testing',
    '2026-03-11 19:49:57'
  ),
  (
    286,
    'TAMBAH',
    'Admin Update Size: 1000',
    '2026-03-11 19:50:42'
  ),
  (
    287,
    'HAPUS',
    'Hapus Item: testing',
    '2026-03-11 19:50:47'
  ),
  (
    288,
    'TAMBAH',
    'Admin Update Mesin: TESTING (active)',
    '2026-03-11 19:50:57'
  ),
  (
    289,
    'HAPUS',
    'Hapus Machine: TESTING',
    '2026-03-11 19:51:13'
  ),
  (
    290,
    'TAMBAH',
    'Admin Update Unit: TESTING',
    '2026-03-11 19:51:20'
  ),
  (
    291,
    'EDIT',
    'Admin Update Unit: TESTING nkln',
    '2026-03-11 19:51:26'
  ),
  (
    292,
    'HAPUS',
    'Hapus Unit: TESTING nkln',
    '2026-03-11 19:51:30'
  ),
  (
    293,
    'TAMBAH',
    'Admin Update Shift: TESTING',
    '2026-03-11 19:52:02'
  ),
  (
    294,
    'EDIT',
    'Admin Update Shift: TESTING UPDATE',
    '2026-03-11 19:52:14'
  ),
  (
    295,
    'HAPUS',
    'Hapus Shift: TESTING UPDATE',
    '2026-03-11 19:52:18'
  ),
  (
    296,
    'TAMBAH',
    'Admin Update Template: RAHMAT UPDATE',
    '2026-03-11 19:52:44'
  ),
  (
    297,
    'LOGIN',
    'User Administrator Utama (admin) berhasil login',
    '2026-03-11 19:59:05'
  ),
  (
    298,
    'LOGIN',
    'User mamang (gudang) berhasil login',
    '2026-03-11 20:00:00'
  ),
  (
    299,
    'EDIT',
    'Admin Memperbarui Hak Akses Role (Permissions)',
    '2026-03-11 20:00:50'
  ),
  (
    300,
    'EDIT',
    'Admin Memperbarui Hak Akses Role (Permissions)',
    '2026-03-11 20:01:13'
  ),
  (
    301,
    'EDIT',
    'Admin Memperbarui Hak Akses Role (Permissions)',
    '2026-03-11 20:01:19'
  ),
  (
    302,
    'EDIT',
    'Admin Memperbarui Hak Akses Role (Permissions)',
    '2026-03-11 20:13:27'
  ),
  (
    303,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A057F (PIN Reset: 7777)',
    '2026-03-11 20:17:28'
  ),
  (
    304,
    'TAMBAH ANTREAN',
    '[SM-A057F] Menambah antrean baru: #120326-1A-BOT-1200-JOPR-600ML (BOTOL) sebanyak 1 label.',
    '2026-03-11 20:19:42'
  ),
  (
    305,
    'PRODUKSI PERDANA',
    '[SM-A057F] Batch 120326-1A-BOT-1200-JOPR-600ML: Mencetak perdana: 1 Dus',
    '2026-03-11 20:19:49'
  ),
  (
    306,
    'HAPUS',
    'Hapus Api_key: SM-A057F',
    '2026-03-11 20:20:34'
  ),
  (
    307,
    'SETUJU',
    'Admin Menyetujui Akses Perangkat: SM-A057F (PIN Reset: 0503)',
    '2026-03-11 20:20:47'
  ),
  (
    308,
    'EDIT',
    'Admin Update PIN Reset Perangkat: SM-A057F menjadi 7777',
    '2026-03-11 20:20:56'
  ),
  (
    309,
    'RESET DATABASE',
    '[SM-A057F] Operator melakukan pembersihan total data lokal di perangkat ini.',
    '2026-03-11 20:21:21'
  ),
  (
    310,
    'EDIT',
    'Koreksi Data Produksi: Batch #120326-1A-BOT-1200-JOPR-600ML (BOTOL)',
    '2026-03-11 22:00:59'
  ),
  (
    311,
    'EDIT',
    'Koreksi Data Produksi: Batch #120326-1A-BOT-1200-JOPR-600ML (BOTOL)',
    '2026-03-11 22:13:47'
  ),
  (
    312,
    'EDIT',
    'Koreksi Data Produksi: Batch #120326-1A-BOT-1200-JOPR-600ML (BOTOL)',
    '2026-03-11 22:13:53'
  ),
  (
    313,
    'EDIT',
    'Koreksi Data Produksi: Batch #120326-1A-BOT-1200-JOPR-600ML (BOTOL)',
    '2026-03-11 22:20:51'
  ),
  (
    314,
    'HAPUS',
    'Hapus Produksi Batch: #110326-1A-BOT-200-YHHH-600ML',
    '2026-03-11 22:22:16'
  ),
  (
    315,
    'HAPUS',
    'Hapus Produksi Batch: #110326-1A-BOT-1200-HSVJ-600ML',
    '2026-03-11 22:22:19'
  ),
  (
    316,
    'HAPUS',
    'Hapus Produksi Batch: #110326-1B-CUP-48-FXVV-240ML',
    '2026-03-11 22:22:21'
  ),
  (
    317,
    'HAPUS',
    'Hapus Template: RAHMAT UPDATE',
    '2026-03-11 22:23:36'
  ),
  (
    318,
    'HAPUS',
    'Hapus Produksi Batch: #100326-1A-BOT-200-WAWA-600ML',
    '2026-03-11 22:24:55'
  ),
  (
    319,
    'EDIT',
    'Koreksi Data Produksi: Batch #120326-1A-BOT-1200-JOPR-600ML (BOTOL)',
    '2026-03-11 22:25:04'
  ),
  (
    320,
    'EDIT',
    'Koreksi Data Produksi: Batch #120326-1A-BOT-1200-JOPR-600ML (BOTOL)',
    '2026-03-11 22:25:18'
  ),
  (
    321,
    'PENGIRIMAN',
    'Kirim 1 dus ke Rahmat ramadhan (No. Resi #1-120320260629-1-RR)',
    '2026-03-11 22:29:07'
  ),
  (
    322,
    'PENGIRIMAN',
    'Tambah susulan 1 dus ke No. Resi #1-120320260629-2-RR',
    '2026-03-11 22:29:35'
  ),
  (
    323,
    'HAPUS',
    'Batalkan Pengiriman No. Resi #1-100320262253-7-RA ke Rahmat',
    '2026-03-11 22:31:15'
  ),
  (
    324,
    'LOGIN',
    'User Administrator Utama (admin) berhasil login',
    '2026-03-11 22:38:22'
  ),
  (
    325,
    'EDIT',
    'Admin Memperbarui Hak Akses Role (Permissions)',
    '2026-03-11 22:38:49'
  ),
  (
    326,
    'LOGIN',
    'User mamang (gudang) berhasil login',
    '2026-03-11 22:39:12'
  ),
  (
    327,
    'LOGOUT',
    'User Administrator Utama (admin) keluar dari sistem',
    '2026-03-11 23:11:11'
  ),
  (
    328,
    'LOGIN',
    'User mamang (gudang) berhasil login',
    '2026-03-11 23:11:20'
  ),
  (
    329,
    'PENGIRIMAN',
    'Kirim 8 dus ke Rahmat ramadhan (No. Resi #1-120320260712-8-RR)',
    '2026-03-11 23:12:30'
  ),
  (
    330,
    'PENGIRIMAN',
    'Kirim 5 dus ke Lutfi Rasyid (No. Resi #2-120320260716-5-LR)',
    '2026-03-11 23:16:09'
  );

-- --------------------------------------------------------
--
-- Table structure for table `api_keys`
--
CREATE TABLE
  `api_keys` (
    `id` int (11) NOT NULL,
    `device_name` varchar(100) NOT NULL,
    `device_uuid` varchar(100) DEFAULT NULL,
    `api_key` varchar(100) DEFAULT NULL,
    `is_active` tinyint (1) DEFAULT 1,
    `status` enum ('pending', 'approved') DEFAULT 'approved',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `reset_pin` varchar(10) DEFAULT '0503'
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `api_keys`
--
INSERT INTO
  `api_keys` (
    `id`,
    `device_name`,
    `device_uuid`,
    `api_key`,
    `is_active`,
    `status`,
    `created_at`,
    `reset_pin`
  )
VALUES
  (
    1,
    'Master Fallback Key',
    NULL,
    'AFTECH-PRO-99X-2026',
    1,
    'approved',
    '2026-03-11 07:24:29',
    '0503'
  ),
  (
    47,
    'SM-A556E',
    'BP2A.250605.031.A3',
    'AFTECH-499F-C349-2026',
    1,
    'approved',
    '2026-03-11 19:02:06',
    '1111'
  ),
  (
    49,
    'SM-A057F',
    'AP3A.240905.015.A2',
    'AFTECH-B8D7-811C-2026',
    1,
    'approved',
    '2026-03-11 20:20:43',
    '7777'
  );

-- --------------------------------------------------------
--
-- Table structure for table `distributor_shipments`
--
CREATE TABLE
  `distributor_shipments` (
    `id` int (11) NOT NULL,
    `shipment_id` int (11) NOT NULL,
    `production_id` int (11) NOT NULL,
    `label_no` int (11) NOT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `distributor_shipments`
--
INSERT INTO
  `distributor_shipments` (`id`, `shipment_id`, `production_id`, `label_no`)
VALUES
  (215, 76, 159, 1),
  (216, 76, 151, 1),
  (217, 77, 148, 1),
  (218, 77, 154, 1),
  (219, 77, 165, 8),
  (220, 77, 165, 5),
  (221, 77, 165, 1),
  (222, 77, 169, 9),
  (223, 77, 169, 1),
  (224, 77, 169, 4),
  (225, 78, 165, 6),
  (226, 78, 165, 9),
  (227, 78, 169, 2),
  (228, 78, 169, 5),
  (229, 78, 169, 3);

-- --------------------------------------------------------
--
-- Table structure for table `master_customers`
--
CREATE TABLE
  `master_customers` (
    `id` int (11) NOT NULL,
    `name` varchar(150) NOT NULL,
    `contact` varchar(100) DEFAULT NULL,
    `address` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `total_orders` int (11) DEFAULT 0
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `master_customers`
--
INSERT INTO
  `master_customers` (
    `id`,
    `name`,
    `contact`,
    `address`,
    `created_at`,
    `total_orders`
  )
VALUES
  (
    1,
    'Rahmat ramadhan',
    '085213976352',
    'JLN.PERINTIS KEMERDEKAAN VII. LORONG 2',
    '2026-03-08 07:44:14',
    3
  ),
  (
    2,
    'Tri Munazzar Abduh',
    '1234567890',
    'JLN.SUKA-SUKA',
    '2026-03-08 07:44:14',
    0
  ),
  (
    8,
    'Lutfi Rasyid',
    '085174302323',
    'Morowali',
    '2026-03-08 08:38:56',
    1
  );

-- --------------------------------------------------------
--
-- Table structure for table `master_items`
--
CREATE TABLE
  `master_items` (
    `id` int (11) NOT NULL,
    `name` varchar(100) NOT NULL,
    `unit_id` int (11) DEFAULT NULL,
    `default_machine_id` int (11) DEFAULT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `master_items`
--
INSERT INTO
  `master_items` (`id`, `name`, `unit_id`)
VALUES
  (1, 'BOTOL', 1),
  (2, 'SEDOTAN', 3),
  (3, 'CUP', 1);

-- --------------------------------------------------------
--
-- Table structure for table `master_machines`
--
CREATE TABLE
  `master_machines` (
    `id` int (11) NOT NULL,
    `name` varchar(100) NOT NULL,
    `status` enum ('active', 'maintenance') DEFAULT 'active'
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `master_machines`
--
INSERT INTO
  `master_machines` (`id`, `name`, `status`)
VALUES
  (1, 'THERMO TINGGI 01', 'active'),
  (7, 'THERMO 1', 'active'),
  (8, 'THERMO 2', 'active'),
  (9, 'THERMO 3', 'maintenance'),
  (10, 'THERMO TINGGI 02', 'active');

-- --------------------------------------------------------
--
-- Table structure for table `master_quantities`
--
CREATE TABLE
  `master_quantities` (
    `id` int (11) NOT NULL,
    `machine_id` int (11) NOT NULL,
    `qty_value` varchar(50) NOT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `master_quantities`
--
INSERT INTO
  `master_quantities` (`id`, `machine_id`, `qty_value`)
VALUES
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
CREATE TABLE
  `master_shifts` (
    `id` int (11) NOT NULL,
    `name` varchar(100) NOT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `master_shifts`
--
INSERT INTO
  `master_shifts` (`id`, `name`)
VALUES
  (1, 'SHIFT A'),
  (2, 'SHIFT B'),
  (7, 'SHIFT C');

-- --------------------------------------------------------
--
-- Table structure for table `master_sizes`
--
CREATE TABLE
  `master_sizes` (
    `id` int (11) NOT NULL,
    `item_id` int (11) NOT NULL,
    `size_value` varchar(50) NOT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `master_sizes`
--
INSERT INTO
  `master_sizes` (`id`, `item_id`, `size_value`)
VALUES
  (1, 1, '330'),
  (2, 1, '600'),
  (3, 2, '100'),
  (4, 3, '120'),
  (6, 3, '200'),
  (7, 1, '1000');

-- --------------------------------------------------------
--
-- Table structure for table `master_templates`
--
CREATE TABLE
  `master_templates` (
    `id` int (11) NOT NULL,
    `template_name` varchar(100) NOT NULL,
    `item` varchar(100) DEFAULT NULL,
    `size` varchar(20) DEFAULT NULL,
    `unit` varchar(20) DEFAULT NULL,
    `machine` varchar(50) DEFAULT NULL,
    `shift` varchar(20) DEFAULT NULL,
    `quantity` varchar(20) DEFAULT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `master_templates`
--
INSERT INTO
  `master_templates` (
    `id`,
    `template_name`,
    `item`,
    `size`,
    `unit`,
    `machine`,
    `shift`,
    `quantity`
  )
VALUES
  (
    1,
    'BOTOL 600ML - THERMO 1',
    'BOTOL',
    '600',
    'ML',
    'THERMO 1',
    'SHIFT A',
    '1200'
  ),
  (
    2,
    'CUP 240ML - MACHINE 1',
    'CUP',
    '240',
    'ML',
    'MACHINE 1',
    'SHIFT B',
    '48'
  ),
  (
    4,
    'CUP 120ML - THERMO 1',
    'CUP',
    '120',
    'ML',
    'THERMO 1',
    'SHIFT A',
    '100'
  ),
  (
    7,
    'SEDOTAN PCS - MACHINE 2',
    'BOTOL',
    '1000',
    'ML',
    'THERMO 2',
    'SHIFT C',
    '800'
  );

-- --------------------------------------------------------
--
-- Table structure for table `master_units`
--
CREATE TABLE
  `master_units` (
    `id` int (11) NOT NULL,
    `name` varchar(20) NOT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `master_units`
--
INSERT INTO
  `master_units` (`id`, `name`)
VALUES
  (2, 'GR'),
  (4, 'KG'),
  (5, 'LITER'),
  (1, 'ML'),
  (3, 'PCS');

-- --------------------------------------------------------
--
-- Table structure for table `outbound_shipments`
--
CREATE TABLE
  `outbound_shipments` (
    `id` int (11) NOT NULL,
    `customer_name` varchar(150) NOT NULL,
    `customer_contact` varchar(100) DEFAULT NULL,
    `customer_address` text DEFAULT NULL,
    `shipment_date` date DEFAULT NULL,
    `total_qty` int (11) NOT NULL DEFAULT 0,
    `shipped_by` varchar(100) NOT NULL,
    `shipped_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `total_actual_qty` int (11) DEFAULT 0
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `outbound_shipments`
--
INSERT INTO
  `outbound_shipments` (
    `id`,
    `customer_name`,
    `customer_contact`,
    `customer_address`,
    `shipment_date`,
    `total_qty`,
    `shipped_by`,
    `shipped_at`,
    `total_actual_qty`
  )
VALUES
  (
    76,
    'Rahmat ramadhan',
    '085213976352',
    'JLN.PERINTIS KEMERDEKAAN VII. LORONG 2',
    '2026-03-11',
    2,
    'Administrator Utama',
    '2026-03-11 22:29:07',
    2600
  ),
  (
    77,
    'Rahmat ramadhan',
    '085213976352',
    'JLN.PERINTIS KEMERDEKAAN VII. LORONG 2',
    '2026-03-12',
    8,
    'mamang',
    '2026-03-11 23:12:30',
    8100
  ),
  (
    78,
    'Lutfi Rasyid',
    '085174302323',
    'Morowali',
    '2026-03-12',
    5,
    'mamang',
    '2026-03-11 23:16:09',
    4000
  );

-- --------------------------------------------------------
--
-- Table structure for table `outbound_shipment_batches`
--
CREATE TABLE
  `outbound_shipment_batches` (
    `id` int (11) NOT NULL,
    `shipment_id` int (11) NOT NULL,
    `production_id` int (11) NOT NULL,
    `label_qty` int (11) NOT NULL,
    `unit_qty` int (11) NOT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `outbound_shipment_batches`
--
INSERT INTO
  `outbound_shipment_batches` (
    `id`,
    `shipment_id`,
    `production_id`,
    `label_qty`,
    `unit_qty`
  )
VALUES
  (34, 76, 159, 1, 2400),
  (35, 76, 151, 1, 200),
  (36, 77, 148, 1, 2400),
  (37, 77, 154, 1, 1500),
  (38, 77, 165, 3, 600),
  (39, 77, 169, 3, 3600),
  (40, 78, 165, 2, 400),
  (41, 78, 169, 3, 3600);

-- --------------------------------------------------------
--
-- Table structure for table `production_labels`
--
CREATE TABLE
  `production_labels` (
    `id` int (11) NOT NULL,
    `batch` varchar(100) NOT NULL,
    `item` varchar(100) DEFAULT NULL,
    `size` varchar(20) DEFAULT NULL,
    `unit` varchar(20) DEFAULT NULL,
    `machine` varchar(50) DEFAULT NULL,
    `shift` varchar(20) DEFAULT NULL,
    `quantity` varchar(20) DEFAULT NULL,
    `operator` varchar(100) DEFAULT NULL,
    `qc` varchar(100) DEFAULT NULL,
    `copies` int (11) DEFAULT 0,
    `production_date` date DEFAULT NULL,
    `production_time` time DEFAULT NULL,
    `device_model` varchar(100) DEFAULT NULL,
    `device_id` varchar(100) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `production_labels`
--
INSERT INTO
  `production_labels` (
    `id`,
    `batch`,
    `item`,
    `size`,
    `unit`,
    `machine`,
    `shift`,
    `quantity`,
    `operator`,
    `qc`,
    `copies`,
    `production_date`,
    `production_time`,
    `device_model`,
    `created_at`
  )
VALUES
  (
    120,
    '080326-01C-SED-1000-RAMA-100PCS',
    'SEDOTAN',
    '100',
    'PCS',
    'THERMO TINGGI 01',
    'SHIFT C',
    '1000',
    'rahmat',
    'mamat',
    10,
    '2026-03-08',
    '02:53:49',
    '25040RP0AG',
    '2026-03-07 18:54:19'
  ),
  (
    122,
    '080326-1A-BOT-500-RAMA-330ML',
    'BOTOL',
    '330',
    'ML',
    'THERMO 1',
    'SHIFT A',
    '500',
    'rahmat',
    'mamat',
    5,
    '2026-03-08',
    '02:53:09',
    '25040RP0AG',
    '2026-03-07 18:56:16'
  ),
  (
    123,
    '090326-2A-SED-2400-FAFA-1PCS',
    'SEDOTAN',
    '1',
    'PCS',
    'MACHINE 2',
    'SHIFT A',
    '2400',
    'Fahrul',
    'Fahrul',
    2,
    '2026-03-09',
    '06:24:19',
    'SM-A057F',
    '2026-03-08 22:29:07'
  ),
  (
    124,
    '090326-1A-CUP-100-FAFA-120ML',
    'CUP',
    '120',
    'ML',
    'THERMO 1',
    'SHIFT A',
    '100',
    'Fahrul',
    'Fahrul',
    2,
    '2026-03-09',
    '06:24:11',
    'SM-A057F',
    '2026-03-08 22:29:53'
  ),
  (
    126,
    '090326-1B-CUP-48-FARA-240ML',
    'CUP',
    '240',
    'ML',
    'MACHINE 1',
    'SHIFT B',
    '48',
    'Fahrul',
    'Rahmat',
    2,
    '2026-03-09',
    '06:24:16',
    'SM-A556E',
    '2026-03-08 22:31:02'
  ),
  (
    127,
    '090326-1A-CUP-100-FARA-120ML',
    'CUP',
    '120',
    'ML',
    'THERMO 1',
    'SHIFT A',
    '100',
    'Fahrul',
    'Rahmat',
    2,
    '2026-03-09',
    '06:32:05',
    'SM-A556E',
    '2026-03-08 22:32:12'
  ),
  (
    128,
    '100326-1A-BOT-200-AHSI-600ML',
    'BOTOL',
    '600',
    'ML',
    'THERMO 1',
    'SHIFT A',
    '200',
    'ahmad',
    'siti',
    2,
    '2026-03-10',
    '01:10:07',
    'SM-A556E',
    '2026-03-09 17:10:14'
  ),
  (
    132,
    '100326-1A-BOT-200-AHSI-1000ML',
    'BOTOL',
    '1000',
    'ML',
    'THERMO 1',
    'SHIFT A',
    '200',
    'ahmad',
    'siti',
    2,
    '2026-03-10',
    '11:23:29',
    'SM-A556E',
    '2026-03-10 03:23:40'
  ),
  (
    134,
    '100326-1B-CUP-48-AHSI-240ML',
    'CUP',
    '240',
    'ML',
    'MACHINE 1',
    'SHIFT B',
    '48',
    'ahmad',
    'siti',
    3,
    '2026-03-10',
    '11:27:42',
    'SM-A556E',
    '2026-03-10 03:27:49'
  ),
  (
    137,
    '100326-1A-CUP-100-AHSI-120ML',
    'CUP',
    '120',
    'ML',
    'THERMO 1',
    'SHIFT A',
    '100',
    'ahmad',
    'siti',
    1,
    '2026-03-10',
    '11:35:21',
    'SM-A556E',
    '2026-03-10 03:35:41'
  ),
  (
    139,
    '100326-2A-BOT-2400-AHSI-50ML',
    'BOTOL',
    '50',
    'ML',
    'MACHINE 2',
    'SHIFT A',
    '2400',
    'ahmad',
    'siti',
    1,
    '2026-03-10',
    '12:06:38',
    'SM-A556E',
    '2026-03-10 04:06:41'
  ),
  (
    141,
    '100326-1B-CUP-48-MAYU-240ML',
    'CUP',
    '240',
    'ML',
    'MACHINE 1',
    'SHIFT B',
    '48',
    'mamay',
    'yu',
    3,
    '2026-03-10',
    '12:41:03',
    'SM-A556E',
    '2026-03-10 04:42:37'
  ),
  (
    148,
    '110326-2A-BOT-2400-DIHA-50ML',
    'BOTOL',
    '50',
    'ML',
    'MACHINE 2',
    'SHIFT A',
    '2400',
    'dian',
    'halo',
    5,
    '2026-03-11',
    '21:49:55',
    'Unknown',
    '2026-03-11 13:51:02'
  ),
  (
    151,
    '110326-1A-BOT-200-FFGV-600ML',
    'BOTOL',
    '600',
    'ML',
    'THERMO 1',
    'SHIFT A',
    '200',
    'fff',
    'gvc',
    1,
    '2026-03-11',
    '21:55:51',
    'SM-A556E',
    '2026-03-11 14:03:05'
  ),
  (
    154,
    '110326-01C-CUP-1500-CCHH-25KG',
    'CUP',
    '25',
    'KG',
    'THERMO TINGGI 01',
    'SHIFT C',
    '1500',
    'cc',
    'hhhcv',
    1,
    '2026-03-11',
    '22:34:50',
    'SM-A556E',
    '2026-03-11 14:37:54'
  ),
  (
    159,
    '110326-2A-BOT-2400-OFOF-50ML',
    'BOTOL',
    '50',
    'ML',
    'MACHINE 2',
    'SHIFT A',
    '2400',
    'off',
    'off',
    1,
    '2026-03-11',
    '22:55:45',
    'SM-A556E',
    '2026-03-11 14:56:45'
  ),
  (
    165,
    '120326-1A-BOT-200-GYGF-600ML',
    'BOTOL',
    '600',
    'ML',
    'THERMO 1',
    'SHIFT A',
    '200',
    'gyu',
    'gfg',
    9,
    '2026-03-12',
    '00:30:04',
    'SM-A556E',
    '2026-03-11 16:30:21'
  ),
  (
    169,
    '120326-1A-BOT-1200-RRRR-600ML',
    'BOTOL',
    '600',
    'ML',
    'THERMO 1',
    'SHIFT A',
    '1200',
    'rr',
    'rr',
    9,
    '2026-03-12',
    '01:03:53',
    'SM-A556E',
    '2026-03-11 17:04:21'
  ),
  (
    182,
    '120326-1A-BOT-1200-JOPR-600ML',
    'BOTOL',
    '600',
    'ML',
    'THERMO 1',
    'SHIFT A',
    '1200',
    'Jokowi dodo',
    'Prabowo',
    1,
    '2026-03-12',
    '04:19:00',
    'SM-A057F',
    '2026-03-11 20:19:48'
  );

-- --------------------------------------------------------
--
-- Table structure for table `role_permissions`
--
CREATE TABLE
  `role_permissions` (
    `id` int (11) NOT NULL,
    `role` varchar(50) NOT NULL,
    `page_slug` varchar(100) NOT NULL,
    `can_access` tinyint (1) DEFAULT 1
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--
INSERT INTO
  `role_permissions` (`id`, `role`, `page_slug`, `can_access`)
VALUES
  (38, 'qc', 'dashboard', 1),
  (39, 'gudang', 'dashboard', 1),
  (40, 'qc', 'production_data', 1),
  (41, 'qc', 'qc_checker', 1),
  (42, 'gudang', 'warehouse', 1),
  (43, 'gudang', 'shipment_reports', 1),
  (44, 'qc', 'reports', 1),
  (45, 'gudang', 'reports', 1);

-- --------------------------------------------------------
--
-- Table structure for table `users`
--
CREATE TABLE
  `users` (
    `id` int (11) NOT NULL,
    `username` varchar(50) DEFAULT NULL,
    `password` varchar(255) DEFAULT NULL,
    `full_name` varchar(100) DEFAULT NULL,
    `role` enum ('admin', 'qc', 'gudang') DEFAULT 'gudang',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `users`
--
INSERT INTO
  `users` (
    `id`,
    `username`,
    `password`,
    `full_name`,
    `role`,
    `created_at`
  )
VALUES
  (
    1,
    'admin',
    '$2b$12$9/2NixIOxzIBB93TkhMPlefboHATZjUdqGxfbYY0179inTHif9hK.',
    'Administrator Utama',
    'admin',
    '2026-03-05 05:38:10'
  ),
  (
    2,
    'mumu',
    '$2b$12$ZkcWC1Ro4cPpGh6IGuDaW.tak23X7nqwsO/xrK3ZR9.3mg/F.vLnm',
    'mumu',
    'qc',
    '2026-03-05 05:38:10'
  ),
  (
    3,
    'mamang',
    '$2b$12$by3uvI6onS1CXl4mruCIuutsquNCCjH4mlheP1K/rWTps9Xj9.K5m',
    'mamang',
    'gudang',
    '2026-03-05 05:38:10'
  ),
  (
    4,
    'coki',
    '$2b$12$hYgmZZXmG58ZosdeeYRb.eNjFd1qu/5R.4fhIljDl1xUdUaWhY1F6',
    'coki',
    'qc',
    '2026-03-11 19:47:26'
  );

-- --------------------------------------------------------
--
-- Table structure for table `warehouse_items`
--
CREATE TABLE
  `warehouse_items` (
    `id` int (11) NOT NULL,
    `production_id` int (11) DEFAULT NULL,
    `label_no` int (11) DEFAULT NULL,
    `transferred_by` varchar(100) DEFAULT NULL,
    `transferred_at` timestamp NOT NULL DEFAULT current_timestamp()
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `warehouse_items`
--
INSERT INTO
  `warehouse_items` (
    `id`,
    `production_id`,
    `label_no`,
    `transferred_by`,
    `transferred_at`
  )
VALUES
  (
    272,
    128,
    1,
    'Administrator Utama',
    '2026-03-10 14:50:23'
  ),
  (
    273,
    139,
    1,
    'Administrator Utama',
    '2026-03-10 14:50:26'
  ),
  (
    274,
    128,
    2,
    'Administrator Utama',
    '2026-03-10 14:50:30'
  ),
  (
    275,
    123,
    2,
    'Administrator Utama',
    '2026-03-10 14:50:33'
  ),
  (
    276,
    126,
    2,
    'Administrator Utama',
    '2026-03-10 14:50:36'
  ),
  (
    277,
    126,
    1,
    'Administrator Utama',
    '2026-03-10 14:50:38'
  ),
  (
    278,
    124,
    2,
    'Administrator Utama',
    '2026-03-10 14:50:40'
  ),
  (
    279,
    122,
    1,
    'Administrator Utama',
    '2026-03-11 05:42:25'
  ),
  (
    280,
    123,
    1,
    'Administrator Utama',
    '2026-03-11 05:42:48'
  ),
  (
    281,
    141,
    3,
    'Administrator Utama',
    '2026-03-11 05:43:06'
  ),
  (
    282,
    124,
    1,
    'Administrator Utama',
    '2026-03-11 05:43:09'
  ),
  (
    283,
    120,
    8,
    'Administrator Utama',
    '2026-03-11 05:43:12'
  ),
  (
    284,
    122,
    5,
    'Administrator Utama',
    '2026-03-11 05:43:20'
  ),
  (
    285,
    122,
    2,
    'Administrator Utama',
    '2026-03-11 05:43:24'
  ),
  (
    286,
    122,
    4,
    'Administrator Utama',
    '2026-03-11 05:43:27'
  ),
  (
    287,
    120,
    4,
    'Administrator Utama',
    '2026-03-11 05:43:30'
  ),
  (
    288,
    120,
    7,
    'Administrator Utama',
    '2026-03-11 05:43:33'
  ),
  (
    289,
    120,
    1,
    'Administrator Utama',
    '2026-03-11 05:43:35'
  ),
  (
    290,
    120,
    9,
    'Administrator Utama',
    '2026-03-11 05:43:40'
  ),
  (
    291,
    120,
    5,
    'Administrator Utama',
    '2026-03-11 05:43:45'
  ),
  (
    292,
    137,
    1,
    'Administrator Utama',
    '2026-03-11 05:43:49'
  ),
  (
    293,
    132,
    2,
    'Administrator Utama',
    '2026-03-11 05:43:54'
  ),
  (
    294,
    132,
    1,
    'Administrator Utama',
    '2026-03-11 05:43:57'
  ),
  (
    295,
    134,
    2,
    'Administrator Utama',
    '2026-03-11 05:44:01'
  ),
  (
    296,
    134,
    3,
    'Administrator Utama',
    '2026-03-11 05:44:07'
  ),
  (
    297,
    134,
    1,
    'Administrator Utama',
    '2026-03-11 05:44:12'
  ),
  (
    298,
    120,
    6,
    'Administrator Utama',
    '2026-03-11 05:44:16'
  ),
  (
    299,
    127,
    2,
    'Administrator Utama',
    '2026-03-11 05:44:20'
  ),
  (
    300,
    120,
    2,
    'Administrator Utama',
    '2026-03-11 05:44:23'
  ),
  (
    301,
    120,
    3,
    'Administrator Utama',
    '2026-03-11 05:44:27'
  ),
  (
    302,
    122,
    3,
    'Administrator Utama',
    '2026-03-11 05:44:30'
  ),
  (
    303,
    127,
    1,
    'Administrator Utama',
    '2026-03-11 05:44:34'
  ),
  (
    304,
    141,
    1,
    'Administrator Utama',
    '2026-03-11 05:44:37'
  ),
  (
    305,
    120,
    10,
    'Administrator Utama',
    '2026-03-11 05:44:41'
  ),
  (
    306,
    141,
    2,
    'Administrator Utama',
    '2026-03-11 05:44:43'
  ),
  (
    307,
    169,
    9,
    'Administrator Utama',
    '2026-03-11 22:19:30'
  ),
  (
    308,
    148,
    1,
    'Administrator Utama',
    '2026-03-11 22:19:32'
  ),
  (
    309,
    151,
    1,
    'Administrator Utama',
    '2026-03-11 22:19:36'
  ),
  (
    310,
    159,
    1,
    'Administrator Utama',
    '2026-03-11 22:19:42'
  ),
  (
    311,
    154,
    1,
    'Administrator Utama',
    '2026-03-11 22:19:45'
  ),
  (
    312,
    165,
    8,
    'Administrator Utama',
    '2026-03-11 22:19:49'
  ),
  (
    313,
    165,
    6,
    'Administrator Utama',
    '2026-03-11 22:19:51'
  ),
  (
    314,
    165,
    9,
    'Administrator Utama',
    '2026-03-11 22:19:53'
  ),
  (
    315,
    169,
    3,
    'Administrator Utama',
    '2026-03-11 22:19:55'
  ),
  (
    316,
    169,
    5,
    'Administrator Utama',
    '2026-03-11 22:20:03'
  ),
  (
    317,
    169,
    1,
    'Administrator Utama',
    '2026-03-11 22:20:06'
  ),
  (
    318,
    169,
    2,
    'Administrator Utama',
    '2026-03-11 22:20:09'
  ),
  (
    319,
    169,
    4,
    'Administrator Utama',
    '2026-03-11 22:20:12'
  ),
  (
    320,
    165,
    5,
    'Administrator Utama',
    '2026-03-11 22:20:15'
  ),
  (
    321,
    165,
    1,
    'Administrator Utama',
    '2026-03-11 22:20:17'
  ),
  (
    322,
    182,
    1,
    'Administrator Utama',
    '2026-03-11 22:22:09'
  );

-- --------------------------------------------------------
--
-- Table structure for table `warehouse_transfers`
--
CREATE TABLE
  `warehouse_transfers` (
    `id` int (11) NOT NULL,
    `production_id` int (11) DEFAULT NULL,
    `transferred_by` varchar(100) DEFAULT NULL,
    `transferred_at` timestamp NOT NULL DEFAULT current_timestamp()
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `cancelled_labels`
--
CREATE TABLE
  `cancelled_labels` (
    `id` int (11) NOT NULL,
    `production_id` int (11) NOT NULL,
    `label_no` int (11) NOT NULL,
    `category` enum ('production', 'warehouse') NOT NULL,
    `reason` varchar(255) DEFAULT NULL,
    `cancelled_by` varchar(100) DEFAULT NULL,
    `cancelled_at` timestamp NOT NULL DEFAULT current_timestamp()
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Indexes for dumped tables
--
--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs` ADD PRIMARY KEY (`id`);

--
-- Indexes for table `api_keys`
--
ALTER TABLE `api_keys` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `api_key` (`api_key`);

--
-- Indexes for table `distributor_shipments`
--
ALTER TABLE `distributor_shipments` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `unique_label` (`production_id`, `label_no`),
ADD KEY `shipment_id` (`shipment_id`);

--
-- Indexes for table `master_customers`
--
ALTER TABLE `master_customers` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `master_items`
--
ALTER TABLE `master_items` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `name` (`name`),
ADD KEY `unit_id` (`unit_id`),
ADD KEY `idx_default_machine` (`default_machine_id`);

--
-- Indexes for table `master_machines`
--
ALTER TABLE `master_machines` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `master_quantities`
--
ALTER TABLE `master_quantities` ADD PRIMARY KEY (`id`),
ADD KEY `machine_id` (`machine_id`);

--
-- Indexes for table `master_shifts`
--
ALTER TABLE `master_shifts` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `master_sizes`
--
ALTER TABLE `master_sizes` ADD PRIMARY KEY (`id`),
ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `master_templates`
--
ALTER TABLE `master_templates` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `template_name` (`template_name`);

--
-- Indexes for table `master_units`
--
ALTER TABLE `master_units` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `outbound_shipments`
--
ALTER TABLE `outbound_shipments` ADD PRIMARY KEY (`id`),
ADD KEY `idx_shipment_date` (`shipment_date`);

--
-- Indexes for table `outbound_shipment_batches`
--
ALTER TABLE `outbound_shipment_batches` ADD PRIMARY KEY (`id`),
ADD KEY `shipment_id` (`shipment_id`),
ADD KEY `production_id` (`production_id`);

--
-- Indexes for table `production_labels`
--
ALTER TABLE `production_labels` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `batch` (`batch`),
ADD KEY `idx_production_date` (`production_date`),
ADD KEY `idx_device_id` (`device_id`),
ADD KEY `idx_device_model` (`device_model`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `role_page` (`role`, `page_slug`);

--
-- Indexes for table `users`
--
ALTER TABLE `users` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `warehouse_items`
--
ALTER TABLE `warehouse_items` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `production_id` (`production_id`, `label_no`),
ADD KEY `idx_transferred_at` (`transferred_at`);

--
-- Indexes for table `warehouse_transfers`
--
ALTER TABLE `warehouse_transfers` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `production_id` (`production_id`);

--
-- Indexes for table `cancelled_labels`
--
ALTER TABLE `cancelled_labels` ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `uniq_prod_label` (`production_id`, `label_no`),
ADD KEY `idx_category` (`category`);

--
-- AUTO_INCREMENT for dumped tables
--
--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs` MODIFY `id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 331;

--
-- AUTO_INCREMENT for table `api_keys`
--
ALTER TABLE `api_keys` MODIFY `id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 50;

--
-- AUTO_INCREMENT for table `distributor_shipments`
--
ALTER TABLE `distributor_shipments` MODIFY `id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 230;

--
-- AUTO_INCREMENT for table `master_customers`
--
ALTER TABLE `master_customers` MODIFY `id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 36;

--
-- AUTO_INCREMENT for table `master_items`
--
ALTER TABLE `master_items` MODIFY `id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 8;

--
-- AUTO_INCREMENT for table `master_machines`
--
ALTER TABLE `master_machines` MODIFY `id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 12;

--
-- AUTO_INCREMENT for table `master_quantities`
--
ALTER TABLE `master_quantities` MODIFY `id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 15;

--
-- AUTO_INCREMENT for table `master_shifts`
--
ALTER TABLE `master_shifts` MODIFY `id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 9;

--
-- AUTO_INCREMENT for table `master_sizes`
--
ALTER TABLE `master_sizes` MODIFY `id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 10;

--
-- AUTO_INCREMENT for table `master_templates`
--
ALTER TABLE `master_templates` MODIFY `id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 10;

--
-- AUTO_INCREMENT for table `master_units`
--
ALTER TABLE `master_units` MODIFY `id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 11;

--
-- AUTO_INCREMENT for table `outbound_shipments`
--
ALTER TABLE `outbound_shipments` MODIFY `id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 79;

--
-- AUTO_INCREMENT for table `outbound_shipment_batches`
--
ALTER TABLE `outbound_shipment_batches` MODIFY `id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 42;

--
-- AUTO_INCREMENT for table `production_labels`
--
ALTER TABLE `production_labels` MODIFY `id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 183;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions` MODIFY `id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 46;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users` MODIFY `id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 5;

--
-- AUTO_INCREMENT for table `warehouse_items`
--
ALTER TABLE `warehouse_items` MODIFY `id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 323;

--
-- AUTO_INCREMENT for table `warehouse_transfers`
--
ALTER TABLE `warehouse_transfers` MODIFY `id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 15;

--
-- AUTO_INCREMENT for table `cancelled_labels`
--
ALTER TABLE `cancelled_labels` MODIFY `id` int (11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--
--
-- Constraints for table `distributor_shipments`
--
ALTER TABLE `distributor_shipments` ADD CONSTRAINT `distributor_shipments_ibfk_1` FOREIGN KEY (`shipment_id`) REFERENCES `outbound_shipments` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `distributor_shipments_ibfk_2` FOREIGN KEY (`production_id`) REFERENCES `production_labels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `master_items`
--
ALTER TABLE `master_items` ADD CONSTRAINT `master_items_ibfk_1` FOREIGN KEY (`unit_id`) REFERENCES `master_units` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `master_items_ibfk_machine` FOREIGN KEY (`default_machine_id`) REFERENCES `master_machines` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `master_quantities`
--
ALTER TABLE `master_quantities` ADD CONSTRAINT `master_quantities_ibfk_1` FOREIGN KEY (`machine_id`) REFERENCES `master_machines` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `master_sizes`
--
ALTER TABLE `master_sizes` ADD CONSTRAINT `master_sizes_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `master_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `outbound_shipment_batches`
--
ALTER TABLE `outbound_shipment_batches` ADD CONSTRAINT `outbound_shipment_batches_ibfk_1` FOREIGN KEY (`shipment_id`) REFERENCES `outbound_shipments` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `outbound_shipment_batches_ibfk_2` FOREIGN KEY (`production_id`) REFERENCES `production_labels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `warehouse_items`
--
ALTER TABLE `warehouse_items` ADD CONSTRAINT `warehouse_items_ibfk_1` FOREIGN KEY (`production_id`) REFERENCES `production_labels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `warehouse_transfers`
--
ALTER TABLE `warehouse_transfers` ADD CONSTRAINT `warehouse_transfers_ibfk_1` FOREIGN KEY (`production_id`) REFERENCES `production_labels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cancelled_labels`
--
ALTER TABLE `cancelled_labels` ADD CONSTRAINT `cancelled_labels_ibfk_1` FOREIGN KEY (`production_id`) REFERENCES `production_labels` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;

/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;

/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;