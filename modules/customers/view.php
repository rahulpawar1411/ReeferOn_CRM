<?php
/**
 * View customer
 *
 * Customer Master record with links to onboarding / prospect.
 * Toolbar: Edit, Inactive / Active (if allowed).
 */
$pageTitle = 'View Customer';
$currentPage = 'customers';
require_once __DIR__ . '/../../includes/functions.php';
require_perm('customers.view');

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM customers WHERE id = ?');
$stmt->execute([$id]);
$c = $stmt->fetch();

if (!$c) {
    flash('error', 'Customer not found.');
    redirect(base_url('modules/customers/index.php'));
}

$pageTitle = 'Customer ' . $c['customer_code'];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="toolbar">
    <?php if (can('customers.edit')): ?>
        <a class="btn" href="<?= e(base_url('modules/customers/edit.php?id=' . $c['id'])) ?>">Edit</a>
    <?php endif; ?>
    <?php if (can('customers.delete') && $c['status'] !== 'Inactive'): ?>
        <a class="btn btn-danger" href="<?= e(base_url('modules/customers/delete.php?id=' . $c['id'])) ?>">Inactive</a>
    <?php elseif (can('customers.delete') && $c['status'] === 'Inactive'): ?>
        <a class="btn btn-success" href="<?= e(base_url('modules/customers/activate.php?id=' . $c['id'])) ?>">Active</a>
    <?php endif; ?>
    <a class="btn btn-secondary btn-back-list" href="<?= e(base_url('modules/customers/index.php')) ?>">Back to List</a>
    <?php if (!empty($c['onboarding_pk'])): ?>
        <a class="btn btn-secondary" href="<?= e(base_url('modules/onboarding/view.php?id=' . $c['onboarding_pk'])) ?>">Open Onboarding</a>
    <?php endif; ?>
    <?php if (!empty($c['prospect_pk'])): ?>
        <a class="btn btn-secondary" href="<?= e(base_url('modules/prospects/view.php?id=' . $c['prospect_pk'])) ?>">Open Prospect</a>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Customer Master Record</h2>
    <p class="step-note"><strong>Official customer:</strong> Code cannot be edited. Use Edit for details / Active–Inactive–On Hold.</p>
    <dl class="dl-grid">
        <dt>Customer Code</dt><dd><strong><?= e($c['customer_code']) ?></strong></dd>
        <dt>Source</dt><dd><?= e($c['source']) ?></dd>
        <dt>Status</dt><dd><span class="badge"><?= e($c['status']) ?></span></dd>
        <dt>Customer Name</dt><dd><?= e($c['customer_name']) ?></dd>
        <dt>Brand Name</dt><dd><?= e($c['brand_name'] ?? '-') ?></dd>
        <dt>Business Type</dt><dd><?= e($c['business_type']) ?></dd>
        <dt>Contact Person</dt><dd><?= e($c['contact_person']) ?></dd>
        <dt>Phone</dt><dd><?= e($c['mobile']) ?></dd>
        <dt>Email</dt><dd><?= e($c['email'] ?? '-') ?></dd>
        <dt>GSTIN</dt><dd><?= e($c['gstin'] ?? '-') ?></dd>
        <dt>Registered Address</dt><dd><?= nl2br(e($c['registered_address'] ?? '-')) ?></dd>
        <dt>Billing Address</dt><dd><?= nl2br(e($c['billing_address'] ?? '-')) ?></dd>
        <dt>Operating Location</dt><dd><?= nl2br(e($c['operating_location'] ?? '-')) ?></dd>
        <dt>Services</dt><dd><?= e($c['services'] ?? '-') ?></dd>
        <dt>Temperature</dt><dd><?= e($c['temperature_requirement'] ?? '-') ?></dd>
        <dt>Commercial Details</dt><dd><?= e($c['commercial_details'] ?? '-') ?></dd>
        <dt>Payment Terms</dt><dd><?= e($c['payment_terms'] ?? '-') ?></dd>
        <dt>Credit Limit</dt><dd><?= e(format_money($c['credit_limit'])) ?></dd>
        <dt>Agreement Start</dt><dd><?= e($c['agreement_start_date'] ?? '-') ?></dd>
        <dt>Account Owner</dt><dd><?= e($c['account_owner_name'] ?? '-') ?></dd>
        <dt>Created</dt><dd><?= e($c['created_at']) ?></dd>
    </dl>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
