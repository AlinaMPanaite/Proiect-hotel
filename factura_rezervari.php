<?php
session_start();
require_once 'config.php';
require_once 'tcpdf/tcpdf.php';

// Activează afișarea erorilor pentru depanare
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start();

// Verifică sesiunea
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    ob_end_clean();
    echo "Sesiune invalidă. SESSION: ";
    var_dump($_SESSION);
    exit;
}

// Verifică ID-ul
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    ob_end_clean();
    die("ID rezervare invalid: " . htmlspecialchars($_GET['id'] ?? 'lipsă'));
}

$reservation_id = (int)$_GET['id'];

try {
    // Preluăm detaliile rezervării
    $stmt = $conn->prepare("
        SELECT 
            rr.*, 
            CONCAT(c.FirstName, ' ', c.LastName) AS CustomerName, 
            c.Email, 
            c.Phone, 
            c.Address, 
            rm.RoomNumber
        FROM RoomReservations rr
        JOIN Customers c ON rr.CustomerID = c.CustomerID
        JOIN Rooms rm ON rr.RoomID = rm.RoomID
        WHERE rr.ReservationID = :reservation_id
    ");
    $stmt->bindValue(':reservation_id', $reservation_id, PDO::PARAM_INT);
    $stmt->execute();
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        ob_end_clean();
        die("Rezervarea nu a fost găsită pentru ID: $reservation_id");
    }

    // Verificăm ce am primit
    echo "Rezervare găsită: ";
    var_dump($reservation);

    // Preluăm serviciile asociate
    $services_stmt = $conn->prepare("
        SELECT s.ServiceName, sr.TotalAmount, sr.ReservationDate
        FROM ServiceReservations sr
        JOIN Services s ON sr.ServiceID = s.ServiceID
        WHERE sr.CustomerID = :customer_id
        AND sr.ReservationDate BETWEEN :checkin AND :checkout
    ");
    $services_stmt->bindValue(':customer_id', $reservation['CustomerID'], PDO::PARAM_INT);
    $services_stmt->bindValue(':checkin', $reservation['CheckInDate'], PDO::PARAM_STR);
    $services_stmt->bindValue(':checkout', $reservation['CheckOutDate'], PDO::PARAM_STR);
    $services_stmt->execute();
    $services = $services_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Servicii găsite: ";
    var_dump($services);

    // Calculăm totalul serviciilor
    $services_total = 0;
    $services_display = [];
    foreach ($services as $service) {
        $services_total += (float)$service['TotalAmount'];
        $services_display[] = [
            'name' => $service['ServiceName'],
            'date' => date('d.m.Y', strtotime($service['ReservationDate'])),
            'amount' => number_format($service['TotalAmount'], 2, ',', '')
        ];
    }
    $grand_total = (float)$reservation['TotalAmount'] + $services_total;

    // Inițializăm TCPDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Hotel Management');
    $pdf->SetTitle('Factură Rezervare #' . $reservation_id);
    $pdf->SetSubject('Factură pentru client');
    $pdf->SetKeywords('factură, rezervare, hotel');
    $pdf->SetMargins(15, 20, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(true);
    $pdf->AddPage();

    // Antet factură
    $html = '
    <table border="0" cellpadding="5">
        <tr>
            <td width="50%">
                <h2>Hotel Confort</h2>
                <p>
                    Str. Principală, Nr. 123, București, România<br>
                    CUI: RO12345678<br>
                    Reg. Com.: J40/1234/2020<br>
                    Telefon: +40 123 456 789<br>
                    Email: contact@hotelconfort.ro
                </p>
            </td>
            <td width="50%" align="right">
                <h1>FACTURĂ</h1>
                <p>
                    Număr factură: ' . sprintf("FR%06d", $reservation_id) . '<br>
                    Data emiterii: ' . date('d.m.Y') . '<br>
                    Data scadenței: ' . date('d.m.Y', strtotime('+7 days')) . '
                </p>
            </td>
        </tr>
    </table>
    <hr>
    <h3>Către:</h3>
    <p>
        ' . htmlspecialchars($reservation['CustomerName']) . '<br>
        Email: ' . htmlspecialchars($reservation['Email']) . '<br>
        Telefon: ' . htmlspecialchars($reservation['Phone']) . '<br>
        Adresă: ' . htmlspecialchars($reservation['Address']) . '
    </p>
    <h3>Detalii Rezervare</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr style="background-color: #f2f2f2;">
            <th>Descriere</th>
            <th>Data</th>
            <th>Suma (RON)</th>
        </tr>
        <tr>
            <td>Cameră ' . htmlspecialchars($reservation['RoomNumber']) . '</td>
            <td>' . date('d.m.Y', strtotime($reservation['CheckInDate'])) . ' - ' . date('d.m.Y', strtotime($reservation['CheckOutDate'])) . '</td>
            <td align="right">' . number_format($reservation['TotalAmount'], 2, ',', '') . '</td>
        </tr>';

    foreach ($services_display as $service) {
        $html .= '
        <tr>
            <td>' . htmlspecialchars($service['name']) . '</td>
            <td>' . $service['date'] . '</td>
            <td align="right">' . $service['amount'] . '</td>
        </tr>';
    }

    $html .= '
        <tr>
            <td colspan="2" align="right"><strong>Total Cameră:</strong></td>
            <td align="right">' . number_format($reservation['TotalAmount'], 2, ',', '') . '</td>
        </tr>
        <tr>
            <td colspan="2" align="right"><strong>Total Servicii:</strong></td>
            <td align="right">' . number_format($services_total, 2, ',', '') . '</td>
        </tr>
        <tr>
            <td colspan="2" align="right"><strong>TOTAL GENERAL:</strong></td>
            <td align="right"><strong>' . number_format($grand_total, 2, ',', '') . '</strong></td>
        </tr>
    </table>
    <p style="margin-top: 20px;">
        Vă mulțumim pentru alegerea Hotel Confort!<br>
        Pentru orice întrebări, contactați-ne la contact@hotelconfort.ro sau +40 123 456 789.
    </p>';

    $pdf->writeHTML($html, true, false, true, false, '');

    $stmt = $conn->prepare("INSERT INTO istoric_utilizare (UserID, Operatie) VALUES (:user_id, :operatie)");
    $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $operatie = "Generare factură rezervare #$reservation_id pentru " . $reservation['CustomerName'];
    $stmt->bindValue(':operatie', $operatie, PDO::PARAM_STR);
    $stmt->execute();

    ob_end_clean();
    $pdf->Output('factura_rezervare_' . $reservation_id . '.pdf', 'I');

} catch (PDOException $e) {
    ob_end_clean();
    die("Eroare SQL: " . htmlspecialchars($e->getMessage()));
}
?>