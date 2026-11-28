<?php
/**
 * Edit prospect
 *
 * Update lead details, stage, and status. Stage changes go into history.
 */
$pageTitle = 'Edit Prospect';
$currentPage = 'prospects';
require_once __DIR__ . '/../../includes/functions.php';
require_perm('prospects.edit');

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM prospects WHERE id = ?');
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) {
    flash('error', 'Prospect not found.');
    redirect(base_url('modules/prospects/index.php'));
}

$user = current_user();
$errors = [];

// Save prospect edits on form submit
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
    $pipeline_stage = $_POST['pipeline_stage'] ?? '';
    $next_followup_date = trim($_POST['next_followup_date'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    $status = $_POST['status'] ?? 'Active';

    if ($company_name === '') $errors[] = 'Company Name is required.';
    if ($contact_person === '') $errors[] = 'Contact Person is required.';
    $phoneErr = phone_error($mobile, true);
    if ($phoneErr) $errors[] = $phoneErr;
    foreach ([
        option_value_error($business_type, business_types(), 'Business Type', true, $p['business_type'] ?? null, 'business_type'),
        option_value_error($service_required, service_required_options(), 'Service Required', true, $p['service_required'] ?? null, 'service_required'),
        option_value_error($temperature_requirement, temperature_options(), 'Temperature Requirement', true, $p['temperature_requirement'] ?? null, 'temperature_requirement'),
        option_value_error($lead_source, lead_source_options(), 'Lead Source', true, $p['lead_source'] ?? null, 'lead_source'),
    ] as $optErr) {
        if ($optErr) $errors[] = $optErr;
    }
    if (!in_array($pipeline_stage, pipeline_stages(), true)) $errors[] = 'Invalid pipeline stage.';
    if (!in_array($status, ['Active', 'Inactive'], true)) $errors[] = 'Invalid status.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email ID is invalid.';
    if ($estimated_monthly_business !== '' && !is_numeric($estimated_monthly_business)) {
        $errors[] = 'Estimated Monthly Business must be a number.';
    }

    if (!$errors) {
        try {
            $pdo = db();
            $pdo->beginTransaction();

            $oldStage = $p['pipeline_stage'];

            $upd = $pdo->prepare(
                "UPDATE prospects SET
                    company_name = ?, contact_person = ?, designation = ?, mobile = ?, email = ?,
                    business_type = ?, industry = ?, city = ?, service_required = ?,
                    temperature_requirement = ?, requirement_details = ?, estimated_monthly_business = ?,
                    lead_source = ?, pipeline_stage = ?, next_followup_date = ?, remarks = ?,
                    status = ?, updated_by = ?
                 WHERE id = ?"
            );

            $upd->execute([
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
                $pipeline_stage,
                $next_followup_date !== '' ? $next_followup_date : null,
                $remarks !== '' ? $remarks : null,
                $status,
                $user['id'],
                $id,
            ]);

            if ($oldStage !== $pipeline_stage) {
                $hist = $pdo->prepare(
                    "INSERT INTO prospect_stage_history
                     (prospect_pk, from_stage, to_stage, remarks, updated_by, updated_by_name)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $hist->execute([
                    $id,
                    $oldStage,
                    $pipeline_stage,
                    $remarks !== '' ? $remarks : 'Stage updated from edit form',
                    $user['id'],
                    $user['name'],
                ]);
            }

            write_audit('prospects', $p['prospect_id'], 'UPDATE', $oldStage, $pipeline_stage);

            $pdo->commit();
            flash('success', 'Prospect updated: ' . $p['prospect_id'], 'edit');
            redirect(base_url('modules/prospects/view.php?id=' . $id));
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            $errors[] = 'Could not update: ' . $e->getMessage();
        }
    }

    // Keep posted values on error
    $p = array_merge($p, $_POST);
}

$pageTitle = 'Edit ' . $p['prospect_id'];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Edit Prospect</h2>
    <p class="step-note"><strong>Edit Lead:</strong> Update company and contact details. Prospect ID <strong><?= e($p['prospect_id']) ?></strong> cannot be changed. Prefer <em>Change Stage</em> for pipeline updates (saves remarks in history).</p>

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
                <!-- Set at create time from the creator's profile; not editable here -->
                <label>Salesperson Name</label>
                <input type="text" value="<?= e($p['salesperson_name'] ?? '') ?>" readonly>
            </div>
            <div class="form-group">
                <label>Company Name <span class="required">*</span></label>
                <input type="text" name="company_name" required value="<?= e($p['company_name']) ?>">
            </div>
            <div class="form-group">
                <label>Contact Person <span class="required">*</span></label>
                <input type="text" name="contact_person" required value="<?= e($p['contact_person']) ?>">
            </div>

            <div class="form-group">
                <label>Designation</label>
                <input type="text" name="designation" value="<?= e($p['designation'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Phone number <span class="required">*</span></label>
                <?php render_phone_input('mobile', $p['mobile'] ?? '', true); ?>
            </div>

            <div class="form-group">
                <label>Email ID</label>
                <input type="email" name="email" value="<?= e($p['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Business Type <span class="required">*</span></label>
                <?php render_select_with_add('business_type', business_types(), $p['business_type'], [
                    'required' => true,
                    'help' => 'Business type from the fixed list',
                ]); ?>
            </div>

            <div class="form-group">
                <label>Industry</label>
                <?php render_input_with_help('industry', [
                    'value' => $p['industry'] ?? '',
                    'help' => 'Industry segment, e.g. Dairy, Pharma',
                ]); ?>
            </div>
            <div class="form-group">
                <label>City</label>
                <?php render_input_with_help('city', [
                    'value' => $p['city'] ?? '',
                    'help' => 'City where the customer operates',
                ]); ?>
            </div>

            <div class="form-group">
                <label>Service Required <span class="required">*</span></label>
                <?php render_select_with_add('service_required', service_required_options(), $p['service_required'], [
                    'required' => true,
                    'help' => 'Main service needed',
                ]); ?>
            </div>
            <div class="form-group">
                <label>Temperature Requirement <span class="required">*</span></label>
                <?php render_select_with_add('temperature_requirement', temperature_options(), $p['temperature_requirement'], [
                    'required' => true,
                    'help' => 'Cold-chain temperature band',
                ]); ?>
            </div>

            <div class="form-group">
                <label>Estimated Monthly Business (₹)</label>
                <?php render_input_with_help('estimated_monthly_business', [
                    'type' => 'number',
                    'step' => '0.01',
                    'value' => (string) ($p['estimated_monthly_business'] ?? ''),
                    'help' => 'Expected monthly revenue in rupees',
                ]); ?>
            </div>
            <div class="form-group">
                <label>Lead Source <span class="required">*</span></label>
                <?php render_select_with_add('lead_source', lead_source_options(), $p['lead_source'], [
                    'required' => true,
                    'help' => 'How this lead came in',
                ]); ?>
            </div>

            <div class="form-group">
                <label>Pipeline Stage</label>
                <?php render_select_with_add('pipeline_stage', pipeline_stages(), $p['pipeline_stage'], [
                    'allow_add' => false,
                    'empty' => false,
                    'help' => 'Sales pipeline stage (system list only)',
                ]); ?>
            </div>
            <div class="form-group">
                <label>Next Follow-up Date</label>
                <?php render_input_with_help('next_followup_date', [
                    'type' => 'date',
                    'value' => $p['next_followup_date'] ?? '',
                    'help' => 'When sales should call or meet next',
                ]); ?>
            </div>

            <div class="form-group">
                <label>Record Status</label>
                <?php if (can('prospects.delete')): ?>
                    <?php render_select_with_add('status', ['Active', 'Inactive'], $p['status'], [
                        'allow_add' => false,
                        'empty' => '',
                        'help' => 'Inactive keeps history (needs Inactive permission)',
                    ]); ?>
                <?php else: ?>
                    <input type="hidden" name="status" value="<?= e($p['status']) ?>">
                    <input type="text" value="<?= e($p['status']) ?>" readonly title="Only users with Inactive permission can change this">
                <?php endif; ?>
            </div>

            <div class="form-group full">
                <label>Requirement Details</label>
                <textarea name="requirement_details"><?= e($p['requirement_details'] ?? '') ?></textarea>
            </div>
            <div class="form-group full">
                <label>Remarks</label>
                <textarea name="remarks"><?= e($p['remarks'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">Update Prospect</button>
            <a class="btn btn-secondary" href="<?= e(base_url('modules/prospects/view.php?id=' . $id)) ?>">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
