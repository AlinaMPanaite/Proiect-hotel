<?php
session_start();

// Verify if user is authenticated and has admin role
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hotel";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize variables
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$error = "";
$report_data = [];

// Generate report if dates are submitted
if (isset($_GET['generate_report'])) {
    // Validate dates
    if (empty($start_date) || empty($end_date)) {
        $error = "Both date fields are required!";
    } elseif ($start_date > $end_date) {
        $error = "Start date cannot be after end date!";
    } else {
        try {
            // Get reservations data
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

           // Get room occupancy data
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

            // Get customers data
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

            // Get services data
            $stmt = $conn->prepare("
                SELECT COUNT(*) as service_count, SUM(TotalAmount) as service_revenue
                FROM ServiceReservations
                WHERE ReservationDate BETWEEN :start_date AND :end_date
            ");
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            $stmt->execute();
            $report_data['services'] = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get popular rooms
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

            // Get revenue by room type
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
            $error = "Error generating report: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel - Rapoarte</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="raport.css">
    <link rel="stylesheet" href="admin.css">

</head>

<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <h1>Hotel Management - Rapoarte</h1>
            </div>
            <div class="user-menu">
                <span>Bine ai venit, <?php echo $_SESSION['email']; ?>!</span>
                <a href="admin.php" class="logout-btn">Înapoi la panou</a>
                <a href="main.php" class="logout-btn">Deconectare</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="report-container">
            <div class="report-header">
                <h2 class="report-title">Raport Hotel</h2>
                <p class="report-subtitle">Analiză performanță și statistici</p>
            </div>

            <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <div class="no-print">
                <form method="GET" class="date-form">
                    <div class="form-group">
                        <label for="start_date">Dată început</label>
                        <input type="date" id="start_date" name="start_date" class="form-control"
                            value="<?php echo $start_date; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="end_date">Dată sfârșit</label>
                        <input type="date" id="end_date" name="end_date" class="form-control"
                            value="<?php echo $end_date; ?>" required>
                    </div>

                    <button type="submit" name="generate_report" class="btn btn-success">
                        <i class="fas fa-chart-bar"></i> Generează raport
                    </button>

                    <button type="button" class="print-btn" onclick="window.print()">
                        <i class="fas fa-print"></i> Printează
                    </button>
                </form>

                <div class="period-info">
                    <p>Perioadă selectată: <strong><?php echo date('d.m.Y', strtotime($start_date)); ?> -
                            <?php echo date('d.m.Y', strtotime($end_date)); ?></strong></p>
                </div>
            </div>

            <?php if (!empty($report_data)): ?>
            <div class="report-content">
                <h3><i class="fas fa-chart-line"></i> Rezumat performanță</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                        <div class="stat-value"><?php echo $report_data['reservations']['reservation_count'] ?? 0; ?>
                        </div>
                        <div class="stat-label">Rezervări</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                        <div class="stat-value">
                            <?php echo number_format($report_data['reservations']['total_revenue'] ?? 0, 2); ?> RON
                        </div>
                        <div class="stat-label">Venit total</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-bed"></i></div>
                        <div class="stat-value">
                            <?php echo number_format($report_data['occupancy']['occupancy_rate'] ?? 0, 1); ?>%</div>
                        <div class="stat-label">Rata de ocupare</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-value"><?php echo $report_data['customers']['new_customers'] ?? 0; ?></div>
                        <div class="stat-label">Clienți noi</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-concierge-bell"></i></div>
                        <div class="stat-value"><?php echo $report_data['services']['service_count'] ?? 0; ?></div>
                        <div class="stat-label">Servicii utilizate</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-coins"></i></div>
                        <div class="stat-value">
                            <?php echo number_format($report_data['services']['service_revenue'] ?? 0, 2); ?> RON</div>
                        <div class="stat-label">Venit servicii</div>
                    </div>
                </div>

                <div class="report-section">
                    <h3><i class="fas fa-star"></i> Cele mai populare camere</h3>
                    <?php if (!empty($report_data['popular_rooms'])): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Cameră</th>
                                <th>Număr rezervări</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data['popular_rooms'] as $room): ?>
                            <tr>
                                <td><?php echo $room['RoomNumber']; ?></td>
                                <td><?php echo $room['reservation_count']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="no-data">Nu există date despre camere pentru perioada selectată</div>
                    <?php endif; ?>
                </div>

                <div class="report-section">
                    <h3><i class="fas fa-chart-pie"></i> Venituri pe tipuri de camere</h3>
                    <?php if (!empty($report_data['revenue_by_type'])): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tip pat</th>
                                <th>Venit total (RON)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data['revenue_by_type'] as $type): ?>
                            <tr>
                                <td><?php echo $type['BedType']; ?></td>
                                <td><?php echo number_format($type['total_revenue'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="no-data">Nu există date despre venituri pe tipuri de camere</div>
                    <?php endif; ?>
                </div>

                <div class="report-section no-print">
                    <h3><i class="fas fa-download"></i> Export raport</h3>
                    <div class="export-options">
                        <a href="export_report.php?type=pdf&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>"
                            class="btn btn-warning" target="_blank">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </a>
                        <a href="export_report.php?type=excel&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>"
                            class="btn">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="no-data">
                <i class="fas fa-info-circle"></i> Selectați o perioadă și apăsați "Generează raport" pentru a vizualiza
                datele.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Set default dates to current month if not set
    document.addEventListener('DOMContentLoaded', function() {
        if (!document.getElementById('start_date').value) {
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            document.getElementById('start_date').valueAsDate = firstDay;
        }

        if (!document.getElementById('end_date').value) {
            const today = new Date();
            const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            document.getElementById('end_date').valueAsDate = lastDay;
        }
    });
    </script>
</body>

</html>