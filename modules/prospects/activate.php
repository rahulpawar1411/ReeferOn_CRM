<?php
/**
 * Make prospect Active again
 *
 * Permission: prospects.delete (same Admin control as Inactive)
 * Popup: type "active" to confirm.
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

if ($p['status'] === 'Active') {
    flash('error', 'Prospect is already Active.');
    redirect(base_url('modules/prospects/view.php?id=' . $id));
}

$cancel = base_url('modules/prospects/view.php?id=' . $id);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!delete_confirm_ok($_POST['confirm_text'] ?? '', 'active')) {
        $errors[] = 'Please type active in the box to confirm.';
    } else {
        try {
            soft_activate_prospect($id, trim($_POST['reason'] ?? ''));
            flash('success', $p['prospect_id'] . ' set to Active.', 'activate', [
                'entity' => 'prospect',
                'id' => $id,
                'to' => 'Inactive',
                'redirect' => base_url('modules/prospects/view.php?id=' . $id),
            ]);
            redirect(base_url('modules/prospects/view.php?id=' . $id));
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'Activate Prospect';
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
    'title' => 'Make prospect Active?',
    'message' => 'Status will become Active again. Type active below to confirm.',
    'item_label' => $p['prospect_id'] . ' — ' . $p['company_name'],
    'cancel_url' => $cancel,
    'confirm_word' => 'active',
    'confirm_btn' => 'Active',
    'confirm_btn_class' => 'btn btn-success',
    'title_class' => 'is-activate',
    'hidden' => ['id' => (int) $p['id']],
]);
?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
