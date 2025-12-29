-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 29, 2025 at 06:26 PM
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
(8, 'Test Broker', '2025-12-27 13:18:32'),
(15, 'Arpit Shah', '2025-12-29 09:06:29'),
(18, 'Varshish Shah', '2025-12-29 09:32:57');

-- --------------------------------------------------------

--
-- Table structure for table `cash_bank_entries`
--

CREATE TABLE `cash_bank_entries` (
  `id` int(11) NOT NULL,
  `txn_date` date NOT NULL DEFAULT curdate(),
  `account_type` enum('Cash','Bank') NOT NULL,
  `transaction_type` enum('Receipt','Payment') DEFAULT 'Payment',
  `invoice_num` varchar(50) DEFAULT NULL,
  `party_or_broker` varchar(10) DEFAULT NULL COMMENT 'PARTY or BROKER',
  `related_name` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `conversion_rate` decimal(12,2) DEFAULT 0.00,
  `dr_usd` decimal(15,2) DEFAULT 0.00,
  `cr_usd` decimal(15,2) DEFAULT 0.00,
  `dr_local` decimal(15,2) DEFAULT 0.00,
  `cr_local` decimal(15,2) DEFAULT 0.00,
  `payment_currency` enum('Dollar','Local') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cash_bank_entries`
--

INSERT INTO `cash_bank_entries` (`id`, `txn_date`, `account_type`, `transaction_type`, `invoice_num`, `party_or_broker`, `related_name`, `description`, `conversion_rate`, `dr_usd`, `cr_usd`, `dr_local`, `cr_local`, `payment_currency`, `created_at`) VALUES
(1, '2025-12-29', 'Bank', 'Payment', 'PU-1001', 'PARTY', 'Suresh Shah', 'Payment of Purchase', 90.00, 25388.43, 0.00, 2284958.70, 0.00, 'Dollar', '2025-12-29 09:12:45'),
(3, '2025-12-29', 'Cash', 'Payment', 'PU-1001', 'BROKER', 'Arpit Shah', '', 0.00, 0.00, 0.00, 22185.00, 0.00, 'Local', '2025-12-29 09:17:59'),
(5, '2025-12-29', 'Cash', 'Payment', 'SA-1001', 'BROKER', 'Varshish Shah', 'Paid Brokerage', 90.00, 220.50, 0.00, 19845.00, 0.00, 'Dollar', '2025-12-29 09:37:07'),
(6, '2025-12-29', 'Bank', 'Receipt', 'SA-1001', 'PARTY', 'Rajat Jain', 'Received Sales Amount', 90.00, 0.00, 22491.00, 0.00, 2024190.00, 'Dollar', '2025-12-29 09:39:54'),
(7, '2025-12-29', 'Cash', 'Payment', 'PU-1002', 'BROKER', 'Arpit Shah', 'Partial payment', 0.00, 0.00, 0.00, 5000.00, 0.00, 'Local', '2025-12-29 10:55:06'),
(8, '2026-01-05', 'Cash', 'Payment', 'PU-1002', 'BROKER', 'Arpit Shah', 'Full payment made', 0.00, 0.00, 0.00, 5051.00, 0.00, 'Local', '2025-12-29 10:55:54'),
(10, '2025-12-29', 'Bank', 'Receipt', 'SA-1002', 'PARTY', 'Rajat Jain', 'Partial Payment Received', 0.00, 0.00, 0.00, 0.00, 1500000.00, 'Local', '2025-12-29 15:18:10');

-- --------------------------------------------------------

--
-- Table structure for table `financial_openings`
--

CREATE TABLE `financial_openings` (
  `id` int(11) NOT NULL,
  `financial_year` varchar(20) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `op_stock_qty` decimal(15,2) DEFAULT 0.00,
  `op_stock_val` decimal(15,2) DEFAULT 0.00,
  `op_cash_local` decimal(15,2) DEFAULT 0.00,
  `op_bank_local` decimal(15,2) DEFAULT 0.00,
  `op_cash_usd` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `financial_openings`
--

INSERT INTO `financial_openings` (`id`, `financial_year`, `start_date`, `end_date`, `op_stock_qty`, `op_stock_val`, `op_cash_local`, `op_bank_local`, `op_cash_usd`, `created_at`) VALUES
(1, '2025-2026', '2025-04-01', '2026-03-31', 400.00, 5400000.00, 100000.00, 500000.00, 5000.00, '2025-12-29 16:09:49');

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
(5, 'PU-1001', 'BOTH', 100.00, 106.0000, 9540.0000, 90.0000, 10600.00, 954000.00, 9972.48, 897523.20, '2025-12-29 09:16:57'),
(6, 'PU-1001', 'BOTH', 150.00, 104.0000, 9360.0000, 90.0000, 15600.00, 1404000.00, 14676.48, 1320883.20, '2025-12-29 09:16:57'),
(7, 'SA-1001', 'BOTH', 150.00, 150.0000, 13500.0000, 90.0000, 22500.00, 2025000.00, 22050.00, 1984500.00, '2025-12-29 09:32:57'),
(9, 'PU-1002', 'BOTH', 151.50, 80.0000, 7200.0000, 90.0000, 12120.00, 1090800.00, 11168.58, 1005172.20, '2025-12-29 10:11:17'),
(10, 'SA-1002', 'BOTH', 150.00, 200.0000, 18000.0000, 90.0000, 30000.00, 2700000.00, 28224.00, 2540160.00, '2025-12-29 10:59:14');

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
(1, 'PU', 1001, 'PU-1001', '2025-12-29', 'Suresh Shah', 'Arpit Shah', 'Buying Diams', 1.00, -2.00, -4.00, 0.00, 22184.06, 246.49, 2218406.40, 24648.96, 66552.19, 739.47, 2284958.59, 25388.43, 1, 1, 30, '2026-01-28', '2025-12-29 09:06:29', '2025-12-29 09:08:57'),
(2, 'SA', 1001, 'SA-1001', '2025-12-29', 'Rajat Jain', 'Varshish Shah', 'Selling the diams', 1.00, -2.00, 0.00, 0.00, 19845.00, 220.50, 1984500.00, 22050.00, 39690.00, 441.00, 2024190.00, 22491.00, 1, 1, 45, '2026-02-12', '2025-12-29 09:32:57', '2025-12-29 09:32:57'),
(3, 'PU', 1002, 'PU-1002', '2025-12-29', 'Suresh Shah', 'Arpit Shah', 'Buying', 1.00, -3.00, -5.00, 0.00, 10051.72, 111.69, 1005172.20, 11168.58, 20103.44, 223.37, 1025275.64, 11391.95, 1, 1, 30, '2026-01-28', '2025-12-29 10:10:18', '2025-12-29 10:10:18'),
(4, 'SA', 1002, 'SA-1002', '2025-12-29', 'Rajat Jain', 'Varshish Shah', 'Sold The items', 0.50, -2.00, -4.00, 0.00, 12700.80, 141.12, 2540160.00, 28224.00, 25401.60, 282.24, 2565561.60, 28506.24, 1, 1, 20, '2026-01-18', '2025-12-29 10:59:14', '2025-12-29 10:59:14');

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
(20, 'Smit Shah', '2025-12-28 15:27:47'),
(23, 'Suresh Shah', '2025-12-29 09:06:29'),
(26, 'Rajat Jain', '2025-12-29 09:32:57');

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
-- Indexes for table `financial_openings`
--
ALTER TABLE `financial_openings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `financial_year` (`financial_year`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `cash_bank_entries`
--
ALTER TABLE `cash_bank_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `financial_openings`
--
ALTER TABLE `financial_openings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `invoice_txn`
--
ALTER TABLE `invoice_txn`
  MODIFY `txn_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `parties`
--
ALTER TABLE `parties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
