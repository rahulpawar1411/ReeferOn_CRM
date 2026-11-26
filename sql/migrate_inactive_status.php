<?php
/**
 * DB migration: Inactive is a status only (no row delete).
 *
 * Ensures status ENUMs, renames old SOFT_DELETE audit rows to INACTIVE,
 * and prints current Active / Inactive counts.
 *
 * Run: php sql/migrate_inactive_status.php
 */
require_once __DIR__ . '/../includes/functions.php';

$pdo = db();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Migrating Inactive status support…\n";

// 1) Ensure status columns allow Inactive
$alters = [
    "ALTER TABLE users MODIFY status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active'",
    "ALTER TABLE prospects MODIFY status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active'",
    "ALTER TABLE customers MODIFY status ENUM('Active','Inactive','On Hold') NOT NULL DEFAULT 'Active'",
    "ALTER TABLE roles MODIFY status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active'",
];

foreach ($alters as $sql) {
    try {
        $pdo->exec($sql);
        echo "OK: {$sql}\n";
    } catch (Throwable $e) {
        echo "SKIP/ERR: " . $e->getMessage() . "\n";
    }
}

// 2) Audit log wording: SOFT_DELETE → INACTIVE
try {
    $n = $pdo->exec("UPDATE audit_logs SET action = 'INACTIVE' WHERE action = 'SOFT_DELETE'");
    echo "OK: audit_logs SOFT_DELETE → INACTIVE ({$n} rows)\n";
} catch (Throwable $e) {
    echo "SKIP audit: " . $e->getMessage() . "\n";
}

// 3) Helpful indexes for filtering Active lists
$indexes = [
    'prospects' => 'CREATE INDEX idx_prospects_status ON prospects (status)',
    'customers' => 'CREATE INDEX idx_customers_status ON customers (status)',
    'users' => 'CREATE INDEX idx_users_status ON users (status)',
];
foreach ($indexes as $table => $sql) {
    try {
        $name = "idx_{$table}_status";
        $found = false;
        foreach ($pdo->query("SHOW INDEX FROM `{$table}`")->fetchAll() as $idx) {
            if (($idx['Key_name'] ?? '') === $name) {
                $found = true;
                break;
            }
        }
        if ($found) {
            echo "OK: index {$name} already exists\n";
            continue;
        }
        $pdo->exec($sql);
        echo "OK: {$sql}\n";
    } catch (Throwable $e) {
        echo "SKIP index {$table}: " . $e->getMessage() . "\n";
    }
}

// 4) Summary counts
foreach (['prospects', 'customers', 'users'] as $table) {
    echo "\n== {$table} ==\n";
    $rows = $pdo->query("SELECT status, COUNT(*) AS c FROM `{$table}` GROUP BY status ORDER BY status")->fetchAll();
    if (!$rows) {
        echo "(empty)\n";
        continue;
    }
    foreach ($rows as $r) {
        echo $r['status'] . ' = ' . $r['c'] . "\n";
    }
}

echo "\nDONE – Inactive is status-only (records stay in DB).\n";
