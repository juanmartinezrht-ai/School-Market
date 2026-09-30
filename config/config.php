<?php
/**
 * School Market Configuration File
 * Compatible with Local development and Hostinger
 */

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    // Session lifetime 2 hours
    ini_set('session.cookie_lifetime', 7200);
    ini_set('session.gc_maxlifetime', 7200);
    session_start();
}

// Error reporting settings (Hide errors in production, show in development)
define('ENVIRONMENT', 'development'); // Change to 'production' on Hostinger
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'school_market');
define('DB_USER', 'root');
define('DB_PASS', ''); // For Hostinger, update these settings in production

// Application Settings
define('SITE_NAME', 'School Market');
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024); // 5 MB

// Helper: Response JSON formatter
function jsonResponse($status, $message, $data = null) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Helper: Sanitize input data
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Helper: Ensure user is logged in
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        jsonResponse('error', 'Authentication required. Please log in.');
    }
}

// Helper: Ensure admin is logged in
function requireAdmin() {
    if (!isset($_SESSION['admin_id'])) {
        // For API calls
        if (strpos($_SERVER['REQUEST_URI'], '/controllers/') !== false) {
            jsonResponse('error', 'Administrator privileges required.');
        } else {
            // Redirect regular page access
            header('Location: /school_market/index.html'); // Fallback login redirect
            exit;
        }
    }
}
