<?php
$pageTitle = 'Admin Approve / Reject';
$currentPage = 'approvals';
require_once __DIR__ . '/../../includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare(
    "SELECT a.*, o.onboarding_id, o.customer_name, o.prospect_id, o.overall_status,
            o.gstin, o.pan, o.service_required, o.vehicle_requirement, o.payment_terms, o.credit_limit
     FROM onboarding_approvals a
     JOIN onboarding o ON o.id = a.onboarding_pk
     WHERE a.id = ?"
);
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    flash('error', 'Approval not found.');
    redirect(base_url('modules/approvals/index.php'));
}

if ($row['department'] !== 'Admin') {
    flash('error', 'Only Admin approval is used.');
    redirect(base_url('modules/approvals/index.php'));
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $decision = $_POST['decision'] ?? '';
    $remarks = trim($_POST['remarks'] ?? '');

    if (!in_array($decision, ['Approved', 'Rejected'], true)) {
        $errors[] = 'Choose Approve or Reject.';
    }
    if ($remarks === '') {
        $errors[] = 'Remarks are required.';
    }

    if (!$errors) {
        try {
            $result = decide_onboarding_approval($id, $decision, $remarks);
            flash('success', $result['message']);
            if (!empty($result['customer_code'])) {
                $cust = db()->prepare('SELECT id FROM customers WHERE customer_code = ?');
                $cust->execute([$result['customer_code']]);
                $c = $cust->fetch();
                if ($c) {
                    redirect(base_url('modules/customers/view.php?id=' . $c['id']));
                }
            }
            redirect(base_url('modules/onboarding/view.php?id=' . $result['onboarding_pk']));
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'Admin Decision';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Admin – Approve / Reject</h2>
    <p class="step-note"><strong>Step 4:</strong> Review full details + documents. Approve creates <strong>REO-CUS-xxxx</strong> and adds the customer to Customer Master. Reject sends it back for correction.</p>

    <dl class="dl-grid">
        <dt>Onboarding</dt><dd><?= e($row['onboarding_id']) ?></dd>
        <dt>Prospect</dt><dd><?= e($row['prospect_id']) ?></dd>
        <dt>Customer</dt><dd><?= e($row['customer_name']) ?></dd>
        <dt>GSTIN</dt><dd><?= e($row['gstin'] ?? '-') ?></dd>
        <dt>PAN</dt><dd><?= e($row['pan'] ?? '-') ?></dd>
        <dt>Service</dt><dd><?= e($row['service_required'] ?? '-') ?></dd>
        <dt>Vehicle</dt><dd><?= e($row['vehicle_requirement'] ?? '-') ?></dd>
        <dt>Payment Terms</dt><dd><?= e($row['payment_terms'] ?? '-') ?></dd>
        <dt>Credit Limit</dt><dd><?= e(format_money($row['credit_limit'] ?? null)) ?></dd>
        <dt>Onboarding Status</dt><dd><span class="badge"><?= e($row['overall_status']) ?></span></dd>
        <dt>This Approval</dt><dd><span class="badge"><?= e($row['status']) ?></span></dd>
    </dl>

    <p><a href="<?= e(base_url('modules/onboarding/view.php?id=' . $row['onboarding_pk'])) ?>">Open full onboarding + documents</a></p>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?>
                <div><?= e($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($row['status'] === 'Pending'): ?>
        <form method="post" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label>Decision <span class="required">*</span></label>
                    <select name="decision" required>
                        <option value="">-- Select --</option>
                        <option value="Approved" <?= (($_POST['decision'] ?? '') === 'Approved') ? 'selected' : '' ?>>Approve</option>
                        <option value="Rejected" <?= (($_POST['decision'] ?? '') === 'Rejected') ? 'selected' : '' ?>>Reject</option>
                    </select>
                </div>
                <div class="form-group full">
                    <label>Remarks <span class="required">*</span></label>
                    <textarea name="remarks" required><?= e($_POST['remarks'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn">Save Decision</button>
                <a class="btn btn-secondary" href="<?= e(base_url('modules/approvals/index.php')) ?>">Cancel</a>
            </div>
        </form>
    <?php else: ?>
        <div class="alert alert-success">Already decided: <?= e($row['status']) ?>.</div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
