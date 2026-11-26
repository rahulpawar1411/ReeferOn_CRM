<?php
/**
 * One-time install
 *
 * Creates the MySQL database and tables from sql/schema.sql.
 * Open once after WAMP is green: http://localhost/CRM/setup_install.php
 * Safe to delete this file after a successful install.
 */

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$schemaFile = __DIR__ . '/sql/schema.sql';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $sql = file_get_contents($schemaFile);
    if ($sql === false) {
        throw new RuntimeException('Cannot read schema.sql');
    }

    // Remove /* */ block comments
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    $lines = explode("\n", $sql);
    $clean = [];
    foreach ($lines as $line) {
        $trim = ltrim($line);
        if (str_starts_with($trim, '--')) {
            continue;
        }
        $clean[] = $line;
    }
    $sql = implode("\n", $clean);

    $parts = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($parts as $statement) {
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }

    echo "OK – Database reeferon_crm installed.\n";
    echo "Open: http://localhost/CRM/\n";
    echo "Create prospect: http://localhost/CRM/modules/prospects/create.php\n";
    echo "You can delete setup_install.php after install.\n";
} catch (Throwable $e) {
    echo "FAILED\n";
    echo $e->getMessage() . "\n\n";
    echo "1) Start WAMP (icon should be green)\n";
    echo "2) Refresh this page\n";
    echo "OR open phpMyAdmin and import sql/schema.sql\n";
}
