-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 29, 2024 at 01:28 PM
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
-- Database: `expense_tracker`
--

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `budget_id` int(11) NOT NULL,
  `id` int(11) DEFAULT NULL,
  `total_budget` decimal(10,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budgets`
--

INSERT INTO `budgets` (`budget_id`, `id`, `total_budget`, `start_date`, `end_date`, `created_at`) VALUES
(70, 22, 777.00, '2024-10-01', '2024-10-31', '2024-10-29 11:53:15'),
(71, 23, 888.00, '2024-12-01', '2024-12-31', '2024-10-29 11:58:59'),
(72, 23, 99.00, '2024-10-01', '2024-10-31', '2024-10-29 12:01:52'),
(73, 23, 1000.00, '2024-01-01', '2024-01-31', '2024-10-29 12:12:40');

-- --------------------------------------------------------

--
-- Table structure for table `budget_allocations`
--

CREATE TABLE `budget_allocations` (
  `allocation_id` int(11) NOT NULL,
  `budget_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `allocated_percentage` decimal(5,2) NOT NULL CHECK (`allocated_percentage` <= 100),
  `allocated_amount` decimal(10,2) NOT NULL,
  `month_year` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_allocations`
--

INSERT INTO `budget_allocations` (`allocation_id`, `budget_id`, `category_id`, `allocated_percentage`, `allocated_amount`, `month_year`, `created_at`) VALUES
(156, 70, 143, 20.00, 155.40, '2024-10-01', '2024-10-29 11:53:15'),
(157, 70, 144, 30.00, 233.10, '2024-10-01', '2024-10-29 11:53:15'),
(158, 71, 145, 20.00, 177.60, '2024-12-01', '2024-10-29 11:58:59'),
(159, 71, 146, 20.00, 177.60, '2024-12-01', '2024-10-29 11:58:59'),
(160, 71, 147, 20.00, 177.60, '2024-12-01', '2024-10-29 11:58:59'),
(161, 71, 148, 20.00, 177.60, '2024-12-01', '2024-10-29 11:58:59'),
(162, 72, 149, 20.00, 19.80, '2024-10-01', '2024-10-29 12:01:52'),
(163, 72, 150, 20.00, 19.80, '2024-10-01', '2024-10-29 12:01:52'),
(164, 72, 151, 20.00, 19.80, '2024-10-01', '2024-10-29 12:01:52'),
(165, 72, 152, 20.00, 19.80, '2024-10-01', '2024-10-29 12:01:52'),
(166, 72, 153, 20.00, 19.80, '2024-10-01', '2024-10-29 12:01:52'),
(167, 73, 154, 20.00, 200.00, '2024-01-01', '2024-10-29 12:12:40');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `id` int(11) DEFAULT NULL,
  `category_name` varchar(100) NOT NULL,
  `month_year` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `id`, `category_name`, `month_year`, `created_at`) VALUES
(143, 22, 'entertainment', '2024-10-01', '2024-10-29 11:37:23'),
(144, 22, 'rent', '2024-10-01', '2024-10-29 11:53:15'),
(145, 23, 'rent', '2024-12-01', '2024-10-29 11:57:46'),
(146, 23, 'savings', '2024-12-01', '2024-10-29 11:57:46'),
(147, 23, 'entertainment', '2024-12-01', '2024-10-29 11:58:54'),
(148, 23, 'groceries', '2024-12-01', '2024-10-29 11:58:54'),
(149, 23, 'entertainment', '2024-10-01', '2024-10-29 12:01:52'),
(150, 23, 'rent', '2024-10-01', '2024-10-29 12:01:52'),
(151, 23, 'groceries', '2024-10-01', '2024-10-29 12:01:52'),
(152, 23, 'savings', '2024-10-01', '2024-10-29 12:01:52'),
(153, 23, 'others', '2024-10-01', '2024-10-29 12:01:52'),
(154, 23, 'entertainment', '2024-01-01', '2024-10-29 12:12:40');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `expense_id` int(11) NOT NULL,
  `id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `budget_id` int(11) DEFAULT NULL,
  `expense_amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`expense_id`, `id`, `category_id`, `budget_id`, `expense_amount`, `expense_date`, `created_at`) VALUES
(81, 22, 143, 70, 20.00, '2024-10-29', '2024-10-29 11:37:50'),
(82, 22, 143, 70, 50.00, '2024-10-10', '2024-10-29 11:38:33'),
(83, 22, 143, 70, 29.00, '2024-10-11', '2024-10-29 11:38:47'),
(84, 22, 144, 70, 40.00, '2024-10-24', '2024-10-29 11:53:41'),
(85, 22, 143, 70, 90.00, '2024-10-24', '2024-10-29 11:53:54'),
(86, 23, 147, 71, 20.00, '2024-12-19', '2024-10-29 12:00:59'),
(87, 23, 145, 71, 45.00, '2024-12-19', '2024-10-29 12:01:08'),
(88, 23, 149, 72, 20.00, '2024-10-17', '2024-10-29 12:17:30');

-- --------------------------------------------------------

--
-- Table structure for table `monthly_reports`
--

CREATE TABLE `monthly_reports` (
  `report_id` int(11) NOT NULL,
  `id` int(11) DEFAULT NULL,
  `budget_id` int(11) DEFAULT NULL,
  `report_month` date NOT NULL,
  `total_spent` decimal(10,2) NOT NULL,
  `total_budget` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `auth_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `auth_token`, `created_at`) VALUES
(22, 'you@example.com', '$2y$10$32iQaLx2Qz/hy.IilhMnCuox8AReBKuzIKsC1Len/J9lnZLBSFPkS', NULL, '2024-10-29 11:36:48'),
(23, 'khan@example.com', '$2y$10$zIpSeT3wiKgFVde6U.9dQeTTxrCqUi22gc4uLUfiL6dO8MRZ0nKku', NULL, '2024-10-29 11:56:50'),
(24, 'sajjad@example.com', '$2y$10$/t9Gxvkk1xzYPNL.c/rQK.cQuVBHTl2hcRRRhjWcMqVd/4Ko5YiEe', '4ab3e0d2022ba94b8d75d0d6a3205f434d0f1a964960802ffa46bbba6512fd74', '2024-10-29 12:24:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`budget_id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `budget_allocations`
--
ALTER TABLE `budget_allocations`
  ADD PRIMARY KEY (`allocation_id`),
  ADD KEY `budget_id` (`budget_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`expense_id`),
  ADD KEY `id` (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `budget_id` (`budget_id`);

--
-- Indexes for table `monthly_reports`
--
ALTER TABLE `monthly_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `id` (`id`),
  ADD KEY `budget_id` (`budget_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `budget_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `budget_allocations`
--
ALTER TABLE `budget_allocations`
  MODIFY `allocation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=168;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=155;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `expense_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT for table `monthly_reports`
--
ALTER TABLE `monthly_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `budgets_ibfk_1` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `budget_allocations`
--
ALTER TABLE `budget_allocations`
  ADD CONSTRAINT `budget_allocations_ibfk_1` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`budget_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `budget_allocations_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expenses_ibfk_3` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`budget_id`) ON DELETE CASCADE;

--
-- Constraints for table `monthly_reports`
--
ALTER TABLE `monthly_reports`
  ADD CONSTRAINT `monthly_reports_ibfk_1` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `monthly_reports_ibfk_2` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`budget_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
