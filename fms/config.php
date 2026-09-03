<?php
/**
 * Faculty Management System (FMS)
 * Application Configuration & Database Connection
 */

// Start session if not already started (ensuring writable storage on serverless hosts like Vercel)
if (session_status() === PHP_SESSION_NONE) {
    $currentSavePath = session_save_path();
    if (empty($currentSavePath) || !is_writable($currentSavePath)) {
        @session_save_path(sys_get_temp_dir());
    }
    session_start();
}

// Environment & Error Reporting
// In production or XAMPP development
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide raw errors in output for clean UX

// Database Configuration (Default XAMPP credentials or Vercel Environment Variables)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'faculty_management_system');
define('DB_PORT', getenv('DB_PORT') ?: 3306);

// Base URL Configuration
// Always use root-relative path (e.g. /fms/) to prevent browser mixed-content blocking across reverse proxies & HTTPS
if (!defined('BASE_URL')) {
    $script_name = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $base_path = rtrim($script_name, '/');

    // Calculate clean root FMS path
    if (strpos($base_path, '/admin') !== false) {
        $base_url = substr($base_path, 0, strpos($base_path, '/admin'));
    } elseif (strpos($base_path, '/faculty') !== false) {
        $base_url = substr($base_path, 0, strpos($base_path, '/faculty'));
    } else {
        $base_url = $base_path;
    }

    $clean_base = '/' . trim($base_url, '/');
    if ($clean_base === '/' || $clean_base === '/.' || $clean_base === '/api') {
        $clean_base = '/fms/';
    } elseif ($clean_base !== '/') {
        $clean_base .= '/';
    }
    define('BASE_URL', $clean_base);
}
define('APP_NAME', 'Faculty Management System');
define('APP_CODE', 'FMS');
define('ACADEMIC_YEAR', '2025-2026');

// Database Connection using PDO (Prepared Statements & UTF-8)
try {
    if (!extension_loaded('pdo_mysql')) {
        throw new PDOException('MySQL driver not available, switching to SQLite');
    }
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
        $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
    }
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // If MySQL is not running or credentials not set, fallback to SQLite
    $sqliteFile = __DIR__ . '/database/fms.sqlite';

    // On serverless hosts (like Vercel) where the app bundle is read-only,
    // replicate the SQLite database to /tmp so write operations (attendance, leaves, roster) work seamlessly!
    $isVercelEnv = isset($_ENV['VERCEL']) || getenv('VERCEL') || (isset($_SERVER['VERCEL']) && $_SERVER['VERCEL']);
    if (($isVercelEnv || !is_writable($sqliteFile) || !is_writable(dirname($sqliteFile))) && file_exists($sqliteFile)) {
        $tmpSqlite = sys_get_temp_dir() . '/fms.sqlite';
        if (!file_exists($tmpSqlite) || filesize($tmpSqlite) === 0) {
            @copy($sqliteFile, $tmpSqlite);
        }
        if (file_exists($tmpSqlite)) {
            $sqliteFile = $tmpSqlite;
        }
    }

    if (file_exists($sqliteFile)) {
        try {
            $pdo = new PDO("sqlite:" . $sqliteFile, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false
            ]);
            $pdo->exec("PRAGMA foreign_keys = ON;");
        } catch (PDOException $sqle) {
            $db_connection_error = "MySQL Error: " . $e->getMessage() . " | SQLite Error: " . $sqle->getMessage();
        }
    } else {
        error_log("Database Connection Error: " . $e->getMessage());
        $db_connection_error = $e->getMessage();
    }
}

/**
 * Returns active PDO instance
 */
function getDB() {
    global $pdo, $db_connection_error;
    if (isset($pdo) && $pdo !== null) {
        return $pdo;
    }
    die("<div style='font-family: sans-serif; padding: 20px; background: #FFF5F5; color: #C53030; border: 1px solid #FEB2B2; border-radius: 8px; max-width: 600px; margin: 40px auto;'>
        <h3 style='margin-top: 0;'>Database Connection Error</h3>
        <p>Could not connect to MySQL database <strong>" . htmlspecialchars(DB_NAME) . "</strong>.</p>
        <p><strong>Troubleshooting in XAMPP:</strong></p>
        <ol style='padding-left: 20px; line-height: 1.6;'>
            <li>Ensure MySQL service is started in XAMPP Control Panel.</li>
            <li>Import <code>database/fms.sql</code> into phpMyAdmin.</li>
            <li>Verify credentials in <code>config.php</code> (default: user 'root', empty password).</li>
        </ol>
        <small style='color: #742A2A;'>Error: " . htmlspecialchars($db_connection_error ?? 'Unknown error') . "</small>
    </div>");
}
