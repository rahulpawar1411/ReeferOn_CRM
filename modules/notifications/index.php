<?php
/**
 * Notifications list
 *
 * Simple inbox for CRM events (unread / all). Prev / Next pages.
 * Follow-up and approval counts stay on the Dashboard — not repeated here.
 */
$pageTitle = 'Notifications';
$currentPage = 'notifications';
require_once __DIR__ . '/../../includes/functions.php';
require_perm('notifications.view');

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

try {
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
                <tr class="<?= ((int) $r['is_read'] === 0) ? 'is-unread' : '' ?>">
                    <td><?= e($r['created_at']) ?></td>
                    <td>
                        <strong><?= e($r['title']) ?></strong>
                        <?php if (!empty($r['message'])): ?>
                            <div class="muted"><?= e($r['message']) ?></div>
                        <?php endif; ?>
                    </td>
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

<div class="card">
    <h2>Notifications</h2>
    <p class="step-note">
        Important CRM events only (stage change, submit, reject, customer code, etc.).
        Today’s follow-ups / pending approvals are on the <strong>Dashboard</strong> — not repeated here.
    </p>

    <div class="toolbar">
        <a class="btn <?= $tab === 'unread' ? '' : 'btn-secondary' ?>" href="?tab=unread">Unread</a>
        <a class="btn <?= $tab === 'all' ? '' : 'btn-secondary' ?>" href="?tab=all">All</a>
        <?php if ($tab === 'unread'): ?>
            <a class="btn btn-secondary" href="?mark_all=1">Mark all read</a>
        <?php endif; ?>
    </div>

    <?php if (!$rows): ?>
        <div class="empty">No <?= e($tab) ?> notifications.</div>
    <?php else: ?>
        <table class="data">
            <thead>
            <tr>
                <th>When</th>
                <th>Notification</th>
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
