-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 05, 2025 at 10:15 AM
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
-- Database: `dcms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `dentist_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled','missed') NOT NULL DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `dentist_id`, `appointment_date`, `appointment_time`, `status`, `reason`, `notes`, `created_at`, `updated_at`) VALUES
(1, 3, 1, '2025-06-05', '13:15:00', 'completed', NULL, NULL, '2025-06-05 07:13:35', '2025-06-05 07:51:41'),
(2, 3, 1, '2025-06-05', '13:30:00', 'confirmed', NULL, NULL, '2025-06-05 07:15:03', '2025-06-05 07:36:19'),
(3, 5, 1, '2025-06-08', '10:00:00', 'confirmed', NULL, NULL, '2025-06-05 07:42:04', '2025-06-05 07:42:58');

--
-- Triggers `appointments`
--
DELIMITER $$
CREATE TRIGGER `before_update_appointments` BEFORE UPDATE ON `appointments` FOR EACH ROW BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `dentists`
--

CREATE TABLE `dentists` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `dob` date NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `specialization` varchar(100) NOT NULL,
  `work_experience` int(11) NOT NULL,
  `degree` varchar(100) NOT NULL,
  `consultation_charge` decimal(10,2) NOT NULL,
  `working_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '["monday", "tuesday", "wednesday", "thursday", "friday"]' COMMENT 'Array of working days' CHECK (json_valid(`working_days`)),
  `working_hours_start` time NOT NULL DEFAULT '09:00:00',
  `working_hours_end` time NOT NULL DEFAULT '17:00:00',
  `consultation_duration` int(11) NOT NULL DEFAULT 30 COMMENT 'Duration in minutes for each consultation',
  `break_time_start` time DEFAULT '13:00:00',
  `break_time_end` time DEFAULT '14:00:00',
  `status` enum('pending','active','inactive') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dentists`
--

INSERT INTO `dentists` (`id`, `user_id`, `first_name`, `last_name`, `gender`, `dob`, `phone`, `email`, `specialization`, `work_experience`, `degree`, `consultation_charge`, `working_days`, `working_hours_start`, `working_hours_end`, `consultation_duration`, `break_time_start`, `break_time_end`, `status`, `created_at`, `updated_at`) VALUES
(1, 4, 'd1', 'Rogers', 'male', '2019-07-10', '+45245342493', 'becirezi@mailinator.com', 'General Dentist', 10, 'MD', 1500.00, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"sunday\"]', '08:00:00', '18:00:00', 15, '22:00:00', '14:00:00', 'active', '2025-06-05 04:51:50', '2025-06-05 07:10:27'),
(2, 5, 'd2', 'Lancaster', 'male', '2019-04-10', '4353453453455', 'gupupenoba@mailinator.com', 'Prosthodontist', 4, 'MD', 1000.00, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\"]', '07:00:00', '07:00:00', 15, '14:00:00', '15:00:00', 'active', '2025-06-05 04:53:29', '2025-06-05 04:54:52'),
(3, 7, 'd3', 'Glass', 'male', '1987-05-23', '+156516516516', 'nasi@mailinator.com', 'Cosmetic Dentist', 3, 'MD', 800.00, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"sunday\"]', '10:00:00', '19:00:00', 15, '13:00:00', '14:00:00', 'active', '2025-06-05 07:19:41', '2025-06-05 07:20:43');

--
-- Triggers `dentists`
--
DELIMITER $$
CREATE TRIGGER `before_update_dentists` BEFORE UPDATE ON `dentists` FOR EACH ROW BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `dob` date NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `address` text DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `current_medications` text DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `user_id`, `first_name`, `last_name`, `gender`, `dob`, `phone`, `email`, `address`, `medical_history`, `allergies`, `current_medications`, `emergency_contact`, `created_at`, `updated_at`) VALUES
(1, 2, 'patient1', 'Abbott', 'male', '1986-02-19', '+9771234567890', 'pidonukoha@mailinator.com', NULL, NULL, NULL, NULL, NULL, '2025-06-05 04:48:23', '2025-06-05 04:48:23'),
(2, 3, 'patient2', 'Paul', 'female', '1994-07-20', '+165656564718', 'womuq@mailinator.com', NULL, NULL, NULL, NULL, NULL, '2025-06-05 04:49:08', '2025-06-05 04:49:08'),
(3, 6, 'patient3', 'Cabrera', 'female', '1986-07-06', '+12116165156846', 'fajubybyle@mailinator.com', NULL, NULL, NULL, NULL, NULL, '2025-06-05 07:13:08', '2025-06-05 07:13:08'),
(5, 9, 'patient4', 'Dunn', 'male', '1992-11-03', '+9775265265265', 'daheby@mailinator.com', NULL, NULL, NULL, NULL, NULL, '2025-06-05 07:41:48', '2025-06-05 07:41:48');

--
-- Triggers `patients`
--
DELIMITER $$
CREATE TRIGGER `before_update_patients` BEFORE UPDATE ON `patients` FOR EACH ROW BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `treatment_reports`
--

CREATE TABLE `treatment_reports` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `diagnosis` text NOT NULL,
  `treatment` text NOT NULL,
  `prescription` text DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `follow_up_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `treatment_reports`
--

INSERT INTO `treatment_reports` (`id`, `appointment_id`, `diagnosis`, `treatment`, `prescription`, `follow_up_date`, `follow_up_notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'Quia corrupti eius ', 'Ratione elit dolor ', 'Dolor neque quo omni', NULL, NULL, '2025-06-05 07:51:41', '2025-06-05 07:51:41');

--
-- Triggers `treatment_reports`
--
DELIMITER $$
CREATE TRIGGER `before_update_treatment_reports` BEFORE UPDATE ON `treatment_reports` FOR EACH ROW BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','dentist','patient') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$HRn4g3vKA0lrkirbgbRQAOYQtCqbrBz0W8TdyUJaYM6vNZi1R0k2.', 'admin', 1, '2025-06-05 04:47:19', '2025-06-05 04:47:19'),
(2, 'patient1', '$2y$10$A4raAwFeNsLwP15TVV67QOzT5cvuOufy76qiXznXPA8bnaLkyrzrm', 'patient', 1, '2025-06-05 04:48:23', '2025-06-05 04:48:23'),
(3, 'patient2', '$2y$10$fgBfevM3u81MAByBfCOwc.J3KrlK/UKOvsP1kyPrk7JwvyRWt859e', 'patient', 1, '2025-06-05 04:49:08', '2025-06-05 04:49:08'),
(4, 'd1', '$2y$10$uutOm/7y4FmxwNm1DGsfH.kc/baZqFC4Ic4lFz3tIZpd5e4KUPOgi', 'dentist', 1, '2025-06-05 04:51:50', '2025-06-05 04:51:50'),
(5, 'd2', '$2y$10$hQU3WDHZ9yFzuggPtu8cEuCYIvKfxKgLpv0SBp7lnijTI9pnLN1JC', 'dentist', 1, '2025-06-05 04:53:29', '2025-06-05 04:53:29'),
(6, 'patient3', '$2y$10$Ke.JHHyJL7fL0TsIleZCXOPndcWSB52rGMoEyRShB7sK3sMbnhr2a', 'patient', 1, '2025-06-05 07:13:08', '2025-06-05 07:13:08'),
(7, 'd3', '$2y$10$sq4yRfpYOYYfq5l8Z//ngeSMkzL0A7r8gleNqPry2GP9pbfIufU5m', 'dentist', 1, '2025-06-05 07:19:41', '2025-06-05 07:19:41'),
(9, 'patient4', '$2y$10$SM0tZDsct505tYI/BiREwun1rx8lCTBpuZ.WZlvyGII3tubSfd9pG', 'patient', 1, '2025-06-05 07:41:48', '2025-06-05 07:41:48');

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `before_update` BEFORE UPDATE ON `users` FOR EACH ROW BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_appointment_date` (`appointment_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_patient_appointments` (`patient_id`,`appointment_date`),
  ADD KEY `idx_dentist_appointments` (`dentist_id`,`appointment_date`);

--
-- Indexes for table `dentists`
--
ALTER TABLE `dentists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_name` (`first_name`,`last_name`),
  ADD KEY `idx_specialization` (`specialization`),
  ADD KEY `idx_phone` (`phone`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_name` (`first_name`,`last_name`),
  ADD KEY `idx_phone` (`phone`);

--
-- Indexes for table `treatment_reports`
--
ALTER TABLE `treatment_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `appointment_id` (`appointment_id`),
  ADD KEY `idx_follow_up_date` (`follow_up_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `dentists`
--
ALTER TABLE `dentists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `treatment_reports`
--
ALTER TABLE `treatment_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`dentist_id`) REFERENCES `dentists` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dentists`
--
ALTER TABLE `dentists`
  ADD CONSTRAINT `dentists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `treatment_reports`
--
ALTER TABLE `treatment_reports`
  ADD CONSTRAINT `treatment_reports_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
