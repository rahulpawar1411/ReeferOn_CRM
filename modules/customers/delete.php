<?php
/**
 * Soft-delete customer
 *
 * Sets status = Inactive. Customer Code is kept. Permission: customers.delete
 * Opens a centered popup — user must type "delete" to confirm.
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
    if (!delete_confirm_ok($_POST['confirm_text'] ?? '')) {
        $errors[] = 'Please type delete in the box to confirm.';
    } else {
        try {
            soft_delete_customer($id, trim($_POST['reason'] ?? ''));
            flash('success', $c['customer_code'] . ' marked Inactive (soft delete).', 'delete');
            redirect(base_url('modules/customers/index.php'));
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'Delete Customer';
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
    'title' => 'Delete customer?',
    'message' => 'Status becomes Inactive. Customer Code stays for history. Type delete below to confirm.',
    'item_label' => $c['customer_code'] . ' — ' . $c['customer_name'],
    'cancel_url' => $cancel,
    'hidden' => ['id' => (int) $c['id']],
]);
?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
