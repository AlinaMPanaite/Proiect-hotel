<?php
session_start();
$error = "";
$success = "";

// Conectare la baza de date
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hotel";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    $error = "Conexiunea la baza de date a eșuat: " . $e->getMessage();
}

// Procesare login
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    if (empty($email) || empty($password)) {
        $error = "Vă rugăm completați toate câmpurile!";
    } else {
        try {
            // Verificăm utilizatorul după Email și UserRole
            $stmt = $conn->prepare("SELECT UserID, Email, password, UserRole FROM users WHERE Email = :email AND UserRole = :role");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':role', $role);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['UserID'];
                    $_SESSION['email'] = $user['Email'];
                    $_SESSION['role'] = $user['UserRole'];
                    
                    // Înregistrăm login-ul în tabela istoric_utilizare
                    $stmt = $conn->prepare("INSERT INTO istoric_utilizare (UserID, Operatie) VALUES (:user_id, 'Login')");
                    $stmt->bindParam(':user_id', $user['UserID']);
                    $stmt->execute();
                    
                    // Redirecționare în funcție de rol
                    if ($role === 'client') {
                        header("Location: client.php");
                    } elseif ($role === 'admin') {
                        header("Location: admin.php");
                    } else {
                        header("Location: dashboard.php");
                    }
                    exit();
                } else {
                    $error = "Parolă incorectă!";
                }
            } else {
                $error = "Email sau rol greșit!";
            }
        } catch(PDOException $e) {
            $error = "Eroare: " . $e->getMessage();
        }
    }
}

// Procesare înregistrare
if (isset($_POST['register'])) {
    $email = trim($_POST['reg_email']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $password = $_POST['reg_password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['reg_role'];
    $admin_password = isset($_POST['admin_password']) ? $_POST['admin_password'] : '';
    
    // Validare
    if (empty($email) || empty($first_name) || empty($last_name) || empty($password) || empty($confirm_password)) {
        $error = "Vă rugăm completați toate câmpurile obligatorii!";
    } elseif ($password !== $confirm_password) {
        $error = "Parolele nu coincid!";
    } elseif ($role === 'admin' && $admin_password !== 'admin123') {
        $error = "Parola specială pentru admin este incorectă!";
    } else {
        try {
            // Verificăm dacă email-ul există deja în tabela users
            $stmt = $conn->prepare("SELECT UserID FROM users WHERE Email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = "Acest email există deja!";
            } else {
                // Generăm UserID
                $stmt = $conn->query("SELECT MAX(UserID) as max_id FROM users");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $next_user_id = ($result['max_id'] ?? 0) + 1;
                
                // Criptăm parola
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Inserăm utilizator nou în tabela users
                $stmt = $conn->prepare("
                    INSERT INTO users (UserID, Email, FirstName, LastName, password, UserRole) 
                    VALUES (:id, :email, :first_name, :last_name, :password, :role)
                ");
                $stmt->bindParam(':id', $next_user_id);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':first_name', $first_name);
                $stmt->bindParam(':last_name', $last_name);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':role', $role);
                $stmt->execute();
                
                // Dacă rolul este client, inserăm în tabela customers
                if ($role === 'client') {
                    // Generăm CustomerID
                    $stmt = $conn->query("SELECT MAX(CustomerID) as max_id FROM customers");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $next_customer_id = ($result['max_id'] ?? 0) + 1;
                    
                    // Inserăm în tabela customers
                    $stmt = $conn->prepare("
                        INSERT INTO customers (CustomerID, FirstName, LastName, Email) 
                        VALUES (:customer_id, :first_name, :last_name, :email)
                    ");
                    $stmt->bindParam(':customer_id', $next_customer_id);
                    $stmt->bindParam(':first_name', $first_name);
                    $stmt->bindParam(':last_name', $last_name);
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();
                }
                
                // Înregistrăm acțiunea de creare cont în istoric_utilizare
                $stmt = $conn->prepare("INSERT INTO istoric_utilizare (UserID, Operatie) VALUES (:user_id, 'Înregistrare cont')");
                $stmt->bindParam(':user_id', $next_user_id);
                $stmt->execute();
                
                $success = "Cont creat cu succes! Vă puteți conecta acum.";
            }
        } catch(PDOException $e) {
            $error = "Eroare: " . $e->getMessage();
        }
    }
}

// Setare tab activ inițial
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'login';
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel - Sistem de Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="main.css">
</head>
<body>
    <div class="container">
        <div class="hotel-logo">
            <h1>Vega Hotel</h1>
            <p>Bine ați venit pe pagina noastră!</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="tab-buttons">
                <button class="tab-button <?php echo $activeTab === 'login' ? 'active' : ''; ?>" onclick="changeTab('login')">
                    <i class="fas fa-sign-in-alt"></i> Autentificare
                </button>
                <button class="tab-button <?php echo $activeTab === 'register' ? 'active' : ''; ?>" onclick="changeTab('register')">
                    <i class="fas fa-user-plus"></i> Înregistrare
                </button>
            </div>
            
            <div class="tab-content">
                <!-- Tab Login -->
                <div id="login-tab" class="tab-pane <?php echo $activeTab === 'login' ? 'active' : ''; ?>">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?tab=login" method="post">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Parolă</label>
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="role">Rol</label>
                            <i class="fas fa-user-tag"></i>
                            <select id="role" name="role" required>
                                <option value="client">Client</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                        <button type="submit" name="login">
                            <i class="fas fa-sign-in-alt"></i> Conectare
                        </button>
                    </form>
                </div>
                
                <!-- Tab Înregistrare -->
                <div id="register-tab" class="tab-pane <?php echo $activeTab === 'register' ? 'active' : ''; ?>">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?tab=register" method="post">
                        <div class="form-group">
                            <label for="first_name">Prenume</label>
                            <i class="fas fa-user"></i>
                            <input type="text" id="first_name" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Nume</label>
                            <i class="fas fa-user"></i>
                            <input type="text" id="last_name" name="last_name" required>
                        </div>
                        <div class="form-group">
                            <label for="reg_email">Email</label>
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="reg_email" name="reg_email" required>
                        </div>
                        <div class="form-group">
                            <label for="reg_password">Parolă</label>
                            <i class="fas fa-lock"></i>
                            <input type="password" id="reg_password" name="reg_password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirmare parolă</label>
                            <i class="fas fa-check-circle"></i>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="form-group">
                            <label for="reg_role">Rol</label>
                            <i class="fas fa-user-tag"></i>
                            <select id="reg_role" name="reg_role" required onchange="toggleAdminPassword()">
                                <option value="client">Client</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                        <div class="form-group admin-password" id="admin_password_group">
                            <label for="admin_password">Parolă specială administrator</label>
                            <i class="fas fa-key"></i>
                            <input type="password" id="admin_password" name="admin_password">
                        </div>
                        <button type="submit" name="register">
                            <i class="fas fa-user-plus"></i> Creare cont
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function changeTab(tabId) {
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.pushState({}, '', url);
            
            document.querySelectorAll('.tab-button').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('active');
            });
            
            document.querySelector(`.tab-button[onclick="changeTab('${tabId}')"]`).classList.add('active');
            document.getElementById(`${tabId}-tab`).classList.add('active');
        }
        
        function toggleAdminPassword() {
            var roleSelect = document.getElementById('reg_role');
            var adminPasswordGroup = document.getElementById('admin_password_group');
            
            if (roleSelect.value === 'admin') {
                adminPasswordGroup.style.display = 'block';
                document.getElementById('admin_password').required = true;
            } else {
                adminPasswordGroup.style.display = 'none';
                document.getElementById('admin_password').required = false;
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('reg_role')) {
                toggleAdminPassword();
            }
        });
    </script>
</body>
</html>