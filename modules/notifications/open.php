<?php
/**
 * Open notification
 *
 * Marks one notification as read, then redirects to its linked page.
 */
require_once __DIR__ . '/../../includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM notifications WHERE id = ?');
$stmt->execute([$id]);
$n = $stmt->fetch();

if (!$n) {
    flash('error', 'Notification not found.');
    redirect(base_url('modules/notifications/index.php'));
}

// Mark as read, then go to the linked screen
db()->prepare('UPDATE notifications SET is_read = 1 WHERE id = ?')->execute([$id]);

$link = !empty($n['link_url']) ? app_url($n['link_url']) : base_url('modules/notifications/index.php');
redirect($link);
