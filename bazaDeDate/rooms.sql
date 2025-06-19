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
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `RoomID` int(11) NOT NULL,
  `RoomNumber` varchar(10) NOT NULL,
  `RoomType` varchar(50) NOT NULL,
  `PricePerNight` decimal(10,2) NOT NULL,
  `IsAvailable` tinyint(1) DEFAULT 1,
  `Facilities` text DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Capacity` int(11) DEFAULT NULL,
  `BedType` varchar(50) DEFAULT NULL,
  `HasAC` tinyint(1) DEFAULT NULL,
  `HasBalcony` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`RoomID`, `RoomNumber`, `RoomType`, `PricePerNight`, `IsAvailable`, `Facilities`, `Description`, `Capacity`, `BedType`, `HasAC`, `HasBalcony`) VALUES
(32, '101', 'Double', 150.75, 1, 'WiFi, TV, Aer condiționat, Seif, Balcon', 'Cameră dublă spațioasă, cu vedere la oraș', 2, 'King', 1, 1),
(41, '203', 'Single', 105.00, 0, 'WiFi, TV, Birou de lucru, Cafetieră', 'Cameră single confortabilă, ideală pentru călătorii de afaceri.', 1, 'Single Bed', 0, 0),
(73, '104', 'Single', 98.50, 1, 'WiFi, TV, Birou, Uscător de păr, Aer condiționat', 'Cameră single modernă, perfectă pentru sejururi scurte.', 1, 'Single Bed', 1, 0),
(89, '201', 'Double', 160.25, 0, 'WiFi, TV, Balcon, Seif, Cadă spa', 'Cameră dublă cu pat king-size și balcon privat.', 2, 'King', 0, 1),
(124, '305', 'Suite', 90.00, 0, 'WiFi, TV, Seif, Halate și papuci, Acces la SPA', 'Suită elegantă cu zonă de relaxare și birou de lucru.', 4, 'King Bed', 0, 0),
(278, '309', 'Single', 100.50, 1, 'WiFi, TV, Aer condiționat, Uscător de păr', 'Cameră single simplă, potrivită pentru călătorii scurte.', 1, 'Single Bed', 1, 0),
(300, '202', 'Double', 140.00, 0, 'WiFi, TV, Balcon, Halate și papuci', 'Cameră dublă modernă, cu acces la lounge privat.', 2, 'Queen Bed', 0, 1),
(317, '102', 'Suite', 120.00, 0, 'WiFi, TV, Jacuzzi, Seif, Uscător de păr', 'Suită de lux cu design elegant și cadă cu hidromasaj.', 4, 'King Bed', 0, 0),
(458, '304', 'Single', 110.00, 0, 'WiFi, TV, Birou, Cafetieră, Uscător de păr', 'Cameră single clasică, cu birou de lucru și scaun ergonomic.', 1, 'Single Bed', 0, 0),
(543, '301', 'Double', 145.50, 0, 'WiFi, TV, Balcon, Cadă spa, Halate și papuci', 'Cameră dublă luminoasă, cu vedere la parc.', 2, 'Queen Bed', 0, 1),
(654, '103', 'Double', 155.00, 0, 'WiFi, TV, Aer condiționat, Seif, Uscător de păr', 'Cameră dublă spațioasă, potrivită pentru familii.', 2, 'Queen Bed', 1, 0),
(876, '208', 'Suite', 175.75, 0, 'WiFi, TV, Jacuzzi, Balcon, Halate și papuci', 'Suită exclusivistă, cu acces la piscină privată.', 4, 'King Bed', 0, 1),
(987, '307', 'Suite', 90.00, 0, 'WiFi, TV, Bucătărie proprie, Cafetieră, Seif', 'Suită confortabilă, cu bucătărie complet echipată.', 4, 'King Bed', 0, 0),
(1201, '204', 'Single', 70.50, 0, 'WiFi, TV, Aer condiționat, Birou', 'Cameră single compactă, perfectă pentru sejururi scurte.', 1, 'Single Bed', 1, 0),
(1324, '105', 'Double', 60.00, 1, 'WiFi, TV, Baie proprie, Aer condiționat, Seif', 'Cameră dublă economică, ideală pentru turiști.', 2, 'Queen Bed', 1, 0),
(1453, '302', 'Suite', 50.00, 1, 'WiFi, TV, Seif, Halate și papuci, Acces la SPA', 'Suită modernă, cu acces la spa și centru de wellness.', 4, 'King Bed', 0, 0),
(1567, '106', 'Single', 65.75, 0, 'WiFi, TV, Birou, Uscător de păr', 'Cameră single accesibilă, potrivită pentru călători singuri.', 1, 'Single Bed', 0, 0),
(1678, '206', 'Double', 75.25, 1, 'WiFi, TV, Balcon, Cadă spa, Uscător de păr', 'Cameră dublă cu balcon privat și vedere la oraș.', 2, 'Queen Bed', 0, 1),
(1782, '303', 'Suite', 80.00, 1, 'WiFi, TV, Balcon, Seif, Halate și papuci', 'Suită elegantă, cu zonă de relaxare și baie cu marmură.', 4, 'King Bed', 0, 1),
(1890, '107', 'Single', 50.00, 1, 'WiFi, TV, Birou, Cafetieră, Aer condiționat', 'Cameră single confortabilă, cu birou și scaun ergonomic.', 1, 'Single Bed', 1, 0),
(1934, '206', 'Double', 60.75, 1, 'WiFi, TV, Balcon, Cadă spa, Uscător de păr', 'Cameră dublă cu balcon privat și vedere la oraș.', 2, 'Queen Bed', 0, 1),
(2001, '308', 'Single', 55.00, 1, 'WiFi, TV, Aer condiționat, Seif', 'Cameră single mică, dar confortabilă și accesibilă.', 1, 'Single Bed', 1, 0),
(2031, '207', 'Suite', 105.50, 1, 'WiFi, TV, Jacuzzi, Balcon, Halate și papuci', 'Suită spațioasă, cu zonă de relaxare și jacuzzi privat.', 4, 'King Bed', 0, 1),
(2145, '205', 'Single', 85.25, 1, 'WiFi, TV, Birou, Cafetieră', 'Cameră single economică, perfectă pentru backpackeri.', 1, 'Single Bed', 0, 0),
(2256, '109', 'Double', 150.00, 0, 'WiFi, TV, Seif', 'Cameră dublă confortabilă, ideală pentru cupluri.', 2, 'Queen Bed', 0, 0),
(2367, '209', 'Suite', 320.00, 0, 'WiFi, TV, Seif, Halate și papuci', 'Suită spațioasă cu dotări premium.', 4, 'King Bed', 1, 0),
(2489, '310', 'Single', 102.50, 1, 'WiFi, TV, Birou', 'Cameră single perfectă pentru călătorii de afaceri.', 1, 'Single Bed', 0, 0),
(2594, '210', 'Double', 140.00, 0, 'WiFi, TV, Uscător de păr, Halate si papuci', 'Cameră dublă modernă, potrivită pentru două persoane.', 2, 'Queen Bed', 1, 0),
(2710, '110', 'Suite', 305.75, 1, 'WiFi, TV, Jacuzzi, Seif, Vedere panoramică', 'Suită elegantă cu vedere impresionantă.', 4, 'King Bed', 1, 0),
(2835, '311', 'Single', 99.00, 0, 'WiFi, TV, Uscător de păr', 'Cameră single cu toate dotările esențiale.', 1, 'Single Bed', 1, 0),
(2941, '211', 'Double', 145.50, 0, 'WiFi, TV, Cafetieră', 'Cameră dublă cu atmosferă relaxantă.', 2, 'Queen Bed', 0, 0),
(3057, '111', 'Suite', 280.00, 1, 'WiFi, TV, Seif', 'Suită luxoasă pentru un sejur relaxant.', 4, 'King Bed', 1, 0),
(3179, '312', 'Single', 110.00, 0, 'WiFi, TV, Birou, Uscător de păr', 'Cameră single potrivită pentru călătorii de afaceri.', 1, 'Single Bed', 0, 0),
(3298, '212', 'Double', 155.00, 1, 'WiFi, TV, Balcon', 'Cameră dublă cu balcon și aer condiționat.', 2, 'Queen Bed', 1, 1),
(3299, '193', 'Double', 60.00, 1, 'WiFi, TV', 'Camera mare, spatioasa de 2 persoane cu aer conditionat.', 2, 'King', 1, 0),
(3301, '874', 'Duble', 100.00, 1, 'Wifi, Balcon', 'Camera confortabila', 2, 'King', 1, 0),
(3303, '108', 'Suite', 200.75, 0, 'WiFi, TV, Jacuzzi, Seif, Halate și papuci, Vedere panoramică', 'Suită de lux cu balcon privat și vedere la mare.', 4, 'King Bed', 0, 0),
(3304, '200', 'Single', 135.00, 1, 'Wifi, Birou, Aer conditionat', 'Camera dubla', 2, 'King Bed', 1, 1),
(3305, '180', 'Double', 150.75, 1, 'WiFi, TV, Aer condiționat, Seif, Balcon', 'Cameră dublă spațioasă, cu vedere la oraș', 2, 'King', 1, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`RoomID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `RoomID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3308;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
