<?php
/**
 * Complete follow-up
 *
 * Marks an open follow-up as Completed, then returns to the list.
 */
require_once __DIR__ . '/../../includes/functions.php';
require_perm('followups.create');

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM followups WHERE id = ?');
$stmt->execute([$id]);
$f = $stmt->fetch();

if (!$f) {
    flash('error', 'Follow-up not found.');
    redirect(base_url('modules/followups/index.php'));
}

if ($f['status'] !== 'Open') {
    flash('error', 'Follow-up is already ' . $f['status'] . '.');
    redirect(base_url('modules/followups/index.php'));
}

$user = current_user();

// Mark this follow-up completed
try {
    $upd = db()->prepare("UPDATE followups SET status = 'Completed' WHERE id = ?");
    $upd->execute([$id]);
    write_audit('followups', $f['prospect_id'], 'COMPLETE', 'Open', 'Completed');
    flash('success', 'Follow-up marked completed.');
} catch (Throwable $e) {
    flash('error', 'Could not complete: ' . $e->getMessage());
}

$tab = $_GET['tab'] ?? 'today';
redirect(base_url('modules/followups/index.php?tab=' . urlencode($tab)));
