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
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `ServiceID` int(11) NOT NULL,
  `ServiceName` varchar(255) DEFAULT NULL,
  `ServiceDescription` text DEFAULT NULL,
  `Price` decimal(12,2) DEFAULT NULL,
  `ServiceDuration` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`ServiceID`, `ServiceName`, `ServiceDescription`, `Price`, `ServiceDuration`) VALUES
(1, 'Sala', 'Sala pentru tineri si adulti', 40.00, NULL),
(1023, 'Spa', 'Masaj delicat cu uleiuri esențiale aromaterapeutice pentru relaxare și revitalizare.', 120.00, '1 oră'),
(1024, 'Spa', 'Terapie cu aburi urmată de un masaj relaxant pentru eliminarea stresului și a tensiunii musculare.', 150.00, '1 oră'),
(1035, 'Spa', 'Un pachet complet de relaxare cu masaj, exfoliere și hidratare pentru revitalizarea pielii.', 125.00, '1 oră'),
(1036, 'Spa', 'Masaj terapeutic cu pietre vulcanice fierbinți pentru detensionare musculară și recuperare.', 140.00, '1 oră'),
(1045, 'Sala de Conferinte', 'Închirierea sălii de conferințe a hotelului, dotată cu echipamente moderne pentru întâlniri și prezentări.', 150.00, 'N/A'),
(1046, 'Sala de Conferinte', 'Spațiu profesional ideal pentru seminarii, echipat cu proiector, sonorizare și mobilier confortabil.', 180.00, 'N/A'),
(1112, 'Sala de Conferinte', 'Închiriere sală de conferințe pentru evenimente corporative, cu setup personalizabil și catering disponibil.', 160.00, 'N/A'),
(1113, 'Sala de Conferinte', 'Sală echipată pentru workshopuri interactive, dotată cu flipchart, proiector și services de coffee break.', 200.00, 'N/A'),
(1134, 'Bufet Mic Dejun', 'Mic dejun bufet cu o varietate de preparate proaspete, inclusiv fructe, cereale și produse de patiserie.', 25.00, 'N/A'),
(1135, 'Bufet Mic Dejun', 'Mic dejun bufet cu preparate internaționale și tradiționale, inclusiv opțiuni pentru toate gusturile.', 28.00, 'N/A'),
(1217, 'Bufet Mic Dejun', 'Mic dejun bufet variat, cu produse de calitate premium și opțiuni sănătoase pentru un început de zi perfect.', 30.00, 'N/A'),
(1218, 'Bufet Mic Dejun', 'Mic dejun bufet cu preparate vegetariene și vegane, incluzând smoothie-uri, salate și alternative proteice.', 32.00, 'N/A'),
(1296, 'Servicii de Ingrijire a Animalelor', 'Servicii profesionale de îngrijire pentru animalele de companie, inclusiv periere și toaletare.', 40.00, 'N/A'),
(1368, 'Servicii de Ingrijire a Animalelor', 'Îngrijire specializată pentru animale, inclusiv joacă, periere și atenție individuală într-un mediu sigur.', 45.00, '1 oră'),
(1369, 'Servicii de Ingrijire a Animalelor', 'Îngrijire completă și hrănire pentru animalele de companie, adaptată nevoilor fiecărui patruped.', 50.00, '1 oră'),
(1378, 'Magazin Suveniruri', 'Magazin de suveniruri cu produse locale, gustări și articole de artizanat românesc.', 0.00, 'N/A'),
(1379, 'Magazin Suveniruri', 'Produse locale și cadouri autentice pentru turiști, incluzând obiecte handmade și dulciuri tradiționale.', 7.00, 'N/A'),
(1440, 'Magazin Suveniruri', 'Suveniruri, produse tradiționale românești și articole de artă populară.', 5.00, 'N/A'),
(1441, 'Magazin Suveniruri', 'Articole tradiționale românești și produse handmade, ideale pentru suveniruri și cadouri speciale.', 8.00, 'N/A'),
(1483, 'Planificare Nunta', 'Serviciu complet de planificare a nunții, incluzând decor, logistică și coordonare profesională.', 1000.00, 'N/A'),
(1484, 'Planificare Nunta', 'Organizare de nuntă personalizată, de la locație și meniu până la flori și muzică.', 1050.00, 'N/A'),
(1567, 'Salon de Infrumusetare', 'Servicii de coafură, manichiură, pedichiură și tratamente faciale pentru un look impecabil.', 50.00, '1.5 ore'),
(1568, 'Salon de Infrumusetare', 'Pachet complet de înfrumusețare, incluzând tuns, vopsit, styling, manichiură și tratamente pentru piele.', 55.00, '1.5 ore'),
(1569, 'Planificare Nunta', 'Planificare detaliată a nunții, de la concept și organizare până la implementare și coordonare în ziua evenimentului.', 1050.00, 'N/A'),
(1673, 'Salon de Infrumusetare', 'Servicii de infrumusetare si tratamente de relaxare', 60.00, '1.5 ore'),
(1721, 'Transfer Aeroport', 'Transfer confortabil de la aeroport la hotel si invers', 50.00, '40 minute'),
(1835, 'Serviciu de Spalatorie', 'Spalatorie si curatatorie haine', 20.00, '1 oră'),
(1999, 'Room Service', 'Livrare rapida de mancare si bauturi in camera ta', 35.00, NULL),
(2157, 'Transfer Aeroport', 'Transfer privat de la si catre aeroport', 45.50, '30 minute'),
(2158, 'Transfer Aeroport', 'Transfer de lux de la aeroport la hotel cu masina privata', 48.00, '45 minute'),
(3589, 'Serviciu de Spalatorie', 'Spalarea, uscarea si impaturirea hainelor', 15.00, '1 oră'),
(3590, 'Serviciu de Spalatorie', 'Spalatorie de haine si curatare profesionala a hainelor delicate', 18.00, '1 oră'),
(4208, 'Room Service', 'Mancare si bauturi livrate direct in camera', 30.00, NULL),
(4209, 'Room Service', 'Servicii de room service disponibile 24/7 pentru oaspeti', 32.00, 'N/A'),
(5183, 'Tur Ghidat', 'Un tur ghidat al orasului pentru a explora atractiile principale', 75.00, '2 ore'),
(5184, 'Tur Ghidat', 'Tur ghidat al orasului cu ghid autorizat si transport inclus', 85.00, '2 ore'),
(6274, 'Masaj Terapeutic', 'Masaj profesional pentru relaxare sau ameliorarea durerilor musculare', 85.00, NULL),
(6275, 'Masaj Terapeutic', 'Masaj de relaxare profunda si tratamente antistres', 90.00, NULL),
(7310, 'Acces Centru Fitness', 'Acces complet la centrul de fitness al hotelului', 20.00, NULL),
(7311, 'Acces Centru Fitness', 'Acces la sala de fitness si antrenamente personalizate', 22.00, NULL),
(8451, 'Parcare', 'Parcare la fata locului pentru oaspetii hotelului', 10.00, '30 minute'),
(8452, 'Parcare', 'Parcare privata la hotel, accesibilitate non-stop', 12.00, '1 oră'),
(9253, 'Inchiriere Masina', 'Inchirierea unei masini pentru excursii sau afaceri', 60.00, NULL),
(9254, 'Inchiriere Masina', 'Inchiriere masina de lux pentru excursii in zona', 65.00, NULL),
(9255, 'Sala', 'Sala pentru tineri si adulti', 40.00, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`ServiceID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `ServiceID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9256;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
