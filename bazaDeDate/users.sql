-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 30, 2025 at 05:43 AM
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
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `UserRole` enum('client','admin') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `LastName`, `FirstName`, `Email`, `password`, `UserRole`) VALUES
(2, 'Alina', 'Panait', 'panaitealina2@gmail.com', '$2y$10$rXHiPK15WrbgDy0lWJMzjeIcDzyVL3Fn.8jlysyCFAa0ikEGWAjk.', 'client'),
(3, 'Purluca', 'Florin', 'florinn2003@gmail.com', '$2y$10$18Wwxzm0wgFIB.WcvilR5eFW6Ch87ObynYpxtVx4kSMGLONibBbEG', 'admin'),
(23, 'test', 'Test', 'test@gmail.com', '$2y$10$CsQkUBwKQVm6fRqVDslAoemwrJ3xzibBcy6j.FlsiKO8uM.NKVFpy', 'client'),
(24, 'Radu', 'Mihai', 'miradu@test.com', '$2y$10$aAYh9hcmMBehFM5MLTyMNOG4B5GmzdhuqqPS0bB8HthyaBpFW.Xz6', 'client'),
(25, 'Popescu', 'Dorian', 'popescu@test.com', '$2y$10$8lu/BCsKP1/ngG5ogG/.Tugjaj/Losqjnl.b35RLeLqynF3ZgCOEm', 'admin'),
(26, 'Popescu', 'Ion', 'ion.popescu@example.com', '$2y$10$kL4F7uphSjheh5W.fVmkPuoObjrp0Z8PkmP8X09CTp.Cfx0YR.tIi', 'client'),
(27, 'Ionescu', 'Andrei', 'andrei.ionescu@gmail.com', '$2y$10$/EhkKfKmjz4jbyKbNJ7SNu49eWpbpJcN/tr7vWyZ4XwIsSxoO0oJW', 'client'),
(28, 'Sef', 'Dan', 'dan.sef@gmail.com', '$2y$10$Apc7acEdWJEbI1SznyrVR.Nyrg/J4fvRu3lHpzCLvjkp92neXdSR6', 'admin'),
(29, 'Ava', 'Delia', 'deliava@gmail.com', '$2y$10$1SQjfinOM3lkvV82XGcmUeZ5WlFGrS01JT49sKZ43W9RhZLLtjw9.', 'client'),
(30, 'olteanu', 'ana', 'ana@test.com', 'miau', 'client'),
(31, 'crina', 'panaite', 'panaitecrina08\"gmail.com', 'PanaiteCrina', 'client'),
(32, 'panaite', 'crina', 'panaitecrina08@gmail.com', '$2y$10$3fFE/awIv427FjNg.efwC.yDxJteOCVtxoLDbPdq3G2.lg0HYtXQe', 'client'),
(33, 'Purluca', 'Florin', 'florinn20003@gmail.com', 'alina', 'client'),
(34, 'admin', 'admin', 'admin@test.com', 'miau', 'admin'),
(35, 'panaite', 'alina', 'p@t.com', '85e7613fc5c2e438bda561c68d9899cf3f648badaa558b01417630f06cf104c1', 'client'),
(36, 'test', 'admin', 'ad@test.com', '85e7613fc5c2e438bda561c68d9899cf3f648badaa558b01417630f06cf104c1', 'admin'),
(37, 'Ionescu', 'Angela', 'angela@gmail.com', '$2y$10$2byLdk.vCmqNTpVan0FEb.Ec9MUu2X5sESYIjgTUT.ssCnIso9pgC', 'client'),
(38, 'Ion', 'Sef', 'ion@sef.com', '$2y$10$iKzTYYHFdUWFeqVK4YqtheBvH/3x2JMrqX6xFMrFRObOzP4SdU6TG', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
