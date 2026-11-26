<?php
/**
 * ReeferON CRM – Database connection
 *
 * Change DB_HOST / DB_NAME / DB_USER / DB_PASS if your WAMP MySQL
 * settings are different from the defaults below.
 *
 * Call db() anywhere to get one shared PDO connection.
 */

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'crm');
define('DB_USER', 'root');
define('DB_PASS', ''); // WAMP default password is empty
define('DB_CHARSET', 'utf8mb4');

// Legacy constants (kept for older code). Real login uses Phase 7 auth.
define('TEMP_USER_ID', 1);
define('TEMP_USER_NAME', 'System User');

date_default_timezone_set('Asia/Kolkata');

/**
 * Return one MySQL PDO connection (created once, then reused).
 */
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
