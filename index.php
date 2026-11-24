<?php
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
require_once __DIR__ . '/includes/header.php';

$counts = [
    'total' => 0,
    'new' => 0,
    'active_pipeline' => 0,
    'commercial_approved' => 0,
];

try {
    $pdo = db();
    $counts['total'] = (int) $pdo->query("SELECT COUNT(*) FROM prospects WHERE status = 'Active'")->fetchColumn();
    $counts['new'] = (int) $pdo->query("SELECT COUNT(*) FROM prospects WHERE pipeline_stage = 'New Prospect' AND status = 'Active'")->fetchColumn();
    $counts['active_pipeline'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM prospects
         WHERE status = 'Active'
           AND pipeline_stage NOT IN ('Lost','On Hold','Cancelled','Customer Activated')"
    )->fetchColumn();
    $counts['commercial_approved'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM prospects WHERE pipeline_stage = 'Commercial Approved' AND status = 'Active'"
    )->fetchColumn();
} catch (Throwable $e) {
    // Schema may not be imported yet
}
?>

<div class="card">
    <h2>Quick Summary (from live data)</h2>
    <p>UI polish later. These numbers come from the prospects table.</p>
    <table class="data">
        <tr>
            <th>Total Prospects</th>
            <th>New Leads</th>
            <th>Active Pipeline</th>
            <th>Commercial Approved</th>
        </tr>
        <tr>
            <td><?= (int) $counts['total'] ?></td>
            <td><?= (int) $counts['new'] ?></td>
            <td><?= (int) $counts['active_pipeline'] ?></td>
            <td><?= (int) $counts['commercial_approved'] ?></td>
        </tr>
    </table>
</div>

<div class="card">
    <h2>Actions</h2>
    <a class="btn" href="<?= e(base_url('modules/prospects/create.php')) ?>">Create New Prospect</a>
    <a class="btn btn-secondary" href="<?= e(base_url('modules/prospects/index.php')) ?>">View All Prospects</a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
