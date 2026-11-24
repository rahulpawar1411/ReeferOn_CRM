<?php
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
require_once __DIR__ . '/includes/header.php';

$counts = [
    'total' => 0,
    'new' => 0,
    'active_pipeline' => 0,
    'commercial_approved' => 0,
    'fu_today' => 0,
    'fu_overdue' => 0,
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
    $counts['fu_today'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM followups WHERE status = 'Open' AND followup_date = CURDATE()"
    )->fetchColumn();
    $counts['fu_overdue'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM followups WHERE status = 'Open' AND followup_date < CURDATE()"
    )->fetchColumn();
} catch (Throwable $e) {
    // Schema may not be imported yet
}
?>

<div class="card">
    <h2>Quick Summary (from live data)</h2>
    <p>UI polish later. These numbers come from saved records.</p>
    <table class="data">
        <tr>
            <th>Total Prospects</th>
            <th>New Leads</th>
            <th>Active Pipeline</th>
            <th>Commercial Approved</th>
            <th>Follow-ups Today</th>
            <th>Overdue Follow-ups</th>
        </tr>
        <tr>
            <td><?= (int) $counts['total'] ?></td>
            <td><?= (int) $counts['new'] ?></td>
            <td><?= (int) $counts['active_pipeline'] ?></td>
            <td><?= (int) $counts['commercial_approved'] ?></td>
            <td><?= (int) $counts['fu_today'] ?></td>
            <td><?= (int) $counts['fu_overdue'] ?></td>
        </tr>
    </table>
</div>

<div class="card">
    <h2>Actions</h2>
    <a class="btn" href="<?= e(base_url('modules/prospects/create.php')) ?>">Create New Prospect</a>
    <a class="btn btn-secondary" href="<?= e(base_url('modules/prospects/index.php')) ?>">View All Prospects</a>
    <a class="btn btn-secondary" href="<?= e(base_url('modules/followups/index.php')) ?>">Follow-ups</a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
