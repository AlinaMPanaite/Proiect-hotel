<?php
session_start();

// Verifică dacă utilizatorul este autentificat și are rolul de admin
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once('tcpdf/tcpdf.php'); // Ajustează calea dacă TCPDF este instalat altundeva

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

// Obține tabela din parametrul URL
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

// Crează un nou obiect PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetFont('dejavusans', '', 10);

// Setări document
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Hotel Management System');
$pdf->SetTitle('Export ' . $table);
$pdf->SetSubject('Export date din sistem');
$pdf->SetKeywords('PDF, Hotel, Export');

// Setări header și footer
$pdf->setHeaderData('', 0, 'Hotel Management System', 'Export ' . $table);
$pdf->setHeaderFont(['dejavusans', '', 8]);
$pdf->setFooterFont(['dejavusans', '', 8]);

// Setează margini
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Adaugă o pagină
$pdf->AddPage();

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
        $pdf->Cell(0, 10, 'Nu există date de exportat pentru această tabelă.', 0, 1);
        $pdf->Output('export_' . $table . '.pdf', 'D');
        exit();
    }
    
    // Generare conținut PDF
    $html = '<h1 style="font-size: 18px; color: #2c3e50; text-align: center; margin-bottom: 10px;">Export ' . htmlspecialchars($table) . '</h1>';
    $html .= '<p style="font-size: 12px; color: #666; text-align: center; margin-bottom: 20px;">Generat la: ' . date('d.m.Y H:i:s') . '</p>';
    
    if ($id > 0) {
        // Afișează o singură înregistrare ca detalii
        $html .= '<table style="border-collapse: collapse; width: 100%; font-size: 10px;">';
        $html .= '<tr style="background-color: #3498db; color: white;">';
        $html .= '<th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Câmp</th>';
        $html .= '<th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Valoare</th>';
        $html .= '</tr>';
        foreach ($data as $key => $value) {
            $html .= '<tr>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">' . htmlspecialchars($key) . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($value) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
    } else {
        // Afișează toate înregistrările ca tabel
        $html .= '<table style="border-collapse: collapse; width: 100%; font-size: 10px;">';
        
        // Antet tabel
        $html .= '<tr style="background-color: #3498db; color: white;">';
        foreach (array_keys($data[0]) as $column) {
            $html .= '<th style="padding: 8px; border: 1px solid #ddd; text-align: center;">' . htmlspecialchars($column) . '</th>';
        }
        $html .= '</tr>';
        
        // Rânduri cu date
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td style="padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">' . htmlspecialchars($cell) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';
    }
    
    // Scrie conținutul HTML
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Ieșire PDF - descărcare fișier
    ob_clean();
    $pdf->Output('export_' . $table . '.pdf', 'D');
    
} catch(PDOException $e) {
    die("Eroare la export: " . $e->getMessage());
}
?>