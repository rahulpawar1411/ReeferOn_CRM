<?php
/**
 * Notifications list
 *
 * Unread / all alerts, plus live counts for due follow-ups and approvals.
 */
$pageTitle = 'Notifications';
$currentPage = 'notifications';
require_once __DIR__ . '/../../includes/functions.php';

$tab = $_GET['tab'] ?? 'unread';
if (!in_array($tab, ['unread', 'all'], true)) {
    $tab = 'unread';
}

// Mark every notification as read
if (isset($_GET['mark_all']) && $_GET['mark_all'] === '1') {
    try {
        db()->exec('UPDATE notifications SET is_read = 1 WHERE is_read = 0');
        flash('success', 'All notifications marked as read.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect(base_url('modules/notifications/index.php?tab=all'));
}

$liveAlerts = [];
try {
    $pdo = db();
    $fuToday = (int) $pdo->query("SELECT COUNT(*) FROM followups WHERE status = 'Open' AND followup_date = CURDATE()")->fetchColumn();
    $fuOver = (int) $pdo->query("SELECT COUNT(*) FROM followups WHERE status = 'Open' AND followup_date < CURDATE()")->fetchColumn();
    $pending = (int) $pdo->query(
        "SELECT COUNT(*) FROM onboarding_approvals a
         JOIN onboarding o ON o.id = a.onboarding_pk
         WHERE a.department = 'Admin' AND a.status = 'Pending'
           AND o.overall_status IN ('Submitted','In Approval')"
    )->fetchColumn();

    if ($fuToday > 0) {
        $liveAlerts[] = [
            'title' => 'Follow-ups Due Today',
            'message' => $fuToday . ' open follow-up(s) due today.',
            'link' => base_url('modules/followups/index.php'),
        ];
    }
    if ($fuOver > 0) {
        $liveAlerts[] = [
            'title' => 'Overdue Follow-ups',
            'message' => $fuOver . ' open follow-up(s) overdue.',
            'link' => base_url('modules/followups/index.php'),
        ];
    }
    if ($pending > 0) {
        $liveAlerts[] = [
            'title' => 'Admin Approval Pending',
            'message' => $pending . ' onboarding(s) waiting for Admin.',
            'link' => base_url('modules/approvals/index.php'),
        ];
    }

    $where = ($tab === 'unread') ? ' WHERE is_read = 0' : '';
    [$rows, $pg] = paginate_sql(
        'SELECT COUNT(*) FROM notifications' . $where,
        'SELECT * FROM notifications' . $where . ' ORDER BY id DESC',
        [],
        page_size_default()
    );
} catch (Throwable $e) {
    $rows = [];
    $pg = make_pagination(0, page_size_default());
    if (!wants_ajax()) {
        flash('error', 'Notifications table missing? Import sql/schema_phase6.sql');
    }
}

$keepQuery = ['tab' => $tab];
$renderNotificationRows = static function (array $rows): string {
    ob_start();
    foreach ($rows as $r): ?>
                <tr>
                    <td><?= e($r['created_at']) ?></td>
                    <td><?= e($r['event_type']) ?></td>
                    <td><?= e($r['title']) ?></td>
                    <td><?= e($r['message']) ?></td>
                    <td><?= ((int) $r['is_read'] === 1) ? 'Yes' : 'No' ?></td>
                    <td>
                        <?php if (!empty($r['link_url'])): ?>
                            <a href="<?= e(base_url('modules/notifications/open.php?id=' . $r['id'])) ?>">Open</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
    <?php endforeach;
    return (string) ob_get_clean();
};

if (wants_ajax()) {
    json_response(lazy_ajax_payload($pg, $renderNotificationRows($rows), $keepQuery));
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="toolbar">
    <a class="btn btn-secondary" href="?tab=unread">Unread</a>
    <a class="btn btn-secondary" href="?tab=all">All</a>
    <a class="btn" href="?mark_all=1">Mark all read</a>
</div>

<div class="card">
    <h2>Live Alerts</h2>
    <p class="step-note">Computed from current data (follow-ups + pending Admin approvals).</p>
    <?php if (!$liveAlerts): ?>
        <div class="empty">No live alerts right now.</div>
    <?php else: ?>
        <table class="data">
            <tr><th>Alert</th><th>Detail</th><th></th></tr>
            <?php foreach ($liveAlerts as $a): ?>
                <tr>
                    <td><strong><?= e($a['title']) ?></strong></td>
                    <td><?= e($a['message']) ?></td>
                    <td><a href="<?= e($a['link']) ?>">Open</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Notification History</h2>
    <div class="tabs">
        <a class="<?= $tab === 'unread' ? 'active' : '' ?>" href="?tab=unread">Unread</a>
        <a class="<?= $tab === 'all' ? 'active' : '' ?>" href="?tab=all">All</a>
    </div>

    <?php if (!$rows): ?>
        <div class="empty">No <?= e($tab) ?> notifications yet. Events like Commercial Approved, submit, reject, and customer code create them.</div>
    <?php else: ?>
        <table class="data" data-lazy="1">
            <thead>
            <tr>
                <th>When</th>
                <th>Type</th>
                <th>Title</th>
                <th>Message</th>
                <th>Read</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?= $renderNotificationRows($rows) ?>
            </tbody>
        </table>
        <?php render_pagination($pg ?? make_pagination(0), $keepQuery); ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
