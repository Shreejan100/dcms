-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 06, 2025 at 06:19 AM
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
(4, 10, 20, '2025-06-22', '18:21:00', 'pending', NULL, NULL, '2025-06-06 04:16:23', '2025-06-06 04:16:23');

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
(14, 21, 'Ram(D1)', 'kc', 'male', '1973-05-28', '+1 (676) 858-2044', 'sawymijicy@mailinator.com', 'General Dentist', 5, 'Aspernatur eum sit t', 1000.00, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"sunday\"]', '10:00:00', '18:00:00', 15, '13:00:00', '14:00:00', 'active', '2025-06-05 17:31:24', '2025-06-05 17:31:24'),
(15, 22, 'Sita(D2)', 'grg', 'female', '1973-08-05', '+1 (475) 385-5812', 'ketuv@mailinator.com', 'Orthodontist', 3, 'Voluptas eaque in ma', 1000.00, '[\"monday\",\"wednesday\",\"friday\"]', '10:00:00', '06:00:00', 15, '13:00:00', '13:30:00', 'active', '2025-06-05 17:33:54', '2025-06-05 17:33:54'),
(16, 23, 'hari(D3)', 'poudel', 'male', '2013-04-23', '+1 (839) 655-7045', 'lihud@mailinator.com', 'Endodontist', 4, 'Error et animi labo', 1000.00, '[\"tuesday\",\"thursday\",\"friday\"]', '01:14:00', '19:51:00', 15, '18:46:00', '03:48:00', 'active', '2025-06-05 17:36:03', '2025-06-05 17:36:03'),
(17, 24, 'Taylor', 'Luna', 'female', '1985-02-22', '+1 (831) 648-3805', 'gimowyrela@mailinator.com', 'Periodontist', 19, 'Molestias tempora qu', 1000.00, '[\"monday\",\"tuesday\",\"wednesday\",\"friday\",\"sunday\"]', '10:55:00', '19:51:00', 15, '13:00:00', '14:00:00', 'active', '2025-06-05 17:37:43', '2025-06-05 17:37:43'),
(18, 25, 'Ambar', 'Clarke', 'male', '2001-05-17', '+1 (172) 106-1285', 'qiharyze@mailinator.com', 'Prosthodontist', 8, 'Mollitia rerum dolor', 1500.00, '[\"monday\",\"wednesday\",\"friday\"]', '10:45:00', '18:32:00', 30, '13:00:00', '14:00:00', 'active', '2025-06-05 17:39:30', '2025-06-05 17:39:30'),
(19, 26, 'Hira', 'Neal', 'other', '2007-05-20', '+1 (877) 785-5455', 'sola@mailinator.com', 'Cosmetic Dentist', 10, 'Ipsa recusandae Al', 1500.00, '[\"monday\",\"tuesday\",\"thursday\",\"friday\",\"sunday\"]', '10:00:00', '17:00:00', 15, '23:20:00', '12:20:00', 'active', '2025-06-05 17:46:30', '2025-06-05 17:47:29'),
(20, 27, 'Robert', 'Sweet', 'other', '2015-04-07', '+9779846852565', 'lavumod@mailinator.com', 'General Dentist', 75, 'Nisi voluptatem Eiu', 88.00, '[\"monday\",\"tuesday\",\"friday\",\"saturday\",\"sunday\"]', '11:51:00', '23:02:00', 30, '18:33:00', '05:25:00', 'pending', '2025-06-05 17:48:30', '2025-06-05 17:48:30'),
(21, 28, 'Isaac', 'Jennings', 'other', '2013-09-20', '+9779848253675', 'mobixuru@mailinator.com', 'Prosthodontist', 79, 'Esse error unde qui', 23.00, '[\"monday\",\"tuesday\",\"wednesday\",\"friday\",\"saturday\"]', '06:04:00', '02:40:00', 15, '13:34:00', '04:40:00', 'pending', '2025-06-05 17:48:51', '2025-06-05 17:48:51'),
(22, 29, 'Brendan', 'Carroll', 'other', '1985-11-11', '4544731796', 'nygib@mailinator.com', 'Prosthodontist', 79, 'Ullamco deserunt vit', 41.00, '[\"tuesday\",\"thursday\"]', '07:41:00', '16:13:00', 30, '02:03:00', '14:27:00', 'pending', '2025-06-05 17:49:17', '2025-06-05 17:49:17'),
(23, 33, 'Conan', 'Franks', 'female', '1976-07-12', '+1 (648) 154-1913', 'qyveto@mailinator.com', 'Prosthodontist', 27, 'Fugiat est soluta c', 17.00, '[\"tuesday\"]', '22:44:00', '09:51:00', 30, '19:45:00', '13:01:00', 'active', '2025-06-06 04:09:36', '2025-06-06 04:09:36');

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
(6, 10, 'p1', 'p1', 'male', '2022-01-09', '9848253675', 'skc95642@gmail.com', NULL, NULL, NULL, NULL, NULL, '2025-06-05 09:36:18', '2025-06-05 09:36:18'),
(7, 30, 'Aurora', 'Delaney', 'other', '2023-11-29', '+9779846852565', 'xatygomoli@mailinator.com', NULL, NULL, NULL, NULL, NULL, '2025-06-05 17:50:14', '2025-06-05 17:50:14'),
(8, 31, 'Jameson', 'Ryan', 'other', '1982-01-09', '+1 (351) 904-3046', 'sydyqot@mailinator.com', 'Dolore est aliquam', 'Aut harum non conseq', 'Esse doloribus ut a', 'Necessitatibus cupid', NULL, '2025-06-05 17:51:02', '2025-06-05 17:51:02'),
(9, 32, 'Mara', 'Terrell', 'other', '2003-01-23', '+1 (751) 747-1849', 'joxypaz@mailinator.com', 'Architecto enim ad m', 'Omnis dolores eum ve', 'Esse sit aliquip a', 'Porro itaque id adip', NULL, '2025-06-05 17:51:08', '2025-06-05 17:51:08'),
(10, 34, 'Maxwell', 'Barrett', 'other', '1992-03-24', '98458284156', 'dyjovydo@mailinator.com', NULL, NULL, NULL, NULL, NULL, '2025-06-06 04:14:41', '2025-06-06 04:14:41');

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
(10, 'patient1', '$2y$10$6ZsbgnJIyiZgFNY4eMjQoOfnlq4irRZv0EUfH4nVNblxJhk//IHdm', 'patient', 1, '2025-06-05 09:36:18', '2025-06-05 09:36:18'),
(21, 'd1', '$2y$10$w0utKidXAa/lSSGnPndG.e9sQ9ZfagnIddPCeFFkf.O3N0AfQHxIW', 'dentist', 1, '2025-06-05 17:31:24', '2025-06-05 17:31:24'),
(22, 'd2', '$2y$10$VInCR2kZKNV14.MWtmUbWuKVFdDt/vd99v99sQNMMEQ6sdFv4u8MG', 'dentist', 1, '2025-06-05 17:33:54', '2025-06-05 17:33:54'),
(23, 'd3', '$2y$10$4NJiMVNicMXDR47g0HxajOTBhMqdl4gnjxOPHF24RyMDRsTFArtmC', 'dentist', 1, '2025-06-05 17:36:03', '2025-06-05 17:36:03'),
(24, 'd4', '$2y$10$g3Q2/hjkgRXusfSqVgA.ZeFEWmvGJSq/CZFTLa2gtMaJ6wkcqrltq', 'dentist', 1, '2025-06-05 17:37:43', '2025-06-05 17:37:43'),
(25, 'd5', '$2y$10$eV0Uo73ksEc0yM7aFNF4Tee3eLG2WLYg5PKm33ceq1ZbuDstpLQSq', 'dentist', 1, '2025-06-05 17:39:30', '2025-06-05 17:39:30'),
(26, 'd6', '$2y$10$ry.iPWUhkMVkav8BpzQy5eaAoxHZp0PvD6bZCmBZBbix/6.JTi9.K', 'dentist', 1, '2025-06-05 17:46:30', '2025-06-05 17:46:30'),
(27, 'kawomiz', '$2y$10$jq2a5nVB7Rd3hF0lJITzeeKfY7zfF5qHj5IH3v4H5IbbtC4y5wxAW', 'dentist', 1, '2025-06-05 17:48:30', '2025-06-05 17:48:30'),
(28, 'tulygamo', '$2y$10$hI3mlwiKT6Yqapo60w.nsuc68IOAoiq3nN2h0Ubp/xyNL7ny8aQDC', 'dentist', 1, '2025-06-05 17:48:51', '2025-06-05 17:48:51'),
(29, 'sugac', '$2y$10$SNpq6.l7Ukzz22EyIG86UeVGx5z6NBxwdllzG8DwIVb6YezyRrC.O', 'dentist', 1, '2025-06-05 17:49:17', '2025-06-05 17:49:17'),
(30, 'mudebecuza', '$2y$10$5eUX7vHe4tP/uNRyLsvTEecUuM76M6hS9xnmsZnRg4pVciLDWLWV.', 'patient', 1, '2025-06-05 17:50:14', '2025-06-05 17:50:14'),
(31, 'mukynytizo', '$2y$10$6zYMQoz/jGUn2TZXP4GNreVr18WL9bq39VivdnHyUQcjS87DR38dm', 'patient', 1, '2025-06-05 17:51:02', '2025-06-05 17:51:02'),
(32, 'pibyp', '$2y$10$csUXpjtEizigTiPpYFSlOeSXJf8jaPlTohyKaD4itReMc.d6gNn22', 'patient', 1, '2025-06-05 17:51:08', '2025-06-05 17:51:08'),
(33, 'hymirib', '$2y$10$i6E7o8uC5rJACWuS01b2Wu.mNBp5d6N44zWCL0q9N3FYs0/UulBq6', 'dentist', 1, '2025-06-06 04:09:36', '2025-06-06 04:09:36'),
(34, 'patient3', '$2y$10$aznFteEYNuXM1k5KeB6a8.Nzag0n7NeQ7bGHKp/li/MQ9acVpaGX.', 'patient', 1, '2025-06-06 04:14:41', '2025-06-06 04:14:41');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `dentists`
--
ALTER TABLE `dentists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `treatment_reports`
--
ALTER TABLE `treatment_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

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
