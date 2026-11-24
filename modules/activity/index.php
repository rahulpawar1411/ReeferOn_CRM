<?php
// Legacy URL → Logs menu
require_once __DIR__ . '/../../includes/functions.php';
$qs = $_SERVER['QUERY_STRING'] ?? '';
redirect(base_url('modules/logs/index.php' . ($qs !== '' ? '?' . $qs : '')));
