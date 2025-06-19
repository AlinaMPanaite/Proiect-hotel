<?php
session_start();

// Verifică dacă utilizatorul este autentificat, are rolul de client și user_id este valid
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0 || !isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
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
$page = isset($_GET['page']) ? $_GET['page'] : 'rooms';

// Setare variabile de sortare specifice pentru fiecare pagină
if ($page === 'rooms') {
    $sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'RoomNumber';
} elseif ($page === 'services') {
    $sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'ServiceName';
} else {
    $sort_by = 'ReservationID'; // Default pentru alte pagini
}

$sort_order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
$error = "";
$success = "";

// Inițializare variabile de căutare și filtrare
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$min_price = isset($_GET['min_price']) ? $_GET['min_price'] : '';
$max_price = isset($_GET['max_price']) ? $_GET['max_price'] : '';
$price_filter = isset($_GET['price_filter']) ? $_GET['price_filter'] : '';
$capacity_filter = isset($_GET['capacity']) ? $_GET['capacity'] : '';

// Procesare rezervare cameră
if (isset($_POST['reserve_room'])) {
    $room_id = $_POST['room_id'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    
    // Validare date
    if (empty($check_in) || empty($check_out)) {
        $error = "Vă rugăm să completați datele de check-in și check-out!";
    } elseif (strtotime($check_in) < strtotime(date('Y-m-d'))) {
        $error = "Data de check-in nu poate fi în trecut!";
    } elseif (strtotime($check_out) <= strtotime($check_in)) {
        $error = "Data de check-out trebuie să fie după data de check-in!";
    } else {
        try {
            // Verifică disponibilitatea camerei în perioada selectată
            $stmt = $conn->prepare("
                SELECT * FROM roomreservations 
                WHERE RoomID = :room_id 
                AND ((CheckInDate <= :check_in AND CheckOutDate >= :check_in) 
                OR (CheckInDate <= :check_out AND CheckOutDate >= :check_out)
                OR (CheckInDate >= :check_in AND CheckOutDate <= :check_out))
            ");
            $stmt->bindParam(':room_id', $room_id);
            $stmt->bindParam(':check_in', $check_in);
            $stmt->bindParam(':check_out', $check_out);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = "Camera nu este disponibilă în perioada selectată!";
            } else {
                // Obține prețul per noapte al camerei
                $stmt = $conn->prepare("SELECT PricePerNight FROM Rooms WHERE RoomID = :room_id");
                $stmt->bindParam(':room_id', $room_id);
                $stmt->execute();
                $room = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Calculează numărul de nopți și suma totală
                $nights = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
                $total_amount = $room['PricePerNight'] * $nights;
                
                // Creare rezervare
                $stmt = $conn->prepare("
                    INSERT INTO roomreservations (CustomerID, RoomID, CheckInDate, CheckOutDate, TotalAmount) 
                    VALUES (:customer_id, :room_id, :check_in, :check_out, :total_amount)
                ");
                $stmt->bindParam(':customer_id', $_SESSION['user_id']);
                $stmt->bindParam(':room_id', $room_id);
                $stmt->bindParam(':check_in', $check_in);
                $stmt->bindParam(':check_out', $check_out);
                $stmt->bindParam(':total_amount', $total_amount);
                $stmt->execute();
                
                // Înregistrăm acțiunea în istoric_utilizare
                $stmt = $conn->prepare("INSERT INTO istoric_utilizare (UserID, Operatie) VALUES (:user_id, 'Rezervare cameră')");
                $stmt->bindParam(':user_id', $_SESSION['user_id']);
                $stmt->execute();
                
                $success = "Rezervarea a fost efectuată cu succes!";
            }
        } catch(PDOException $e) {
            $error = "Eroare: " . $e->getMessage();
        }
    }
}
// Procesare rezervare serviciu
if (isset($_POST['reserve_service'])) {
    $service_id = $_POST['service_id'];
    $date = $_POST['service_date'];
    $time = $_POST['service_time'];
    
    try {
        // Verificăm dacă user_id este valid
        if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
            throw new Exception("Sesiune invalidă. Vă rugăm să vă autentificați din nou.");
        }
        
        // Începe tranzacție
        $conn->beginTransaction();
        
        // Obținem datele utilizatorului din users
        $stmt = $conn->prepare("SELECT FirstName, LastName, Email FROM users WHERE UserID = :user_id");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception("Utilizatorul nu există în tabela users!");
        }
        
        // Verificăm dacă utilizatorul există în tabela customers
        $stmt = $conn->prepare("SELECT CustomerID FROM customers WHERE CustomerID = :customer_id");
        $stmt->bindParam(':customer_id', $_SESSION['user_id']);
        $stmt->execute();
        $exists = $stmt->fetchColumn();
        
        if (!$exists) {
            // Verificăm dacă email-ul este deja folosit în customers
            $stmt = $conn->prepare("SELECT CustomerID FROM customers WHERE Email = :email");
            $stmt->bindParam(':email', $user['Email']);
            $stmt->execute();
            $existing_customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_customer && $existing_customer['CustomerID'] != $_SESSION['user_id']) {
                // Actualizăm CustomerID pentru a-l alinia cu UserID
                $stmt = $conn->prepare("UPDATE customers SET CustomerID = :new_customer_id WHERE CustomerID = :old_customer_id");
                $stmt->bindParam(':new_customer_id', $_SESSION['user_id']);
                $stmt->bindParam(':old_customer_id', $existing_customer['CustomerID']);
                $stmt->execute();
                
                if ($stmt->rowCount() == 0) {
                    throw new Exception("Eroare la actualizarea CustomerID pentru email-ul {$user['Email']}");
                }
            } else {
                // Creăm o înregistrare în customers
                $stmt = $conn->prepare("
                    INSERT INTO customers (CustomerID, FirstName, LastName, Email, Phone, Address) 
                    VALUES (:customer_id, :first_name, :last_name, :email, NULL, NULL)
                ");
                $stmt->bindParam(':customer_id', $_SESSION['user_id']);
                $stmt->bindParam(':first_name', $user['FirstName']);
                $stmt->bindParam(':last_name', $user['LastName']);
                $stmt->bindParam(':email', $user['Email']);
                $stmt->execute();
            }
        }
        
        // Combină data și ora
        $datetime = $date . ' ' . $time;
        
        // Obține prețul serviciului
        $stmt = $conn->prepare("SELECT Price FROM Services WHERE ServiceID = :service_id");
        $stmt->bindParam(':service_id', $service_id);
        $stmt->execute();
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Creare rezervare serviciu (fără ServiceReservationID, generat automat)
        $stmt = $conn->prepare("
            INSERT INTO servicereservations (CustomerID, ServiceID, ReservationDate, OraRezervare, TotalAmount) 
            VALUES (:customer_id, :service_id, :reservation_date, :ora_rezervare, :total_amount)
        ");
        $stmt->bindParam(':customer_id', $_SESSION['user_id']);
        $stmt->bindParam(':service_id', $service_id);
        $stmt->bindParam(':reservation_date', $date);
        $stmt->bindParam(':ora_rezervare', $time);
        $stmt->bindParam(':total_amount', $service['Price']);
        $stmt->execute();
        
        // Înregistrăm acțiunea în istoric_utilizare
        $stmt = $conn->prepare("INSERT INTO istoric_utilizare (UserID, Operatie) VALUES (:user_id, 'Rezervare serviciu')");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        
        // Confirmăm tranzacția
        $conn->commit();
        
        $success = "Serviciul a fost rezervat cu succes!";
    } catch(PDOException $e) {
        $conn->rollBack();
        $error = "Eroare: " . $e->getMessage();
    } catch(Exception $e) {
        $conn->rollBack();
        $error = "Eroare: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel - Portal Client</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="client.css">
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <h1>Hotel Management</h1>
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
                    <a href="?page=rooms" class="<?php echo $page === 'rooms' ? 'active' : ''; ?>">
                        <i class="fas fa-bed"></i> Camere
                    </a>
                    <a href="?page=services" class="<?php echo $page === 'services' ? 'active' : ''; ?>">
                        <i class="fas fa-concierge-bell"></i> Servicii
                    </a>
                    <a href="?page=roomreservations" class="<?php echo $page === 'roomreservations' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check"></i> Rezervările mele
                    </a>
                    <a href="?page=profile" class="<?php echo $page === 'profile' ? 'active' : ''; ?>">
                        <i class="fas fa-user"></i> Profilul meu
                    </a>
                </div>
            </div>
        </nav>

        <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?php echo $success; ?>
        </div>
        <?php endif; ?>

        <?php if ($page === 'rooms'): ?>
        <div class="card">
            <div class="card-header">
                <div>Camere disponibile</div>
                <div class="sort-controls">
                    <a href="?page=rooms&sort=RoomNumber&order=<?php echo $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search_term); ?>&min_price=<?php echo urlencode($min_price); ?>&max_price=<?php echo urlencode($max_price); ?>&price_filter=<?php echo urlencode($price_filter); ?>&capacity=<?php echo urlencode($capacity_filter); ?>" class="sort-btn">
                        <i class="fas fa-sort"></i> Număr cameră
                    </a>
                    <a href="?page=rooms&sort=PricePerNight&order=<?php echo $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search_term); ?>&min_price=<?php echo urlencode($min_price); ?>&max_price=<?php echo urlencode($max_price); ?>&price_filter=<?php echo urlencode($price_filter); ?>&capacity=<?php echo urlencode($capacity_filter); ?>" class="sort-btn">
                        <i class="fas fa-sort"></i> Preț
                    </a>
                </div>
            </div>

            <!-- Adăugăm filtrare după capacitate -->
            <div class="search-bar">
                <form method="GET" class="search-form">
                    <input type="hidden" name="page" value="rooms">
                    <input type="hidden" name="sort" value="<?php echo $sort_by; ?>">
                    <input type="hidden" name="order" value="<?php echo $sort_order; ?>">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                    <input type="hidden" name="min_price" value="<?php echo htmlspecialchars($min_price); ?>">
                    <input type="hidden" name="max_price" value="<?php echo htmlspecialchars($max_price); ?>">
                    <input type="hidden" name="price_filter" value="<?php echo htmlspecialchars($price_filter); ?>">

                    <!-- Butoane pentru filtrarea după capacitate -->
                    <div class="capacity-filter">
                        <span style="margin-right: 10px; align-self: center;">Filtrează după număr de persoane: </span>
                        <button type="submit" name="capacity" value="" class="capacity-btn <?php echo $capacity_filter === '' ? 'active' : ''; ?>">Toate</button>
                        <button type="submit" name="capacity" value="1" class="capacity-btn <?php echo $capacity_filter === '1' ? 'active' : ''; ?>">1 persoană</button>
                        <button type="submit" name="capacity" value="2" class="capacity-btn <?php echo $capacity_filter === '2' ? 'active' : ''; ?>">2 persoane</button>
                        <button type="submit" name="capacity" value="4" class="capacity-btn <?php echo $capacity_filter === '4' ? 'active' : ''; ?>">4 persoane</button>
                    </div>
                </form>
            </div>

            <!-- Bara de căutare și filtrare pentru camere -->
            <div class="search-bar">
                <form method="GET" class="search-form">
                    <input type="hidden" name="page" value="rooms">
                    <input type="hidden" name="sort" value="<?php echo $sort_by; ?>">
                    <input type="hidden" name="order" value="<?php echo $sort_order; ?>">
                    <input type="hidden" name="capacity" value="<?php echo htmlspecialchars($capacity_filter); ?>">

                    <div class="search-group">
                        <input type="text" name="search" placeholder="Caută după număr cameră, tip pat, facilități..." value="<?php echo htmlspecialchars($search_term); ?>">
                        <i class="fas fa-search"></i>
                    </div>

                    <div class="filter-group">
                        <select name="price_filter" id="price_filter">
                            <option value="" hidden <?php echo $price_filter === '' ? 'selected' : ''; ?>>Filtru preț</option>
                            <option value="min" <?php echo $price_filter === 'min' ? 'selected' : ''; ?>>Mai mare decât</option>
                            <option value="max" <?php echo $price_filter === 'max' ? 'selected' : ''; ?>>Mai mic decât</option>
                            <option value="between" <?php echo $price_filter === 'between' ? 'selected' : ''; ?>>Între</option>
                        </select>

                        <div class="price-range" id="min_price_container" <?php echo ($price_filter === 'min' || $price_filter === 'between') ? '' : 'style="display:none;"'; ?>>
                            <input type="number" name="min_price" placeholder="Preț min" value="<?php echo htmlspecialchars($min_price); ?>">
                        </div>

                        <div class="price-range" id="max_price_container" <?php echo ($price_filter === 'max' || $price_filter === 'between') ? '' : 'style="display:none;"'; ?>>
                            <input type="number" name="max_price" placeholder="Preț max" value="<?php echo htmlspecialchars($max_price); ?>">
                        </div>

                        <button type="submit" class="btn-search">
                            <i class="fas fa-filter"></i> Filtrează
                        </button>

                        <a href="?page=rooms" class="btn-reset">
                            <i class="fas fa-undo"></i> Resetează
                        </a>
                    </div>
                </form>
            </div>

            <div class="card-body">
                <?php
                try {
                    // Construiește query-ul de căutare și filtrare pentru camere
                    $query = "SELECT * FROM Rooms WHERE 1=1";
                    $params = [];
                    
                    // Adaugă condiții de căutare
                    if (!empty($search_term)) {
                        $query .= " AND (
                            RoomNumber LIKE :search OR 
                            BedType LIKE :search OR 
                            Description LIKE :search OR
                            (LOWER(:search_term) = 'balcon' AND HasBalcony = 1) OR
                            (LOWER(:search_term) = 'ac' AND HasAC = 1)
                        )";
                        $params[':search'] = '%' . $search_term . '%';
                        $params[':search_term'] = strtolower($search_term);
                    }
                    
                    // Adaugă filtru pentru capacitate
                    if (!empty($capacity_filter)) {
                        $query .= " AND Capacity = :capacity";
                        $params[':capacity'] = $capacity_filter;
                    }
                    
                    // Adaugă condiții de filtrare a prețului DOAR dacă există filtre selectate
                    if ($price_filter === 'min' && !empty($min_price)) {
                        $query .= " AND PricePerNight >= :min_price";
                        $params[':min_price'] = $min_price;
                    } elseif ($price_filter === 'max' && !empty($max_price)) {
                        $query .= " AND PricePerNight <= :max_price";
                        $params[':max_price'] = $max_price;
                    } elseif ($price_filter === 'between' && !empty($min_price) && !empty($max_price)) {
                        $query .= " AND PricePerNight BETWEEN :min_price AND :max_price";
                        $params[':min_price'] = $min_price;
                        $params[':max_price'] = $max_price;
                    }
                    
                    // Adaugă ordinea sortării
                    $query .= " ORDER BY $sort_by $sort_order";
                    $stmt = $conn->prepare($query);
                    foreach ($params as $key => $value) {
                        $stmt->bindValue($key, $value);
                    }
                    $stmt->execute();
                    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Afișează numărul de rezultate
                    echo '<div class="results-count">Rezultate găsite: ' . count($rooms) . '</div>';
                    
                    echo '<div class="items-grid">';
                    
                    if (count($rooms) > 0) {
                        foreach ($rooms as $room) {
                            echo '<div class="item-card">';
                            echo '<div class="item-image"><i class="fas fa-bed"></i></div>';
                            echo '<div class="item-details">';
                            echo '<div class="item-title">Camera ' . $room['RoomNumber'] . '</div>';
                            echo '<div class="item-price">' . $room['PricePerNight'] . ' RON / noapte</div>';
                            echo '<div class="item-description">' . $room['Description'] . '</div>';
                            echo '<div class="item-features">';
                            echo '<span class="feature"><i class="fas fa-user"></i> ' . $room['Capacity'] . ' persoane</span>';
                            echo '<span class="feature"><i class="fas fa-bed"></i> ' . $room['BedType'] . '</span>';
                            if ($room['HasAC']) echo '<span class="feature"><i class="fas fa-snowflake"></i> AC</span>';
                            if ($room['HasBalcony']) echo '<span class="feature"><i class="fas fa-door-open"></i> Balcon</span>';
                            
                            // Adaugă butonul cu popup pentru facilități
                            if (!empty($room['Facilities'])) {
                                echo '<button class="facilities-btn">';
                                echo '<i class="fas fa-info-circle"></i> ';
                                echo '<div class="facilities-popup">';
                                echo '<ul class="facilities-list">';
                                $facilities = explode(',', $room['Facilities']);
                                foreach ($facilities as $facility) {
                                    $facility = trim($facility);
                                    $icon = 'check-circle'; // Default icon
                                    
                                    // Atribuie iconițe specifice pentru facilități comune
                                    if (stripos($facility, 'WiFi') !== false) {
                                        $icon = 'wifi';
                                    } elseif (stripos($facility, 'TV') !== false) {
                                        $icon = 'tv';
                                    } elseif (stripos($facility, 'Aer') !== false || stripos($facility, 'AC') !== false) {
                                        $icon = 'snowflake';
                                    } elseif (stripos($facility, 'Birou') !== false) {
                                        $icon = 'desktop';
                                    } elseif (stripos($facility, 'Seif') !== false) {
                                        $icon = 'lock';
                                    } elseif (stripos($facility, 'Balcon') !== false) {
                                        $icon = 'door-open';
                                    } elseif (stripos($facility, 'Jacuzzi') !== false || stripos($facility, 'Cadă') !== false) {
                                        $icon = 'bath';
                                    } elseif (stripos($facility, 'Uscător') !== false) {
                                        $icon = 'wind';
                                    } elseif (stripos($facility, 'Cafetieră') !== false) {
                                        $icon = 'coffee';
                                    } elseif (stripos($facility, 'Vedere') !== false) {
                                        $icon = 'eye';
                                    } elseif (stripos($facility, 'SPA') !== false) {
                                        $icon = 'spa';
                                    } elseif (stripos($facility, 'Halate') !== false) {
                                        $icon = 'tshirt';
                                    }
                                    
                                    echo '<li><i class="fas fa-' . $icon . '"></i> ' . htmlspecialchars($facility) . '</li>';
                                }
                                echo '</ul>';
                                echo '</div>';
                                echo '</button>';
                            }
                            
                            echo '</div>';
                            echo '<div class="item-actions">';
                            echo '<button class="btn btn-reserve" onclick="openReserveModal(' . $room['RoomID'] . ', \'Camera ' . $room['RoomNumber'] . '\', ' . $room['PricePerNight'] . ')">Rezervă</button>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="no-items">Nu s-au găsit camere care să corespundă criteriilor de căutare.</div>';
                    }
                    
                    echo '</div>';
                } catch(PDOException $e) {
                    echo "Eroare: " . $e->getMessage();
                }
                ?>
            </div>
        </div>
<?php elseif ($page === 'profile'): ?>
    <div class="card">
        <div class="card-header">
            <div>Editare profil</div>
        </div>
        <div class="card-body">
            <?php
            // Preluare date client
            try {
                $stmt = $conn->prepare("SELECT * FROM customers WHERE CustomerID = :customer_id");
                $stmt->bindParam(':customer_id', $_SESSION['user_id']);
                $stmt->execute();
                $customer = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$customer) {
                    // Dacă nu există încă înregistrare în customers, preluăm din users
                    $stmt = $conn->prepare("SELECT FirstName, LastName, Email FROM users WHERE UserID = :user_id");
                    $stmt->bindParam(':user_id', $_SESSION['user_id']);
                    $stmt->execute();
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $customer = [
                        'FirstName' => $user['FirstName'] ?? '',
                        'LastName' => $user['LastName'] ?? '',
                        'Email' => $user['Email'] ?? ($_SESSION['email'] ?? ''),
                        'Phone' => '',
                        'Address' => ''
                    ];
                }
            } catch(PDOException $e) {
                $error = "Eroare la încărcarea datelor profilului: " . $e->getMessage();
            }
            
            // Procesare actualizare profil
            if (isset($_POST['update_profile'])) {
                $first_name = trim($_POST['first_name']);
                $last_name = trim($_POST['last_name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $address = trim($_POST['address']);
                
                // Validare
                if (empty($first_name) || empty($last_name) || empty($email)) {
                    $error = "Prenumele, numele și email-ul sunt obligatorii!";
                } else {
                    try {
                        // Verificăm dacă email-ul este deja folosit de alt utilizator
                        $stmt = $conn->prepare("SELECT UserID FROM users WHERE Email = :email AND UserID != :user_id");
                        $stmt->bindParam(':email', $email);
                        $stmt->bindParam(':user_id', $_SESSION['user_id']);
                        $stmt->execute();
                        
                        if ($stmt->rowCount() > 0) {
                            $error = "Acest email este deja folosit de alt utilizator!";
                        } else {
                            // Începe tranzacție pentru a asigura consistența
                            $conn->beginTransaction();
                            
                            // Verificăm dacă există deja înregistrare în customers
                            $stmt = $conn->prepare("SELECT COUNT(*) FROM customers WHERE CustomerID = :customer_id");
                            $stmt->bindParam(':customer_id', $_SESSION['user_id']);
                            $stmt->execute();
                            $exists = $stmt->fetchColumn();
                            
                            if ($exists) {
                                // Actualizare existent
                                $stmt = $conn->prepare("
                                    UPDATE customers 
                                    SET FirstName = :first_name, 
                                        LastName = :last_name, 
                                        Email = :email, 
                                        Phone = :phone, 
                                        Address = :address 
                                    WHERE CustomerID = :customer_id
                                ");
                            } else {
                                // Inserare nouă
                                $stmt = $conn->prepare("
                                    INSERT INTO customers (CustomerID, FirstName, LastName, Email, Phone, Address)
                                    VALUES (:customer_id, :first_name, :last_name, :email, :phone, :address)
                                ");
                            }
                            
                            $stmt->bindParam(':customer_id', $_SESSION['user_id']);
                            $stmt->bindParam(':first_name', $first_name);
                            $stmt->bindParam(':last_name', $last_name);
                            $stmt->bindParam(':email', $email);
                            $stmt->bindParam(':phone', $phone);
                            $stmt->bindParam(':address', $address);
                            $stmt->execute();
                            
                            // Actualizăm și în users
                            $stmt = $conn->prepare("
                                UPDATE users 
                                SET FirstName = :first_name, 
                                    LastName = :last_name, 
                                    Email = :email 
                                WHERE UserID = :user_id
                            ");
                            $stmt->bindParam(':first_name', $first_name);
                            $stmt->bindParam(':last_name', $last_name);
                            $stmt->bindParam(':email', $email);
                            $stmt->bindParam(':user_id', $_SESSION['user_id']);
                            $stmt->execute();
                            
                            // Înregistrăm acțiunea în istoric_utilizare
                            $stmt = $conn->prepare("INSERT INTO istoric_utilizare (UserID, Operatie) VALUES (:user_id, 'Actualizare profil')");
                            $stmt->bindParam(':user_id', $_SESSION['user_id']);
                            $stmt->execute();
                            
                            // Actualizăm sesiunea
                            $_SESSION['email'] = $email;
                            
                            // Confirmăm tranzacția
                            $conn->commit();
                            
                            $success = "Profilul a fost actualizat cu succes!";
                            // Reîncărcăm datele
                            $customer = [
                                'FirstName' => $first_name,
                                'LastName' => $last_name,
                                'Email' => $email,
                                'Phone' => $phone,
                                'Address' => $address
                            ];
                        }
                    } catch(PDOException $e) {
                        $conn->rollBack();
                        $error = "Eroare la actualizarea profilului: " . $e->getMessage();
                    }
                }
            }
            ?>
            
            <!-- Afișăm mesajul de succes sau eroare specific pentru profil -->
            <?php if (!empty($success) && $page === 'profile'): ?>
            <div class="alert alert-success profile-success">
                <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error) && $page === 'profile'): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="first_name">Prenume:</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($customer['FirstName']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Nume:</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($customer['LastName']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($customer['Email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Telefon:</label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($customer['Phone']); ?>">
                </div>
                <div class="form-group">
                    <label for="address">Adresă:</label>
                    <textarea id="address" name="address"><?php echo htmlspecialchars($customer['Address']); ?></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" name="update_profile" class="btn btn-primary">Actualizează profil</button>
                </div>
            </form>
        </div>
    </div>

       <?php elseif ($page === 'services'): ?>
<div class="card">
    <div class="card-header">
        <div>Servicii disponibile</div>
        <div class="sort-controls">
            <a href="?page=services&sort=ServiceName&order=<?php echo $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search_term); ?>&min_price=<?php echo urlencode($min_price); ?>&max_price=<?php echo urlencode($max_price); ?>&price_filter=<?php echo urlencode($price_filter); ?>" class="sort-btn">
                <i class="fas fa-sort"></i> Nume
            </a>
            <a href="?page=services&sort=Price&order=<?php echo $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search_term); ?>&min_price=<?php echo urlencode($min_price); ?>&max_price=<?php echo urlencode($max_price); ?>&price_filter=<?php echo urlencode($price_filter); ?>" class="sort-btn">
                <i class="fas fa-sort"></i> Preț
            </a>
            <a href="?page=services&sort=ServiceDuration&order=<?php echo $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search_term); ?>&min_price=<?php echo urlencode($min_price); ?>&max_price=<?php echo urlencode($max_price); ?>&price_filter=<?php echo urlencode($price_filter); ?>" class="sort-btn">
                <i class="fas fa-sort"></i> Durată
            </a>
        </div>
    </div>

    <!-- Bara de căutare și filtrare pentru servicii -->
    <div class="search-bar">
        <form method="GET" class="search-form">
            <input type="hidden" name="page" value="services">
            <input type="hidden" name="sort" value="<?php echo $sort_by; ?>">
            <input type="hidden" name="order" value="<?php echo $sort_order; ?>">

            <div class="search-group">
                <input type="text" name="search" placeholder="Caută după nume serviciu..." value="<?php echo htmlspecialchars($search_term); ?>">
                <i class="fas fa-search"></i>
            </div>

            <div class="filter-group">
                <select name="price_filter" id="service_price_filter">
                    <option value="" hidden <?php echo $price_filter === '' ? 'selected' : ''; ?>>Filtru preț</option>
                    <option value="min" <?php echo $price_filter === 'min' ? 'selected' : ''; ?>>Mai mare decât</option>
                    <option value="max" <?php echo $price_filter === 'max' ? 'selected' : ''; ?>>Mai mic decât</option>
                    <option value="between" <?php echo $price_filter === 'between' ? 'selected' : ''; ?>>Între</option>
                </select>

                <div class="price-range" id="service_min_price_container" <?php echo ($price_filter === 'min' || $price_filter === 'between') ? '' : 'style="display:none;"'; ?>>
                    <input type="number" name="min_price" placeholder="Preț min" value="<?php echo htmlspecialchars($min_price); ?>">
                </div>

                <div class="price-range" id="service_max_price_container" <?php echo ($price_filter === 'max' || $price_filter === 'between') ? '' : 'style="display:none;"'; ?>>
                    <input type="number" name="max_price" placeholder="Preț max" value="<?php echo htmlspecialchars($max_price); ?>">
                </div>

                <button type="submit" class="btn-search">
                    <i class="fas fa-filter"></i> Filtrează
                </button>

                <a href="?page=services" class="btn-reset">
                    <i class="fas fa-undo"></i> Resetează
                </a>
            </div>
        </form>
    </div>

    <div class="card-body">
        <?php
        try {
            // Construiește query-ul de căutare și filtrare pentru servicii
            $query = "
                SELECT *,
                    CASE 
                        WHEN ServiceDuration IS NULL OR ServiceDuration = 'N/A' THEN 999999
                        WHEN ServiceDuration LIKE '%minute%' THEN 
                            CAST(REGEXP_REPLACE(ServiceDuration, '[^0-9]', '') AS UNSIGNED)
                        WHEN ServiceDuration LIKE '%oră%' OR ServiceDuration LIKE '%ore%' THEN 
                            CAST(REGEXP_REPLACE(ServiceDuration, '[^0-9.]', '') AS DECIMAL(5,2)) * 60
                        ELSE 999999
                    END AS duration_minutes
                FROM Services WHERE 1=1";
            $params = [];

            // Adaugă condiții de căutare
            if (!empty($search_term)) {
                $query .= " AND (
                    ServiceName LIKE :search OR 
                    ServiceDescription LIKE :search
                )";
                $params[':search'] = '%' . $search_term . '%';
            }

            // Adaugă condiții de filtrare a prețului DOAR dacă există filtre selectate
            if ($price_filter === 'min' && !empty($min_price)) {
                $query .= " AND Price >= :min_price";
                $params[':min_price'] = $min_price;
            } elseif ($price_filter === 'max' && !empty($max_price)) {
                $query .= " AND Price <= :max_price";
                $params[':max_price'] = $max_price;
            } elseif ($price_filter === 'between' && !empty($min_price) && !empty($max_price)) {
                $query .= " AND Price BETWEEN :min_price AND :max_price";
                $params[':min_price'] = $min_price;
                $params[':max_price'] = $max_price;
            }

            // Adaugă ordinea sortării
            if ($sort_by === 'ServiceDuration') {
                $query .= " ORDER BY duration_minutes $sort_order, ServiceName ASC";
            } else {
                $query .= " ORDER BY $sort_by $sort_order";
            }

            $stmt = $conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Afișează numărul de rezultate
            echo '<div class="results-count">Rezultate găsite: ' . count($services) . '</div>';

            echo '<div class="items-grid">';

            if (count($services) > 0) {
                foreach ($services as $service) {
                    echo '<div class="item-card">';
                    echo '<div class="item-image"><i class="fas fa-concierge-bell"></i></div>';
                    echo '<div class="item-details">';
                    echo '<div class="item-title">' . htmlspecialchars($service['ServiceName']) . '</div>';
                    echo '<div class="item-price">' . number_format($service['Price'], 2, ',', '.') . ' RON</div>';
                    echo '<div class="item-description">' . htmlspecialchars($service['ServiceDescription']) . '</div>';

                    // Afișăm durata doar dacă nu este N/A sau NULL
                    if (isset($service['ServiceDuration']) && $service['ServiceDuration'] != 'N/A' && !empty($service['ServiceDuration'])) {
                        echo '<div class="item-features">';
                        echo '<span class="feature"><i class="fas fa-clock"></i> Durată: ' . htmlspecialchars($service['ServiceDuration']) . '</span>';
                        echo '</div>';
                    }

                    echo '<div class="item-actions">';
                    echo '<button class="btn btn-reserve" onclick="openServiceModal(' . $service['ServiceID'] . ', \'' . addslashes($service['ServiceName']) . '\', ' . $service['Price'] . ')">Rezervă</button>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="no-items">Nu s-au găsit servicii care să corespundă criteriilor de căutare.</div>';
            }

            echo '</div>';
        } catch(PDOException $e) {
            echo "Eroare: " . htmlspecialchars($e->getMessage());
        }
        ?>
    </div>
</div>


        <?php elseif ($page === 'roomreservations'): ?>
        <div class="card">
            <div class="card-header">
                <div>Rezervările mele</div>
            </div>
            <div class="card-body">
                <?php
                try {
                    // Obținem suma totală pentru rezervările de camere
                    $stmt = $conn->prepare("
                        SELECT SUM(TotalAmount) as total_rooms
                        FROM roomreservations
                        WHERE CustomerID = :customer_id
                    ");
                    $stmt->bindParam(':customer_id', $_SESSION['user_id']);
                    $stmt->execute();
                    $room_total = $stmt->fetch(PDO::FETCH_ASSOC)['total_rooms'] ?? 0;
                    
                    // Obținem suma totală pentru rezervările de servicii
                    $stmt = $conn->prepare("
                        SELECT SUM(TotalAmount) as total_services
                        FROM Servicereservations
                        WHERE CustomerID = :customer_id
                    ");
                    $stmt->bindParam(':customer_id', $_SESSION['user_id']);
                    $stmt->execute();
                    $service_total = $stmt->fetch(PDO::FETCH_ASSOC)['total_services'] ?? 0;
                    
                    // Calculăm totalul general
                    $grand_total = $room_total + $service_total;
                    
                    // Afișăm sumarul cheltuielilor
                    echo '<div class="reservation-summary">';
                    echo '<h3>Sumar cheltuieli</h3>';
                    echo '<div class="summary-table">';
                    echo '<div class="summary-row"><div>Total rezervări camere:</div><div>' . number_format($room_total, 2, '.', ',') . ' RON</div></div>';
                    echo '<div class="summary-row"><div>Total rezervări servicii:</div><div>' . number_format($service_total, 2, '.', ',') . ' RON</div></div>';
                    echo '<div class="summary-row total"><div>TOTAL GENERAL:</div><div>' . number_format($grand_total, 2, '.', ',') . ' RON</div></div>';
                    echo '</div>';
                    echo '</div>';
                } catch(PDOException $e) {
                    echo "Eroare la calculul totalului: " . $e->getMessage();
                }
                ?>

                <h3>Rezervări camere</h3>
                <?php
                try {
                    $stmt = $conn->prepare("
                        SELECT r.*, rm.RoomNumber, rm.PricePerNight  
                        FROM roomreservations r
                        JOIN Rooms rm ON r.RoomID = rm.RoomID
                        WHERE r.CustomerID = :customer_id
                        ORDER BY r.CheckInDate DESC
                    ");
                    $stmt->bindParam(':customer_id', $_SESSION['user_id']);
                    $stmt->execute();
                    $roomreservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($roomreservations) > 0) {
                        echo '<div class="table-responsive">';
                        echo '<table class="table">';
                        echo '<thead>';
                        echo '<tr>';
                        echo '<th>Nr. Rezervare</th>';
                        echo '<th>Camera</th>';
                        echo '<th>Check-in</th>';
                        echo '<th>Check-out</th>';
                        echo '<th>Sumă totală</th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';
                        
                        foreach ($roomreservations as $reservation) {
                            $check_in = date('d.m.Y', strtotime($reservation['CheckInDate']));
                            $check_out = date('d.m.Y', strtotime($reservation['CheckOutDate']));
                            
                            echo '<tr>';
                            echo '<td>' . $reservation['ReservationID'] . '</td>';
                            echo '<td>Camera ' . $reservation['RoomNumber'] . '</td>';
                            echo '<td>' . $check_in . '</td>';
                            echo '<td>' . $check_out . '</td>';
                            echo '<td>' . $reservation['TotalAmount'] . ' RON</td>';
                            echo '</tr>';
                        }
                        
                        echo '</tbody>';
                        echo '</table>';
                        echo '</div>';
                    } else {
                        echo '<div class="no-items">Nu aveți rezervări de camere.</div>';
                    }
                } catch(PDOException $e) {
                    echo "Eroare: " . $e->getMessage();
                }
                ?>

                <h3 style="margin-top: 30px;">Rezervări servicii</h3>
                <?php
                try {
                    $stmt = $conn->prepare("
                        SELECT sr.*, s.ServiceName, s.Price  
                        FROM Servicereservations sr
                        JOIN Services s ON sr.ServiceID = s.ServiceID
                        WHERE sr.CustomerID = :customer_id
                        ORDER BY sr.ReservationDate DESC, sr.OraRezervare DESC
                    ");
                    $stmt->bindParam(':customer_id', $_SESSION['user_id']);
                    $stmt->execute();
                    $service_roomreservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($service_roomreservations) > 0) {
                        echo '<div class="table-responsive">';
                        echo '<table class="table">';
                        echo '<thead>';
                        echo '<tr>';
                        echo '<th>Nr. Rezervare</th>';
                        echo '<th>Serviciu</th>';
                        echo '<th>Data</th>';
                        echo '<th>Ora</th>';
                        echo '<th>Sumă totală</th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';
                        
                        foreach ($service_roomreservations as $sr) {
                            $date = date('d.m.Y', strtotime($sr['ReservationDate']));
                            $time = date('H:i', strtotime($sr['OraRezervare']));
                            
                            echo '<tr>';
                            echo '<td>' . $sr['ServiceReservationID'] . '</td>';
                            echo '<td>' . $sr['ServiceName'] . '</td>';
                            echo '<td>' . $date . '</td>';
                            echo '<td>' . $time . '</td>';
                            echo '<td>' . $sr['TotalAmount'] . ' RON</td>';
                            echo '</tr>';
                        }
                        
                        echo '</tbody>';
                        echo '</table>';
                        echo '</div>';
                    } else {
                        echo '<div class="no-items">Nu aveți rezervări de servicii.</div>';
                    }
                } catch(PDOException $e) {
                    echo "Eroare: " . $e->getMessage();
                }
                ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal pentru rezervare cameră -->
    <div id="reserveRoomModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeRoomModal()">×</span>
            <h3>Rezervare cameră</h3>
            <form method="POST" action="">
                <input type="hidden" id="room_id" name="room_id">
                <p id="room_details"></p>
                <div class="form-group">
                    <label for="check_in">Data check-in:</label>
                    <input type="date" id="check_in" name="check_in" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label for="check_out">Data check-out:</label>
                    <input type="date" id="check_out" name="check_out" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                </div>
                <div class="form-group">
                    <label>Cost total: <span id="total_cost">Se calculează...</span></label>
                </div>
                <div class="form-group">
                    <button type="submit" name="reserve_room" class="btn btn-primary">Confirmă rezervarea</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal pentru rezervare serviciu -->
    <div id="reserveServiceModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeServiceModal()">×</span>
            <h3>Rezervare serviciu</h3>
            <form method="POST" action="">
                <input type="hidden" id="service_id" name="service_id">
                <p id="service_details"></p>
                <div class="form-group">
                    <label for="service_date">Data:</label>
                    <input type="date" id="service_date" name="service_date" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label for="service_time">Ora:</label>
                    <input type="time" id="service_time" name="service_time" min="08:00" max="20:00" required>
                    <small>Ore disponibile: 08:00 - 20:00</small>
                </div>
                <div class="form-group">
                    <button type="submit" name="reserve_service" class="btn btn-primary">Confirmă rezervarea</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    // Funcție pentru calculul costului total
    function calculateTotalCost() {
        const checkIn = new Date(document.getElementById("check_in").value);
        const checkOut = new Date(document.getElementById("check_out").value);
        const pricePerNight = document.getElementById("price_per_night").value;

        if (!isNaN(checkIn.getTime()) && !isNaN(checkOut.getTime()) && pricePerNight) {
            const diffTime = Math.abs(checkOut - checkIn);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            const totalCost = diffDays * pricePerNight;
            document.getElementById("total_cost").textContent = totalCost + " RON";
        } else {
            document.getElementById("total_cost").textContent = "Se calculează...";
        }
    }

    // Funcții pentru deschiderea/închiderea modalelor
    function openReserveModal(roomId, roomName, pricePerNight) {
        document.getElementById("room_id").value = roomId;
        document.getElementById("room_details").innerHTML =
            `<strong>${roomName}</strong><br>Preț per noapte: ${pricePerNight} RON<input type="hidden" id="price_per_night" value="${pricePerNight}">`;
        document.getElementById("reserveRoomModal").style.display = "flex";
    }

    function closeRoomModal() {
        document.getElementById("reserveRoomModal").style.display = "none";
    }

    function openServiceModal(serviceId, serviceName, price) {
        document.getElementById("service_id").value = serviceId;
        document.getElementById("service_details").innerHTML = `<strong>${serviceName}</strong><br>Preț: ${price} RON`;
        document.getElementById("reserveServiceModal").style.display = "flex";
    }

    function closeServiceModal() {
        document.getElementById("reserveServiceModal").style.display = "none";
    }

    // Eveniment pentru calculul costului la schimbarea datelor
    document.getElementById("check_in").addEventListener("change", calculateTotalCost);
    document.getElementById("check_out").addEventListener("change", calculateTotalCost);

    // Funcții pentru filtrele de preț
    document.getElementById("price_filter").addEventListener("change", function() {
        const value = this.value;
        const minPriceContainer = document.getElementById("min_price_container");
        const maxPriceContainer = document.getElementById("max_price_container");

        if (value === "min") {
            minPriceContainer.style.display = "block";
            maxPriceContainer.style.display = "none";
        } else if (value === "max") {
            minPriceContainer.style.display = "none";
            maxPriceContainer.style.display = "block";
        } else if (value === "between") {
            minPriceContainer.style.display = "block";
            maxPriceContainer.style.display = "block";
        } else {
            minPriceContainer.style.display = "none";
            maxPriceContainer.style.display = "none";
        }
    });

    document.getElementById("service_price_filter").addEventListener("change", function() {
        const value = this.value;
        const minPriceContainer = document.getElementById("service_min_price_container");
        const maxPriceContainer = document.getElementById("service_max_price_container");

        if (value === "min") {
            minPriceContainer.style.display = "block";
            maxPriceContainer.style.display = "none";
        } else if (value === "max") {
            minPriceContainer.style.display = "none";
            maxPriceContainer.style.display = "block";
        } else if (value === "between") {
            minPriceContainer.style.display = "block";
            maxPriceContainer.style.display = "block";
        } else {
            minPriceContainer.style.display = "none";
            maxPriceContainer.style.display = "none";
        }
    });

    // Închiderea modalului la click în afara lui
    window.onclick = function(event) {
        const roomModal = document.getElementById("reserveRoomModal");
        const serviceModal = document.getElementById("reserveServiceModal");

        if (event.target == roomModal) {
            roomModal.style.display = "none";
        }

        if (event.target == serviceModal) {
            serviceModal.style.display = "none";
        }
    }
    </script>
</body>
</html>