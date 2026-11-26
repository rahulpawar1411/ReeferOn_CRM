<?php
/**
 * Soft-delete prospect
 *
 * Sets status = Inactive (keeps history). Permission: prospects.delete
 * Opens a centered popup — user must type "delete" to confirm.
 */
require_once __DIR__ . '/../../includes/functions.php';
require_perm('prospects.delete');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM prospects WHERE id = ?');
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) {
    flash('error', 'Prospect not found.');
    redirect(base_url('modules/prospects/index.php'));
}

if ($p['status'] === 'Inactive') {
    flash('error', 'Prospect is already Inactive.');
    redirect(base_url('modules/prospects/view.php?id=' . $id));
}

$cancel = base_url('modules/prospects/view.php?id=' . $id);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!delete_confirm_ok($_POST['confirm_text'] ?? '')) {
        $errors[] = 'Please type delete in the box to confirm.';
    } else {
        try {
            soft_delete_prospect($id, trim($_POST['reason'] ?? ''));
            flash('success', $p['prospect_id'] . ' marked Inactive (soft delete).', 'delete');
            redirect(base_url('modules/prospects/index.php'));
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'Delete Prospect';
$currentPage = 'prospects';
require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($errors): ?>
    <div class="alert alert-error" style="max-width:480px;margin:16px auto">
        <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
render_delete_confirm_modal([
    'title' => 'Delete prospect?',
    'message' => 'Record will become Inactive (not permanently erased). Type delete below to confirm.',
    'item_label' => $p['prospect_id'] . ' — ' . $p['company_name'],
    'cancel_url' => $cancel,
    'hidden' => ['id' => (int) $p['id']],
]);
?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
