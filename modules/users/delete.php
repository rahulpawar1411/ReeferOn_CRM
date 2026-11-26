<?php
/**
 * Soft-delete (deactivate) user
 *
 * Sets status = Inactive so they cannot login. Permission: users.manage
 * Opens a centered popup — user must type "delete" to confirm.
 */
require_once __DIR__ . '/../../includes/functions.php';
require_perm('users.manage');
ensure_auth_schema();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$u = $stmt->fetch();

if (!$u) {
    flash('error', 'User not found.');
    redirect(base_url('modules/users/index.php'));
}

$cancel = base_url('modules/users/edit.php?id=' . $id);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!delete_confirm_ok($_POST['confirm_text'] ?? '')) {
        $errors[] = 'Please type delete in the box to confirm.';
    } else {
        try {
            soft_delete_user($id, trim($_POST['reason'] ?? ''));
            flash('success', $u['user_id'] . ' deactivated (soft delete).', 'delete');
            redirect(base_url('modules/users/index.php'));
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'Deactivate User';
$currentPage = 'users';
require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($errors): ?>
    <div class="alert alert-error" style="max-width:480px;margin:16px auto">
        <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
render_delete_confirm_modal([
    'title' => 'Deactivate user?',
    'message' => 'Login will stop working. You can activate again later from Edit. Type delete below to confirm.',
    'item_label' => $u['employee_name'] . ' (' . $u['user_id'] . ')',
    'cancel_url' => $cancel,
    'hidden' => ['id' => (int) $u['id']],
]);
?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
