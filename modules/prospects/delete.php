<?php
/**
 * Make prospect Inactive
 *
 * Soft status change only (keeps history). Permission: prospects.delete
 * Popup: type "inactive" to confirm.
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
    if (!delete_confirm_ok($_POST['confirm_text'] ?? '', 'inactive')) {
        $errors[] = 'Please type inactive in the box to confirm.';
    } else {
        try {
            soft_delete_prospect($id, trim($_POST['reason'] ?? ''));
            flash('success', $p['prospect_id'] . ' set to Inactive.', 'delete', [
                'entity' => 'prospect',
                'id' => $id,
                'to' => 'Active',
                'redirect' => base_url('modules/prospects/view.php?id=' . $id),
            ]);
            redirect(base_url('modules/prospects/index.php'));
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'Inactive Prospect';
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
    'title' => 'Make prospect Inactive?',
    'message' => 'Record stays in history/logs. Status becomes Inactive. Type inactive below to confirm.',
    'item_label' => $p['prospect_id'] . ' — ' . $p['company_name'],
    'cancel_url' => $cancel,
    'confirm_word' => 'inactive',
    'confirm_btn' => 'Inactive',
    'hidden' => ['id' => (int) $p['id']],
]);
?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
