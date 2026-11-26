<?php
/**
 * View report
 *
 * Shows one report table on screen (live MySQL data).
 */
$pageTitle = 'Report';
$currentPage = 'reports';
require_once __DIR__ . '/../../includes/functions.php';
require_perm('reports.view');
require_once __DIR__ . '/_data.php';

$type = $_GET['type'] ?? '';
if (!in_array($type, report_types(), true)) {
    flash('error', 'Unknown report.');
    redirect(base_url('modules/reports/index.php'));
}

// Load headers + rows for the selected report type
try {
    [$headers, $rows, $title] = report_dataset($type);
} catch (Throwable $e) {
    flash('error', $e->getMessage());
    redirect(base_url('modules/reports/index.php'));
}

$pageTitle = $title;
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="toolbar">
    <?php if (can('reports.export')): ?>
        <a class="btn" href="<?= e(base_url('modules/reports/export.php?type=' . urlencode($type))) ?>">Excel (CSV)</a>
    <?php endif; ?>
    <a class="btn btn-secondary" href="<?= e(base_url('modules/reports/index.php')) ?>">All Reports</a>
</div>

<div class="card">
    <h2><?= e($title) ?></h2>
    <p class="step-note"><?= count($rows) ?> row(s). Data is live from the database.</p>

    <?php if (!$rows): ?>
        <div class="empty">No rows for this report.</div>
    <?php else: ?>
        <table class="data">
            <thead>
            <tr>
                <?php foreach ($headers as $h): ?>
                    <th><?= e($h) ?></th>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($row as $cell): ?>
                        <td><?= e((string) ($cell ?? '')) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
