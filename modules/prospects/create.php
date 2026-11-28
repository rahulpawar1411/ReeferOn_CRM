<?php
/**
 * Create prospect
 *
 * Form to add a new sales lead (company, contact, service needs).
 * Permission: prospects.create
 */
$pageTitle = 'Create Prospect';
$currentPage = 'prospect_create';
require_once __DIR__ . '/../../includes/functions.php';
require_perm('prospects.create');

$user = current_user();
$errors = [];

// Save new prospect on form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $mobile = phone_digits($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $business_type = sanitize_option_value($_POST['business_type'] ?? '');
    $industry = trim($_POST['industry'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $service_required = sanitize_option_value($_POST['service_required'] ?? '');
    $temperature_requirement = sanitize_option_value($_POST['temperature_requirement'] ?? '', 50);
    $requirement_details = trim($_POST['requirement_details'] ?? '');
    $estimated_monthly_business = trim($_POST['estimated_monthly_business'] ?? '');
    $lead_source = sanitize_option_value($_POST['lead_source'] ?? '');
    $next_followup_date = trim($_POST['next_followup_date'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');

    if ($company_name === '') $errors[] = 'Company Name is required.';
    if ($contact_person === '') $errors[] = 'Contact Person is required.';
    $phoneErr = phone_error($mobile, true);
    if ($phoneErr) $errors[] = $phoneErr;
    foreach ([
        option_value_error($business_type, business_types(), 'Business Type', true, null, 'business_type'),
        option_value_error($service_required, service_required_options(), 'Service Required', true, null, 'service_required'),
        option_value_error($temperature_requirement, temperature_options(), 'Temperature Requirement', true, null, 'temperature_requirement'),
        option_value_error($lead_source, lead_source_options(), 'Lead Source', true, null, 'lead_source'),
    ] as $optErr) {
        if ($optErr) $errors[] = $optErr;
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email ID is invalid.';
    if ($estimated_monthly_business !== '' && !is_numeric($estimated_monthly_business)) {
        $errors[] = 'Estimated Monthly Business must be a number.';
    }

    // Basic duplicate check: same company + mobile
    if (!$errors) {
        $dup = db()->prepare(
            "SELECT prospect_id FROM prospects
             WHERE status = 'Active' AND company_name = ? AND mobile = ?
             LIMIT 1"
        );
        $dup->execute([$company_name, $mobile]);
        $existing = $dup->fetch();
        if ($existing) {
            $errors[] = 'Possible duplicate: active prospect ' . $existing['prospect_id'] . ' already exists for this company + mobile.';
        }
    }

    if (!$errors) {
        try {
            $pdo = db();
            // Sequence uses its own short transaction — call before outer transaction
            $prospectCode = next_business_id('prospect');
            $today = date('Y-m-d');

            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "INSERT INTO prospects (
                    prospect_id, lead_creation_date, company_name, contact_person, designation,
                    mobile, email, business_type, industry, city, service_required,
                    temperature_requirement, requirement_details, estimated_monthly_business,
                    lead_source, salesperson_id, salesperson_name, pipeline_stage,
                    next_followup_date, remarks, status, created_by
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, 'New Prospect',
                    ?, ?, 'Active', ?
                )"
            );

            $stmt->execute([
                $prospectCode,
                $today,
                $company_name,
                $contact_person,
                $designation !== '' ? $designation : null,
                $mobile,
                $email !== '' ? $email : null,
                $business_type,
                $industry !== '' ? $industry : null,
                $city !== '' ? $city : null,
                $service_required,
                $temperature_requirement,
                $requirement_details !== '' ? $requirement_details : null,
                $estimated_monthly_business !== '' ? $estimated_monthly_business : null,
                $lead_source,
                $user['id'],
                $user['name'],
                $next_followup_date !== '' ? $next_followup_date : null,
                $remarks !== '' ? $remarks : null,
                $user['id'],
            ]);

            $pk = (int) $pdo->lastInsertId();

            $hist = $pdo->prepare(
                "INSERT INTO prospect_stage_history (prospect_pk, from_stage, to_stage, remarks, updated_by, updated_by_name)
                 VALUES (?, NULL, 'New Prospect', 'Prospect created', ?, ?)"
            );
            $hist->execute([$pk, $user['id'], $user['name']]);

            write_audit('prospects', $prospectCode, 'CREATE', null, $company_name);

            $pdo->commit();
            flash('success', 'Prospect created: ' . $prospectCode, 'create');
            redirect(base_url('modules/prospects/view.php?id=' . $pk));
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            $errors[] = 'Could not save prospect: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>New Sales Prospect</h2>
    <p class="step-note"><strong>Step 1 – Create Lead:</strong> Add a new company/lead here. Prospect ID is generated automatically (REO-LEAD-xxxx). Update pipeline stage and follow-ups later from the prospect page.</p>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?>
                <div><?= e($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <div class="form-grid">
            <div class="form-group">
                <label>Lead Creation Date</label>
                <input type="text" value="<?= e(date('Y-m-d')) ?>" readonly>
            </div>
            <div class="form-group">
                <!-- Always the logged-in user who is creating this lead -->
                <label>Salesperson Name</label>
                <input type="text" value="<?= e($user['name']) ?>" readonly>
            </div>

            <div class="form-group">
                <label>Company Name <span class="required">*</span></label>
                <?php render_input_with_help('company_name', [
                    'required' => true,
                    'value' => $_POST['company_name'] ?? '',
                    'help' => 'Legal / trading company name',
                    'placeholder' => 'e.g. CoolTreat Foods Pvt Ltd',
                ]); ?>
            </div>
            <div class="form-group">
                <label>Contact Person <span class="required">*</span></label>
                <?php render_input_with_help('contact_person', [
                    'required' => true,
                    'value' => $_POST['contact_person'] ?? '',
                    'help' => 'Primary decision-maker or contact name',
                    'placeholder' => 'Full name',
                ]); ?>
            </div>

            <div class="form-group">
                <label>Designation</label>
                <?php render_input_with_help('designation', [
                    'value' => $_POST['designation'] ?? '',
                    'help' => 'Job title, e.g. Purchase Manager',
                    'placeholder' => 'e.g. Manager',
                ]); ?>
            </div>
            <div class="form-group">
                <label>Phone number <span class="required">*</span></label>
                <?php render_phone_input('mobile', $_POST['mobile'] ?? '', true); ?>
            </div>

            <div class="form-group">
                <label>Email ID</label>
                <?php render_input_with_help('email', [
                    'type' => 'email',
                    'value' => $_POST['email'] ?? '',
                    'help' => 'Official email for this contact',
                    'placeholder' => 'name@company.com',
                ]); ?>
            </div>
            <div class="form-group">
                <label>Business Type <span class="required">*</span></label>
                <?php render_select_with_add('business_type', business_types(), $_POST['business_type'] ?? '', [
                    'required' => true,
                    'help' => 'Business type from the fixed list',
                ]); ?>
            </div>

            <div class="form-group">
                <label>Industry</label>
                <?php render_input_with_help('industry', [
                    'value' => $_POST['industry'] ?? '',
                    'help' => 'Industry segment, e.g. Dairy, Pharma',
                ]); ?>
            </div>
            <div class="form-group">
                <label>City</label>
                <?php render_input_with_help('city', [
                    'value' => $_POST['city'] ?? '',
                    'help' => 'City where the customer operates',
                    'placeholder' => 'e.g. Pune',
                ]); ?>
            </div>

            <div class="form-group">
                <label>Service Required <span class="required">*</span></label>
                <?php render_select_with_add('service_required', service_required_options(), $_POST['service_required'] ?? '', [
                    'required' => true,
                    'help' => 'Main service needed',
                ]); ?>
            </div>
            <div class="form-group">
                <label>Temperature Requirement <span class="required">*</span></label>
                <?php render_select_with_add('temperature_requirement', temperature_options(), $_POST['temperature_requirement'] ?? '', [
                    'required' => true,
                    'help' => 'Cold-chain temperature band',
                ]); ?>
            </div>

            <div class="form-group">
                <label>Estimated Monthly Business (₹)</label>
                <?php render_input_with_help('estimated_monthly_business', [
                    'type' => 'number',
                    'step' => '0.01',
                    'value' => $_POST['estimated_monthly_business'] ?? '',
                    'help' => 'Expected monthly revenue in rupees',
                    'placeholder' => 'e.g. 150000',
                ]); ?>
            </div>
            <div class="form-group">
                <label>Lead Source <span class="required">*</span></label>
                <?php render_select_with_add('lead_source', lead_source_options(), $_POST['lead_source'] ?? '', [
                    'required' => true,
                    'help' => 'How this lead came in',
                ]); ?>
            </div>

            <div class="form-group">
                <label>Next Follow-up Date</label>
                <?php render_input_with_help('next_followup_date', [
                    'type' => 'date',
                    'value' => $_POST['next_followup_date'] ?? '',
                    'help' => 'When sales should call or meet next',
                ]); ?>
            </div>

            <div class="form-group full">
                <label>Requirement Details</label>
                <?php render_input_with_help('requirement_details', [
                    'type' => 'textarea',
                    'value' => $_POST['requirement_details'] ?? '',
                    'help' => 'Lanes, volume, vehicle, SLA, or special needs',
                    'rows' => 4,
                ]); ?>
            </div>
            <div class="form-group full">
                <label>Remarks</label>
                <?php render_input_with_help('remarks', [
                    'type' => 'textarea',
                    'value' => $_POST['remarks'] ?? '',
                    'help' => 'Internal notes for the sales team',
                    'rows' => 3,
                ]); ?>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">Save Prospect</button>
            <a class="btn btn-secondary" href="<?= e(base_url('modules/prospects/index.php')) ?>">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
