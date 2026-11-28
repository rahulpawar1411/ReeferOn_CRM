<?php
/**
 * Edit customer
 *
 * Update customer master details and Active / Inactive / On Hold status.
 * Customer code itself cannot be changed.
 */
$pageTitle = 'Edit Customer';
$currentPage = 'customers';
require_once __DIR__ . '/../../includes/functions.php';
require_perm('customers.edit');

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM customers WHERE id = ?');
$stmt->execute([$id]);
$c = $stmt->fetch();

if (!$c) {
    flash('error', 'Customer not found.');
    redirect(base_url('modules/customers/index.php'));
}

$user = current_user();
$errors = [];
$dupMatches = [];

// Save customer edits on form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $brand_name = trim($_POST['brand_name'] ?? '');
    $business_type = sanitize_option_value($_POST['business_type'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $mobile = phone_digits($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $gstin = strtoupper(trim($_POST['gstin'] ?? ''));
    $registered_address = trim($_POST['registered_address'] ?? '');
    $billing_address = trim($_POST['billing_address'] ?? '');
    $operating_location = trim($_POST['operating_location'] ?? '');
    $services = trim($_POST['services'] ?? '');
    $temperature_requirement = sanitize_option_value($_POST['temperature_requirement'] ?? '', 50);
    $commercial_details = trim($_POST['commercial_details'] ?? '');
    $payment_terms = trim($_POST['payment_terms'] ?? '');
    $credit_limit = trim($_POST['credit_limit'] ?? '');
    $agreement_start_date = trim($_POST['agreement_start_date'] ?? '');
    $agreement_end_date = trim($_POST['agreement_end_date'] ?? '');
    $status = $_POST['status'] ?? '';
    $confirm_dup = !empty($_POST['confirm_duplicate']);

    if ($customer_name === '') $errors[] = 'Customer Name is required.';
    if ($contact_person === '') $errors[] = 'Contact Person is required.';
    $phoneErr = phone_error($mobile, true);
    if ($phoneErr) $errors[] = $phoneErr;
    foreach ([
        option_value_error($business_type, business_types(), 'Business Type', true, $c['business_type'] ?? null, 'business_type'),
        option_value_error($temperature_requirement, temperature_options(), 'Temperature', false, $c['temperature_requirement'] ?? null, 'temperature_requirement'),
    ] as $optErr) {
        if ($optErr) $errors[] = $optErr;
    }
    if (!in_array($status, customer_statuses(), true)) $errors[] = 'Invalid status.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
    if ($credit_limit !== '' && !is_numeric($credit_limit)) $errors[] = 'Credit limit must be a number.';
    // Status Active/Inactive/On Hold: Inactive needs customers.delete
    if ($status === 'Inactive' && ($c['status'] ?? '') !== 'Inactive' && !can('customers.delete')) {
        $errors[] = 'You do not have permission to make customers Inactive.';
    }

    if (!$errors) {
        $dupMatches = find_customer_duplicates($customer_name, $gstin !== '' ? $gstin : null, $id);
        if ($dupMatches && !$confirm_dup) {
            $errors[] = 'Possible duplicate found (GSTIN / name). Review matches below and tick confirm to save anyway.';
        }
    }

    if (!$errors) {
        try {
            $upd = db()->prepare(
                "UPDATE customers SET
                    customer_name=?, brand_name=?, business_type=?, contact_person=?, mobile=?, email=?, gstin=?,
                    registered_address=?, billing_address=?, operating_location=?, services=?, temperature_requirement=?,
                    commercial_details=?, payment_terms=?, credit_limit=?,
                    agreement_start_date=?, agreement_end_date=?, status=?, updated_by=?
                 WHERE id=?"
            );
            $upd->execute([
                $customer_name,
                $brand_name !== '' ? $brand_name : null,
                $business_type,
                $contact_person,
                $mobile,
                $email !== '' ? $email : null,
                $gstin !== '' ? $gstin : null,
                $registered_address !== '' ? $registered_address : null,
                $billing_address !== '' ? $billing_address : null,
                $operating_location !== '' ? $operating_location : null,
                $services !== '' ? $services : null,
                $temperature_requirement !== '' ? $temperature_requirement : null,
                $commercial_details !== '' ? $commercial_details : null,
                $payment_terms !== '' ? $payment_terms : null,
                $credit_limit !== '' ? $credit_limit : null,
                $agreement_start_date !== '' ? $agreement_start_date : null,
                $agreement_end_date !== '' ? $agreement_end_date : null,
                $status,
                $user['id'],
                $id,
            ]);

            write_audit(
                'customers',
                $c['customer_code'],
                'UPDATE',
                'status=' . $c['status'] . '; name=' . $c['customer_name'],
                'status=' . $status . '; name=' . $customer_name
            );
            flash('success', 'Customer updated.', 'edit');
            redirect(base_url('modules/customers/view.php?id=' . $id));
        } catch (Throwable $e) {
            $errors[] = 'Could not save: ' . $e->getMessage();
        }
    }

    $c = array_merge($c, $_POST);
    $c['gstin'] = $gstin;
}

$pageTitle = 'Edit ' . $c['customer_code'];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Edit Customer</h2>
    <p class="step-note"><strong>Customer code cannot be changed.</strong> Soft status only: Active / Inactive / On Hold (no permanent delete).</p>
    <p>Code: <strong><?= e($c['customer_code']) ?></strong> | Source: <?= e($c['source']) ?></p>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?>
                <div><?= e($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($dupMatches): ?>
        <div class="alert alert-error">
            <strong>Possible duplicates:</strong>
            <ul>
                <?php foreach ($dupMatches as $d): ?>
                    <li>
                        <a href="<?= e(base_url('modules/customers/view.php?id=' . $d['id'])) ?>"><?= e($d['customer_code']) ?></a>
                        — <?= e($d['customer_name']) ?>
                        / GSTIN <?= e($d['gstin'] ?? '-') ?>
                        / <?= e($d['status']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <div class="form-grid">
            <div class="form-group">
                <label>Customer Name <span class="required">*</span></label>
                <input type="text" name="customer_name" required value="<?= e($c['customer_name']) ?>">
            </div>
            <div class="form-group">
                <label>Brand Name</label>
                <input type="text" name="brand_name" value="<?= e($c['brand_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Business Type <span class="required">*</span></label>
                <?php render_select_with_add('business_type', business_types(), $c['business_type'], [
                    'required' => true,
                    'help' => 'Business type from the fixed list',
                ]); ?>
            </div>
            <div class="form-group">
                <label>Status <span class="required">*</span></label>
                <?php
                $statusOpts = can('customers.delete')
                    ? customer_statuses()
                    : array_values(array_filter(customer_statuses(), static fn($s) => $s !== 'Inactive' || ($c['status'] ?? '') === 'Inactive'));
                if (($c['status'] ?? '') === 'Inactive' && !can('customers.delete')) {
                    $statusOpts = ['Inactive'];
                }
                render_select_with_add('status', $statusOpts, $c['status'] ?? 'Active', [
                    'required' => true,
                    'allow_add' => false,
                    'empty' => false,
                    'help' => can('customers.delete')
                        ? 'Active / Inactive / On Hold'
                        : 'Inactive requires customers.delete permission',
                ]);
                ?>
            </div>
            <div class="form-group">
                <label>Contact Person <span class="required">*</span></label>
                <input type="text" name="contact_person" required value="<?= e($c['contact_person']) ?>">
            </div>
            <div class="form-group">
                <label>Phone number <span class="required">*</span></label>
                <?php render_phone_input('mobile', $c['mobile'] ?? '', true); ?>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= e($c['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>GSTIN</label>
                <input type="text" name="gstin" maxlength="20" value="<?= e($c['gstin'] ?? '') ?>">
            </div>
            <div class="form-group full">
                <label>Registered Address</label>
                <textarea name="registered_address"><?= e($c['registered_address'] ?? '') ?></textarea>
            </div>
            <div class="form-group full">
                <label>Billing Address</label>
                <textarea name="billing_address"><?= e($c['billing_address'] ?? '') ?></textarea>
            </div>
            <div class="form-group full">
                <label>Operating Location</label>
                <textarea name="operating_location"><?= e($c['operating_location'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Services</label>
                <input type="text" name="services" value="<?= e($c['services'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Temperature</label>
                <?php render_select_with_add('temperature_requirement', temperature_options(), $c['temperature_requirement'] ?? '', [
                    'help' => 'Cold-chain temperature',
                ]); ?>
            </div>
            <div class="form-group">
                <label>Commercial Details</label>
                <input type="text" name="commercial_details" value="<?= e($c['commercial_details'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Payment Terms</label>
                <input type="text" name="payment_terms" value="<?= e($c['payment_terms'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Credit Limit (₹)</label>
                <input type="number" step="0.01" name="credit_limit" value="<?= e($c['credit_limit'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Agreement Start</label>
                <input type="date" name="agreement_start_date" value="<?= e($c['agreement_start_date'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Agreement End</label>
                <input type="date" name="agreement_end_date" value="<?= e($c['agreement_end_date'] ?? '') ?>">
            </div>
        </div>

        <?php if ($dupMatches): ?>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="confirm_duplicate" value="1">
                    I reviewed possible duplicates and still want to save
                </label>
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn">Save</button>
            <a class="btn btn-secondary" href="<?= e(base_url('modules/customers/view.php?id=' . $id)) ?>">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
