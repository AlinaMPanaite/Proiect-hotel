-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 30, 2025 at 05:42 AM
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
-- Database: `hotel`
--

-- --------------------------------------------------------

--
-- Table structure for table `istoric_utilizare`
--

CREATE TABLE `istoric_utilizare` (
  `IstoricID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `DataOra` datetime NOT NULL DEFAULT current_timestamp(),
  `Operatie` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `istoric_utilizare`
--

INSERT INTO `istoric_utilizare` (`IstoricID`, `UserID`, `DataOra`, `Operatie`) VALUES
(1, 2, '2025-05-19 12:26:30', 'Înregistrare cont'),
(2, 2, '2025-05-19 12:28:32', 'Login'),
(3, 2, '2025-05-19 12:40:45', 'Login'),
(4, 2, '2025-05-19 12:46:52', 'Rezervare serviciu'),
(5, 2, '2025-05-19 13:35:30', 'Rezervare serviciu'),
(6, 2, '2025-05-19 13:35:53', 'Rezervare serviciu'),
(7, 2, '2025-05-19 13:38:42', 'Rezervare cameră'),
(8, 2, '2025-05-19 13:39:14', 'Actualizare profil'),
(9, 2, '2025-05-19 13:39:16', 'Actualizare profil'),
(10, 2, '2025-05-19 13:42:57', 'Actualizare profil'),
(11, 3, '2025-05-19 13:45:46', 'Înregistrare cont'),
(12, 3, '2025-05-19 13:46:02', 'Login'),
(13, 3, '2025-05-19 14:34:12', 'Login'),
(14, 3, '2025-05-19 14:36:54', 'Editare serviciu Spa'),
(15, 3, '2025-05-19 14:37:09', 'Editare serviciu Spa'),
(16, 3, '2025-05-19 14:41:46', 'Ștergere Employees ID 1809'),
(17, 3, '2025-05-20 08:55:36', 'Login'),
(18, 2, '2025-05-22 16:15:50', 'Login'),
(19, 2, '2025-05-22 16:16:25', 'Rezervare cameră'),
(20, 2, '2025-05-22 16:16:48', 'Rezervare serviciu'),
(21, 2, '2025-05-22 16:17:15', 'Actualizare profil'),
(22, 2, '2025-05-22 16:20:25', 'Actualizare profil'),
(23, 2, '2025-05-22 16:20:47', 'Actualizare profil'),
(24, 3, '2025-05-22 16:21:03', 'Login'),
(25, 2, '2025-05-22 16:29:54', 'Login'),
(26, 2, '2025-05-22 16:30:36', 'Rezervare cameră'),
(27, 2, '2025-05-22 16:30:53', 'Rezervare serviciu'),
(28, 3, '2025-05-22 16:36:05', 'Login'),
(29, 3, '2025-05-22 18:41:11', 'Editare utilizator Alina Panaite'),
(30, 3, '2025-05-22 18:41:16', 'Editare utilizator Alina Panaite'),
(31, 3, '2025-05-22 18:41:36', 'Editare utilizator Florin Purluca'),
(32, 3, '2025-05-22 18:41:52', 'Editare utilizator Florin Purluca'),
(33, 3, '2025-05-22 18:43:18', 'Adăugare utilizator test test'),
(34, 3, '2025-05-22 19:49:01', 'Ștergere Users ID 22'),
(35, 3, '2025-05-22 19:49:04', 'Ștergere Users ID 22'),
(36, 3, '2025-05-22 19:49:23', 'Ștergere Users ID 22'),
(37, 3, '2025-05-22 19:49:30', 'Ștergere Users ID 22'),
(38, 3, '2025-05-22 19:49:47', 'Ștergere Users ID 22'),
(39, 3, '2025-05-22 19:50:21', 'Ștergere Users ID 22'),
(40, 3, '2025-05-22 19:50:31', 'Ștergere Users ID 22'),
(41, 3, '2025-05-22 19:52:08', 'Ștergere Users ID 22'),
(42, 3, '2025-05-22 19:52:16', 'Ștergere Users ID 22'),
(43, 3, '2025-05-22 19:52:39', 'Ștergere Users ID 22'),
(44, 3, '2025-05-22 19:52:51', 'Ștergere Users ID 22'),
(45, 3, '2025-05-22 19:53:02', 'Ștergere Users ID 22'),
(46, 3, '2025-05-22 19:53:08', 'Ștergere Users ID 22'),
(47, 3, '2025-05-22 19:53:20', 'Ștergere Users ID 22'),
(48, 3, '2025-05-22 19:53:30', 'Ștergere Users ID 22'),
(49, 3, '2025-05-22 19:53:49', 'Ștergere Users ID 22'),
(50, 3, '2025-05-22 19:53:59', 'Ștergere Users ID 22'),
(51, 3, '2025-05-22 19:54:08', 'Ștergere Users ID 22'),
(52, 3, '2025-05-22 19:54:37', 'Adăugare utilizator test test'),
(53, 3, '2025-05-22 19:55:24', 'Adăugare utilizator mihai radu'),
(54, 3, '2025-05-22 19:55:56', 'Adăugare utilizator dorian popescu'),
(55, 3, '2025-05-22 19:56:24', 'Editare utilizator Mihai rRdu'),
(56, 3, '2025-05-22 19:56:32', 'Editare utilizator Mihai Radu'),
(57, 3, '2025-05-22 19:56:39', 'Editare utilizator Test test'),
(58, 3, '2025-05-22 19:56:52', 'Editare utilizator Dorian Popescu'),
(59, 3, '2025-05-22 19:57:48', 'Editare utilizator Dorian Popescu'),
(60, 25, '2025-05-22 19:58:19', 'Login'),
(61, 25, '2025-05-22 19:59:12', 'Ștergere Services ID 1297'),
(62, 25, '2025-05-22 19:59:35', 'Ștergere Employees ID 1083'),
(63, 2, '2025-05-22 20:06:53', 'Login'),
(64, 3, '2025-05-22 20:07:14', 'Login'),
(65, 3, '2025-05-22 21:26:50', 'Editare cameră 201'),
(66, 3, '2025-05-22 21:27:02', 'Editare cameră 201'),
(67, 3, '2025-05-22 22:04:00', 'Adăugare cameră 193'),
(68, 3, '2025-05-22 22:04:34', 'Adăugare rezervare pentru client 2'),
(69, 3, '2025-05-22 22:04:46', 'Editare rezervare ID 1'),
(70, 3, '2025-05-22 22:05:10', 'Adăugare serviciu Sala'),
(71, 3, '2025-05-22 22:06:57', 'Adăugare serviciu Sala'),
(72, 3, '2025-05-22 22:32:57', 'Editare cameră 101'),
(73, 3, '2025-05-22 22:33:31', 'Adăugare cameră 3'),
(74, 3, '2025-05-22 22:33:41', 'Ștergere Rooms ID 3300'),
(75, 25, '2025-05-23 07:03:31', 'Login'),
(76, 25, '2025-05-23 07:23:29', 'Import XLS în tabela Rooms (1 succese, 0 erori)'),
(77, 25, '2025-05-23 07:26:18', 'Import XLS în tabela Rooms (1 succese, 0 erori)'),
(78, 25, '2025-05-23 07:42:22', 'Editare client test test'),
(79, 25, '2025-05-23 07:42:32', 'Editare client test test'),
(80, 25, '2025-05-23 07:42:36', 'Ștergere Customers ID 26'),
(81, 25, '2025-05-23 07:51:55', 'Generare factură rezervare #1 pentru Diana Aron'),
(82, 25, '2025-05-23 08:10:11', 'Generare factură rezervare #1 pentru Diana Aron'),
(83, 27, '2025-05-23 09:27:06', 'Înregistrare cont'),
(84, 27, '2025-05-23 09:30:21', 'Login'),
(85, 27, '2025-05-23 09:34:43', 'Rezervare serviciu'),
(86, 27, '2025-05-23 09:38:54', 'Rezervare cameră'),
(87, 28, '2025-05-23 09:45:52', 'Înregistrare cont'),
(88, 28, '2025-05-23 09:47:56', 'Login'),
(89, 2, '2025-05-23 11:13:59', 'Login'),
(90, 29, '2025-05-23 12:12:00', 'Înregistrare cont'),
(91, 29, '2025-05-23 12:12:17', 'Login'),
(92, 29, '2025-05-23 12:12:44', 'Rezervare cameră'),
(93, 29, '2025-05-23 12:13:04', 'Rezervare serviciu'),
(94, 3, '2025-05-23 12:14:33', 'Login'),
(95, 3, '2025-05-23 12:16:33', 'Generare factură rezervare #2 pentru Panaite Alina'),
(96, 3, '2025-05-23 12:16:44', 'Generare factură rezervare #3 pentru Panaite Alina'),
(97, 29, '2025-05-23 12:19:05', 'Login'),
(98, 3, '2025-05-23 12:21:18', 'Login'),
(99, 3, '2025-05-24 17:45:37', 'Login'),
(100, 3, '2025-05-25 08:12:30', 'Login'),
(101, 3, '2025-05-25 08:12:30', 'Login'),
(102, 2, '2025-05-25 08:13:36', 'Login'),
(103, 32, '2025-05-25 19:37:17', 'Înregistrare cont'),
(104, 32, '2025-05-25 19:37:30', 'Login'),
(105, 2, '2025-05-25 21:11:29', 'Login'),
(106, 3, '2025-05-25 21:19:03', 'Login'),
(107, 35, '2025-05-26 09:25:35', 'Înregistrare cont'),
(108, 35, '2025-05-26 09:25:51', 'Login'),
(109, 2, '2025-05-26 09:27:04', 'Login'),
(110, 35, '2025-05-26 09:41:12', 'Login'),
(111, 35, '2025-05-26 09:43:24', 'Login'),
(112, 35, '2025-05-26 10:29:42', 'Login'),
(113, 35, '2025-05-26 10:39:17', 'Login'),
(114, 35, '2025-05-26 10:44:47', 'Login'),
(115, 35, '2025-05-26 10:58:44', 'Login'),
(116, 35, '2025-05-26 11:03:40', 'Login'),
(117, 35, '2025-05-26 11:08:28', 'Login'),
(118, 35, '2025-05-26 11:09:16', 'Login'),
(119, 35, '2025-05-26 11:14:55', 'Login'),
(120, 35, '2025-05-26 11:30:46', 'Login'),
(121, 35, '2025-05-26 11:39:05', 'Login'),
(122, 35, '2025-05-26 13:05:48', 'Login'),
(123, 35, '2025-05-26 13:16:38', 'Login'),
(124, 35, '2025-05-26 15:32:28', 'Login'),
(125, 35, '2025-05-26 15:39:28', 'Login'),
(126, 35, '2025-05-26 15:43:53', 'Login'),
(127, 35, '2025-05-26 15:48:19', 'Login'),
(128, 35, '2025-05-26 15:55:31', 'Login'),
(129, 35, '2025-05-26 16:26:40', 'Login'),
(130, 35, '2025-05-26 17:12:01', 'Login'),
(131, 35, '2025-05-26 17:13:02', 'Actualizare profil'),
(132, 35, '2025-05-26 17:14:27', 'Actualizare profil'),
(133, 35, '2025-05-26 17:39:15', 'Login'),
(134, 35, '2025-05-26 17:50:47', 'Login'),
(135, 35, '2025-05-26 17:51:08', 'Generare factură PDF'),
(136, 35, '2025-05-26 17:51:53', 'Generare factură PDF'),
(137, 35, '2025-05-26 17:57:02', 'Login'),
(138, 35, '2025-05-26 18:28:15', 'Login'),
(139, 35, '2025-05-26 18:29:39', 'Login'),
(140, 35, '2025-05-26 18:29:45', 'Generare factură PDF'),
(141, 3, '2025-05-26 18:33:02', 'Login'),
(142, 36, '2025-05-26 18:56:58', 'Înregistrare'),
(143, 36, '2025-05-26 18:57:08', 'Login'),
(144, 36, '2025-05-26 19:04:18', 'Login'),
(145, 36, '2025-05-26 19:04:20', 'Login'),
(146, 36, '2025-05-26 19:04:45', 'Login'),
(147, 36, '2025-05-26 19:08:52', 'Login'),
(148, 36, '2025-05-26 19:17:28', 'Login'),
(149, 36, '2025-05-26 19:37:16', 'Login'),
(150, 36, '2025-05-26 19:38:36', 'Login'),
(151, 36, '2025-05-26 20:02:30', 'Login'),
(152, 35, '2025-05-26 20:25:49', 'Login'),
(153, 35, '2025-05-26 20:27:21', 'Generare factură PDF'),
(154, 36, '2025-05-26 20:28:05', 'Login'),
(155, 36, '2025-05-26 21:29:41', 'Login'),
(156, 36, '2025-05-26 21:31:16', 'Login'),
(157, 36, '2025-05-26 21:32:05', 'Login'),
(158, 36, '2025-05-26 21:36:36', 'Login'),
(159, 36, '2025-05-27 07:03:25', 'Login'),
(160, 3, '2025-05-27 07:04:08', 'Login'),
(161, 36, '2025-05-27 10:41:29', 'Login'),
(162, 35, '2025-05-27 10:42:25', 'Login'),
(163, 35, '2025-05-27 10:42:37', 'Generare factură PDF'),
(164, 36, '2025-05-27 10:53:22', 'Login'),
(165, 36, '2025-05-27 11:04:35', 'Login'),
(166, 36, '2025-05-27 11:18:36', 'Login'),
(167, 36, '2025-05-27 11:20:16', 'Login'),
(168, 36, '2025-05-27 11:25:04', 'Login'),
(169, 36, '2025-05-27 11:33:06', 'Login'),
(170, 36, '2025-05-27 11:33:51', 'Login'),
(171, 36, '2025-05-27 11:38:52', 'Login'),
(172, 36, '2025-05-27 11:46:15', 'Login'),
(173, 36, '2025-05-27 11:54:50', 'Login'),
(174, 36, '2025-05-27 12:12:46', 'Login'),
(175, 36, '2025-05-27 12:27:35', 'Login'),
(176, 35, '2025-05-27 12:28:39', 'Login'),
(177, 35, '2025-05-27 12:32:58', 'Login'),
(178, 35, '2025-05-27 12:39:34', 'Login'),
(179, 35, '2025-05-27 12:40:22', 'Login'),
(180, 35, '2025-05-27 12:49:27', 'Login'),
(181, 35, '2025-05-27 12:51:15', 'Login'),
(182, 35, '2025-05-27 13:07:30', 'Login'),
(183, 35, '2025-05-27 13:11:32', 'Login'),
(184, 35, '2025-05-27 13:26:43', 'Login'),
(185, 35, '2025-05-27 16:18:29', 'Login'),
(186, 35, '2025-05-27 16:21:57', 'Generare factură PDF'),
(187, 35, '2025-05-27 16:22:35', 'Actualizare profil'),
(188, 36, '2025-05-27 16:23:00', 'Login'),
(189, 3, '2025-05-27 16:32:44', 'Login'),
(190, 36, '2025-05-27 17:41:41', 'Login'),
(191, 37, '2025-05-28 12:05:17', 'Înregistrare cont'),
(192, 37, '2025-05-28 12:05:35', 'Login'),
(193, 37, '2025-05-28 12:05:35', 'Login'),
(194, 37, '2025-05-28 12:12:02', 'Rezervare serviciu'),
(195, 37, '2025-05-28 12:12:13', 'Rezervare cameră'),
(196, 37, '2025-05-28 12:12:24', 'Rezervare cameră'),
(197, 37, '2025-05-28 12:12:38', 'Rezervare cameră'),
(198, 37, '2025-05-28 12:13:00', 'Actualizare profil'),
(199, 38, '2025-05-28 12:13:54', 'Înregistrare cont'),
(200, 38, '2025-05-28 12:14:11', 'Login'),
(201, 38, '2025-05-28 12:14:11', 'Login'),
(202, 38, '2025-05-28 12:15:50', 'Adăugare cameră 44'),
(203, 38, '2025-05-28 12:16:27', 'Ștergere Rooms ID 3306'),
(204, 38, '2025-05-28 12:16:59', 'Editare client ttt3 ttt4'),
(205, 38, '2025-05-28 12:17:06', 'Ștergere Customers ID 39'),
(206, 38, '2025-05-28 12:17:10', 'Ștergere Customers ID 39'),
(207, 38, '2025-05-28 12:17:10', 'Editare client Panaite Alina'),
(208, 2, '2025-05-28 16:45:17', 'Login'),
(209, 2, '2025-05-28 16:45:48', 'Rezervare cameră'),
(210, 2, '2025-05-28 16:47:34', 'Rezervare serviciu'),
(211, 2, '2025-05-28 16:48:19', 'Actualizare profil'),
(212, 3, '2025-05-28 16:48:43', 'Login'),
(213, 3, '2025-05-28 16:53:44', 'Import XLS în tabela Rooms (1 succese, 0 erori)'),
(214, 3, '2025-05-28 16:54:35', 'Ștergere Rooms ID 3307'),
(215, 2, '2025-05-28 16:57:27', 'Login');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `istoric_utilizare`
--
ALTER TABLE `istoric_utilizare`
  ADD PRIMARY KEY (`IstoricID`),
  ADD KEY `fk_userid_idx` (`UserID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `istoric_utilizare`
--
ALTER TABLE `istoric_utilizare`
  MODIFY `IstoricID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=216;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `istoric_utilizare`
--
ALTER TABLE `istoric_utilizare`
  ADD CONSTRAINT `fk_user_istoric` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
