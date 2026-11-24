<?php
$pageTitle = 'Create Prospect';
$currentPage = 'prospect_create';
require_once __DIR__ . '/../../includes/functions.php';

$user = current_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $business_type = $_POST['business_type'] ?? '';
    $industry = trim($_POST['industry'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $service_required = $_POST['service_required'] ?? '';
    $temperature_requirement = $_POST['temperature_requirement'] ?? '';
    $requirement_details = trim($_POST['requirement_details'] ?? '');
    $estimated_monthly_business = trim($_POST['estimated_monthly_business'] ?? '');
    $lead_source = $_POST['lead_source'] ?? '';
    $next_followup_date = trim($_POST['next_followup_date'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');

    if ($company_name === '') $errors[] = 'Company Name is required.';
    if ($contact_person === '') $errors[] = 'Contact Person is required.';
    if ($mobile === '') $errors[] = 'Mobile Number is required.';
    if ($business_type === '') $errors[] = 'Business Type is required.';
    if ($service_required === '') $errors[] = 'Service Required is required.';
    if ($temperature_requirement === '') $errors[] = 'Temperature Requirement is required.';
    if ($lead_source === '') $errors[] = 'Lead Source is required.';
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
            flash('success', 'Prospect created: ' . $prospectCode);
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
    <p>Prospect ID will be generated automatically (REO-LEAD-0001…).</p>

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
                <label>Salesperson Name</label>
                <input type="text" value="<?= e($user['name']) ?>" readonly>
            </div>

            <div class="form-group">
                <label>Company Name <span class="required">*</span></label>
                <input type="text" name="company_name" required value="<?= e($_POST['company_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Contact Person <span class="required">*</span></label>
                <input type="text" name="contact_person" required value="<?= e($_POST['contact_person'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Designation</label>
                <input type="text" name="designation" value="<?= e($_POST['designation'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Mobile Number <span class="required">*</span></label>
                <input type="text" name="mobile" required value="<?= e($_POST['mobile'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Email ID</label>
                <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Business Type <span class="required">*</span></label>
                <select name="business_type" required>
                    <option value="">Select</option>
                    <?php foreach (['QSR','FMCG','Ice Cream','Retail','Other'] as $opt): ?>
                        <option value="<?= e($opt) ?>" <?= (($_POST['business_type'] ?? '') === $opt) ? 'selected' : '' ?>><?= e($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Industry</label>
                <input type="text" name="industry" value="<?= e($_POST['industry'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>City</label>
                <input type="text" name="city" value="<?= e($_POST['city'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Service Required <span class="required">*</span></label>
                <select name="service_required" required>
                    <option value="">Select</option>
                    <?php foreach (['Transportation','Warehousing','EV','Integrated Supply Chain','Other'] as $opt): ?>
                        <option value="<?= e($opt) ?>" <?= (($_POST['service_required'] ?? '') === $opt) ? 'selected' : '' ?>><?= e($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Temperature Requirement <span class="required">*</span></label>
                <select name="temperature_requirement" required>
                    <option value="">Select</option>
                    <?php foreach (['Ambient','Chiller','Frozen'] as $opt): ?>
                        <option value="<?= e($opt) ?>" <?= (($_POST['temperature_requirement'] ?? '') === $opt) ? 'selected' : '' ?>><?= e($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Estimated Monthly Business (₹)</label>
                <input type="number" step="0.01" name="estimated_monthly_business" value="<?= e($_POST['estimated_monthly_business'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Lead Source <span class="required">*</span></label>
                <select name="lead_source" required>
                    <option value="">Select</option>
                    <?php foreach (['Reference','Website','Direct','Other','LinkedIn'] as $opt): ?>
                        <option value="<?= e($opt) ?>" <?= (($_POST['lead_source'] ?? '') === $opt) ? 'selected' : '' ?>><?= e($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Next Follow-up Date</label>
                <input type="date" name="next_followup_date" value="<?= e($_POST['next_followup_date'] ?? '') ?>">
            </div>

            <div class="form-group full">
                <label>Requirement Details</label>
                <textarea name="requirement_details"><?= e($_POST['requirement_details'] ?? '') ?></textarea>
            </div>
            <div class="form-group full">
                <label>Remarks</label>
                <textarea name="remarks"><?= e($_POST['remarks'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">Save Prospect</button>
            <a class="btn btn-secondary" href="<?= e(base_url('modules/prospects/index.php')) ?>">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
