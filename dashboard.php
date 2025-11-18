<?php
session_start();

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

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

// Logout handling
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Fetch user data
$stmt = $pdo->prepare("SELECT username, email, role, created_at FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Admin: view all users (Role-based access control)
$allUsers = [];
if ($user['role'] === 'admin') {
    $stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
    $allUsers = $stmt->fetchAll();
}

// Generate CSRF token for any forms
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Student Portal</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Student Dashboard</h1>
            <a href="?logout" class="logout-btn">Logout</a>
        </div>
        
        <div class="user-info">
            <h2>Welcome, <?= htmlspecialchars($user['username']) ?>!</h2>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
            <p><strong>Role:</strong> <?= htmlspecialchars($user['role']) ?></p>
            <p><strong>Member Since:</strong> <?= htmlspecialchars($user['created_at']) ?></p>
        </div>

        <?php if ($user['role'] === 'admin'): ?>
            <div class="admin-section">
                <h2>Admin Panel - All Users</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allUsers as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['id']) ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['role']) ?></td>
                            <td><?= htmlspecialchars($u['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="content">
                <h3>Your Content</h3>
                <p>This is a secured area accessible only to logged-in users.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
