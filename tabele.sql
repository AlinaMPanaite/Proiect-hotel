CREATE TABLE `customers` (
  `CustomerID` int(11) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Phone` varchar(15) DEFAULT NULL,
  `Address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


ALTER TABLE `customers`
  ADD PRIMARY KEY (`CustomerID`),
  ADD UNIQUE KEY `Email` (`Email`);


ALTER TABLE `customers`
  MODIFY `CustomerID` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;







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


ALTER TABLE `employees`
  ADD PRIMARY KEY (`EmployeeID`);
COMMIT;




CREATE TABLE `istoric_utilizare` (
  `IstoricID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `DataOra` datetime NOT NULL DEFAULT current_timestamp(),
  `Operatie` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


ALTER TABLE `istoric_utilizare`
  ADD PRIMARY KEY (`IstoricID`),
  ADD KEY `fk_userid_idx` (`UserID`);


ALTER TABLE `istoric_utilizare`
  MODIFY `IstoricID` int(11) NOT NULL AUTO_INCREMENT;


ALTER TABLE `istoric_utilizare`
  ADD CONSTRAINT `fk_user_istoric` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;







CREATE TABLE `roomreservations` (
  `ReservationID` int(11) NOT NULL,
  `CustomerID` int(11) NOT NULL,
  `RoomID` int(11) NOT NULL,
  `CheckInDate` date NOT NULL,
  `CheckOutDate` date NOT NULL,
  `TotalAmount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


ALTER TABLE `roomreservations`
  ADD PRIMARY KEY (`ReservationID`),
  ADD KEY `CustomerID` (`CustomerID`),
  ADD KEY `RoomID` (`RoomID`);


ALTER TABLE `roomreservations`
  MODIFY `ReservationID` int(11) NOT NULL AUTO_INCREMENT;


ALTER TABLE `roomreservations`
  ADD CONSTRAINT `roomreservations_ibfk_1` FOREIGN KEY (`CustomerID`) REFERENCES `customers` (`CustomerID`),
  ADD CONSTRAINT `roomreservations_ibfk_2` FOREIGN KEY (`RoomID`) REFERENCES `rooms` (`RoomID`);
COMMIT;











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


ALTER TABLE `rooms`
  ADD PRIMARY KEY (`RoomID`);


ALTER TABLE `rooms`
  MODIFY `RoomID` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;











CREATE TABLE `servicereservations` (
  `ServiceReservationID` int(11) NOT NULL,
  `CustomerID` int(11) NOT NULL,
  `ServiceID` int(11) NOT NULL,
  `ReservationDate` date NOT NULL,
  `OraRezervare` time DEFAULT NULL,
  `TotalAmount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


ALTER TABLE `servicereservations`
  ADD PRIMARY KEY (`ServiceReservationID`),
  ADD KEY `CustomerID` (`CustomerID`),
  ADD KEY `ServiceID` (`ServiceID`);


ALTER TABLE `servicereservations`
  MODIFY `ServiceReservationID` int(11) NOT NULL AUTO_INCREMENT;


ALTER TABLE `servicereservations`
  ADD CONSTRAINT `servicereservations_ibfk_1` FOREIGN KEY (`CustomerID`) REFERENCES `customers` (`CustomerID`),
  ADD CONSTRAINT `servicereservations_ibfk_2` FOREIGN KEY (`ServiceID`) REFERENCES `services` (`ServiceID`);
COMMIT;











CREATE TABLE `services` (
  `ServiceID` int(11) NOT NULL,
  `ServiceName` varchar(255) DEFAULT NULL,
  `ServiceDescription` text DEFAULT NULL,
  `Price` decimal(12,2) DEFAULT NULL,
  `ServiceDuration` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


ALTER TABLE `services`
  ADD PRIMARY KEY (`ServiceID`);
COMMIT;











CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `UserRole` enum('client','admin') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`);


ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;








