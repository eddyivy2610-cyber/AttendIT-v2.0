-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 13, 2025 at 03:07 PM
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
-- Database: `attendit_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `arrival_time` time DEFAULT NULL,
  `departure_time` time DEFAULT NULL,
  `status` enum('Present','Absent','Late','Pending') DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `marked_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `student_id`, `attendance_date`, `arrival_time`, `departure_time`, `status`, `notes`, `marked_by`, `created_at`) VALUES
(0, 3, '2025-11-10', '12:45:48', NULL, 'Late', NULL, NULL, '2025-11-10 11:45:48'),
(0, 2, '2025-11-10', '12:45:56', NULL, 'Late', NULL, NULL, '2025-11-10 11:45:56'),
(0, 0, '2025-11-10', '12:46:01', NULL, 'Late', NULL, NULL, '2025-11-10 11:46:01'),
(0, 5, '2025-11-10', '12:46:02', NULL, 'Late', NULL, NULL, '2025-11-10 11:46:02'),
(0, 4, '2025-11-10', '12:46:03', NULL, 'Late', NULL, NULL, '2025-11-10 11:46:03'),
(0, 1, '2025-11-10', '12:46:05', NULL, 'Late', NULL, NULL, '2025-11-10 11:46:05');

-- --------------------------------------------------------

--
-- Table structure for table `daily_summaries`
--

CREATE TABLE `daily_summaries` (
  `summary_id` int(11) NOT NULL,
  `summary_date` date NOT NULL,
  `total_students` int(11) DEFAULT 0,
  `present_count` int(11) DEFAULT 0,
  `late_count` int(11) DEFAULT 0,
  `absent_count` int(11) DEFAULT 0,
  `attendance_rate` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_summaries`
--

INSERT INTO `daily_summaries` (`summary_id`, `summary_date`, `total_students`, `present_count`, `late_count`, `absent_count`, `attendance_rate`) VALUES
(0, '2025-11-10', 6, 0, 1, 5, 16.67),
(0, '2025-11-10', 6, 0, 2, 4, 33.33),
(0, '2025-11-10', 6, 0, 3, 3, 50.00),
(0, '2025-11-10', 6, 0, 4, 2, 66.67),
(0, '2025-11-10', 6, 0, 5, 1, 83.33),
(0, '2025-11-10', 6, 0, 6, 0, 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `institutions`
--

CREATE TABLE `institutions` (
  `institution_id` int(11) NOT NULL,
  `institution_name` varchar(255) NOT NULL,
  `institution_logo` varchar(500) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `institutions`
--

INSERT INTO `institutions` (`institution_id`, `institution_name`, `institution_logo`, `contact_email`, `created_at`) VALUES
(1, 'Ahmadu Bello University', NULL, 'contact@abu.edu.ng', '2025-09-25 12:51:48'),
(2, 'Nuhu Bamalli Polytechnic', NULL, 'info@nuhubamallipoly.edu.ng', '2025-09-25 12:51:48'),
(3, 'AFIT Kaduna', NULL, 'admin@afit.edu.ng', '2025-09-25 12:51:48'),
(4, 'Kaduna State University', NULL, 'admissions@ksu.edu.ng', '2025-09-25 12:51:48'),
(5, 'Yobe State University', NULL, 'info@yobeuni.edu.ng', '2025-09-25 12:51:48'),
(6, 'Federal University Dutse, Jigawa', NULL, 'fud.edu.ng', '2025-09-25 12:51:48'),
(7, 'Base Universiy Abuja', NULL, 'baseuni.edu.ng', '2025-09-25 12:51:48'),
(8, 'Federal University of Education, Zaria', NULL, 'fue.edu.ng', '2025-09-25 12:51:48');

-- --------------------------------------------------------

--
-- Table structure for table `performance_metrics`
--

CREATE TABLE `performance_metrics` (
  `metric_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `evaluated_by` int(11) DEFAULT NULL,
  `evaluation_date` date NOT NULL,
  `technical_skill` int(11) DEFAULT 0,
  `learning_activity` int(11) DEFAULT 0,
  `active_contribution` int(11) DEFAULT 0,
  `overall_rating` decimal(5,2) DEFAULT NULL,
  `comments` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `period_of_attachment` varchar(100) DEFAULT NULL,
  `institution_id` int(11) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `course_of_study` varchar(255) DEFAULT NULL,
  `skill_of_interest` text DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `join_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `supervisor` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `phone` varchar(20) DEFAULT NULL,
  `photo_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `email`, `student_name`, `period_of_attachment`, `institution_id`, `birthday`, `course_of_study`, `skill_of_interest`, `gender`, `join_date`, `end_date`, `supervisor`, `status`, `phone`, `photo_url`, `created_at`) VALUES
(1, 'john.lucky@student.abu.edu.ng', 'John Lucky', '6 months', 1, '2000-10-26', 'Computer Science', 'Web Design, Graphics Design', 'Male', '2025-03-26', '2025-09-26', 'Mr Kabir Muhammad', 'Active', '+2347081066985', NULL, '2025-09-25 12:51:49'),
(2, 'zipporah.toma@student.nbp.edu.ng', 'Zipporah Toma', '6 months', 2, '1999-05-15', 'Computer Science', 'Database Management, Networking', 'Female', '2025-03-26', '2025-09-26', 'Mr Kabir Muhammad', 'Active', '+2348012345678', NULL, '2025-09-25 12:51:49'),
(3, 'shuaibuananu@gmail.com', 'Ananu Awodi', '3', 3, '0000-00-00', 'information and communication engineering', 'Data Analysis and Hardware', 'Female', '2025-09-26', '2025-12-26', 'Kabir Muhammad', 'Active', '+2348132529287', '', '2025-09-26 09:29:48'),
(4, 'wakawajeremiah@gmail.cm', 'Jeremiah Wakawa', '6', 1, '0000-00-00', 'Computer Science', 'Web Development', 'Male', '2025-09-26', '2026-03-26', 'Kabir Muhammad', 'Active', '+2349037091852', '', '2025-09-26 10:05:53'),
(5, 'halima@gmail.com', 'Halima Abubakar', '6', 1, '0000-00-00', 'Computer Science', 'Web Development', 'Female', '2025-10-23', '2026-04-23', 'Kabir Muhammad', 'Active', '+2348132529287', 'uploads/passports/passport_68f9f728311c46.12149578.jpg', '2025-10-23 08:36:40'),
(6, 'pashirumar003@gmail.com', 'Umar Pashir', '6', 6, '0001-11-01', 'Computer Science', 'Web Devrelopment', 'Male', '2025-11-10', '2026-05-10', '', 'Active', '+2347048447822', '', '2025-11-10 11:42:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `user_name` varchar(50) NOT NULL,
  `user_email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_role` varchar(20) NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `user_name`, `user_email`, `password`, `user_role`, `created_at`, `is_active`, `last_login`) VALUES
(1, 'ATE School', 'info@ncat.gov.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'supervisor', '2025-10-08 23:20:24', 1, '2025-11-13 12:11:34'),
(2, 'admin', 'info@ncat.gov.ng', '$2y$10$O2J6BFMI3vB0UUrAjHyslOoBzDKF1/Nf3ZkyFkxKwruz83aLFSHe.', 'admin', '2025-10-22 14:00:48', 1, NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
