<?php
$pageTitle = 'Approvals';
$currentPage = 'approvals';
require_once __DIR__ . '/../../includes/functions.php';

$tab = $_GET['tab'] ?? 'pending';
if (!in_array($tab, ['pending', 'done', 'all'], true)) {
    $tab = 'pending';
}

$from = " FROM onboarding_approvals a
        JOIN onboarding o ON o.id = a.onboarding_pk
        WHERE a.department = 'Admin'";
$params = [];

if ($tab === 'pending') {
    $from .= " AND a.status = 'Pending' AND o.overall_status IN ('Submitted','In Approval')";
} elseif ($tab === 'done') {
    $from .= " AND a.status IN ('Approved','Rejected')";
}

[$rows, $pg] = paginate_sql(
    'SELECT COUNT(*)' . $from,
    'SELECT a.*, o.onboarding_id, o.customer_name, o.prospect_id, o.overall_status, o.gstin' . $from . ' ORDER BY a.id DESC',
    $params,
    page_size_default()
);

$keepQuery = ['tab' => $tab];
$renderApprovalRows = static function (array $rows): string {
    ob_start();
    foreach ($rows as $r): ?>
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
    <?php endforeach;
    return (string) ob_get_clean();
};

if (wants_ajax()) {
    json_response(lazy_ajax_payload($pg, $renderApprovalRows($rows), $keepQuery));
}

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
        <table class="data" data-lazy="1">
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
            <?= $renderApprovalRows($rows) ?>
            </tbody>
        </table>
        <?php render_pagination($pg, $keepQuery); ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
