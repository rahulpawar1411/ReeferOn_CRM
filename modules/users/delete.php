<?php
/**
 * Make user Inactive (cannot login)
 *
 * Soft status only. Permission: users.manage
 * Popup: type "inactive" to confirm.
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
    if (!delete_confirm_ok($_POST['confirm_text'] ?? '', 'inactive')) {
        $errors[] = 'Please type inactive in the box to confirm.';
    } else {
        try {
            soft_delete_user($id, trim($_POST['reason'] ?? ''));
            flash('success', $u['user_id'] . ' set to Inactive.', 'delete', [
                'entity' => 'user',
                'id' => $id,
                'to' => 'Active',
                'redirect' => base_url('modules/users/index.php'),
            ]);
            redirect(base_url('modules/users/index.php'));
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'Inactive User';
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
    'title' => 'Make user Inactive?',
    'message' => 'Login will stop. You can set Active again later from Edit. Type inactive below to confirm.',
    'item_label' => $u['employee_name'] . ' (' . $u['user_id'] . ')',
    'cancel_url' => $cancel,
    'confirm_word' => 'inactive',
    'confirm_btn' => 'Inactive',
    'hidden' => ['id' => (int) $u['id']],
]);
?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
