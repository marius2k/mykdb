-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 14, 2025 at 03:12 PM
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
(141, 2, 'logout', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '\"User logged out\"', '2025-05-14 12:06:21');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `articles`
--

INSERT INTO `articles` (`id`, `title`, `content`, `category_id`, `user_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Test 1 for Installation and Configuration', 'This is the first test article for the Installation and Configuration category', 1, 2, 'approved', '2025-04-23 11:06:19', '2025-04-23 13:32:35'),
(2, 'How To create a knowledge article', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce pharetra sagittis porta. Sed nec mauris nulla. Sed tristique iaculis diam, ut ultrices sem fringilla nec. Nunc sollicitudin, turpis ac tincidunt porta, lectus tortor luctus sapien, quis dignissim diam orci pharetra urna. Proin sagittis nunc ut urna euismod, quis vehicula risus ullamcorper. Fusce nibh nibh, ultricies eu ante eget, dictum laoreet ex. Integer diam ipsum, egestas vel risus tincidunt, convallis consectetur elit. Etiam auctor erat quis sem dignissim, at malesuada est laoreet. Donec id porta tortor, non auctor metus. Pellentesque bibendum mollis risus. Maecenas cursus ipsum urna, at vestibulum turpis ornare sit amet. Nunc malesuada, dolor non tincidunt laoreet, erat risus faucibus ante, eu viverra elit felis at leo. Maecenas tincidunt mattis diam. first', 2, 3, 'approved', '2025-04-23 13:34:05', '2025-04-23 21:00:26'),
(3, 'How to Reset Your Password', 'If you forgot your password, follow these steps to reset it. Steps to Reset Password:\r\n1. Go to the Login Page.\r\n2. Click \"Forgot Password?\".\r\n3. Enter your registered email address.\r\n4. Check your inbox for a reset link (valid for 24 hours).\r\n5.Click the link and set a new password.', 2, 4, 'approved', '2025-04-23 13:38:49', '2025-04-23 20:20:10'),
(4, 'Articol de test cu upload de imagine', '<strong>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi nisi neque, cursus at pulvinar at, dapibus sed nibh.</strong> <br><br><em>Nullam bibendum nisi sed mi vulputate, vitae viverra mauris fringilla.</em> Fusce vitae laoreet neque. Fusce vitae purus et diam bibendum tristique. Nunc sit amet scelerisque dui. Pellentesque tempor enim ac ullamcorper mollis. <br><a href=\"uploads/img_680f527d2b5cb2.20153610.jpeg\">kdb_top.jpeg 53.39 KB</a><em><br></em>Praesent placerat convallis ante, non viverra libero lacinia quis. Aliquam non eros sit amet leo dictum vestibulum. Integer felis turpis, venenatis nec gravida sodales, laoreet in neque. Nunc eu accumsan diam. Sed dolor ex, ultricies nec purus et, vehicula tincidunt metus. Nulla quis leo vitae nisl posuere faucibus. Curabitur mi ipsum, tempor sollicitudin orci mattis, posuere consequat purus.<br><br>Suspendisse non cursus lacus, interdum gravida justo. Maecenas cursus tincidunt arcu. Aliquam quis tellus massa. Nullam maximus luctus lectus vel efficitur. Nulla tempor, purus ac pulvinar ornare, libero dolor convallis sapien, quis dictum lacus arcu lacinia ex. Donec auctor odio imperdiet, semper purus in, posuere leo. Mauris in dictum massa. Aliquam urna risus, lobortis non urna nec, bibendum egestas urna. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Nunc non dui ut tortor elementum mattis. Mauris ut eleifend nibh, non laoreet neque. Pellentesque elementum laoreet arcu ut pellentesque. Morbi egestas urna et consequat consequat.<br><br><br>', 2, 2, 'approved', '2025-04-28 10:04:15', '2025-04-28 10:50:59'),
(5, 'test cu imagine', 'Vestibulum dignissim blandit augue ac elementum. Maecenas luctus, ligula a tempus varius, leo dui laoreet purus, auctor tempor libero lorem vitae nibh. Interdum et malesuada fames ac ante ipsum primis in faucibus. Praesent rhoncus turpis in urna scelerisque congue. <br><a href=\"http://localhost/mykdb/uploads/img_680f606e54a986.34743773.png\"></a><br><br>Phasellus finibus vestibulum eros eget aliquet. Duis euismod viverra ex. Nunc sollicitudin erat id leo efficitur, et aliquam felis dignissim. Aenean fringilla viverra mi, a tincidunt ligula ornare eu. Sed nulla nibh, malesuada a accumsan vitae, tincidunt vel ipsum. Nulla facilisi. In vitae ullamcorper purus. Duis ornare diam quis tortor vulputate, pellentesque pharetra nunc aliquam. Duis vitae enim in lorem tristique rhoncus. Maecenas ultrices condimentum ipsum, nec tristique enim accumsan volutpat. Cras placerat maximus mauris in interdum. Maecenas finibus ex at gravida auctor.', 1, 2, 'approved', '2025-04-28 11:03:16', '2025-04-29 10:12:24'),
(9, 'test qwe', 'Vestibulum dignissim blandit augue ac elementum.<br><br><figure data-trix-attachment=\"{&quot;contentType&quot;:&quot;image/jpeg&quot;,&quot;filename&quot;:&quot;kdb_top.jpeg&quot;,&quot;filesize&quot;:54675,&quot;height&quot;:348,&quot;href&quot;:&quot;http://localhost/mykdb/uploads/img_680f9abc0960a7.16651368.jpeg&quot;,&quot;url&quot;:&quot;http://localhost/mykdb/uploads/img_680f9abc0960a7.16651368.jpeg&quot;,&quot;width&quot;:1102}\" data-trix-content-type=\"image/jpeg\" data-trix-attributes=\"{&quot;presentation&quot;:&quot;gallery&quot;}\" class=\"attachment attachment--preview attachment--jpeg\"><a href=\"http://localhost/mykdb/uploads/img_680f9abc0960a7.16651368.jpeg\"><img src=\"http://localhost/mykdb/uploads/img_680f9abc0960a7.16651368.jpeg\" width=\"1102\" height=\"348\"></a></figure>\r\nIn vitae ullamcorper purus. Duis ornare diam quis tortor vulputate, pellentesque pharetra nunc aliquam. Duis vitae enim in lorem tristique rhoncus. Maecenas ultrices condimentum ipsum, nec tristique enim accumsan volutpat. Cras placerat maximus mauris in interdum. Maecenas finibus ex at gravida auctor.', 1, 2, 'approved', '2025-04-28 15:12:06', '2025-04-29 10:12:47'),
(11, 'test 2904_01', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi nisi neque, cursus at pulvinar at, dapibus sed nibh. Nullam bibendum nisi sed mi vulputate, vitae viverra mauris fringilla. Fusce vitae laoreet neque. Fusce vitae purus et diam bibendum tristique. Nunc sit amet scelerisque dui. Pellentesque tempor enim ac ullamcorper mollis. Praesent placerat convallis ante, non viverra libero lacinia quis. Aliquam non eros sit amet leo dictum vestibulum. Integer felis turpis, venenatis nec gravida sodales, laoreet in neque. Nunc eu accumsan diam. Sed dolor ex, ultricies nec purus et, vehicula tincidunt metus. Nulla quis leo vitae nisl posuere faucibus. Curabitur mi ipsum, tempor sollicitudin orci mattis, posuere consequat purus.', 1, 2, 'pending', '2025-04-29 08:52:13', '2025-05-02 10:28:48'),
(12, 'test 1234567', 'L111122121orem ipsum dolor sit amet, consectetur adipiscing elit. Morbi nisi neque, cursus at pulvinar at, dapibus sed nibh. Nullam bibendum nisi sed mi vulputate, vitae viverra mauris fringilla.<br><figure data-trix-attachment=\"{&quot;contentType&quot;:&quot;image/jpeg&quot;,&quot;filename&quot;:&quot;kdb_top.jpeg&quot;,&quot;filesize&quot;:54675,&quot;height&quot;:348,&quot;href&quot;:&quot;http://localhost/mykdb/uploads/img_6810a670e29597.06364992.jpeg&quot;,&quot;url&quot;:&quot;http://localhost/mykdb/uploads/img_6810a670e29597.06364992.jpeg&quot;,&quot;width&quot;:1102}\" data-trix-content-type=\"image/jpeg\" data-trix-attributes=\"{&quot;presentation&quot;:&quot;gallery&quot;}\" class=\"attachment attachment--preview attachment--jpeg\"><a href=\"http://localhost/mykdb/uploads/img_6810a670e29597.06364992.jpeg\"><img src=\"http://localhost/mykdb/uploads/img_6810a670e29597.06364992.jpeg\" width=\"1102\" height=\"348\"></a></figure>Suspendisse non cursus lacus, interdum gravida justo. Maecenas cursus tincidunt arcu. Aliquam quis tellus massa. Nullam maximus luctus lectus vel efficitur. Nulla tempor, purus ac pulvinar ornare, libero dolor convallis sapien, quis dictum lacus arcu lacinia ex. Donec auctor odio imperdiet, semper purus in, posuere leo.&nbsp;', 1, 2, 'pending', '2025-04-29 10:16:03', '2025-04-30 11:24:32');

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
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','active','disabled') DEFAULT 'pending',
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `first_name`, `last_name`, `email`, `password`, `role`, `created_at`, `status`, `profile_picture`) VALUES
(2, 'admin', 'Marius', 'Dragulescu', 'marius.dragulescu@yahoo.com', '$2y$10$7Dd39yJZg4aXeJhG1Cvy8ejv31Dt0yIcKxWFyt8bc1FQa6okZRVhS', 'admin', '2025-04-23 10:45:27', 'active', 'user_2_6821e4ccdc072.jpeg'),
(3, 'user1', 'Adrian', 'Ionescu', '', '$2y$10$N68o/sEk5L4WoPpquepyn.XI.BAjb56i7ProIs4/Q01vnb5PaOlQ6', 'user', '2025-04-23 11:22:37', 'disabled', NULL),
(4, 'user2', 'Marcel', 'Popescu', 'marcel.popescu@example.co', '$2y$10$qj4MqWqGuGMWv9yTL51IPuK57ar4c3/QQPN8Ws5EBoaGl8mIzhcBq', 'user', '2025-04-23 11:23:02', 'active', 'user_4_6821e34cbb8e2.jpeg'),
(5, 'mihai', 'Mihai', 'Dragulescu', 'mihaid2002@yahoo.com', '$2y$10$WdXQXrY/Lmnu526H1i75Xu7KXlIK8b5MKCbfTHkfrWy/Xatey57Pu', 'user', '2025-05-01 12:38:22', 'active', 'mihai.png'),
(6, 'corny', 'Cornelia', 'Buse', '', '$2y$10$JKXBLX2EUgS/KjC2wgY2Eul8RqBMdZMuD3OrNbMQ75AKKLHAQcA46', 'user', '2025-05-05 13:33:26', 'pending', NULL);

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
(1, 2, 'language', 'ro', '2025-05-14 11:22:08'),
(2, 2, 'theme', 'light', '2025-05-14 09:41:21'),
(3, 5, 'theme', 'light', '2025-05-13 17:16:26'),
(4, 5, 'language', 'en', '2025-05-09 08:59:04');

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
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;

--
-- AUTO_INCREMENT for table `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
-- Constraints for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;