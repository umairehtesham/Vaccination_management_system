-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 17, 2026 at 09:24 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `vaccination_db`
--
CREATE DATABASE IF NOT EXISTS `vaccination_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `vaccination_db`;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `citizen_id` int(11) NOT NULL,
  `vaccine_id` int(11) NOT NULL,
  `center_id` int(11) DEFAULT NULL,
  `scheduled_date` datetime NOT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `appointments`
--
DELIMITER $$
CREATE TRIGGER `prevent_duplicate_appointments` BEFORE INSERT ON `appointments` FOR EACH ROW BEGIN
    DECLARE appointment_count INT;

    -- Check if the citizen already has an appointment that is in 'scheduled' state
    -- AND the date is today or in the future
    SELECT COUNT(*) INTO appointment_count
    FROM appointments
    WHERE citizen_id = NEW.citizen_id 
    AND scheduled_date >= CURDATE(); 

    IF appointment_count > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Validation Error: This citizen already has an active scheduled appointment.';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `citizens`
--

CREATE TABLE `citizens` (
  `citizen_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `address` text DEFAULT NULL,
  `contact` varchar(15) NOT NULL,
  `emergency_contact` varchar(15) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff','citizen') NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vaccination_centers`
--

CREATE TABLE `vaccination_centers` (
  `center_id` int(11) NOT NULL,
  `center_name` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `capacity` int(11) DEFAULT 50,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vaccination_records`
--

CREATE TABLE `vaccination_records` (
  `record_id` int(11) NOT NULL,
  `citizen_id` int(11) NOT NULL,
  `vaccine_id` int(11) NOT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `center_id` int(11) DEFAULT NULL,
  `dose_number` int(11) NOT NULL,
  `date_administered` date NOT NULL,
  `next_dose_date` date DEFAULT NULL,
  `administered_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `vaccination_records`
--
DELIMITER $$
CREATE TRIGGER `after_vaccination_insert` AFTER INSERT ON `vaccination_records` FOR EACH ROW BEGIN
    -- We must match BOTH vaccine_id and batch_number 
    -- to find the unique row in vaccine_stock
    UPDATE vaccine_stock 
    SET quantity = quantity - 1 
    WHERE vaccine_id = NEW.vaccine_id 
    AND batch_number = NEW.batch_number;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_vaccination_insert` BEFORE INSERT ON `vaccination_records` FOR EACH ROW BEGIN
    DECLARE expiry DATE;
    
    -- Finding expiry for the specific batch and vaccine selected
    SELECT expiry_date INTO expiry 
    FROM vaccine_stock 
    WHERE batch_number = NEW.batch_number 
    AND vaccine_id = NEW.vaccine_id 
    LIMIT 1;

    IF expiry IS NOT NULL AND expiry < CURDATE() THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Error: This specific vaccine batch has expired!';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `vaccines`
--

CREATE TABLE `vaccines` (
  `vaccine_id` int(11) NOT NULL,
  `vaccine_name` varchar(100) NOT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `vaccine_type` varchar(50) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `doses_required` int(11) DEFAULT 1,
  `interval_days` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `next_dose_interval` int(11) DEFAULT 21
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vaccine_categories`
--

CREATE TABLE `vaccine_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `target_age_group` varchar(50) DEFAULT NULL,
  `priority_level` enum('High','Medium','Low') DEFAULT 'Medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vaccine_stock`
--

CREATE TABLE `vaccine_stock` (
  `stock_id` int(11) NOT NULL,
  `vaccine_id` int(11) NOT NULL,
  `center_id` int(11) DEFAULT NULL,
  `batch_number` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `expiry_date` date NOT NULL,
  `received_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_low_stock_vaccines`
-- (See below for the actual view)
--
CREATE TABLE `v_low_stock_vaccines` (
`vaccine_id` int(11)
,`vaccine_name` varchar(100)
,`manufacturer` varchar(100)
,`category_name` varchar(50)
,`total_stock` decimal(32,0)
,`nearest_expiry` date
,`stock_status` varchar(12)
);

-- --------------------------------------------------------

--
-- Structure for view `v_low_stock_vaccines`
--
DROP TABLE IF EXISTS `v_low_stock_vaccines`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_low_stock_vaccines`  AS SELECT `v`.`vaccine_id` AS `vaccine_id`, `v`.`vaccine_name` AS `vaccine_name`, `v`.`manufacturer` AS `manufacturer`, `vc`.`category_name` AS `category_name`, coalesce(sum(`vs`.`quantity`),0) AS `total_stock`, min(`vs`.`expiry_date`) AS `nearest_expiry`, CASE WHEN coalesce(sum(`vs`.`quantity`),0) = 0 THEN 'Out of Stock' WHEN coalesce(sum(`vs`.`quantity`),0) < 50 THEN 'Critical' WHEN coalesce(sum(`vs`.`quantity`),0) < 100 THEN 'Low' ELSE 'Adequate' END AS `stock_status` FROM ((`vaccines` `v` left join `vaccine_categories` `vc` on(`v`.`category_id` = `vc`.`category_id`)) left join `vaccine_stock` `vs` on(`v`.`vaccine_id` = `vs`.`vaccine_id` and `vs`.`expiry_date` >= curdate())) GROUP BY `v`.`vaccine_id`, `v`.`vaccine_name`, `v`.`manufacturer`, `vc`.`category_name` HAVING `total_stock` < 100 ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `citizen_id` (`citizen_id`),
  ADD KEY `vaccine_id` (`vaccine_id`),
  ADD KEY `center_id` (`center_id`);

--
-- Indexes for table `citizens`
--
ALTER TABLE `citizens`
  ADD PRIMARY KEY (`citizen_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vaccination_centers`
--
ALTER TABLE `vaccination_centers`
  ADD PRIMARY KEY (`center_id`);

--
-- Indexes for table `vaccination_records`
--
ALTER TABLE `vaccination_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `citizen_id` (`citizen_id`),
  ADD KEY `vaccine_id` (`vaccine_id`),
  ADD KEY `center_id` (`center_id`),
  ADD KEY `administered_by` (`administered_by`);

--
-- Indexes for table `vaccines`
--
ALTER TABLE `vaccines`
  ADD PRIMARY KEY (`vaccine_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `vaccine_categories`
--
ALTER TABLE `vaccine_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `vaccine_stock`
--
ALTER TABLE `vaccine_stock`
  ADD PRIMARY KEY (`stock_id`),
  ADD KEY `vaccine_id` (`vaccine_id`),
  ADD KEY `center_id` (`center_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `citizens`
--
ALTER TABLE `citizens`
  MODIFY `citizen_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vaccination_centers`
--
ALTER TABLE `vaccination_centers`
  MODIFY `center_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vaccination_records`
--
ALTER TABLE `vaccination_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vaccines`
--
ALTER TABLE `vaccines`
  MODIFY `vaccine_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vaccine_categories`
--
ALTER TABLE `vaccine_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vaccine_stock`
--
ALTER TABLE `vaccine_stock`
  MODIFY `stock_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`citizen_id`) REFERENCES `citizens` (`citizen_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines` (`vaccine_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`center_id`) REFERENCES `vaccination_centers` (`center_id`) ON DELETE SET NULL;

--
-- Constraints for table `citizens`
--
ALTER TABLE `citizens`
  ADD CONSTRAINT `citizens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `vaccination_records`
--
ALTER TABLE `vaccination_records`
  ADD CONSTRAINT `vaccination_records_ibfk_1` FOREIGN KEY (`citizen_id`) REFERENCES `citizens` (`citizen_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vaccination_records_ibfk_2` FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines` (`vaccine_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vaccination_records_ibfk_3` FOREIGN KEY (`center_id`) REFERENCES `vaccination_centers` (`center_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `vaccination_records_ibfk_4` FOREIGN KEY (`administered_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `vaccines`
--
ALTER TABLE `vaccines`
  ADD CONSTRAINT `vaccines_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `vaccine_categories` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `vaccine_stock`
--
ALTER TABLE `vaccine_stock`
  ADD CONSTRAINT `vaccine_stock_ibfk_1` FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines` (`vaccine_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vaccine_stock_ibfk_2` FOREIGN KEY (`center_id`) REFERENCES `vaccination_centers` (`center_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
