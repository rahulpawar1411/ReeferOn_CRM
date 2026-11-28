<?php
/**
 * Dropdown master edits via form + are disabled.
 * Kept so old bookmarks return a clear JSON error.
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

header('Content-Type: application/json; charset=utf-8');
http_response_code(410);
echo json_encode([
    'ok' => false,
    'error' => 'Dropdown master edit from forms is disabled. Use the fixed list only.',
]);
