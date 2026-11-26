<?php
/**
 * Health check (JSON)
 *
 * Checks PHP, folders, DB connection, and required tables.
 * URL: http://localhost/CRM/health.php
 */
require_once __DIR__ . '/config/database.php';

$checks = [];
$okCount = 0;
$failCount = 0;
$warnCount = 0;

$add = static function (string $group, string $name, string $status, string $detail = '') use (&$checks, &$okCount, &$failCount, &$warnCount): void {
    $checks[] = compact('group', 'name', 'status', 'detail');
    if ($status === 'OK') {
        $okCount++;
    } elseif ($status === 'WARN') {
        $warnCount++;
    } else {
        $failCount++;
    }
};

// --- PHP / runtime ---
$add('Runtime', 'PHP version', version_compare(PHP_VERSION, '8.0.0', '>=') ? 'OK' : 'FAIL', PHP_VERSION);
$add('Runtime', 'PDO MySQL', extension_loaded('pdo_mysql') ? 'OK' : 'FAIL', extension_loaded('pdo_mysql') ? 'Loaded' : 'Missing');
$add('Runtime', 'GD (images)', extension_loaded('gd') ? 'OK' : 'WARN', extension_loaded('gd') ? 'Loaded' : 'Optional');
$add('Runtime', 'File uploads', ini_get('file_uploads') ? 'OK' : 'FAIL', 'upload_max_filesize=' . ini_get('upload_max_filesize') . ', post_max_size=' . ini_get('post_max_size'));
$add('Runtime', 'Timezone', 'OK', date_default_timezone_get() . ' / ' . date('Y-m-d H:i:s'));
$add('Runtime', 'Temp user (auth later)', 'WARN', 'TEMP_USER_ID=' . TEMP_USER_ID . ' (' . TEMP_USER_NAME . ')');

// --- Paths ---
$uploadDir = __DIR__ . '/uploads/documents';
$logoPath = __DIR__ . '/assets/logo.png';
$add('Files', 'uploads/documents writable', (is_dir($uploadDir) || @mkdir($uploadDir, 0755, true)) && is_writable($uploadDir) ? 'OK' : 'FAIL', $uploadDir);
$add('Files', 'Logo file', is_file($logoPath) ? 'OK' : 'WARN', $logoPath);
$add('Files', 'schema.sql', is_file(__DIR__ . '/sql/schema.sql') ? 'OK' : 'WARN', 'sql/schema.sql');

// --- Database ---
$pdo = null;
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $add('Database', 'Connection', 'OK', DB_HOST . ' / ' . DB_NAME);
} catch (Throwable $e) {
    $add('Database', 'Connection', 'FAIL', $e->getMessage());
}

$requiredTables = [
    'users',
    'id_sequences',
    'prospects',
    'prospect_stage_history',
    'followups',
    'onboarding',
    'onboarding_documents',
    'onboarding_approvals',
    'customers',
    'audit_logs',
    'notifications',
];

if ($pdo) {
    try {
        $ver = $pdo->query('SELECT VERSION()')->fetchColumn();
        $add('Database', 'MySQL version', 'OK', (string) $ver);
    } catch (Throwable $e) {
        $add('Database', 'MySQL version', 'WARN', $e->getMessage());
    }

    foreach ($requiredTables as $table) {
        try {
            $exists = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table))->fetchColumn();
            if (!$exists) {
                $add('Tables', $table, 'FAIL', 'Missing — import sql/schema.sql');
                continue;
            }
            $cnt = (int) $pdo->query('SELECT COUNT(*) FROM `' . str_replace('`', '``', $table) . '`')->fetchColumn();
            $add('Tables', $table, 'OK', $cnt . ' row(s)');
        } catch (Throwable $e) {
            $add('Tables', $table, 'FAIL', $e->getMessage());
        }
    }

    try {
        $seqs = $pdo->query('SELECT seq_name, prefix, next_number, pad_length FROM id_sequences ORDER BY seq_name')->fetchAll();
        $needed = ['prospect' => 'REO-LEAD-', 'onboarding' => 'REO-ONB-', 'customer' => 'REO-CUS-'];
        $byName = [];
        foreach ($seqs as $s) {
            $byName[$s['seq_name']] = $s;
        }
        foreach ($needed as $name => $prefix) {
            if (empty($byName[$name])) {
                $add('Sequences', $name, 'FAIL', 'Missing sequence');
            } else {
                $s = $byName[$name];
                $next = $s['prefix'] . str_pad((string) (int) $s['next_number'], (int) $s['pad_length'], '0', STR_PAD_LEFT);
                $status = ($s['prefix'] === $prefix) ? 'OK' : 'WARN';
                $add('Sequences', $name, $status, 'next = ' . $next);
            }
        }
    } catch (Throwable $e) {
        $add('Sequences', 'id_sequences', 'FAIL', $e->getMessage());
    }

    try {
        $u = $pdo->prepare('SELECT id, employee_name, status FROM users WHERE id = ?');
        $u->execute([TEMP_USER_ID]);
        $user = $u->fetch();
        if (!$user) {
            $add('Auth prep', 'Temp user row', 'FAIL', 'users.id=' . TEMP_USER_ID . ' missing');
        } else {
            $add('Auth prep', 'Temp user row', $user['status'] === 'Active' ? 'OK' : 'WARN', $user['employee_name'] . ' / ' . $user['status']);
        }
    } catch (Throwable $e) {
        $add('Auth prep', 'Temp user row', 'FAIL', $e->getMessage());
    }
}

$overall = $failCount > 0 ? 'FAIL' : ($warnCount > 0 ? 'DEGRADED' : 'OK');
http_response_code($failCount > 0 ? 503 : 200);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode([
    'service' => 'ReeferON CRM',
    'status' => $overall,
    'checked_at' => date('c'),
    'summary' => [
        'ok' => $okCount,
        'warn' => $warnCount,
        'fail' => $failCount,
    ],
    'checks' => $checks,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
