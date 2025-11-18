<?php
/**
 * Database Connection with PDO
 * SECURITY FEATURE #2: SQL Injection Protection
 * Using PDO with prepared statements
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'studentdb');
define('DB_USER', 'student');
define('DB_PASS', 'Password123!');
define('DB_CHARSET', 'utf8mb4');

// Create DSN (Data Source Name)
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

// PDO options for security
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,     // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,           // Fetch associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                       // Use real prepared statements
    PDO::ATTR_PERSISTENT         => false,                       // Don't use persistent connections
];

try {
    // Create PDO instance
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Log error (in production, don't display sensitive info)
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed. Please contact administrator.");
}
?>
