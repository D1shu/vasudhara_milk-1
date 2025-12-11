-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 07, 2025 at 01:29 PM
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
(1, 'Navsari', 'active', '2025-12-07 07:29:00', '2025-12-07 07:29:00'),
(2, 'Valsad', 'active', '2025-12-07 07:29:00', '2025-12-07 07:29:00'),
(3, 'Dang', 'active', '2025-12-07 07:29:00', '2025-12-07 07:29:00'),
(4, 'Tapi', 'active', '2025-12-07 07:29:00', '2025-12-07 07:29:00');

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
(1, '9999999999', '539557', '2025-12-03 09:04:55', 1, '2025-12-03 09:04:40'),
(2, '9999999999', '862770', '2025-12-03 16:43:03', 1, '2025-12-03 16:42:51'),
(3, '9999999999', '559166', '2025-12-03 16:54:24', 1, '2025-12-03 16:54:15'),
(4, '9999999999', '658545', '2025-12-03 16:59:14', 1, '2025-12-03 16:59:05'),
(5, '9999999999', '904676', '2025-12-06 13:19:45', 1, '2025-12-06 13:19:32'),
(6, '9999999999', '560494', '2025-12-06 14:03:55', 1, '2025-12-06 14:03:43'),
(7, '9999999999', '103661', '2025-12-06 16:27:21', 1, '2025-12-06 16:27:06'),
(8, '9999999999', '826173', '2025-12-07 11:40:13', 1, '2025-12-07 11:39:58'),
(9, '9999999999', '208909', '2025-12-07 11:49:13', 1, '2025-12-07 11:49:00'),
(10, '9999999999', '654029', '2025-12-07 12:08:17', 1, '2025-12-07 12:08:06'),
(11, '9999999999', '691336', '2025-12-07 12:23:01', 1, '2025-12-07 12:22:52');

-- --------------------------------------------------------

--
-- Table structure for table `rates`
--

CREATE TABLE `rates` (
  `id` int(11) NOT NULL,
  `rate_per_packet` decimal(10,2) NOT NULL,
  `effective_from_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rates`
--

INSERT INTO `rates` (`id`, `rate_per_packet`, `effective_from_date`, `description`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 25.00, '2025-11-01', NULL, 'active', NULL, '2025-12-06 16:35:20', '2025-12-06 16:35:20'),
(2, 28.00, '2025-12-01', NULL, 'active', NULL, '2025-12-06 16:35:20', '2025-12-06 16:35:20'),
(3, 30.00, '2025-12-15', NULL, 'active', NULL, '2025-12-06 16:35:20', '2025-12-06 16:35:20');

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
(1, NULL, NULL, 'Admin', '9999999999', 'admin@gmail.com', 'admin', 'active', NULL, '2025-12-03 09:01:03', '2025-12-03 09:01:03'),
(3, NULL, NULL, 'Admin', '9974171087', 'admin@gmail.com', 'admin', 'active', NULL, '2025-12-03 09:02:17', '2025-12-03 09:02:17');

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
-- Indexes for table `rates`
--
ALTER TABLE `rates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rates_created_by` (`created_by`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `anganwadi`
--
ALTER TABLE `anganwadi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `districts`
--
ALTER TABLE `districts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `otp_logs`
--
ALTER TABLE `otp_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `rates`
--
ALTER TABLE `rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `routes`
--
ALTER TABLE `routes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `talukas`
--
ALTER TABLE `talukas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `villages`
--
ALTER TABLE `villages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `weekly_orders`
--
ALTER TABLE `weekly_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Constraints for table `rates`
--
ALTER TABLE `rates`
  ADD CONSTRAINT `fk_rates_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
