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
-- Table structure for table `roomreservations`
--

CREATE TABLE `roomreservations` (
  `ReservationID` int(11) NOT NULL,
  `CustomerID` int(11) NOT NULL,
  `RoomID` int(11) NOT NULL,
  `CheckInDate` date NOT NULL,
  `CheckOutDate` date NOT NULL,
  `TotalAmount` decimal(10,2) NOT NULL,
  `EmployeeID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roomreservations`
--

INSERT INTO `roomreservations` (`ReservationID`, `CustomerID`, `RoomID`, `CheckInDate`, `CheckOutDate`, `TotalAmount`, `EmployeeID`) VALUES
(1, 19, 317, '2025-05-20', '2025-05-24', 480.00, 8312),
(2, 2, 1324, '2025-05-24', '2025-05-26', 120.00, 1924),
(3, 2, 1678, '2025-05-29', '2025-05-31', 150.50, 11002),
(4, 2, 2145, '2025-05-05', '2025-05-07', 170.50, 11001),
(5, 27, 73, '2025-05-28', '2025-05-30', 197.00, 11001),
(6, 2, 32, '2025-06-01', '2025-06-03', 301.50, 11002),
(7, 19, 278, '2025-06-02', '2025-06-04', 201.00, 11001),
(8, 20, 1201, '2025-06-03', '2025-06-06', 211.50, 1701),
(9, 21, 1453, '2025-06-04', '2025-06-07', 150.00, 8312),
(10, 23, 1890, '2025-06-05', '2025-06-08', 150.00, 1701),
(11, 24, 2031, '2025-06-06', '2025-06-09', 316.50, 8312),
(12, 25, 2256, '2025-06-07', '2025-06-10', 450.00, 1924),
(13, 26, 2489, '2025-06-08', '2025-06-11', 307.50, 11001),
(14, 28, 2594, '2025-06-09', '2025-06-12', 420.00, 1924),
(15, 30, 3298, '2025-06-10', '2025-06-13', 465.00, 11002),
(16, 29, 73, '2025-05-25', '2025-05-26', 98.50, 11001),
(19, 35, 2941, '2025-05-26', '2025-05-27', 145.50, NULL),
(21, 35, 2367, '2025-05-27', '2025-05-29', 320.00, NULL),
(22, 35, 2594, '2025-05-27', '2025-05-29', 140.00, NULL),
(25, 37, 317, '2025-05-29', '2025-06-08', 1200.00, NULL),
(26, 37, 3305, '2025-05-29', '2025-05-31', 301.50, NULL),
(27, 37, 3299, '2025-06-16', '2025-06-30', 840.00, NULL),
(28, 2, 2710, '2025-06-15', '2025-06-23', 2446.00, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `roomreservations`
--
ALTER TABLE `roomreservations`
  ADD PRIMARY KEY (`ReservationID`),
  ADD KEY `CustomerID` (`CustomerID`),
  ADD KEY `RoomID` (`RoomID`),
  ADD KEY `fk_roomreservations_employee` (`EmployeeID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `roomreservations`
--
ALTER TABLE `roomreservations`
  MODIFY `ReservationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `roomreservations`
--
ALTER TABLE `roomreservations`
  ADD CONSTRAINT `fk_roomreservations_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `roomreservations_ibfk_1` FOREIGN KEY (`CustomerID`) REFERENCES `customers` (`CustomerID`),
  ADD CONSTRAINT `roomreservations_ibfk_2` FOREIGN KEY (`RoomID`) REFERENCES `rooms` (`RoomID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
