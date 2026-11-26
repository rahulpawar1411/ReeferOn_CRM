<?php
/**
 * Live search suggestions API (JSON)
 *
 * Called by Dashboard while the user types in Global Search.
 * Needs at least 2 characters. Returns short list of clickable matches.
 *
 * Example: /CRM/api/search_suggest.php?q=reefer
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

$q = trim((string) ($_GET['q'] ?? ''));
// Too short → empty list (avoids heavy queries on 1 letter)
if (mb_strlen($q) < 2) {
    json_response(['ok' => true, 'q' => $q, 'items' => []]);
}

try {
    $raw = global_search($q, 6);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => 'Search failed', 'items' => []], 500);
}

// Flatten DB results into one suggestion list for the dropdown
$items = [];

foreach ($raw['prospects'] as $r) {
    $items[] = [
        'type' => 'Prospect',
        'label' => $r['prospect_id'] . ' — ' . ($r['company_name'] ?: '—'),
        'sub' => trim(($r['contact_person'] ?? '') . ($r['mobile'] ? ' · ' . $r['mobile'] : '') . ' · ' . ($r['pipeline_stage'] ?? '')),
        'url' => base_url('modules/prospects/view.php?id=' . (int) $r['id']),
    ];
}
foreach ($raw['onboarding'] as $r) {
    $items[] = [
        'type' => 'Onboarding',
        'label' => $r['onboarding_id'] . ' — ' . ($r['customer_name'] ?: '—'),
        'sub' => trim(($r['gstin'] ?? '') . ($r['mobile'] ? ' · ' . $r['mobile'] : '') . ' · ' . ($r['overall_status'] ?? '')),
        'url' => base_url('modules/onboarding/view.php?id=' . (int) $r['id']),
    ];
}
foreach ($raw['customers'] as $r) {
    $items[] = [
        'type' => 'Customer',
        'label' => $r['customer_code'] . ' — ' . ($r['customer_name'] ?: '—'),
        'sub' => trim(($r['contact_person'] ?? '') . ($r['mobile'] ? ' · ' . $r['mobile'] : '') . ' · ' . ($r['status'] ?? '')),
        'url' => base_url('modules/customers/view.php?id=' . (int) $r['id']),
    ];
}

// Cap combined suggestions for dropdown
$items = array_slice($items, 0, 10);

json_response([
    'ok' => true,
    'q' => $q,
    'items' => $items,
    'more_url' => base_url('index.php?q=' . rawurlencode($q)),
]);
