-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 05, 2026 at 11:07 PM
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
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `id` int(11) NOT NULL,
  `type` varchar(30) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_notifications`
--

INSERT INTO `admin_notifications` (`id`, `type`, `title`, `message`, `created_by`, `is_read`, `created_at`) VALUES
(1, 'ecommerce', 'Sale completed', 'A product sale was completed in the ecommerce module.', 'admin', 1, '2026-06-30 06:43:44'),
(2, 'ecommerce', 'Product removed', 'A product was removed from inventory.', 'admin', 1, '2026-06-30 06:43:52'),
(3, 'ecommerce', 'New product added', 'A new product was added to inventory.', 'admin', 1, '2026-06-30 06:44:04'),
(4, 'ecommerce', 'Sale completed', 'A product sale was completed in the ecommerce module.', 'admin', 1, '2026-06-30 06:44:21'),
(5, 'ecommerce', 'Inventory updated', 'Product stock was increased in the ecommerce module.', 'admin', 1, '2026-06-30 06:44:57'),
(6, 'staff', 'Password reset handled', 'A password reset request was approved or rejected.', 'admin', 1, '2026-06-30 06:47:34'),
(7, 'staff', 'Staff status changed', 'A staff account status was changed.', 'admin', 1, '2026-06-30 06:49:24'),
(8, 'staff', 'Staff status changed', 'A staff account status was changed.', 'admin', 1, '2026-06-30 06:49:41'),
(9, 'member', 'Member archived', 'A member account was archived from the management page.', 'admin', 1, '2026-06-30 06:50:00'),
(10, 'staff', 'Staff status changed', 'A staff account status was changed.', 'admin', 1, '2026-06-30 06:52:34'),
(11, 'staff', 'Staff status changed', 'A staff account status was changed.', 'admin', 1, '2026-06-30 06:52:36'),
(12, 'staff', 'Staff archived', 'A staff account was archived.', 'admin', 1, '2026-06-30 06:52:38'),
(13, 'staff', 'Staff recovered', 'A previously archived staff account was recovered.', 'admin', 1, '2026-06-30 06:53:48'),
(14, 'staff', 'Staff archived', 'A staff account was archived.', 'admin', 1, '2026-06-30 06:53:57'),
(15, 'wallet', 'Wallet credit added', 'Admin added credit to a member wallet.', 'admin', 1, '2026-06-30 06:54:09'),
(16, 'member', 'Member archived', 'A member account was archived from the management page.', 'admin', 1, '2026-06-30 06:55:11'),
(17, 'member', 'Member archived', 'A member account was archived from the management page.', 'admin', 1, '2026-06-30 06:55:19'),
(18, 'staff', 'Staff recovered', 'A previously archived staff account was recovered.', 'admin', 1, '2026-06-30 06:58:11'),
(19, 'member', 'Member archived', 'A member account was archived from the management page.', 'admin', 1, '2026-06-30 06:58:18'),
(20, 'member', 'Member archived', 'A member account was archived from the management page.', 'admin', 1, '2026-06-30 06:58:23'),
(21, 'ecommerce', 'Sale completed', 'A product sale was completed in the ecommerce module.', 'admin', 1, '2026-06-30 07:09:46'),
(22, 'member', 'Member archived', 'A member account was archived from the management page.', 'admin', 1, '2026-06-30 07:10:08'),
(23, 'staff', 'Staff status changed', 'A staff account status was changed.', 'admin', 1, '2026-06-30 07:11:22'),
(24, 'staff', 'Staff archived', 'A staff account was archived.', 'admin', 1, '2026-06-30 07:11:31'),
(25, 'staff', 'Staff recovered', 'A previously archived staff account was recovered.', 'admin', 1, '2026-06-30 07:11:37'),
(26, 'ecommerce', 'Inventory updated', 'Product stock was increased in the ecommerce module.', 'admin', 1, '2026-06-30 07:12:02'),
(27, 'ecommerce', 'Product archived', 'A product was archived from inventory.', 'admin', 1, '2026-06-30 07:24:27'),
(28, 'ecommerce', 'Product restored', 'A product was restored to inventory.', 'admin', 1, '2026-06-30 07:24:38'),
(29, 'ecommerce', 'Inventory decreased', 'Product stock was decreased in the ecommerce module.', 'admin', 1, '2026-06-30 07:24:55'),
(30, 'staff', 'Staff account created', 'A new staff account was added and is ready for use.', 'admin', 1, '2026-06-30 16:48:35'),
(31, 'member', 'Session member created', 'A new session member was added to the system.', 'admin', 0, '2026-07-01 08:01:59'),
(32, 'member', 'Member archived', 'A member account was archived from the management page.', 'admin', 0, '2026-07-01 08:12:42'),
(33, 'member', 'Session member created', 'A new session member was added to the system.', 'admin', 0, '2026-07-01 08:12:55'),
(34, 'staff', 'Staff account created', 'A new staff account was added and is ready for use.', 'admin', 1, '2026-07-01 08:15:04'),
(35, 'staff', 'Password reset handled', 'A password reset request was approved or rejected.', 'admin', 0, '2026-07-01 08:18:20'),
(36, 'member', 'Session member created', 'A new session member was added to the system.', 'admin', 0, '2026-07-01 08:27:25'),
(37, 'staff', 'Staff archived', 'A staff account was archived.', 'admin', 0, '2026-07-01 08:27:56'),
(38, 'staff', 'Staff account created', 'A new staff account was added and is ready for use.', 'admin', 0, '2026-07-01 08:28:22'),
(39, 'staff', 'Staff account created', 'A new staff account was added and is ready for use.', 'admin', 1, '2026-07-01 08:29:19'),
(40, 'support', 'New support request', 'A member submitted a new support ticket: test', 'asd asd', 1, '2026-07-01 08:37:36'),
(41, 'support', 'Support reply sent', 'A staff member replied to a support conversation.', 'admin', 1, '2026-07-01 08:39:40'),
(42, 'support', 'Support reply sent', 'A staff member replied to a support conversation.', 'admin', 1, '2026-07-01 08:40:13'),
(43, 'support', 'New support request', 'A member submitted a new support ticket: test1', 'asd asd', 1, '2026-07-01 08:42:34'),
(44, 'support', 'Support reply sent', 'A staff member replied to a support conversation.', 'admin', 1, '2026-07-01 08:42:38'),
(45, 'support', 'New support request', 'A member submitted a new support ticket: test1', 'asd asd', 1, '2026-07-01 08:46:51'),
(46, 'support', 'Support reply received', 'A member replied to an existing support conversation.', 'asd asd', 1, '2026-07-01 08:52:50'),
(47, 'support', 'Support reply sent', 'A staff member replied to a support conversation.', 'admin', 1, '2026-07-01 08:53:07'),
(48, 'support', 'Support reply received', 'A member replied to an existing support conversation.', 'asd asd', 1, '2026-07-01 08:53:14'),
(49, 'support', 'Support reply received', 'A member replied to an existing support conversation.', 'asd asd', 1, '2026-07-01 08:53:26'),
(50, 'support', 'Support reply sent', 'A staff member replied to a support conversation.', 'admin', 1, '2026-07-01 08:55:11'),
(51, 'support', 'Support reply sent', 'A staff member replied to a support conversation.', 'admin', 1, '2026-07-01 08:55:15'),
(52, 'support', 'Support reply sent', 'A staff member replied to a support conversation.', 'admin', 1, '2026-07-01 08:55:17'),
(53, 'support', 'Support reply sent', 'A staff member replied to a support conversation.', 'admin', 1, '2026-07-01 08:55:20'),
(54, 'support', 'Support reply sent', 'A staff member replied to a support conversation.', 'admin', 1, '2026-07-01 08:55:41'),
(55, 'support', 'Support reply sent', 'A staff member replied to a support conversation.', 'admin', 1, '2026-07-01 08:59:54'),
(56, 'staff', 'Staff account created', 'A new staff account was added and is ready for use.', 'admin', 1, '2026-07-01 09:49:02'),
(57, 'support', 'Support reply sent', 'A staff member replied to a support conversation.', 'test', 1, '2026-07-01 09:54:45'),
(58, 'staff', 'Staff account updated', 'Staff account details were updated.', 'admin', 1, '2026-07-06 00:01:58'),
(59, 'support', 'Support reply sent', 'A staff member replied to a support conversation.', 'staff', 1, '2026-07-06 02:05:07'),
(60, 'support', 'Support reply sent', 'A staff member replied to a support conversation.', 'staff', 1, '2026-07-06 02:05:22'),
(61, 'support', 'Support reply sent', 'A staff member replied to a support conversation.', 'admin', 1, '2026-07-06 02:05:30'),
(62, 'staff', 'Password reset approved', 'An auto-login link was sent to the staff.', 'admin', 0, '2026-07-06 04:11:40');

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

--
-- Dumping data for table `blocked_rfids`
--

INSERT INTO `blocked_rfids` (`id`, `rfid`, `member_id`, `blocked_at`, `reason`) VALUES
(2, '1245543762', 34, '2026-06-29 23:46:04', 'lost'),
(3, '1245543762', 34, '2026-06-29 23:46:21', 'lost');

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
(14, 21, 'jerome bernante', 'session', 50.00, 'cash', '2026-04-17 19:51:42'),
(15, NULL, 'yordan', 'walk-in', 50.00, 'cash', '2026-04-28 17:41:13'),
(16, 24, 'sean roscoe', 'membership', 0.00, 'free', '2026-06-28 23:17:24'),
(17, 24, 'sean roscoe', 'membership', 0.00, 'free', '2026-06-28 23:17:42'),
(18, 24, 'sean roscoe', 'membership', 0.00, 'free', '2026-06-28 23:17:44'),
(19, 24, 'sean roscoe', 'membership', 0.00, 'free', '2026-06-28 23:17:45'),
(20, 24, 'sean roscoe', 'membership', 0.00, 'free', '2026-06-28 23:17:45'),
(21, 21, 'jerome bernante', 'session', 50.00, 'cash', '2026-06-28 23:18:08'),
(22, 24, 'sean roscoe', 'membership', 0.00, 'free', '2026-06-29 23:41:41'),
(23, 24, 'sean roscoe', 'membership', 0.00, 'free', '2026-06-29 23:41:47'),
(24, 21, 'jerome bernante', 'membership_renewal', 850.00, 'credit', '2026-06-30 05:28:17'),
(25, 21, 'jerome bernante', 'membership_renewal', 850.00, 'credit', '2026-06-30 05:28:42'),
(26, 19, 'alegria hosting', 'membership_renewal', 850.00, 'credit', '2026-06-30 05:29:47'),
(27, 19, 'alegria hosting', 'membership_renewal', 850.00, 'cash', '2026-06-30 05:30:35'),
(28, 19, 'alegria hosting', 'membership_renewal', 850.00, 'cash', '2026-06-30 05:34:30'),
(29, 34, 'jayson test', 'membership_renewal', 850.00, 'cash', '2026-06-30 05:35:07'),
(30, 19, 'alegria hosting', 'membership_renewal', 850.00, 'cash', '2026-06-30 05:44:47'),
(31, 21, 'jerome bernante', 'membership_renewal', 850.00, 'credit', '2026-06-30 05:45:32'),
(32, 21, 'jerome bernante', 'membership_renewal', 1800.00, 'credit', '2026-06-30 05:46:07'),
(33, 21, 'jerome bernante', 'membership_renewal', 1800.00, 'cash', '2026-06-30 05:46:22'),
(34, 34, 'jayson test', 'membership_renewal', 850.00, 'cash', '2026-06-30 06:24:00');

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
(38, NULL, 'asd', 'asd', '11111111111', 'asd', 'session', NULL, NULL, '$2y$10$RDOt1D97rwEZJAohrsoJ8uIM5CB.jeQ7CEijs6V30gfwPoyWtmqxi', 'asdasd', 'asd@gmail.com', NULL, '2026-07-01', 0.00, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `members_archived`
--

CREATE TABLE `members_archived` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `gmail` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `RFID` varchar(50) DEFAULT NULL,
  `archived_at` datetime DEFAULT current_timestamp(),
  `archived_by` varchar(100) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `original_data` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members_archived`
--

INSERT INTO `members_archived` (`id`, `member_id`, `first_name`, `last_name`, `gmail`, `phone`, `address`, `type`, `RFID`, `archived_at`, `archived_by`, `reason`, `original_data`) VALUES
(1, 5, 'asd', 'asd', 'asd@example.com', '', '', 'member', '111', '2026-06-29 11:32:43', 'system', 'No reason provided', '{\"id\":5,\"user_id\":null,\"first_name\":\"asd\",\"last_name\":\"asd\",\"phone\":\"\",\"address\":\"\",\"type\":\"member\",\"membership_start\":null,\"membership_end\":null,\"password\":\"$2y$10$upP3tN6xk9lmZXpcLTalbuBVSX4qcbF.La74LudLcegUGd8as.5Qa\",\"username\":\"asd\",\"gmail\":\"asd@example.com\",\"RFID\":\"111\",\"Joined_Date\":\"2026-04-13\",\"credit\":\"0.00\",\"plan_months\":null,\"membership_expiry\":null}'),
(4, 18, 'ben', 'onde', 'benonde@gmail.com', '098564645', 'caloocan', 'session', NULL, '2026-06-30 06:50:00', 'admin', 'No reason provided', '{\"id\":18,\"user_id\":null,\"first_name\":\"ben\",\"last_name\":\"onde\",\"phone\":\"098564645\",\"address\":\"caloocan\",\"type\":\"session\",\"membership_start\":null,\"membership_end\":null,\"password\":\"$2y$10$n7Vj0WpCibfHTaXsDK4gHOnRvQPyG9MCUwYsjhSXy5LhzT\\/IGmh\\/y\",\"username\":\"benonde\",\"gmail\":\"benonde@gmail.com\",\"RFID\":null,\"Joined_Date\":\"2026-04-15\",\"credit\":\"0.00\",\"plan_months\":null,\"membership_expiry\":null}'),
(5, 22, 'sean1258282482', 'roscoe', '', '', '', 'session', NULL, '2026-06-30 06:55:11', 'admin', 'No reason provided', '{\"id\":22,\"user_id\":null,\"first_name\":\"sean1258282482\",\"last_name\":\"roscoe\",\"phone\":\"\",\"address\":\"\",\"type\":\"session\",\"membership_start\":null,\"membership_end\":null,\"password\":\"$2y$10$OkRZTBHCMHgD6ENslKXuVepmF0Mw7z9Ly5mcD4bq35O2gMuHZQKwi\",\"username\":\"sean1258282482roscoe\",\"gmail\":\"\",\"RFID\":null,\"Joined_Date\":\"2026-06-28\",\"credit\":\"0.00\",\"plan_months\":null,\"membership_expiry\":null}'),
(6, 23, 'sean', 'roscoe1258282482', '', '', '', 'session', NULL, '2026-06-30 06:55:19', 'admin', 'sdfsdffsdf', '{\"id\":23,\"user_id\":null,\"first_name\":\"sean\",\"last_name\":\"roscoe1258282482\",\"phone\":\"\",\"address\":\"\",\"type\":\"session\",\"membership_start\":null,\"membership_end\":null,\"password\":\"$2y$10$.HEwYRweOZ4gWEAEGwcVWO5dO.Dh9Mr0t7VR3rJx9Tz\\/i9OBKjeNS\",\"username\":\"seanroscoe1258282482\",\"gmail\":\"\",\"RFID\":null,\"Joined_Date\":\"2026-06-28\",\"credit\":\"0.00\",\"plan_months\":null,\"membership_expiry\":null}'),
(7, 6, 'jayson', 'bernante', '', '', '', 'session', '11', '2026-06-30 06:58:18', 'admin', 'No reason provided', '{\"id\":6,\"user_id\":null,\"first_name\":\"jayson\",\"last_name\":\"bernante\",\"phone\":\"\",\"address\":\"\",\"type\":\"session\",\"membership_start\":null,\"membership_end\":null,\"password\":\"$2y$10$MNXZPTcntSM6U8Go8ebXXO\\/Rsum1Rdehwuy0tSdxiAby04aG\\/M6\\/m\",\"username\":\"jaysonbernante\",\"gmail\":\"\",\"RFID\":\"11\",\"Joined_Date\":\"2026-04-13\",\"credit\":\"440.00\",\"plan_months\":null,\"membership_expiry\":null}'),
(8, 27, 'aasd', 'sdsd', '', '11111111111', 'asdasdasdasd', 'session', NULL, '2026-06-30 06:58:23', 'admin', 'No reason provided', '{\"id\":27,\"user_id\":null,\"first_name\":\"aasd\",\"last_name\":\"sdsd\",\"phone\":\"11111111111\",\"address\":\"asdasdasdasd\",\"type\":\"session\",\"membership_start\":null,\"membership_end\":null,\"password\":\"$2y$10$BfGgVWbFm0v7jK.EaIr\\/t.hDplfes9D94QTk4OEWMNcqjgEw7UqAS\",\"username\":\"aasdsdsd\",\"gmail\":\"\",\"RFID\":null,\"Joined_Date\":\"2026-06-29\",\"credit\":\"0.00\",\"plan_months\":null,\"membership_expiry\":null}'),
(9, 24, 'sean', 'roscoe', '', '', '', 'member', '1258282482', '2026-06-30 07:10:08', 'admin', 'hjgjhg', '{\"id\":24,\"user_id\":null,\"first_name\":\"sean\",\"last_name\":\"roscoe\",\"phone\":\"\",\"address\":\"\",\"type\":\"member\",\"membership_start\":null,\"membership_end\":null,\"password\":\"$2y$10$AxyScLgRzXHazr0NETjkKuAHOGiwmwAZDEOfs7Z4UQp7teYLhwK.y\",\"username\":\"seanroscoe\",\"gmail\":\"\",\"RFID\":\"1258282482\",\"Joined_Date\":\"2026-06-28\",\"credit\":\"901.00\",\"plan_months\":1,\"membership_expiry\":\"2026-07-28\"}'),
(10, 36, 'zxczxc', 'zxczxc', 'zxc@gmail.com', '11111111111', 'zxczxc', 'session', NULL, '2026-07-01 08:12:42', 'admin', 'remove', '{\"id\":36,\"user_id\":null,\"first_name\":\"zxczxc\",\"last_name\":\"zxczxc\",\"phone\":\"11111111111\",\"address\":\"zxczxc\",\"type\":\"session\",\"membership_start\":null,\"membership_end\":null,\"password\":\"$2y$10$L251mRGfA1T4tS.VBCMn.OcM4p1JuPjBI39eY\\/H3\\/PrmffmNK6on6\",\"username\":\"zxczxczxczxc\",\"gmail\":\"zxc@gmail.com\",\"RFID\":null,\"Joined_Date\":\"2026-07-01\",\"credit\":\"0.00\",\"plan_months\":null,\"membership_expiry\":null}');

-- --------------------------------------------------------

--
-- Table structure for table `member_audit`
--

CREATE TABLE `member_audit` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `action` varchar(50) DEFAULT NULL,
  `staff_username` varchar(100) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `details` longtext DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `member_audit`
--

INSERT INTO `member_audit` (`id`, `member_id`, `action`, `staff_username`, `reason`, `details`, `created_at`) VALUES
(1, 5, 'archive', 'system', 'No reason provided', '{\"first_name\":\"asd\",\"last_name\":\"asd\"}', '2026-06-29 11:32:43'),
(2, 6, 'archive', 'system', 'No reason provided', '{\"first_name\":\"jayson\",\"last_name\":\"bernante\"}', '2026-06-29 23:03:03'),
(3, 33, 'add', 'system', NULL, '{\"first_name\":\"jayson\",\"last_name\":\"bernante\",\"type\":\"session\"}', '2026-06-29 23:06:02'),
(4, 34, 'add', 'system', NULL, '{\"first_name\":\"jayson\",\"last_name\":\"test\",\"type\":\"session\"}', '2026-06-29 23:45:16'),
(5, 34, 'edit', 'system', NULL, '{\"first_name\":\"jayson\",\"last_name\":\"test\",\"gmail\":\"jaysonbernante2@gmail.com\",\"phone\":\"11111111111\",\"rfid_changed\":false}', '2026-06-29 23:46:04'),
(6, 34, 'edit', 'system', NULL, '{\"first_name\":\"jayson\",\"last_name\":\"test\",\"gmail\":\"jaysonbernante2@gmail.com\",\"phone\":\"11111111111\",\"rfid_changed\":true}', '2026-06-29 23:46:21'),
(7, 6, 'recover', 'system', NULL, '{\"first_name\":\"jayson\",\"last_name\":\"bernante\"}', '2026-06-30 06:25:08'),
(8, 35, 'add', 'system', NULL, '{\"first_name\":\"asdasdasd\",\"last_name\":\"asdasd\",\"type\":\"session\"}', '2026-06-30 06:25:53'),
(9, 6, 'recover', 'system', NULL, '{\"first_name\":\"jayson\",\"last_name\":\"bernante\"}', '2026-06-30 06:28:47'),
(10, 18, 'archive', 'system', 'No reason provided', '{\"first_name\":\"ben\",\"last_name\":\"onde\"}', '2026-06-30 06:29:12'),
(11, 18, 'recover', 'system', NULL, '{\"first_name\":\"ben\",\"last_name\":\"onde\"}', '2026-06-30 06:41:59'),
(12, 18, 'archive', 'admin', 'No reason provided', '{\"first_name\":\"ben\",\"last_name\":\"onde\"}', '2026-06-30 06:50:00'),
(13, 22, 'archive', 'admin', 'No reason provided', '{\"first_name\":\"sean1258282482\",\"last_name\":\"roscoe\"}', '2026-06-30 06:55:11'),
(14, 23, 'archive', 'admin', 'sdfsdffsdf', '{\"first_name\":\"sean\",\"last_name\":\"roscoe1258282482\"}', '2026-06-30 06:55:19'),
(15, 6, 'archive', 'admin', 'No reason provided', '{\"first_name\":\"jayson\",\"last_name\":\"bernante\"}', '2026-06-30 06:58:18'),
(16, 27, 'archive', 'admin', 'No reason provided', '{\"first_name\":\"aasd\",\"last_name\":\"sdsd\"}', '2026-06-30 06:58:23'),
(17, 24, 'archive', 'admin', 'hjgjhg', '{\"first_name\":\"sean\",\"last_name\":\"roscoe\"}', '2026-06-30 07:10:08'),
(18, 36, 'add', 'admin', NULL, '{\"first_name\":\"zxczxc\",\"last_name\":\"zxczxc\",\"type\":\"session\"}', '2026-07-01 08:01:59'),
(19, 36, 'archive', 'admin', 'remove', '{\"first_name\":\"zxczxc\",\"last_name\":\"zxczxc\"}', '2026-07-01 08:12:42'),
(20, 37, 'add', 'admin', NULL, '{\"first_name\":\"zxc\",\"last_name\":\"zxc\",\"type\":\"session\"}', '2026-07-01 08:12:55'),
(21, 38, 'add', 'admin', NULL, '{\"first_name\":\"asd\",\"last_name\":\"asd\",\"type\":\"session\"}', '2026-07-01 08:27:25');

-- --------------------------------------------------------

--
-- Table structure for table `member_transactions`
--

CREATE TABLE `member_transactions` (
  `id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `member_name` varchar(150) DEFAULT NULL,
  `transaction_type` varchar(50) NOT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `payment_method` varchar(20) DEFAULT 'cash',
  `plan_months` int(11) DEFAULT NULL,
  `old_expiry` date DEFAULT NULL,
  `new_expiry` date DEFAULT NULL,
  `rfid` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `created_by` varchar(100) DEFAULT 'system'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `member_transactions`
--

INSERT INTO `member_transactions` (`id`, `member_id`, `member_name`, `transaction_type`, `amount`, `payment_method`, `plan_months`, `old_expiry`, `new_expiry`, `rfid`, `notes`, `created_at`, `created_by`) VALUES
(1, 21, 'jerome bernante', 'membership_renewal', 850.00, 'credit', 1, NULL, '2026-07-30', '1261553234', 'Membership renewal', '2026-06-30 05:28:17', 'system'),
(2, 21, 'jerome bernante', 'membership_renewal', 850.00, 'credit', 1, '2026-07-30', '2026-07-30', '1261553234', 'Membership renewal', '2026-06-30 05:28:42', 'system'),
(3, 19, 'alegria hosting', 'membership_renewal', 850.00, 'credit', 1, '2026-05-17', '2026-07-30', '1244219170', 'Membership renewal', '2026-06-30 05:29:47', 'system'),
(4, 19, 'alegria hosting', 'membership_renewal', 850.00, 'cash', 1, '2026-07-30', '2026-07-30', '1244219170', 'Membership renewal', '2026-06-30 05:30:35', 'system'),
(5, 19, 'alegria hosting', 'membership_renewal', 850.00, 'cash', 2, '2026-07-30', '2026-08-30', '1244219170', 'Membership renewal / advance', '2026-06-30 05:34:30', 'system'),
(6, 34, 'jayson test', 'membership_renewal', 850.00, 'cash', 1, NULL, '2026-07-30', '1247670786', 'Membership renewal / advance', '2026-06-30 05:35:07', 'system'),
(7, 19, 'alegria hosting', 'membership_renewal', 850.00, 'cash', 1, '2026-08-30', '2026-07-30', '1244219170', 'Membership renewal', '2026-06-30 05:44:47', 'system'),
(8, 21, 'jerome bernante', 'membership_renewal', 850.00, 'credit', 1, '2026-07-30', '2026-07-30', '1261553234', 'Membership renewal', '2026-06-30 05:45:32', 'system'),
(9, 21, 'jerome bernante', 'membership_renewal', 1800.00, 'credit', 3, '2026-07-30', '2026-09-30', '1261553234', 'Membership renewal', '2026-06-30 05:46:07', 'system'),
(10, 21, 'jerome bernante', 'membership_renewal', 1800.00, 'cash', 3, '2026-09-30', '2026-09-30', '1261553234', 'Membership renewal', '2026-06-30 05:46:22', 'system'),
(11, 34, 'jayson test', 'Membership Extension', 850.00, 'cash', 1, '2026-07-30', '2026-07-30', '1247670786', 'Membership Extension', '2026-06-30 06:24:00', 'system');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_requests`
--

CREATE TABLE `password_reset_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `handled_by` varchar(100) DEFAULT NULL,
  `handled_at` datetime DEFAULT NULL,
  `auto_login_token` varchar(128) DEFAULT NULL,
  `auto_login_expiry` datetime DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `requested_by` int(11) DEFAULT NULL,
  `requested_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset_requests`
--

INSERT INTO `password_reset_requests` (`id`, `user_id`, `username`, `reason`, `status`, `created_at`, `handled_by`, `handled_at`, `auto_login_token`, `auto_login_expiry`, `email`, `requested_by`, `requested_at`) VALUES
(7, 4, 'staff', 'forgot pass test', 'approved', '2026-06-29 23:16:20', 'admin', '2026-06-29 23:16:28', NULL, NULL, NULL, NULL, '2026-07-06 04:11:09'),
(8, 7, 'staff', 'Forgotpassword testing2', 'approved', '2026-06-29 23:55:29', 'admin', '2026-06-29 23:55:56', NULL, NULL, NULL, NULL, '2026-07-06 04:11:09'),
(9, 7, 'staff', 'forgotpassword test', 'approved', '2026-06-30 06:47:30', 'admin', '2026-06-30 06:47:34', NULL, NULL, NULL, NULL, '2026-07-06 04:11:09'),
(10, 15, 'zxc', 'zxc', 'approved', '2026-07-01 08:17:55', 'admin', '2026-07-01 08:18:20', NULL, NULL, NULL, NULL, '2026-07-06 04:11:09'),
(11, 10, 'staff', 'Forgot password request', 'approved', '2026-07-06 04:11:11', 'admin', '2026-07-06 04:11:39', 'a8794fafbbdf2496f5c3701ba4ba312d1def115f5676136b', '2026-07-06 04:21:39', 'Jaysonbernante@gmail.com', 10, '2026-07-06 04:11:11');

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
  `date_stocked` date DEFAULT curdate(),
  `is_archived` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_name`, `quantity`, `price`, `img`, `date_stocked`, `is_archived`) VALUES
(9, 'Protein', 858, 200.00, '', '2026-06-30', 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_stock_history`
--

CREATE TABLE `product_stock_history` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `change_qty` int(11) NOT NULL,
  `prev_qty` int(11) DEFAULT NULL,
  `new_qty` int(11) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `changed_by` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_stock_history`
--

INSERT INTO `product_stock_history` (`id`, `product_id`, `change_qty`, `prev_qty`, `new_qty`, `note`, `changed_by`, `created_at`) VALUES
(1, 9, -3, 861, 858, 'Decreased stock', 'admin', '2026-06-30 07:24:55');

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
(37, 8, 'Protein', 1, 199.00, 199.00, '2026-06-30 06:43:44', 'cash', '-', 'admin', 'dd034aebe330d201'),
(38, 9, 'Protein', 1, 200.00, 200.00, '2026-06-30 06:44:21', 'card', 'alegria hosting', 'admin', '207fe43e0defcedc'),
(39, 9, 'Protein', 1, 200.00, 200.00, '2026-06-30 07:09:46', 'cash', '-', 'admin', 'efe7eb09f21659a0');

-- --------------------------------------------------------

--
-- Table structure for table `staff_archive`
--

CREATE TABLE `staff_archive` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `role` varchar(50) NOT NULL,
  `archived_at` datetime DEFAULT current_timestamp(),
  `archived_by` varchar(100) DEFAULT NULL,
  `reason` varchar(255) DEFAULT 'archived'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_archive`
--

INSERT INTO `staff_archive` (`id`, `user_id`, `username`, `email`, `role`, `archived_at`, `archived_by`, `reason`) VALUES
(2, 6, 'asdasd', 'asdasd@gmail.com', 'staff', '2026-06-29 10:20:08', 'admin', 'archived'),
(7, 15, 'zxc', 'zxc@gmail.com', 'staff', '2026-07-01 08:27:56', 'admin', 'archived');

-- --------------------------------------------------------

--
-- Table structure for table `support_conversations`
--

CREATE TABLE `support_conversations` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `support_conversations`
--

INSERT INTO `support_conversations` (`id`, `member_id`, `subject`, `status`, `created_at`, `updated_at`) VALUES
(1, 38, 'test', 'closed', '2026-07-01 08:37:36', '2026-07-01 08:42:38'),
(2, 38, 'test1', 'closed', '2026-07-01 08:42:34', '2026-07-01 09:00:08'),
(3, 38, 'test1', 'open', '2026-07-01 08:46:51', '2026-07-06 02:05:30');

-- --------------------------------------------------------

--
-- Table structure for table `support_messages`
--

CREATE TABLE `support_messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_type` varchar(20) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `sender_name` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `support_messages`
--

INSERT INTO `support_messages` (`id`, `conversation_id`, `sender_type`, `sender_id`, `sender_name`, `message`, `created_at`) VALUES
(1, 1, 'member', 38, 'asd asd', 'testing', '2026-07-01 08:37:36'),
(2, 1, 'admin', 1, 'admin', 'okie', '2026-07-01 08:39:40'),
(3, 1, 'admin', 1, 'admin', 'asd', '2026-07-01 08:40:13'),
(4, 2, 'member', 38, 'asd asd', 'test1', '2026-07-01 08:42:34'),
(5, 1, 'admin', 1, 'admin', 'asd', '2026-07-01 08:42:38'),
(6, 3, 'member', 38, 'asd asd', 'test1', '2026-07-01 08:46:51'),
(7, 2, 'member', 38, 'asd asd', 'asd', '2026-07-01 08:52:50'),
(8, 2, 'admin', 1, 'admin', 'asd', '2026-07-01 08:53:07'),
(9, 2, 'member', 38, 'asd asd', 'asd', '2026-07-01 08:53:14'),
(10, 2, 'member', 38, 'asd asd', 'cge', '2026-07-01 08:53:26'),
(11, 2, 'admin', 1, 'admin', 'asd', '2026-07-01 08:55:11'),
(12, 2, 'admin', 1, 'admin', 'asd', '2026-07-01 08:55:15'),
(13, 2, 'admin', 1, 'admin', 'asd', '2026-07-01 08:55:17'),
(14, 2, 'admin', 1, 'admin', 'asd', '2026-07-01 08:55:20'),
(15, 2, 'admin', 1, 'admin', 'asd', '2026-07-01 08:55:41'),
(16, 2, 'admin', 1, 'admin', 'asd', '2026-07-01 08:59:54'),
(17, 3, 'staff', 19, 'test', 'hgfhtf', '2026-07-01 09:54:45'),
(18, 3, 'staff', 10, 'staff', 'asdasd', '2026-07-06 02:05:07'),
(19, 3, 'staff', 10, 'staff', 'testing staff', '2026-07-06 02:05:22'),
(20, 3, 'admin', 1, 'admin', 'testing admin', '2026-07-06 02:05:30');

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
  `status` varchar(10) NOT NULL DEFAULT 'active',
  `failed_login_attempts` int(11) NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `remember_expires_at` datetime DEFAULT NULL,
  `password_reset_required` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `created_at`, `status`, `failed_login_attempts`, `locked_until`, `remember_token`, `remember_expires_at`, `password_reset_required`) VALUES
(1, 'admin', '$2y$10$m1/cRs5rdJpdi1pq8PiLH.ladcjeYv1/bSD8kzbbX/YX6aJfyx6oy', 'jaysonbernante1@gmail.com', 'super_admin', '2026-04-12 08:43:31', 'active', 0, NULL, NULL, NULL, 0),
(10, 'staff', '$2y$10$4ZqSHJr4dgWX6kKWn6NhKO5enfymLP/ucbG75q8thsi6f29RjyXxW', 'jaysonbernante@gmail.com', 'staff', '2026-06-29 23:11:37', 'active', 0, NULL, NULL, NULL, 0),
(11, 'admin1', '$2y$10$px.QfLkwIBzrwMK6WdggeO7GFYLhuGQ.iIfg1DiJujl6MxHo8QHOe', 'jaysonbernante0@gmail.com', 'super_admin', '2026-06-30 08:43:21', 'active', 0, NULL, NULL, NULL, 0),
(12, 'asd', '$2y$10$ox45y54JExAkmTzHl36Z8uYCuXZ0/aPnoxqQpIVH414PC8D7AJ1wy', 'asd@gmail.com', 'super_admin', '2026-06-30 08:45:55', 'active', 0, NULL, NULL, NULL, 0),
(14, 'asdasd', '$2y$10$DZBwALe4x4b8ujyhk11A2OSiP./CyGPxewvZOwl/3bX5opyLoa0mO', 'sdsdssdsd@gmail.com', 'super_admin', '2026-06-30 08:48:35', 'active', 0, NULL, NULL, NULL, 0),
(16, 'asdasdasd', '$2y$10$Zt1jefASzgFkTiN8JaC2zOVFaJnLfnJ.Bfak./ysBeXslA19W9mZG', 'asdasdasd@gmail.com', 'staff', '2026-07-01 00:28:22', 'active', 0, NULL, NULL, NULL, 0),
(18, 'asdasdasda', '$2y$10$0WeeUNxRSUjieBwN9alyeeoaarumXqwvqNqqzSGiXgdCFzQT6dW6u', 'asdasd@gmail.com', 'super_admin', '2026-07-01 00:29:19', 'active', 0, NULL, NULL, NULL, 0),
(19, 'test', '$2y$10$pxn3llye8z1HoysFrROmO.kvxgJDuToWfNEBVDHGUMK9htHLlxSLm', 'test@gmail.com', 'staff', '2026-07-01 01:49:02', 'active', 2, NULL, NULL, NULL, 0);

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
(1, 19, 'credit_add', 1111.00, 111.00, 1222.00, 'Credit input', 'admin', '2026-04-17 10:19:51'),
(2, 24, 'credit_add', 200.00, 0.00, 200.00, 'Credit input', 'admin', '2026-06-28 15:25:42'),
(3, 24, 'credit_add', 1000.00, 1.00, 1001.00, 'Credit input', 'admin', '2026-06-28 15:29:53'),
(4, 24, 'refund', -100.00, 1001.00, 901.00, 'Refund', 'admin', '2026-06-28 15:31:14'),
(5, 21, 'credit_add', 11111.00, 0.00, 11111.00, 'Credit input', 'admin', '2026-06-28 19:01:50'),
(6, 21, 'refund', -11111.00, 11111.00, 0.00, 'over cash in', 'admin', '2026-06-29 02:11:43'),
(7, 21, 'credit_add', 5000.00, 0.00, 5000.00, 'Credit input', 'admin', '2026-06-29 02:12:22'),
(8, 34, 'credit_add', 500.00, 0.00, 500.00, 'Credit input', 'admin', '2026-06-29 21:07:45'),
(9, 19, 'credit_add', 1.00, 172.00, 173.00, 'Credit input', 'admin', '2026-06-29 22:54:09');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `members_archived`
--
ALTER TABLE `members_archived`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `member_audit`
--
ALTER TABLE `member_audit`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `member_transactions`
--
ALTER TABLE `member_transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_stock_history`
--
ALTER TABLE `product_stock_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff_archive`
--
ALTER TABLE `staff_archive`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `support_conversations`
--
ALTER TABLE `support_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `updated_at` (`updated_at`);

--
-- Indexes for table `support_messages`
--
ALTER TABLE `support_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `created_at` (`created_at`);

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
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `blocked_rfids`
--
ALTER TABLE `blocked_rfids`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `entry_logs`
--
ALTER TABLE `entry_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `members_archived`
--
ALTER TABLE `members_archived`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `member_audit`
--
ALTER TABLE `member_audit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `member_transactions`
--
ALTER TABLE `member_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `product_stock_history`
--
ALTER TABLE `product_stock_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `staff_archive`
--
ALTER TABLE `staff_archive`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `support_conversations`
--
ALTER TABLE `support_conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `support_messages`
--
ALTER TABLE `support_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
