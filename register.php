<?php
session_start();

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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request";
    } else {
        // Input validation and sanitization
        $user = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $pass = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if (empty($user) || empty($email) || empty($pass)) {
            $error = "All fields required";
        } elseif (!$email) {
            $error = "Invalid email format";
        } elseif (strlen($pass) < 8) {
            $error = "Password must be at least 8 characters";
        } elseif ($pass !== $confirm) {
            $error = "Passwords do not match";
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $user)) {
            $error = "Username: 3-50 alphanumeric characters only";
        } else {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$user, $email]);
            
            if ($stmt->fetch()) {
                $error = "Username or email already exists";
            } else {
                // Hash password and insert
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'user')");
                
                if ($stmt->execute([$user, $email, $hash])) {
                    $success = "Account created! <a href='index.php'>Login here</a>";
                } else {
                    $error = "Registration failed";
                }
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
    <title>Register - Student Portal</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <div class="container">
        <h1>Create Account</h1>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php else: ?>
            <form method="POST" action="" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <label>Username:</label>
                <input type="text" name="username" required maxlength="50" pattern="[a-zA-Z0-9_]{3,50}" 
                       title="3-50 alphanumeric characters only">
                
                <label>Email:</label>
                <input type="email" name="email" required maxlength="100">
                
                <label>Password:</label>
                <input type="password" name="password" required minlength="8" id="password">
                
                <label>Confirm Password:</label>
                <input type="password" name="confirm_password" required minlength="8" id="confirm_password">
                
                <button type="submit">Register</button>
            </form>
            
            <p><a href="index.php">Back to Login</a></p>
        <?php endif; ?>
    </div>

    <script>
        // Client-side validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const pass = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            
            if (pass !== confirm) {
                e.preventDefault();
                alert('Passwords do not match');
            }
        });
    </script>
</body>
</html>
