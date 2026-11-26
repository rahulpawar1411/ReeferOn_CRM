<?php
/**
 * Make user Active again (can login)
 *
 * Permission: users.manage
 * Popup: type "active" to confirm.
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
    if (!delete_confirm_ok($_POST['confirm_text'] ?? '', 'active')) {
        $errors[] = 'Please type active in the box to confirm.';
    } else {
        try {
            soft_activate_user($id, trim($_POST['reason'] ?? ''));
            flash('success', $u['user_id'] . ' set to Active.', 'activate');
            redirect(base_url('modules/users/index.php'));
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'Activate User';
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
    'title' => 'Make user Active?',
    'message' => 'Login will work again. Type active below to confirm.',
    'item_label' => $u['employee_name'] . ' (' . $u['user_id'] . ')',
    'cancel_url' => $cancel,
    'confirm_word' => 'active',
    'confirm_btn' => 'Active',
    'confirm_btn_class' => 'btn btn-success',
    'title_class' => 'is-activate',
    'hidden' => ['id' => (int) $u['id']],
]);
?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
