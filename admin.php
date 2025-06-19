<?php
session_start();

// Verifică dacă utilizatorul este autentificat și are rolul de admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Conectare la baza de date
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hotel";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Conexiunea la baza de date a eșuat: " . $e->getMessage());
}

// Inițializare variabile
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_istoric = isset($_GET['search_istoric']) ? trim($_GET['search_istoric']) : '';
$error = "";
$success = "";

// Definim coloanele valide pentru sortare pentru fiecare pagină
$valid_sort_columns = [
    'dashboard' => ['RoomID'],
    'rooms' => ['RoomID', 'RoomNumber', 'PricePerNight', 'Capacity', 'BedType'],
    'customers' => ['CustomerID', 'LastName', 'FirstName', 'Email', 'Phone'],
    'Roomreservations' => ['ReservationID', 'CustomerName', 'RoomNumber', 'CheckInDate', 'CheckOutDate'],
    'services' => ['ServiceID', 'ServiceName', 'Price'],
    'employees' => ['EmployeeID', 'LastName', 'FirstName', 'Position', 'Email', 'Phone', 'Salary'],
    'users' => [
        'users_UserID', 'users_LastName', 'users_FirstName', 'users_Email', 'users_UserRole',
        'istoric_IstoricID', 'istoric_UserID', 'istoric_DataOra', 'istoric_Operatie'
    ]
];

// Setăm sortarea implicită pentru fiecare pagină
$default_sort = '';
switch($page) {
    case 'rooms': $default_sort = 'RoomID'; break;
    case 'customers': $default_sort = 'CustomerID'; break;
    case 'Roomreservations': $default_sort = 'ReservationID'; break;
    case 'services': $default_sort = 'ServiceID'; break;
    case 'employees': $default_sort = 'EmployeeID'; break;
    case 'users': $default_sort = 'users_UserID'; break; // Implicit pentru users
    default: $default_sort = 'RoomID'; 
}

// Validăm sort_by și sort_order
$sort_by = isset($_GET['sort']) && in_array($_GET['sort'], $valid_sort_columns[$page]) ? $_GET['sort'] : $default_sort;
$sort_order = isset($_GET['order']) && in_array($_GET['order'], ['ASC', 'DESC']) ? $_GET['order'] : 'ASC';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$error = "";
$success = "";

// Determinăm tabela și coloana efectivă pentru sortare
$sort_table = 'users'; // Implicit pentru users
$sort_column = $sort_by;
if (strpos($sort_by, 'istoric_') === 0) {
    $sort_table = 'istoric';
    $sort_column = substr($sort_by, strlen('istoric_')); // Scoatem prefixul
} elseif (strpos($sort_by, 'users_') === 0) {
    $sort_table = 'users';
    $sort_column = substr($sort_by, strlen('users_')); // Scoatem prefixul
}

// Procesare ștergere
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = $_GET['id'];
    $table = $_GET['table'];
    
    try {
        $id_field = '';
        switch ($table) {
            case 'Rooms':
                $id_field = 'RoomID';
                break;
            case 'Customers':
                $id_field = 'CustomerID';
                break;
            case 'Roomreservations':
                $id_field = 'ReservationID';
                break;
            case 'Services':
                $id_field = 'ServiceID';
                break;
            case 'Employees':
                $id_field = 'EmployeeID';
                break;
            case 'Users':
                $id_field = 'UserID';
                break;
        }
        
        $conn->beginTransaction();
        
        // Pentru clienți, ștergem și din users
        if ($table === 'Customers') {
            $stmt = $conn->prepare("DELETE FROM users WHERE UserID = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
        }
        
        $stmt = $conn->prepare("DELETE FROM $table WHERE $id_field = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        // Înregistrăm acțiunea în istoric
        $stmt = $conn->prepare("INSERT INTO istoric_utilizare (UserID, Operatie) VALUES (:user_id, :operatie)");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $operatie = "Ștergere $table ID $id";
        $stmt->bindParam(':operatie', $operatie);
        $stmt->execute();
        
        $conn->commit();
        
        $success = "Înregistrarea a fost ștearsă cu succes!";
    } catch(PDOException $e) {
        $conn->rollBack();
        $error = "Eroare la ștergere: " . $e->getMessage();
    }
}

// Procesare adăugare/editare cameră
if (isset($_POST['save_room'])) {
    $room_id = $_POST['room_id'] ?? '';
    $room_number = trim($_POST['room_number']);
    $price = trim($_POST['price']);
    $capacity = trim($_POST['capacity']);
    $bed_type = trim($_POST['bed_type']);
    $has_ac = isset($_POST['has_ac']) ? 1 : 0;
    $has_balcony = isset($_POST['has_balcony']) ? 1 : 0;
    $description = trim($_POST['description']);
    
    try {
        if (empty($room_number) || empty($price) || empty($capacity) || empty($bed_type)) {
            throw new Exception("Toate câmpurile obligatorii trebuie completate!");
        }
        
        if (empty($room_id)) {
            // Adăugare cameră nouă
            $stmt = $conn->prepare("
                INSERT INTO Rooms (RoomNumber, PricePerNight, Capacity, BedType, HasAC, HasBalcony, Description)
                VALUES (:room_number, :price, :capacity, :bed_type, :has_ac, :has_balcony, :description)
            ");
        } else {
            // Editare cameră existentă
            $stmt = $conn->prepare("
                UPDATE Rooms SET 
                    RoomNumber = :room_number,
                    PricePerNight = :price,
                    Capacity = :capacity,
                    BedType = :bed_type,
                    HasAC = :has_ac,
                    HasBalcony = :has_balcony,
                    Description = :description
                WHERE RoomID = :room_id
            ");
            $stmt->bindParam(':room_id', $room_id);
        }
        
        $stmt->bindParam(':room_number', $room_number);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':capacity', $capacity);
        $stmt->bindParam(':bed_type', $bed_type);
        $stmt->bindParam(':has_ac', $has_ac);
        $stmt->bindParam(':has_balcony', $has_balcony);
        $stmt->bindParam(':description', $description);
        $stmt->execute();
        
        // Înregistrăm acțiunea în istoric
        $stmt = $conn->prepare("INSERT INTO istoric_utilizare (UserID, Operatie) VALUES (:user_id, :operatie)");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $operatie = empty($room_id) ? "Adăugare cameră $room_number" : "Editare cameră $room_number";
        $stmt->bindParam(':operatie', $operatie);
        $stmt->execute();
        
        $success = "Camera a fost " . (empty($room_id) ? "adăugată" : "actualizată") . " cu succes!";
    } catch(PDOException $e) {
        $error = "Eroare: " . $e->getMessage();
    } catch(Exception $e) {
        $error = "Eroare: " . $e->getMessage();
    }
}

// Procesare adăugare/editare client
if (isset($_POST['save_customer'])) {
    $customer_id = $_POST['customer_id'] ?? '';
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    try {
        // Validare
        if (empty($first_name) || empty($last_name) || empty($email)) {
            throw new Exception("Prenumele, numele și email-ul sunt obligatorii!");
        }
        
        // Verificăm unicitatea email-ului în users
        $stmt = $conn->prepare("SELECT UserID FROM users WHERE Email = :email AND UserID != :user_id");
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $customer_id ?: 0, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            throw new Exception("Acest email este deja folosit de alt utilizator!");
        }
        
        // Începe tranzacție
        $conn->beginTransaction();
        
        if (empty($customer_id)) {
            // Adăugare client nou
            // Generăm un UserID nou dacă nu există
            $stmt = $conn->query("SELECT MAX(UserID) + 1 AS new_id FROM users");
            $customer_id = $stmt->fetchColumn() ?: 1;
            
            // Creăm utilizator în users cu parolă implicită și rol 'client'
            $default_password = password_hash('Client123!', PASSWORD_DEFAULT);
            $role = 'client';
            $stmt = $conn->prepare("
                INSERT INTO users (UserID, FirstName, LastName, Email, Password, UserRole)
                VALUES (:user_id, :first_name, :last_name, :email, :password, :role)
            ");
            $stmt->bindValue(':user_id', $customer_id, PDO::PARAM_INT);
            $stmt->bindValue(':first_name', $first_name, PDO::PARAM_STR);
            $stmt->bindValue(':last_name', $last_name, PDO::PARAM_STR);
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->bindValue(':password', $default_password, PDO::PARAM_STR);
            $stmt->bindValue(':role', $role, PDO::PARAM_STR);
            $stmt->execute();
            
            // Adăugăm în customers
            $stmt = $conn->prepare("
                INSERT INTO Customers (CustomerID, FirstName, LastName, Email, Phone, Address)
                VALUES (:customer_id, :first_name, :last_name, :email, :phone, :address)
            ");
        } else {
            // Editare client existent
            // Verificăm dacă clientul există în customers
            $stmt = $conn->prepare("SELECT COUNT(*) FROM Customers WHERE CustomerID = :customer_id");
            $stmt->bindValue(':customer_id', $customer_id, PDO::PARAM_INT);
            $stmt->execute();
            $exists = $stmt->fetchColumn();
            
            if (!$exists) {
                throw new Exception("Clientul cu ID-ul specificat nu există!");
            }
            
            // Actualizăm customers
            $stmt = $conn->prepare("
                UPDATE Customers SET 
                    FirstName = :first_name,
                    LastName = :last_name,
                    Email = :email,
                    Phone = :phone,
                    Address = :address
                WHERE CustomerID = :customer_id
            ");
            
            // Actualizăm users
            $stmt_user = $conn->prepare("
                UPDATE users SET 
                    FirstName = :first_name,
                    LastName = :last_name,
                    Email = :email
                WHERE UserID = :user_id
            ");
            $stmt_user->bindValue(':user_id', $customer_id, PDO::PARAM_INT);
            $stmt_user->bindValue(':first_name', $first_name, PDO::PARAM_STR);
            $stmt_user->bindValue(':last_name', $last_name, PDO::PARAM_STR);
            $stmt_user->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt_user->execute();
        }
        
        // Legăm parametrii pentru customers
        $stmt->bindValue(':customer_id', $customer_id, PDO::PARAM_INT);
        $stmt->bindValue(':first_name', $first_name, PDO::PARAM_STR);
        $stmt->bindValue(':last_name', $last_name, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':phone', $phone, PDO::PARAM_STR);
        $stmt->bindValue(':address', $address, PDO::PARAM_STR);
        $stmt->execute();
        
        // Înregistrăm acțiunea în istoric
        $stmt = $conn->prepare("INSERT INTO istoric_utilizare (UserID, Operatie) VALUES (:user_id, :operatie)");
        $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $operatie = empty($customer_id) ? "Adăugare client $first_name $last_name" : "Editare client $first_name $last_name";
        $stmt->bindValue(':operatie', $operatie, PDO::PARAM_STR);
        $stmt->execute();
        
        // Confirmăm tranzacția
        $conn->commit();
        
        $success = "Clientul a fost " . (empty($customer_id) ? "adăugat" : "actualizat") . " cu succes!";
    } catch(PDOException $e) {
        $conn->rollBack();
        $error = "Eroare: " . $e->getMessage();
    } catch(Exception $e) {
        $conn->rollBack();
        $error = "Eroare: " . $e->getMessage();
    }
}

// Procesare adăugare/editare serviciu
if (isset($_POST['save_service'])) {
    $service_id = $_POST['service_id'] ?? '';
    $service_name = trim($_POST['service_name']);
    $price = trim($_POST['price']);
    $servicedescription = trim($_POST['servicedescription']);
    
    try {
        if (empty($service_name) || empty($price)) {
            throw new Exception("Numele serviciului și prețul sunt obligatorii!");
        }
        
        if (empty($service_id)) {
            // Adăugare serviciu nou
            $stmt = $conn->prepare("
                INSERT INTO Services (ServiceName, Price, ServiceDescription)
                VALUES (:service_name, :price, :servicedescription)
            ");
        } else {
            // Editare serviciu existent
            $stmt = $conn->prepare("
                UPDATE Services SET 
                    ServiceName = :service_name,
                    Price = :price,
                    ServiceDescription = :servicedescription
                WHERE ServiceID = :service_id
            ");
            $stmt->bindParam(':service_id', $service_id);
        }
        
        $stmt->bindParam(':service_name', $service_name);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':servicedescription', $servicedescription);
        $stmt->execute();
        
        // Înregistrăm acțiunea în istoric
        $stmt = $conn->prepare("INSERT INTO istoric_utilizare (UserID, Operatie) VALUES (:user_id, :operatie)");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $operatie = empty($service_id) ? "Adăugare serviciu $service_name" : "Editare serviciu $service_name";
        $stmt->bindParam(':operatie', $operatie);
        $stmt->execute();
        
        $success = "Serviciul a fost " . (empty($service_id) ? "adăugat" : "actualizat") . " cu succes!";
    } catch(PDOException $e) {
        $error = "Eroare: " . $e->getMessage();
    } catch(Exception $e) {
        $error = "Eroare: " . $e->getMessage();
    }
}

// Procesare adăugare/editare angajat
if (isset($_POST['save_employee'])) {
    $employee_id = $_POST['employee_id'] ?? '';
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $position = trim($_POST['position']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $salary = trim($_POST['salary']);
    
    try {
        if (empty($first_name) || empty($last_name) || empty($position) || empty($email) || empty($phone) || empty($salary)) {
            throw new Exception("Toate câmpurile sunt obligatorii!");
        }
        
        if (empty($employee_id)) {
            // Adăugare angajat nou
            $stmt = $conn->prepare("
                INSERT INTO Employees (FirstName, LastName, Position, Email, Phone, Salary)
                VALUES (:first_name, :last_name, :position, :email, :phone, :salary)
            ");
        } else {
            // Editare angajat existent
            $stmt = $conn->prepare("
                UPDATE Employees SET 
                    FirstName = :first_name,
                    LastName = :last_name,
                    Position = :position,
                    Email = :email,
                    Phone = :phone,
                    Salary = :salary
                WHERE EmployeeID = :employee_id
            ");
            $stmt->bindParam(':employee_id', $employee_id);
        }
        
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':position', $position);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':salary', $salary);
        $stmt->execute();
        
        // Înregistrăm acțiunea în istoric
        $stmt = $conn->prepare("INSERT INTO istoric_utilizare (UserID, Operatie) VALUES (:user_id, :operatie)");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $operatie = empty($employee_id) ? "Adăugare angajat $first_name $last_name" : "Editare angajat $first_name $last_name";
        $stmt->bindParam(':operatie', $operatie);
        $stmt->execute();
        
        $success = "Angajatul a fost " . (empty($employee_id) ? "adăugat" : "actualizat") . " cu succes!";
    } catch(PDOException $e) {
        $error = "Eroare: " . $e->getMessage();
    } catch(Exception $e) {
        $error = "Eroare: " . $e->getMessage();
    }
}

// Procesare adăugare/editare utilizator
if (isset($_POST['save_user'])) {
    $user_id = $_POST['user_id'] ?? '';
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = isset($_POST['password']) && !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
    $role = trim($_POST['role']);
    
    try {
        if (empty($first_name) || empty($last_name) || empty($email) || empty($role)) {
            throw new Exception("Numele, prenumele, email-ul și rolul sunt obligatorii!");
        }
        
        if (empty($user_id) && $password === null) {
            throw new Exception("Parola este obligatorie pentru utilizatorii noi!");
        }
        
      // Verificăm unicitatea email-ului
$stmt = $conn->prepare("SELECT UserID FROM users WHERE Email = :email AND UserID != :user_id");
$stmt->bindParam(':email', $email);
$user_id_param = $user_id ?: 0; // Stocăm valoarea într-o variabilă
$stmt->bindParam(':user_id', $user_id_param, PDO::PARAM_INT); // Specificăm tipul de dată
$stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            throw new Exception("Acest email este deja folosit de alt utilizator!");
        }
        
        if (empty($user_id)) {
            // Adăugare utilizator nou
            $stmt = $conn->prepare("
                INSERT INTO Users (FirstName, LastName, Email, Password, UserRole)
                VALUES (:first_name, :last_name, :email, :password, :role)
            ");
            $stmt->bindParam(':password', $password);
        } else {
            // Editare utilizator existent
            if ($password === null) {
                $stmt = $conn->prepare("
                    UPDATE Users SET 
                        FirstName = :first_name,
                        LastName = :last_name,
                        Email = :email,
                        UserRole = :role
                    WHERE UserID = :user_id
                ");
            } else {
                $stmt = $conn->prepare("
                    UPDATE Users SET 
                        FirstName = :first_name,
                        LastName = :last_name,
                        Email = :email,
                        Password = :password,
                        UserRole = :role
                    WHERE UserID = :user_id
                ");
                $stmt->bindParam(':password', $password);
            }
            $stmt->bindParam(':user_id', $user_id);
        }
        
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':role', $role);
        $stmt->execute();
        
        // Înregistrăm acțiunea în istoric
        $stmt = $conn->prepare("INSERT INTO istoric_utilizare (UserID, Operatie) VALUES (:user_id, :operatie)");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $operatie = empty($user_id) ? "Adăugare utilizator $first_name $last_name" : "Editare utilizator $first_name $last_name";
        $stmt->bindParam(':operatie', $operatie);
        $stmt->execute();
        
        $success = "Utilizatorul a fost " . (empty($user_id) ? "adăugat" : "actualizat") . " cu succes!";
    } catch(PDOException $e) {
        $error = "Eroare: " . $e->getMessage();
    } catch(Exception $e) {
        $error = "Eroare: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel - Portal Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
    .alert.alert-success {
        background-color: #4CAF50;
        color: white;
        padding: 15px;
        margin: 10px 0;
        border-radius: 5px;
        position: relative;
        animation: slideIn 0.5s ease-in-out;
        opacity: 1;
        transition: opacity 0.5s ease-out;
    }

    .alert.alert-success::after {
        content: '×';
        position: absolute;
        top: 10px;
        right: 15px;
        cursor: pointer;
        font-size: 18px;
    }

    @keyframes slideIn {
        from {
            transform: translateY(-20px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .alert.alert-success.auto-hide {
        opacity: 0;
    }

    .alert.alert-error {
        background-color: #f44336;
        color: white;
        padding: 15px;
        margin: 10px 0;
        border-radius: 5px;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.4);
    }

    .modal-content {
        background-color: #fefefe;
        margin: 15% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 600px;
        border-radius: 5px;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #ddd;
        padding-bottom: 10px;
    }

    .modal-body {
        padding: 20px 0;
    }

    .close-btn {
        color: #aaa;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .close-btn:hover {
        color: #000;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-label {
        display: block;
        margin-bottom: 5px;
    }

    .form-control {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
    }

    .form-check {
        margin-bottom: 10px;
    }

    .form-check-input {
        margin-right: 10px;
    }

    .btn {
        padding: 8px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        margin-right: 5px;
    }

    .btn-success {
        background-color: #4CAF50;
        color: white;
    }

    .btn-warning {
        background-color: #ff9800;
        color: white;
    }

    .btn-info {
        background-color: #2196F3;
        color: white;
    }

    .action-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 5px;
        margin: 0 5px;
    }

    .edit-btn {
        color: #2196F3;
    }

    .delete-btn {
        color: #f44336;
    }

    .table-responsive {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th,
    td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    th {
        background-color: #f5f5f5;
    }

    tr:hover {
        background-color: #f9f9f9;
    }
    .search-form select.form-control {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-right: 10px;
}
    </style>
</head>

<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <h1>Hotel Management - Panel Manager</h1>
            </div>
            <div class="user-menu">
                <span>Bine ai venit, <?php echo htmlspecialchars($_SESSION['email']); ?>!</span>
                <a href="main.php" class="logout-btn">Deconectare</a>
            </div>
        </div>
    </header>

    <div class="container">
        <nav>
            <div class="nav-container">
                <div class="nav-links">
                    <a href="?page=dashboard" class="<?php echo $page === 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="?page=rooms" class="<?php echo $page === 'rooms' ? 'active' : ''; ?>">
                        <i class="fas fa-bed"></i> Camere
                    </a>
                    <a href="?page=customers" class="<?php echo $page === 'customers' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Clienți
                    </a>
                    <a href="?page=Roomreservations"
                        class="<?php echo $page === 'Roomreservations' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check"></i> Rezervări
                    </a>
                    <a href="?page=services" class="<?php echo $page === 'services' ? 'active' : ''; ?>">
                        <i class="fas fa-concierge-bell"></i> Servicii
                    </a>
                    <a href="?page=employees" class="<?php echo $page === 'employees' ? 'active' : ''; ?>">
                        <i class="fas fa-user-tie"></i> Angajați
                    </a>
                    <a href="?page=users" class="<?php echo $page === 'users' ? 'active' : ''; ?>">
                        <i class="fas fa-user-cog"></i> Utilizatori
                    </a>
                </div>
            </div>
        </nav>

        <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <?php if ($page === 'dashboard'): ?>
        <!-- Dashboard -->
        <div class="card">
            <div class="card-header">
                <div>Dashboard</div>
            </div>
            <div class="card-body">
                <div class="dashboard-stats">
                    <?php
                    try {
                        // Număr de camere
                        $stmt = $conn->query("SELECT COUNT(*) as count FROM Rooms");
                        $rooms_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        
                        // Număr de clienți
                        $stmt = $conn->query("SELECT COUNT(*) as count FROM Customers");
                        $customers_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        
                        // Număr de rezervări
                        $stmt = $conn->query("SELECT COUNT(*) as count FROM Roomreservations");
                        $roomreservations_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        
                        // Venituri totale
                        $stmt = $conn->query("SELECT SUM(TotalAmount) as total FROM Roomreservations");
                        $total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
                        
                        // Număr de angajați
                        $stmt = $conn->query("SELECT COUNT(*) as count FROM Employees");
                        $employees_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        
                        // Număr de servicii
                        $stmt = $conn->query("SELECT COUNT(*) as count FROM Services");
                        $services_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    } catch(PDOException $e) {
                        echo "Eroare: " . htmlspecialchars($e->getMessage());
                    }
                    ?>
                    <div class="stat-card">
                        <i class="fas fa-bed"></i>
                        <div class="stat-value"><?php echo $rooms_count; ?></div>
                        <div class="stat-label">Camere</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-users"></i>
                        <div class="stat-value"><?php echo $customers_count; ?></div>
                        <div class="stat-label">Clienți</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-calendar-check"></i>
                        <div class="stat-value"><?php echo $roomreservations_count; ?></div>
                        <div class="stat-label">Rezervări</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-money-bill-wave"></i>
                        <div class="stat-value"><?php echo number_format($total_revenue, 2); ?> RON</div>
                        <div class="stat-label">Venit total</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-user-tie"></i>
                        <div class="stat-value"><?php echo $employees_count; ?></div>
                        <div class="stat-label">Angajați</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-concierge-bell"></i>
                        <div class="stat-value"><?php echo $services_count; ?></div>
                        <div class="stat-label">Servicii</div>
                    </div>
                </div>

                <div class="card-actions">
                    <a href="grafice.php" class="btn btn-success">
                        <i class="fas fa-chart-line"></i> Vizualizare grafice
                    </a>
                    <a href="raport.php" class="btn btn-success">
                        <i class="fas fa-chart-bar"></i> Rapoarte
                    </a>

                </div>
            </div>
        </div>

        <!-- Ultimele rezervări -->
        <div class="card">
            <div class="card-header">
                <div>Ultimele rezervări</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Cameră</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $stmt = $conn->query("
                                    SELECT r.*, c.FirstName, c.LastName, rm.RoomNumber
                                    FROM Roomreservations r
                                    JOIN Customers c ON r.CustomerID = c.CustomerID
                                    JOIN Rooms rm ON r.RoomID = rm.RoomID
                                    ORDER BY r.CheckInDate DESC
                                    LIMIT 5
                                ");
                                
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['ReservationID']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['RoomNumber']) . "</td>";
                                    echo "<td>" . date('d.m.Y', strtotime($row['CheckInDate'])) . "</td>";
                                    echo "<td>" . date('d.m.Y', strtotime($row['CheckOutDate'])) . "</td>";
                                    echo "<td>" . number_format($row['TotalAmount'], 2) . " RON</td>";
                                    echo "</tr>";
                                }
                            } catch(PDOException $e) {
                                echo "<tr><td colspan='6'>Eroare: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php elseif ($page === 'rooms'): ?>
        <!-- Camere -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Gestionare camere</div>
                <div class="card-actions">
                    <button class="btn btn-success" onclick="openModal('roomModal')">
                        <i class="fas fa-plus"></i> Adaugă cameră
                    </button>
                    <a href="export_pdf.php?table=Rooms" class="btn btn-warning">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a>
                    <a href="export_xls.php?table=Rooms" class="btn">
                        <i class="fas fa-file-excel"></i> Export XLS
                    </a>
                    <a href="import_xls.php?table=Rooms" class="btn">
                        <i class="fas fa-file-import"></i> Import XLS
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="" method="GET" class="search-form">
                    <input type="hidden" name="page" value="rooms">
                    <input type="text" name="search" placeholder="Caută..."
                        value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn">Caută</button>
                    <?php if (!empty($search)): ?>
                    <a href="?page=rooms" class="btn btn-warning">Resetează</a>
                    <?php endif; ?>
                </form>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th><a
                                        href="?page=rooms&sort=RoomID&order=<?php echo $sort_by === 'RoomID' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>">ID
                                        <?php echo $sort_by === 'RoomID' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=rooms&sort=RoomNumber&order=<?php echo $sort_by === 'RoomNumber' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>">Număr
                                        cameră
                                        <?php echo $sort_by === 'RoomNumber' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=rooms&sort=PricePerNight&order=<?php echo $sort_by === 'PricePerNight' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>">Preț/noapte
                                        <?php echo $sort_by === 'PricePerNight' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=rooms&sort=Capacity&order=<?php echo $sort_by === 'Capacity' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>">Capacitate
                                        <?php echo $sort_by === 'Capacity' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=rooms&sort=BedType&order=<?php echo $sort_by === 'BedType' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>">Tip
                                        pat
                                        <?php echo $sort_by === 'BedType' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th>Facilități</th>
                                <th>Descriere</th>
                                <th>Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $query = "SELECT * FROM Rooms WHERE 1=1";
                                
                                if (!empty($search)) {
                                    $query .= " AND (RoomNumber LIKE :search OR BedType LIKE :search OR Description LIKE :search)";
                                }
                                
                                $query .= " ORDER BY $sort_by $sort_order";
                                
                                $stmt = $conn->prepare($query);
                                
                                if (!empty($search)) {
                                    $search_param = '%' . $search . '%';
                                    $stmt->bindParam(':search', $search_param);
                                }
                                
                                $stmt->execute();
                                
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['RoomID']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['RoomNumber']) . "</td>";
                                    echo "<td>" . number_format($row['PricePerNight'], 2) . " RON</td>";
                                    echo "<td>" . htmlspecialchars($row['Capacity']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['BedType']) . "</td>";
                                    echo "<td>";
                                    if ($row['HasAC']) echo '<i class="fas fa-wind"></i> AC<br>';
                                    if ($row['HasBalcony']) echo '<i class="fas fa-door-open"></i> Balcon';
                                    echo "</td>";
                                    echo "<td>" . (strlen($row['Description']) > 50 ? htmlspecialchars(substr($row['Description'], 0, 50)) . '...' : htmlspecialchars($row['Description'])) . "</td>";
                                    echo "<td class='actions'>";
                                    echo "<button class='action-btn edit-btn' onclick='editRoom({$row['RoomID']}, \"" . htmlspecialchars($row['RoomNumber'], ENT_QUOTES) . "\", {$row['PricePerNight']}, {$row['Capacity']}, \"" . htmlspecialchars($row['BedType'], ENT_QUOTES) . "\", {$row['HasAC']}, {$row['HasBalcony']}, \"" . htmlspecialchars($row['Description'], ENT_QUOTES) . "\")'><i class='fas fa-edit'></i></button>";
                                    echo "<a href='?page=rooms&action=delete&table=Rooms&id={$row['RoomID']}' class='action-btn delete-btn' onclick='return confirm(\"Sigur doriți să ștergeți această cameră?\")'><i class='fas fa-trash'></i></a>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } catch(PDOException $e) {
                                echo "<tr><td colspan='8'>Eroare: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal pentru adăugare/editare cameră -->
        <div id="roomModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="roomModalTitle">Adaugă cameră nouă</h3>
                    <button class="close-btn" onclick="closeModal('roomModal')">×</button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" id="room_id" name="room_id">

                        <div class="form-group">
                            <label for="room_number" class="form-label">Număr cameră</label>
                            <input type="text" id="room_number" name="room_number" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="price" class="form-label">Preț per noapte (RON)</label>
                            <input type="number" id="price" name="price" class="form-control" step="0.01" required>
                        </div>

                        <div class="form-group">
                            <label for="capacity" class="form-label">Capacitate</label>
                            <input type="number" id="capacity" name="capacity" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="bed_type" class="form-label">Tip pat</label>
                            <select id="bed_type" name="bed_type" class="form-control" required>
                                <option value="Single">Single</option>
                                <option value="Double">Double</option>
                                <option value="Queen">Queen</option>
                                <option value="King">King</option>
                                <option value="Twin">Twin</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" id="has_ac" name="has_ac" class="form-check-input">
                                <label for="has_ac" class="form-check-label">Are aer condiționat</label>
                            </div>

                            <div class="form-check">
                                <input type="checkbox" id="has_balcony" name="has_balcony" class="form-check-input">
                                <label for="has_balcony" class="form-check-label">Are balcon</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Descriere</label>
                            <textarea id="description" name="description" class="form-control" rows="4"></textarea>
                        </div>

                        <button type="submit" name="save_room" class="btn btn-success">Salvează</button>
                    </form>
                </div>
            </div>
        </div>

        <?php elseif ($page === 'customers'): ?>
        <!-- Clienți -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Gestionare clienți</div>
                <div class="card-actions">
                    <button class="btn btn-success" onclick="openModal('customerModal')">
                        <i class="fas fa-plus"></i> Adaugă client
                    </button>
                    <a href="export_pdf.php?table=Customers" class="btn btn-warning">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a>
                    <a href="export_xls.php?table=Customers" class="btn">
                        <i class="fas fa-file-excel"></i> Export XLS
                    </a>
                    <a href="import_xls.php?table=Customers" class="btn">
                        <i class="fas fa-file-import"></i> Import XLS
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="" method="GET" class="search-form">
                    <input type="hidden" name="page" value="customers">
                    <input type="text" name="search" placeholder="Caută..."
                        value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn">Caută</button>
                    <?php if (!empty($search)): ?>
                    <a href="?page=customers" class="btn btn-warning">Resetează</a>
                    <?php endif; ?>
                </form>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th><a
                                        href="?page=customers&sort=CustomerID&order=<?php echo $sort_by === 'CustomerID' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>">ID
                                        <?php echo $sort_by === 'CustomerID' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=customers&sort=LastName&order=<?php echo $sort_by === 'LastName' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>">Nume
                                        <?php echo $sort_by === 'LastName' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=customers&sort=FirstName&order=<?php echo $sort_by === 'FirstName' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>">Prenume
                                        <?php echo $sort_by === 'FirstName' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=customers&sort=Email&order=<?php echo $sort_by === 'Email' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>">Email
                                        <?php echo $sort_by === 'Email' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=customers&sort=Phone&order=<?php echo $sort_by === 'Phone' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>">Telefon
                                        <?php echo $sort_by === 'Phone' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th>Adresă</th>
                                <th>Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $query = "SELECT * FROM Customers WHERE 1=1";
                                
                                if (!empty($search)) {
                                    $query .= " AND (FirstName LIKE :search OR LastName LIKE :search OR Email LIKE :search OR Phone LIKE :search OR Address LIKE :search)";
                                }
                                
                                $query .= " ORDER BY $sort_by $sort_order";
                                
                                $stmt = $conn->prepare($query);
                                
                                if (!empty($search)) {
                                    $search_param = '%' . $search . '%';
                                    $stmt->bindParam(':search', $search_param);
                                }
                                
                                $stmt->execute();
                                
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['CustomerID']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['LastName']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['FirstName']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['Email']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['Phone']) . "</td>";
                                    echo "<td>" . (strlen($row['Address']) > 30 ? htmlspecialchars(substr($row['Address'], 0, 30)) . '...' : htmlspecialchars($row['Address'])) . "</td>";
                                    echo "<td class='actions'>";
                                    echo "<button class='action-btn edit-btn' onclick='editCustomer({$row['CustomerID']}, \"" . htmlspecialchars($row['FirstName'], ENT_QUOTES) . "\", \"" . htmlspecialchars($row['LastName'], ENT_QUOTES) . "\", \"" . htmlspecialchars($row['Email'], ENT_QUOTES) . "\", \"" . htmlspecialchars($row['Phone'], ENT_QUOTES) . "\", \"" . htmlspecialchars($row['Address'], ENT_QUOTES) . "\")'><i class='fas fa-edit'></i></button>";
                                    echo "<a href='?page=customers&action=delete&table=Customers&id={$row['CustomerID']}' class='action-btn delete-btn' onclick='return confirm(\"Sigur doriți să ștergeți acest client?\")'><i class='fas fa-trash'></i></a>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } catch(PDOException $e) {
                                echo "<tr><td colspan='7'>Eroare: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal pentru adăugare/editare client -->
        <div id="customerModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="customerModalTitle">Adaugă client nou</h3>
                    <button class="close-btn" onclick="closeModal('customerModal')">×</button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" id="customer_id" name="customer_id">

                        <div class="form-group">
                            <label for="first_name" class="form-label">Prenume</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="last_name" class="form-label">Nume</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="phone" class="form-label">Telefon</label>
                            <input type="text" id="phone" name="phone" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="address" class="form-label">Adresă</label>
                            <textarea id="address" name="address" class="form-control" rows="3"></textarea>
                        </div>

                        <button type="submit" name="save_customer" class="btn btn-success">Salvează</button>
                    </form>
                </div>
            </div>
        </div>

        <?php elseif ($page === 'Roomreservations'): ?>
        <!-- Rezervari -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Gestionare rezervări</div>
                <div class="card-actions">
                    <button class="btn btn-success" onclick="openModal('reservationModal')">
                        <i class="fas fa-plus"></i> Adaugă rezervare
                    </button>
                    <a href="export_pdf.php?table=Roomreservations" class="btn btn-warning">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a>
                    <a href="export_xls.php?table=Roomreservations" class="btn">
                        <i class="fas fa-file-excel"></i> Export XLS
                    </a>
                    <a href="import_xls.php?table=Roomreservations" class="btn">
                        <i class="fas fa-file-import"></i> Import XLS
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="" method="GET" class="search-form">
                    <input type="hidden" name="page" value="Roomreservations">
                    <select name="search_type" class="form-control" style="width: 150px; display: inline-block;">
                        <option value="name"
                            <?php echo (isset($_GET['search_type']) && $_GET['search_type'] === 'name') ? 'selected' : ''; ?>>
                            Nume client</option>
                        <option value="reservation_id"
                            <?php echo (isset($_GET['search_type']) && $_GET['search_type'] === 'reservation_id') ? 'selected' : ''; ?>>
                            ID rezervare</option>
                        <option value="customer_id"
                            <?php echo (isset($_GET['search_type']) && $_GET['search_type'] === 'customer_id') ? 'selected' : ''; ?>>
                            ID client</option>
                        <option value="service"
                            <?php echo (isset($_GET['search_type']) && $_GET['search_type'] === 'service') ? 'selected' : ''; ?>>
                            Serviciu</option>
                    </select>
                    <input type="text" name="search" placeholder="Caută..."
                        value="<?php echo htmlspecialchars($search); ?>" class="form-control"
                        style="width: 200px; display: inline-block;">
                    <button type="submit" class="btn">Caută</button>
                    <?php if (!empty($search)): ?>
                    <a href="?page=Roomreservations" class="btn btn-warning">Resetează</a>
                    <?php endif; ?>
                </form>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th><a
                                        href="?page=Roomreservations&sort=ReservationID&order=<?php echo $sort_by === 'ReservationID' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>">ID
                                        <?php echo $sort_by === 'ReservationID' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=Roomreservations&sort=CustomerName&order=<?php echo $sort_by === 'CustomerName' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>">Client
                                        <?php echo $sort_by === 'CustomerName' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=Roomreservations&sort=RoomNumber&order=<?php echo $sort_by === 'RoomNumber' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>">Cameră
                                        <?php echo $sort_by === 'RoomNumber' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=Roomreservations&sort=CheckInDate&order=<?php echo $sort_by === 'CheckInDate' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>">Check-in
                                        <?php echo $sort_by === 'CheckInDate' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=Roomreservations&sort=CheckOutDate&order=<?php echo $sort_by === 'CheckOutDate' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>">Check-out
                                        <?php echo $sort_by === 'CheckOutDate' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th>Total Cameră (RON)</th>
                                <th>Total Servicii (RON)</th>
                                <th>Total General (RON)</th>
                                <th>Servicii</th>
                                <th>Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
try {
    $search_type = isset($_GET['search_type']) ? $_GET['search_type'] : 'name';
    
    $query = "
        SELECT 
            rr.*, 
            CONCAT(c.FirstName, ' ', c.LastName) AS CustomerName, 
            rm.RoomNumber
        FROM Roomreservations rr
        JOIN Customers c ON rr.CustomerID = c.CustomerID
        JOIN Rooms rm ON rr.RoomID = rm.RoomID
        WHERE 1=1
    ";
    
    if (!empty($search)) {
        if ($search_type === 'reservation_id') {
            $query .= " AND rr.ReservationID = :search";
        } elseif ($search_type === 'customer_id') {
            $query .= " AND rr.CustomerID = :search";
        } elseif ($search_type === 'service') {
            $query .= " AND EXISTS (
                SELECT 1 FROM ServiceReservations sr
                JOIN Services s ON sr.ServiceID = s.ServiceID
                WHERE sr.CustomerID = rr.CustomerID
                AND sr.ReservationDate BETWEEN rr.CheckInDate AND rr.CheckOutDate
                AND s.ServiceName LIKE :search
            )";
        } else {
            $query .= " AND (CONCAT(c.FirstName, ' ', c.LastName) LIKE :search OR rm.RoomNumber LIKE :search)";
        }
    }
    
    if ($sort_by === 'CustomerName') {
        $query .= " ORDER BY CustomerName $sort_order";
    } elseif ($sort_by === 'RoomNumber') {
        $query .= " ORDER BY rm.RoomNumber $sort_order";
    } else {
        $query .= " ORDER BY rr.$sort_by $sort_order";
    }
    
    $stmt = $conn->prepare($query);
    
    if (!empty($search)) {
        if ($search_type === 'reservation_id' || $search_type === 'customer_id') {
            $stmt->bindValue(':search', (int)$search, PDO::PARAM_INT);
        } else {
            $search_param = '%' . $search . '%';
            $stmt->bindValue(':search', $search_param, PDO::PARAM_STR);
        }
    }
    
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        echo "<tr><td colspan='10'>Nicio rezervare găsită.</td></tr>";
    }
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $services_query = "
            SELECT s.ServiceName, sr.TotalAmount, sr.ReservationDate
            FROM ServiceReservations sr
            JOIN Services s ON sr.ServiceID = s.ServiceID
            WHERE sr.CustomerID = :customer_id
            AND sr.ReservationDate BETWEEN :checkin AND :checkout
        ";
        
        $services_stmt = $conn->prepare($services_query);
        $services_stmt->bindValue(':customer_id', $row['CustomerID'], PDO::PARAM_INT);
        $services_stmt->bindValue(':checkin', $row['CheckInDate'], PDO::PARAM_STR);
        $services_stmt->bindValue(':checkout', $row['CheckOutDate'], PDO::PARAM_STR);
        $services_stmt->execute();
        $services = $services_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $services_total = 0;
        $services_display = [];
        foreach ($services as $service) {
            $services_total += (float)$service['TotalAmount'];
            $services_display[] = htmlspecialchars($service['ServiceName']) . ' (' . 
                date('d.m.Y', strtotime($service['ReservationDate'])) . ', ' . 
                number_format($service['TotalAmount'], 2) . ' RON)';
        }
        
        $grand_total = (float)$row['TotalAmount'] + $services_total;
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['ReservationID']) . "</td>";
        echo "<td>" . htmlspecialchars($row['CustomerName']) . "</td>";
        echo "<td>" . htmlspecialchars($row['RoomNumber']) . "</td>";
        echo "<td>" . date('d.m.Y', strtotime($row['CheckInDate'])) . "</td>";
        echo "<td>" . date('d.m.Y', strtotime($row['CheckOutDate'])) . "</td>";
        echo "<td>" . number_format($row['TotalAmount'], 2) . "</td>";
        echo "<td>" . number_format($services_total, 2) . "</td>";
        echo "<td><strong>" . number_format($grand_total, 2) . "</strong></td>";
        echo "<td>";
        echo !empty($services_display) ? implode('<br>', $services_display) : "Niciun serviciu";
        echo "</td>";
        echo "<td class='actions'>";
        echo "<button class='action-btn edit-btn' onclick='editRoomreservation({$row['ReservationID']}, {$row['CustomerID']}, {$row['RoomID']}, \"" . htmlspecialchars($row['CheckInDate'], ENT_QUOTES) . "\", \"" . htmlspecialchars($row['CheckOutDate'], ENT_QUOTES) . "\", {$row['TotalAmount']})'><i class='fas fa-edit'></i></button>";
        echo "<a href='?page=Roomreservations&action=delete&table=Roomreservations&id={$row['ReservationID']}' class='action-btn delete-btn' onclick='return confirm(\"Sigur doriți să ștergeți această rezervare?\")'><i class='fas fa-trash'></i></a>";
        echo "<a href='factura_rezervari.php?id={$row['ReservationID']}' class='action-btn' title='Export PDF'><i class='fas fa-file-pdf'></i></a>";
        echo "</td>";
        echo "</tr>";
    }
} catch(PDOException $e) {
    echo "<tr><td colspan='10'>Eroare: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}
?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal pentru adăugare/editare rezervare -->
        <div id="reservationModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="reservationModalTitle">Adaugă rezervare nouă</h3>
                    <button class="close-btn" onclick="closeModal('reservationModal')">×</button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" id="reservation_id" name="reservation_id">

                        <div class="form-group">
                            <label for="customer_id" class="form-label">Client</label>
                            <select id="customer_id" name="customer_id" class="form-control" required>
                                <option value="">-- Selectați clientul --</option>
                                <?php
                                try {
                                    $stmt = $conn->query("SELECT CustomerID, FirstName, LastName FROM Customers ORDER BY LastName, FirstName");
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<option value=\"" . htmlspecialchars($row['CustomerID']) . "\">" . htmlspecialchars($row['LastName'] . ' ' . $row['FirstName']) . "</option>";
                                    }
                                } catch(PDOException $e) {
                                    echo "<option value=''>Eroare: " . htmlspecialchars($e->getMessage()) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="room_id" class="form-label">Cameră</label>
                            <select id="room_id" name="room_id" class="form-control" required
                                onchange="updatePricePerNight()">
                                <option value="">-- Selectați camera --</option>
                                <?php
                                try {
                                    $stmt = $conn->query("SELECT RoomID, RoomNumber, PricePerNight FROM Rooms ORDER BY RoomNumber");
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<option value=\"" . htmlspecialchars($row['RoomID']) . "\" data-price=\"" . htmlspecialchars($row['PricePerNight']) . "\">Camera " . htmlspecialchars($row['RoomNumber']) . " - " . number_format($row['PricePerNight'], 2) . " RON/noapte</option>";
                                    }
                                } catch(PDOException $e) {
                                    echo "<option value=''>Eroare: " . htmlspecialchars($e->getMessage()) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="check_in" class="form-label">Data check-in</label>
                            <input type="date" id="check_in" name="check_in" class="form-control" required
                                onchange="calculateTotal()">
                        </div>

                        <div class="form-group">
                            <label for="check_out" class="form-label">Data check-out</label>
                            <input type="date" id="check_out" name="check_out" class="form-control" required
                                onchange="calculateTotal()">
                        </div>

                        <div class="form-group">
                            <label for="total_amount" class="form-label">Sumă totală (RON)</label>
                            <input type="number" id="total_amount" name="total_amount" class="form-control" step="0.01"
                                required>
                        </div>

                        <button type="submit" name="save_reservation" class="btn btn-success">Salvează</button>
                    </form>
                </div>
            </div>
        </div>

        <?php elseif ($page === 'services'): ?>
        <!-- Servicii -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Gestionare servicii</div>
                <div class="card-actions">
                    <button class="btn btn-success" onclick="openModal('serviceModal')">
                        <i class="fas fa-plus"></i> Adaugă serviciu
                    </button>
                    <a href="export_pdf.php?table=Services" class="btn btn-warning">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a>
                    <a href="export_xls.php?table=Services" class="btn">
                        <i class="fas fa-file-excel"></i> Export XLS
                    </a>
                    <a href="import_xls.php?table=Services" class="btn">
                        <i class="fas fa-file-import"></i> Import XLS
                    </a>
                    <a href="grafice.php?data=services" class="btn btn-info">
                        <i class="fas fa-chart-bar"></i> Grafice
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="" method="GET" class="search-form">
                    <input type="hidden" name="page" value="services">
                    <input type="text" name="search" placeholder="Caută..."
                        value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn">Caută</button>
                    <?php if (!empty($search)): ?>
                    <a href="?page=services" class="btn btn-warning">Resetează</a>
                    <?php endif; ?>
                </form>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th><a
                                        href="?page=services&sort=ServiceID&order=<?php echo $sort_by === 'ServiceID' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>">ID
                                        <?php echo $sort_by === 'ServiceID' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=services&sort=ServiceName&order=<?php echo $sort_by === 'ServiceName' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>">Nume
                                        serviciu
                                        <?php echo $sort_by === 'ServiceName' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=services&sort=Price&order=<?php echo $sort_by === 'Price' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>">Preț
                                        (RON)
                                        <?php echo $sort_by === 'Price' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th>Descriere</th>
                                <th>Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $query = "SELECT * FROM Services WHERE 1=1";
                                
                                if (!empty($search)) {
                                    $query .= " AND (ServiceName LIKE :search OR ServiceDescription LIKE :search)";
                                }
                                
                                $query .= " ORDER BY $sort_by $sort_order";
                                
                                $stmt = $conn->prepare($query);
                                
                                if (!empty($search)) {
                                    $search_param = '%' . $search . '%';
                                    $stmt->bindParam(':search', $search_param);
                                }
                                
                                $stmt->execute();
                                
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['ServiceID']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['ServiceName']) . "</td>";
                                    echo "<td>" . number_format($row['Price'], 2) . "</td>";
                                    echo "<td>" . (strlen($row['ServiceDescription']) > 50 ? htmlspecialchars(substr($row['ServiceDescription'], 0, 50)) . '...' : htmlspecialchars($row['ServiceDescription'])) . "</td>";
                                    echo "<td class='actions'>";
                                    echo "<button class='action-btn edit-btn' onclick='editService({$row['ServiceID']}, \"" . htmlspecialchars($row['ServiceName'], ENT_QUOTES) . "\", {$row['Price']}, \"" . htmlspecialchars($row['ServiceDescription'], ENT_QUOTES) . "\")'><i class='fas fa-edit'></i></button>";
                                    echo "<a href='?page=services&action=delete&table=Services&id={$row['ServiceID']}' class='action-btn delete-btn' onclick='return confirm(\"Sigur doriți să ștergeți acest serviciu?\")'><i class='fas fa-trash'></i></a>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } catch(PDOException $e) {
                                echo "<tr><td colspan='5'>Eroare: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal pentru adăugare/editare serviciu -->
        <div id="serviceModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="serviceModalTitle">Adaugă serviciu nou</h3>
                    <button class="close-btn" onclick="closeModal('serviceModal')">×</button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" id="service_id" name="service_id">

                        <div class="form-group">
                            <label for="service_name" class="form-label">Nume serviciu</label>
                            <input type="text" id="service_name" name="service_name" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="price" class="form-label">Preț (RON)</label>
                            <input type="number" id="price" name="price" class="form-control" step="0.01" required>
                        </div>

                        <div class="form-group">
                            <label for="servicedescription" class="form-label">Descriere</label>
                            <textarea id="servicedescription" name="servicedescription" class="form-control"
                                rows="4"></textarea>
                        </div>

                        <button type="submit" name="save_service" class="btn btn-success">Salvează</button>
                    </form>
                </div>
            </div>
        </div>

        <?php elseif ($page === 'employees'): ?>
        <!-- Angajați -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Gestionare angajați</div>
                <div class="card-actions">
                    <button class="btn btn-success" onclick="openModal('employeeModal')">
                        <i class="fas fa-plus"></i> Adaugă angajat
                    </button>
                    <a href="export_pdf.php?table=Employees" class="btn btn-warning">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a>
                    <a href="export_xls.php?table=Employees" class="btn">
                        <i class="fas fa-file-excel"></i> Export XLS
                    </a>
                    <a href="import_xls.php?table=Employees" class="btn">
                        <i class="fas fa-file-import"></i> Import XLS
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="" method="GET" class="search-form">
                    <input type="hidden" name="page" value="employees">
                    <input type="text" name="search" placeholder="Caută..."
                        value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn">Caută</button>
                    <?php if (!empty($search)): ?>
                    <a href="?page=employees" class="btn btn-warning">Resetează</a>
                    <?php endif; ?>
                </form>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th><a
                                        href="?page=employees&sort=EmployeeID&order=<?php echo $sort_by === 'EmployeeID' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>">ID
                                        <?php echo $sort_by === 'EmployeeID' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=employees&sort=LastName&order=<?php echo $sort_by === 'LastName' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>">Nume
                                        <?php echo $sort_by === 'LastName' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=employees&sort=FirstName&order=<?php echo $sort_by === 'FirstName' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>">Prenume
                                        <?php echo $sort_by === 'FirstName' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=employees&sort=Position&order=<?php echo $sort_by === 'Position' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>">Poziție
                                        <?php echo $sort_by === 'Position' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=employees&sort=Email&order=<?php echo $sort_by === 'Email' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>">Email
                                        <?php echo $sort_by === 'Email' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=employees&sort=Phone&order=<?php echo $sort_by === 'Phone' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>">Telefon
                                        <?php echo $sort_by === 'Phone' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=employees&sort=Salary&order=<?php echo $sort_by === 'Salary' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>">Salariu
                                        (RON)
                                        <?php echo $sort_by === 'Salary' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th>Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $query = "SELECT * FROM Employees WHERE 1=1";
                                
                                if (!empty($search)) {
                                    $query .= " AND (FirstName LIKE :search OR LastName LIKE :search OR Position LIKE :search OR Email LIKE :search OR Phone LIKE :search)";
                                }
                                
                                $query .= " ORDER BY $sort_by $sort_order";
                                
                                $stmt = $conn->prepare($query);
                                
                                if (!empty($search)) {
                                    $search_param = '%' . $search . '%';
                                    $stmt->bindParam(':search', $search_param);
                                }
                                
                                $stmt->execute();
                                
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['EmployeeID']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['LastName']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['FirstName']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['Position']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['Email']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['Phone']) . "</td>";
                                    echo "<td>" . number_format($row['Salary'], 2) . "</td>";
                                    echo "<td class='actions'>";
                                    echo "<button class='action-btn edit-btn' onclick='editEmployee({$row['EmployeeID']}, \"" . htmlspecialchars($row['FirstName'], ENT_QUOTES) . "\", \"" . htmlspecialchars($row['LastName'], ENT_QUOTES) . "\", \"" . htmlspecialchars($row['Position'], ENT_QUOTES) . "\", \"" . htmlspecialchars($row['Email'], ENT_QUOTES) . "\", \"" . htmlspecialchars($row['Phone'], ENT_QUOTES) . "\", {$row['Salary']})'><i class='fas fa-edit'></i></button>";
                                    echo "<a href='?page=employees&action=delete&table=Employees&id={$row['EmployeeID']}' class='action-btn delete-btn' onclick='return confirm(\"Sigur doriți să ștergeți acest angajat?\")'><i class='fas fa-trash'></i></a>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } catch(PDOException $e) {
                                echo "<tr><td colspan='8'>Eroare: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal pentru adăugare/editare angajat -->
        <div id="employeeModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="employeeModalTitle">Adaugă angajat nou</h3>
                    <button class="close-btn" onclick="closeModal('employeeModal')">×</button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" id="employee_id" name="employee_id">

                        <div class="form-group">
                            <label for="first_name_employee" class="form-label">Prenume</label>
                            <input type="text" id="first_name_employee" name="first_name" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="last_name_employee" class="form-label">Nume</label>
                            <input type="text" id="last_name_employee" name="last_name" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="position" class="form-label">Poziție</label>
                            <input type="text" id="position" name="position" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="email_employee" class="form-label">Email</label>
                            <input type="email" id="email_employee" name="email" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="phone_employee" class="form-label">Telefon</label>
                            <input type="text" id="phone_employee" name="phone" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="salary" class="form-label">Salariu (RON)</label>
                            <input type="number" id="salary" name="salary" class="form-control" step="0.01" required>
                        </div>

                        <button type="submit" name="save_employee" class="btn btn-success">Salvează</button>
                    </form>
                </div>
            </div>
        </div>

        <?php elseif ($page === 'users'): ?>
        <!-- Utilizatori -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Gestionare utilizatori</div>
                <div class="card-actions">
                    <button class="btn btn-success" onclick="openModal('userModal')">
                        <i class="fas fa-plus"></i> Adaugă utilizator
                    </button>
                    <a href="export_pdf.php?table=Users" class="btn btn-warning">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a>
                    <a href="export_xls.php?table=Users" class="btn">
                        <i class="fas fa-file-excel"></i> Export XLS
                    </a>
                    <a href="import_xls.php?table=Users" class="btn">
                        <i class="fas fa-file-import"></i> Import XLS
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="" method="GET" class="search-form">
                    <input type="hidden" name="page" value="users">
                    <input type="text" name="search" placeholder="Caută utilizatori..."
                        value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn">Caută</button>
                    <?php if (!empty($search)): ?>
                    <a href="?page=users" class="btn btn-warning">Resetează</a>
                    <?php endif; ?>
                </form>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th><a
                                        href="?page=users&sort=users_UserID&order=<?php echo $sort_by === 'users_UserID' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>&search_istoric=<?php echo htmlspecialchars($search_istoric); ?>">ID
                                        <?php echo $sort_by === 'users_UserID' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=users&sort=users_LastName&order=<?php echo $sort_by === 'users_LastName' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>&search_istoric=<?php echo htmlspecialchars($search_istoric); ?>">Nume
                                        <?php echo $sort_by === 'users_LastName' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=users&sort=users_FirstName&order=<?php echo $sort_by === 'users_FirstName' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>&search_istoric=<?php echo htmlspecialchars($search_istoric); ?>">Prenume
                                        <?php echo $sort_by === 'users_FirstName' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=users&sort=users_Email&order=<?php echo $sort_by === 'users_Email' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>&search_istoric=<?php echo htmlspecialchars($search_istoric); ?>">Email
                                        <?php echo $sort_by === 'users_Email' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=users&sort=users_UserRole&order=<?php echo $sort_by === 'users_UserRole' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>&search_istoric=<?php echo htmlspecialchars($search_istoric); ?>">Rol
                                        <?php echo $sort_by === 'users_UserRole' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th>Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                    try {
                        $query = "SELECT * FROM Users WHERE 1=1";
                        
                        if (!empty($search)) {
                            $query .= " AND (FirstName LIKE :search OR LastName LIKE :search OR Email LIKE :search OR UserRole LIKE :search)";
                        }
                        
                        $query .= " ORDER BY $sort_column $sort_order";
                        
                        $stmt = $conn->prepare($query);
                        
                        if (!empty($search)) {
                            $search_param = '%' . $search . '%';
                            $stmt->bindParam(':search', $search_param);
                        }
                        
                        $stmt->execute();
                        
                        if ($stmt->rowCount() === 0) {
                            echo "<tr><td colspan='6'>Niciun utilizator găsit.</td></tr>";
                        } else {
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['UserID']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['LastName']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['FirstName']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['Email']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['UserRole']) . "</td>";
                                echo "<td class='actions'>";
                                echo "<button class='action-btn edit-btn' onclick='editUser({$row['UserID']}, \"" . htmlspecialchars($row['FirstName'], ENT_QUOTES) . "\", \"" . htmlspecialchars($row['LastName'], ENT_QUOTES) . "\", \"" . htmlspecialchars($row['Email'], ENT_QUOTES) . "\", \"" . htmlspecialchars($row['UserRole'], ENT_QUOTES) . "\")'><i class='fas fa-edit'></i></button>";
                                echo "<a href='?page=users&action=delete&table=Users&id={$row['UserID']}' class='action-btn delete-btn' onclick='return confirm(\"Sigur doriți să ștergeți acest utilizator?\")'><i class='fas fa-trash'></i></a>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        }
                    } catch(PDOException $e) {
                        echo "<tr><td colspan='6'>Eroare: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                    }
                    ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Istoric Utilizare -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Gestionare istoric utilizare</div>
                <div class="card-actions">
                    <a href="export_pdf.php?table=IstoricUtilizare" class="btn btn-warning">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a>
                    <a href="export_xls.php?table=IstoricUtilizare" class="btn">
                        <i class="fas fa-file-excel"></i> Export XLS
                    </a>
                    <a href="import_xls.php?table=IstoricUtilizare" class="btn">
                        <i class="fas fa-file-import"></i> Import XLS
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="" method="GET" class="search-form">
                    <input type="hidden" name="page" value="users">
                    <input type="text" name="search_istoric" placeholder="Caută în istoric..."
                        value="<?php echo htmlspecialchars($search_istoric); ?>">
                    <button type="submit" class="btn">Caută</button>
                    <?php if (!empty($search_istoric)): ?>
                    <a href="?page=users" class="btn btn-warning">Resetează</a>
                    <?php endif; ?>
                </form>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th><a
                                        href="?page=users&sort=istoric_IstoricID&order=<?php echo $sort_by === 'istoric_IstoricID' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>&search_istoric=<?php echo htmlspecialchars($search_istoric); ?>">ID
                                        <?php echo $sort_by === 'istoric_IstoricID' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=users&sort=users_LastName&order=<?php echo $sort_by === 'users_LastName' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>&search_istoric=<?php echo htmlspecialchars($search_istoric); ?>">Nume
                                        <?php echo $sort_by === 'users_LastName' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=users&sort=users_FirstName&order=<?php echo $sort_by === 'users_FirstName' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>&search_istoric=<?php echo htmlspecialchars($search_istoric); ?>">Prenume
                                        <?php echo $sort_by === 'users_FirstName' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=users&sort=istoric_Data&order=<?php echo $sort_by === 'istoric_Data' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>&search_istoric=<?php echo htmlspecialchars($search_istoric); ?>">Data
                                        <?php echo $sort_by === 'istoric_Data' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=users&sort=istoric_Ora&order=<?php echo $sort_by === 'istoric_Ora' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>&search_istoric=<?php echo htmlspecialchars($search_istoric); ?>">Ora
                                        <?php echo $sort_by === 'istoric_Ora' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                                <th><a
                                        href="?page=users&sort=istoric_Operatie&order=<?php echo $sort_by === 'istoric_Operatie' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo htmlspecialchars($search); ?>&search_istoric=<?php echo htmlspecialchars($search_istoric); ?>">Operație
                                        <?php echo $sort_by === 'istoric_Operatie' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></a>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                    try {
                        $query = "SELECT iu.IstoricID, iu.UserID, iu.DataOra, iu.Operatie, u.LastName, u.FirstName,
                                  DATE_FORMAT(iu.DataOra, '%d.%m.%Y') AS Data,
                                  DATE_FORMAT(iu.DataOra, '%H:%i:%s') AS Ora
                                  FROM istoric_utilizare iu 
                                  LEFT JOIN users u ON iu.UserID = u.UserID 
                                  WHERE 1=1";
                        
                        if (!empty($search_istoric)) {
                            $query .= " AND (u.LastName LIKE :search OR u.FirstName LIKE :search OR iu.Operatie LIKE :search OR DATE_FORMAT(iu.DataOra, '%d.%m.%Y') LIKE :search OR DATE_FORMAT(iu.DataOra, '%H:%i:%s') LIKE :search)";
                        }
                        
                        $query .= " ORDER BY $sort_column $sort_order";
                        
                        $stmt = $conn->prepare($query);
                        
                        if (!empty($search_istoric)) {
                            $search_param = '%' . $search_istoric . '%';
                            $stmt->bindParam(':search', $search_param);
                        }
                        
                        $stmt->execute();
                        
                        if ($stmt->rowCount() === 0) {
                            echo "<tr><td colspan='6'>Nicio înregistrare găsită în istoric.</td></tr>";
                        } else {
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['IstoricID']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['LastName'] ?? '') . "</td>";
                                echo "<td>" . htmlspecialchars($row['FirstName'] ?? '') . "</td>";
                                echo "<td>" . htmlspecialchars($row['Data']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['Ora']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['Operatie']) . "</td>";
                                echo "</tr>";
                            }
                        }
                    } catch(PDOException $e) {
                        echo "<tr><td colspan='6'>Eroare: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                    }
                    ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal pentru adăugare/editare utilizator -->
        <div id="userModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="userModalTitle">Adaugă utilizator nou</h3>
                    <button class="close-btn" onclick="closeModal('userModal')">×</button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" id="user_id" name="user_id">

                        <div class="form-group">
                            <label for="first_name_user" class="form-label">Prenume</label>
                            <input type="text" id="first_name_user" name="first_name" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="last_name_user" class="form-label">Nume</label>
                            <input type="text" id="last_name_user" name="last_name" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="email_user" class="form-label">Email</label>
                            <input type="email" id="email_user" name="email" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Parolă (lăsați gol pentru a păstra parola
                                curentă)</label>
                            <input type="password" id="password" name="password" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="role" class="form-label">Rol</label>
                            <select id="role" name="role" class="form-control" required>
                                <option value="admin">Admin</option>
                                <option value="client">Client</option>
                                <option value="employee">Angajat</option>
                            </select>
                        </div>

                        <button type="submit" name="save_user" class="btn btn-success">Salvează</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>


        <footer>
            <p>&copy; <?php echo date('Y'); ?> Hotel Management System. Toate drepturile rezervate.</p>
        </footer>

        <script>
        // Funcții pentru gestionarea modalelor
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            resetModal(modalId);
        }

        function resetModal(modalId) {
            const modal = document.getElementById(modalId);
            const form = modal.querySelector('form');
            if (form) form.reset();

            // Reset titluri și câmpuri specifice
            if (modalId === 'roomModal') {
                document.getElementById('roomModalTitle').textContent = 'Adaugă cameră nouă';
                document.getElementById('room_id').value = '';
            } else if (modalId === 'customerModal') {
                document.getElementById('customerModalTitle').textContent = 'Adaugă client nou';
                document.getElementById('customer_id').value = '';
            } else if (modalId === 'reservationModal') {
                document.getElementById('reservationModalTitle').textContent = 'Adaugă rezervare nouă';
                document.getElementById('reservation_id').value = '';
            } else if (modalId === 'serviceModal') {
                document.getElementById('serviceModalTitle').textContent = 'Adaugă serviciu nou';
                document.getElementById('service_id').value = '';
            } else if (modalId === 'employeeModal') {
                document.getElementById('employeeModalTitle').textContent = 'Adaugă angajat nou';
                document.getElementById('employee_id').value = '';
            } else if (modalId === 'userModal') {
                document.getElementById('userModalTitle').textContent = 'Adaugă utilizator nou';
                document.getElementById('user_id').value = '';
                document.getElementById('password').required = true;
            }
        }

        // Funcții pentru editare
        function editRoom(id, roomNumber, price, capacity, bedType, hasAC, hasBalcony, description) {
            document.getElementById('roomModalTitle').textContent = 'Editează cameră';
            document.getElementById('room_id').value = id;
            document.getElementById('room_number').value = roomNumber;
            document.getElementById('price').value = price;
            document.getElementById('capacity').value = capacity;
            document.getElementById('bed_type').value = bedType;
            document.getElementById('has_ac').checked = hasAC;
            document.getElementById('has_balcony').checked = hasBalcony;
            document.getElementById('description').value = description;
            openModal('roomModal');
        }

        function editCustomer(id, firstName, lastName, email, phone, address) {
            document.getElementById('customerModalTitle').textContent = 'Editează client';
            document.getElementById('customer_id').value = id;
            document.getElementById('first_name').value = firstName;
            document.getElementById('last_name').value = lastName;
            document.getElementById('email').value = email;
            document.getElementById('phone').value = phone;
            document.getElementById('address').value = address;
            openModal('customerModal');
        }

        function editRoomreservation(id, customerId, roomId, checkIn, checkOut, totalAmount) {
            document.getElementById('reservationModalTitle').textContent = 'Editează rezervare';
            document.getElementById('reservation_id').value = id;
            document.getElementById('customer_id').value = customerId;
            document.getElementById('room_id').value = roomId;
            document.getElementById('check_in').value = checkIn;
            document.getElementById('check_out').value = checkOut;
            document.getElementById('total_amount').value = totalAmount;
            openModal('reservationModal');
        }

        function editService(id, serviceName, price, description) {
            document.getElementById('serviceModalTitle').textContent = 'Editează serviciu';
            document.getElementById('service_id').value = id;
            document.getElementById('service_name').value = serviceName;
            document.getElementById('price').value = price;
            document.getElementById('servicedescription').value = description;
            openModal('serviceModal');
        }

        function editEmployee(id, firstName, lastName, position, email, phone, salary) {
            document.getElementById('employeeModalTitle').textContent = 'Editează angajat';
            document.getElementById('employee_id').value = id;
            document.getElementById('first_name_employee').value = firstName;
            document.getElementById('last_name_employee').value = lastName;
            document.getElementById('position').value = position;
            document.getElementById('email_employee').value = email;
            document.getElementById('phone_employee').value = phone;
            document.getElementById('salary').value = salary;
            openModal('employeeModal');
        }

        function editUser(id, firstName, lastName, email, role) {
            document.getElementById('userModalTitle').textContent = 'Editează utilizator';
            document.getElementById('user_id').value = id;
            document.getElementById('first_name_user').value = firstName;
            document.getElementById('last_name_user').value = lastName;
            document.getElementById('email_user').value = email;
            document.getElementById('role').value = role;
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            openModal('userModal');
        }

        // Închidere modal la clic pe fundal
        window.onclick = function(event) {
            const modals = document.getElementsByClassName('modal');
            for (let i = 0; i < modals.length; i++) {
                if (event.target === modals[i]) {
                    closeModal(modals[i].id);
                }
            }
        };

        // Calcul sumă totală rezervare
        function calculateTotal() {
            const checkIn = document.getElementById('check_in').value;
            const checkOut = document.getElementById('check_out').value;
            const roomSelect = document.getElementById('room_id');
            const totalAmountInput = document.getElementById('total_amount');

            if (checkIn && checkOut && roomSelect.value) {
                const pricePerNight = parseFloat(roomSelect.options[roomSelect.selectedIndex].dataset.price);
                const checkInDate = new Date(checkIn);
                const checkOutDate = new Date(checkOut);

                if (checkOutDate > checkInDate) {
                    const timeDiff = checkOutDate - checkInDate;
                    const days = timeDiff / (1000 * 3600 * 24);
                    const total = pricePerNight * days;
                    totalAmountInput.value = total.toFixed(2);
                } else {
                    totalAmountInput.value = '';
                }
            }
        }

        // Ascunde mesajele de succes după 5 secunde
        document.addEventListener('DOMContentLoaded', function() {
            const successAlerts = document.querySelectorAll('.alert.alert-success');
            successAlerts.forEach(alert => {
                setTimeout(() => {
                    alert.classList.add('auto-hide');
                    setTimeout(() => {
                        alert.remove();
                    }, 500);
                }, 5000);

                // Adaugă eveniment pentru închiderea manuală
                const closeBtn = alert.querySelector('::after');
                if (closeBtn) {
                    closeBtn.addEventListener('click', () => {
                        alert.classList.add('auto-hide');
                        setTimeout(() => {
                            alert.remove();
                        }, 500);
                    });
                }
            });
        });
        </script>
</body>

</html>