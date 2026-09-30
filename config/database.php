<?php
/**
 * Database Connection Manager
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $conn = null;

    public static function getConnection() {
        if (self::$conn === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                
                self::$conn = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Return descriptive error or generic message depending on env
                if (ENVIRONMENT === 'development') {
                    die("Database connection failed: " . $e->getMessage());
                } else {
                    die("A database error occurred. Please try again later.");
                }
            }
        }
        return self::$conn;
    }
}
