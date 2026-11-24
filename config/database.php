<?php
/**
 * ReeferON CRM - Database Configuration
 * Update credentials if your WAMP MySQL password is different.
 */

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'reeferon_crm');
define('DB_USER', 'root');
define('DB_PASS', ''); // WAMP default is empty
define('DB_CHARSET', 'utf8mb4');

/**
 * Temporary user until Authentication phase (Phase 7)
 */
define('TEMP_USER_ID', 1);
define('TEMP_USER_NAME', 'System User');

date_default_timezone_set('Asia/Kolkata');

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        die('Database connection failed. Import sql/schema.sql in phpMyAdmin, then refresh. Error: ' . htmlspecialchars($e->getMessage()));
    }

    return $pdo;
}
