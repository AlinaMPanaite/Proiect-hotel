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
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `CustomerID` int(11) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Phone` varchar(15) DEFAULT NULL,
  `Address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`CustomerID`, `FirstName`, `LastName`, `Email`, `Phone`, `Address`) VALUES
(2, 'Panait', 'Alina', 'panaitealina2@gmail.com', '0771446618', 'Strada Lalelelor, nr 57'),
(19, 'Diana', 'Aron', 'diana.aron@example.com', '0733999888', 'Str. Saturn 3, Ploiești'),
(20, 'Mihail', 'Tudor', 'mihail.tudor@example.com', '0744666777', 'Str. Zorilor 4, Focșani'),
(21, 'Anamaria', 'Nistorl', 'anamaria.nistor@example.com', '0755999111', 'Str. Eminescu 10, Vaslui'),
(22, 'sffsf', 'sfsfsf', 'sfsfsf', '0767000222', 'Str. Mihai Viteazu 8, Reșița'),
(23, 'Ion', 'Popescu', 'ion.popescu@example.com', '0722333444', 'Str. Libertății 12, București'),
(24, 'Maria', 'Ionescu', 'maria.ionescu@example.com', '0733444555', 'Str. Florilor 7, Cluj-Napoca'),
(25, 'Andrei', 'Georgescu', 'andrei.georgescu@example.com', '0744555666', 'Str. Păcii 23, Timișoara'),
(26, 'Elena', 'Dumitrescu', 'elena.dumitrescu@example.com', '0755666777', 'Str. Unirii 15, Iași'),
(27, 'Vlad', 'Marinescu', 'vlad.marinescu@example.com', '0766777888', 'Str. Victoriei 9, Brașov'),
(28, 'Ana', 'Stoica', 'ana.stoica@example.com', '0777888999', 'Str. Republicii 4, Constanța'),
(29, 'Mihai', 'Radu', 'mihai.radu@example.com', '0788999000', 'Str. Centrală 18, Sibiu'),
(30, 'Cristina', 'Voicu', 'cristina.voicu@example.com', '0799000111', 'Str. Ștefan cel Mare 22, Suceava'),
(31, 'Gabriel', 'Lazăr', 'gabriel.lazar@example.com', '0711222333', 'Str. Dacia 6, Oradea'),
(32, 'Roxana', 'Munteanu', 'roxana.munteanu@example.com', '0723444555', 'Str. Avram Iancu 11, Arad'),
(33, 'Andrei', 'Ionescu', 'andrei.ionescu@gmail.com', NULL, NULL),
(34, 'Delia', 'Ava', 'deliava@gmail.com', NULL, NULL),
(35, 'alina', 'panaite', 'p@t.com', '0771446634', 'Galati, Str Basarabiei, nr 10'),
(37, 'Angela', 'Ionescu', 'angela@gmail.com', '0771446618', 'Str Vlad Tepes'),
(999, 'Test', 'Client', 'test.client@example.com', NULL, NULL),
(1000, 'crina', 'panaite', 'panaitecrina08@gmail.com', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`CustomerID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `CustomerID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1002;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
