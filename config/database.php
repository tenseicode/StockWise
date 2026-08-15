<?php
/** PDO database connection (XAMPP/MariaDB). */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'env.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host   = env('DB_HOST', 'localhost');
    $name   = env('DB_NAME', 'stockwise_db');
    $user   = env('DB_USER', 'root');
    $pass   = env('DB_PASS', '');
    $charset = 'utf8mb4';

    $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        // Log and show a friendly message (development only).
        error_log('[DB] ' . $e->getMessage());
        http_response_code(500);
        die('Database connection failed. Please check your .env settings and that MariaDB is running in XAMPP.');
    }

    return $pdo;
}
