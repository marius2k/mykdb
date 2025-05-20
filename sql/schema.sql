-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 20, 2025 at 10:34 PM
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
-- Database: `knowledge_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action_type`, `ip_address`, `user_agent`, `details`, `created_at`) VALUES
(1, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-05 11:43:43'),
(2, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged in\"', '2025-05-05 11:44:03'),
(3, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-05 11:44:17'),
(4, NULL, 'login_failed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"Failed login attempt for username: user1\"', '2025-05-05 11:44:24'),
(5, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged in\"', '2025-05-05 11:44:43'),
(6, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-05 11:44:57'),
(7, 4, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged in\"', '2025-05-05 11:45:06'),
(8, 4, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-05 11:47:31'),
(9, 3, 'login_failed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"Login attempt for an inactive user: user1\"', '2025-05-05 11:47:38'),
(10, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-05 12:37:32'),
(11, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-05 13:32:54'),
(12, 6, 'register_user', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User registered: corny\"', '2025-05-05 13:33:26'),
(13, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-05 13:36:45'),
(14, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-06 07:46:02'),
(15, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-06 07:52:21'),
(16, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-06 07:57:25'),
(17, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-06 07:57:31'),
(18, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-06 08:10:37'),
(19, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-06 08:10:51'),
(20, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-06 08:11:15'),
(21, NULL, 'login_failed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"Failed login attempt for username: admin\"', '2025-05-06 08:11:20'),
(22, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-06 08:11:25'),
(23, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-06 08:14:21'),
(24, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-06 08:14:41'),
(25, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-06 09:59:58'),
(26, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-06 10:00:03'),
(27, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-06 10:18:23'),
(28, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-06 10:19:44'),
(29, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-06 10:21:42'),
(30, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-06 10:21:51'),
(31, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-06 10:36:44'),
(32, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-06 10:36:54'),
(33, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-06 12:13:05'),
(34, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-06 12:25:33'),
(35, 2, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User admin viewed the article:test qwe\"', '2025-05-06 12:36:20'),
(36, 2, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User admin viewed the article:test qwe\"', '2025-05-06 14:38:36'),
(37, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-06 14:42:53'),
(38, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-07 07:51:33'),
(39, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-07 10:56:54'),
(40, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-07 10:57:00'),
(41, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-07 11:00:23'),
(42, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-07 11:00:35'),
(43, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-07 11:14:52'),
(44, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-07 11:14:58'),
(45, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-07 11:41:08'),
(46, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-07 11:41:24'),
(47, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-07 11:43:04'),
(48, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-07 11:43:08'),
(49, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-07 11:48:38'),
(50, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-07 11:48:42'),
(51, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-07 11:57:16'),
(52, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-07 11:57:21'),
(53, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-07 12:28:27'),
(54, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-07 12:28:33'),
(55, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-07 12:39:05'),
(56, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-07 12:39:13'),
(57, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-07 13:41:34'),
(58, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-07 14:55:15'),
(59, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-07 14:55:23'),
(60, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-07 14:55:29'),
(61, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-07 14:56:40'),
(62, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-07 14:56:45'),
(63, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-07 14:56:52'),
(64, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-07 14:56:58'),
(65, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-08 07:08:49'),
(66, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-08 07:08:53'),
(67, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-08 08:33:02'),
(68, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-08 08:33:07'),
(69, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-08 09:04:06'),
(70, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-08 09:04:14'),
(71, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-08 10:14:49'),
(72, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-08 10:14:55'),
(73, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-08 11:55:09'),
(74, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-08 11:55:15'),
(75, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-08 12:16:33'),
(76, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-08 12:18:57'),
(77, NULL, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-08 12:19:07'),
(78, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-08 12:21:23'),
(79, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-08 12:29:26'),
(80, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-09 06:22:03'),
(81, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-09 06:23:31'),
(82, NULL, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-09 06:24:16'),
(83, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-09 06:24:25'),
(84, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-09 06:42:16'),
(85, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-09 06:42:29'),
(86, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-09 07:25:15'),
(87, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-09 07:26:00'),
(88, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-09 07:26:04'),
(89, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-09 07:26:11'),
(90, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-09 07:26:20'),
(91, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-09 07:26:26'),
(92, NULL, 'login_failed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"Failed login attempt for username: mihai\"', '2025-05-09 07:26:32'),
(93, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-09 07:32:35'),
(94, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-09 07:38:01'),
(95, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-09 07:38:06'),
(96, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-09 07:38:44'),
(97, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-09 07:38:48'),
(98, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-09 07:39:51'),
(99, NULL, 'login_failed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"Failed login attempt for username: mihai\"', '2025-05-09 07:39:55'),
(100, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-09 07:40:00'),
(101, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-09 07:42:45'),
(102, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-09 07:48:07'),
(103, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-09 08:16:47'),
(104, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-09 08:16:53'),
(105, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-09 08:19:09'),
(106, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-09 08:21:16'),
(107, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-09 09:01:16'),
(108, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-09 09:01:21'),
(109, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-12 07:25:21'),
(110, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-12 07:25:52'),
(111, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-12 07:27:18'),
(112, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-12 07:27:23'),
(113, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-12 10:49:56'),
(114, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-12 10:50:01'),
(115, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-12 10:58:47'),
(116, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-12 10:58:51'),
(117, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-12 11:20:12'),
(118, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-12 11:20:19'),
(119, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-12 11:39:24'),
(120, 4, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inuser2\"', '2025-05-12 11:39:45'),
(121, 4, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-12 12:02:54'),
(122, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-12 12:02:58'),
(123, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-13 14:10:56'),
(124, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-13 14:11:01'),
(125, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-13 14:20:58'),
(126, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-13 14:21:03'),
(127, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-13 15:25:37'),
(128, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-13 15:25:42'),
(129, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-13 15:55:20'),
(130, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-13 15:55:25'),
(131, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-13 17:03:24'),
(132, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-13 17:03:29'),
(133, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-13 17:13:27'),
(134, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-13 17:13:34'),
(135, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-13 17:17:27'),
(136, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-13 17:17:31'),
(137, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-13 18:14:22'),
(138, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-13 18:14:28'),
(139, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-13 18:16:07'),
(140, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-14 08:03:03'),
(141, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-14 12:06:21'),
(142, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-14 13:23:15'),
(143, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-14 14:25:04'),
(144, 2, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User admin viewed the article:Articol de test cu upload de imagine\"', '2025-05-14 14:25:21'),
(145, 2, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User admin viewed the article:test qwe\"', '2025-05-14 14:25:42'),
(146, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-15 07:52:39'),
(147, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-15 07:59:10'),
(148, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-15 08:01:37'),
(149, NULL, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-15 08:15:41'),
(150, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-15 08:49:15'),
(151, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-15 08:51:15'),
(152, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-15 08:51:21'),
(153, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-15 08:51:27'),
(154, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-15 08:52:04'),
(155, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-15 08:52:09'),
(156, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-15 08:54:51'),
(157, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-15 08:54:58'),
(158, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-15 08:57:26'),
(159, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-15 08:57:33'),
(160, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-15 08:59:20'),
(161, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-15 08:59:29'),
(162, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-15 09:01:06'),
(163, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-15 09:01:10'),
(164, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-15 12:40:34'),
(165, 7, 'register_user', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User registered: marius\"', '2025-05-15 12:58:28'),
(166, 7, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmarius\"', '2025-05-15 12:59:29'),
(167, 7, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-15 13:09:54'),
(168, 7, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmarius\"', '2025-05-15 13:10:01'),
(169, 7, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-15 14:31:26'),
(170, 7, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmarius\"', '2025-05-15 14:31:34'),
(171, 7, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-15 14:45:06'),
(172, 7, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmarius\"', '2025-05-15 14:45:13'),
(173, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-15 19:34:48'),
(174, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-15 19:37:44'),
(175, 7, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmarius\"', '2025-05-15 19:37:50'),
(176, 7, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-15 19:38:00'),
(177, 7, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmarius\"', '2025-05-15 19:38:08'),
(178, 7, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmarius\"', '2025-05-16 07:36:38'),
(179, 7, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-16 07:36:49'),
(180, NULL, 'login_failed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"Failed login attempt for username: corny\"', '2025-05-16 07:36:56'),
(181, NULL, 'login_failed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"Failed login attempt for username: corny\"', '2025-05-16 07:37:00'),
(182, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-16 07:37:08'),
(183, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-16 07:46:49'),
(184, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-16 07:46:54'),
(185, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-16 07:57:29'),
(186, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-16 07:57:38'),
(187, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-16 08:00:12'),
(188, 7, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmarius\"', '2025-05-16 08:00:17'),
(189, 7, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmarius\"', '2025-05-16 09:41:52'),
(190, 7, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-16 09:53:27'),
(191, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-16 09:59:56'),
(192, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-16 10:00:54'),
(193, NULL, 'login_failed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"Failed login attempt for username: corny\"', '2025-05-16 10:01:00'),
(194, 4, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inuser2\"', '2025-05-16 10:01:13'),
(195, 4, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-16 10:33:44'),
(196, 7, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmarius\"', '2025-05-16 10:33:50'),
(197, 7, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-16 10:37:51'),
(198, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-16 10:37:57'),
(199, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-16 12:18:23'),
(200, 7, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmarius\"', '2025-05-16 12:18:29'),
(201, 7, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-16 12:18:34'),
(202, NULL, 'login_failed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"Failed login attempt for username: mihai\"', '2025-05-16 12:18:39'),
(203, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-16 12:18:44'),
(204, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-16 12:51:00'),
(205, NULL, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-16 13:30:49'),
(206, NULL, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-16 13:30:50'),
(207, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-16 13:37:26'),
(208, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-16 13:37:39'),
(209, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-16 13:37:45'),
(210, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-16 13:52:29'),
(211, NULL, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User  viewed the article:test qwe\"', '2025-05-16 13:52:51'),
(212, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-16 13:55:05'),
(213, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-16 14:26:10'),
(214, 4, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inuser2\"', '2025-05-16 14:26:25'),
(215, 6, 'user_approved', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User approved: user2\"', '2025-05-16 14:26:45'),
(216, 6, 'user_disabled', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User disabled: user2\"', '2025-05-16 14:27:02'),
(217, 4, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-16 14:29:21'),
(218, 7, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmarius\"', '2025-05-16 14:29:26'),
(219, 7, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-16 16:08:02'),
(220, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-16 16:08:13'),
(221, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-16 16:08:44'),
(222, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-17 19:22:45'),
(223, 2, 'create_article', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User admin created the article:What is a Knowledge Base System? \"', '2025-05-17 20:02:33'),
(224, 2, 'article_approved', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User admin approved an article\"', '2025-05-17 20:02:42'),
(225, 2, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User admin viewed the article:What is a Knowledge Base System?\"', '2025-05-17 20:02:49'),
(226, 2, 'create_article', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User admin created the article:Why You Need a Knowledge Base\"', '2025-05-17 20:13:57'),
(227, 2, 'article_approved', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User admin approved an article\"', '2025-05-17 20:14:45'),
(228, 2, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User admin viewed the article:Why You Need a Knowledge Base\"', '2025-05-19 10:59:05'),
(229, 2, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User admin viewed the article:Why You Need a Knowledge Base\"', '2025-05-19 13:38:05'),
(230, 2, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User admin viewed the article:Articol de test cu upload de imagine\"', '2025-05-19 13:40:51'),
(231, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-19 14:02:01'),
(232, 4, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inuser2\"', '2025-05-19 14:02:07'),
(233, 4, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User user2 viewed the article:Why You Need a Knowledge Base\"', '2025-05-19 14:57:21'),
(234, 4, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-19 15:55:54'),
(235, 4, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inuser2\"', '2025-05-19 15:56:08'),
(236, 4, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-19 15:56:30'),
(237, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-19 15:56:35'),
(238, 2, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User admin viewed the article:What is a Knowledge Base System?\"', '2025-05-19 15:57:12'),
(239, 2, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User admin viewed the article:How To create a knowledge article\"', '2025-05-19 16:36:25'),
(240, 2, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User admin viewed the article:test cu imagine\"', '2025-05-19 20:04:47'),
(241, 2, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User admin viewed the article:test cu imagine\"', '2025-05-19 20:04:59'),
(242, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-20 06:40:15'),
(243, 5, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmihai\"', '2025-05-20 06:40:21'),
(244, 5, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-20 06:41:37'),
(245, NULL, 'login_failed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"Failed login attempt for username: corny\"', '2025-05-20 06:41:45'),
(246, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-20 06:42:14'),
(247, 3, 'user_restored', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User restored: admin\"', '2025-05-20 06:42:23'),
(248, 6, 'user_restored', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User restored: admin\"', '2025-05-20 06:42:26'),
(249, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-20 06:42:30'),
(250, 6, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged incorny\"', '2025-05-20 06:42:36'),
(251, 6, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User corny viewed the article:What is a Knowledge Base System?\"', '2025-05-20 06:44:02'),
(252, 6, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User corny viewed the article:What is a Knowledge Base System?\"', '2025-05-20 06:44:09'),
(253, 6, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User corny viewed the article:test cu imagine\"', '2025-05-20 06:44:25'),
(254, 6, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User corny viewed the article:test cu imagine\"', '2025-05-20 06:44:34'),
(255, 6, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-20 07:06:12'),
(256, 7, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inmarius\"', '2025-05-20 07:06:18'),
(257, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 07:07:45'),
(258, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 07:09:59'),
(259, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 07:14:07'),
(260, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 07:14:09'),
(261, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 07:15:05'),
(262, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 07:15:06'),
(263, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 07:15:09'),
(264, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 07:16:15'),
(265, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 07:17:02');
INSERT INTO `activity_log` (`id`, `user_id`, `action_type`, `ip_address`, `user_agent`, `details`, `created_at`) VALUES
(266, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 07:17:03'),
(267, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 07:17:16'),
(268, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 07:18:48'),
(269, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 07:19:13'),
(270, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 07:19:37'),
(271, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 07:20:13'),
(272, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 07:21:24'),
(273, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 07:23:26'),
(274, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 07:24:29'),
(275, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 07:28:44'),
(276, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 07:30:02'),
(277, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 07:31:50'),
(278, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:What is a Knowledge Base System?\"', '2025-05-20 07:37:25'),
(279, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:What is a Knowledge Base System?\"', '2025-05-20 07:38:17'),
(280, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:What is a Knowledge Base System?\"', '2025-05-20 07:42:43'),
(281, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:What is a Knowledge Base System?\"', '2025-05-20 07:43:54'),
(282, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:What is a Knowledge Base System?\"', '2025-05-20 07:44:12'),
(283, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 07:49:35'),
(284, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 07:51:29'),
(285, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 07:51:44'),
(286, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 08:18:48'),
(287, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 08:19:41'),
(288, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Why You Need a Knowledge Base\"', '2025-05-20 08:19:47'),
(289, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:What is a Knowledge Base System?\"', '2025-05-20 08:21:50'),
(290, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:What is a Knowledge Base System?\"', '2025-05-20 08:21:55'),
(291, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Articol de test cu upload de imagine\"', '2025-05-20 08:39:46'),
(292, 7, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User marius viewed the article:Articol de test cu upload de imagine\"', '2025-05-20 08:40:33'),
(293, 7, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-20 08:46:04'),
(294, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-20 08:46:09'),
(295, 2, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User admin viewed the article:How to Reset Your Password\"', '2025-05-20 08:57:29'),
(296, 2, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User admin viewed the article:test qwe\"', '2025-05-20 08:57:58'),
(297, 2, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User admin viewed the article:Test 1 for Installation and Configuration\"', '2025-05-20 08:58:18'),
(298, 2, 'article_viewed', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User admin viewed the article:Test 1 for Installation and Configuration\"', '2025-05-20 08:58:34'),
(299, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-20 09:54:48'),
(300, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-20 09:54:54'),
(301, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-20 09:55:25'),
(302, 2, 'login_success', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged inadmin\"', '2025-05-20 09:55:43'),
(303, 2, 'reject_comment', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User admin rejected a comment\"', '2025-05-20 13:25:42'),
(304, 2, 'reject_comment', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User admin rejected a comment\"', '2025-05-20 13:32:36'),
(305, 2, 'approve_comment', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User admin approved a comment\"', '2025-05-20 13:32:53'),
(306, 3, 'user_disabled', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User disabled: admin\"', '2025-05-20 20:13:18');

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--

CREATE TABLE `articles` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `views` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `articles`
--

INSERT INTO `articles` (`id`, `title`, `content`, `category_id`, `user_id`, `status`, `created_at`, `updated_at`, `views`) VALUES
(1, 'Test 1 for Installation and Configuration', 'This is the first test article for the Installation and Configuration category', 1, 2, 'approved', '2025-04-23 11:06:19', '2025-05-20 08:58:34', 2),
(2, 'How To create a knowledge article', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce pharetra sagittis porta. Sed nec mauris nulla. Sed tristique iaculis diam, ut ultrices sem fringilla nec. Nunc sollicitudin, turpis ac tincidunt porta, lectus tortor luctus sapien, quis dignissim diam orci pharetra urna. Proin sagittis nunc ut urna euismod, quis vehicula risus ullamcorper. Fusce nibh nibh, ultricies eu ante eget, dictum laoreet ex. Integer diam ipsum, egestas vel risus tincidunt, convallis consectetur elit. Etiam auctor erat quis sem dignissim, at malesuada est laoreet. Donec id porta tortor, non auctor metus. Pellentesque bibendum mollis risus. Maecenas cursus ipsum urna, at vestibulum turpis ornare sit amet. Nunc malesuada, dolor non tincidunt laoreet, erat risus faucibus ante, eu viverra elit felis at leo. Maecenas tincidunt mattis diam. first', 2, 3, 'approved', '2025-04-23 13:34:05', '2025-05-19 16:36:25', 1),
(3, 'How to Reset Your Password', 'If you forgot your password, follow these steps to reset it. Steps to Reset Password:\r\n1. Go to the Login Page.\r\n2. Click \"Forgot Password?\".\r\n3. Enter your registered email address.\r\n4. Check your inbox for a reset link (valid for 24 hours).\r\n5.Click the link and set a new password.', 2, 4, 'approved', '2025-04-23 13:38:49', '2025-05-20 08:57:29', 1),
(4, 'Articol de test cu upload de imagine', '<strong>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi nisi neque, cursus at pulvinar at, dapibus sed nibh.</strong> <br><br><em>Nullam bibendum nisi sed mi vulputate, vitae viverra mauris fringilla.</em> Fusce vitae laoreet neque. Fusce vitae purus et diam bibendum tristique. Nunc sit amet scelerisque dui. Pellentesque tempor enim ac ullamcorper mollis. <br><a href=\"uploads/img_680f527d2b5cb2.20153610.jpeg\">kdb_top.jpeg 53.39 KB</a><em><br></em>Praesent placerat convallis ante, non viverra libero lacinia quis. Aliquam non eros sit amet leo dictum vestibulum. Integer felis turpis, venenatis nec gravida sodales, laoreet in neque. Nunc eu accumsan diam. Sed dolor ex, ultricies nec purus et, vehicula tincidunt metus. Nulla quis leo vitae nisl posuere faucibus. Curabitur mi ipsum, tempor sollicitudin orci mattis, posuere consequat purus.<br><br>Suspendisse non cursus lacus, interdum gravida justo. Maecenas cursus tincidunt arcu. Aliquam quis tellus massa. Nullam maximus luctus lectus vel efficitur. Nulla tempor, purus ac pulvinar ornare, libero dolor convallis sapien, quis dictum lacus arcu lacinia ex. Donec auctor odio imperdiet, semper purus in, posuere leo. Mauris in dictum massa. Aliquam urna risus, lobortis non urna nec, bibendum egestas urna. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Nunc non dui ut tortor elementum mattis. Mauris ut eleifend nibh, non laoreet neque. Pellentesque elementum laoreet arcu ut pellentesque. Morbi egestas urna et consequat consequat.<br><br><br>', 2, 2, 'approved', '2025-04-28 10:04:15', '2025-05-20 08:40:33', 3),
(5, 'test cu imagine', 'Vestibulum dignissim blandit augue ac elementum. Maecenas luctus, ligula a tempus varius, leo dui laoreet purus, auctor tempor libero lorem vitae nibh. Interdum et malesuada fames ac ante ipsum primis in faucibus. Praesent rhoncus turpis in urna scelerisque congue. <br><a href=\"http://localhost/mykdb/uploads/img_680f606e54a986.34743773.png\"></a><br><br>Phasellus finibus vestibulum eros eget aliquet. Duis euismod viverra ex. Nunc sollicitudin erat id leo efficitur, et aliquam felis dignissim. Aenean fringilla viverra mi, a tincidunt ligula ornare eu. Sed nulla nibh, malesuada a accumsan vitae, tincidunt vel ipsum. Nulla facilisi. In vitae ullamcorper purus. Duis ornare diam quis tortor vulputate, pellentesque pharetra nunc aliquam. Duis vitae enim in lorem tristique rhoncus. Maecenas ultrices condimentum ipsum, nec tristique enim accumsan volutpat. Cras placerat maximus mauris in interdum. Maecenas finibus ex at gravida auctor.', 1, 2, 'approved', '2025-04-28 11:03:16', '2025-05-20 06:44:34', 4),
(9, 'test qwe', 'Vestibulum dignissim blandit augue ac elementum.<br><br><figure data-trix-attachment=\"{&quot;contentType&quot;:&quot;image/jpeg&quot;,&quot;filename&quot;:&quot;kdb_top.jpeg&quot;,&quot;filesize&quot;:54675,&quot;height&quot;:348,&quot;href&quot;:&quot;http://localhost/mykdb/uploads/img_680f9abc0960a7.16651368.jpeg&quot;,&quot;url&quot;:&quot;http://localhost/mykdb/uploads/img_680f9abc0960a7.16651368.jpeg&quot;,&quot;width&quot;:1102}\" data-trix-content-type=\"image/jpeg\" data-trix-attributes=\"{&quot;presentation&quot;:&quot;gallery&quot;}\" class=\"attachment attachment--preview attachment--jpeg\"><a href=\"http://localhost/mykdb/uploads/img_680f9abc0960a7.16651368.jpeg\"><img src=\"http://localhost/mykdb/uploads/img_680f9abc0960a7.16651368.jpeg\" width=\"1102\" height=\"348\"></a></figure>\r\nIn vitae ullamcorper purus. Duis ornare diam quis tortor vulputate, pellentesque pharetra nunc aliquam. Duis vitae enim in lorem tristique rhoncus. Maecenas ultrices condimentum ipsum, nec tristique enim accumsan volutpat. Cras placerat maximus mauris in interdum. Maecenas finibus ex at gravida auctor.', 1, 2, 'approved', '2025-04-28 15:12:06', '2025-05-20 08:57:58', 1),
(11, 'test 2904_01', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi nisi neque, cursus at pulvinar at, dapibus sed nibh. Nullam bibendum nisi sed mi vulputate, vitae viverra mauris fringilla. Fusce vitae laoreet neque. Fusce vitae purus et diam bibendum tristique. Nunc sit amet scelerisque dui. Pellentesque tempor enim ac ullamcorper mollis. Praesent placerat convallis ante, non viverra libero lacinia quis. Aliquam non eros sit amet leo dictum vestibulum. Integer felis turpis, venenatis nec gravida sodales, laoreet in neque. Nunc eu accumsan diam. Sed dolor ex, ultricies nec purus et, vehicula tincidunt metus. Nulla quis leo vitae nisl posuere faucibus. Curabitur mi ipsum, tempor sollicitudin orci mattis, posuere consequat purus.', 1, 2, 'pending', '2025-04-29 08:52:13', '2025-05-02 10:28:48', 0),
(12, 'test 1234567', 'L111122121orem ipsum dolor sit amet, consectetur adipiscing elit. Morbi nisi neque, cursus at pulvinar at, dapibus sed nibh. Nullam bibendum nisi sed mi vulputate, vitae viverra mauris fringilla.<br><figure data-trix-attachment=\"{&quot;contentType&quot;:&quot;image/jpeg&quot;,&quot;filename&quot;:&quot;kdb_top.jpeg&quot;,&quot;filesize&quot;:54675,&quot;height&quot;:348,&quot;href&quot;:&quot;http://localhost/mykdb/uploads/img_6810a670e29597.06364992.jpeg&quot;,&quot;url&quot;:&quot;http://localhost/mykdb/uploads/img_6810a670e29597.06364992.jpeg&quot;,&quot;width&quot;:1102}\" data-trix-content-type=\"image/jpeg\" data-trix-attributes=\"{&quot;presentation&quot;:&quot;gallery&quot;}\" class=\"attachment attachment--preview attachment--jpeg\"><a href=\"http://localhost/mykdb/uploads/img_6810a670e29597.06364992.jpeg\"><img src=\"http://localhost/mykdb/uploads/img_6810a670e29597.06364992.jpeg\" width=\"1102\" height=\"348\"></a></figure>Suspendisse non cursus lacus, interdum gravida justo. Maecenas cursus tincidunt arcu. Aliquam quis tellus massa. Nullam maximus luctus lectus vel efficitur. Nulla tempor, purus ac pulvinar ornare, libero dolor convallis sapien, quis dictum lacus arcu lacinia ex. Donec auctor odio imperdiet, semper purus in, posuere leo.&nbsp;', 1, 2, 'pending', '2025-04-29 10:16:03', '2025-04-30 11:24:32', 0),
(13, 'What is a Knowledge Base System?', 'A knowledge base is a centralized repository of structured information, often used by businesses to provide answers and solutions to common questions and issues. It can be either internal for employees or external for customers, and it can include various resources like FAQs, troubleshooting guides, and product documentation.&nbsp;<br><br>Here\'s a more detailed explanation:<br><br><strong><em>Purpose:<br></em></strong><strong><br>Self-service customer support:<br></strong>Knowledge bases enable customers to find answers and resolve problems independently, reducing the burden on support teams. <br><br><strong>Employee onboarding and training:<br></strong>Internal knowledge bases provide employees with quick access to information, improving efficiency and productivity. <br><br><strong>Documentation and guidance:<br></strong>Knowledge bases can store and organize various types of information, like internal policies, procedures, and best practices.&nbsp;<br><strong><em>Key Characteristics:<br><br></em></strong><strong>Centralized:<br></strong>All information is stored in one place, making it easy to access and share. <br><br><strong>Structured:<br></strong>Information is organized in a way that is easy to understand and navigate. <br><br><strong>Self-service:<br></strong>Users can find information and answers without needing to contact a human representative. <br><br><strong>Up-to-date:<br></strong>Content should be regularly reviewed and updated to ensure accuracy and relevance.&nbsp;<br><br><br><strong><em>Examples of Content:<br></em></strong><strong>FAQs:</strong> Frequently asked questions about products, services, or processes.&nbsp;<br><strong>Troubleshooting guides:</strong> Step-by-step instructions for resolving common issues.&nbsp;<br><strong>Product documentation:</strong> Detailed information about products, including specifications, features, and instructions.&nbsp;<br><strong>Internal policies and procedures:</strong> Guidelines for employees on various aspects of the company.&nbsp;<br><strong>Tutorials and guides:</strong> Step-by-step instructions on how to use specific tools or software.&nbsp;<br><br><br><strong><em>Benefits:<br></em></strong><strong>Reduced support costs:<br></strong>By providing self-service options, knowledge bases can significantly reduce the number of support requests handled by human agents. <br><br><strong>Improved customer satisfaction:<br></strong>Customers can find answers quickly and efficiently, leading to increased satisfaction.&nbsp;<br><br><strong>Increased employee productivity:<br></strong>Employees can access information quickly, reducing the need for time-consuming searches and questions.&nbsp;<br><br><strong>Improved knowledge sharing:<br></strong>Knowledge bases facilitate the sharing of knowledge across the organization, fostering collaboration and innovation.&nbsp;<br><br><strong>Enhanced brand image:<br></strong>By providing a comprehensive and helpful resource, knowledge bases can improve the overall customer experience and enhance the brand\'s reputation.&nbsp;', 3, 2, 'approved', '2025-05-17 20:02:33', '2025-05-20 08:21:55', 10),
(14, 'Why You Need a Knowledge Base', '<br>Customer service departments exist to improve the customer experience. When users have a question or issue, they speak to your customer service team to get the answers they need.&nbsp;<br><br>This type of customer service is reactive and can become time-consuming because you have to have well-trained support staff operating these systems and interacting with customers to ensure a good experience. For example, if you work at a photo-sharing startup, you might get the same question about how to upload a photo dozens of times a day.<br><br>This reactive repetition is time-consuming, stressful, and isn’t scalable. Enter the knowledge base. Let’s go over some key reasons why knowledge bases are important.<br><br><strong>1. It\'s helpful for your reps.</strong>&nbsp;<br><br>Whether answering questions over the phone, email, or live chat, your support teams can only handle so much at a time.&nbsp;<br><br>By providing a helpful knowledge base, your customers can get the answers they need on their own. This frees up your support reps to handle more important issues and allows your customers to feel accomplished that they found a solution all by themselves. After all, <a href=\"https://clouddamcdnprodep.azureedge.net/gdc/gdcPiLLQw/original?ocid=mkto_eml_EM582302A1LA1\">consumers expect your business to help them find their own solutions</a> with self-service tools.&nbsp;<br><br><strong>2. It\'s faster for your customers.<br></strong><br>People no longer have the patience to flip through dusty encyclopedias in public libraries. They want answers, and they want them now. If a customer or prospect has a specific question about your company -- whether it be \"What is artificial intelligence?\" or \"How do I apply for a job?\" -- they\'ll assume they can simply search that question or similar keywords on your website and find the answers they’re looking for.&nbsp;<br><br><strong>3. It can include all types of helpful content.&nbsp;<br></strong><br>A knowledge base doesn’t simply have to be a reservoir of help center articles. It can also include functions like a frequently-asked questions (FAQ) section, a user forum, articles, white papers, how-to articles, video tutorials, case studies, and dictionaries or glossaries — anything that helps customers understand and use your products or services.<br><br>But you could also set up an IT or internal company knowledge base to help your employers learn how your organization operates, discover benefits and HR information, or get answers to common technical questions.<br><br><strong>4. It\'s easy to build.<br></strong><br>If you can\'t provide immediate answers to your customers, they will turn to a competitor who can. It can be tough to create and manage a knowledge base. However, it can start off easily -- think about the questions you\'ve had customers ask you in the past and, maybe, the questions you\'ve searched for yourself. You can analyze common tickets to determine which issues crop up most frequently that you need to address first. Slowly, your team can build an informative knowledge base that can be a constant resource. Let’s discuss how.<br><br><br>', 2, 2, 'approved', '2025-05-17 20:13:57', '2025-05-20 08:19:47', 129);

-- --------------------------------------------------------

--
-- Table structure for table `article_comments`
--

CREATE TABLE `article_comments` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `article_comments`
--

INSERT INTO `article_comments` (`id`, `article_id`, `user_id`, `content`, `status`, `created_at`) VALUES
(1, 14, 2, 'UX design skills can grow pay — Build in-demand skills for a new career—now with AI training from Google UX designers. Gain the skills, confidence, and experience needed to launch your career in UX Design.', 'approved', '2025-05-19 13:42:58'),
(2, 13, 7, 'Acesta este primul comentariu la un articol....', 'approved', '2025-05-20 07:44:10'),
(3, 14, 7, 'Alt comentariu la acest articol ... bla bla bla....', 'approved', '2025-05-20 07:51:26'),
(4, 4, 7, 'Comentariu la acest articol...', 'rejected', '2025-05-20 08:40:00');

-- --------------------------------------------------------

--
-- Table structure for table `article_likes`
--

CREATE TABLE `article_likes` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `vote_type` enum('like','dislike') NOT NULL DEFAULT 'like'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `article_likes`
--

INSERT INTO `article_likes` (`id`, `article_id`, `user_id`, `created_at`, `vote_type`) VALUES
(1, 9, 2, '2025-05-19 16:22:58', 'like'),
(2, 5, 2, '2025-05-19 16:32:47', 'dislike'),
(3, 1, 2, '2025-05-19 16:35:31', 'like'),
(4, 2, 2, '2025-05-19 16:35:38', 'like'),
(5, 14, 2, '2025-05-19 16:40:38', 'like'),
(6, 13, 2, '2025-05-19 17:52:49', 'like'),
(7, 4, 2, '2025-05-19 17:55:26', 'like'),
(8, 3, 2, '2025-05-19 18:00:07', 'like'),
(9, 14, 5, '2025-05-20 06:40:30', 'like'),
(10, 13, 6, '2025-05-20 06:44:01', 'like'),
(11, 14, 7, '2025-05-20 07:32:36', 'dislike'),
(12, 3, 7, '2025-05-20 08:18:28', 'like'),
(13, 2, 7, '2025-05-20 08:38:50', 'like'),
(14, 1, 7, '2025-05-20 08:39:00', 'dislike');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Installation and Configuration', 'Articles related to software installation and configuration'),
(2, 'Tutorials and HOWTOs', 'Guides and how tos '),
(3, 'User Guides', 'Contains all the user guides');

-- --------------------------------------------------------

--
-- Table structure for table `operations`
--

CREATE TABLE `operations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `operations`
--

INSERT INTO `operations` (`id`, `name`, `description`) VALUES
(1, 'search', 'Search info in published Articles'),
(2, 'view_article', 'View all published Articles'),
(3, 'create_article', 'Create an Article and send it for approval'),
(4, 'edit_own_article', 'Edit own Articles'),
(5, 'edit_article', 'Edit an Article'),
(6, 'publish_article', 'Make the Articles public'),
(7, 'disable_article', 'Set an Article as disabled'),
(8, 'enable_article', 'Make an Article visible'),
(9, 'approve_article', 'Approve submitted articles'),
(10, 'delete_article', 'Remove an Article completely from DB'),
(11, 'export_article', 'Export an Article in a specified format'),
(12, 'add_comment', 'Add a Comment to an Article'),
(13, 'edit_comment', 'Modify a Comment on an Article'),
(14, 'delete_comment', 'Remove a Comment on an Article'),
(15, 'approve_comment', 'Approve a Comment to an Article'),
(16, 'add_category', 'Add a Category'),
(17, 'edit_category', 'Edit a Category'),
(18, 'edit_user', 'Modify info for an User'),
(19, 'disable_user', 'Disable an User'),
(20, 'enable_user', 'Activate an User'),
(21, 'delete_user', 'Remove an User'),
(22, 'modify_user', 'Modify an User'),
(23, 'modify_own_user', 'Modify the info for own User'),
(24, 'view_own_activity', 'View Activity of own User'),
(25, 'view_all_activity', 'View Activity of all Users'),
(26, 'backup_data', 'Make Data Backup'),
(27, 'edit_acl', 'Edit the Access for Operations'),
(28, 'register', 'user registration'),
(29, 'approve_user', 'approve user in pending state'),
(30, 'reject_comment', 'Reject a Comment');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `label` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `label`) VALUES
(1, 'admin', 'Administrator'),
(2, 'contributor', 'Contributor'),
(3, 'editor', 'Editor'),
(4, 'moderator', 'Moderator'),
(5, 'superadmin', 'MasterAdmin'),
(6, 'guest', 'Guest');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `operation_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `operation_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(1, 12),
(1, 13),
(1, 14),
(1, 15),
(1, 16),
(1, 17),
(1, 18),
(1, 19),
(1, 20),
(1, 21),
(1, 22),
(1, 23),
(1, 24),
(1, 25),
(1, 27),
(1, 29),
(1, 30),
(2, 2),
(2, 3),
(2, 4),
(2, 5),
(2, 23),
(2, 24),
(3, 1),
(3, 2),
(3, 3),
(3, 4),
(3, 5),
(3, 9),
(3, 12),
(3, 15),
(3, 23),
(3, 24),
(4, 1),
(4, 2),
(4, 3),
(4, 4),
(4, 6),
(4, 7),
(4, 8),
(4, 9),
(4, 12),
(4, 13),
(4, 14),
(4, 15),
(4, 19),
(4, 20),
(4, 23),
(4, 24),
(4, 29),
(4, 30),
(5, 1),
(5, 2),
(5, 3),
(5, 4),
(5, 5),
(5, 6),
(5, 7),
(5, 8),
(5, 9),
(5, 10),
(5, 11),
(5, 12),
(5, 13),
(5, 14),
(5, 15),
(5, 16),
(5, 17),
(5, 18),
(5, 19),
(5, 20),
(5, 21),
(5, 22),
(5, 23),
(5, 24),
(5, 25),
(5, 26),
(5, 27),
(5, 29),
(5, 30),
(6, 1),
(6, 2),
(6, 28);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','active','disabled') DEFAULT 'pending',
  `profile_picture` varchar(255) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `first_name`, `last_name`, `email`, `password`, `created_at`, `status`, `profile_picture`, `role_id`) VALUES
(2, 'admin', 'Marius', 'Dragulescu', 'marius.dragulescu@yahoo.com', '$2y$10$7Dd39yJZg4aXeJhG1Cvy8ejv31Dt0yIcKxWFyt8bc1FQa6okZRVhS', '2025-04-23 10:45:27', 'active', 'user_2_6825c636195d8.jpeg', 1),
(3, 'user1', 'Adrian', 'Ionescu', '', '$2y$10$N68o/sEk5L4WoPpquepyn.XI.BAjb56i7ProIs4/Q01vnb5PaOlQ6', '2025-04-23 11:22:37', 'disabled', NULL, 2),
(4, 'user2', 'Marcel', 'Popescu', 'marcel.popescu@example.co', '$2y$10$qj4MqWqGuGMWv9yTL51IPuK57ar4c3/QQPN8Ws5EBoaGl8mIzhcBq', '2025-04-23 11:23:02', 'active', 'user_4_6821e34cbb8e2.jpeg', 4),
(5, 'mihai', 'Mihai', 'Dragulescu', 'mihaid2002@yahoo.com', '$2y$10$WdXQXrY/Lmnu526H1i75Xu7KXlIK8b5MKCbfTHkfrWy/Xatey57Pu', '2025-05-01 12:38:22', 'active', 'mihai.png', 2),
(6, 'corny', 'Cornelia', 'Buse', '', '$2y$10$JKXBLX2EUgS/KjC2wgY2Eul8RqBMdZMuD3OrNbMQ75AKKLHAQcA46', '2025-05-05 13:33:26', 'active', 'user_6_682c24869ddbe.jpeg', 2),
(7, 'marius', 'Marius', 'Dragulescu', 'mariusdragulescu@gmail.com', '$2y$10$422zXDULi727g6bu/.4sDu4ZCA1UvbF4A6p7sSk9RW7MkNH/90pf2', '2025-05-15 12:58:28', 'active', 'user_7_6825f530152f3.jpeg', 5);

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

CREATE TABLE `user_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_settings`
--

INSERT INTO `user_settings` (`id`, `user_id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 2, 'language', 'en', '2025-05-20 20:31:36'),
(2, 2, 'theme', 'light', '2025-05-14 09:41:21'),
(3, 5, 'theme', 'light', '2025-05-13 17:16:26'),
(4, 5, 'language', 'en', '2025-05-09 08:59:04'),
(5, 7, 'theme', 'light', '2025-05-20 08:44:15'),
(6, 7, 'language', 'ro', '2025-05-20 08:44:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_log_user` (`user_id`);

--
-- Indexes for table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `article_comments`
--
ALTER TABLE `article_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `article_id` (`article_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `article_likes`
--
ALTER TABLE `article_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_vote` (`article_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `operations`
--
ALTER TABLE `operations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`operation_id`),
  ADD KEY `operation_id` (`operation_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fk_users_role_id` (`role_id`);

--
-- Indexes for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_setting` (`user_id`,`setting_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=307;

--
-- AUTO_INCREMENT for table `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `article_comments`
--
ALTER TABLE `article_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `article_likes`
--
ALTER TABLE `article_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `operations`
--
ALTER TABLE `operations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `articles_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `article_comments`
--
ALTER TABLE `article_comments`
  ADD CONSTRAINT `article_comments_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `article_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `article_likes`
--
ALTER TABLE `article_likes`
  ADD CONSTRAINT `article_likes_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `article_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`operation_id`) REFERENCES `operations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Constraints for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;