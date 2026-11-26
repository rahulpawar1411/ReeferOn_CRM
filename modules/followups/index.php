<?php
/**
 * Follow-ups list
 *
 * Tabs for today / upcoming / overdue / completed call & meeting tasks.
 */
$pageTitle = 'Follow-ups';
$currentPage = 'followups';
require_once __DIR__ . '/../../includes/functions.php';
require_perm('followups.view');

$tab = $_GET['tab'] ?? 'today';
$allowedTabs = ['today', 'upcoming', 'overdue', 'completed', 'all'];
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'today';
}

$today = date('Y-m-d');

// Filter rows by selected tab
switch ($tab) {
    case 'today':
        $where = " WHERE status = 'Open' AND followup_date = ?";
        $order = ' ORDER BY id DESC';
        $params = [$today];
        break;
    case 'upcoming':
        $where = " WHERE status = 'Open' AND followup_date > ?";
        $order = ' ORDER BY followup_date ASC, id DESC';
        $params = [$today];
        break;
    case 'overdue':
        $where = " WHERE status = 'Open' AND followup_date < ?";
        $order = ' ORDER BY followup_date ASC, id DESC';
        $params = [$today];
        break;
    case 'completed':
        $where = " WHERE status = 'Completed'";
        $order = ' ORDER BY updated_at DESC, id DESC';
        $params = [];
        break;
    default:
        $where = '';
        $order = ' ORDER BY followup_date DESC, id DESC';
        $params = [];
}

[$rows, $pg] = paginate_sql(
    'SELECT COUNT(*) FROM followups' . $where,
    'SELECT * FROM followups' . $where . $order,
    $params,
    page_size_default()
);

$keepQuery = ['tab' => $tab];
$renderFollowupRows = static function (array $rows): string {
    ob_start();
    foreach ($rows as $r): ?>
                <tr>
                    <td><?= e($r['followup_date']) ?></td>
                    <td>
                        <a href="<?= e(base_url('modules/prospects/view.php?id=' . $r['prospect_pk'])) ?>">
                            <?= e($r['prospect_id']) ?>
                        </a>
                    </td>
                    <td><?= e($r['company_name']) ?></td>
                    <td><?= e($r['contact_person']) ?></td>
                    <td><?= e($r['interaction_type']) ?></td>
                    <td><?= e($r['next_followup_date'] ?? '-') ?></td>
                    <td><span class="badge"><?= e($r['status']) ?></span></td>
                    <td><?= e($r['created_by_name']) ?></td>
                    <td>
                        <?php if ($r['status'] === 'Open'): ?>
                            <a href="<?= e(base_url('modules/followups/complete.php?id=' . $r['id'])) ?>">Complete</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
    <?php endforeach;
    return (string) ob_get_clean();
};

if (wants_ajax()) {
    json_response(lazy_ajax_payload($pg, $renderFollowupRows($rows), $keepQuery));
}

// Counts for tabs
$countStmt = db()->prepare("SELECT
    SUM(CASE WHEN status='Open' AND followup_date = CURDATE() THEN 1 ELSE 0 END) AS today_c,
    SUM(CASE WHEN status='Open' AND followup_date > CURDATE() THEN 1 ELSE 0 END) AS upcoming_c,
    SUM(CASE WHEN status='Open' AND followup_date < CURDATE() THEN 1 ELSE 0 END) AS overdue_c,
    SUM(CASE WHEN status='Completed' THEN 1 ELSE 0 END) AS completed_c,
    COUNT(*) AS all_c
  FROM followups");
$countStmt->execute();
$counts = $countStmt->fetch() ?: [
    'today_c' => 0, 'upcoming_c' => 0, 'overdue_c' => 0, 'completed_c' => 0, 'all_c' => 0,
];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="toolbar">
    <a class="btn" href="<?= e(base_url('modules/followups/create.php')) ?>">Add Follow-up</a>
</div>

<div class="tabs">
    <a class="<?= $tab === 'today' ? 'active' : '' ?>" href="?tab=today">Today (<?= (int) $counts['today_c'] ?>)</a>
    <a class="<?= $tab === 'upcoming' ? 'active' : '' ?>" href="?tab=upcoming">Upcoming (<?= (int) $counts['upcoming_c'] ?>)</a>
    <a class="<?= $tab === 'overdue' ? 'active' : '' ?>" href="?tab=overdue">Overdue (<?= (int) $counts['overdue_c'] ?>)</a>
    <a class="<?= $tab === 'completed' ? 'active' : '' ?>" href="?tab=completed">Completed (<?= (int) $counts['completed_c'] ?>)</a>
    <a class="<?= $tab === 'all' ? 'active' : '' ?>" href="?tab=all">All (<?= (int) $counts['all_c'] ?>)</a>
</div>

<div class="card">
    <h2>Follow-up List</h2>
    <p class="step-note"><strong>Follow-ups:</strong> Check Today, Upcoming, and Overdue items. Mark done with <em>Complete</em>. This is your sales chase list before onboarding.</p>
    <?php if (!$rows): ?>
        <div class="empty">No follow-ups in this tab.</div>
    <?php else: ?>
        <table class="data" data-lazy="1">
            <thead>
            <tr>
                <th>Date</th>
                <th>Prospect</th>
                <th>Company</th>
                <th>Contact</th>
                <th>Type</th>
                <th>Next</th>
                <th>Status</th>
                <th>By</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?= $renderFollowupRows($rows) ?>
            </tbody>
        </table>
        <?php render_pagination($pg, $keepQuery); ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
