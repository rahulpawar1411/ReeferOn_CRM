<?php
$pageTitle = 'Edit Customer';
$currentPage = 'customers';
require_once __DIR__ . '/../../includes/functions.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $brand_name = trim($_POST['brand_name'] ?? '');
    $business_type = $_POST['business_type'] ?? '';
    $contact_person = trim($_POST['contact_person'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $gstin = strtoupper(trim($_POST['gstin'] ?? ''));
    $registered_address = trim($_POST['registered_address'] ?? '');
    $billing_address = trim($_POST['billing_address'] ?? '');
    $operating_location = trim($_POST['operating_location'] ?? '');
    $services = trim($_POST['services'] ?? '');
    $temperature_requirement = $_POST['temperature_requirement'] ?? '';
    $commercial_details = trim($_POST['commercial_details'] ?? '');
    $payment_terms = trim($_POST['payment_terms'] ?? '');
    $credit_limit = trim($_POST['credit_limit'] ?? '');
    $agreement_start_date = trim($_POST['agreement_start_date'] ?? '');
    $agreement_end_date = trim($_POST['agreement_end_date'] ?? '');
    $status = $_POST['status'] ?? '';
    $confirm_dup = !empty($_POST['confirm_duplicate']);

    if ($customer_name === '') $errors[] = 'Customer Name is required.';
    if ($contact_person === '') $errors[] = 'Contact Person is required.';
    if ($mobile === '') $errors[] = 'Mobile is required.';
    if (!in_array($business_type, ['QSR', 'FMCG', 'Ice Cream', 'Retail', 'Other'], true)) {
        $errors[] = 'Invalid business type.';
    }
    if (!in_array($status, customer_statuses(), true)) $errors[] = 'Invalid status.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
    if ($credit_limit !== '' && !is_numeric($credit_limit)) $errors[] = 'Credit limit must be a number.';
    if ($temperature_requirement !== '' && !in_array($temperature_requirement, ['Ambient', 'Chiller', 'Frozen'], true)) {
        $errors[] = 'Invalid temperature.';
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
            flash('success', 'Customer updated.');
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
                <select name="business_type" required>
                    <?php foreach (['QSR', 'FMCG', 'Ice Cream', 'Retail', 'Other'] as $opt): ?>
                        <option value="<?= e($opt) ?>" <?= ($c['business_type'] === $opt) ? 'selected' : '' ?>><?= e($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Status <span class="required">*</span></label>
                <select name="status" required>
                    <?php foreach (customer_statuses() as $s): ?>
                        <option value="<?= e($s) ?>" <?= (($c['status'] ?? '') === $s) ? 'selected' : '' ?>><?= e($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Contact Person <span class="required">*</span></label>
                <input type="text" name="contact_person" required value="<?= e($c['contact_person']) ?>">
            </div>
            <div class="form-group">
                <label>Mobile <span class="required">*</span></label>
                <input type="text" name="mobile" required value="<?= e($c['mobile']) ?>">
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
                <select name="temperature_requirement">
                    <option value="">-- Select --</option>
                    <?php foreach (['Ambient', 'Chiller', 'Frozen'] as $opt): ?>
                        <option value="<?= e($opt) ?>" <?= (($c['temperature_requirement'] ?? '') === $opt) ? 'selected' : '' ?>><?= e($opt) ?></option>
                    <?php endforeach; ?>
                </select>
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
