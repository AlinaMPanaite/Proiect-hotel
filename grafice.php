<?php
session_start();

// Verify if user is authenticated
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
$chart_type = isset($_GET['chart_type']) ? $_GET['chart_type'] : 'daily_revenue';
$error = "";
$chart_data = [];

// Generate chart data if dates are submitted
if (isset($_GET['generate_chart'])) {
    // Validate dates
    if (empty($start_date) || empty($end_date)) {
        $error = "Both date fields are required!";
    } elseif ($start_date > $end_date) {
        $error = "Start date cannot be after end date!";
    } else {
        try {
            switch ($chart_type) {
                case 'daily_revenue':
                    // Get daily revenue data
                    $stmt = $conn->prepare("
                        SELECT DATE(CheckInDate) as date, SUM(TotalAmount) as revenue
                        FROM Roomreservations
                        WHERE CheckInDate BETWEEN :start_date AND :end_date
                        GROUP BY DATE(CheckInDate)
                        ORDER BY DATE(CheckInDate)
                    ");
                    $stmt->bindParam(':start_date', $start_date);
                    $stmt->bindParam(':end_date', $end_date);
                    $stmt->execute();
                    $chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
                
                case 'room_type_revenue':
                    // Get revenue by room type
                    $stmt = $conn->prepare("
                        SELECT r.BedType as room_type, SUM(rr.TotalAmount) as revenue
                        FROM Roomreservations rr
                        JOIN Rooms r ON rr.RoomID = r.RoomID
                        WHERE rr.CheckInDate BETWEEN :start_date AND :end_date
                        GROUP BY r.BedType
                        ORDER BY revenue DESC
                    ");
                    $stmt->bindParam(':start_date', $start_date);
                    $stmt->bindParam(':end_date', $end_date);
                    $stmt->execute();
                    $chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
                
                case 'occupancy_rate':
                    // Get daily occupancy rate
                    $stmt = $conn->prepare("
                        SELECT 
                            dates.date as date,
                            COUNT(DISTINCT rr.RoomID) as occupied_rooms,
                            (SELECT COUNT(*) FROM Rooms) as total_rooms,
                            (COUNT(DISTINCT rr.RoomID) / (SELECT COUNT(*) FROM Rooms) * 100) as occupancy_rate
                        FROM 
                            (SELECT DATE(:start_date) + INTERVAL a + b DAY as date
                             FROM 
                                (SELECT 0 a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3
                                 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7
                                 UNION SELECT 8 UNION SELECT 9) d,
                                (SELECT 0 b UNION SELECT 10 UNION SELECT 20 UNION SELECT 30
                                 UNION SELECT 40 UNION SELECT 50 UNION SELECT 60 UNION SELECT 70
                                 UNION SELECT 80 UNION SELECT 90) m
                             WHERE DATE(:start_date) + INTERVAL a + b DAY <= DATE(:end_date)
                            ) dates
                        LEFT JOIN Roomreservations rr ON dates.date BETWEEN DATE(rr.CheckInDate) AND DATE(rr.CheckOutDate)
                        GROUP BY dates.date
                        ORDER BY dates.date
                    ");
                    $stmt->bindParam(':start_date', $start_date);
                    $stmt->bindParam(':end_date', $end_date);
                    $stmt->execute();
                    $chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
                
                case 'service_categories':
                    // Get revenue by service categories
                    $stmt = $conn->prepare("
                        SELECT s.ServiceName as service, COUNT(sr.ServiceID) as count, SUM(s.Price) as revenue
                        FROM ServiceReservations sr
                        JOIN Services s ON sr.ServiceID = s.ServiceID
                        WHERE sr.ReservationDate BETWEEN :start_date AND :end_date
                        GROUP BY s.ServiceID
                        ORDER BY revenue DESC
                    ");
                    $stmt->bindParam(':start_date', $start_date);
                    $stmt->bindParam(':end_date', $end_date);
                    $stmt->execute();
                    $chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
            }
        } catch(PDOException $e) {
            $error = "Error generating chart data: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel - Grafice</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .chart-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .chart-title {
            font-size: 24px;
            color: #333;
            margin: 0;
        }
        .chart-form {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        .chart-form .form-group {
            margin-bottom: 0;
            min-width: 200px;
        }
        .chart-wrapper {
            position: relative;
            height: 400px;
            width: 100%;
        }
        .chart-options {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .chart-option {
            padding: 8px 15px;
            background-color: #f5f5f5;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .chart-option:hover {
            background-color: #e0e0e0;
        }
        .chart-option.active {
            background-color: #3498db;
            color: white;
        }
        .no-data {
            text-align: center;
            padding: 50px;
            color: #7f8c8d;
            font-style: italic;
        }
        @media (max-width: 768px) {
            .chart-form {
                flex-direction: column;
                align-items: stretch;
            }
            .chart-form .form-group {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <h1>Hotel Management - Grafice</h1>
            </div>
            <div class="user-menu">
                <span>Bine ai venit, <?php echo $_SESSION['email']; ?>!</span>
                <a href="admin.php" class="logout-btn">Înapoi la panou</a>
                <a href="logout.php" class="logout-btn">Deconectare</a>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="chart-container">
            <div class="chart-header">
                <h2 class="chart-title">Analiză vizuală a datelor</h2>
                <p class="chart-subtitle">Generați grafice pentru diferite aspecte ale afacerii</p>
            </div>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="GET" class="chart-form">
                <input type="hidden" name="chart_type" value="<?php echo $chart_type; ?>">
                
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
                
                <div class="form-group">
                    <button type="submit" name="generate_chart" class="btn btn-success">
                        <i class="fas fa-chart-line"></i> Generează grafic
                    </button>
                </div>
            </form>
            
            <div class="chart-options">
                <div class="chart-option <?php echo $chart_type === 'daily_revenue' ? 'active' : ''; ?>" 
                     onclick="changeChartType('daily_revenue')">
                    <i class="fas fa-money-bill-wave"></i> Venit zilnic
                </div>
                <div class="chart-option <?php echo $chart_type === 'room_type_revenue' ? 'active' : ''; ?>" 
                     onclick="changeChartType('room_type_revenue')">
                    <i class="fas fa-bed"></i> Venit pe tip cameră
                </div>
                <div class="chart-option <?php echo $chart_type === 'occupancy_rate' ? 'active' : ''; ?>" 
                     onclick="changeChartType('occupancy_rate')">
                    <i class="fas fa-percentage"></i> Rata de ocupare
                </div>
                <div class="chart-option <?php echo $chart_type === 'service_categories' ? 'active' : ''; ?>" 
                     onclick="changeChartType('service_categories')">
                    <i class="fas fa-concierge-bell"></i> Servicii pe categorii
                </div>
            </div>
            
            <div class="period-info">
                <p>Perioadă selectată: <strong><?php echo date('d.m.Y', strtotime($start_date)); ?> - <?php echo date('d.m.Y', strtotime($end_date)); ?></strong></p>
            </div>
            
            <div class="chart-wrapper">
                <?php if (!empty($chart_data)): ?>
                    <canvas id="dataChart"></canvas>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-chart-pie"></i>
                        <p>Selectați o perioadă și un tip de grafic, apoi apăsați "Generează grafic" pentru a vizualiza datele.</p>
                    </div>
                <?php endif; ?>
            </div>
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
            
            // Initialize chart if data exists
            <?php if (!empty($chart_data)): ?>
                renderChart();
            <?php endif; ?>
        });
        
        function changeChartType(type) {
            document.querySelector('input[name="chart_type"]').value = type;
            document.querySelector('.chart-option.active').classList.remove('active');
            event.target.classList.add('active');
        }
        
        function renderChart() {
            const ctx = document.getElementById('dataChart').getContext('2d');
            
            <?php
            switch ($chart_type) {
                case 'daily_revenue':
                    $labels = array_map(function($item) { return date('d M', strtotime($item['date'])); }, $chart_data);
                    $data = array_map(function($item) { return $item['revenue']; }, $chart_data);
                    $label = "Venit zilnic (RON)";
                    $chartType = "line";
                    break;
                
                case 'room_type_revenue':
                    $labels = array_map(function($item) { return $item['room_type']; }, $chart_data);
                    $data = array_map(function($item) { return $item['revenue']; }, $chart_data);
                    $label = "Venit pe tip cameră (RON)";
                    $chartType = "bar";
                    break;
                
                case 'occupancy_rate':
                    $labels = array_map(function($item) { return date('d M', strtotime($item['date'])); }, $chart_data);
                    $data = array_map(function($item) { return $item['occupancy_rate']; }, $chart_data);
                    $label = "Rata de ocupare (%)";
                    $chartType = "line";
                    break;
                
                case 'service_categories':
                    $labels = array_map(function($item) { return $item['service']; }, $chart_data);
                    $data = array_map(function($item) { return $item['revenue']; }, $chart_data);
                    $label = "Venit servicii pe categorii (RON)";
                    $chartType = "doughnut";
                    break;
            }
            ?>
            
            const chart = new Chart(ctx, {
                type: '<?php echo $chartType; ?>',
                data: {
                    labels: <?php echo json_encode($labels); ?>,
                    datasets: [{
                        label: '<?php echo $label; ?>',
                        data: <?php echo json_encode($data); ?>,
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.5)',
                            'rgba(255, 99, 132, 0.5)',
                            'rgba(75, 192, 192, 0.5)',
                            'rgba(255, 206, 86, 0.5)',
                            'rgba(153, 102, 255, 0.5)',
                            'rgba(255, 159, 64, 0.5)'
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 99, 132, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(255, 159, 64, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: '<?php echo $label; ?> - <?php echo date("d.m.Y", strtotime($start_date)) . " - " . date("d.m.Y", strtotime($end_date)); ?>',
                            font: {
                                size: 16
                            }
                        },
                        legend: {
                            position: '<?php echo $chartType === "doughnut" ? "right" : "top"; ?>'
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>