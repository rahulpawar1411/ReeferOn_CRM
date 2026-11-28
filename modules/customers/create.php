<?php
/**
 * Add existing customer
 *
 * Create a customer directly (skip prospect pipeline). Checks duplicates.
 * Permission: customers.create
 */
$pageTitle = 'Add Existing Customer';
$currentPage = 'customer_create';
require_once __DIR__ . '/../../includes/functions.php';
require_perm('customers.create');

$user = current_user();
$errors = [];
$dupMatches = [];
$form = [
    'customer_code' => '',
    'customer_name' => '',
    'brand_name' => '',
    'business_type' => 'Other',
    'contact_person' => '',
    'mobile' => '',
    'email' => '',
    'gstin' => '',
    'registered_address' => '',
    'billing_address' => '',
    'operating_location' => '',
    'services' => '',
    'temperature_requirement' => '',
    'commercial_details' => '',
    'payment_terms' => '',
    'credit_limit' => '',
    'agreement_start_date' => '',
    'agreement_end_date' => '',
    'status' => 'Active',
];

// Save new customer on form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $k => $_) {
        if (isset($_POST[$k])) {
            $form[$k] = is_string($_POST[$k]) ? trim($_POST[$k]) : $_POST[$k];
        }
    }
    $form['gstin'] = strtoupper($form['gstin']);
    $form['customer_code'] = strtoupper(trim($form['customer_code']));
    $confirm_dup = !empty($_POST['confirm_duplicate']);

    if ($form['customer_name'] === '') $errors[] = 'Customer Name is required.';
    if ($form['contact_person'] === '') $errors[] = 'Contact Person is required.';
    $form['mobile'] = phone_digits($form['mobile']);
    $phoneErr = phone_error($form['mobile'], true);
    if ($phoneErr) $errors[] = $phoneErr;
    $form['business_type'] = sanitize_option_value($form['business_type']);
    $form['temperature_requirement'] = sanitize_option_value($form['temperature_requirement'], 50);
    foreach ([
        option_value_error($form['business_type'], business_types(), 'Business Type', true, null, 'business_type'),
        option_value_error($form['temperature_requirement'], temperature_options(), 'Temperature', false, null, 'temperature_requirement'),
    ] as $optErr) {
        if ($optErr) $errors[] = $optErr;
    }
    if (!in_array($form['status'], customer_statuses(), true)) $errors[] = 'Invalid status.';
    if ($form['email'] !== '' && !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
    if ($form['credit_limit'] !== '' && !is_numeric($form['credit_limit'])) $errors[] = 'Credit limit must be a number.';

    // If known code provided, must be unique and look like a code
    if ($form['customer_code'] !== '') {
        if (!preg_match('/^[A-Z0-9\-]{4,30}$/', $form['customer_code'])) {
            $errors[] = 'Customer code format invalid (use letters, numbers, hyphen).';
        } else {
            $codeChk = db()->prepare('SELECT id FROM customers WHERE customer_code = ?');
            $codeChk->execute([$form['customer_code']]);
            if ($codeChk->fetch()) {
                $errors[] = 'Customer code already exists: ' . $form['customer_code'];
            }
        }
    }

    if (!$errors) {
        $dupMatches = find_customer_duplicates(
            $form['customer_name'],
            $form['gstin'] !== '' ? $form['gstin'] : null
        );
        if ($dupMatches && !$confirm_dup) {
            $errors[] = 'Possible duplicate found (GSTIN / name). Review matches below and tick confirm to create anyway.';
        }
    }

    if (!$errors) {
        try {
            $code = $form['customer_code'] !== ''
                ? $form['customer_code']
                : next_business_id('customer');

            $ins = db()->prepare(
                "INSERT INTO customers (
                    customer_code, source, onboarding_pk, prospect_pk,
                    customer_name, brand_name, business_type, contact_person, mobile, email, gstin,
                    registered_address, billing_address, operating_location, services, temperature_requirement,
                    commercial_details, payment_terms, credit_limit, agreement_start_date, agreement_end_date,
                    account_owner_id, account_owner_name, status, created_by
                 ) VALUES (
                    ?, 'Existing Customer', NULL, NULL,
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?
                 )"
            );
            $ins->execute([
                $code,
                $form['customer_name'],
                $form['brand_name'] !== '' ? $form['brand_name'] : null,
                $form['business_type'],
                $form['contact_person'],
                $form['mobile'],
                $form['email'] !== '' ? $form['email'] : null,
                $form['gstin'] !== '' ? $form['gstin'] : null,
                $form['registered_address'] !== '' ? $form['registered_address'] : null,
                $form['billing_address'] !== '' ? $form['billing_address'] : null,
                $form['operating_location'] !== '' ? $form['operating_location'] : null,
                $form['services'] !== '' ? $form['services'] : null,
                $form['temperature_requirement'] !== '' ? $form['temperature_requirement'] : null,
                $form['commercial_details'] !== '' ? $form['commercial_details'] : null,
                $form['payment_terms'] !== '' ? $form['payment_terms'] : null,
                $form['credit_limit'] !== '' ? $form['credit_limit'] : null,
                $form['agreement_start_date'] !== '' ? $form['agreement_start_date'] : null,
                $form['agreement_end_date'] !== '' ? $form['agreement_end_date'] : null,
                $user['id'],
                $user['name'],
                $form['status'],
                $user['id'],
            ]);

            $pk = (int) db()->lastInsertId();
            write_audit('customers', $code, 'CREATE', null, 'Direct existing customer');
            flash('success', 'Existing customer added: ' . $code, 'create');
            redirect(base_url('modules/customers/view.php?id=' . $pk));
        } catch (Throwable $e) {
            $errors[] = 'Could not create: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Add Existing Customer</h2>
    <p class="step-note"><strong>Skip prospect pipeline</strong> for customers already doing business. Leave Customer Code blank to auto-generate <strong>REO-CUS-xxxx</strong>, or enter the old ReeferON code if known. Duplicate GSTIN / name matches are shown (not auto-merged).</p>

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
                        / <?= e($d['mobile']) ?>
                        / <?= e($d['status']) ?>
                        (<?= e($d['source']) ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <div class="form-grid">
            <div class="form-group">
                <label>Existing Customer Code (optional)</label>
                <input type="text" name="customer_code" maxlength="30" placeholder="Leave blank to auto REO-CUS-xxxx" value="<?= e($form['customer_code']) ?>">
            </div>
            <div class="form-group">
                <label>Status <span class="required">*</span></label>
                <select name="status" required>
                    <?php foreach (customer_statuses() as $s): ?>
                        <option value="<?= e($s) ?>" <?= $form['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Customer Name <span class="required">*</span></label>
                <input type="text" name="customer_name" required value="<?= e($form['customer_name']) ?>">
            </div>
            <div class="form-group">
                <label>Brand Name</label>
                <input type="text" name="brand_name" value="<?= e($form['brand_name']) ?>">
            </div>
            <div class="form-group">
                <label>Business Type <span class="required">*</span></label>
                <?php render_select_with_add('business_type', business_types(), $form['business_type'], [
                    'required' => true,
                    'help' => 'Business type from the fixed list',
                ]); ?>
            </div>
            <div class="form-group">
                <label>Contact Person <span class="required">*</span></label>
                <input type="text" name="contact_person" required value="<?= e($form['contact_person']) ?>">
            </div>
            <div class="form-group">
                <label>Phone number <span class="required">*</span></label>
                <?php render_phone_input('mobile', $form['mobile'], true); ?>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= e($form['email']) ?>">
            </div>
            <div class="form-group">
                <label>GSTIN</label>
                <input type="text" name="gstin" maxlength="20" value="<?= e($form['gstin']) ?>">
            </div>
            <div class="form-group full">
                <label>Registered Address</label>
                <textarea name="registered_address"><?= e($form['registered_address']) ?></textarea>
            </div>
            <div class="form-group full">
                <label>Billing Address</label>
                <textarea name="billing_address"><?= e($form['billing_address']) ?></textarea>
            </div>
            <div class="form-group full">
                <label>Operating Location</label>
                <textarea name="operating_location"><?= e($form['operating_location']) ?></textarea>
            </div>
            <div class="form-group">
                <label>Services</label>
                <input type="text" name="services" value="<?= e($form['services']) ?>">
            </div>
            <div class="form-group">
                <label>Temperature</label>
                <?php render_select_with_add('temperature_requirement', temperature_options(), $form['temperature_requirement'], [
                    'help' => 'Cold-chain temperature',
                ]); ?>
            </div>
            <div class="form-group">
                <label>Commercial Details</label>
                <input type="text" name="commercial_details" value="<?= e($form['commercial_details']) ?>">
            </div>
            <div class="form-group">
                <label>Payment Terms</label>
                <input type="text" name="payment_terms" value="<?= e($form['payment_terms']) ?>">
            </div>
            <div class="form-group">
                <label>Credit Limit (₹)</label>
                <input type="number" step="0.01" name="credit_limit" value="<?= e($form['credit_limit']) ?>">
            </div>
            <div class="form-group">
                <label>Agreement Start</label>
                <input type="date" name="agreement_start_date" value="<?= e($form['agreement_start_date']) ?>">
            </div>
            <div class="form-group">
                <label>Agreement End</label>
                <input type="date" name="agreement_end_date" value="<?= e($form['agreement_end_date']) ?>">
            </div>
        </div>

        <?php if ($dupMatches): ?>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="confirm_duplicate" value="1">
                    I reviewed possible duplicates and still want to create this customer
                </label>
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn">Add Customer</button>
            <a class="btn btn-secondary" href="<?= e(base_url('modules/customers/index.php')) ?>">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
