-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 01, 2025 at 03:41 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `vasudhara_milk`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_data` text DEFAULT NULL,
  `new_data` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 4, 'LOGOUT', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 02:01:32'),
(2, 1, '1', 'order_created', 0, '4', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 02:20:10'),
(3, 1, 'order_created', 'weekly_orders', 5, NULL, '\"{\\\"total_qty\\\":131,\\\"week\\\":\\\"2025-12-22\\\"}\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 02:33:37');

-- --------------------------------------------------------

--
-- Table structure for table `anganwadi`
--

CREATE TABLE `anganwadi` (
  `id` int(11) NOT NULL,
  `village_id` int(11) NOT NULL,
  `route_id` int(11) DEFAULT NULL,
  `aw_code` varchar(50) NOT NULL,
  `name` varchar(200) NOT NULL,
  `type` enum('anganwadi','school') NOT NULL,
  `total_children` int(11) DEFAULT 0,
  `pregnant_women` int(11) DEFAULT 0,
  `contact_person` varchar(100) DEFAULT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `anganwadi`
--

INSERT INTO `anganwadi` (`id`, `village_id`, `route_id`, `aw_code`, `name`, `type`, `total_children`, `pregnant_women`, `contact_person`, `mobile`, `email`, `address`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'AW001', 'Vansda Anganwadi Center', 'anganwadi', 50, 6, 'Pushpa Damor', '9328366460', NULL, NULL, 'active', '2025-11-29 01:58:40', '2025-11-29 01:58:40'),
(2, 1, 1, 'AW002', 'Vansda Government School', 'school', 130, 0, 'Aman Malik', '8160948069', NULL, NULL, 'active', '2025-11-29 01:58:40', '2025-11-29 01:58:40'),
(3, 2, 1, 'AW003', 'Jalalpore Anganwadi', 'anganwadi', 40, 4, 'Abhi Patel', '7284832327', NULL, NULL, 'active', '2025-11-29 01:58:40', '2025-11-29 01:58:40');

-- --------------------------------------------------------

--
-- Table structure for table `districts`
--

CREATE TABLE `districts` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `districts`
--

INSERT INTO `districts` (`id`, `name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Navsari', 'active', '2025-11-29 01:58:40', '2025-11-29 01:58:40'),
(2, 'Valsad', 'active', '2025-11-29 01:58:40', '2025-11-29 01:58:40'),
(3, 'Dang', 'active', '2025-11-29 01:58:40', '2025-11-29 01:58:40'),
(4, 'Tapi', 'active', '2025-11-29 01:58:40', '2025-11-29 01:58:40');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('order','approval','dispatch','system') DEFAULT 'system',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 1, 'Order Approved', 'Your order for week 2025-12-22 to 2025-12-26 has been approved.', 'approval', 1, '2025-11-29 02:38:28'),
(2, 1, 'Order Approved', 'Your order for week 2025-12-08 to 2025-12-12 has been approved.', 'approval', 1, '2025-11-29 15:26:10'),
(3, 1, 'Order Approved', 'Your order for week 2025-11-24 to 2025-11-28 has been approved.', 'approval', 1, '2025-11-29 15:26:16');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `anganwadi_id` int(11) NOT NULL,
  `delivery_date` date NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','rejected','dispatched','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `old_status`, `new_status`, `changed_by`, `remarks`, `created_at`) VALUES
(1, 5, 'pending', 'approved', 4, '', '2025-11-29 02:38:28'),
(2, 4, 'pending', 'approved', 4, '', '2025-11-29 15:26:10'),
(3, 1, 'pending', 'approved', 4, '', '2025-11-29 15:26:16');

-- --------------------------------------------------------

--
-- Table structure for table `otp_logs`
--

CREATE TABLE `otp_logs` (
  `id` int(11) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `expiry_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `otp_logs`
--

INSERT INTO `otp_logs` (`id`, `mobile`, `otp`, `expiry_time`, `verified`, `created_at`) VALUES
(26, '9999999999', '495233', '2025-11-29 16:57:24', 1, '2025-11-29 16:56:57'),
(27, '9999999999', '758475', '2025-11-30 05:37:31', 0, '2025-11-30 05:36:31'),
(28, '9999999999', '257291', '2025-11-30 05:38:21', 1, '2025-11-30 05:38:08'),
(29, '9999999999', '467149', '2025-11-30 08:14:21', 1, '2025-11-30 08:13:56'),
(30, '9999999999', '708843', '2025-11-30 09:05:09', 1, '2025-11-30 09:04:56'),
(31, '9999999999', '388961', '2025-11-30 11:52:34', 1, '2025-11-30 11:52:20'),
(32, '9328366460', '757352', '2025-11-30 12:11:45', 1, '2025-11-30 12:11:03'),
(33, '9999999999', '782960', '2025-11-30 12:51:13', 1, '2025-11-30 12:50:55'),
(34, '9999999999', '881208', '2025-12-01 02:35:05', 1, '2025-12-01 02:34:45');

-- --------------------------------------------------------

--
-- Table structure for table `routes`
--

CREATE TABLE `routes` (
  `id` int(11) NOT NULL,
  `route_number` varchar(50) NOT NULL,
  `route_name` varchar(150) NOT NULL,
  `vehicle_number` varchar(50) DEFAULT NULL,
  `driver_name` varchar(100) DEFAULT NULL,
  `driver_mobile` varchar(15) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `routes`
--

INSERT INTO `routes` (`id`, `route_number`, `route_name`, `vehicle_number`, `driver_name`, `driver_mobile`, `status`, `created_at`, `updated_at`) VALUES
(1, 'R001', 'Vansda Town Route', 'GJ-05-AB-1234', 'Vikram Patel', '9876543210', 'active', '2025-11-29 01:58:40', '2025-11-29 01:58:40'),
(2, 'R002', 'Vansda Rural Route', 'GJ-05-CD-5678', 'Ajay Singh', '9876543211', 'active', '2025-11-29 01:58:40', '2025-11-29 01:58:40'),
(3, 'R003', 'Jalalpore Route', 'GJ-05-EF-9012', 'Rajesh Desai', '9876543212', 'active', '2025-11-29 01:58:40', '2025-11-29 01:58:40');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'sms_api', 'fast2sms', 'SMS API provider (fast2sms/msg91)', '2025-11-29 01:58:40'),
(2, 'sms_api_key', '', 'API key for SMS service', '2025-11-29 01:58:40'),
(3, 'bag_size', '500', 'Milk bag size in ml', '2025-11-29 01:58:40'),
(4, 'otp_expiry', '5', 'OTP expiry time in minutes', '2025-11-29 01:58:40'),
(5, 'session_timeout', '30', 'Session timeout in minutes', '2025-11-29 01:58:40'),
(6, 'order_approval_sms', '1', 'Send SMS on order approval (0/1)', '2025-11-29 01:58:40'),
(7, 'company_name', 'Vasudhara Milk Distribution', 'Company name for reports', '2025-11-29 01:58:40'),
(8, 'company_address', 'Vansda, Navsari, Gujarat', 'Company address', '2025-11-29 01:58:40'),
(9, 'company_phone', '020-12345678', 'Company contact number', '2025-11-29 01:58:40');

-- --------------------------------------------------------

--
-- Table structure for table `talukas`
--

CREATE TABLE `talukas` (
  `id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `talukas`
--

INSERT INTO `talukas` (`id`, `district_id`, `name`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Vansda', 'active', '2025-11-29 01:58:40', '2025-11-29 01:58:40'),
(2, 1, 'Jalalpore', 'active', '2025-11-29 01:58:40', '2025-11-29 01:58:40'),
(3, 1, 'Chikhli', 'active', '2025-11-29 01:58:40', '2025-11-29 01:58:40'),
(4, 2, 'Valsad', 'active', '2025-11-29 01:58:40', '2025-11-29 01:58:40'),
(5, 2, 'Umbergaon', 'active', '2025-11-29 01:58:40', '2025-11-29 01:58:40'),
(6, 2, 'Pardi', 'active', '2025-11-29 01:58:40', '2025-11-29 01:58:40');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `village_id` int(11) DEFAULT NULL,
  `anganwadi_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('user','admin','supervisor') DEFAULT 'user',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `village_id`, `anganwadi_id`, `name`, `mobile`, `email`, `role`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, NULL, 1, 'Pushpa Damor', '9328366460', 'pushpa@example.com', 'user', 'active', NULL, '2025-11-29 01:58:40', '2025-11-29 01:58:40'),
(2, NULL, 2, 'Aman Malik', '8160948069', 'aman@example.com', 'user', 'active', NULL, '2025-11-29 01:58:40', '2025-11-29 01:58:40'),
(3, NULL, 3, 'Abhi Patel', '7284832327', 'abhi@example.com', 'user', 'active', NULL, '2025-11-29 01:58:40', '2025-11-29 01:58:40'),
(4, NULL, NULL, 'Admin User', '9999999999', 'admin@vasudhara.com', 'admin', 'active', NULL, '2025-11-29 01:58:40', '2025-11-29 01:58:40'),
(5, NULL, NULL, 'Dishant Admin', '9999999991', 'dishant@vasudhara.com', 'admin', 'active', NULL, '2025-11-29 01:58:40', '2025-11-29 01:58:40'),
(6, NULL, NULL, 'JINAY', '9999999996', 'malijinay@test.com', '', 'active', NULL, '2025-11-29 09:58:33', '2025-11-29 09:58:33'),
(8, 2, 3, 'pintu', '9876543210', 'admin@coffee.com', 'user', 'active', NULL, '2025-11-29 14:18:49', '2025-11-29 14:35:03');

-- --------------------------------------------------------

--
-- Table structure for table `villages`
--

CREATE TABLE `villages` (
  `id` int(11) NOT NULL,
  `taluka_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `villages`
--

INSERT INTO `villages` (`id`, `taluka_id`, `name`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Vansda', 'active', '2025-11-29 01:58:40', '2025-11-29 01:58:40'),
(2, 1, 'Bhedkund', 'active', '2025-11-29 01:58:40', '2025-11-29 01:58:40'),
(3, 1, 'Adoli', 'active', '2025-11-29 01:58:40', '2025-11-29 01:58:40'),
(4, 2, 'Jalalpore', 'active', '2025-11-29 01:58:40', '2025-11-29 01:58:40'),
(5, 2, 'Unava', 'active', '2025-11-29 01:58:40', '2025-11-29 01:58:40'),
(6, 2, 'Uchhal', 'active', '2025-11-29 01:58:40', '2025-11-29 01:58:40');

-- --------------------------------------------------------

--
-- Table structure for table `weekly_orders`
--

CREATE TABLE `weekly_orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `anganwadi_id` int(11) NOT NULL,
  `week_start_date` date NOT NULL,
  `week_end_date` date NOT NULL,
  `mon_qty` decimal(10,2) DEFAULT 0.00,
  `tue_qty` decimal(10,2) DEFAULT 0.00,
  `wed_qty` decimal(10,2) DEFAULT 0.00,
  `thu_qty` decimal(10,2) DEFAULT 0.00,
  `fri_qty` decimal(10,2) DEFAULT 0.00,
  `total_qty` decimal(10,2) DEFAULT 0.00,
  `children_allocation` decimal(10,2) DEFAULT 0.00,
  `pregnant_women_allocation` decimal(10,2) DEFAULT 0.00,
  `total_bags` int(11) DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','dispatched','completed') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `dispatched_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `weekly_orders`
--

INSERT INTO `weekly_orders` (`id`, `user_id`, `anganwadi_id`, `week_start_date`, `week_end_date`, `mon_qty`, `tue_qty`, `wed_qty`, `thu_qty`, `fri_qty`, `total_qty`, `children_allocation`, `pregnant_women_allocation`, `total_bags`, `remarks`, `status`, `approved_by`, `approved_at`, `dispatched_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2025-11-24', '2025-11-28', 22.00, 22.00, 22.00, 22.00, 22.00, 110.00, 90.00, 22.00, 225, NULL, 'approved', 4, '2025-11-29 15:26:16', NULL, '2025-11-29 01:58:40', '2025-11-29 15:26:16'),
(2, 2, 2, '2025-11-24', '2025-11-28', 60.00, 60.00, 60.00, 60.00, 60.00, 300.00, 300.00, 0.00, 600, NULL, 'approved', NULL, NULL, NULL, '2025-11-29 01:58:40', '2025-11-29 01:58:40'),
(3, 3, 3, '2025-11-24', '2025-11-28', 20.50, 19.00, 21.00, 20.50, 19.00, 100.00, 76.00, 24.00, 200, NULL, 'dispatched', NULL, NULL, NULL, '2025-11-29 01:58:40', '2025-11-29 01:58:40'),
(4, 1, 1, '2025-12-08', '2025-12-12', 17.00, 25.00, 41.00, 56.00, 74.00, 213.00, 197.00, 16.00, 426, '', 'approved', 4, '2025-11-29 15:26:10', NULL, '2025-11-29 02:20:10', '2025-11-29 15:26:10'),
(5, 1, 1, '2025-12-22', '2025-12-26', 24.00, 25.00, 16.00, 36.00, 30.00, 131.00, 111.00, 20.00, 131, '', 'approved', 4, '2025-11-29 02:38:28', NULL, '2025-11-29 02:33:37', '2025-11-29 02:38:28'),
(6, 1, 1, '2025-12-01', '2025-12-05', 4.00, 2.00, 10.00, 13.00, 6.00, 35.00, 31.00, 4.00, 35, '', 'pending', NULL, NULL, NULL, '2025-11-30 12:50:38', '2025-11-30 12:50:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `anganwadi`
--
ALTER TABLE `anganwadi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `aw_code` (`aw_code`),
  ADD KEY `idx_village` (`village_id`),
  ADD KEY `idx_route` (`route_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`type`);

--
-- Indexes for table `districts`
--
ALTER TABLE `districts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_read` (`is_read`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_anganwadi` (`anganwadi_id`),
  ADD KEY `idx_delivery_date` (`delivery_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `idx_order` (`order_id`);

--
-- Indexes for table `otp_logs`
--
ALTER TABLE `otp_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mobile` (`mobile`),
  ADD KEY `idx_expiry` (`expiry_time`);

--
-- Indexes for table `routes`
--
ALTER TABLE `routes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `route_number` (`route_number`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `talukas`
--
ALTER TABLE `talukas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_taluka` (`district_id`,`name`),
  ADD KEY `idx_district` (`district_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mobile` (`mobile`),
  ADD KEY `idx_mobile` (`mobile`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_anganwadi` (`anganwadi_id`),
  ADD KEY `idx_village` (`village_id`);

--
-- Indexes for table `villages`
--
ALTER TABLE `villages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_village` (`taluka_id`,`name`),
  ADD KEY `idx_taluka` (`taluka_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `weekly_orders`
--
ALTER TABLE `weekly_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_weekly_order` (`anganwadi_id`,`week_start_date`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_anganwadi` (`anganwadi_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_week_start` (`week_start_date`),
  ADD KEY `idx_week_end` (`week_end_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `anganwadi`
--
ALTER TABLE `anganwadi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `districts`
--
ALTER TABLE `districts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `otp_logs`
--
ALTER TABLE `otp_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `routes`
--
ALTER TABLE `routes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `talukas`
--
ALTER TABLE `talukas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `villages`
--
ALTER TABLE `villages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `weekly_orders`
--
ALTER TABLE `weekly_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `anganwadi`
--
ALTER TABLE `anganwadi`
  ADD CONSTRAINT `anganwadi_ibfk_1` FOREIGN KEY (`village_id`) REFERENCES `villages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `anganwadi_ibfk_2` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`anganwadi_id`) REFERENCES `anganwadi` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `weekly_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_status_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `talukas`
--
ALTER TABLE `talukas`
  ADD CONSTRAINT `talukas_ibfk_1` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`anganwadi_id`) REFERENCES `anganwadi` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`village_id`) REFERENCES `villages` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `villages`
--
ALTER TABLE `villages`
  ADD CONSTRAINT `villages_ibfk_1` FOREIGN KEY (`taluka_id`) REFERENCES `talukas` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `weekly_orders`
--
ALTER TABLE `weekly_orders`
  ADD CONSTRAINT `weekly_orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `weekly_orders_ibfk_2` FOREIGN KEY (`anganwadi_id`) REFERENCES `anganwadi` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `weekly_orders_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
