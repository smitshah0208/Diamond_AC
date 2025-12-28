-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 28, 2025 at 05:58 PM
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
-- Database: `ac_test`
--

-- --------------------------------------------------------

--
-- Table structure for table `brokers`
--

CREATE TABLE `brokers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brokers`
--

INSERT INTO `brokers` (`id`, `name`, `created_at`) VALUES
(1, 'Raj Patel', '2025-12-27 13:05:24'),
(2, 'Rahul Mehta', '2025-12-27 13:05:24'),
(3, 'Rakesh Shah', '2025-12-27 13:05:24'),
(4, 'Ramesh Broker', '2025-12-27 13:05:24'),
(5, 'Rajiv Kumar', '2025-12-27 13:05:24'),
(6, 'Suresh Shah', '2025-12-27 13:05:24'),
(7, 'Amit Broker', '2025-12-27 13:05:24'),
(8, 'Test Broker', '2025-12-27 13:18:32');

-- --------------------------------------------------------

--
-- Table structure for table `cash_bank_entries`
--

CREATE TABLE `cash_bank_entries` (
  `id` int(11) NOT NULL,
  `txn_date` date NOT NULL DEFAULT curdate(),
  `account_type` enum('Cash','Bank') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_type` enum('Receipt','Payment') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Payment',
  `invoice_num` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `party_or_broker` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'PARTY or BROKER',
  `related_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conversion_rate` decimal(12,2) DEFAULT 0.00,
  `dr_usd` decimal(15,2) DEFAULT 0.00,
  `cr_usd` decimal(15,2) DEFAULT 0.00,
  `dr_local` decimal(15,2) DEFAULT 0.00,
  `cr_local` decimal(15,2) DEFAULT 0.00,
  `tax_usd` decimal(15,2) DEFAULT 0.00,
  `tax_local` decimal(15,2) DEFAULT 0.00,
  `payment_currency` enum('Dollar','Local') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cash_bank_entries`
--

INSERT INTO `cash_bank_entries` (`id`, `txn_date`, `account_type`, `transaction_type`, `invoice_num`, `party_or_broker`, `related_name`, `description`, `conversion_rate`, `dr_usd`, `cr_usd`, `dr_local`, `cr_local`, `tax_usd`, `tax_local`, `payment_currency`, `created_at`) VALUES
(1, '2025-12-27', 'Cash', 'Payment', NULL, NULL, NULL, 'Paid Lightbill', 0.00, 100.00, 0.00, 0.00, 0.00, 0.00, 0.00, NULL, '2025-12-27 14:53:38'),
(2, '2025-12-27', 'Cash', 'Payment', NULL, NULL, NULL, 'Gas Bill', 90.00, 100.00, 0.00, 9000.00, 0.00, 0.00, 0.00, NULL, '2025-12-27 15:03:24'),
(3, '2025-12-27', 'Bank', 'Payment', NULL, NULL, NULL, '', 90.00, 100.00, 0.00, 9000.00, 0.00, 0.00, 0.00, NULL, '2025-12-27 15:04:18'),
(4, '2025-12-27', 'Cash', 'Payment', NULL, NULL, NULL, '', 90.00, 0.00, 100.00, 0.00, 9000.00, 0.00, 0.00, NULL, '2025-12-27 15:05:24'),
(5, '2025-12-27', 'Cash', 'Payment', NULL, NULL, NULL, 'Software Bill', 90.00, 105.50, 0.00, 9495.00, 0.00, 0.00, 0.00, NULL, '2025-12-27 15:07:46'),
(6, '2025-12-27', 'Cash', 'Payment', NULL, NULL, NULL, 'Kitchen Bill', 0.00, 0.00, 0.00, 9500.00, 0.00, 0.00, 0.00, NULL, '2025-12-27 15:08:13'),
(7, '2025-12-27', 'Cash', 'Payment', 'PU-1001', NULL, NULL, 'Payment for PU-1001 (Rahul Singh)', 90.00, 600.00, 0.00, 54000.00, 0.00, 0.00, 0.00, NULL, '2025-12-27 15:16:28'),
(8, '2025-12-27', 'Bank', 'Payment', 'PU-1001', NULL, NULL, 'Payment for PU-1001 (Rahul Singh)', 0.00, 0.00, 0.00, 42500.00, 0.00, 0.00, 0.00, NULL, '2025-12-27 15:22:38'),
(9, '2025-12-27', 'Cash', 'Payment', NULL, NULL, NULL, 'laptop', 89.00, 54.00, 0.00, 4806.00, 0.00, 0.00, 0.00, NULL, '2025-12-27 15:25:02'),
(10, '2025-12-27', 'Bank', 'Payment', 'jhngtbjnhjn', NULL, NULL, 'Payment for PU-1001 (Rahul Singh)', 0.00, 0.00, 0.00, 9500.00, 0.00, 0.00, 0.00, NULL, '2025-12-27 15:30:50'),
(11, '2025-12-27', 'Cash', 'Payment', NULL, NULL, NULL, '', 88.00, 99.00, 0.00, 8712.00, 0.00, 0.00, 0.00, NULL, '2025-12-27 15:32:31'),
(12, '2025-12-27', 'Bank', 'Payment', 'PU-1001', NULL, NULL, 'Payment for PU-1001 (Rahul Singh)', 91.00, 150.00, 0.00, 13650.00, 0.00, 0.00, 0.00, NULL, '2025-12-27 15:41:50'),
(13, '2025-12-27', 'Cash', 'Payment', NULL, NULL, NULL, 'Gift from the company', 0.00, 0.00, 0.00, 0.00, 1000000.00, 0.00, 0.00, NULL, '2025-12-27 15:44:24'),
(14, '2025-12-27', 'Cash', 'Payment', NULL, NULL, NULL, 'Anuj bill', 0.00, 0.00, 0.00, 20000.00, 0.00, 0.00, 0.00, NULL, '2025-12-27 15:50:44'),
(15, '2025-12-27', 'Cash', 'Payment', 'PU-1001', NULL, NULL, 'Payment for PU-1001 (Rahul Singh)\'s broker', 0.00, 0.00, 0.00, 9500.00, 0.00, 0.00, 0.00, NULL, '2025-12-27 17:42:35'),
(16, '2025-12-27', 'Cash', 'Payment', 'PU-1001', NULL, NULL, 'Payment for PU-1001 (Rahul Singh)', 0.00, 0.00, 0.00, 0.00, 9500.00, 0.00, 0.00, NULL, '2025-12-27 17:45:43'),
(17, '2025-12-27', 'Cash', 'Payment', 'PU-1001', 'PARTY', 'Rahul Singh', 'Payment for PU-1001 (Rahul Singh)', 0.00, 0.00, 0.00, 9500.00, 0.00, 0.00, 0.00, NULL, '2025-12-27 17:53:13'),
(18, '2025-12-27', 'Cash', 'Payment', NULL, 'PARTY', 'Rajesh & Co', '', 0.00, 0.00, 0.00, 0.00, 9500.00, 0.00, 0.00, NULL, '2025-12-27 17:54:13'),
(19, '2025-12-27', 'Bank', 'Payment', NULL, NULL, NULL, 'Bought Furniture', 100.00, 100.00, 0.00, 10000.00, 0.00, 0.00, 0.00, NULL, '2025-12-27 17:58:11'),
(20, '2025-12-27', 'Cash', 'Payment', NULL, 'PARTY', 'Rakesh Jewels', 'Gift', 0.00, 0.00, 0.00, 0.00, 20000.00, 0.00, 0.00, NULL, '2025-12-27 18:15:40'),
(21, '2025-12-27', 'Cash', 'Payment', NULL, NULL, NULL, 'Bought the office Furniture', 90.00, 100.00, 0.00, 9000.00, 0.00, 0.00, 0.00, NULL, '2025-12-27 19:14:16'),
(22, '2025-12-27', 'Bank', 'Payment', 'PU-1007', 'BROKER', 'Rajiv Kumar', 'Payment for PU-1007 (Rajiv Kumar) ', 0.00, 0.00, 0.00, 25000.00, 0.00, 0.00, 0.00, NULL, '2025-12-27 19:15:07'),
(23, '2025-12-27', 'Cash', 'Payment', NULL, NULL, NULL, 'Bought Furniture', 0.00, 0.00, 0.00, 150000.00, 0.00, 0.00, 0.00, NULL, '2025-12-27 19:25:32'),
(24, '2025-12-27', 'Bank', 'Payment', 'PU-1008', 'BROKER', 'Rakesh Shah', 'Payment for PU-1008 (Rakesh Shah)', 91.00, 1040.00, 0.00, 94640.00, 0.00, 0.00, 0.00, NULL, '2025-12-27 19:26:30'),
(25, '2025-12-27', 'Cash', 'Payment', NULL, NULL, NULL, 'Bought Furniture for the office', 0.00, 0.00, 0.00, 160000.00, 0.00, 0.00, 0.00, NULL, '2025-12-27 19:34:38'),
(26, '2025-12-27', 'Bank', 'Payment', 'PU-1009', 'BROKER', 'Rakesh Shah', 'Payment for the brokerage of  PU-1009 (Rakesh Shah)', 0.00, 0.00, 0.00, 55455.00, 0.00, 0.00, 0.00, NULL, '2025-12-27 19:35:17'),
(27, '2025-12-28', 'Bank', 'Payment', 'PU-1012', 'TAX', 'TAX', '', 90.50, 747.94, 0.00, 67688.57, 0.00, 0.00, 0.00, 'Dollar', '2025-12-28 15:12:06'),
(28, '2025-12-28', 'Cash', 'Payment', NULL, 'GENERAL', NULL, 'Bought Sofa', 0.00, 0.00, 0.00, 15000.00, 0.00, 0.00, 0.00, 'Local', '2025-12-28 15:18:11'),
(29, '2025-12-28', 'Cash', 'Payment', 'PU-1012', 'TAX', 'TAX', '', 95.00, 747.94, 0.00, 71054.30, 0.00, 0.00, 0.00, 'Dollar', '2025-12-28 15:19:31'),
(30, '2025-12-28', 'Bank', 'Payment', NULL, 'GENERAL', 'General', 'Got the Birthday Gift', 0.00, 0.00, 0.00, 0.00, 25000.00, 0.00, 0.00, 'Local', '2025-12-28 15:20:29'),
(32, '2025-12-28', 'Cash', 'Receipt', 'PU-1010', 'TAX', 'TAX', 'tax payment', 91.15, 460.00, 0.00, 41929.00, 0.00, 0.00, 0.00, 'Dollar', '2025-12-28 15:43:27'),
(34, '2025-12-28', 'Cash', 'Payment', 'PU-1010', 'TAX', 'TAX', '', 91.00, 460.75, 0.00, 41928.25, 0.00, 0.00, 0.00, 'Dollar', '2025-12-28 15:49:16');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` varchar(50) NOT NULL COMMENT 'Stores invoice_num like PU-1001',
  `currency` varchar(10) NOT NULL COMMENT 'BOTH or INR',
  `qty` decimal(15,2) NOT NULL,
  `rate_usd` decimal(15,4) DEFAULT 0.0000,
  `rate_local` decimal(15,4) DEFAULT NULL,
  `conv_rate` decimal(15,4) DEFAULT 0.0000,
  `base_amount_usd` decimal(15,2) DEFAULT 0.00,
  `base_amount_local` decimal(15,2) DEFAULT NULL,
  `adjusted_amount_usd` decimal(15,2) DEFAULT 0.00,
  `adjusted_amount_local` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `currency`, `qty`, `rate_usd`, `rate_local`, `conv_rate`, `base_amount_usd`, `base_amount_local`, `adjusted_amount_usd`, `adjusted_amount_local`, `created_at`) VALUES
(23, 'PU-1001', 'BOTH', 100.00, 104.0000, 9360.0000, 90.0000, 10400.00, 936000.00, 9684.48, 871603.20, '2025-12-27 18:43:36'),
(24, 'PU-1002', 'BOTH', 150.00, 105.5000, 9495.0000, 90.0000, 15825.00, 1424250.00, 14888.16, 1339934.40, '2025-12-27 18:47:59'),
(25, 'PU-1002', 'INR', 200.00, 0.0000, 9700.0000, 0.0000, 0.00, 1940000.00, 0.00, 1825152.00, '2025-12-27 18:47:59'),
(26, 'PU-1003', 'INR', 100.00, 0.0000, 11500.0000, 0.0000, 0.00, 1150000.00, 0.00, 1081920.00, '2025-12-27 18:55:18'),
(27, 'PU-1006', 'INR', 150.00, 0.0000, 5161.0000, 0.0000, 0.00, 774150.00, 0.00, 728320.32, '2025-12-27 19:06:39'),
(30, 'PU-1007', 'BOTH', 150.00, 155.0000, 13950.0000, 90.0000, 23250.00, 2092500.00, 20320.50, 1828845.00, '2025-12-27 19:12:50'),
(31, 'PU-1007', 'INR', 160.00, 0.0000, 11000.0000, 0.0000, 0.00, 1760000.00, 0.00, 1538240.00, '2025-12-27 19:12:50'),
(34, 'PU-1008', 'BOTH', 101.00, 80.0000, 7200.0000, 90.0000, 8080.00, 727200.00, 7138.68, 642481.20, '2025-12-27 19:24:27'),
(35, 'PU-1008', 'INR', 145.00, 0.0000, 15400.0000, 0.0000, 0.00, 2233000.00, 0.00, 1972855.50, '2025-12-27 19:24:27'),
(40, 'PU-1010', 'BOTH', 100.00, 100.0000, 9000.0000, 90.0000, 10000.00, 900000.00, 10000.00, 900000.00, '2025-12-28 12:28:21'),
(41, 'PU-1010', 'LOCAL', 100.00, 0.0000, 100.0000, 0.0000, 0.00, 10000.00, 0.00, 10000.00, '2025-12-28 12:28:21'),
(42, 'PU-1011', 'BOTH', 100.00, 100.0000, 9000.0000, 90.0000, 10000.00, 900000.00, 9408.00, 846720.00, '2025-12-28 12:35:57'),
(43, 'PU-1011', 'LOCAL', 100.00, 0.0000, 9600.0000, 0.0000, 0.00, 960000.00, 0.00, 903168.00, '2025-12-28 12:35:57'),
(46, 'PU-1012', 'BOTH', 100.00, 100.0000, 9050.0000, 90.5000, 10000.00, 905000.00, 9408.00, 851424.00, '2025-12-28 14:33:52'),
(47, 'PU-1012', 'BOTH', 150.00, 110.0000, 9955.0000, 90.5000, 16500.00, 1493250.00, 15523.20, 1404849.60, '2025-12-28 14:33:52'),
(48, 'SA-1001', 'BOTH', 100.00, 100.0000, 9050.0000, 90.5000, 10000.00, 905000.00, 9506.00, 860293.00, '2025-12-28 14:42:29'),
(49, 'SA-1001', 'BOTH', 150.00, 150.0000, 13650.0000, 91.0000, 22500.00, 2047500.00, 21388.50, 1946353.50, '2025-12-28 14:42:29');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_txn`
--

CREATE TABLE `invoice_txn` (
  `txn_id` int(11) NOT NULL,
  `txn_type` varchar(20) NOT NULL COMMENT 'PU or SA',
  `txn_number` int(11) NOT NULL COMMENT 'Sequential number: 1001, 1002, 1003...',
  `invoice_num` varchar(50) NOT NULL COMMENT 'Formatted: PU-1001, SA-1001',
  `txn_date` date NOT NULL,
  `party_name` varchar(100) NOT NULL,
  `broker_name` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `brokerage_pct` decimal(5,2) DEFAULT 0.00,
  `cal1` decimal(15,2) DEFAULT 0.00,
  `cal2` decimal(15,2) DEFAULT 0.00,
  `cal3` decimal(15,2) DEFAULT 0.00,
  `brokerage_amt` decimal(15,2) DEFAULT 0.00,
  `brokerage_amt_usd` decimal(15,2) DEFAULT 0.00,
  `gross_amt_local` decimal(15,2) DEFAULT 0.00,
  `gross_amt_usd` decimal(15,2) DEFAULT 0.00,
  `tax_local` decimal(15,2) DEFAULT 0.00,
  `tax_usd` decimal(15,2) DEFAULT 0.00,
  `net_amount_local` decimal(15,2) DEFAULT 0.00,
  `net_amount_usd` decimal(15,2) DEFAULT 0.00,
  `party_status` tinyint(4) DEFAULT 1,
  `broker_status` tinyint(4) DEFAULT 0,
  `credit_days` int(11) DEFAULT 0,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_txn`
--

INSERT INTO `invoice_txn` (`txn_id`, `txn_type`, `txn_number`, `invoice_num`, `txn_date`, `party_name`, `broker_name`, `notes`, `brokerage_pct`, `cal1`, `cal2`, `cal3`, `brokerage_amt`, `brokerage_amt_usd`, `gross_amt_local`, `gross_amt_usd`, `tax_local`, `tax_usd`, `net_amount_local`, `net_amount_usd`, `party_status`, `broker_status`, `credit_days`, `due_date`, `created_at`, `updated_at`) VALUES
(10, 'PU', 1001, 'PU-1001', '2025-12-27', 'Rahul Singh', 'Raj Patel', 'Buying something', 0.00, -3.00, -4.00, 0.00, 17432.06, 0.00, 871603.20, 0.00, 3.00, 0.00, 897751.30, 0.00, 1, 1, 30, '2026-01-26', '2025-12-27 14:19:41', '2025-12-27 18:43:36'),
(11, 'PU', 1002, 'PU-1002', '2025-12-27', 'Rakesh Jewels', 'Ramesh Broker', 'Buying Polish Diamonds', 0.00, -2.00, -4.00, 0.00, 15825.43, 0.00, 3165086.40, 0.00, 5.00, 0.00, 3323340.00, 0.00, 1, 1, 90, '2026-03-27', '2025-12-27 18:47:59', '2025-12-27 18:48:29'),
(12, 'PU', 1003, 'PU-1003', '2025-12-27', 'Ravi Gems', '', 'Buying Diams', 0.00, -2.00, -4.00, 0.00, 10819.20, 0.00, 1081920.00, 0.00, 3.00, 0.00, 1114377.00, 0.00, 1, 0, 60, '2026-02-25', '2025-12-27 18:55:18', '2025-12-27 18:57:26'),
(15, 'PU', 1006, 'PU-1006', '2025-12-27', 'Rahul Singh', 'Amit Broker', 'jsbnvr vrkj v', 0.00, -2.00, -4.00, 0.00, 0.00, 0.00, 728320.32, 0.00, 2.00, 0.00, 742886.73, 0.00, 1, 1, 15, '2026-01-11', '2025-12-27 19:06:39', '2025-12-27 19:07:59'),
(16, 'PU', 1007, 'PU-1007', '2025-12-27', 'Ramesh Kumar', 'Rajiv Kumar', 'Buying Diams', 0.00, -5.00, -8.00, 0.00, 33670.85, 0.00, 3367085.00, 0.00, 3.00, 0.00, 3468097.55, 0.00, 1, 1, 60, '2026-02-25', '2025-12-27 19:11:44', '2025-12-27 19:12:50'),
(17, 'PU', 1008, 'PU-1008', '2025-12-27', 'Raghav Exports', 'Rakesh Shah', 'Buying Diams', 0.00, -5.00, -7.00, 0.00, 52306.73, 0.00, 2615336.70, 0.00, 5.00, 0.00, 2746103.54, 0.00, 1, 1, 60, '2026-02-25', '2025-12-27 19:23:24', '2025-12-27 19:24:27'),
(19, 'PU', 1010, 'PU-1010', '2025-12-28', 'Rajesh & Co', 'Raj Patel', 'Butyinfg', 1.00, -3.00, -5.00, 0.00, 8385.65, 92.15, 838565.00, 9215.00, 41928.25, 460.75, 880493.25, 9675.75, 1, 1, 45, '2026-02-11', '2025-12-28 12:28:21', '2025-12-28 12:28:21'),
(20, 'PU', 1011, 'PU-1011', '2025-12-28', 'Rajesh & Co', 'Rajiv Kumar', 'ierbvekvbkrjb', 1.50, -2.00, -4.00, 0.00, 26248.32, 141.12, 1749888.00, 9408.00, 61246.08, 329.28, 1811134.08, 9737.28, 1, 1, 45, '2026-02-11', '2025-12-28 12:35:57', '2025-12-28 12:35:57'),
(21, 'PU', 1012, 'PU-1012', '2025-12-28', 'Ravi Gems', 'Ramesh Broker', 'ramesh to rames3', 1.00, -2.00, -4.00, 0.00, 22562.74, 249.31, 2256273.60, 24931.20, 67688.21, 747.94, 2323961.81, 25679.14, 1, 1, 30, '2026-01-27', '2025-12-28 12:44:10', '2025-12-28 14:33:52'),
(22, 'SA', 1001, 'SA-1001', '2025-12-28', 'Rakesh Jewels', 'Amit Broker', 'Selling', 1.00, -2.00, -3.00, 0.00, 28066.47, 308.94, 2806646.50, 30894.50, 56132.93, 617.89, 2862779.43, 31512.39, 1, 1, 60, '2026-02-26', '2025-12-28 14:42:29', '2025-12-28 14:42:29');

-- --------------------------------------------------------

--
-- Table structure for table `parties`
--

CREATE TABLE `parties` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parties`
--

INSERT INTO `parties` (`id`, `name`, `created_at`) VALUES
(1, 'Ravi Gems', '2025-12-27 13:05:24'),
(2, 'Rahul Singh', '2025-12-27 13:05:24'),
(3, 'Ramesh Kumar', '2025-12-27 13:05:24'),
(4, 'Rajesh & Co', '2025-12-27 13:05:24'),
(5, 'Royal Traders', '2025-12-27 13:05:24'),
(6, 'Rakesh Jewels', '2025-12-27 13:05:24'),
(7, 'Raghav Exports', '2025-12-27 13:05:24'),
(8, 'Diamond Hub', '2025-12-27 13:05:24'),
(9, 'Shree Exports', '2025-12-27 13:05:24'),
(10, 'Amit Traders', '2025-12-27 13:05:24'),
(13, 'Test Party', '2025-12-27 13:18:32'),
(20, 'Smit Shah', '2025-12-28 15:27:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `brokers`
--
ALTER TABLE `brokers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `cash_bank_entries`
--
ALTER TABLE `cash_bank_entries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_invoice_id` (`invoice_id`);

--
-- Indexes for table `invoice_txn`
--
ALTER TABLE `invoice_txn`
  ADD PRIMARY KEY (`txn_id`),
  ADD KEY `idx_txn_type` (`txn_type`),
  ADD KEY `idx_txn_number` (`txn_type`,`txn_number`),
  ADD KEY `idx_invoice_num` (`invoice_num`),
  ADD KEY `idx_txn_date` (`txn_date`);

--
-- Indexes for table `parties`
--
ALTER TABLE `parties`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_name` (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `brokers`
--
ALTER TABLE `brokers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `cash_bank_entries`
--
ALTER TABLE `cash_bank_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `invoice_txn`
--
ALTER TABLE `invoice_txn`
  MODIFY `txn_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `parties`
--
ALTER TABLE `parties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
