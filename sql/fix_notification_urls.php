<?php
require_once __DIR__ . '/../includes/functions.php';

$n = db()->exec("UPDATE notifications SET link_url = CONCAT('/CRM', link_url) WHERE link_url LIKE '/modules/%'");
echo "updated_rows={$n}\n";
$rows = db()->query("SELECT related_id, link_url FROM notifications WHERE related_id LIKE 'DEMO-NOTIF%'")->fetchAll();
foreach ($rows as $r) {
    echo $r['related_id'] . ' => ' . $r['link_url'] . PHP_EOL;
}
echo 'base=' . base_url('modules/logs/index.php') . PHP_EOL;
