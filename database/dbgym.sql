-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 20, 2026 at 12:13 PM
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
-- Database: `dbgym`
--

-- --------------------------------------------------------

--
-- Table structure for table `blocked_rfids`
--

CREATE TABLE `blocked_rfids` (
  `id` int(11) NOT NULL,
  `rfid` varchar(100) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `blocked_at` datetime DEFAULT current_timestamp(),
  `reason` varchar(100) DEFAULT 'lost'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `entry_logs`
--

CREATE TABLE `entry_logs` (
  `id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `member_name` varchar(100) NOT NULL DEFAULT 'Walk-in',
  `entry_type` varchar(20) NOT NULL DEFAULT 'session',
  `amount_charged` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(20) NOT NULL DEFAULT 'cash',
  `entry_time` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `entry_logs`
--

INSERT INTO `entry_logs` (`id`, `member_id`, `member_name`, `entry_type`, `amount_charged`, `payment_method`, `entry_time`) VALUES
(1, 5, 'asd', 'membership', 0.00, 'free', '2026-04-14 03:11:26'),
(2, NULL, 'asd', 'walk-in', 50.00, 'cash', '2026-04-14 03:11:58'),
(3, 6, 'jayson bernante', 'session', 50.00, 'credit', '2026-04-15 22:16:14'),
(4, 5, 'asd', 'membership', 0.00, 'free', '2026-04-15 22:24:22'),
(5, NULL, 'yordan', 'walk-in', 50.00, 'cash', '2026-04-15 22:25:11'),
(6, 19, 'alegria hosting', 'membership', 0.00, 'free', '2026-04-17 17:59:15'),
(7, 19, 'alegria hosting', 'membership', 0.00, 'free', '2026-04-17 17:59:18'),
(8, 19, 'alegria hosting', 'membership', 0.00, 'free', '2026-04-17 17:59:19'),
(9, 19, 'alegria hosting', 'membership', 0.00, 'free', '2026-04-17 17:59:26'),
(10, 19, 'alegria hosting', 'membership', 0.00, 'free', '2026-04-17 17:59:34'),
(11, 20, 'jerome bernante', 'membership', 0.00, 'free', '2026-04-17 19:49:24'),
(12, 20, 'jerome bernante', 'membership', 0.00, 'free', '2026-04-17 19:49:33'),
(13, 21, 'jerome bernante', 'session', 50.00, 'cash', '2026-04-17 19:50:25'),
(14, 21, 'jerome bernante', 'session', 50.00, 'cash', '2026-04-17 19:51:42');

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `membership_start` date DEFAULT NULL,
  `membership_end` date DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `username` varchar(50) NOT NULL,
  `gmail` varchar(100) NOT NULL,
  `RFID` varchar(50) DEFAULT NULL,
  `Joined_Date` date DEFAULT curdate(),
  `credit` decimal(10,2) DEFAULT 0.00,
  `plan_months` int(11) DEFAULT NULL,
  `membership_expiry` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `user_id`, `first_name`, `last_name`, `phone`, `address`, `type`, `membership_start`, `membership_end`, `password`, `username`, `gmail`, `RFID`, `Joined_Date`, `credit`, `plan_months`, `membership_expiry`) VALUES
(5, NULL, 'asd', '', '', '', 'member', NULL, NULL, '$2y$10$upP3tN6xk9lmZXpcLTalbuBVSX4qcbF.La74LudLcegUGd8as.5Qa', 'asd', 'asd@example.com', '111', '2026-04-13', 0.00, NULL, NULL),
(6, NULL, 'jayson', 'bernante', '', '', 'session', NULL, NULL, '$2y$10$MNXZPTcntSM6U8Go8ebXXO/Rsum1Rdehwuy0tSdxiAby04aG/M6/m', 'jaysonbernante', '', '11', '2026-04-13', 440.00, NULL, NULL),
(18, NULL, 'ben', 'onde', '098564645', 'caloocan', 'session', NULL, NULL, '$2y$10$n7Vj0WpCibfHTaXsDK4gHOnRvQPyG9MCUwYsjhSXy5LhzT/IGmh/y', 'benonde', 'benonde@gmail.com', NULL, '2026-04-15', 0.00, NULL, NULL),
(19, NULL, 'alegria', 'hosting', '123123123123', 'asdasdd', 'member', NULL, NULL, '$2y$10$UpyVMci82F8J44PeQPJVYeFlwO7ZskQGGd4D3BacUJHp14tDZHpRW', 'alegriahosting', 'alegriasystemmngr@gmail.com', '1244219170', '2026-04-17', 1222.00, 1, '2026-05-17'),
(21, NULL, 'jerome', 'bernante', '', '', 'session', NULL, NULL, '$2y$10$bBZ27KQTtOkqGCEBmeSvL.d4PLWAYb32sN0oNyhdFQOadMMSYs5rS', 'jeromebernante', '', '1261553234', '2026-04-17', 0.00, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `img` varchar(255) DEFAULT NULL,
  `date_stocked` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_name`, `quantity`, `price`, `img`, `date_stocked`) VALUES
(6, 'shabu', 82, 100.00, 'prod_1776262814_8334df77.jpg', '2026-04-15'),
(7, 'shake', 4, 10.00, '', '2026-04-15');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `qty_sold` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `sold_at` datetime DEFAULT current_timestamp(),
  `payment_method` varchar(20) DEFAULT 'cash',
  `member_name` varchar(100) DEFAULT NULL,
  `transacted_by` varchar(100) DEFAULT NULL,
  `transaction_id` varchar(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `product_id`, `product_name`, `qty_sold`, `unit_price`, `total`, `sold_at`, `payment_method`, `member_name`, `transacted_by`, `transaction_id`) VALUES
(26, 6, 'shabu', 5, 100.00, 500.00, '2026-04-15 22:20:59', 'cash', '-', 'admin', 'f7af2b85cab86017'),
(27, 6, 'shabu', 5, 100.00, 500.00, '2026-04-15 22:22:51', 'card', 'jayson bernante', 'admin', 'd9052a090e0a302e'),
(28, 6, 'shabu', 2, 100.00, 200.00, '2026-04-15 22:23:31', 'card', 'jayson bernante', 'admin', 'b0aeef5a5cd1d853'),
(29, 7, 'shake', 2, 10.00, 20.00, '2026-04-15 22:23:31', 'card', 'jayson bernante', 'admin', 'b0aeef5a5cd1d853'),
(30, 6, 'shabu', 3, 100.00, 300.00, '2026-04-17 18:17:12', 'cash', '-', 'admin', '9dd1ff9263481e56'),
(31, 6, 'shabu', 3, 100.00, 300.00, '2026-04-17 18:17:53', 'cash', '-', 'admin', '998f0871406432d8'),
(32, 7, 'shake', 4, 10.00, 40.00, '2026-04-17 18:17:53', 'cash', '-', 'admin', '998f0871406432d8'),
(33, 7, 'shake', 3, 10.00, 30.00, '2026-04-17 18:23:23', 'cash', '-', 'admin', '60c1ca0514e66802');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('super_admin','staff') DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(10) NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `created_at`, `status`) VALUES
(1, 'admin', '$2y$10$PEsXIWlBFf3dqwJnx8Z4..ucCQKz0K8JxsQpwG6CxQKbb4trcnvn6', 'admin@example.com', 'super_admin', '2026-04-12 08:43:31', 'active'),
(3, 'asd', '$2y$10$470XpC7qnO/sFKFU9Pmh1eK53s3NXeL1lJpjDhRHBIZijl8VCggda', 'asd@example.com', 'staff', '2026-04-13 11:46:38', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `transaction_type` varchar(32) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_before` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `reason` text DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallet_transactions`
--

INSERT INTO `wallet_transactions` (`id`, `member_id`, `transaction_type`, `amount`, `balance_before`, `balance_after`, `reason`, `created_by`, `created_at`) VALUES
(1, 19, 'credit_add', 1111.00, 111.00, 1222.00, 'Credit input', 'admin', '2026-04-17 10:19:51');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `blocked_rfids`
--
ALTER TABLE `blocked_rfids`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `entry_logs`
--
ALTER TABLE `entry_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `blocked_rfids`
--
ALTER TABLE `blocked_rfids`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `entry_logs`
--
ALTER TABLE `entry_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `members_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
