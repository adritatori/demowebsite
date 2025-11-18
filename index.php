<?php
session_start();

// Database connection
$dsn = 'mysql:host=localhost;dbname=studentdb;charset=utf8mb4';
$username = 'student';
$password = 'Password123!';

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Database connection error");
}

// Create users table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Login handling
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request";
    } else {
        // Input validation and sanitization
        $user = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $pass = $_POST['password'] ?? '';
        
        if (empty($user) || empty($pass)) {
            $error = "All fields required";
        } else {
            // Prepared statement (SQL injection prevention)
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$user]);
            $userData = $stmt->fetch();
            
            if ($userData && password_verify($pass, $userData['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $userData['id'];
                $_SESSION['username'] = $userData['username'];
                $_SESSION['role'] = $userData['role'];
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Invalid credentials";
            }
        }
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Login - Student Portal</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <div class="container">
        <h1>Student Portal - Secure Login</h1>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <p>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>! 
            <a href="dashboard.php">Go to Dashboard</a></p>
        <?php else: ?>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <label>Username:</label>
                <input type="text" name="username" required maxlength="50" pattern="[a-zA-Z0-9_]{3,50}">
                
                <label>Password:</label>
                <input type="password" name="password" required minlength="8">
                
                <button type="submit" name="login">Login</button>
            </form>
            
            <p><a href="register.php">Create Account</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
