-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 25, 2025 at 03:33 PM
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
-- Database: `fitness_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `email`, `password`) VALUES
(1, 'admin123@gmail.com', '$2y$10$EIc24cGtn5uyVkhV0tDXoecnLV4APy9PTpDP.kPUBg5SAfB.azeGC');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`comment_id`, `post_id`, `user_id`, `content`, `created_at`) VALUES
(1, 1, 1, 'csadsadsa', '2025-07-13 10:45:32'),
(2, 2, 1, 'dasdas', '2025-07-15 12:32:54'),
(7, 2, 3, '123', '2025-08-25 13:31:08');

-- --------------------------------------------------------

--
-- Table structure for table `community_posts`
--

CREATE TABLE `community_posts` (
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `likes` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `community_posts`
--

INSERT INTO `community_posts` (`post_id`, `user_id`, `content`, `likes`, `created_at`) VALUES
(1, 1, 'fsdfdsf', 0, '2025-07-13 10:45:21'),
(2, 1, 'dsda', 1, '2025-07-15 12:32:51'),
(5, 3, 'asdads', 1, '2025-08-25 13:17:56');

-- --------------------------------------------------------

--
-- Table structure for table `goals`
--

CREATE TABLE `goals` (
  `goal_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `target_value` decimal(10,2) DEFAULT NULL,
  `current_value` decimal(10,2) DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `status` enum('active','completed','abandoned') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `goals`
--

INSERT INTO `goals` (`goal_id`, `user_id`, `title`, `description`, `target_value`, `current_value`, `target_date`, `status`, `created_at`) VALUES
(1, 1, 'wqdqw', 'qwdqwdq', 1000.00, 1000.00, '2025-07-14', 'completed', '2025-07-13 10:45:07');

-- --------------------------------------------------------

--
-- Table structure for table `meals`
--

CREATE TABLE `meals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `meal_type` enum('breakfast','lunch','dinner','snack') NOT NULL,
  `meal_name` varchar(100) NOT NULL,
  `meal_time` time NOT NULL,
  `serving_size` varchar(50) DEFAULT NULL,
  `calories` int(11) NOT NULL,
  `protein` decimal(5,2) NOT NULL COMMENT 'in grams',
  `carbs` decimal(5,2) NOT NULL COMMENT 'in grams',
  `fats` decimal(5,2) NOT NULL COMMENT 'in grams',
  `notes` text DEFAULT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meals`
--

INSERT INTO `meals` (`id`, `user_id`, `meal_type`, `meal_name`, `meal_time`, `serving_size`, `calories`, `protein`, `carbs`, `fats`, `notes`, `date`, `created_at`) VALUES
(5, 1, 'breakfast', 'Sample Breakfast', '08:00:00', NULL, 425, 23.00, 48.00, 14.00, 'Plan: Quick & Easy Meals', '2025-07-13', '2025-07-13 11:42:48'),
(6, 1, 'lunch', 'Sample Lunch', '12:30:00', NULL, 595, 32.00, 67.00, 19.00, 'Plan: Quick & Easy Meals', '2025-07-13', '2025-07-13 11:42:48'),
(7, 1, 'dinner', 'Sample Dinner', '18:30:00', NULL, 510, 27.00, 57.00, 17.00, 'Plan: Quick & Easy Meals', '2025-07-13', '2025-07-13 11:42:48'),
(8, 1, 'snack', 'Healthy Snack', '15:30:00', NULL, 170, 9.00, 19.00, 6.00, 'Plan: Quick & Easy Meals', '2025-07-13', '2025-07-13 11:42:48'),
(13, 2, 'breakfast', 'Sample Breakfast', '08:00:00', NULL, 450, 25.00, 50.00, 15.00, 'Plan: Basic Healthy Eating', '2025-07-28', '2025-07-27 18:25:44'),
(14, 2, 'lunch', 'Sample Lunch', '12:30:00', NULL, 630, 35.00, 70.00, 21.00, 'Plan: Basic Healthy Eating', '2025-07-28', '2025-07-27 18:25:44'),
(15, 2, 'dinner', 'Sample Dinner', '18:30:00', NULL, 540, 30.00, 60.00, 18.00, 'Plan: Basic Healthy Eating', '2025-07-28', '2025-07-27 18:25:44'),
(16, 2, 'snack', 'Healthy Snack', '15:30:00', NULL, 180, 10.00, 20.00, 6.00, 'Plan: Basic Healthy Eating', '2025-07-28', '2025-07-27 18:25:44'),
(17, 1, 'breakfast', 'Sample Breakfast', '08:00:00', NULL, 450, 25.00, 50.00, 15.00, 'Plan: Basic Healthy Eating', '2025-08-25', '2025-08-25 04:23:24'),
(18, 1, 'lunch', 'Sample Lunch', '12:30:00', '', 530, 35.00, 70.00, 21.00, 'Plan: Basic Healthy Eating', '2025-08-25', '2025-08-25 04:23:24'),
(19, 1, 'dinner', 'Sample Dinner', '18:30:00', NULL, 540, 30.00, 60.00, 18.00, 'Plan: Basic Healthy Eating', '2025-08-25', '2025-08-25 04:23:24'),
(20, 1, 'snack', 'Healthy Snack', '15:30:00', NULL, 180, 10.00, 20.00, 6.00, 'Plan: Basic Healthy Eating', '2025-08-25', '2025-08-25 04:23:24');

-- --------------------------------------------------------

--
-- Table structure for table `meal_plans`
--

CREATE TABLE `meal_plans` (
  `id` int(11) NOT NULL,
  `plan_name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `goal` enum('weight_loss','muscle_gain','endurance','maintenance','general') NOT NULL,
  `calories` int(11) NOT NULL,
  `protein` decimal(5,2) NOT NULL COMMENT 'in grams',
  `carbs` decimal(5,2) NOT NULL COMMENT 'in grams',
  `fats` decimal(5,2) NOT NULL COMMENT 'in grams',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meal_plans`
--

INSERT INTO `meal_plans` (`id`, `plan_name`, `description`, `goal`, `calories`, `protein`, `carbs`, `fats`, `created_at`) VALUES
(1, 'Balanced Weight Loss', 'A well-balanced meal plan focused on portion control and nutrient-dense foods to promote healthy weight loss.', 'weight_loss', 1500, 112.00, 150.00, 50.00, '2025-07-13 10:48:40'),
(2, 'High Protein Weight Loss', 'Higher protein intake to preserve muscle mass while losing fat, with moderate carbs and healthy fats.', 'weight_loss', 1600, 140.00, 120.00, 60.00, '2025-07-13 10:48:40'),
(3, 'Vegetarian Weight Loss', 'Plant-based meal plan with complete proteins and fiber-rich foods for sustainable weight loss.', 'weight_loss', 1400, 90.00, 160.00, 45.00, '2025-07-13 10:48:40'),
(4, 'Lean Muscle Builder', 'High protein with balanced macros to support muscle growth while minimizing fat gain.', 'muscle_gain', 2800, 200.00, 300.00, 80.00, '2025-07-13 10:48:40'),
(5, 'Bulking Plan', 'Calorie-dense meals with ample protein to support significant muscle growth.', 'muscle_gain', 3200, 220.00, 400.00, 100.00, '2025-07-13 10:48:40'),
(6, 'Clean Mass Gain', 'Quality whole foods providing steady energy and nutrients for lean muscle development.', 'muscle_gain', 3000, 210.00, 350.00, 90.00, '2025-07-13 10:48:40'),
(7, 'Endurance Athlete', 'Higher carb intake to fuel long training sessions with adequate protein for recovery.', 'endurance', 2500, 150.00, 350.00, 70.00, '2025-07-13 10:48:40'),
(8, 'Pre-Competition Fuel', 'Optimized carb loading strategy for endurance events with easily digestible proteins.', 'endurance', 2700, 130.00, 400.00, 60.00, '2025-07-13 10:48:40'),
(9, 'Recovery Focus', 'Post-workout nutrition emphasis with balanced macros to replenish glycogen stores.', 'endurance', 2300, 160.00, 300.00, 75.00, '2025-07-13 10:48:40'),
(10, 'Balanced Maintenance', 'Sustainable eating pattern to maintain current weight and support overall health.', 'maintenance', 2000, 120.00, 220.00, 70.00, '2025-07-13 10:48:40'),
(11, 'Flexible Dieting', 'Macro-balanced plan allowing for variety while maintaining energy balance.', 'maintenance', 2100, 130.00, 230.00, 75.00, '2025-07-13 10:48:40'),
(12, 'Mediterranean Style', 'Heart-healthy fats with lean proteins and complex carbs for long-term wellness.', 'maintenance', 1900, 110.00, 200.00, 65.00, '2025-07-13 10:48:40'),
(13, 'Basic Healthy Eating', 'Foundation plan with whole foods suitable for anyone starting their fitness journey.', 'general', 1800, 100.00, 200.00, 60.00, '2025-07-13 10:48:40'),
(14, 'Quick & Easy Meals', 'Simple recipes with minimal prep time that still meet nutritional needs.', 'general', 1700, 90.00, 190.00, 55.00, '2025-07-13 10:48:40'),
(15, 'Budget-Friendly', 'Nutritious meals using affordable ingredients without sacrificing quality.', 'general', 1750, 95.00, 195.00, 58.00, '2025-07-13 10:48:40');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `age` int(11) NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `height` decimal(5,2) NOT NULL COMMENT 'in cm',
  `weight` decimal(5,2) NOT NULL COMMENT 'in kg',
  `fitness_goal` enum('weight_loss','muscle_gain','endurance','maintenance') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `calorie_target` int(11) DEFAULT 2000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `password`, `age`, `gender`, `height`, `weight`, `fitness_goal`, `created_at`, `updated_at`, `calorie_target`) VALUES
(1, 'ongjipet', 'ongjipet123@gmail.com', '$2y$10$XMza2MkxCkj0CIxc5b1RkeZOXPrdLAICOx2MLJc2bXxbVEtvVdbuu', 22, 'male', 170.00, 60.00, '', '2025-07-13 10:36:44', '2025-08-25 04:23:24', 1800),
(2, 'nobody', 'dllm@gmail.com', '$2y$10$Qd9zWhAyhyDKYh/o5Fgugez4YKKFRvNIvZhQvxrSDjnbYyXU2kOJ6', 22, 'female', 180.00, 65.00, 'weight_loss', '2025-07-24 18:28:57', '2025-08-25 13:01:19', 1700),
(3, 'Admin', 'admin@system.local', '$2y$10$DzgiMyEI/nRp5yxdgCzh/.h/CZ/f6Lvk07MrjtycJgR4zwg1WUq3a', 0, 'other', 0.00, 0.00, '', '2025-08-25 13:17:56', '2025-08-25 13:17:56', 0);

-- --------------------------------------------------------

--
-- Table structure for table `water_intake`
--

CREATE TABLE `water_intake` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `glasses` int(11) NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `water_intake`
--

INSERT INTO `water_intake` (`id`, `user_id`, `glasses`, `date`, `created_at`) VALUES
(1, 1, 1, '2025-07-13', '2025-07-13 11:42:36');

-- --------------------------------------------------------

--
-- Table structure for table `workouts`
--

CREATE TABLE `workouts` (
  `workout_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `workout_type` enum('strength','cardio','hiit','yoga','other') NOT NULL,
  `workout_date` date NOT NULL,
  `duration_minutes` int(11) NOT NULL,
  `calories_burned` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workouts`
--

INSERT INTO `workouts` (`workout_id`, `user_id`, `workout_type`, `workout_date`, `duration_minutes`, `calories_burned`, `notes`, `created_at`) VALUES
(1, 1, 'strength', '2025-07-13', 20, 200, 'updated 25/8', '2025-07-13 10:44:39');

-- --------------------------------------------------------

--
-- Table structure for table `workout_plans`
--

CREATE TABLE `workout_plans` (
  `plan_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_date` date NOT NULL,
  `morning_routine` text DEFAULT NULL,
  `evening_activity` text DEFAULT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workout_plans`
--

INSERT INTO `workout_plans` (`plan_id`, `user_id`, `plan_date`, `morning_routine`, `evening_activity`, `is_completed`, `created_at`, `updated_at`) VALUES
(1, 1, '2025-07-14', 'fdeqwedwq', 'dwqdqw', 0, '2025-07-14 07:19:14', '2025-08-08 10:54:21'),
(2, 2, '2025-07-31', '12345678', '1234567890', 1, '2025-07-31 10:31:58', '2025-08-02 11:09:44'),
(3, 1, '2025-08-06', 'Drink', 'Workout', 0, '2025-08-06 20:16:27', '2025-08-07 20:27:44');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_comments_post` (`post_id`);

--
-- Indexes for table `community_posts`
--
ALTER TABLE `community_posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `idx_community_posts_user` (`user_id`);

--
-- Indexes for table `goals`
--
ALTER TABLE `goals`
  ADD PRIMARY KEY (`goal_id`),
  ADD KEY `idx_goals_user_status` (`user_id`,`status`);

--
-- Indexes for table `meals`
--
ALTER TABLE `meals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_date` (`user_id`,`date`);

--
-- Indexes for table `meal_plans`
--
ALTER TABLE `meal_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_meal_plans_goal` (`goal`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `water_intake`
--
ALTER TABLE `water_intake`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_water_user_date` (`user_id`,`date`);

--
-- Indexes for table `workouts`
--
ALTER TABLE `workouts`
  ADD PRIMARY KEY (`workout_id`),
  ADD KEY `idx_workouts_user_date` (`user_id`,`workout_date`);

--
-- Indexes for table `workout_plans`
--
ALTER TABLE `workout_plans`
  ADD PRIMARY KEY (`plan_id`),
  ADD UNIQUE KEY `unique_user_plan` (`user_id`,`plan_date`),
  ADD KEY `idx_workout_plans_user_date` (`user_id`,`plan_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `community_posts`
--
ALTER TABLE `community_posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `goals`
--
ALTER TABLE `goals`
  MODIFY `goal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `meals`
--
ALTER TABLE `meals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `meal_plans`
--
ALTER TABLE `meal_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `water_intake`
--
ALTER TABLE `water_intake`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `workouts`
--
ALTER TABLE `workouts`
  MODIFY `workout_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `workout_plans`
--
ALTER TABLE `workout_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `community_posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `community_posts`
--
ALTER TABLE `community_posts`
  ADD CONSTRAINT `community_posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `goals`
--
ALTER TABLE `goals`
  ADD CONSTRAINT `goals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `meals`
--
ALTER TABLE `meals`
  ADD CONSTRAINT `meals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `water_intake`
--
ALTER TABLE `water_intake`
  ADD CONSTRAINT `water_intake_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `workouts`
--
ALTER TABLE `workouts`
  ADD CONSTRAINT `workouts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `workout_plans`
--
ALTER TABLE `workout_plans`
  ADD CONSTRAINT `workout_plans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
