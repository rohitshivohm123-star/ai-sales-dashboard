<?php
/**
 * AI Sales Calling Assistant Dashboard
 * Core Configuration
 */

// Error reporting — set to 0 in production
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// -------------------------------------------------------
// Database Configuration
// -------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'ai_sales_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// -------------------------------------------------------
// Application Settings
// -------------------------------------------------------
define('APP_NAME', 'AI Sales Calling Assistant');
define('APP_VERSION', '1.0.0');
// Dynamic BASE_URL for redirects and API calls
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
define('BASE_URL', $protocol . '://' . $host . '/ai-sales-dashboard');

// Asset path for CSS/JS/Images (relative from document root)
define('ASSET_PATH', '/ai-sales-dashboard');

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_CSV_ROWS', 5000);

// -------------------------------------------------------
// Call Queue Settings
// -------------------------------------------------------
define('QUEUE_MAX_RETRIES', 2);
define('QUEUE_CALL_DELAY', 5); // seconds between calls

// -------------------------------------------------------
// Timezone
// -------------------------------------------------------
date_default_timezone_set('Asia/Kolkata');

// -------------------------------------------------------
// Session
// -------------------------------------------------------
define('SESSION_LIFETIME', 3600 * 8); // 8 hours
define('SESSION_NAME', 'AISALES_SESS');

// -------------------------------------------------------
// Start Session
// -------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => false, // Set true on HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// -------------------------------------------------------
// Authentication Check
// -------------------------------------------------------
// Skip authentication for Twilio webhooks and public endpoints
if (!defined('SKIP_SESSION_AUTH')) {
    // List of files that don't require authentication
    $public_files = [
        'twiml.php',
        'call_callback.php',
        'record_callback.php',
        'transcribe_callback.php',
        'webhook.php'
    ];
    
    $current_file = basename($_SERVER['PHP_SELF']);
    $is_public = in_array($current_file, $public_files);
    
    // Check if user is authenticated
    if (!$is_public && !isset($_SESSION['user_id'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        exit(json_encode(['error' => 'Unauthorized', 'code' => 401]));
    }
}
?>
