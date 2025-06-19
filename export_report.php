<?php
session_start();

// Verifică dacă utilizatorul este autentificat
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include bibliotecile necesare
require_once('tcpdf/tcpdf.php'); // Pentru PDF
require_once('vendor/autoload.php'); // Pentru PhpSpreadsheet (dacă folosești Composer)

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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

// Verifică parametrii GET
if (!isset($_GET['type']) || !in_array($_GET['type'], ['pdf', 'excel'])) {
    die("Tip de export invalid");
}
$type = $_GET['type'];

if (!isset($_GET['start_date']) || !isset($_GET['end_date'])) {
    die("Datele de început și sfârșit sunt obligatorii");
}
$start_date = $_GET['start_date'];
$end_date = $_GET['end_date'];

// Validează datele
if (empty($start_date) || empty($end_date)) {
    die("Ambele câmpuri de dată sunt obligatorii");
}
if ($start_date > $end_date) {
    die("Data de început nu poate fi după data de sfârșit");
}

$report_data = [];

try {
    // Obține datele despre rezervări
    $stmt = $conn->prepare("
        SELECT COUNT(*) as reservation_count, 
               SUM(TotalAmount) as total_revenue,
               AVG(TotalAmount) as avg_revenue,
               DATEDIFF(MAX(CheckOutDate), MIN(CheckInDate)) as avg_stay_days
        FROM Roomreservations 
        WHERE CheckInDate BETWEEN :start_date AND :end_date
    ");
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    $report_data['reservations'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obține datele despre ocupare
    $stmt = $conn->prepare("
        SELECT 
            COUNT(DISTINCT RoomID) as occupied_rooms,
            (SELECT COUNT(*) FROM Rooms) as total_rooms,
            (COUNT(DISTINCT RoomID) / (SELECT COUNT(*) FROM Rooms)) * 100 as occupancy_rate
        FROM Roomreservations 
        WHERE CheckInDate <= :end_date AND CheckOutDate >= :start_date
    ");
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    $report_data['occupancy'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obține datele despre clienți
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT UserID) as new_customers
        FROM istoric_utilizare
        WHERE Operatie = 'Înregistrare cont'
        AND DataOra BETWEEN :start_date AND :end_date
    ");
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    $report_data['customers'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obține datele despre servicii
    $stmt = $conn->prepare("
        SELECT COUNT(*) as service_count, SUM(TotalAmount) as service_revenue
        FROM ServiceReservations
        WHERE ReservationDate BETWEEN :start_date AND :end_date
    ");
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    $report_data['services'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obține camerele populare
    $stmt = $conn->prepare("
        SELECT r.RoomNumber, COUNT(*) as reservation_count
        FROM Roomreservations rr
        JOIN Rooms r ON rr.RoomID = r.RoomID
        WHERE rr.CheckInDate BETWEEN :start_date AND :end_date
        GROUP BY rr.RoomID
        ORDER BY reservation_count DESC
        LIMIT 5
    ");
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    $report_data['popular_rooms'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obține veniturile pe tipuri de camere
    $stmt = $conn->prepare("
        SELECT r.BedType, SUM(rr.TotalAmount) as total_revenue
        FROM Roomreservations rr
        JOIN Rooms r ON rr.RoomID = r.RoomID
        WHERE rr.CheckInDate BETWEEN :start_date AND :end_date
        GROUP BY r.BedType
        ORDER BY total_revenue DESC
    ");
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    $report_data['revenue_by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Eroare la generarea raportului: " . $e->getMessage());
}

if ($type === 'pdf') {
    // Creează documentul PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Setează informațiile documentului
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Hotel Management System');
    $pdf->SetTitle('Raport Hotel ' . date('d.m.Y', strtotime($start_date)) . ' - ' . date('d.m.Y', strtotime($end_date)));
    $pdf->SetSubject('Raport performanță hotel');
    $pdf->SetKeywords('Raport, Hotel, Performanță');

    // Setează antetul
    $pdf->SetHeaderData('', 0, 'Hotel Grand Plaza', 'Raport performanță: ' . date('d.m.Y', strtotime($start_date)) . ' - ' . date('d.m.Y', strtotime($end_date)));

    // Setează fontul implicit pentru suport diacritice
    $pdf->SetFont('dejavusans', '', 10);

    // Setează fonturile pentru antet și subsol
    $pdf->setHeaderFont(['dejavusans', '', PDF_FONT_SIZE_MAIN]);
    $pdf->setFooterFont(['dejavusans', '', PDF_FONT_SIZE_DATA]);

    // Setează marginile
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // Setează întreruperile automate de pagină
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // Setează factorul de scalare a imaginii
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // Adaugă o pagină
    $pdf->AddPage();

    // Antet stilizat
    $hotel_info = <<<EOD
    <div style="text-align:center; border-bottom: 1px solid #ccc; padding-bottom: 10px; margin-bottom: 20px;">
        <h1 style="font-size: 24px; color: #2c3e50; margin: 0;">Hotel Grand Plaza</h1>
        <p style="font-size: 12px; color: #666; margin: 5px 0;">Strada Principală 123, București, România</p>
        <p style="font-size: 12px; color: #666; margin: 5px 0;">Telefon: +40 21 123 4567 | Email: contact@grandplaza.com</p>
    </div>
    EOD;
    $pdf->writeHTML($hotel_info, true, false, true, false, '');

    // Titlu raport
    $start_date_formatted = date('d.m.Y', strtotime($start_date));
    $end_date_formatted = date('d.m.Y', strtotime($end_date));
    $report_title = <<<EOD
    <div style="text-align:center; margin-bottom: 20px;">
        <h2 style="font-size: 18px; color: #2c3e50; margin: 0;">Raport Performanță Hotel</h2>
        <p style="font-size: 12px; color: #666; margin: 5px 0;">Perioadă: <strong>de la $start_date_formatted până la $end_date_formatted</strong></p>
    </div>
    EOD;
    $pdf->writeHTML($report_title, true, false, true, false, '');

    // Rezumat performanță
    $summary_title = '<h3 style="font-size: 16px; color: #333;">Rezumat Performanță</h3>';
    $pdf->writeHTML($summary_title, true, false, true, false, '');

    $summary_table = '<table border="1" cellpadding="5">
        <tbody>
            <tr>
                <td width="50%">Rezervări</td>
                <td width="50%">' . ($report_data['reservations']['reservation_count'] ?? 0) . '</td>
            </tr>
            <tr>
                <td width="50%">Venit total</td>
                <td width="50%">' . number_format($report_data['reservations']['total_revenue'] ?? 0, 2) . ' RON</td>
            </tr>
            <tr>
                <td width="50%">Rata de ocupare</td>
                <td width="50%">' . number_format($report_data['occupancy']['occupancy_rate'] ?? 0, 1) . '%</td>
            </tr>
            <tr>
                <td width="50%">Clienți noi</td>
                <td width="50%">' . ($report_data['customers']['new_customers'] ?? 0) . '</td>
            </tr>
            <tr>
                <td width="50%">Servicii utilizate</td>
                <td width="50%">' . ($report_data['services']['service_count'] ?? 0) . '</td>
            </tr>
            <tr>
                <td width="50%">Venit servicii</td>
                <td width="50%">' . number_format($report_data['services']['service_revenue'] ?? 0, 2) . ' RON</td>
            </tr>
        </tbody>
    </table>';
    $pdf->writeHTML($summary_table, true, false, true, false, '');

    // Camere populare
    $popular_rooms_title = '<h3 style="font-size: 16px; color: #333;">Cele mai populare camere</h3>';
    $pdf->writeHTML($popular_rooms_title, true, false, true, false, '');

    if (!empty($report_data['popular_rooms'])) {
        $popular_rooms_table = '<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#f2f2f2;">
                    <th width="50%">Cameră</th>
                    <th width="50%">Număr rezervări</th>
                </tr>
            </thead>
            <tbody>';
        foreach ($report_data['popular_rooms'] as $room) {
            $popular_rooms_table .= '<tr>
                <td width="50%">' . htmlspecialchars($room['RoomNumber']) . '</td>
                <td width="50%">' . $room['reservation_count'] . '</td>
            </tr>';
        }
        $popular_rooms_table .= '</tbody></table>';
        $pdf->writeHTML($popular_rooms_table, true, false, true, false, '');
    } else {
        $no_data = '<p>Nu există date despre camere pentru perioada selectată</p>';
        $pdf->writeHTML($no_data, true, false, true, false, '');
    }

    // Venituri pe tipuri de camere
    $revenue_title = '<h3 style="font-size: 16px; color: #333;">Venituri pe tipuri de camere</h3>';
    $pdf->writeHTML($revenue_title, true, false, true, false, '');

    if (!empty($report_data['revenue_by_type'])) {
        $revenue_table = '<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#f2f2f2;">
                    <th width="50%">Tip pat</th>
                    <th width="50%">Venit total (RON)</th>
                </tr>
            </thead>
            <tbody>';
        foreach ($report_data['revenue_by_type'] as $type) {
            $revenue_table .= '<tr>
                <td width="50%">' . htmlspecialchars($type['BedType']) . '</td>
                <td width="50%">' . number_format($type['total_revenue'], 2) . '</td>
            </tr>';
        }
        $revenue_table .= '</tbody></table>';
        $pdf->writeHTML($revenue_table, true, false, true, false, '');
    } else {
        $no_data = '<p>Nu există date despre venituri pe tipuri de camere</p>';
        $pdf->writeHTML($no_data, true, false, true, false, '');
    }

    // Notă subsol
    $footer_note = '<p style="text-align:center; font-size:10px; color:#666;">Vă mulțumim că utilizați Hotel Grand Plaza!</p>';
    $pdf->writeHTML($footer_note, true, false, true, false, '');

    // Închide și afișează documentul PDF
    $pdf->Output('raport_hotel_' . date('Ymd', strtotime($start_date)) . '_' . date('Ymd', strtotime($end_date)) . '.pdf', 'I');

} elseif ($type === 'excel') {
    // Creează un nou spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Raport Hotel');

    // Setează stilurile
    $headerStyle = [
        'font' => ['bold' => true, 'size' => 12],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f2f2f2']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    ];
    $titleStyle = [
        'font' => ['bold' => true, 'size' => 16],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ];
    $subtitleStyle = [
        'font' => ['size' => 12],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ];
    $cellStyle = [
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    ];

    // Antet hotel
    $sheet->setCellValue('A1', 'Hotel Grand Plaza');
    $sheet->mergeCells('A1:B1');
    $sheet->getStyle('A1')->applyFromArray($titleStyle);

    $sheet->setCellValue('A2', 'Strada Principală 123, București, România');
    $sheet->mergeCells('A2:B2');
    $sheet->getStyle('A2')->applyFromArray($subtitleStyle);

    $sheet->setCellValue('A3', 'Telefon: +40 21 123 4567 | Email: contact@grandplaza.com');
    $sheet->mergeCells('A3:B3');
    $sheet->getStyle('A3')->applyFromArray($subtitleStyle);

    // Titlu raport
    $sheet->setCellValue('A5', 'Raport Performanță Hotel');
    $sheet->mergeCells('A5:B5');
    $sheet->getStyle('A5')->applyFromArray($titleStyle);

    $start_date_formatted = date('d.m.Y', strtotime($start_date));
    $end_date_formatted = date('d.m.Y', strtotime($end_date));
    $sheet->setCellValue('A6', "Perioadă: de la $start_date_formatted până la $end_date_formatted");
    $sheet->mergeCells('A6:B6');
    $sheet->getStyle('A6')->applyFromArray($subtitleStyle);

    // Rezumat performanță
    $sheet->setCellValue('A8', 'Rezumat Performanță');
    $sheet->mergeCells('A8:B8');
    $sheet->getStyle('A8')->applyFromArray($titleStyle);

    $summary_data = [
        ['Rezervări', $report_data['reservations']['reservation_count'] ?? 0],
        ['Venit total', number_format($report_data['reservations']['total_revenue'] ?? 0, 2) . ' RON'],
        ['Rata de ocupare', number_format($report_data['occupancy']['occupancy_rate'] ?? 0, 1) . '%'],
        ['Clienți noi', $report_data['customers']['new_customers'] ?? 0],
        ['Servicii utilizate', $report_data['services']['service_count'] ?? 0],
        ['Venit servicii', number_format($report_data['services']['service_revenue'] ?? 0, 2) . ' RON'],
    ];

    $sheet->fromArray([['Metrică', 'Valoare']], null, 'A10');
    $sheet->getStyle('A10:B10')->applyFromArray($headerStyle);
    $sheet->fromArray($summary_data, null, 'A11');
    $sheet->getStyle('A11:B16')->applyFromArray($cellStyle);

    // Camere populare
    $row = 18;
    $sheet->setCellValue("A$row", 'Cele mai populare camere');
    $sheet->mergeCells("A$row:B$row");
    $sheet->getStyle("A$row")->applyFromArray($titleStyle);
    $row++;

    if (!empty($report_data['popular_rooms'])) {
        $sheet->fromArray([['Cameră', 'Număr rezervări']], null, "A$row");
        $sheet->getStyle("A$row:B$row")->applyFromArray($headerStyle);
        $row++;

        foreach ($report_data['popular_rooms'] as $room) {
            $sheet->fromArray([[$room['RoomNumber'], $room['reservation_count']]], null, "A$row");
            $row++;
        }
        $sheet->getStyle("A20:B" . ($row - 1))->applyFromArray($cellStyle);
    } else {
        $sheet->setCellValue("A$row", 'Nu există date despre camere pentru perioada selectată');
        $sheet->mergeCells("A$row:B$row");
        $row++;
    }

    // Venituri pe tipuri de camere
    $row++;
    $sheet->setCellValue("A$row", 'Venituri pe tipuri de camere');
    $sheet->mergeCells("A$row:B$row");
    $sheet->getStyle("A$row")->applyFromArray($titleStyle);
    $row++;

    if (!empty($report_data['revenue_by_type'])) {
        $sheet->fromArray([['Tip pat', 'Venit total (RON)']], null, "A$row");
        $sheet->getStyle("A$row:B$row")->applyFromArray($headerStyle);
        $row++;

        foreach ($report_data['revenue_by_type'] as $type) {
            $sheet->fromArray([[$type['BedType'], number_format($type['total_revenue'], 2)]], null, "A$row");
            $row++;
        }
        $sheet->getStyle("A" . ($row - count($report_data['revenue_by_type'])) . ":B" . ($row - 1))->applyFromArray($cellStyle);
    } else {
        $sheet->setCellValue("A$row", 'Nu există date despre venituri pe tipuri de camere');
        $sheet->mergeCells("A$row:B$row");
        $row++;
    }

    // Ajustează lățimea coloanelor
    $sheet->getColumnDimension('A')->setWidth(30);
    $sheet->getColumnDimension('B')->setWidth(20);

    // Generează fișierul Excel
    $filename = 'raport_hotel_' . date('Ymd', strtotime($start_date)) . '_' . date('Ymd', strtotime($end_date)) . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// Închide conexiunea la baza de date
$conn = null;
?>