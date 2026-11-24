<?php
$pageTitle = 'Approvals';
$currentPage = 'approvals';
require_once __DIR__ . '/../../includes/functions.php';

$tab = $_GET['tab'] ?? 'pending';
if (!in_array($tab, ['pending', 'done', 'all'], true)) {
    $tab = 'pending';
}

$sql = "SELECT a.*, o.onboarding_id, o.customer_name, o.prospect_id, o.overall_status, o.gstin
        FROM onboarding_approvals a
        JOIN onboarding o ON o.id = a.onboarding_pk
        WHERE a.department = 'Admin'";
$params = [];

if ($tab === 'pending') {
    $sql .= " AND a.status = 'Pending' AND o.overall_status IN ('Submitted','In Approval')";
} elseif ($tab === 'done') {
    $sql .= " AND a.status IN ('Approved','Rejected')";
}
$sql .= ' ORDER BY a.id DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Admin Approvals</h2>
    <p class="step-note"><strong>Step 4 – Admin approval:</strong> After all onboarding details + documents are filled and submitted, Admin Approve / Reject here. Approve creates customer code <strong>REO-CUS-xxxx</strong> and moves the record to Customer Master.</p>

    <div class="tabs">
        <a class="<?= $tab === 'pending' ? 'active' : '' ?>" href="?tab=pending">Pending</a>
        <a class="<?= $tab === 'done' ? 'active' : '' ?>" href="?tab=done">Done</a>
        <a class="<?= $tab === 'all' ? 'active' : '' ?>" href="?tab=all">All</a>
    </div>

    <?php if (!$rows): ?>
        <div class="empty">No <?= e(strtolower($tab)) ?> Admin approvals.</div>
    <?php else: ?>
        <table class="data">
            <thead>
            <tr>
                <th>Onboarding</th>
                <th>Prospect</th>
                <th>Customer</th>
                <th>GSTIN</th>
                <th>Onboarding Status</th>
                <th>Approval</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td>
                        <a href="<?= e(base_url('modules/onboarding/view.php?id=' . $r['onboarding_pk'])) ?>">
                            <?= e($r['onboarding_id']) ?>
                        </a>
                    </td>
                    <td><?= e($r['prospect_id']) ?></td>
                    <td><?= e($r['customer_name']) ?></td>
                    <td><?= e($r['gstin'] ?? '-') ?></td>
                    <td><span class="badge"><?= e($r['overall_status']) ?></span></td>
                    <td><span class="badge"><?= e($r['status']) ?></span></td>
                    <td>
                        <?php if ($r['status'] === 'Pending' && in_array($r['overall_status'], ['Submitted', 'In Approval'], true)): ?>
                            <a href="<?= e(base_url('modules/approvals/decide.php?id=' . $r['id'])) ?>">Decide</a>
                        <?php else: ?>
                            <a href="<?= e(base_url('modules/onboarding/view.php?id=' . $r['onboarding_pk'])) ?>">View</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
