<?php
/**
 * Activity (legacy redirect)
 *
 * Old activity URL — forwards to the Logs page with the same query string.
 */
require_once __DIR__ . '/../../includes/functions.php';
$qs = $_SERVER['QUERY_STRING'] ?? '';
redirect(base_url('modules/logs/index.php' . ($qs !== '' ? '?' . $qs : '')));
