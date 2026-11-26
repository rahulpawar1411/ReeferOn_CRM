<?php
/**
 * Make customer Inactive
 *
 * Soft status change only. Customer Code kept. Permission: customers.delete
 * Popup: type "inactive" to confirm.
 */
require_once __DIR__ . '/../../includes/functions.php';
require_perm('customers.delete');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM customers WHERE id = ?');
$stmt->execute([$id]);
$c = $stmt->fetch();

if (!$c) {
    flash('error', 'Customer not found.');
    redirect(base_url('modules/customers/index.php'));
}

if ($c['status'] === 'Inactive') {
    flash('error', 'Customer is already Inactive.');
    redirect(base_url('modules/customers/view.php?id=' . $id));
}

$cancel = base_url('modules/customers/view.php?id=' . $id);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!delete_confirm_ok($_POST['confirm_text'] ?? '', 'inactive')) {
        $errors[] = 'Please type inactive in the box to confirm.';
    } else {
        try {
            soft_delete_customer($id, trim($_POST['reason'] ?? ''));
            flash('success', $c['customer_code'] . ' set to Inactive.', 'delete');
            redirect(base_url('modules/customers/index.php'));
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'Inactive Customer';
$currentPage = 'customers';
require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($errors): ?>
    <div class="alert alert-error" style="max-width:480px;margin:16px auto">
        <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
render_delete_confirm_modal([
    'title' => 'Make customer Inactive?',
    'message' => 'Customer Code stays for history. Status becomes Inactive. Type inactive below to confirm.',
    'item_label' => $c['customer_code'] . ' — ' . $c['customer_name'],
    'cancel_url' => $cancel,
    'confirm_word' => 'inactive',
    'confirm_btn' => 'Inactive',
    'hidden' => ['id' => (int) $c['id']],
]);
?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
