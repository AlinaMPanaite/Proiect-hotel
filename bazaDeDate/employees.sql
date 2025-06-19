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
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `EmployeeID` int(11) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `Position` varchar(50) NOT NULL,
  `Salary` decimal(10,2) NOT NULL,
  `HireDate` date NOT NULL,
  `Phone` varchar(15) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`EmployeeID`, `FirstName`, `LastName`, `Position`, `Salary`, `HireDate`, `Phone`, `Email`) VALUES
(1089, 'Ion', 'Popescu', 'Manager', 3500.00, '2020-02-15', '0745321654', 'ion.popescu@example.com'),
(1256, 'Florin', 'Neagu', 'Room Service Attendant', 2200.00, '2021-07-20', '0761876543', 'florin.neagu@example.com'),
(1450, 'Georgiana', 'Popa', 'Bartender', 2100.00, '2020-05-05', '0773654321', 'georgiana.popa@example.com'),
(1701, 'Alexandru', 'Mihăilescu', 'Housekeeper', 2300.00, '2022-02-17', '0722111222', 'alexandru.mihailescu@example.com'),
(1924, 'Cristian', 'Toma', 'Receptionist', 2500.00, '2021-09-23', '0732333444', 'cristian.toma@example.com'),
(2065, 'Irina', 'Neagu', 'Chef', 3500.00, '2019-11-11', '0744444555', 'irina.neagu@example.com'),
(2147, 'Maria', 'Ionescu', 'Receptionist', 2500.00, '2021-03-10', '0766555666', 'maria.ionescu@example.com'),
(3645, 'Andrei', 'Georgescu', 'Laundry Staff', 2200.00, '2020-06-25', '0770666777', 'andrei.georgescu@example.com'),
(5019, 'Ana', 'Vasilescu', 'Beautician', 1800.00, '2022-01-12', '0723777888', 'ana.vasilescu@example.com'),
(6312, 'Mihai', 'Popa', 'Souvenir Shop Attendant', 2000.00, '2021-08-30', '0750888999', 'mihai.popa@example.com'),
(7410, 'Radu', 'Călinescu', 'Chef', 3500.00, '2019-12-01', '0769999000', 'radu.calinescu@example.com'),
(8312, 'Loredana', 'Petrescu', 'Event Planner', 4000.00, '2018-04-10', '0747000111', 'loredana.petrescu@example.com'),
(9025, 'Vlad', 'Radu', 'Tour Guide', 2500.00, '2020-11-22', '0731111222', 'vlad.radu@example.com'),
(10001, 'Elena', 'Dumitrescu', 'Spa Therapist', 2600.00, '2023-08-15', '0721001100', 'elena.dumitrescu@example.com'),
(10002, 'George', 'Iacob', 'Conference Coordinator', 2800.00, '2022-05-22', '0722002200', 'george.iacob@example.com'),
(10003, 'Irina', 'Marin', 'Breakfast Attendant', 2000.00, '2023-03-10', '0723003300', 'irina.marin@example.com'),
(10004, 'Roxana', 'Pop', 'Pet Care Specialist', 2100.00, '2023-06-01', '0724004400', 'roxana.pop@example.com'),
(10005, 'Andrei', 'Stefan', 'Souvenir Shop Clerk', 1900.00, '2024-01-05', '0725005500', 'andrei.stefan@example.com'),
(10006, 'Camelia', 'Voineag', 'Wedding Planner', 4000.00, '2021-09-15', '0726006600', 'camelia.voineag@example.com'),
(10007, 'Adina', 'Rosu', 'Beauty Salon Stylist', 2500.00, '2023-04-10', '0727007700', 'adina.rosu@example.com'),
(10008, 'Mihnea', 'Cristea', 'Airport Shuttle Driver', 2300.00, '2022-10-20', '0728008800', 'mihnea.cristea@example.com'),
(10009, 'Daria', 'Enache', 'Laundry Attendant', 2000.00, '2023-12-01', '0729009900', 'daria.enache@example.com'),
(10010, 'Vlad', 'Iliescu', 'Room Service Waiter', 2100.00, '2024-02-12', '0730000000', 'vlad.iliescu@example.com'),
(10011, 'Sorina', 'Pavel', 'City Tour Guide', 2700.00, '2022-07-01', '0731001100', 'sorina.pavel@example.com'),
(10012, 'Ovidiu', 'Manole', 'Massage Therapist', 2700.00, '2021-11-30', '0732002200', 'ovidiu.manole@example.com'),
(10013, 'Larisa', 'Petcu', 'Fitness Trainer', 2500.00, '2023-01-25', '0733003300', 'larisa.petcu@example.com'),
(10014, 'Cristi', 'Stan', 'Parking Attendant', 1900.00, '2023-08-18', '0734004400', 'cristi.stan@example.com'),
(10015, 'Teodor', 'Badea', 'Car Rental Assistant', 2400.00, '2022-06-16', '0735005500', 'teodor.badea@example.com'),
(11001, 'Mihai', 'Constantinescu', 'Senior Housekeeper', 2400.00, '2023-02-15', '0736000111', 'mihai.constantinescu@example.com'),
(11002, 'Andreea', 'Stanescu', 'Guest Relations Officer', 2700.00, '2022-09-10', '0737000222', 'andreea.stanescu@example.com'),
(11003, 'Bogdan', 'Marinescu', 'Suite Concierge', 3000.00, '2021-11-05', '0738000333', 'bogdan.marinescu@example.com'),
(11004, 'Diana', 'Florescu', 'Luxury Suite Manager', 3200.00, '2020-07-20', '0739000444', 'diana.florescu@example.com'),
(11005, 'Horia', 'Dumitru', 'Room Service Supervisor', 2800.00, '2023-05-12', '0740000555', 'horia.dumitru@example.com'),
(11006, 'Larisa', 'Munteanu', 'VIP Guest Coordinator', 3100.00, '2022-03-18', '0741000666', 'larisa.munteanu@example.com'),
(11007, 'Vasile', 'Iordache', 'Maintenance Supervisor', 2600.00, '2023-01-22', '0742000777', 'vasile.iordache@example.com'),
(11008, 'Claudia', 'Nistor', 'Housekeeping Coordinator', 2500.00, '2024-01-08', '0743000888', 'claudia.nistor@example.com'),
(11009, 'Adrian', 'Voicu', 'Front Office Manager', 3500.00, '2019-08-15', '0744000999', 'adrian.voicu@example.com'),
(11010, 'Elena', 'Barbu', 'Reservations Specialist', 2900.00, '2022-06-30', '0745000000', 'elena.barbu@example.com');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`EmployeeID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
