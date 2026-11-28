<?php
/**
 * Edit onboarding
 *
 * Fill GST/PAN/addresses and submit for Admin approval when ready.
 */
$pageTitle = 'Edit Onboarding';
$currentPage = 'onboarding';
require_once __DIR__ . '/../../includes/functions.php';
require_perm('onboarding.edit');

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM onboarding WHERE id = ?');
$stmt->execute([$id]);
$o = $stmt->fetch();

if (!$o) {
    flash('error', 'Onboarding not found.');
    redirect(base_url('modules/onboarding/index.php'));
}

$user = current_user();
$errors = [];

// Save draft or submit for Admin approval
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $brand_name = trim($_POST['brand_name'] ?? '');
    $business_type = sanitize_option_value($_POST['business_type'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $mobile = phone_digits($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $registered_address = trim($_POST['registered_address'] ?? '');
    $billing_address = trim($_POST['billing_address'] ?? '');
    $operating_address = trim($_POST['operating_address'] ?? '');
    $gstin = strtoupper(trim($_POST['gstin'] ?? ''));
    $pan = strtoupper(trim($_POST['pan'] ?? ''));
    $service_required = sanitize_option_value($_POST['service_required'] ?? '');
    $temperature_requirement = sanitize_option_value($_POST['temperature_requirement'] ?? '', 50);
    $vehicle_requirement = sanitize_option_value($_POST['vehicle_requirement'] ?? '', 50);
    $storage_requirement = sanitize_option_value($_POST['storage_requirement'] ?? '', 50);
    $origin = trim($_POST['origin'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $estimated_monthly_volume = trim($_POST['estimated_monthly_volume'] ?? '');
    $business_start_date = trim($_POST['business_start_date'] ?? '');
    $commercial_reference = trim($_POST['commercial_reference'] ?? '');
    $payment_terms = trim($_POST['payment_terms'] ?? '');
    $credit_limit = trim($_POST['credit_limit'] ?? '');
    $action = $_POST['action'] ?? 'save';
    // Approved / Rejected / In Approval only via Approvals — never from this form
    if ($action === 'submit') {
        $overall_status = 'Submitted';
    } elseif (in_array($o['overall_status'], ['Draft', 'Rejected', 'Cancelled'], true)) {
        $postedStatus = $_POST['overall_status'] ?? 'Draft';
        if (!in_array($postedStatus, ['Draft', 'Cancelled'], true)) {
            $errors[] = 'Invalid status.';
            $overall_status = $o['overall_status'];
        } else {
            $overall_status = $postedStatus;
        }
    } else {
        $overall_status = $o['overall_status'];
    }

    if ($customer_name === '') $errors[] = 'Customer Name is required.';
    if ($contact_person === '') $errors[] = 'Contact Person is required.';
    $phoneErr = phone_error($mobile, true);
    if ($phoneErr) $errors[] = $phoneErr;
    foreach ([
        option_value_error($business_type, business_types(), 'Business Type', true, $o['business_type'] ?? null, 'business_type'),
        option_value_error($service_required, service_required_options(), 'Service Required', true, $o['service_required'] ?? null, 'service_required'),
        option_value_error($temperature_requirement, temperature_options(), 'Temperature', true, $o['temperature_requirement'] ?? null, 'temperature_requirement'),
        option_value_error($vehicle_requirement, vehicle_requirements(), 'Vehicle Requirement', false, $o['vehicle_requirement'] ?? null, 'vehicle_requirement'),
        option_value_error($storage_requirement, storage_requirements(), 'Storage Requirement', false, $o['storage_requirement'] ?? null, 'storage_requirement'),
    ] as $optErr) {
        if ($optErr) $errors[] = $optErr;
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
    if ($credit_limit !== '' && !is_numeric($credit_limit)) $errors[] = 'Credit limit must be a number.';

    if ($action === 'submit') {
        if (!in_array($o['overall_status'], ['Draft', 'Rejected', 'Cancelled'], true)) {
            $errors[] = 'This onboarding cannot be submitted from its current status.';
        }
        if ($gstin === '') $errors[] = 'GSTIN is required before submit.';
        if ($registered_address === '') $errors[] = 'Registered Address is required before submit.';
        if ($pan === '') $errors[] = 'PAN is required before submit.';
        // Every document must be Received (with file) or Not Applicable
        foreach (onboarding_doc_submit_errors($id) as $docErr) {
            $errors[] = $docErr;
        }
    }

    if (!$errors) {
        try {
            $wasRejected = ($o['overall_status'] === 'Rejected');

            $upd = db()->prepare(
                "UPDATE onboarding SET
                    customer_name=?, brand_name=?, business_type=?, contact_person=?, mobile=?, email=?,
                    registered_address=?, billing_address=?, operating_address=?, gstin=?, pan=?,
                    service_required=?, temperature_requirement=?, vehicle_requirement=?, storage_requirement=?,
                    origin=?, destination=?, estimated_monthly_volume=?, business_start_date=?,
                    commercial_reference=?, payment_terms=?, credit_limit=?, overall_status=?, updated_by=?
                 WHERE id=?"
            );
            $upd->execute([
                $customer_name,
                $brand_name !== '' ? $brand_name : null,
                $business_type,
                $contact_person,
                $mobile,
                $email !== '' ? $email : null,
                $registered_address !== '' ? $registered_address : null,
                $billing_address !== '' ? $billing_address : null,
                $operating_address !== '' ? $operating_address : null,
                $gstin !== '' ? $gstin : null,
                $pan !== '' ? $pan : null,
                $service_required,
                $temperature_requirement,
                $vehicle_requirement !== '' ? $vehicle_requirement : null,
                $storage_requirement !== '' ? $storage_requirement : null,
                $origin !== '' ? $origin : null,
                $destination !== '' ? $destination : null,
                $estimated_monthly_volume !== '' ? $estimated_monthly_volume : null,
                $business_start_date !== '' ? $business_start_date : null,
                $commercial_reference !== '' ? $commercial_reference : null,
                $payment_terms !== '' ? $payment_terms : null,
                $credit_limit !== '' ? $credit_limit : null,
                $overall_status,
                $user['id'],
                $id,
            ]);

            if ($action === 'submit' && $wasRejected) {
                reset_onboarding_approvals($id);
            }

            write_audit('onboarding', $o['onboarding_id'], $action === 'submit' ? 'SUBMIT' : 'UPDATE', $o['overall_status'], $overall_status);

            if ($action === 'submit') {
                create_notification(
                    'Admin Approval Pending',
                    'Admin Approval Pending',
                    $o['onboarding_id'] . ' – ' . $customer_name . ' submitted for approval.',
                    base_url('modules/approvals/index.php'),
                    'onboarding',
                    $o['onboarding_id']
                );
            }

            flash(
                'success',
                $action === 'submit'
                    ? 'Onboarding submitted for Admin approval.'
                    : 'Onboarding saved.',
                'edit'
            );
            redirect(base_url('modules/onboarding/view.php?id=' . $id));
        } catch (Throwable $e) {
            $errors[] = 'Could not save: ' . $e->getMessage();
        }
    }

    $o = array_merge($o, $_POST);
}

$pageTitle = 'Edit ' . $o['onboarding_id'];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Complete Onboarding Details</h2>
    <p class="step-note"><strong>Step 3 – Onboarding:</strong> Fill GST, PAN, addresses and other details. On the View page, every document must be <strong>Received</strong> (file uploaded) or <strong>Not Applicable</strong> before <em>Submit for Admin Approval</em>.</p>
    <p>
        ID <strong><?= e($o['onboarding_id']) ?></strong> |
        Prospect <a href="<?= e(base_url('modules/prospects/view.php?id=' . $o['prospect_pk'])) ?>"><?= e($o['prospect_id']) ?></a>
    </p>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?>
                <div><?= e($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <h3>Customer Information</h3>
        <div class="form-grid">
            <div class="form-group">
                <label>Customer Name <span class="required">*</span></label>
                <input type="text" name="customer_name" required value="<?= e($o['customer_name']) ?>">
            </div>
            <div class="form-group">
                <label>Brand Name</label>
                <input type="text" name="brand_name" value="<?= e($o['brand_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Business Type <span class="required">*</span></label>
                <?php render_select_with_add('business_type', business_types(), $o['business_type'], [
                    'required' => true,
                    'help' => 'Business type from the fixed list',
                ]); ?>
            </div>
            <div class="form-group">
                <label>Contact Person <span class="required">*</span></label>
                <input type="text" name="contact_person" required value="<?= e($o['contact_person']) ?>">
            </div>
            <div class="form-group">
                <label>Phone number <span class="required">*</span></label>
                <?php render_phone_input('mobile', $o['mobile'] ?? '', true); ?>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= e($o['email'] ?? '') ?>">
            </div>
            <div class="form-group full">
                <label>Registered Address</label>
                <textarea name="registered_address"><?= e($o['registered_address'] ?? '') ?></textarea>
            </div>
            <div class="form-group full">
                <label>Billing Address</label>
                <textarea name="billing_address"><?= e($o['billing_address'] ?? '') ?></textarea>
            </div>
            <div class="form-group full">
                <label>Operating Address</label>
                <textarea name="operating_address"><?= e($o['operating_address'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>GSTIN</label>
                <input type="text" name="gstin" maxlength="20" value="<?= e($o['gstin'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>PAN</label>
                <input type="text" name="pan" maxlength="20" value="<?= e($o['pan'] ?? '') ?>">
            </div>
        </div>

        <h3>Business / Service</h3>
        <div class="form-grid">
            <div class="form-group">
                <label>Service Required <span class="required">*</span></label>
                <?php render_select_with_add('service_required', service_required_options(), $o['service_required'], [
                    'required' => true,
                    'help' => 'Main service needed',
                ]); ?>
            </div>
            <div class="form-group">
                <label>Temperature <span class="required">*</span></label>
                <?php render_select_with_add('temperature_requirement', temperature_options(), $o['temperature_requirement'], [
                    'required' => true,
                    'help' => 'Cold-chain temperature',
                ]); ?>
            </div>
            <div class="form-group">
                <label>Vehicle Requirement</label>
                <?php render_select_with_add('vehicle_requirement', vehicle_requirements(), $o['vehicle_requirement'] ?? '', [
                    'help' => 'Vehicle size',
                ]); ?>
            </div>
            <div class="form-group">
                <label>Storage Requirement</label>
                <?php render_select_with_add('storage_requirement', storage_requirements(), $o['storage_requirement'] ?? '', [
                    'help' => 'Storage type',
                ]); ?>
            </div>
            <div class="form-group">
                <label>Origin</label>
                <input type="text" name="origin" value="<?= e($o['origin'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Destination</label>
                <input type="text" name="destination" value="<?= e($o['destination'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Estimated Monthly Volume</label>
                <input type="text" name="estimated_monthly_volume" value="<?= e($o['estimated_monthly_volume'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Business Start Date</label>
                <input type="date" name="business_start_date" value="<?= e($o['business_start_date'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Commercial Reference</label>
                <input type="text" name="commercial_reference" value="<?= e($o['commercial_reference'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Payment Terms</label>
                <input type="text" name="payment_terms" placeholder="e.g. 30 days" value="<?= e($o['payment_terms'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Credit Limit (₹)</label>
                <input type="number" step="0.01" name="credit_limit" value="<?= e($o['credit_limit'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Status</label>
                <?php if (in_array($o['overall_status'], ['Draft', 'Rejected', 'Cancelled'], true)): ?>
                    <select name="overall_status">
                        <?php foreach (['Draft', 'Cancelled'] as $s): ?>
                            <option value="<?= e($s) ?>" <?= ($o['overall_status'] === $s || ($o['overall_status'] === 'Rejected' && $s === 'Draft')) ? 'selected' : '' ?>><?= e($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($o['overall_status'] === 'Rejected'): ?>
                        <small class="step-note">Currently Rejected — save as Draft or Cancelled, or submit again.</small>
                    <?php endif; ?>
                <?php else: ?>
                    <input type="text" value="<?= e($o['overall_status']) ?>" disabled>
                    <small class="step-note">Approval statuses change only from Approvals.</small>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" name="action" value="save" class="btn">Save Draft</button>
            <button type="submit" name="action" value="submit" class="btn">Submit for Admin Approval</button>
            <a class="btn btn-secondary" href="<?= e(base_url('modules/onboarding/view.php?id=' . $id)) ?>">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
