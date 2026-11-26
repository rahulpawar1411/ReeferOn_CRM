<?php
/**
 * View onboarding
 *
 * Customer details, document checklist (View / Download / Clear), and Admin approval.
 */
$pageTitle = 'View Onboarding';
$currentPage = 'onboarding';
require_once __DIR__ . '/../../includes/functions.php';
require_perm('onboarding.view');

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM onboarding WHERE id = ?');
$stmt->execute([$id]);
$o = $stmt->fetch();

if (!$o) {
    flash('error', 'Onboarding not found.');
    redirect(base_url('modules/onboarding/index.php'));
}

$docs = db()->prepare('SELECT * FROM onboarding_documents WHERE onboarding_pk = ? ORDER BY id');
$docs->execute([$id]);
$documents = $docs->fetchAll();

$apprs = db()->prepare('SELECT * FROM onboarding_approvals WHERE onboarding_pk = ? ORDER BY id');
$apprs->execute([$id]);
$approvals = $apprs->fetchAll();

$pageTitle = 'Onboarding ' . $o['onboarding_id'];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="toolbar">
    <a class="btn" href="<?= e(base_url('modules/onboarding/edit.php?id=' . $o['id'])) ?>">Edit Details</a>
    <a class="btn btn-secondary btn-back-list" href="<?= e(base_url('modules/onboarding/index.php')) ?>">Back to List</a>
    <a class="btn btn-secondary" href="<?= e(base_url('modules/prospects/view.php?id=' . $o['prospect_pk'])) ?>">Open Prospect</a>
</div>

<div class="card">
    <h2>Customer Information</h2>
    <p class="step-note"><strong>Step 3 – Onboarding view:</strong> Edit details (GST/PAN/address) → set every document to <strong>Received</strong> or <strong>Not Applicable</strong> → then <em>Submit for Admin Approval</em> from Edit.</p>
    <dl class="dl-grid">
        <dt>Onboarding ID</dt><dd><?= e($o['onboarding_id']) ?></dd>
        <dt>Prospect ID</dt><dd><?= e($o['prospect_id']) ?></dd>
        <dt>Status</dt><dd><span class="badge"><?= e($o['overall_status']) ?></span></dd>
        <dt>Customer Name</dt><dd><?= e($o['customer_name']) ?></dd>
        <dt>Brand Name</dt><dd><?= e($o['brand_name'] ?? '-') ?></dd>
        <dt>Business Type</dt><dd><?= e($o['business_type']) ?></dd>
        <dt>Contact Person</dt><dd><?= e($o['contact_person']) ?></dd>
        <dt>Phone</dt><dd><?= e($o['mobile']) ?></dd>
        <dt>Email</dt><dd><?= e($o['email'] ?? '-') ?></dd>
        <dt>GSTIN</dt><dd><?= e($o['gstin'] ?? '-') ?></dd>
        <dt>PAN</dt><dd><?= e($o['pan'] ?? '-') ?></dd>
        <dt>Registered Address</dt><dd><?= nl2br(e($o['registered_address'] ?? '-')) ?></dd>
        <dt>Billing Address</dt><dd><?= nl2br(e($o['billing_address'] ?? '-')) ?></dd>
        <dt>Operating Address</dt><dd><?= nl2br(e($o['operating_address'] ?? '-')) ?></dd>
    </dl>
</div>

<div class="card">
    <h2>Business / Service</h2>
    <dl class="dl-grid">
        <dt>Service</dt><dd><?= e($o['service_required']) ?></dd>
        <dt>Temperature</dt><dd><?= e($o['temperature_requirement']) ?></dd>
        <dt>Vehicle</dt><dd><?= e($o['vehicle_requirement'] ?? '-') ?></dd>
        <dt>Storage</dt><dd><?= e($o['storage_requirement'] ?? '-') ?></dd>
        <dt>Origin</dt><dd><?= e($o['origin'] ?? '-') ?></dd>
        <dt>Destination</dt><dd><?= e($o['destination'] ?? '-') ?></dd>
        <dt>Monthly Volume</dt><dd><?= e($o['estimated_monthly_volume'] ?? '-') ?></dd>
        <dt>Business Start</dt><dd><?= e($o['business_start_date'] ?? '-') ?></dd>
        <dt>Commercial Ref</dt><dd><?= e($o['commercial_reference'] ?? '-') ?></dd>
        <dt>Payment Terms</dt><dd><?= e($o['payment_terms'] ?? '-') ?></dd>
        <dt>Credit Limit</dt><dd><?= e(format_money($o['credit_limit'])) ?></dd>
    </dl>
</div>

<div class="card">
    <h2>Documents</h2>
    <p class="step-note">After upload you can always come back here to <strong>View</strong> (open in browser) or <strong>Download</strong> the file.</p>
    <table class="data">
        <thead>
        <tr>
            <th>Document</th>
            <th>Status</th>
            <th>File</th>
            <th>Uploaded</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($documents as $d): ?>
            <tr>
                <td><?= e($d['doc_type']) ?></td>
                <td><span class="badge"><?= e($d['status']) ?></span></td>
                <td>
                    <?php if (!empty($d['file_path'])): ?>
                        <?= e($d['file_name']) ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td><?= e($d['uploaded_by_name'] ?? '-') ?><?= !empty($d['updated_at']) ? ' / ' . e($d['updated_at']) : '' ?></td>
                <td>
                    <?php if (!empty($d['file_path'])): ?>
                        <a href="<?= e(base_url('modules/onboarding/download.php?id=' . $d['id'])) ?>" target="_blank" rel="noopener">View</a>
                        ·
                        <a href="<?= e(base_url('modules/onboarding/download.php?id=' . $d['id'] . '&mode=download')) ?>">Download</a>
                        ·
                    <?php endif; ?>
                    <?php if (can('onboarding.edit')): ?>
                        <a href="<?= e(base_url('modules/onboarding/upload.php?id=' . $d['id'])) ?>">Upload / Update</a>
                    <?php endif; ?>
                    <?php if (can('onboarding.delete_docs') && !empty($d['file_path']) && $o['overall_status'] !== 'Approved'): ?>
                        ·
                        <a class="link-danger" href="<?= e(base_url('modules/onboarding/delete_doc.php?id=' . $d['id'])) ?>">Clear</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$cust = null;
try {
    $cStmt = db()->prepare('SELECT id, customer_code, status FROM customers WHERE onboarding_pk = ?');
    $cStmt->execute([$o['id']]);
    $cust = $cStmt->fetch() ?: null;
} catch (Throwable $e) {
    $cust = null;
}
?>

<?php if ($cust): ?>
<div class="alert alert-success">
    Customer created: <strong><?= e($cust['customer_code']) ?></strong>
    — <a href="<?= e(base_url('modules/customers/view.php?id=' . $cust['id'])) ?>">Open in Customer Master</a>
</div>
<?php endif; ?>

<div class="card">
    <h2>Admin Approval</h2>
    <p class="step-note"><strong>Step 4:</strong> After submit, Admin Approve / Reject. Approve creates customer code and Customer Master record.</p>
    <table class="data">
        <thead>
        <tr>
            <th>Approver Role</th>
            <th>Status</th>
            <th>Approver</th>
            <th>Remarks</th>
            <th>Decided</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($approvals as $a): ?>
            <?php if ($a['department'] !== 'Admin') continue; ?>
            <tr>
                <td><?= e($a['department']) ?></td>
                <td><span class="badge"><?= e($a['status']) ?></span></td>
                <td><?= e($a['approver_name'] ?? '-') ?></td>
                <td><?= e($a['remarks'] ?? '-') ?></td>
                <td><?= e($a['decided_at'] ?? '-') ?></td>
                <td>
                    <?php if ($a['status'] === 'Pending' && in_array($o['overall_status'], ['Submitted', 'In Approval'], true)): ?>
                        <a href="<?= e(base_url('modules/approvals/decide.php?id=' . $a['id'])) ?>">Decide</a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
