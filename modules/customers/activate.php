<?php
/**
 * Make customer Active again
 *
 * Permission: customers.delete (same Admin control as Inactive)
 * Popup: type "active" to confirm.
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

if ($c['status'] === 'Active') {
    flash('error', 'Customer is already Active.');
    redirect(base_url('modules/customers/view.php?id=' . $id));
}

$cancel = base_url('modules/customers/view.php?id=' . $id);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!delete_confirm_ok($_POST['confirm_text'] ?? '', 'active')) {
        $errors[] = 'Please type active in the box to confirm.';
    } else {
        try {
            soft_activate_customer($id, trim($_POST['reason'] ?? ''));
            flash('success', $c['customer_code'] . ' set to Active.', 'activate', [
                'entity' => 'customer',
                'id' => $id,
                'to' => 'Inactive',
                'redirect' => base_url('modules/customers/view.php?id=' . $id),
            ]);
            redirect(base_url('modules/customers/view.php?id=' . $id));
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'Activate Customer';
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
    'title' => 'Make customer Active?',
    'message' => 'Status will become Active again. Type active below to confirm.',
    'item_label' => $c['customer_code'] . ' — ' . $c['customer_name'],
    'cancel_url' => $cancel,
    'confirm_word' => 'active',
    'confirm_btn' => 'Active',
    'confirm_btn_class' => 'btn btn-success',
    'title_class' => 'is-activate',
    'hidden' => ['id' => (int) $c['id']],
]);
?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
