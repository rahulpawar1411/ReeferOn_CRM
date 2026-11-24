<?php
$pageTitle = 'Reports';
$currentPage = 'reports';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/header.php';

$reports = [
    'prospects' => 'Prospect Report',
    'pipeline' => 'Sales Pipeline Report',
    'salesperson' => 'Salesperson Performance Report',
    'followups' => 'Follow-up Report',
    'commercial_approval' => 'Commercial Approval Report',
    'onboarding' => 'Onboarding Report',
    'pending_approvals' => 'Pending Approval Report',
    'customers' => 'Customer Master Report',
    'customers_status' => 'Active / Inactive Customer Report',
];
?>

<div class="card">
    <h2>Reports + Excel Export</h2>
    <p class="step-note"><strong>Phase 6:</strong> Open a report on screen, or download CSV (opens in Excel). All figures come from live MySQL data.</p>

    <table class="data">
        <thead>
        <tr>
            <th>Report</th>
            <th>View</th>
            <th>Export</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($reports as $key => $label): ?>
            <tr>
                <td><?= e($label) ?></td>
                <td><a href="<?= e(base_url('modules/reports/view.php?type=' . urlencode($key))) ?>">Open</a></td>
                <td><a href="<?= e(base_url('modules/reports/export.php?type=' . urlencode($key))) ?>">Excel (CSV)</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
