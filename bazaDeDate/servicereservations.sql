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
-- Table structure for table `servicereservations`
--

CREATE TABLE `servicereservations` (
  `ServiceReservationID` int(11) NOT NULL,
  `CustomerID` int(11) NOT NULL,
  `ServiceID` int(11) NOT NULL,
  `ReservationDate` date NOT NULL,
  `OraRezervare` time DEFAULT NULL,
  `TotalAmount` decimal(10,2) NOT NULL,
  `EmployeeID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `servicereservations`
--

INSERT INTO `servicereservations` (`ServiceReservationID`, `CustomerID`, `ServiceID`, `ReservationDate`, `OraRezervare`, `TotalAmount`, `EmployeeID`) VALUES
(1, 2, 1135, '2025-05-21', '17:02:00', 28.00, 10003),
(2, 2, 1217, '2025-05-26', '17:35:00', 30.00, 10003),
(3, 2, 7311, '2025-05-29', '11:19:00', 22.00, 10013),
(4, 2, 1368, '2025-05-30', '10:30:00', 45.00, 10004),
(5, 23, 1217, '2025-05-27', '08:00:00', 30.00, 10003),
(6, 19, 1023, '2025-05-28', '15:30:00', 120.00, 10012),
(7, 20, 7311, '2025-05-29', '09:00:00', 22.00, 10013),
(8, 24, 1567, '2025-05-30', '14:00:00', 50.00, 10007),
(9, 21, 1045, '2025-06-01', '10:00:00', 150.00, 10013),
(10, 25, 1368, '2025-06-02', '11:30:00', 45.00, 10004),
(11, 22, 5183, '2025-06-03', '13:00:00', 75.00, 10011),
(12, 26, 1835, '2025-06-04', '12:00:00', 20.00, 10012),
(13, 27, 4208, '2025-06-05', '19:00:00', 30.00, 10010),
(14, 28, 2157, '2025-06-06', '07:30:00', 45.50, 10008),
(15, 27, 7310, '2025-06-25', '12:37:00', 20.00, 10013),
(16, 29, 7310, '2025-05-25', '15:00:00', 20.00, 10013),
(17, 35, 1217, '2025-05-26', NULL, 30.00, NULL),
(18, 35, 1999, '2025-05-26', NULL, 35.00, NULL),
(19, 35, 3589, '2025-05-26', '15:30:00', 15.00, NULL),
(21, 35, 8451, '2025-05-27', '14:00:00', 10.00, NULL),
(22, 37, 7311, '2025-05-31', '14:13:00', 22.00, NULL),
(23, 2, 1440, '2025-06-19', '11:53:00', 5.00, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `servicereservations`
--
ALTER TABLE `servicereservations`
  ADD PRIMARY KEY (`ServiceReservationID`),
  ADD KEY `CustomerID` (`CustomerID`),
  ADD KEY `ServiceID` (`ServiceID`),
  ADD KEY `fk_servicereservations_employee` (`EmployeeID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `servicereservations`
--
ALTER TABLE `servicereservations`
  MODIFY `ServiceReservationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `servicereservations`
--
ALTER TABLE `servicereservations`
  ADD CONSTRAINT `fk_servicereservations_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `servicereservations_ibfk_1` FOREIGN KEY (`CustomerID`) REFERENCES `customers` (`CustomerID`),
  ADD CONSTRAINT `servicereservations_ibfk_2` FOREIGN KEY (`ServiceID`) REFERENCES `services` (`ServiceID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
