-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 14, 2026 at 08:09 AM
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
-- Database: `neurosatva`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('active','deactivated') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password_hash`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Neurosatva Admin', 'admin@neurosatva.local', '$2y$10$3SjkDQaOUe7ADveSEHnP..ObQsZAtFY3W.p5f7Quqeh3daijlGa.2', 'active', '2026-07-02 04:47:37', '2026-07-02 04:50:18');

-- --------------------------------------------------------

--
-- Table structure for table `admin_actions`
--

CREATE TABLE `admin_actions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admin_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `entity_type` varchar(80) NOT NULL,
  `entity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`metadata`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_actions`
--

INSERT INTO `admin_actions` (`id`, `admin_id`, `action`, `entity_type`, `entity_id`, `metadata`, `ip_address`, `created_at`) VALUES
(1, 1, 'created_tutor', 'tutor', 1, '[]', '::1', '2026-07-02 04:50:29'),
(2, 1, 'approved_tutor_registration', 'tutor_registration_request', 1, '{\"tutor_id\":2}', '::1', '2026-07-02 06:57:40'),
(3, 1, 'deleted_tutor', 'tutor', 1, '[]', '::1', '2026-07-02 06:57:46'),
(4, 1, 'approved_tutor_registration', 'tutor_registration_request', 2, '{\"tutor_id\":3}', '::1', '2026-07-02 10:40:16'),
(5, 1, 'approved_tutor_registration', 'tutor_registration_request', 3, '{\"tutor_id\":4}', '::1', '2026-07-02 12:13:11'),
(6, 1, 'approved_tutor_registration', 'tutor_registration_request', 4, '{\"tutor_id\":5,\"welcome_email_sent\":false}', '::1', '2026-07-03 05:47:42'),
(7, 1, 'updated_tutor', 'tutor', 5, '{\"status\":\"active\"}', '::1', '2026-07-11 09:52:36');

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(160) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(80) NOT NULL,
  `entity_type` varchar(80) DEFAULT NULL,
  `entity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_notifications`
--

INSERT INTO `admin_notifications` (`id`, `title`, `message`, `type`, `entity_type`, `entity_id`, `link`, `read_at`, `created_at`) VALUES
(1, 'New tutor registration', 'swapna registered and is waiting for approval.', 'tutor_registration', 'tutor_registration_request', 2, '/admin/registration-requests?status=Pending', '2026-07-02 10:49:24', '2026-07-02 10:28:05'),
(2, 'New tutor registration', 'aman registered and is waiting for approval.', 'tutor_registration', 'tutor_registration_request', 3, '/admin/registration-requests?status=Pending', '2026-07-02 12:13:11', '2026-07-02 12:05:04'),
(3, 'New tutor registration', 'Kanike Yuvaraj registered and is waiting for approval.', 'tutor_registration', 'tutor_registration_request', 4, '/admin/registration-requests?status=Pending', '2026-07-03 05:47:42', '2026-07-03 05:41:53');

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role` enum('admin','tutor') NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `email` varchar(190) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`id`, `role`, `user_id`, `email`, `ip_address`, `user_agent`, `success`, `created_at`) VALUES
(1, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-02 04:47:46'),
(2, 'tutor', 1, 'rsplyuvaraj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 0, '2026-07-02 04:50:47'),
(3, 'tutor', 1, 'rsplyuvaraj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-02 04:50:52'),
(4, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-02 06:27:03'),
(5, 'tutor', 1, 'rsplyuvaraj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-02 06:38:49'),
(6, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-02 06:52:05'),
(7, 'tutor', NULL, 'saitarun45@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 0, '2026-07-02 06:56:45'),
(8, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-02 06:57:19'),
(9, 'tutor', 2, 'saitarun45@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-02 06:57:59'),
(10, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-02 08:42:57'),
(11, 'tutor', NULL, 'sai_neuro@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 0, '2026-07-02 08:43:32'),
(12, 'tutor', NULL, 'sai_neuro@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 0, '2026-07-02 08:43:38'),
(13, 'tutor', NULL, 'sai_neuro@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 0, '2026-07-02 08:43:46'),
(14, 'tutor', NULL, 'sai_neuro@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 0, '2026-07-02 08:43:53'),
(15, 'tutor', 2, 'saitarun45@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-02 08:44:01'),
(16, 'tutor', NULL, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 0, '2026-07-02 10:08:17'),
(17, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 0, '2026-07-02 10:08:17'),
(18, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-02 10:08:30'),
(19, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-02 10:25:47'),
(20, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-02 10:39:49'),
(21, 'tutor', NULL, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 0, '2026-07-02 10:43:55'),
(22, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 0, '2026-07-02 10:43:55'),
(23, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-02 10:44:04'),
(24, 'tutor', 3, 'swapna07@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-02 10:44:21'),
(25, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-02 10:47:01'),
(26, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-02 11:16:05'),
(27, 'tutor', NULL, 'aman567@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 0, '2026-07-02 12:05:24'),
(28, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-02 12:05:43'),
(29, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-02 12:07:31'),
(30, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-02 12:13:00'),
(31, 'tutor', 4, 'aman567@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 0, '2026-07-02 12:13:27'),
(32, 'tutor', 4, 'aman567@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-02 12:13:37'),
(33, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-02 12:23:04'),
(34, 'tutor', NULL, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 0, '2026-07-02 12:24:47'),
(35, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 0, '2026-07-02 12:24:47'),
(36, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-02 12:27:36'),
(37, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-03 04:44:40'),
(38, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-03 05:16:01'),
(39, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-03 05:42:26'),
(40, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-03 08:46:51'),
(41, 'tutor', 5, 'ns-tut-000005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 0, '2026-07-03 08:47:17'),
(42, 'tutor', 5, 'ns-tut-000005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 0, '2026-07-03 08:47:35'),
(43, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-03 08:50:39'),
(44, 'tutor', 5, 'ns-tut-000005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 0, '2026-07-03 08:52:50'),
(45, 'tutor', 5, 'ns-tut-000005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-03 09:16:52'),
(46, 'tutor', NULL, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 0, '2026-07-03 10:42:01'),
(47, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 0, '2026-07-03 10:42:01'),
(48, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-03 10:42:11'),
(49, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-07 08:57:30'),
(50, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-07 09:35:18'),
(51, 'tutor', 5, 'rsplyuvaraj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-07 09:35:49'),
(52, 'tutor', 5, 'ns-tut-000005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 0, '2026-07-11 09:50:53'),
(53, 'tutor', 5, 'ns-tut-000005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 0, '2026-07-11 09:51:07'),
(54, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-11 09:51:16'),
(55, 'tutor', 4, 'ns-tut-000004', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 0, '2026-07-11 09:51:41'),
(56, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-11 09:52:11'),
(57, 'tutor', 5, 'ns-tut-000005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-11 09:52:49'),
(58, 'tutor', 5, 'ns-tut-000005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-11 11:09:14'),
(59, 'admin', 1, 'admin@neurosatva.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-07-11 11:10:19');

-- --------------------------------------------------------

--
-- Table structure for table `tutors`
--

CREATE TABLE `tutors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `personal_email` varchar(190) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('active','deactivated') NOT NULL DEFAULT 'active',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `school_name` varchar(160) DEFAULT NULL,
  `gender` varchar(40) DEFAULT NULL,
  `official_gmail` varchar(190) DEFAULT NULL,
  `gmail_verified` tinyint(1) NOT NULL DEFAULT 0,
  `gmail_verified_at` timestamp NULL DEFAULT NULL,
  `gmail_verification_token` varchar(128) DEFAULT NULL,
  `gmail_otp_hash` varchar(255) DEFAULT NULL,
  `gmail_otp_expires_at` timestamp NULL DEFAULT NULL,
  `gmail_otp_attempts` int(11) NOT NULL DEFAULT 0,
  `gmail_updated_at` timestamp NULL DEFAULT NULL,
  `first_login_completed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tutors`
--

INSERT INTO `tutors` (`id`, `name`, `email`, `personal_email`, `phone`, `password_hash`, `status`, `created_by`, `school_name`, `gender`, `official_gmail`, `gmail_verified`, `gmail_verified_at`, `gmail_verification_token`, `gmail_otp_hash`, `gmail_otp_expires_at`, `gmail_otp_attempts`, `gmail_updated_at`, `first_login_completed`, `created_at`, `updated_at`) VALUES
(2, 'Sai Tarun', 'sai_neuro@gmail.com', 'sai_neuro@gmail.com', NULL, '$2y$10$N34Q58eB6yO0th12zynTpuvWM7OQrGt40KC6dmcfT10bcLnq200Ye', 'active', 1, 'Oxford public school', 'Male', 'sai_neuro@gmail.com', 1, '2026-07-02 07:14:08', NULL, NULL, NULL, 0, '2026-07-02 07:13:40', 1, '2026-07-02 06:57:40', '2026-07-03 04:53:23'),
(3, 'swapna', 'swapna_neuro@gmail.com', 'swapna07@gmail.com', '7898451485', '$2y$10$k0PCTuIiKabEuCkMhegqpOyLT0BpIgzR/EEnWiyzKnsQSgE1fyh6u', 'active', 1, 'Oxford public school', 'Female', 'swapna_neuro@gmail.com', 1, '2026-07-02 10:46:34', NULL, NULL, NULL, 0, '2026-07-02 10:45:58', 1, '2026-07-02 10:40:16', '2026-07-03 04:53:23'),
(4, 'aman', 'aman_neuro@gmail.com', 'aman567@gmail.com', '8951522952', '$2y$10$u1kYf.KdtzdKHVG/dgJBNuxFPSNVJDuGYeoWB7ISl5UvO0VudVy1G', 'active', 1, 'Oxford public school', 'Male', 'aman_neuro@gmail.com', 1, '2026-07-02 12:22:42', NULL, NULL, NULL, 0, '2026-07-02 12:22:23', 1, '2026-07-02 12:13:11', '2026-07-03 04:53:23'),
(5, 'Kanike Yuvaraj', 'yuva_neuro@gmail.com', 'rsplyuvaraj@gmail.com', '8945456454', '$2y$10$8dLQGDoCpfpCV3bZHczec.UinLWx2lTVzUwqBy11ERUlIT27CfglG', 'active', 1, NULL, NULL, 'yuva_neuro@gmail.com', 1, '2026-07-07 09:55:01', NULL, NULL, NULL, 0, '2026-07-07 09:36:17', 1, '2026-07-03 05:47:41', '2026-07-11 09:52:36');

-- --------------------------------------------------------

--
-- Table structure for table `tutor_registration_requests`
--

CREATE TABLE `tutor_registration_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(20) NOT NULL DEFAULT '',
  `school_name` varchar(160) DEFAULT NULL,
  `gender` varchar(40) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `admin_remarks` text DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tutor_registration_requests`
--

INSERT INTO `tutor_registration_requests` (`id`, `full_name`, `email`, `phone`, `school_name`, `gender`, `password_hash`, `status`, `admin_remarks`, `approved_by`, `approved_at`, `created_at`, `updated_at`) VALUES
(1, 'Sai Tarun', 'saitarun45@gmail.com', '', 'Oxford public school', 'Male', '$2y$10$N34Q58eB6yO0th12zynTpuvWM7OQrGt40KC6dmcfT10bcLnq200Ye', 'Approved', NULL, 1, '2026-07-02 06:57:40', '2026-07-02 06:56:30', '2026-07-02 06:57:40'),
(2, 'swapna', 'swapna07@gmail.com', '7898451485', 'Oxford public school', 'Female', '$2y$10$k0PCTuIiKabEuCkMhegqpOyLT0BpIgzR/EEnWiyzKnsQSgE1fyh6u', 'Approved', NULL, 1, '2026-07-02 10:40:16', '2026-07-02 10:28:05', '2026-07-02 10:40:16'),
(3, 'aman', 'aman567@gmail.com', '8951522952', 'Oxford public school', 'Male', '$2y$10$u1kYf.KdtzdKHVG/dgJBNuxFPSNVJDuGYeoWB7ISl5UvO0VudVy1G', 'Approved', NULL, 1, '2026-07-02 12:13:11', '2026-07-02 12:05:04', '2026-07-02 12:13:11'),
(4, 'Kanike Yuvaraj', 'rsplyuvaraj@gmail.com', '8945456454', NULL, NULL, '$2y$10$Ma56OkY0JGHeg2ST8IMI0.RoInI0Xnk.kUo3ZLOBe3dLhAej8uSIi', 'Approved', NULL, 1, '2026-07-03 05:47:41', '2026-07-03 05:41:53', '2026-07-03 05:47:41');

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--

CREATE TABLE `videos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tutor_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(180) NOT NULL,
  `email_subject` varchar(180) DEFAULT NULL,
  `source_email` varchar(190) DEFAULT NULL,
  `storage_path` text DEFAULT NULL,
  `status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `admin_remarks` text DEFAULT NULL,
  `received_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `videos`
--

INSERT INTO `videos` (`id`, `tutor_id`, `title`, `email_subject`, `source_email`, `storage_path`, `status`, `admin_remarks`, `received_at`, `verified_at`, `created_at`, `updated_at`) VALUES
(1, 5, 'Session !', 'Tutor upload link submission', 'yuva_neuro@gmail.com', 'https://drive.google.com/drive/u/0/home', 'pending', 'Its my first video', '2026-07-11 07:40:05', NULL, '2026-07-11 11:10:05', '2026-07-11 11:10:05');

-- --------------------------------------------------------

--
-- Table structure for table `video_verifications`
--

CREATE TABLE `video_verifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `video_id` bigint(20) UNSIGNED NOT NULL,
  `admin_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('pending','verified','rejected') NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `admin_actions`
--
ALTER TABLE `admin_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_actions_admin` (`admin_id`);

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_notifications_read_at` (`read_at`),
  ADD KEY `idx_admin_notifications_entity` (`entity_type`,`entity_id`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_login_logs_email` (`email`);

--
-- Indexes for table `tutors`
--
ALTER TABLE `tutors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_tutors_created_by` (`created_by`),
  ADD KEY `idx_tutors_status` (`status`),
  ADD KEY `idx_tutors_official_gmail` (`official_gmail`);

--
-- Indexes for table `tutor_registration_requests`
--
ALTER TABLE `tutor_registration_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tutor_requests_admin` (`approved_by`),
  ADD KEY `idx_tutor_requests_status` (`status`),
  ADD KEY `idx_tutor_requests_email` (`email`);

--
-- Indexes for table `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_videos_tutor_status` (`tutor_id`,`status`),
  ADD KEY `idx_videos_received_at` (`received_at`);

--
-- Indexes for table `video_verifications`
--
ALTER TABLE `video_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_video_verifications_video` (`video_id`),
  ADD KEY `fk_video_verifications_admin` (`admin_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_actions`
--
ALTER TABLE `admin_actions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `tutors`
--
ALTER TABLE `tutors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tutor_registration_requests`
--
ALTER TABLE `tutor_registration_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `videos`
--
ALTER TABLE `videos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `video_verifications`
--
ALTER TABLE `video_verifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_actions`
--
ALTER TABLE `admin_actions`
  ADD CONSTRAINT `fk_admin_actions_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tutors`
--
ALTER TABLE `tutors`
  ADD CONSTRAINT `fk_tutors_created_by` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tutor_registration_requests`
--
ALTER TABLE `tutor_registration_requests`
  ADD CONSTRAINT `fk_tutor_requests_admin` FOREIGN KEY (`approved_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `videos`
--
ALTER TABLE `videos`
  ADD CONSTRAINT `fk_videos_tutor` FOREIGN KEY (`tutor_id`) REFERENCES `tutors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `video_verifications`
--
ALTER TABLE `video_verifications`
  ADD CONSTRAINT `fk_video_verifications_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_video_verifications_video` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
