<?php
session_start();

// Verifică dacă utilizatorul este autentificat și are rolul de admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Include biblioteca PhpSpreadsheet
require 'vendor/autoload.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

// Conectare la baza de date
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hotel";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8"); // Asigură suportul pentru diacritice
} catch(PDOException $e) {
    die("Conexiunea la baza de date a eșuat: " . $e->getMessage());
}

// Obține tabela din parametrul URL
$table = isset($_GET['table']) ? $_GET['table'] : '';

// Mapare pentru denumirile tabelelor
$table_mapping = [
    'Rooms' => 'Rooms',
    'Customers' => 'Customers',
    'Roomreservations' => 'Roomreservations',
    'Services' => 'Services',
    'Employees' => 'Employees',
    'Users' => 'Users',
    'IstoricUtilizare' => 'istoric_utilizare',
    'istoric_utilizare' => 'istoric_utilizare',
    'Istoric_Utilizare' => 'istoric_utilizare',
];

// Validează tabela
if (!array_key_exists($table, $table_mapping)) {
    die("Tabelă invalidă!");
}

// Setează tabela corectă pentru interogare
$db_table = $table_mapping[$table];

// Definim câmpurile așteptate pentru fiecare tabelă
$expected_columns = [
    'Rooms' => ['RoomNumber', 'PricePerNight', 'Capacity', 'BedType', 'HasAC', 'HasBalcony', 'Description'],
    'Customers' => ['CustomerID', 'FirstName', 'LastName', 'Email', 'Phone', 'Address'],
    'Roomreservations' => ['CustomerID', 'RoomID', 'CheckInDate', 'CheckOutDate', 'TotalAmount'],
    'Services' => ['ServiceName', 'Price', 'ServiceDescription'],
    'Employees' => ['FirstName', 'LastName', 'Position', 'Email', 'Phone', 'Salary'],
    'Users' => ['FirstName', 'LastName', 'Email', 'Password', 'UserRole'],
    'istoric_utilizare' => ['UserID', 'DataOra', 'Operatie'],
];

// Procesare formular de încărcare doar pentru cereri POST
$successes = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['xls_file'])) {
    $file = $_FILES['xls_file'];
    
    // Validare fișier
    $allowed_extensions = ['xls', 'xlsx'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_extensions)) {
        $errors[] = "Fișierul trebuie să fie în format XLS sau XLSX.";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Eroare la încărcarea fișierului: " . $file['error'];
    } else {
        try {
            // Citim fișierul XLS/XLSX
            $spreadsheet = IOFactory::load($file['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray(null, true, true, true);
            $headers = array_shift($data); // Prima linie conține anteturile
            $headers = array_values(array_map('trim', $headers)); // Extragem doar valorile și eliminăm spațiile
            
            // Depanare: Afișăm caracterele exacte ale anteturilor găsite
            $debug_headers = array_map(function($header) {
                $chars = str_split($header);
                $char_codes = array_map(function($char) {
                    return ord($char);
                }, $chars);
                return "$header (" . implode(', ', $char_codes) . ")";
            }, $headers);
            
            // Comparam anteturile
            if ($headers !== $expected_columns[$db_table]) {
                $errors[] = "Anteturile fișierului nu corespund câmpurilor așteptate pentru tabela $db_table.";
                $errors[] = "Anteturi găsite: " . implode(', ', $debug_headers);
                $errors[] = "Anteturi așteptate: " . implode(', ', $expected_columns[$db_table]);
            } else {
                $conn->beginTransaction();
                
                foreach ($data as $row_number => $row) {
                    // Ignorăm rândurile goale
                    if (empty(array_filter($row))) {
                        continue;
                    }
                    
                    try {
                        $row_data = array_combine($expected_columns[$db_table], array_map('trim', array_values($row)));
                        
                        // Validări specifice pentru fiecare tabelă
                        switch ($db_table) {
                            case 'Rooms':
                                if (empty($row_data['RoomNumber']) || empty($row_data['PricePerNight']) || empty($row_data['Capacity']) || empty($row_data['BedType'])) {
                                    throw new Exception("Câmpurile RoomNumber, PricePerNight, Capacity și BedType sunt obligatorii.");
                                }
                                $row_data['HasAC'] = filter_var($row_data['HasAC'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? 0;
                                $row_data['HasBalcony'] = filter_var($row_data['HasBalcony'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? 0;
                                $query = "INSERT INTO Rooms (RoomNumber, PricePerNight, Capacity, BedType, HasAC, HasBalcony, Description)
                                          VALUES (:RoomNumber, :PricePerNight, :Capacity, :BedType, :HasAC, :HasBalcony, :Description)";
                                break;
                            
                            case 'Customers':
                                if (empty($row_data['FirstName']) || empty($row_data['LastName']) || empty($row_data['Email'])) {
                                    throw new Exception("Câmpurile FirstName, LastName și Email sunt obligatorii.");
                                }
                                // Verificăm unicitatea email-ului
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM Customers WHERE Email = :Email");
                                $stmt->bindParam(':Email', $row_data['Email']);
                                $stmt->execute();
                                if ($stmt->fetchColumn() > 0) {
                                    throw new Exception("Email-ul {$row_data['Email']} există deja.");
                                }
                                // Generăm CustomerID dacă nu este specificat
                                if (empty($row_data['CustomerID'])) {
                                    $stmt = $conn->query("SELECT MAX(CustomerID) + 1 AS new_id FROM Customers");
                                    $row_data['CustomerID'] = $stmt->fetchColumn() ?: 1;
                                }
                                $query = "INSERT INTO Customers (CustomerID, FirstName, LastName, Email, Phone, Address)
                                          VALUES (:CustomerID, :FirstName, :LastName, :Email, :Phone, :Address)";
                                break;
                            
                            case 'Roomreservations':
                                if (empty($row_data['CustomerID']) || empty($row_data['RoomID']) || empty($row_data['CheckInDate']) || empty($row_data['CheckOutDate']) || empty($row_data['TotalAmount'])) {
                                    throw new Exception("Toate câmpurile sunt obligatorii.");
                                }
                                // Validăm datele
                                if (strtotime($row_data['CheckOutDate']) <= strtotime($row_data['CheckInDate'])) {
                                    throw new Exception("CheckOutDate trebuie să fie după CheckInDate.");
                                }
                                $query = "INSERT INTO Roomreservations (CustomerID, RoomID, CheckInDate, CheckOutDate, TotalAmount)
                                          VALUES (:CustomerID, :RoomID, :CheckInDate, :CheckOutDate, :TotalAmount)";
                                break;
                            
                            case 'Services':
                                if (empty($row_data['ServiceName']) || empty($row_data['Price'])) {
                                    throw new Exception("Câmpurile ServiceName și Price sunt obligatorii.");
                                }
                                $query = "INSERT INTO Services (ServiceName, Price, ServiceDescription)
                                          VALUES (:ServiceName, :Price, :ServiceDescription)";
                                break;
                            
                            case 'Employees':
                                if (empty($row_data['FirstName']) || empty($row_data['LastName']) || empty($row_data['Position']) || empty($row_data['Email']) || empty($row_data['Phone']) || empty($row_data['Salary'])) {
                                    throw new Exception("Toate câmpurile sunt obligatorii.");
                                }
                                $query = "INSERT INTO Employees (FirstName, LastName, Position, Email, Phone, Salary)
                                          VALUES (:FirstName, :LastName, :Position, :Email, :Phone, :Salary)";
                                break;
                            
                            case 'Users':
                                if (empty($row_data['FirstName']) || empty($row_data['LastName']) || empty($row_data['Email']) || empty($row_data['Password']) || empty($row_data['UserRole'])) {
                                    throw new Exception("Toate câmpurile sunt obligatorii.");
                                }
                                // Hash parola
                                $row_data['Password'] = password_hash($row_data['Password'], PASSWORD_DEFAULT);
                                $query = "INSERT INTO Users (FirstName, LastName, Email, Password, UserRole)
                                          VALUES (:FirstName, :LastName, :Email, :Password, :UserRole)";
                                break;
                            
                            case 'istoric_utilizare':
                                if (empty($row_data['UserID']) || empty($row_data['DataOra']) || empty($row_data['Operatie'])) {
                                    throw new Exception("Toate câmpurile sunt obligatorii.");
                                }
                                $query = "INSERT INTO istoric_utilizare (UserID, DataOra, Operatie)
                                          VALUES (:UserID, :DataOra, :Operatie)";
                                break;
                        }
                        
                        // Pregătim și executăm interogarea
                        $stmt = $conn->prepare($query);
                        foreach ($row_data as $key => $value) {
                            $stmt->bindValue(":$key", $value);
                        }
                        $stmt->execute();
                        
                        // Obținem ID-ul generat
                        $inserted_id = $conn->lastInsertId();
                        $successes[] = [
                            'row' => $row_number + 2,
                            'data' => $row_data,
                            'inserted_id' => $inserted_id
                        ];
                        
                    } catch (Exception $e) {
                        $errors[] = [
                            'row' => $row_number + 2,
                            'error' => $e->getMessage(),
                            'data' => array_values($row)
                        ];
                    }
                }
                
                // Înregistrăm acțiunea în istoric
                $stmt = $conn->prepare("INSERT INTO istoric_utilizare (UserID, Operatie) VALUES (:user_id, :operatie)");
                $stmt->bindParam(':user_id', $_SESSION['user_id']);
                $operatie = "Import XLS în tabela $db_table (" . count($successes) . " succese, " . count($errors) . " erori)";
                $stmt->bindParam(':operatie', $operatie);
                $stmt->execute();
                
                $conn->commit();
            }
        } catch (Exception $e) {
            $errors[] = "Eroare la procesarea fișierului: " . $e->getMessage();
        }
        
        // Generăm raportul XLS
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Raport Import');

        // Adăugăm titlul și informațiile generale
        $sheet->setCellValue('A1', "Raport Import $table");
        $sheet->setCellValue('A2', 'Generat la: ' . date('d.m.Y H:i:s'));
        $sheet->setCellValue('A3', 'Utilizator: ' . htmlspecialchars($_SESSION['email']));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2:A3')->getFont()->setItalic(true)->setSize(10);

        // Secțiunea succese
        $row_number = 5;
        $sheet->setCellValue('A' . $row_number, 'Înregistrări importate cu succes');
        $sheet->getStyle('A' . $row_number)->getFont()->setBold(true)->setSize(12);
        $row_number++;

        if (!empty($successes)) {
            // Adăugăm anteturile
            $columns = array_merge(['ID Generat'], $expected_columns[$db_table]);
            $col = 'A';
            foreach ($columns as $column) {
                $sheet->setCellValue($col . $row_number, $column);
                $col++;
            }
            
            // Stilizare antet
            $last_column = chr(ord('A') + count($columns) - 1);
            $sheet->getStyle('A' . $row_number . ':' . $last_column . $row_number)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF3498DB');
            $sheet->getStyle('A' . $row_number . ':' . $last_column . $row_number)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row_number . ':' . $last_column . $row_number)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
            $row_number++;
            
            // Adăugăm datele
            foreach ($successes as $success) {
                $col = 'A';
                $sheet->setCellValue($col . $row_number, $success['inserted_id']);
                $col++;
                foreach ($success['data'] as $value) {
                    $sheet->setCellValue($col . $row_number, $value);
                    $col++;
                }
                $row_number++;
            }
        } else {
            $sheet->setCellValue('A' . $row_number, 'Nicio înregistrare importată cu succes.');
            $row_number++;
        }

        // Secțiunea erori
        $row_number += 2;
        $sheet->setCellValue('A' . $row_number, 'erori la import');
        $sheet->getStyle('A' . $row_number)->getFont()->setBold(true)->setSize(12);
        $row_number++;

        if (!empty($errors)) {
            $sheet->setCellValue('A' . $row_number, 'Rând');
            $sheet->setCellValue('B' . $row_number, 'Eroare');
            $sheet->setCellValue('C' . $row_number, 'Date');
            
            // Stilizare antet
            $sheet->getStyle('A' . $row_number . ':C' . $row_number)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF44336');
            $sheet->getStyle('A' . $row_number . ':C' . $row_number)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row_number . ':C' . $row_number)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
            $row_number++;
            
            foreach ($errors as $error) {
                if (isset($error['row'])) {
                    $sheet->setCellValue('A' . $row_number, $error['row']);
                    $sheet->setCellValue('B' . $row_number, $error['error']);
                    $sheet->setCellValue('C' . $row_number, implode(', ', $error['data']));
                } else {
                    $sheet->setCellValue('A' . $row_number, '-');
                    $sheet->setCellValue('B' . $row_number, $error);
                    $sheet->setCellValue('C' . $row_number, '-');
                }
                $row_number++;
            }
        } else {
            $sheet->setCellValue('A' . $row_number, 'Nicio eroare la import.');
        }

        // Ajustăm lățimea coloanelor
        foreach (range('A', $last_column ?? 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Setează fontul
        $sheet->getStyle('A1:' . ($last_column ?? 'C') . $row_number)->getFont()->setName('Arial')->setSize(10);

        // Salvează și descarcă raportul
        $filename = 'import_' . strtolower($table) . '_' . date('Ymd_Hi') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        ob_clean();
        $writer->save('php://output');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import XLS - <?php echo htmlspecialchars($table); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
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
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background-color: #4CAF50;
            color: white;
        }
        .error {
            color: red;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <h2>Import XLS pentru <?php echo htmlspecialchars($table); ?></h2>
    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="xls_file" class="form-label">Selectează fișierul XLS/XLSX</label>
            <input type="file" id="xls_file" name="xls_file" class="form-control" accept=".xls,.xlsx" required>
        </div>
        <button type="submit" class="btn">Încarcă și importă</button>
    </form>
</body>
</html>