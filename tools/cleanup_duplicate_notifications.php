<?php
/**
 * Remove duplicate notification rows (same event + title + message + link).
 * Keeps the newest id in each duplicate group.
 *
 * Run: php tools/cleanup_duplicate_notifications.php
 */
require_once __DIR__ . '/../includes/functions.php';

$pdo = db();
$rows = $pdo->query(
    'SELECT id, event_type, title, message, link_url, related_module, related_id, created_at
     FROM notifications
     ORDER BY id DESC'
)->fetchAll(PDO::FETCH_ASSOC);

$seen = [];
$deleteIds = [];

foreach ($rows as $r) {
    $key = strtolower(trim(
        ($r['event_type'] ?? '') . '|' .
        ($r['title'] ?? '') . '|' .
        ($r['message'] ?? '') . '|' .
        ($r['link_url'] ?? '') . '|' .
        ($r['related_module'] ?? '') . '|' .
        ($r['related_id'] ?? '')
    ));
    if (isset($seen[$key])) {
        $deleteIds[] = (int) $r['id'];
    } else {
        $seen[$key] = (int) $r['id']; // keep newest
    }
}

if (!$deleteIds) {
    echo "No duplicate notifications found. total=" . count($rows) . "\n";
    exit(0);
}

$in = implode(',', array_map('intval', $deleteIds));
$n = $pdo->exec("DELETE FROM notifications WHERE id IN ({$in})");
echo "deleted_duplicates={$n}\n";
echo "kept=" . (count($rows) - $n) . "\n";
echo "OK\n";
