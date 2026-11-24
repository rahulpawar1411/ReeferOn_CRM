<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/_data.php';

$type = $_GET['type'] ?? '';
if (!in_array($type, report_types(), true)) {
    flash('error', 'Unknown report.');
    redirect(base_url('modules/reports/index.php'));
}

try {
    [$headers, $rows, $title] = report_dataset($type);
    $safe = preg_replace('/[^a-z0-9_\-]+/i', '_', strtolower($type));
    $filename = 'reeferon_' . $safe . '_' . date('Ymd_His') . '.csv';
    export_csv($filename, $headers, $rows);
} catch (Throwable $e) {
    flash('error', 'Export failed: ' . $e->getMessage());
    redirect(base_url('modules/reports/index.php'));
}
