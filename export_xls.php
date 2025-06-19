<?php
session_start();

// Verifică dacă utilizatorul este autentificat și are rolul de admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Include biblioteca PhpSpreadsheet
require 'vendor/autoload.php'; // Ajustează calea dacă folosești Composer în alt director

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

// Obține tabela și ID-ul din parametrul URL
$table = isset($_GET['table']) ? $_GET['table'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

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

// Crează un nou obiect Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Setează titlul foii
$sheet->setTitle($table);

// Obține datele din tabel
try {
    $query = "SELECT * FROM $db_table";
    
    // Dacă avem un ID specific, exportăm doar acea înregistrare
    if ($id > 0) {
        $id_field = '';
        switch ($db_table) {
            case 'Rooms': $id_field = 'RoomID'; break;
            case 'Customers': $id_field = 'CustomerID'; break;
            case 'Roomreservations': $id_field = 'ReservationID'; break;
            case 'Services': $id_field = 'ServiceID'; break;
            case 'Employees': $id_field = 'EmployeeID'; break;
            case 'Users': $id_field = 'UserID'; break;
            case 'istoric_utilizare': $id_field = 'IstoricID'; break;
        }
        
        if (!empty($id_field)) {
            $query .= " WHERE $id_field = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $data = [];
        }
    } else {
        // Exportăm tot tabelul
        $stmt = $conn->query($query);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Dacă nu avem date
    if (empty($data)) {
        $sheet->setCellValue('A1', 'Nu există date de exportat pentru această tabelă.');
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="export_' . $table . '.xlsx"');
        header('Cache-Control: max-age=0');
        ob_clean();
        $writer->save('php://output');
        exit();
    }
    
    // Generare conținut XLS
    if ($id > 0) {
        // Export individual (format: Câmp | Valoare)
        $sheet->setCellValue('A1', 'Câmp');
        $sheet->setCellValue('B1', 'Valoare');
        
        // Stilizare antet
        $sheet->getStyle('A1:B1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF3498DB');
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);
        $sheet->getStyle('A1:B1')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
        
        // Adaugă datele
        $row_number = 2;
        foreach ($data as $key => $value) {
            $sheet->setCellValue('A' . $row_number, $key);
            $sheet->setCellValue('B' . $row_number, $value);
            $row_number++;
        }
        
        // Ajustează lățimea coloanelor
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    } else {
        // Export complet al tabelului
        // Adaugă anteturile
        $columns = array_keys($data[0]);
        $col = 'A';
        foreach ($columns as $column) {
            $sheet->setCellValue($col . '1', $column);
            $col++;
        }
        
        // Stilizare antet
        $last_column = chr(ord('A') + count($columns) - 1);
        $sheet->getStyle('A1:' . $last_column . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF3498DB');
        $sheet->getStyle('A1:' . $last_column . '1')->getFont()->setBold(true);
        $sheet->getStyle('A1:' . $last_column . '1')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
        
        // Adaugă datele
        $row_number = 2;
        foreach ($data as $row) {
            $col = 'A';
            foreach ($row as $cell) {
                $sheet->setCellValue($col . $row_number, $cell);
                $col++;
            }
            $row_number++;
        }
        
        // Ajustează lățimea coloanelor
        foreach (range('A', $last_column) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
    
    // Setează fontul pentru întregul document
    $sheet->getStyle('A1:' . $last_column . ($row_number - 1))->getFont()->setName('Arial')->setSize(10);
    
    // Adaugă titlul și data generării
    $sheet->insertNewRowBefore(1, 3);
    $sheet->setCellValue('A1', 'Export ' . $table);
    $sheet->setCellValue('A2', 'Generat la: ' . date('d.m.Y H:i:s'));
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10);
    
    // Salvează și descarcă fișierul
    $filename = $id > 0 ? 'export_' . strtolower($table) . '_id_' . $id . '.xlsx' : 'export_' . strtolower($table) . '.xlsx';
    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    ob_clean();
    $writer->save('php://output');
    exit();
    
} catch(PDOException $e) {
    die("Eroare la export: " . $e->getMessage());
}
?>